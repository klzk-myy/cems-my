<?php

namespace Tests\Feature\Audit;

use Tests\TestCase;

class ReportDownloadSanitizationTest extends TestCase
{
    public function test_report_download_uses_basename_sanitization(): void
    {
        $file = base_path('app/Http/Controllers/Api/V1/ReportController.php');
        $this->assertFileExists($file);

        $content = file_get_contents($file);

        // basename() strips any directory component, which confines the lookup
        // to the reports directory and neutralizes ../ traversal attempts.
        // Behavioral coverage (authenticated traversal 404 + streamed file)
        // lives in tests/Feature/Api/ReportDownloadTest.
        $this->assertStringContainsString('basename($filename)', $content);
        $this->assertStringContainsString('reports/{$filename}', $content);
    }
}
