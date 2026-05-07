<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted network protection
    |--------------------------------------------------------------------------
    |
    | Keep this disabled locally until you are ready to verify the office IPs
    | or VPN ranges that should be able to reach the ERP in production.
    |
    */
    'enabled' => env('TRUSTED_NETWORKS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Allowed IPs and CIDR ranges
    |--------------------------------------------------------------------------
    |
    | Examples:
    |  - 127.0.0.1
    |  - ::1
    |  - 203.0.113.14
    |  - 198.51.100.0/24
    |
    */
    'allowed' => array_values(array_filter(array_map(
        static fn ($network) => trim($network),
        explode(',', (string) env('TRUSTED_NETWORKS_ALLOWED', '127.0.0.1,::1'))
    ))),

    'denied_message' => env(
        'TRUSTED_NETWORKS_DENIED_MESSAGE',
        'This ERP is available only from the company network or VPN.'
    ),
];
