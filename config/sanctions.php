<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sanctions List Sources
    |--------------------------------------------------------------------------
    |
    | Configuration for automated sanctions list downloads.
    | These URLs are official sources from UN, OFAC, Malaysia MOHA, and EU.
    |
    */

    'sources' => [
        'un' => [
            'name' => 'UN Security Council Consolidated List',
            'url' => env('SANCTIONS_UN_URL', 'https://scsanctions.un.org/resources/xml/en/consolidated.xml'),
            'format' => 'XML',
            'list_type' => 'UNSCR',
            'enabled' => env('SANCTIONS_UN_ENABLED', true),
            'description' => 'United Nations Security Council sanctions consolidated list',
            'update_frequency' => 'daily',
            'timeout' => 300, // 5 minutes
            'retry_attempts' => 3,
            'retry_delay' => 60, // seconds between retries
        ],

        'ofac' => [
            'name' => 'OFAC SDN List',
            'url' => env('SANCTIONS_OFAC_URL', 'https://www.treasury.gov/ofac/downloads/sdn.xml'),
            'format' => 'XML',
            'list_type' => 'OFAC',
            'enabled' => env('SANCTIONS_OFAC_ENABLED', true),
            'description' => 'US Treasury Office of Foreign Assets Control Specially Designated Nationals',
            'update_frequency' => 'daily',
            'timeout' => 300,
            'retry_attempts' => 3,
            'retry_delay' => 60,
        ],

        'ofac_consolidated' => [
            'name' => 'OFAC Consolidated Sanctions',
            'url' => env('SANCTIONS_OFAC_CONSOLIDATED_URL', 'https://www.treasury.gov/ofac/downloads/consolidated/consolidated.xml'),
            'format' => 'XML',
            'list_type' => 'OFAC',
            'enabled' => env('SANCTIONS_OFAC_CONSOLIDATED_ENABLED', true),
            'description' => 'OFAC consolidated sanctions list (includes non-SDN sanctions)',
            'update_frequency' => 'daily',
            'timeout' => 300,
            'retry_attempts' => 3,
            'retry_delay' => 60,
        ],

        'moha' => [
            'name' => 'Malaysia MOHA Terrorism List',
            'url' => env('SANCTIONS_MOHA_URL', ''),
            'format' => 'CSV',
            'list_type' => 'MOHA',
            'enabled' => env('SANCTIONS_MOHA_ENABLED', false),
            'description' => 'Malaysia Ministry of Home Affairs designated terrorist organizations',
            'update_frequency' => 'daily',
            'timeout' => 120,
            'retry_attempts' => 3,
            'retry_delay' => 60,
            'note' => 'MOHA does not provide a public automated download URL. Manual import required.',
        ],

        'eu' => [
            'name' => 'EU Consolidated Financial Sanctions',
            'url' => env('SANCTIONS_EU_URL', 'https://webgate.ec.europa.eu/fsd/fsf/public/files/csvFullSanctionsList_1_1/content?token=n/a'),
            'format' => 'CSV',
            'list_type' => 'EU',
            'enabled' => env('SANCTIONS_EU_ENABLED', true),
            'description' => 'European Union consolidated list of financial sanctions',
            'update_frequency' => 'daily',
            'timeout' => 300,
            'retry_attempts' => 3,
            'retry_delay' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Download Settings
    |--------------------------------------------------------------------------
    |
    | General settings for downloading sanctions lists.
    |
    */

    'download' => [
        'temp_directory' => storage_path('app/temp/sanctions'),
        'archive_directory' => storage_path('app/archive/sanctions'),
        'keep_archives_days' => 30,
        'user_agent' => 'CEMS-MY/1.0 (Compliance Management System)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Update Schedule
    |--------------------------------------------------------------------------
    |
    | When to run automatic updates. Daily at 03:00 recommended to avoid
    | peak hours. BNM requires sanctions be updated within 24 hours.
    |
    */

    'schedule' => [
        'enabled' => env('SANCTIONS_AUTO_UPDATE_ENABLED', true),
        'time' => '03:00',
        'timezone' => env('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Who to notify when updates fail or significant changes are detected.
    |
    */

    'notifications' => [
        'enabled' => env('SANCTIONS_NOTIFICATIONS_ENABLED', true),
        'channels' => ['mail', 'database'],
        'recipients' => [
            'compliance' => env('SANCTIONS_COMPLIANCE_EMAIL'),
            'admin' => env('SANCTIONS_ADMIN_EMAIL'),
        ],
        'alert_on' => [
            'update_failed' => true,
            'new_entries_found' => true,
            'significant_changes' => true, // > 10% change
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Change Detection Thresholds
    |--------------------------------------------------------------------------
    |
    | When to trigger alerts based on changes between updates.
    |
    */

    'change_thresholds' => [
        'significant_percentage' => 10.0, // Alert if >10% of entries changed
        'minimum_new_entries' => 5, // Alert if 5+ new entries
        'minimum_removed_entries' => 5, // Alert if 5+ entries removed
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Rescreening
    |--------------------------------------------------------------------------
    |
    | Automatically rescreen customers when new entries are added.
    |
    */

    'rescreening' => [
        'enabled' => env('SANCTIONS_AUTOMATIC_RESREEN_ENABLED', true),
        'batch_size' => 100, // Process in batches
        'queue' => 'sanctions', // Dedicated queue
        'match_threshold' => 0.80, // Same as SanctionScreeningService
    ],

    /*
    |--------------------------------------------------------------------------
    | System User
    |--------------------------------------------------------------------------
    |
    | User ID to use for automated updates (for audit trail).
    | This should be a system/internal user.
    |
    */

    'system_user_id' => env('SANCTIONS_SYSTEM_USER_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for receiving sanctions update webhooks.
    | Some sanction list providers support webhooks for immediate updates.
    |
    */

    'webhook' => [
        'enabled' => env('SANCTIONS_WEBHOOK_ENABLED', false),
        'token' => env('SANCTIONS_WEBHOOK_TOKEN'),
        'allowed_ips' => explode(',', env('SANCTIONS_WEBHOOK_ALLOWED_IPS', '')),
        'rate_limit' => env('SANCTIONS_WEBHOOK_RATE_LIMIT', 10), // requests per minute
    ],
];
