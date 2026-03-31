<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'full_name',
        'id_type',
        'id_number_encrypted',
        'nationality',
        'date_of_birth',
        'address',
        'phone',
        'email',
        'pep_status',
        'risk_score',
        'risk_rating',
        'risk_assessed_at',
        'last_transaction_at'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'pep_status' => 'boolean',
        'risk_score' => 'integer',
        'risk_assessed_at' => 'datetime',
        'last_transaction_at' => 'datetime',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function documents()
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function riskHistory()
    {
        return $this->hasMany(CustomerRiskHistory::class);
    }
}
