<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    |
    | Configure CSP directives for the application.
    |
    */

    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'report_only' => env('CSP_REPORT_ONLY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Sanitization
    |--------------------------------------------------------------------------
    |
    | Configure sanitization rules for different content types.
    |
    */

    'sanitization' => [
        // Maximum content length for FAQ entries
        'max_content_length' => 5000,

        // Maximum number of trigger keywords per FAQ entry
        'max_keywords' => 20,

        // Maximum length per keyword
        'max_keyword_length' => 100,

        // Allowed HTML tags in content (whitelist approach)
        'allowed_tags' => ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li'],

        // Regex pattern for response_key validation
        'response_key_pattern' => '/^[a-zA-Z0-9\s\-_]+$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for different route groups.
    |
    */

    'rate_limits' => [
        'agent_faq' => [
            'max_attempts' => 30,
            'decay_minutes' => 1,
        ],
        'manager_faq' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
        'ai_chat' => [
            'max_attempts' => 20,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configure AI service settings (Gemini Free tier).
    |
    */

    'ai' => [
        'daily_limit' => env('AI_DAILY_LIMIT', 1500),
        'rpm_limit' => env('AI_RPM_LIMIT', 15),
        'timeout' => env('AI_TIMEOUT', 30),
        'max_tokens' => env('AI_MAX_TOKENS', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configure audit logging for compliance (PDPA Malaysia).
    |
    */

    'audit' => [
        'enabled' => env('AUDIT_ENABLED', true),
        'log_level' => env('AUDIT_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | Configure password requirements.
    |
    */

    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_special' => false,
    ],

];
