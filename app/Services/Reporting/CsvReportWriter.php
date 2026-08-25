<?php

namespace App\Services\Reporting;

use App\Exceptions\Domain\ReportValidationException;
use Illuminate\Support\Facades\Storage;

class CsvReportWriter
{
    /**
     * Write a simple CSV report with a single header row followed by data rows.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function write(string $filename, array $headers, array $rows): string
    {
        return $this->writeToDisk($filename, function ($csv) use ($headers, $rows) {
            fputcsv($csv, $this->sanitizeRow($headers));

            foreach ($rows as $row) {
                fputcsv($csv, $this->sanitizeRow($row));
            }
        });
    }

    /**
     * Write a CSV report with leading title rows, a blank separator row,
     * a header row, and then data rows.
     *
     * @param  array<int, array<int, mixed>>  $titleRows
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function writeWithTitleRows(string $filename, array $titleRows, array $headers, array $rows): string
    {
        return $this->writeToDisk($filename, function ($csv) use ($titleRows, $headers, $rows) {
            foreach ($titleRows as $titleRow) {
                fputcsv($csv, $this->sanitizeRow($titleRow));
            }

            fputcsv($csv, []);
            fputcsv($csv, $this->sanitizeRow($headers));

            foreach ($rows as $row) {
                fputcsv($csv, $this->sanitizeRow($row));
            }
        });
    }

    /**
     * Neutralize spreadsheet formula injection (CSV injection, OWASP).
     *
     * Cells beginning with =, +, -, @, tab, or CR are interpreted by Excel and
     * Google Sheets as formulas. Prefixing them with a single quote forces them
     * to be treated as plain text. Non-string values (e.g. numeric amounts) are
     * passed through untouched.
     *
     * Cells that parse as plain decimal numbers - including negative monetary
     * values such as "-1234.50" - are exempt from prefixing: a leading "-" is
     * how negative amounts are rendered in BNM exports, not an injection
     * vector. Non-numeric cells starting with "-" keep the guard.
     *
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    public function sanitizeRow(array $row): array
    {
        return array_map(function (mixed $value): mixed {
            if (! is_string($value) || $value === '') {
                return $value;
            }

            if (preg_match('/^-?\d+(\.\d+)?$/', $value) === 1) {
                return $value;
            }

            $first = $value[0];

            if (in_array($first, ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'".$value;
            }

            return $value;
        }, $row);
    }

    /**
     * @param  callable(resource): void  $writer
     */
    protected function writeToDisk(string $filename, callable $writer): string
    {
        $filename = basename($filename);
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\') ||
            $filename === '' || $filename === '.') {
            throw new ReportValidationException("Invalid report filename: {$filename}");
        }

        if (! Storage::exists('reports')) {
            Storage::makeDirectory('reports');
        }

        $filepath = "reports/{$filename}";
        $csv = fopen(Storage::path($filepath), 'w');

        if (! $csv) {
            throw new ReportValidationException("Failed to open report file for writing: {$filepath}");
        }

        $writer($csv);
        fclose($csv);

        return $filepath;
    }
}
