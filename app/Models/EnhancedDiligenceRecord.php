<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnhancedDiligenceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'flagged_transaction_id',
        'customer_id',
        'edd_reference',
        'status',
        'risk_level',
        'source_of_funds',
        'source_of_funds_description',
        'source_of_funds_documents',
        'purpose_of_transaction',
        'business_justification',
        'employment_status',
        'employer_name',
        'employer_address',
        'annual_income_range',
        'estimated_net_worth',
        'source_of_wealth',
        'source_of_wealth_description',
        'additional_information',
        'supporting_documents',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'source_of_funds_documents' => 'array',
        'supporting_documents' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function flaggedTransaction(): BelongsTo
    {
        return $this->belongsTo(FlaggedTransaction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isComplete(): bool
    {
        return $this->status !== 'Incomplete';
    }

    public function isPendingReview(): bool
    {
        return $this->status === 'Pending_Review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }
}
