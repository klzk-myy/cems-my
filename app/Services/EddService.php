<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\EnhancedDiligenceRecord;
use App\Models\FlaggedTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EddService
{
    protected MathService $mathService;

    public function __construct(MathService $mathService)
    {
        $this->mathService = $mathService;
    }

    public function createEddRecord(FlaggedTransaction $flag, array $data = []): EnhancedDiligenceRecord
    {
        return DB::transaction(function () use ($flag, $data) {
            $eddReference = $this->generateEddReference();

            $record = EnhancedDiligenceRecord::create([
                'flagged_transaction_id' => $flag->id,
                'customer_id' => $flag->customer_id,
                'edd_reference' => $eddReference,
                'status' => 'Incomplete',
                'risk_level' => $data['risk_level'] ?? 'Medium',
            ]);

            return $record;
        });
    }

    public function updateEddRecord(EnhancedDiligenceRecord $record, array $data): EnhancedDiligenceRecord
    {
        $record->update($data);

        if ($this->isRecordComplete($record)) {
            $record->update(['status' => 'Pending_Review']);
        }

        return $record->fresh();
    }

    public function submitForReview(EnhancedDiligenceRecord $record): EnhancedDiligenceRecord
    {
        if (!$this->isRecordComplete($record)) {
            throw new \InvalidArgumentException('EDD record must be complete before submission');
        }

        $record->update(['status' => 'Pending_Review']);

        return $record;
    }

    public function approve(EnhancedDiligenceRecord $record, User $reviewer, ?string $notes = null): EnhancedDiligenceRecord
    {
        $record->update([
            'status' => 'Approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        return $record;
    }

    public function reject(EnhancedDiligenceRecord $record, User $reviewer, string $reason): EnhancedDiligenceRecord
    {
        $record->update([
            'status' => 'Rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $reason,
        ]);

        return $record;
    }

    public function isRecordComplete(EnhancedDiligenceRecord $record): bool
    {
        $required = [
            $record->source_of_funds,
            $record->purpose_of_transaction,
        ];

        return !in_array(null, $required, true) && !empty($record->source_of_funds);
    }

    protected function generateEddReference(): string
    {
        $prefix = 'EDD-' . date('Ym') . '-';
        $lastRecord = EnhancedDiligenceRecord::where('edd_reference', 'like', $prefix . '%')
            ->orderBy('edd_reference', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->edd_reference, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }
}
