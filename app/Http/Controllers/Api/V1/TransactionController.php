<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Domain\DomainException;
use App\Exceptions\Domain\TransactionBlockedException;
use App\Http\Concerns\BranchScopedQuery;
use App\Http\Controllers\Api\V1\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transaction\StoreTransactionRequest;
use App\Http\Requests\Api\V1\TransactionIndexRequest;
use App\Http\Resources\Api\V1\TransactionCollection;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Models\Transaction;
use App\Services\Contracts\TransactionCreationServiceInterface;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    use ApiResponse, BranchScopedQuery;

    public function __construct(
        protected TransactionCreationServiceInterface $creationService
    ) {}

    /**
     * Display a paginated list of transactions.
     */
    public function index(TransactionIndexRequest $request): TransactionCollection
    {
        $perPage = $request->get('per_page', 20);
        $query = Transaction::with(['customer', 'user', 'branch']);

        $transactions = $this->scopeByBranch($query)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->resourceWithSuccess(new TransactionCollection($transactions), 'Transactions retrieved successfully.');
    }

    /**
     * Store a new transaction.
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $ipAddress = $request->ip();

        try {
            $transaction = $this->creationService->prepareAndCreate($validated, auth()->id(), $ipAddress);

            $transaction->load(['customer', 'user', 'approver']);

            return $this->resourceResponse(
                new TransactionResource($transaction),
                'Transaction created successfully.',
                201
            );
        } catch (TransactionBlockedException $e) {
            return $this->errorResponse('Transaction blocked due to compliance restrictions.', ['reason' => 'blocked'], 403);
        } catch (DomainException $e) {
            return $this->errorResponse('Transaction validation failed.', [], $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Transaction failed. Please contact support.', $e);
        }
    }

    /**
     * Display a single transaction.
     */
    public function show(int $id): TransactionResource
    {
        $transaction = $this->scopeByBranch(Transaction::with(['customer', 'user', 'approver', 'flags']))
            ->findOrFail($id);

        $this->authorize('view', $transaction);

        return $this->resourceWithSuccess(new TransactionResource($transaction), 'Transaction retrieved successfully.');
    }
}
