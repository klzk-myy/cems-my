<?php

namespace App\Console\Commands\Concerns;

use App\Enums\ReportType;
use App\Models\ReportGenerated;
use App\Services\Reporting\CsvReportWriter;
use App\Services\Reporting\ReportingService;
use Carbon\Carbon;

trait HasReportFormatting
{
    protected function createReportRecord(
        ReportType $reportType,
        Carbon $periodStart,
        Carbon $periodEnd,
        string $status = 'Generated',
        string $format = 'CSV'
    ): ReportGenerated {
        return app(ReportingService::class)->recordGeneratedReport(
            $reportType,
            $periodStart,
            $periodEnd,
            $status,
            $format
        );
    }

    protected function getReportFilename(ReportType $type, string $suffix): string
    {
        return $type->filenameKey().'_'.now()->format('Y-m-d').'_'.$suffix.'.csv';
    }

    protected function getReportPath(string $filename): string
    {
        return storage_path('app/reports/'.$filename);
    }

    protected function saveReportCsv(string $filepath, string $csvContent): void
    {
        $dir = dirname($filepath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Re-emit through fputcsv so cells are properly quoted and every cell
        // passes CsvReportWriter's spreadsheet formula-injection guard; the
        // caller-provided text is plain comma/newline separated rows. Column
        // order is preserved exactly as the caller laid it out.
        $handle = fopen($filepath, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Failed to open report file for writing: {$filepath}");
        }

        try {
            foreach (preg_split('/\r?\n/', rtrim($csvContent, "\r\n")) as $line) {
                if ($line === '') {
                    continue;
                }

                fputcsv($handle, app(CsvReportWriter::class)->sanitizeRow(str_getcsv($line)));
            }
        } finally {
            fclose($handle);
        }
    }
}
