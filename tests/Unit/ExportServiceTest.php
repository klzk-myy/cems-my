<?php

namespace Tests\Unit;

use App\Services\ExportService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportServiceTest extends TestCase
{
    protected ExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExportService;
        Storage::fake('local');
    }

    public function test_can_export_to_csv()
    {
        $data = [
            ['id' => 1, 'name' => 'Test 1', 'amount' => 100.00],
            ['id' => 2, 'name' => 'Test 2', 'amount' => 200.00],
        ];

        $filename = 'test_export_'.time().'.csv';
        $path = $this->service->toCSV($data, $filename);

        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('id,name,amount', $content);
        // CSV fputcsv may add quotes, so we check for parts of the content
        $this->assertStringContainsString('1', $content);
        $this->assertStringContainsString('Test 1', $content);
        $this->assertStringContainsString('100', $content);
    }

    public function test_csv_export_handles_empty_data()
    {
        $data = [];
        $filename = 'empty_export_'.time().'.csv';
        $path = $this->service->toCSV($data, $filename);

        $this->assertFileExists($path);
    }

    public function test_can_export_to_excel()
    {
        // Skip if Maatwebsite Excel is not installed
        if (! class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            $this->markTestSkipped('Maatwebsite Excel package not installed');
        }

        $data = [
            ['id' => 1, 'name' => 'Test 1', 'amount' => 100.00],
            ['id' => 2, 'name' => 'Test 2', 'amount' => 200.00],
        ];

        $filename = 'test_export_'.time().'.xlsx';
        $path = $this->service->toExcel($data, $filename);

        $this->assertFileExists($path);
    }

    public function test_cleanup_old_reports_deletes_files_older_than_days()
    {
        // Create a test file
        $testPath = storage_path('app/reports/test_old_report.csv');
        if (! is_dir(dirname($testPath))) {
            mkdir(dirname($testPath), 0755, true);
        }
        file_put_contents($testPath, 'test data');

        // Set file modification time to 40 days ago
        touch($testPath, time() - (40 * 24 * 60 * 60));

        $this->service->cleanupOldReports(30);

        // File should be deleted
        $this->assertFileDoesNotExist($testPath);
    }

    public function test_cleanup_old_reports_keeps_recent_files()
    {
        // Create a test file
        $testPath = storage_path('app/reports/test_recent_report.csv');
        if (! is_dir(dirname($testPath))) {
            mkdir(dirname($testPath), 0755, true);
        }
        file_put_contents($testPath, 'test data');

        $this->service->cleanupOldReports(30);

        // File should still exist
        $this->assertFileExists($testPath);

        // Cleanup
        @unlink($testPath);
    }

    protected function tearDown(): void
    {
        // Cleanup test files
        $testDir = storage_path('app/reports');
        if (is_dir($testDir)) {
            $files = glob($testDir.'/test_*');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }
}
