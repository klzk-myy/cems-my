<?php

namespace App\Models;

use App\Enums\CaseStatus;
use App\Enums\AlertPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ComplianceCase extends Model
{
    use HasFactory;

    protected $table = 'compliance_cases';

    protected $fillable = [
        'case_number',
        'customer_id',
        'status',
        'priority',
        'assigned_to',
        'opened_by',
        'sla_deadline',
        'resolved_at',
        'notes',
    ];

    protected $casts = [
        'status' => CaseStatus::class,
        'priority' => AlertPriority::class,
        'sla_deadline' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'case_id');
    }

    public function strDrafts(): HasMany
    {
        return $this->hasMany(StrDraft::class, 'case_id');
    }

    public function enhancedDiligenceRecords(): HasManyThrough
    {
        return $this->hasManyThrough(
            EnhancedDiligenceRecord::class,
            Alert::class,
            'case_id',
            'customer_id',
            'id',
            'customer_id'
        );
    }

    public static function generateCaseNumber(): string
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;
        return sprintf('CASE-%s-%04d', $date, $count);
    }

    public function isOverdue(): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }
        return now()->isAfter($this->sla_deadline);
    }

    public function canBeResolved(): bool
    {
        return $this->alerts()->whereNull('case_id')->count() === 0;
    }

    public function derivePriorityFromAlerts(): AlertPriority
    {
        $maxScore = $this->alerts()->max('risk_score') ?? 0;
        return AlertPriority::fromRiskScore($maxScore);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            CaseStatus::Resolved,
            CaseStatus::Closed,
        ]);
    }
}