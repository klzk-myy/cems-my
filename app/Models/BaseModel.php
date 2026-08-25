<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    /**
     * Defensive mass-assignment guard.
     * Forces every concrete model to declare $fillable explicitly.
     * Adding a new model without $fillable will now fail loudly.
     */
    protected $guarded = ['*'];
}
