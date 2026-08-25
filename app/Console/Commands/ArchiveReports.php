<?php

namespace App\Console\Commands;

use App\Enums\ReportGeneratedStatus;
use App\Models\ReportGenerated;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArchiveReports extends Command
{
    protected $signature = 'reports:archive {--months=12 : Archive reports older than N months}';

    protected $description = 'Archive generated reports for regulatory compliance (BNM 7-year retention)';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $cutoffDate = now()->subMonths($months);

        $this->info("Archiving reports generated before {$cutoffDate->toDateString()}...");

        // Use a dedicated archive disk when configured; fall back to the
        // default (local) disk otherwise.
        $archiveDisk = Storage::disk(
            config('filesystems.disks.archive') !== null ? 'archive' : config('filesystems.default')
        );

        $archiveDir = 'archives/'.now()->format('Y/m');
        if (! $archiveDisk->exists($archiveDir)) {
            $archiveDisk->makeDirectory($archiveDir);
        }

        $reportsToArchive = ReportGenerated::where('generated_at', '<', $cutoffDate)
            ->where('status', '!=', ReportGeneratedStatus::Archived->value)
            ->get();

        $count = 0;
        foreach ($reportsToArchive as $report) {
            // Copy the actual artifact into the archive directory BEFORE
            // flipping status so BNM 7-year retention has real files, not
            // just status flips. Only records whose artifact is confirmed
            // present at the destination are marked Archived.
            if (! $this->copyReportFileToArchive($report, $archiveDisk, $archiveDir)) {
                $this->warn("Report {$report->id} left unarchived; see log for details.");

                continue;
            }

            $report->update(['status' => ReportGeneratedStatus::Archived]);
            $count++;
        }

        $this->info("Archived {$count} report records.");

        $this->info("Archive directory: {$archiveDir}");

        return 0;
    }

    /**
     * Copy a report's stored file into the archive directory.
     *
     * Returns true only when the artifact is confirmed present at the archive
     * destination (copied now, or already archived by an earlier run). Missing
     * artifacts are skipped with a warning so archival of the record set
     * continues; the warning documents the gap for compliance review and the
     * caller leaves the record's status unchanged.
     */
    private function copyReportFileToArchive(ReportGenerated $report, Filesystem $archiveDisk, string $archiveDir): bool
    {
        $filePath = $report->file_path;

        if (empty($filePath)) {
            $this->warn("Report {$report->id} has no recorded file path; nothing to copy.");

            Log::warning('Report archive skipped: no recorded file path', [
                'report_id' => $report->id,
                'path' => null,
            ]);

            return false;
        }

        // file_path may be an absolute filesystem path or storage-relative,
        // depending on which writer produced it.
        if (is_file($filePath)) {
            $contents = file_get_contents($filePath);
        } elseif (Storage::exists($filePath)) {
            $contents = Storage::get($filePath);
        } else {
            $contents = false;
        }

        if ($contents === false) {
            // Already archived by an earlier run: the artifact exists at the
            // destination even though the source copy is gone.
            if ($archiveDisk->exists($archiveDir.'/'.basename($filePath))) {
                return true;
            }

            $this->warn("Report file for {$report->id} not found at '{$filePath}'; skipping copy.");

            Log::warning('Report archive skipped: source file missing', [
                'report_id' => $report->id,
                'path' => $filePath,
            ]);

            return false;
        }

        return (bool) $archiveDisk->put($archiveDir.'/'.basename($filePath), $contents);
    }
}
