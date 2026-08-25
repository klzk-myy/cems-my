<?php

namespace App\Models;

use App\Enums\EntityType;
use App\Enums\SanctionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SanctionEntry extends BaseModel
{
    use HasFactory, SoftDeletes;

    public $timestamps = true;

    protected $fillable = [
        'list_id',
        'list_source',
        'entity_name',
        'entity_type',
        'aliases',
        'nationality',
        'date_of_birth',
        'reference_number',
        'listing_date',
        'details',
        'address',
        'city',
        'country',
        'postal_code',
        'normalized_name',
        'soundex_code',
        'metaphone_code',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'listing_date' => 'date',
        'details' => 'array',
        'entity_type' => EntityType::class,
        'status' => SanctionStatus::class,
    ];

    public static function buildForCreate(array $data, array $normalized): array
    {
        return [
            'list_id' => $data['list_id'],
            'entity_name' => $data['entity_name'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'aliases' => static::parseAliases($data['aliases'] ?? null),
            'nationality' => $data['nationality'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'listing_date' => $data['listing_date'] ?? null,
            'details' => $data['details'] ?? null,
            'normalized_name' => $normalized['normalized_name'],
            'soundex_code' => $normalized['soundex_code'],
            'metaphone_code' => $normalized['metaphone_code'],
            'status' => SanctionStatus::Active->value,
        ];
    }

    public static function buildForUpdate(array $data, array $normalized): array
    {
        $payload = [
            'entity_name' => $data['entity_name'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'aliases' => static::parseAliases($data['aliases'] ?? null),
            'nationality' => $data['nationality'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'listing_date' => $data['listing_date'] ?? null,
            'details' => $data['details'] ?? null,
            'list_source' => $data['list_source'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'normalized_name' => $normalized['normalized_name'],
            'soundex_code' => $normalized['soundex_code'],
            'metaphone_code' => $normalized['metaphone_code'],
        ];

        if (array_key_exists('status', $data)) {
            $payload['status'] = $data['status'];
        }

        return $payload;
    }

    public function toEntrySummaryArray(): array
    {
        return [
            'id' => $this->id,
            'entity_name' => $this->entity_name,
            'entity_type' => $this->entity_type,
            'list' => $this->sanctionList ? [
                'id' => $this->sanctionList->id,
                'name' => $this->sanctionList->name,
            ] : null,
            'list_source' => $this->list_source,
            'nationality' => $this->nationality,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'listing_date' => $this->listing_date?->format('Y-m-d'),
        ];
    }

    public static function parseAliases(?string $aliases): ?array
    {
        if ($aliases === null || trim($aliases) === '') {
            return null;
        }

        return array_filter(array_map('trim', explode("\n", $aliases)));
    }

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

    public function setDetailsAttribute($value)
    {
        $this->attributes['details'] = is_string($value) ? $value : (is_array($value) ? json_encode($value) : null);
    }
}
