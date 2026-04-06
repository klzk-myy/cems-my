<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function sessions()
    {
        return $this->hasMany(CounterSession::class);
    }

    public function currentSession()
    {
        return $this->hasOne(CounterSession::class)
            ->where('session_date', now()->toDateString())
            ->where('status', 'open')
            ->latest();
    }
}
