<?php

namespace App\Models;

use App\Enums\HighRiskCountryRiskLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class HighRiskCountry extends BaseModel
{
    use HasFactory;

    /**
     * Cache key for the high-risk country code list.
     */
    private const COUNTRY_CODES_CACHE_KEY = 'high_risk_country_codes';

    protected $primaryKey = 'country_code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'country_code',
        'country_name',
        'risk_level',
        'source',
        'list_date',
    ];

    protected $casts = [
        'list_date' => 'date',
        'risk_level' => HighRiskCountryRiskLevel::class,
    ];

    /**
     * Get the list of high-risk ISO country codes, cached for 24 hours.
     *
     * The list is seed/reference data that changes rarely (FATF-style updates),
     * so a day-long cache avoids a redundant query on every risk calculation.
     * The cache is invalidated whenever an entry is created, updated, or deleted.
     *
     * @return array<string>
     */
    public static function countryCodes(): array
    {
        return Cache::remember(self::COUNTRY_CODES_CACHE_KEY, now()->addDay(), function () {
            return static::query()->pluck('country_code')->toArray();
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::COUNTRY_CODES_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::COUNTRY_CODES_CACHE_KEY));
    }
}
