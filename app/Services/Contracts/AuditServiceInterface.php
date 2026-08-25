<?php

namespace App\Services\Contracts;

use App\Models\SystemLog;

interface AuditServiceInterface
{
    /**
     * Unified audit logging entry point. Replaces all former Audits* concern
     * trait methods; domain-specific wrappers delegate to this.
     */
    public function logAction(
        string $action,
        string $entityType,
        ?int $entityId,
        array $data = [],
        string $severity = ''
    ): SystemLog;

    public function computeEntryHash(
        string $timestamp,
        ?int $userId,
        string $action,
        ?string $entityType,
        ?int $entityId,
        ?string $previousHash
    ): string;

    public function logWithSeverity(
        string $action,
        array $data = [],
        string $severity = 'INFO'
    ): SystemLog;

    public function logWithSeveritySealed(
        string $action,
        array $data = [],
        string $severity = 'INFO'
    ): SystemLog;

    public function log(
        string $action,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $oldValues = [],
        array $newValues = []
    ): SystemLog;

    public function logTransaction(
        string $action,
        int $transactionId,
        array $data = []
    ): SystemLog;

    public function logTransactionSealed(
        string $action,
        int $transactionId,
        array $data = []
    ): SystemLog;

    public function logCustomer(
        string $action,
        int $customerId,
        array $data = []
    ): SystemLog;

    public function logCustomerSealed(
        string $action,
        int $customerId,
        array $data = []
    ): SystemLog;

    public function logComplianceDecision(string $action, int $entityId, array $data = [], string $severity = 'INFO'): SystemLog;

    public function logCustomerRiskEvent(string $action, int $customerId, array $data = []): SystemLog;

    public function logAmlMonitorEvent(string $action, ?int $entityId = null, array $data = []): SystemLog;

    public function logSanctionEvent(string $action, ?int $entityId = null, array $data = []): SystemLog;

    public function logBatch(array $logs): bool;

    public function verifyChainIntegrity(?int $limit = null): array;

    public function getUnsealedCount(): int;
}
