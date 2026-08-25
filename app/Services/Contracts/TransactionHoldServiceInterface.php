<?php

namespace App\Services\Contracts;

use App\Enums\CddLevel;

interface TransactionHoldServiceInterface
{
    /**
     * Determine if a transaction requires a hold based on CDD level and risk flags.
     *
     * @param  CddLevel  $cddLevel  The determined CDD level
     * @param  array  $riskFlags  Risk flags from historical analysis (each flag has 'type', 'severity', etc.)
     * @return bool True if hold required, false otherwise
     */
    public function requiresHold(CddLevel $cddLevel, array $riskFlags = []): bool;

    /**
     * Get hold reasons for audit logging.
     *
     * @return array<string> List of reasons for hold
     */
    public function getHoldReasons(CddLevel $cddLevel, array $riskFlags = []): array;
}
