<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer Model
 *
 * Represents customer information with encrypted identification data.
 * Supports risk assessment, PEP status tracking, and compliance monitoring.
 *
 * @property int $id
 * @property string $full_name
 * @property string $id_type 'mykad', 'passport', 'other'
 * @property string $id_number_encrypted Encrypted ID/passport number
 * @property string $nationality
 * @property \Illuminate\Support\Carbon $date_of_birth
 * @property string|null $address
 * @property string $phone
 * @property string|null $email
 * @property bool $pep_status Politically Exposed Person
 * @property int $risk_score 0-100
 * @property string $risk_rating 'Low', 'Medium', 'High'
 * @property \Illuminate\Support\Carbon|null $risk_assessed_at
 * @property \Illuminate\Support\Carbon|null $last_transaction_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
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
        'annual_volume_estimate',
        'risk_assessed_at',
        'last_transaction_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'pep_status' => 'boolean',
        'risk_score' => 'integer',
        'annual_volume_estimate' => 'decimal:4',
        'risk_assessed_at' => 'datetime',
        'last_transaction_at' => 'datetime',
    ];

    /**
     * Get all transactions for this customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all documents associated with this customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents()
    {
        return $this->hasMany(CustomerDocument::class);
    }

    /**
     * Get risk assessment history for this customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function riskHistory()
    {
        return $this->hasMany(CustomerRiskHistory::class);
    }

    /**
     * Determine if customer is high risk.
     */
    public function isHighRisk(): bool
    {
        return $this->risk_rating === 'High' || $this->pep_status;
    }
}
