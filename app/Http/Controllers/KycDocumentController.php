<?php

namespace App\Http\Controllers;

use App\Models\CustomerDocument;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KycDocumentController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * Verify a customer document.
     */
    public function verify(Request $request, CustomerDocument $customerDocument): JsonResponse
    {
        $customerDocument->update([
            'status' => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $this->auditService->logWithSeverity(
            'kyc_document_verified',
            "KYC document #{$customerDocument->id} verified",
            'info',
            ['document_id' => $customerDocument->id, 'customer_id' => $customerDocument->customer_id]
        );

        return response()->json(['success' => true, 'message' => 'Document verified']);
    }

    /**
     * Reject a customer document with a reason.
     */
    public function reject(Request $request, CustomerDocument $customerDocument): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $customerDocument->update([
            'status' => 'rejected',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $this->auditService->logWithSeverity(
            'kyc_document_rejected',
            "KYC document #{$customerDocument->id} rejected",
            'warning',
            ['document_id' => $customerDocument->id, 'customer_id' => $customerDocument->customer_id, 'reason' => $validated['reason']]
        );

        return response()->json(['success' => true, 'message' => 'Document rejected']);
    }

    /**
     * Download a customer document.
     */
    public function download(CustomerDocument $customerDocument)
    {
        if (! $customerDocument->file_path) {
            abort(404, 'Document file not found');
        }

        return Storage::disk('local')->download($customerDocument->file_path);
    }
}
