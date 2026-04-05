<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\SystemLog;
use App\Services\EncryptionService;
use App\Services\RiskRatingService;
use App\Services\SanctionScreeningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * CustomerController
 *
 * Handles customer onboarding and management operations.
 * Provides CRUD operations for customer data with KYC document management.
 */
class CustomerController extends Controller
{
    public function __construct(
        protected EncryptionService $encryptionService,
        protected SanctionScreeningService $sanctionService,
        protected RiskRatingService $riskRatingService
    ) {}

    /**
     * Display a paginated listing of all customers.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Search by name
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where('full_name', 'like', "%{$search}%");
        }

        // Filter by ID number (partial match on decrypted)
        if ($request->has('id_number') && ! empty($request->id_number)) {
            // Note: For encrypted fields, we would need to search differently
            // This is a simplified search
            $query->where('id_number_encrypted', 'like', "%{$request->id_number}%");
        }

        // Filter by risk rating
        if ($request->has('risk_rating') && ! empty($request->risk_rating)) {
            $query->where('risk_rating', $request->risk_rating);
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        // Filter by PEP status
        if ($request->has('pep_status')) {
            $query->where('pep_status', $request->pep_status === '1');
        }

        // Filter by nationality
        if ($request->has('nationality') && ! empty($request->nationality)) {
            $query->where('nationality', $request->nationality);
        }

        // Order by
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $customers = $query->paginate(20)->withQueryString();

        // Get filter options
        $riskRatings = ['Low', 'Medium', 'High'];
        $nationalities = Customer::distinct()->pluck('nationality')->sort()->toArray();

        return view('customers.index', compact(
            'customers',
            'riskRatings',
            'nationalities'
        ));
    }

    /**
     * Show the form for creating a new customer.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $idTypes = [
            'MyKad' => 'MyKad (Malaysian IC)',
            'Passport' => 'Passport',
            'Others' => 'Other ID',
        ];

        $riskRatings = ['Low', 'Medium', 'High'];

        // Common nationalities
        $nationalities = [
            'Malaysian',
            'Singaporean',
            'Indonesian',
            'Thai',
            'Filipino',
            'Vietnamese',
            'Chinese',
            'Indian',
            'Bangladeshi',
            'Pakistani',
            'Other',
        ];

        return view('customers.create', compact(
            'idTypes',
            'riskRatings',
            'nationalities'
        ));
    }

    /**
     * Store a newly created customer in the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'id_type' => ['required', 'in:MyKad,Passport,Others'],
            'id_number' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($request) {
                    // MyKad validation: 12 digits in format XXXXXX-XX-XXXX
                    if ($request->id_type === 'MyKad') {
                        if (! preg_match('/^\d{6}-\d{2}-\d{4}$/', $value)) {
                            $fail('MyKad ID must be in format XXXXXX-XX-XXXX (e.g., 900123-01-2345)');
                        }
                    }
                },
            ],
            'date_of_birth' => 'required|date|before:today',
            'nationality' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+?6?01)[0-9]{7,9}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'pep_status' => 'sometimes|boolean',
            'risk_rating' => ['required', 'in:Low,Medium,High'],
            'occupation' => 'nullable|string|max:255',
            'employer_name' => 'nullable|string|max:255',
            'employer_address' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // Encrypt sensitive fields
            $encryptedIdNumber = $this->encryptionService->encrypt($validated['id_number']);
            $encryptedAddress = ! empty($validated['address'])
                ? $this->encryptionService->encrypt($validated['address'])
                : null;
            $encryptedPhone = ! empty($validated['phone'])
                ? $this->encryptionService->encrypt($validated['phone'])
                : null;

            // Create customer
            $customer = Customer::create([
                'full_name' => $validated['full_name'],
                'id_type' => $validated['id_type'],
                'id_number_encrypted' => $encryptedIdNumber,
                'date_of_birth' => $validated['date_of_birth'],
                'nationality' => $validated['nationality'],
                'address' => $encryptedAddress,
                'phone' => $encryptedPhone,
                'email' => $validated['email'] ?? null,
                'pep_status' => $validated['pep_status'] ?? false,
                'risk_rating' => $validated['risk_rating'],
                'risk_score' => $this->calculateInitialRiskScore($validated),
                'occupation' => $validated['occupation'] ?? null,
                'employer_name' => $validated['employer_name'] ?? null,
                'employer_address' => $encryptedAddress ?? null,
                'is_active' => true,
            ]);

            // Screen against sanctions list
            $sanctionMatches = $this->sanctionService->screenName($validated['full_name']);
            $hasSanctionHit = ! empty($sanctionMatches);

            // Update sanction status and risk rating if hit found
            if ($hasSanctionHit) {
                $customer->update([
                    'risk_rating' => 'High',
                    'sanction_hit' => true,
                ]);
                // Log sanction hit
                SystemLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'customer_sanction_hit',
                    'severity' => 'WARNING',
                    'entity_type' => 'Customer',
                    'entity_id' => $customer->id,
                    'new_values' => [
                        'customer_name' => $customer->full_name,
                        'sanction_matches' => $sanctionMatches,
                    ],
                    'ip_address' => $request->ip(),
                ]);
            }

            // Initial risk assessment using RiskRatingService
            $riskAssessment = $this->riskRatingService->assessCustomer($customer, auth()->id());

            // Log customer creation
            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'customer_created',
                'entity_type' => 'Customer',
                'entity_id' => $customer->id,
                'new_values' => [
                    'full_name' => $customer->full_name,
                    'id_type' => $customer->id_type,
                    'nationality' => $customer->nationality,
                    'risk_rating' => $customer->risk_rating,
                    'pep_status' => $customer->pep_status,
                    'sanction_hit' => $hasSanctionHit,
                ],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            $message = "Customer {$customer->full_name} created successfully.";
            if ($hasSanctionHit) {
                $message .= ' WARNING: Sanction match(es) found - customer flagged as High Risk.';
            }

            return redirect()->route('customers.show', $customer)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'customer_creation_failed',
                'severity' => 'ERROR',
                'new_values' => ['error' => $e->getMessage()],
                'ip_address' => $request->ip(),
            ]);

            return back()->with('error', 'Failed to create customer: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified customer's profile with transaction history.
     *
     * @return \Illuminate\View\View
     */
    public function show(Customer $customer)
    {
        $customer->load(['documents', 'transactions' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }]);

        // Calculate transaction stats
        $transactionStats = [
            'total_transactions' => $customer->transactions()->count(),
            'total_volume' => $customer->transactions()->sum('amount_local'),
            'avg_transaction' => $customer->transactions()->avg('amount_local') ?? 0,
            'last_transaction' => $customer->last_transaction_at,
        ];

        // Get document status
        $documentStatus = [
            'total' => $customer->documents()->count(),
            'verified' => $customer->documents()->verified()->count(),
            'pending' => $customer->documents()->unverified()->count(),
            'expired' => $customer->documents()->expired()->count(),
        ];

        return view('customers.show', compact(
            'customer',
            'transactionStats',
            'documentStatus'
        ));
    }

    /**
     * Show the form for editing the specified customer.
     *
     * @return \Illuminate\View\View
     */
    public function edit(Customer $customer)
    {
        $idTypes = [
            'MyKad' => 'MyKad (Malaysian IC)',
            'Passport' => 'Passport',
            'Others' => 'Other ID',
        ];

        $riskRatings = ['Low', 'Medium', 'High'];

        $nationalities = [
            'Malaysian',
            'Singaporean',
            'Indonesian',
            'Thai',
            'Filipino',
            'Vietnamese',
            'Chinese',
            'Indian',
            'Bangladeshi',
            'Pakistani',
            'Other',
        ];

        // Decrypt ID number for display
        $decryptedIdNumber = $this->encryptionService->decrypt($customer->id_number_encrypted);

        return view('customers.edit', compact(
            'customer',
            'idTypes',
            'riskRatings',
            'nationalities',
            'decryptedIdNumber'
        ));
    }

    /**
     * Update the specified customer in the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'id_type' => ['required', 'in:MyKad,Passport,Others'],
            'id_number' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->id_type === 'MyKad') {
                        if (! preg_match('/^\d{6}-\d{2}-\d{4}$/', $value)) {
                            $fail('MyKad ID must be in format XXXXXX-XX-XXXX (e.g., 900123-01-2345)');
                        }
                    }
                },
            ],
            'date_of_birth' => 'required|date|before:today',
            'nationality' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+?6?01)[0-9]{7,9}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'pep_status' => 'sometimes|boolean',
            'risk_rating' => ['required', 'in:Low,Medium,High'],
            'occupation' => 'nullable|string|max:255',
            'employer_name' => 'nullable|string|max:255',
            'employer_address' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $oldValues = [
            'full_name' => $customer->full_name,
            'id_type' => $customer->id_type,
            'nationality' => $customer->nationality,
            'risk_rating' => $customer->risk_rating,
            'pep_status' => $customer->pep_status,
            'is_active' => $customer->is_active,
        ];

        DB::beginTransaction();
        try {
            // Encrypt sensitive fields
            $encryptedIdNumber = $this->encryptionService->encrypt($validated['id_number']);
            $encryptedAddress = ! empty($validated['address'])
                ? $this->encryptionService->encrypt($validated['address'])
                : null;
            $encryptedPhone = ! empty($validated['phone'])
                ? $this->encryptionService->encrypt($validated['phone'])
                : null;

            // Update customer
            $customer->update([
                'full_name' => $validated['full_name'],
                'id_type' => $validated['id_type'],
                'id_number_encrypted' => $encryptedIdNumber,
                'date_of_birth' => $validated['date_of_birth'],
                'nationality' => $validated['nationality'],
                'address' => $encryptedAddress,
                'phone' => $encryptedPhone,
                'email' => $validated['email'] ?? null,
                'pep_status' => $validated['pep_status'] ?? false,
                'risk_rating' => $validated['risk_rating'],
                'occupation' => $validated['occupation'] ?? null,
                'employer_name' => $validated['employer_name'] ?? null,
                'employer_address' => $encryptedAddress ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Re-screen sanctions if name changed
            if ($oldValues['full_name'] !== $validated['full_name']) {
                $sanctionMatches = $this->sanctionService->screenName($validated['full_name']);
                if (! empty($sanctionMatches)) {
                    $customer->update(['risk_rating' => 'High']);
                }
            }

            // Re-assess risk
            $this->riskRatingService->assessCustomer($customer, auth()->id());

            // Log customer update
            SystemLog::create([
                'user_id' => auth()->id(),
                'action' => 'customer_updated',
                'entity_type' => 'Customer',
                'entity_id' => $customer->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'full_name' => $customer->full_name,
                    'id_type' => $customer->id_type,
                    'nationality' => $customer->nationality,
                    'risk_rating' => $customer->risk_rating,
                    'pep_status' => $customer->pep_status,
                    'is_active' => $customer->is_active,
                ],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('customers.show', $customer)
                ->with('success', "Customer {$customer->full_name} updated successfully.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to update customer: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified customer from the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, Customer $customer)
    {
        // Check if customer has transactions
        if ($customer->transactions()->exists()) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'Cannot delete customer with existing transactions. Deactivate instead.');
        }

        // Check if customer has unverified documents
        if ($customer->documents()->whereNull('verified_at')->exists()) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'Cannot delete customer with pending KYC documents.');
        }

        $customerName = $customer->full_name;
        $customerId = $customer->id;

        // Soft delete or hard delete based on requirements
        // For compliance, we typically soft delete. Using regular delete for now.
        $customer->delete();

        // Log customer deletion
        SystemLog::create([
            'user_id' => auth()->id(),
            'action' => 'customer_deleted',
            'entity_type' => 'Customer',
            'entity_id' => $customerId,
            'old_values' => ['full_name' => $customerName],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('customers.index')
            ->with('success', "Customer {$customerName} deleted successfully.");
    }

    /**
     * Show the KYC document management form.
     *
     * @return \Illuminate\View\View
     */
    public function kyc(Customer $customer)
    {
        // Only compliance officers and admins can verify documents
        $canVerify = auth()->user()->isComplianceOfficer() || auth()->user()->isAdmin();

        $documentTypes = CustomerDocument::DOCUMENT_TYPES;

        $documents = $customer->documents()->with(['uploader', 'verifier'])->get();

        return view('customers.kyc', compact(
            'customer',
            'documents',
            'documentTypes',
            'canVerify'
        ));
    }

    /**
     * Handle KYC document upload.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function uploadDocument(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'document_type' => ['required', 'in:MyKad_Front,MyKad_Back,Passport,Proof_of_Address,Others'],
            'document_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB max
            'expiry_date' => 'nullable|date|after:today',
        ]);

        $file = $request->file('document_file');

        // Store file with encryption consideration
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('customer-documents/'.$customer->id, $filename, 'local');

        // Calculate file hash for integrity
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Create document record
        $document = CustomerDocument::create([
            'customer_id' => $customer->id,
            'document_type' => $validated['document_type'],
            'file_path' => $path,
            'file_hash' => $fileHash,
            'file_size' => $file->getSize(),
            'encrypted' => true,
            'uploaded_by' => auth()->id(),
            'expiry_date' => $validated['expiry_date'] ?? null,
        ]);

        // Log document upload
        SystemLog::create([
            'user_id' => auth()->id(),
            'action' => 'customer_document_uploaded',
            'entity_type' => 'CustomerDocument',
            'entity_id' => $document->id,
            'new_values' => [
                'customer_id' => $customer->id,
                'document_type' => $document->document_type,
                'file_size' => $document->file_size,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('customers.kyc', $customer)
            ->with('success', 'Document uploaded successfully.');
    }

    /**
     * Verify a KYC document.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyDocument(Request $request, Customer $customer, CustomerDocument $document)
    {
        // Only compliance officers and admins can verify
        if (! auth()->user()->isComplianceOfficer() && ! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Compliance Officer or Admin access required.');
        }

        if ($document->customer_id !== $customer->id) {
            abort(404, 'Document does not belong to this customer.');
        }

        $document->update([
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        // Log verification
        SystemLog::create([
            'user_id' => auth()->id(),
            'action' => 'customer_document_verified',
            'severity' => 'INFO',
            'entity_type' => 'CustomerDocument',
            'entity_id' => $document->id,
            'new_values' => [
                'customer_id' => $customer->id,
                'document_type' => $document->document_type,
                'verified_by' => auth()->id(),
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('customers.kyc', $customer)
            ->with('success', 'Document verified successfully.');
    }

    /**
     * Delete a KYC document.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteDocument(Request $request, Customer $customer, CustomerDocument $document)
    {
        if ($document->customer_id !== $customer->id) {
            abort(404, 'Document does not belong to this customer.');
        }

        // Only uploader, manager, or admin can delete
        $canDelete = auth()->id() === $document->uploaded_by
            || auth()->user()->isManager()
            || auth()->user()->isAdmin();

        if (! $canDelete) {
            abort(403, 'Unauthorized to delete this document.');
        }

        // Delete the file
        if (Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        $documentType = $document->document_type;
        $document->delete();

        // Log document deletion
        SystemLog::create([
            'user_id' => auth()->id(),
            'action' => 'customer_document_deleted',
            'entity_type' => 'CustomerDocument',
            'entity_id' => $document->id,
            'old_values' => [
                'customer_id' => $customer->id,
                'document_type' => $documentType,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('customers.kyc', $customer)
            ->with('success', 'Document deleted successfully.');
    }

    /**
     * Calculate initial risk score based on customer attributes.
     */
    protected function calculateInitialRiskScore(array $data): int
    {
        $score = 0;

        // PEP status
        if (! empty($data['pep_status'])) {
            $score += 40;
        }

        // High-risk nationality check would go here
        // For now, we'll use the RiskRatingService

        return min($score, 100);
    }
}
