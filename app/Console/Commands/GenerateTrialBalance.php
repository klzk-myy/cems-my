<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\HasReportFormatting;
use App\Enums\ReportType;
use App\Services\Accounting\LedgerService;
use App\Services\Reporting\CsvReportWriter;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateTrialBalance extends Command
{
    use HasReportFormatting;

    protected $signature = 'report:trial-balance {--date= : Specific date (Y-m-d), defaults to last closed period}';

    protected $description = 'Generate trial balance report for accounting period';

    public function handle(LedgerService $ledgerService): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $this->info("Generating Trial Balance for {$date->toDateString()}...");

        try {
            // getTrialBalance() returns a wrapper array; the account rows live
            // under 'accounts' and the BCMath-computed totals under
            // 'total_debits'/'total_credits'.
            $reportData = $ledgerService->getTrialBalance($date->toDateString());

            $filename = $this->getReportFilename(ReportType::TrialBalance, 'report');

            $rows = [];
            foreach ($reportData['accounts'] as $row) {
                $rows[] = [
                    $row['account_code'],
                    $row['account_name'],
                    number_format((float) $row['debit'], 2, '.', ''),
                    number_format((float) $row['credit'], 2, '.', ''),
                ];
            }

            // Reuse the service's totals instead of recomputing row sums.
            $rows[] = [
                '',
                'TOTAL',
                number_format((float) ($reportData['total_debits'] ?? '0'), 2, '.', ''),
                number_format((float) ($reportData['total_credits'] ?? '0'), 2, '.', ''),
            ];

            // Emit through CsvReportWriter so cells containing commas (account
            // names) are quoted by fputcsv and every cell passes the
            // formula-injection guard - no pre-rendered string round-trip.
            $filepath = app(CsvReportWriter::class)->write(
                $filename,
                ['Account Code', 'Account Name', 'Debit', 'Credit'],
                $rows
            );

            $this->createReportRecord(ReportType::TrialBalance, $date->startOfMonth(), $date->endOfMonth());

            $this->info("Trial Balance generated: {$filepath}");

            return 0;
        } catch (\Exception $e) {
            $this->error('Trial Balance generation failed: '.$e->getMessage());

            return 1;
        }
    }
}
