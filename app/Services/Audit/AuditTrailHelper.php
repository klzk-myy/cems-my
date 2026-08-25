<?php

namespace App\Services\Audit;

use App\Models\AuditTrail;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

/**
 * Helper that records auditable events to both the application audit_trails
 * table and the tamper-evident system_logs stream via AuditService.
 *
 * The dual-write design preserves the existing system_logs chain (hashed,
 * sequential, tamper-evident) while also populating the richer audit_trails
 * table used for business-level querying and reporting.
 *
 * All domain-specific methods (recordTransaction, recordCustomer, etc.)
 * delegate to recordEntity() with an entityType, sealed flag, and the audit
 * service method to call.
 */
class AuditTrailHelper
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * Record a generic audit event to the audit_trails table, with an optional
     * best-effort dual-write to system_logs via AuditService.
     */
    public function record(
        string $auditableType,
        int $auditableId,
        string $action,
        array $metadata = [],
        ?User $user = null,
        ?string $ipAddress = null
    ): AuditTrail {
        return AuditTrail::create([
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'action' => $action,
            'user_id' => $user?->id,
            'metadata' => $metadata,
            'ip_address' => $ipAddress ?? request()?->ip(),
        ]);
    }

    /**
     * Unified dual-write: create an audit_trails row and best-effort log to
     * system_logs via AuditService. Callers should use the domain-specific
     * wrappers (recordTransaction, recordCustomer) for clarity.
     *
     * @param  string  $entityType  'Transaction' or 'Customer'
     * @param  string  $sealed  'log' for async, 'logSealed' for synchronous hash sealing
     */
    public function recordEntity(
        string $entityType,
        int $entityId,
        string $action,
        array $metadata = [],
        ?User $user = null,
        string $severity = 'INFO',
        ?string $ipAddress = null,
        string $sealed = 'log'
    ): AuditTrail {
        $auditTrail = $this->record($entityType, $entityId, $action, $metadata, $user, $ipAddress);

        try {
            $method = $sealed === 'logSealed'
                ? "log{$entityType}Sealed"
                : "log{$entityType}";

            $this->auditService->{$method}($action, $entityId, [
                'old' => $metadata['old'] ?? [],
                'new' => $metadata['new'] ?? [],
                'severity' => $severity,
                'user_id' => $user?->id,
                'ip_address' => $ipAddress,
            ]);
        } catch (\Exception $e) {
            Log::error("AuditService {$entityType} {$sealed} write failed", [
                'action' => $action,
                "{$entityType}_id" => $entityId,
                'exception' => $e->getMessage(),
            ]);
        }

        return $auditTrail;
    }

    public function recordTransaction(
        int $transactionId,
        string $action,
        array $metadata = [],
        ?User $user = null,
        string $severity = 'INFO',
        ?string $ipAddress = null
    ): AuditTrail {
        return $this->recordEntity('Transaction', $transactionId, $action, $metadata, $user, $severity, $ipAddress, 'log');
    }

    public function recordTransactionSealed(
        int $transactionId,
        string $action,
        array $metadata = [],
        ?User $user = null,
        string $severity = 'INFO',
        ?string $ipAddress = null
    ): AuditTrail {
        return $this->recordEntity('Transaction', $transactionId, $action, $metadata, $user, $severity, $ipAddress, 'logSealed');
    }

    public function recordCustomer(
        int $customerId,
        string $action,
        array $metadata = [],
        ?User $user = null,
        string $severity = 'INFO',
        ?string $ipAddress = null
    ): AuditTrail {
        return $this->recordEntity('Customer', $customerId, $action, $metadata, $user, $severity, $ipAddress, 'log');
    }

    public function recordCustomerSealed(
        int $customerId,
        string $action,
        array $metadata = [],
        ?User $user = null,
        string $severity = 'INFO',
        ?string $ipAddress = null
    ): AuditTrail {
        return $this->recordEntity('Customer', $customerId, $action, $metadata, $user, $severity, $ipAddress, 'logSealed');
    }
}
