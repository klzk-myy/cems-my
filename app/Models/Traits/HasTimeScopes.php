<?php

namespace App\Models\Traits;

use Illuminate\Support\Carbon;

trait HasTimeScopes
{
    protected string $timeScopeColumn = 'created_at';

    public function scopeLatest($query)
    {
        return $query->orderBy($this->timeScopeColumn, 'desc');
    }

    public function scopeToday($query)
    {
        return $query->whereBetween($this->timeScopeColumn, [today()->startOfDay(), today()->endOfDay()]);
    }

    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween($this->timeScopeColumn, [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay(),
        ]);
    }
}
