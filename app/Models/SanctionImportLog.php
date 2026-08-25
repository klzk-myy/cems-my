<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SanctionImportLog extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'list_id',
        'imported_at',
        'records_added',
        'records_updated',
        'records_deactivated',
        'status',
        'error_message',
        'triggered_by',
        'user_id',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'records_added' => 'integer',
        'records_updated' => 'integer',
        'records_deactivated' => 'integer',
    ];

    public function toSummaryArray(): array
    {
        return [
            'id' => $this->id,
            'list' => $this->sanctionList ? [
                'id' => $this->sanctionList->id,
                'name' => $this->sanctionList->name,
            ] : null,
            'imported_at' => $this->imported_at->toIso8601String(),
            'records_added' => $this->records_added,
            'records_updated' => $this->records_updated,
            'records_deactivated' => $this->records_deactivated,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'triggered_by' => $this->triggered_by,
        ];
    }

    public function sanctionList(): BelongsTo
    {
        return $this->belongsTo(SanctionList::class, 'list_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
