<?php

namespace App\Models;

use App\Services\MathService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchPool extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'currency_code',
        'available_balance',
        'allocated_balance',
    ];

    protected $casts = [
        'available_balance' => 'decimal:4',
        'allocated_balance' => 'decimal:4',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function hasAvailable(string $amount): bool
    {
        $math = new MathService;

        return $math->compare($this->available_balance, $amount) >= 0;
    }

    public function allocate(string $amount): bool
    {
        $math = new MathService;

        if (! $this->hasAvailable($amount)) {
            return false;
        }

        $this->available_balance = $math->subtract($this->available_balance, $amount);
        $this->allocated_balance = $math->add($this->allocated_balance, $amount);
        $this->save();

        return true;
    }

    public function deallocate(string $amount): bool
    {
        $math = new MathService;

        if ($math->compare($this->allocated_balance, $amount) < 0) {
            return false;
        }

        $this->available_balance = $math->add($this->available_balance, $amount);
        $this->allocated_balance = $math->subtract($this->allocated_balance, $amount);
        $this->save();

        return true;
    }
}
