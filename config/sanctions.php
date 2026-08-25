<?php

return [
    'sources' => [
        'un_consolidated' => [
            'name' => 'UN Security Council Consolidated',
            'url' => env('SANCTIONS_UN_URL', 'https://www.opensanctions.org/datasets/un_sc_sanctions/targets.nested.json'),
            'format' => 'JSON',
            'frequency' => 'daily',
            'list_type' => 'international',
            'default_list' => true,
        ],
        'moha_malaysia' => [
            'name' => 'MOHA Malaysia Sanctions',
            'url' => env('SANCTIONS_MOHA_URL', 'https://www.opensanctions.org/datasets/my_moha_sanctions/targets.nested.json'),
            'format' => 'JSON',
            'frequency' => 'weekly',
            'list_type' => 'national',
            'default_list' => true,
        ],
        'eu_consolidated' => [
            'name' => 'EU Consolidated Sanctions',
            'url' => env('SANCTIONS_EU_URL', 'https://www.opensanctions.org/datasets/eu_fsf/targets.nested.json'),
            'format' => 'JSON',
            'frequency' => 'weekly',
            'list_type' => 'international',
            'default_list' => false,
        ],
        'ofac_sdn' => [
            'name' => 'US OFAC SDN List',
            'url' => env('SANCTIONS_OFAC_URL', 'https://www.opensanctions.org/datasets/us_ofac_sdn/targets.nested.json'),
            'format' => 'JSON',
            'frequency' => 'weekly',
            'list_type' => 'international',
            'default_list' => false,
        ],
    ],

    'matching' => [
        'threshold_flag' => 75.0,
        'threshold_block' => 90.0,
        'algorithm' => 'levenshtein',
        'use_dob' => true,
        'use_nationality' => true,
        'max_candidates' => 100,
    ],

    'import' => [
        'timeout' => 300,
        'retry_attempts' => 3,
        'retry_delay' => 60,
        'fallback_continue' => true,
    ],
];
