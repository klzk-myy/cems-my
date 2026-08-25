<?php

return [
    /*
    |--------------------------------------------------------------------------
    | STR (Suspicious Transaction Report) Settings
    |--------------------------------------------------------------------------
    */

    'str_auto_generate' => env('STR_AUTO_GENERATE', true),
    'str_approval_required' => env('STR_APPROVAL_REQUIRED', true),

    'public_holidays' => (function () {
        $holidays = env('BNM_PUBLIC_HOLIDAYS', '');

        return $holidays ? explode(',', $holidays) : [];
    })(),

    'domestic_nationalities' => array_map('trim', explode(',', env('DOMESTIC_NATIONALITIES', 'Malaysian,Malaysia'))),
];
