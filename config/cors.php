<?php

return [
    // Rutas sujetas a CORS. La API de agentes vive bajo /api/*.
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Orígenes de primera parte (no wildcard). Añade otros vía CORS_ALLOWED_ORIGINS
    // (lista separada por comas) si necesitas apps/clientes adicionales.
    'allowed_origins' => array_values(array_unique(array_filter(array_merge(
        [env('APP_URL', 'https://fifardos.com'), 'https://fifardos.com'],
        array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // La API usa Bearer token (Sanctum), no cookies → sin credenciales cross-origin.
    'supports_credentials' => false,
];
