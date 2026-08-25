<?php

namespace Tests\Feature\Audit;

use Tests\TestCase;

class TransactionImportIdempotencyTest extends TestCase
{
    /**
     * Test that TransactionImportService includes idempotency check.
     */
    public function test_transaction_import_has_idempotency_check(): void
    {
        $file = base_path('app/Services/Transaction/TransactionImportService.php');
        $this->assertFileExists($file);

        $content = file_get_contents($file);
        $this->assertStringContainsString(
            "\$data['idempotency_key'] = hash('sha256', json_encode(\$data));",
            $content,
            'Should generate idempotency key for every import row'
        );
        $this->assertStringContainsString(
            'createForImport',
            $content,
            'Should delegate dedup to TransactionCreationService via createForImport'
        );
    }
}
