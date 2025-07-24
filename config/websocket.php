<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebSocket Server Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the WebSocket server settings for your application.
    |
    */

    'host' => env('WEBSOCKET_HOST', '0.0.0.0'),
    'port' => env('WEBSOCKET_PORT', 6001),
    'scheme' => env('WEBSOCKET_SCHEME', 'ws'),

    /*
    |--------------------------------------------------------------------------
    | WebSocket Authentication
    |--------------------------------------------------------------------------
    |
    | Configure authentication settings for WebSocket connections.
    |
    */

    'auth' => [
        'enabled' => env('WEBSOCKET_AUTH_ENABLED', true),
        'guard' => env('WEBSOCKET_AUTH_GUARD', 'web'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket Channels
    |--------------------------------------------------------------------------
    |
    | Configure default channels and their settings.
    |
    */

    'channels' => [
        'public' => [
            'enabled' => true,
            'auth_required' => false,
        ],
        'private' => [
            'enabled' => true,
            'auth_required' => true,
        ],
        'presence' => [
            'enabled' => true,
            'auth_required' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging settings for WebSocket events.
    |
    */

    'logging' => [
        'enabled' => env('WEBSOCKET_LOGGING_ENABLED', true),
        'level' => env('WEBSOCKET_LOG_LEVEL', 'info'),
    ],
]; 