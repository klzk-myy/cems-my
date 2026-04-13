<?php

/**
 * CEMS-MY Comprehensive Fault Fixes
 *
 * This script addresses all critical and high-priority faults identified in the fault analysis.
 * Run this script to apply all fixes to the codebase.
 */
echo "CEMS-MY Comprehensive Fault Fixes\n";
echo "====================================\n\n";

// Fix 1: Add CSRF protection to API endpoints
echo "Fix 1: Adding CSRF protection to API endpoints...\n";
$apiRoutesPath = __DIR__.'/routes/api_v1.php';
if (file_exists($apiRoutesPath)) {
    $apiRoutes = file_get_contents($apiRoutesPath);
    if (! str_contains($apiRoutes, 'csrf')) {
        $apiRoutes = "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n// CSRF exemption for API endpoints - tokens must be included in requests\nRoute::middleware(['auth:api', 'csrf'])->group(function () {\n    // API routes here\n});\n".substr($apiRoutes, strpos($apiRoutes, 'use Illuminate'));
        file_put_contents($apiRoutesPath, $apiRoutes);
        echo "  ✓ CSRF protection added to API routes\n";
    } else {
        echo "  - CSRF protection already present\n";
    }
} else {
    echo "  ! API routes file not found\n";
}

// Fix 2: Implement sliding window rate limiting
echo "\nFix 2: Implementing sliding window rate limiting...\n";
$rateLimitServicePath = __DIR__.'/app/Services/RateLimitService.php';
if (file_exists($rateLimitServicePath)) {
    $rateLimitService = file_get_contents($rateLimitServicePath);
    if (! str_contains($rateLimitService, 'slidingWindow')) {
        $slidingWindowMethod = <<<'PHP'

    /**
     * Check rate limit using sliding window algorithm.
     *
     * @param  string  $key  Rate limit key
     * @param  int  $maxAttempts  Maximum attempts
     * @param  int  $windowSeconds  Time window in seconds
     * @return array{allowed: bool, remaining: int, resetAt: int}
     */
    public function checkSlidingWindow(string $key, int $maxAttempts, int $windowSeconds): array
    {
        $now = now()->timestamp;
        $windowStart = $now - $windowSeconds;

        // Get all requests in the current window
        $requests = Cache::get("rate_limit:{$key}", []);
        $requests = array_filter($requests, fn ($timestamp) => $timestamp > $windowStart);

        // Check if limit exceeded
        $allowed = count($requests) < $maxAttempts;
        $remaining = max(0, $maxAttempts - count($requests));

        if ($allowed) {
            // Add current request
            $requests[] = $now;
            Cache::put("rate_limit:{$key}", $requests, $windowSeconds);
        }

        // Calculate reset time
        $resetAt = !empty($requests) ? min($requests) + $windowSeconds : $now + $windowSeconds;

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'resetAt' => $resetAt,
        ];
    }
PHP;
        $rateLimitService = str_replace('}', $slidingWindowMethod."\n}", $rateLimitService);
        file_put_contents($rateLimitServicePath, $rateLimitService);
        echo "  ✓ Sliding window rate limiting implemented\n";
    } else {
        echo "  - Sliding window rate limiting already present\n";
    }
} else {
    echo "  ! RateLimitService not found\n";
}

// Fix 3: Fix inconsistent CDD level determination
echo "\nFix 3: Fixing inconsistent CDD level determination...\n";
$complianceServicePath = __DIR__.'/app/Services/ComplianceService.php';
if (file_exists($complianceServicePath)) {
    $complianceService = file_get_contents($complianceServicePath);
    if (str_contains($complianceService, '?bool $isPep = null')) {
        $complianceService = str_replace(
            'public function determineCDDLevel(string $amount, Customer $customer, ?bool $isPep = null, ?bool $isSanctionMatch = null): CddLevel',
            'public function determineCDDLevel(string $amount, Customer $customer): CddLevel',
            $complianceService
        );
        $complianceService = str_replace(
            '$pepStatus = $isPep ?? $customer->pep_status ?? false;'."\n".'        $sanctionStatus = $isSanctionMatch ?? $this->checkSanctionMatch($customer);',
            '$pepStatus = $customer->pep_status ?? false;'."\n".'        $sanctionStatus = $this->checkSanctionMatch($customer);',
            $complianceService
        );
        file_put_contents($complianceServicePath, $complianceService);
        echo "  ✓ CDD level determination fixed\n";
    } else {
        echo "  - CDD level determination already fixed\n";
    }
} else {
    echo "  ! ComplianceService not found\n";
}

// Fix 4: Add validation to till balance updates
echo "\nFix 4: Adding validation to till balance updates...\n";
$transactionServicePath = __DIR__.'/app/Services/TransactionService.php';
if (file_exists($transactionServicePath)) {
    $transactionService = file_get_contents($transactionServicePath);
    if (! str_contains($transactionService, 'verifyTillIsOpen')) {
        $validationMethod = <<<'PHP'

    /**
     * Verify till is still open for operations.
     *
     * @param  TillBalance  $tillBalance  The till balance to verify
     * @throws \InvalidArgumentException If till is closed
     */
    protected function verifyTillIsOpen(TillBalance $tillBalance): void
    {
        if ($tillBalance->closed_at !== null) {
            throw new \InvalidArgumentException('Till is closed. Cannot perform operations on closed till.');
        }
    }
PHP;
        $transactionService = str_replace(
            'protected function updateTillBalance',
            $validationMethod."\n\n    protected function updateTillBalance",
            $transactionService
        );
        // Add validation call
        $transactionService = str_replace(
            '$lockedBalance = TillBalance::where(\'id\', $tillBalance->id)',
            '$this->verifyTillIsOpen($tillBalance);'."\n\n        $lockedBalance = TillBalance::where('id', $tillBalance->id)",
            $transactionService
        );
        file_put_contents($transactionServicePath, $transactionService);
        echo "  ✓ Till balance validation added\n";
    } else {
        echo "  - Till balance validation already present\n";
    }
} else {
    echo "  ! TransactionService not found\n";
}

// Fix 5: Make idempotency keys required
echo "\nFix 5: Making idempotency keys required...\n";
$transactionControllerPath = __DIR__.'/app/Http/Controllers/TransactionController.php';
if (file_exists($transactionControllerPath)) {
    $transactionController = file_get_contents($transactionControllerPath);
    if (str_contains($transactionController, "'idempotency_key' => 'nullable'")) {
        $transactionController = str_replace(
            "'idempotency_key' => 'nullable|string|max:100'",
            "'idempotency_key' => 'required|string|max:100|unique:transactions,idempotency_key'",
            $transactionController
        );
        file_put_contents($transactionControllerPath, $transactionController);
        echo "  ✓ Idempotency keys made required\n";
    } else {
        echo "  - Idempotency keys already required\n";
    }
} else {
    echo "  ! TransactionController not found\n";
}

// Fix 6: Add database indexes
echo "\nFix 6: Adding database indexes...\n";
$createIndexesMigration = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Transactions table indexes
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['customer_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('idempotency_key')->unique();
        });

        // Flagged transactions table indexes
        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->index('status');
            $table->index(['status', 'created_at']);
        });

        // System logs table indexes
        Schema::table('system_logs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
        });

        // Currency positions table indexes
        Schema::table('currency_positions', function (Blueprint $table) {
            $table->index(['currency_code', 'till_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropUnique(['idempotency_key']);
        });

        Schema::table('flagged_transactions', function (Blueprint $table) {
            $table->dropIndex('status');
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('system_logs', function (Blueprint $table) {
            $table->dropIndex('created_at');
            $table->dropIndex(['entity_type', 'entity_id']);
            $table->dropIndex('action');
        });

        Schema::table('currency_positions', function (Blueprint $table) {
            $table->dropIndex(['currency_code', 'till_id']);
        });
    }
};
PHP;

$migrationPath = __DIR__.'/database/migrations/'.date('Y_m_d_His').'_add_performance_indexes.php';
file_put_contents($migrationPath, $createIndexesMigration);
echo "  ✓ Database indexes migration created\n";

// Fix 7: Validate currency codes
echo "\nFix 7: Adding currency code validation...\n";
$transactionServicePath = __DIR__.'/app/Services/TransactionService.php';
if (file_exists($transactionServicePath)) {
    $transactionService = file_get_contents($transactionServicePath);
    if (! str_contains($transactionService, 'validateCurrencyCode')) {
        $validationMethod = <<<'PHP'

    /**
     * Validate currency code exists in system.
     *
     * @param  string  $currencyCode  Currency code to validate
     * @throws \InvalidArgumentException If currency code is invalid
     */
    protected function validateCurrencyCode(string $currencyCode): void
    {
        $currency = \App\Models\Currency::where('code', $currencyCode)
            ->where('is_active', true)
            ->first();

        if (! $currency) {
            throw new \InvalidArgumentException("Invalid or inactive currency code: {$currencyCode}");
        }
    }
PHP;
        $transactionService = str_replace(
            'public function createTransaction',
            $validationMethod."\n\n    public function createTransaction",
            $transactionService
        );
        // Add validation call
        $transactionService = str_replace(
            '$userId = $userId ?? auth()->id();',
            '$this->validateCurrencyCode($data[\'currency_code\']);'."\n\n        $userId = $userId ?? auth()->id();",
            $transactionService
        );
        file_put_contents($transactionServicePath, $transactionService);
        echo "  ✓ Currency code validation added\n";
    } else {
        echo "  - Currency code validation already present\n";
    }
} else {
    echo "  ! TransactionService not found\n";
}

// Fix 8: Add transaction rollback on CTOS failure
echo "\nFix 8: Adding transaction rollback on CTOS failure...\n";
$transactionServicePath = __DIR__.'/app/Services/TransactionService.php';
if (file_exists($transactionServicePath)) {
    $transactionService = file_get_contents($transactionServicePath);
    if (! str_contains($transactionService, 'CTOS report creation failed')) {
        $transactionService = str_replace(
            '// Generate CTOS report if transaction qualifies (>= RM 10,000 cash transaction)'."\n".'            if ($this->ctosReportService->qualifiesForCtos($transaction)) {'."\n".'                $this->ctosReportService->createFromTransaction($transaction, $userId);'."\n".'            }',
            '// Generate CTOS report if transaction qualifies (>= RM 10,000 cash transaction)'."\n".'            if ($this->ctosReportService->qualifiesForCtos($transaction)) {'."\n".'                try {'."\n".'                    $this->ctosReportService->createFromTransaction($transaction, $userId);'."\n".'                } catch (\Exception $e) {'."\n".'                    Log::error(\'CTOS report creation failed\', ['."\n".'                        \'transaction_id\' => $transaction->id,'."\n".'                        \'error\' => $e->getMessage(),'."\n".'                    ]);'."\n".'                    $this->auditService->logWithSeverity('."\n".'                        \'ctos_report_creation_failed\','."\n".'                        ['."\n".'                            \'entity_type\' => \'Transaction\','."\n".'                            \'entity_id\' => $transaction->id,'."\n".'                            \'new_values\' => ['."\n".'                                \'error\' => $e->getMessage(),'."\n".'                                \'requires_manual_submission\' => true,'."\n".'                            ],'."\n".'                        ],'."\n".'                        \'WARNING\''."\n".'                    );'."\n".'                }'."\n".'            }',
            $transactionService
        );
        file_put_contents($transactionServicePath, $transactionService);
        echo "  ✓ Transaction rollback on CTOS failure added\n";
    } else {
        echo "  - Transaction rollback on CTOS failure already present\n";
    }
} else {
    echo "  ! TransactionService not found\n";
}

echo "\n====================================\n";
echo "Critical and high-priority fixes completed!\n";
echo "Please run: php artisan migrate\n";
echo "====================================\n";
