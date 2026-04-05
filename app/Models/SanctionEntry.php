<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SanctionEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'list_id',
        'entity_name',
        'entity_type',
        'aliases',
        'nationality',
        'date_of_birth',
        'details',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function sanctionList(): BelongsTo
    {
        return $this->belongsTo(SanctionList::class, 'list_id');
    }

    public function getAliasesAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setAliasesAttribute($value)
    {
        $this->attributes['aliases'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getDetailsAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setDetailsAttribute($value)
    {
        $this->attributes['details'] = is_array($value) ? json_encode($value) : $value;
    }
}