<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function log(
        string $action,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $oldValues = [],
        array $newValues = []
    ): SystemLog {
        return SystemLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => !empty($oldValues) ? $oldValues : null,
            'new_values' => !empty($newValues) ? $newValues : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function logTransaction(
        string $action,
        int $transactionId,
        array $data = []
    ): SystemLog {
        return $this->log(
            $action,
            null,
            'Transaction',
            $transactionId,
            $data['old'] ?? [],
            $data['new'] ?? []
        );
    }

    public function logCustomer(
        string $action,
        int $customerId,
        array $data = []
    ): SystemLog {
        return $this->log(
            $action,
            null,
            'Customer',
            $customerId,
            $data['old'] ?? [],
            $data['new'] ?? []
        );
    }
}
