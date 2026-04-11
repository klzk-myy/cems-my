<?php

/**
 * Security configuration for CEMS-MY.
 *
 * This file contains security-related settings including:
 * - HSTS (HTTP Strict Transport Security)
 * - Rate limiting
 * - Session security
 * - Password policies
 *
 * BNM Compliance: These settings help meet AML/CFT security requirements.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security (HSTS)
    |--------------------------------------------------------------------------
    |
    | HSTS instructs browsers to always use HTTPS connections.
    | Required for BNM compliance on production systems.
    |
    */

    'hsts_max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000), // 1 year
    'hsts_include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
    'hsts_preload' => env('SECURITY_HSTS_PRELOAD', false),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | BNM requires rate limiting to prevent brute force attacks
    | and ensure system availability.
    |
    */

    'rate_limits' => [
        'login' => [
            'attempts' => 5,
            'per_minutes' => 1,
        ],
        'api' => [
            'attempts' => 60,
            'per_minutes' => 1,
        ],
        'transactions' => [
            'attempts' => 30,
            'per_minutes' => 1,
        ],
        'str' => [
            'attempts' => 10,
            'per_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Secure session configuration for BNM compliance.
    |
    */

    'session' => [
        'lifetime' => env('SESSION_LIFETIME', 480), // 8 hours (480 minutes)
        'expire_on_close' => false,
        'secure' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => true,
        'same_site' => 'strict',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | BNM requires minimum 12 characters with mixed case, numbers, and symbols.
    |
    */

    'password' => [
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'max_attempts' => 5,
        'lockout_duration' => 15, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log Security
    |--------------------------------------------------------------------------
    |
    | Tamper-evident audit logging configuration.
    |
    */

    'audit' => [
        'hash_algorithm' => 'sha256',
        'retention_days' => env('AUDIT_RETENTION_DAYS', 2555), // 7 years (BNM requirement)
        'encrypt_sensitive' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | CSP directives for XSS protection.
    |
    */

    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', true),
        'report_only' => env('SECURITY_CSP_REPORT_ONLY', false),
        'report_uri' => env('SECURITY_CSP_REPORT_URI', null),
    ],
];
