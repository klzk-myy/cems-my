<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Position Limits
    |--------------------------------------------------------------------------
    |
    | Maximum currency holdings per currency code before requiring
    | escalation/reporting to BNM. Values in foreign currency units.
    |
    */
    'position_limits' => [
        'USD' => 1000000,  // $1M USD max position
        'EUR' => 800000,    // €800K EUR max position
        'GBP' => 700000,    // £700K GBP max position
        'SGD' => 900000,   // S$900K SGD max position
        'JPY' => 100000000, // ¥100M JPY max position
        'AUD' => 750000,   // A$750K AUD max position
        'CHF' => 700000,   // CHF 700K max position
        'CAD' => 700000,   // C$700K CAD max position
        'HKD' => 6000000,  // HK$6M HKD max position
    ],

    /*
    |--------------------------------------------------------------------------
    | BNM Reporting Thresholds
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'ctr' => 50000,      // Cash Transaction Report threshold (RM)
        'edd' => 50000,      // Enhanced Due Diligence threshold (RM)
        'str' => 50000,      // Suspicious Transaction Report threshold (RM)
        'lctr' => 50000,     // Large Cash Transaction Report threshold (RM)
    ],

    /*
    |--------------------------------------------------------------------------
    | System Configuration
    |--------------------------------------------------------------------------
    */
    'version' => '1.0.0',
];
