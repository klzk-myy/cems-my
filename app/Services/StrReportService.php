<?php

namespace App\Services;

use App\Enums\StrStatus;
use App\Models\FlaggedTransaction;
use App\Models\StrReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StrReportService
{
    public function generateStrNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "STR-{$year}{$month}-";

        $lastStr = StrReport::where('str_no', 'like', $prefix.'%')
            ->orderBy('str_no', 'desc')
            ->first();

        if ($lastStr) {
            $lastNumber = (int) substr($lastStr->str_no, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    public function generateFromAlert(FlaggedTransaction $alert): StrReport
    {
        return DB::transaction(function () use ($alert) {
            $alert->load(['transaction', 'transaction.customer']);

            $transaction = $alert->transaction;
            $customer = $transaction?->customer;

            if (! $customer) {
                throw new \Exception('Cannot generate STR: Alert has no associated customer');
            }

            $branchId = $transaction?->branch_id ?? auth()->user()->branch_id ?? 1;

            // Use the alert creation date as the suspicion date (when suspicion first arose)
            $suspicionDate = $alert->created_at ?? now();

            $strReport = StrReport::create([
                'str_no' => $this->generateStrNumber(),
                'branch_id' => $branchId,
                'customer_id' => $customer->id,
                'alert_id' => $alert->id,
                'transaction_ids' => $transaction ? [$transaction->id] : [],
                'reason' => $this->buildReasonFromAlert($alert),
                'supporting_documents' => $this->gatherSupportingDocuments($alert),
                'status' => StrStatus::Draft,
                'created_by' => auth()->id(),
                'suspicion_date' => $suspicionDate,
            ]);

            // Calculate and store filing deadline
            $complianceService = app(ComplianceService::class);
            $deadlineInfo = $complianceService->calculateStrDeadline($suspicionDate);
            $strReport->filing_deadline = $deadlineInfo['deadline'];
            $strReport->save();

            // Update the flag status to UnderReview
            $alert->update(['status' => \App\Enums\FlagStatus::UnderReview]);

            Log::info('STR Report generated from alert', [
                'str_id' => $strReport->id,
                'str_no' => $strReport->str_no,
                'alert_id' => $alert->id,
                'suspicion_date' => $suspicionDate->toDateTimeString(),
                'filing_deadline' => $deadlineInfo['deadline']->toDateTimeString(),
                'created_by' => auth()->id(),
            ]);

            // Audit log for STR generation
            $auditService = app(AuditService::class);
            $auditService->logStrAction('str_generated', $strReport->id, [
                'new' => [
                    'str_no' => $strReport->str_no,
                    'customer_id' => $customer->id,
                    'alert_id' => $alert->id,
                    'suspicion_date' => $suspicionDate->toDateTimeString(),
                    'filing_deadline' => $deadlineInfo['deadline']->toDateTimeString(),
                ],
            ]);

            return $strReport;
        });
    }

    public function submitToGoAML(StrReport $report): bool
    {
        if (! $report->status->canSubmit()) {
            Log::warning('STR cannot be submitted: invalid status', [
                'str_id' => $report->id,
                'status' => $report->status->value,
            ]);

            return false;
        }

        try {
            $goAmlPayload = $this->buildGoAMLPayload($report);

            Log::info('Submitting STR to goAML', [
                'str_id' => $report->id,
                'str_no' => $report->str_no,
                'payload' => $goAmlPayload,
            ]);

            $submitted = $this->callGoAMLApi($goAmlPayload);

            // Always update status when submission is attempted
            // Even if API fails, we mark it as submitted for tracking
            $updateData = [
                'status' => StrStatus::Submitted,
                'submitted_at' => now(),
            ];
            $report->update($updateData);

            if ($submitted) {
                Log::info('STR submitted successfully', [
                    'str_id' => $report->id,
                    'str_no' => $report->str_no,
                ]);
            } else {
                Log::warning('STR submission API call failed', [
                    'str_id' => $report->id,
                    'str_no' => $report->str_no,
                ]);
            }

            return $submitted;
        } catch (\Exception $e) {
            Log::error('STR submission failed', [
                'str_id' => $report->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function trackSubmission(StrReport $report, string $bnmRef): void
    {
        $report->update([
            'bnm_reference' => $bnmRef,
            'status' => StrStatus::Acknowledged,
        ]);

        Log::info('STR submission tracked with BNM reference', [
            'str_id' => $report->id,
            'str_no' => $report->str_no,
            'bnm_reference' => $bnmRef,
        ]);
    }

    public function submitForReview(StrReport $report): bool
    {
        if (! $report->isDraft()) {
            return false;
        }

        $report->update(['status' => StrStatus::PendingReview]);

        return true;
    }

    public function submitForApproval(StrReport $report): bool
    {
        if (! $report->status->canApprove()) {
            return false;
        }

        $report->update([
            'status' => StrStatus::PendingApproval,
            'reviewed_by' => auth()->id(),
        ]);

        return true;
    }

    public function approve(StrReport $report): bool
    {
        if (! $report->status->canSubmit()) {
            return false;
        }

        $report->update([
            'status' => StrStatus::PendingApproval,
            'approved_by' => auth()->id(),
        ]);

        return true;
    }

    private function buildReasonFromAlert(FlaggedTransaction $alert): string
    {
        $reason = "Suspicious Transaction Alert - {$alert->flag_type->value}\n\n";
        $reason .= "Flag Reason: {$alert->flag_reason}\n";

        if ($alert->transaction) {
            $txn = $alert->transaction;
            $reason .= "\nTransaction Details:\n";
            $reason .= '- Transaction ID: #'.($txn->id ?? 'N/A')."\n";
            $reason .= '- Amount: RM '.($txn->amount_local ?? 'N/A')."\n";
            $reason .= '- Currency: '.($txn->currency_code ?? 'N/A')."\n";
            $reason .= '- Date: '.($txn->created_at?->format('Y-m-d H:i:s') ?? 'N/A')."\n";
        }

        return $reason;
    }

    private function gatherSupportingDocuments(FlaggedTransaction $alert): array
    {
        $documents = [];

        $customer = $alert->transaction?->customer;
        if ($customer) {
            $customerDocs = $customer->documents()->get();
            foreach ($customerDocs as $doc) {
                $documents[] = [
                    'type' => 'customer_document',
                    'id' => $doc->id,
                    'name' => $doc->document_type,
                ];
            }
        }

        return $documents;
    }

    private function buildGoAMLPayload(StrReport $report): array
    {
        $report->load('customer');

        return [
            'str_no' => $report->str_no,
            'submission_date' => now()->format('Y-m-d\TH:i:s'),
            'branch' => [
                'code' => $report->branch?->code ?? 'HQ',
                'name' => $report->branch?->name ?? 'Head Office',
            ],
            'customer' => [
                'id' => $report->customer->id,
                'name' => $report->customer->full_name,
                'id_type' => $report->customer->id_type,
                'nationality' => $report->customer->nationality,
                'pep_status' => $report->customer->pep_status,
            ],
            'transactions' => $report->transactions()->map(function ($txn) {
                return [
                    'id' => $txn->id,
                    'amount' => $txn->amount_local,
                    'currency' => $txn->currency,
                    'date' => $txn->created_at->format('Y-m-d\TH:i:s'),
                    'type' => $txn->transaction_type,
                ];
            })->toArray(),
            'reason' => $report->reason,
            'supporting_documents' => $report->supporting_documents,
            'filed_by' => $report->creator?->full_name ?? 'Unknown',
            'filed_at' => $report->created_at->format('Y-m-d\TH:i:s'),
        ];
    }

    private function callGoAMLApi(array $payload): bool
    {
        $goAmlEndpoint = config('services.goaml.endpoint', 'https://goaml.bnm.gov.my/api/v1');
        $apiKey = config('services.goaml.api_key');

        try {
            $ch = curl_init($goAmlEndpoint.'/str');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$apiKey,
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return true;
            }

            Log::warning('goAML API returned non-success status', [
                'http_code' => $httpCode,
                'response' => $response,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('goAML API call failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
