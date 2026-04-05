<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log with severity level
     */
    public function logWithSeverity(
        string $action,
        array $data = [],
        string $severity = 'INFO'
    ): SystemLog {
        $userId = $data['user_id'] ?? auth()->id();

        return SystemLog::create([
            'user_id' => $userId,
            'action' => $action,
            'severity' => $severity,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'old_values' => ! empty($data['old_values'] ?? []) ? $data['old_values'] : null,
            'new_values' => ! empty($data['new_values'] ?? []) ? $data['new_values'] : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Log standard action
     */
    public function log(
        string $action,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $oldValues = [],
        array $newValues = []
    ): SystemLog {
        return $this->logWithSeverity(
            $action,
            [
                'user_id' => $userId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ],
            'INFO'
        );
    }

    /**
     * Log transaction action
     */
    public function logTransaction(
        string $action,
        int $transactionId,
        array $data = []
    ): SystemLog {
        $severity = $data['severity'] ?? 'INFO';

        return $this->logWithSeverity(
            $action,
            [
                'entity_type' => 'Transaction',
                'entity_id' => $transactionId,
                'old_values' => $data['old'] ?? [],
                'new_values' => $data['new'] ?? [],
            ],
            $severity
        );
    }

    /**
     * Log customer action
     */
    public function logCustomer(
        string $action,
        int $customerId,
        array $data = []
    ): SystemLog {
        $severity = $data['severity'] ?? 'INFO';

        return $this->logWithSeverity(
            $action,
            [
                'entity_type' => 'Customer',
                'entity_id' => $customerId,
                'old_values' => $data['old'] ?? [],
                'new_values' => $data['new'] ?? [],
            ],
            $severity
        );
    }

    /**
     * Get audit trail with filters
     */
    public function getAuditTrail(array $filters = []): array
    {
        $query = SystemLog::with('user');

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', 'like', '%'.$filters['action'].'%');
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        // Default sort by newest first
        $query->orderBy('created_at', 'desc');

        // Get total count before pagination
        $count = $query->count();

        return [
            'logs' => $query->paginate($filters['per_page'] ?? 50),
            'filters' => $filters,
            'count' => $count,
        ];
    }

    /**
     * Log STR (Suspicious Transaction Report) action for compliance audit trail.
     *
     * @param  string  $action  STR action (str_created, str_submitted, str_approved, etc.)
     * @param  int  $strId  STR Report ID
     * @param  array  $data  Additional data
     * @return SystemLog
     */
    public function logStrAction(string $action, int $strId, array $data = []): SystemLog
    {
        $severity = $data['severity'] ?? 'WARNING';

        return $this->logWithSeverity(
            $action,
            [
                'entity_type' => 'StrReport',
                'entity_id' => $strId,
                'old_values' => $data['old'] ?? [],
                'new_values' => $data['new'] ?? [],
            ],
            $severity
        );
    }

    /**
     * Log compliance decision action (flag resolved, EDD decision, etc.).
     *
     * @param  string  $action  Action type
     * @param  int  $entityId  Entity ID (flag ID, transaction ID, etc.)
     * @param  array  $data  Decision data including old/new values
     * @param  string  $severity  Log severity level
     * @return SystemLog
     */
    public function logComplianceDecision(string $action, int $entityId, array $data = [], string $severity = 'INFO'): SystemLog
    {
        return $this->logWithSeverity(
            $action,
            [
                'entity_type' => $data['entity_type'] ?? 'Compliance',
                'entity_id' => $entityId,
                'old_values' => $data['old'] ?? [],
                'new_values' => $data['new'] ?? [],
            ],
            $severity
        );
    }

    /**
     * Log CDD/EDD decision for a transaction.
     *
     * @param  int  $transactionId  Transaction ID
     * @param  string  $cddLevel  CDD level determined
     * @param  array  $triggers  What triggered the CDD level
     * @return SystemLog
     */
    public function logCddDecision(int $transactionId, string $cddLevel, array $triggers = []): SystemLog
    {
        return $this->logWithSeverity(
            'cdd_decision',
            [
                'entity_type' => 'Transaction',
                'entity_id' => $transactionId,
                'new_values' => [
                    'cdd_level' => $cddLevel,
                    'triggers' => $triggers,
                ],
            ],
            'INFO'
        );
    }

    /**
     * Export audit log to CSV or PDF
     */
    public function exportAuditLog(string $dateFrom, string $dateTo, string $format = 'CSV')
    {
        $query = SystemLog::with('user')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->orderBy('created_at', 'desc');

        $logs = $query->get();

        if ($format === 'CSV') {
            return $this->generateCsvExport($logs, $dateFrom, $dateTo);
        }

        // For PDF, return the logs for rendering
        return $logs;
    }

    /**
     * Generate CSV export
     */
    protected function generateCsvExport($logs, string $dateFrom, string $dateTo): array
    {
        $filename = "audit_log_{$dateFrom}_to_{$dateTo}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Timestamp',
                'User',
                'Action',
                'Severity',
                'Entity Type',
                'Entity ID',
                'IP Address',
                'User Agent',
                'Session ID',
                'Old Values',
                'New Values',
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user?->username ?? 'System',
                    $log->action,
                    $log->severity ?? 'INFO',
                    $log->entity_type ?? 'N/A',
                    $log->entity_id ?? 'N/A',
                    $log->ip_address,
                    $log->user_agent ?? 'N/A',
                    $log->session_id ?? 'N/A',
                    json_encode($log->old_values ?? []),
                    json_encode($log->new_values ?? []),
                ]);
            }

            fclose($file);
        };

        return ['callback' => $callback, 'headers' => $headers];
    }
}
