<?php

namespace App\Enums;

enum ErrorType: string
{
    case Validation = 'validation';
    case Processing = 'processing';
    case System = 'system';
    case Data = 'data';

    // Concrete error types written by TransactionErrorHandler and the factory.
    // These are the values actually persisted in transaction_errors.error_type.
    case ProcessingError = 'processing_error';
    case ValidationError = 'validation_error';
    case ComplianceError = 'compliance_error';
    case AccountingError = 'accounting_error';
    case StockError = 'stock_error';
    case NetworkError = 'network_error';
    case DeadlockError = 'deadlock_error';
    case TimeoutError = 'timeout_error';

    public function label(): string
    {
        return match ($this) {
            self::Validation, self::ValidationError => 'Validation',
            self::Processing, self::ProcessingError, self::AccountingError, self::StockError => 'Processing',
            self::ComplianceError => 'Compliance',
            self::System, self::NetworkError, self::DeadlockError, self::TimeoutError => 'System',
            self::Data => 'Data',
        };
    }

    /**
     * Whether failures of this type should be retried instead of sent to the DLQ.
     */
    public function isRetryable(): bool
    {
        return ! in_array($this, [self::Validation, self::ValidationError, self::ComplianceError], true);
    }
}
