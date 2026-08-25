<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRiskHistory extends BaseModel
{
    use HasFactory;

    protected $table = 'customer_risk_history';

    protected $fillable = [
        'customer_id',
        'new_score',
        'change_reason',
    ];

    protected $casts = [
        'new_score' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
