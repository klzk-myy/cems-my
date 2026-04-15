<?php

namespace App\Modules\Pos\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosReceipt extends Model
{
    use HasFactory;

    protected $table = 'pos_receipts';

    protected $fillable = [
        'transaction_id',
        'receipt_number',
        'receipt_type',
        'template_type',
        'receipt_data',
        'printed_at',
        'printed_by',
    ];

    protected $casts = [
        'receipt_data' => 'array',
        'printed_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Transaction::class);
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'printed_by');
    }

    public function scopeThermal($query)
    {
        return $query->where('receipt_type', 'thermal');
    }

    public function scopePdf($query)
    {
        return $query->where('receipt_type', 'pdf');
    }
}
