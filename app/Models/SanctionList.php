<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SanctionList extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'list_type',
        'source_file',
        'uploaded_by',
        'is_active',
        'uploaded_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uploaded_at = now();
        });
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SanctionEntry::class, 'list_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}