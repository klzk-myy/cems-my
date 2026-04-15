<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer Model
 *
 * Represents customer information with encrypted identification data.
 * Supports risk assessment, PEP status tracking, and compliance monitoring.
 *
 * @property int $id
 * @property string $full_name
 * @property string $id_type 'MyKad', 'Passport', 'Others'
 * @property string $id_number_encrypted Encrypted ID/passport number
 * @property string $nationality
 * @property \Illuminate\Support\Carbon $date_of_birth
 * @property string|null $address
 * @property string $phone
 * @property string|null $email
 * @property bool $pep_status Politically Exposed Person
 * @property bool $sanction_hit Sanctions list match
 * @property int $risk_score 0-100
 * @property string $risk_rating 'Low', 'Medium', 'High'
 * @property string $cdd_level 'Simplified', 'Standard', 'Enhanced'
 * @property bool $is_active
 * @property string|null $occupation
 * @property string|null $employer_name
 * @property string|null $employer_address
 * @property float|null $annual_volume_estimate
 * @property \Illuminate\Support\Carbon|null $risk_assessed_at
 * @property \Illuminate\Support\Carbon|null $last_transaction_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Customer extends Model
{
    use HasFactory, SoftDeletes;

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
        'sanction_hit',
        'is_pep_associate',
        'risk_score',
        'risk_rating',
        'cdd_level',
        'is_active',
        'occupation',
        'employer_name',
        'employer_address',
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
        'sanction_hit' => 'boolean',
        'is_active' => 'boolean',
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
     * Get risk score snapshots for this customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function riskScoreSnapshots()
    {
        return $this->hasMany(RiskScoreSnapshot::class);
    }

    /**
     * Get PEP relations for this customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pepRelations()
    {
        return $this->hasMany(CustomerRelation::class, 'customer_id');
    }

    /**
     * Get associate relations where this customer is the related party.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function associateRelations()
    {
        return $this->hasMany(CustomerRelation::class, 'related_customer_id');
    }

    /**
     * Determine if this customer is a PEP associate.
     * Returns true if any PEP relation exists.
     */
    public function isPepAssociate(): bool
    {
        return $this->pepRelations()->where('is_pep', true)->exists();
    }

    /**
     * Determine if customer is high risk.
     *
     * A customer is high risk if their risk rating is 'High', they are a PEP,
     * or they have a sanctions match.
     *
     * @return bool True if the customer is high risk
     */
    public function isHighRisk(): bool
    {
        return $this->risk_rating === 'High' || $this->pep_status || $this->sanction_hit;
    }

    /**
     * Get CDD level display label.
     */
    public function getCddLevelLabelAttribute(): string
    {
        return $this->cdd_level ?? 'Simplified';
    }
}
