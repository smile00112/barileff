<?php

return [
    // Authorization key (Base64(Client ID:Client Secret)). Optional if client_id/client_secret specified.
    'auth_key' => env('GIGACHAT_AUTH_KEY', null),

    // Alternatively, specify Client ID and Client Secret to auto-generate auth_key
    'client_id' => env('GIGACHAT_CLIENT_ID', null),
    'client_secret' => env('GIGACHAT_CLIENT_SECRET', null),

    // API version scope: GIGACHAT_API_PERS | GIGACHAT_API_B2B | GIGACHAT_API_CORP
    'scope' => env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS'),

    // TLS certificate chain path or true to use system CA bundle
    'verify' => env('GIGACHAT_CERT_PATH', true),

    // Base URIs
    'base_uri' => env('GIGACHAT_BASE_URI', 'https://gigachat.devices.sberbank.ru'),
    'oauth_uri' => env('GIGACHAT_OAUTH_URI', 'https://ngw.devices.sberbank.ru:9443'),

    // Default model name
    'default_model' => env('GIGACHAT_DEFAULT_MODEL', 'GigaChat-2'),

    // Default generation options
    'default_options' => [
        'temperature' => (float) env('GIGACHAT_TEMPERATURE', 0.7),
        'max_tokens' => (int) env('GIGACHAT_MAX_TOKENS', 1000),
        'top_p' => (float) env('GIGACHAT_TOP_P', 0.9),
        'repetition_penalty' => (float) env('GIGACHAT_REPETITION_PENALTY', 1.1),
    ],

    // Rate limiting settings
    'rate_limit' => [
        'enabled' => env('GIGACHAT_RATE_LIMIT_ENABLED', true),
        'max_attempts' => (int) env('GIGACHAT_RATE_LIMIT_MAX_ATTEMPTS', 60),
        'decay_minutes' => (int) env('GIGACHAT_RATE_LIMIT_DECAY_MINUTES', 1),
    ],

    // Logging settings
    'logging' => [
        'enabled' => env('GIGACHAT_LOGGING_ENABLED', false),
        'channel' => env('GIGACHAT_LOG_CHANNEL', 'default'),
        'level' => env('GIGACHAT_LOG_LEVEL', 'info'),
    ],
];
