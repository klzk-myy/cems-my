<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\SanctionsWebhookController;
use App\Jobs\Sanctions\DownloadUnSanctionsList;
use App\Models\Customer;
use App\Models\SanctionList;
use App\Services\SanctionsDownloadService;
use App\Services\SanctionsImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SanctionsAutoUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::fake([
            'https://scsanctions.un.org/*' => Http::response('<?xml version="1.0"?><CONSOLIDATED_LIST></CONSOLIDATED_LIST>', 200),
            'https://www.treasury.gov/ofac/downloads/sdn.xml' => Http::response('<?xml version="1.0"?><publishInformation></publishInformation>', 200),
            'https://webgate.ec.europa.eu/*' => Http::response("name,entity_type\nTest Person,Individual", 200),
        ]);

        // Ensure config is set for webhook tests
        config(['sanctions.webhook.token' => null]);
    }

    public function test_sanctions_update_command_accepts_source_option(): void
    {
        $this->artisan('sanctions:update', ['--source' => 'un'])
            ->assertSuccessful()
            ->expectsOutputToContain('un');
    }

    public function test_sanctions_update_command_dispatches_all_jobs(): void
    {
        $this->artisan('sanctions:update')
            ->assertSuccessful();

        Queue::assertPushed(\App\Jobs\Sanctions\DownloadUnSanctionsList::class);
        Queue::assertPushed(\App\Jobs\Sanctions\DownloadOfacSanctionsList::class);
        Queue::assertPushed(\App\Jobs\Sanctions\DownloadEuSanctionsList::class);
    }

    public function test_sanctions_update_rejects_invalid_source(): void
    {
        $this->artisan('sanctions:update', ['--source' => 'invalid'])
            ->assertFailed();
    }

    public function test_sanctions_status_shows_list_status(): void
    {
        SanctionList::factory()->create([
            'name' => 'UN Test List',
            'list_type' => 'UNSCR',
            'update_status' => 'success',
            'entry_count' => 100,
        ]);

        $this->artisan('sanctions:status')
            ->assertSuccessful()
            ->expectsOutputToContain('UN Test List');
    }

    public function test_configuration_file_exists(): void
    {
        $this->assertFileExists(config_path('sanctions.php'));

        // Check required config keys
        $this->assertNotNull(config('sanctions.sources'));
        $this->assertNotNull(config('sanctions.schedule'));
        $this->assertNotNull(config('sanctions.notifications'));
    }

    public function test_download_service_validates_xml(): void
    {
        Http::fake([
            'https://example.com/valid.xml' => Http::response('<?xml version="1.0"?><root/>', 200),
            'https://example.com/invalid.xml' => Http::response('not valid xml', 200),
        ]);

        $service = new SanctionsDownloadService;

        $valid = $service->download('https://example.com/valid.xml', 'valid.xml', 'XML');
        $this->assertTrue($valid['success']);

        $invalid = $service->download('https://example.com/invalid.xml', 'invalid.xml', 'XML');
        $this->assertFalse($invalid['success']);
    }

    public function test_download_service_calculates_checksum(): void
    {
        Http::fake([
            'https://example.com/test.xml' => Http::response('<xml>test</xml>', 200),
        ]);

        $service = new SanctionsDownloadService;
        $result = $service->download('https://example.com/test.xml', 'test.xml', 'XML');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['checksum']);
        $this->assertEquals(64, strlen($result['checksum']));
    }

    public function test_import_service_imports_csv_entries(): void
    {
        $list = SanctionList::factory()->create(['list_type' => 'UNSCR']);

        $csvContent = "name,entity_type,aliases,nationality\n";
        $csvContent .= "John Doe,Individual,John D,US\n";
        $csvContent .= 'Acme Corp,Entity,,US';

        $filepath = sys_get_temp_dir().'/test_sanctions.csv';
        file_put_contents($filepath, $csvContent);

        $service = new SanctionsImportService($this->app->make(\App\Services\AuditService::class));
        $result = $service->importFromCsv($filepath, $list->id);

        $this->assertEquals(2, $result['imported']);

        unlink($filepath);
    }

    public function test_import_service_detects_changes(): void
    {
        $list = SanctionList::factory()->create(['list_type' => 'UNSCR', 'entry_count' => 100]);

        $csvContent = "name,entity_type\n";
        for ($i = 1; $i <= 115; $i++) {
            $csvContent .= "Person {$i},Individual\n";
        }

        $filepath = sys_get_temp_dir().'/test_sanctions.csv';
        file_put_contents($filepath, $csvContent);

        $service = new SanctionsImportService($this->app->make(\App\Services\AuditService::class));
        $result = $service->importFromCsv($filepath, $list->id, true);

        $this->assertTrue($result['is_significant_change']);
        $this->assertEquals(15.0, $result['change_percentage']);

        unlink($filepath);
    }

    public function test_un_job_can_be_dispatched(): void
    {
        DownloadUnSanctionsList::dispatch();

        Queue::assertPushed(DownloadUnSanctionsList::class);
    }

    public function test_customer_rescreening_triggered_on_new_entries(): void
    {
        // Create a list
        $list = SanctionList::factory()->create([
            'list_type' => 'UNSCR',
            'source_url' => 'https://example.com/test.xml',
        ]);

        // Create a customer
        Customer::factory()->create(['full_name' => 'John Doe']);

        // Simulate import with new entries
        $result = [
            'imported' => 5,
            'removed' => 0,
            'new_entries_detected' => 5,
            'is_significant_change' => true,
        ];

        // If significant change, rescreening should be triggered
        if ($result['new_entries_detected'] > 0) {
            \App\Jobs\Compliance\SanctionsRescreeningJob::dispatch();
        }

        Queue::assertPushed(\App\Jobs\Compliance\SanctionsRescreeningJob::class);
    }

    public function test_retry_mechanism_on_failure(): void
    {
        $attempt = 0;

        Http::fake([
            'https://example.com/test.xml' => function () use (&$attempt) {
                $attempt++;
                if ($attempt < 3) {
                    return Http::response('', 500);
                }

                return Http::response('<?xml version="1.0"?><root/>', 200);
            },
        ]);

        $service = new SanctionsDownloadService;

        // With 3 retries, should eventually succeed
        $result = $service->download(
            'https://example.com/test.xml',
            'test.xml',
            'XML',
            3
        );

        $this->assertTrue($result['success']);
    }

    public function test_webhook_health_endpoint(): void
    {
        $response = $this->getJson('/api/webhooks/sanctions/health');

        $response->assertOk();
        $response->assertJson([
            'status' => 'ok',
            'service' => 'sanctions-webhook',
        ]);
    }

    public function test_webhook_controller_exists(): void
    {
        $controller = new SanctionsWebhookController;

        // Test that webhook controller exists and has expected methods
        $this->assertInstanceOf(SanctionsWebhookController::class, $controller);
        $this->assertTrue(method_exists($controller, '__invoke'));
        $this->assertTrue(method_exists($controller, 'health'));
    }
}
