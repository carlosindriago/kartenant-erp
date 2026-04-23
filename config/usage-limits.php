<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Usage Limits Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the usage tracking and limits system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'ttl' => env('USAGE_CACHE_TTL', 3600), // 1 hour
        'counter_ttl' => env('USAGE_COUNTER_TTL', 86400 * 32), // 32 days
        'prefix' => env('USAGE_CACHE_PREFIX', 'tenant_usage'),
        'counter_prefix' => env('USAGE_COUNTER_PREFIX', 'usage_counter'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Traffic Light Thresholds
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'warning' => 80,      // 80% - Show warnings
        'overdraft' => 100,   // 100% - Allow but flag for upgrade
        'critical' => 120,    // 120% - Block new creations
    ],

    /*
    |--------------------------------------------------------------------------
    | Bypass Rules
    |--------------------------------------------------------------------------
    | Define which actions should never be blocked
    */
    'bypass_rules' => [
        // Sales should never be blocked for business continuity
        'sales' => [
            'always_allow' => true,
            'reason' => 'Business continuity - Sales are revenue generating',
        ],

        // Critical operations that should never be blocked
        'critical_operations' => [
            'user_logout' => true,
            'password_change' => true,
            'billing_view' => true,
            'usage_view' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'enabled' => env('USAGE_ALERTS_ENABLED', true),

        'channels' => [
            'email' => [
                'enabled' => env('USAGE_EMAIL_ALERTS_ENABLED', true),
                'to_tenant' => true,
                'to_sales_team' => true,
                'sales_team_email' => env('USAGE_SALES_TEAM_EMAIL', 'sales@emporiodigital.com'),
            ],

            'slack' => [
                'enabled' => env('USAGE_SLACK_ALERTS_ENABLED', false),
                'webhook_key' => 'usage_alerts',
                'mention_channel' => env('USAGE_SLACK_MENTION_CHANNEL', false),
            ],

            'in_app' => [
                'enabled' => true,
                'banner_dismissal_hours' => 1, // Hours before banner can show again
            ],
        ],

        'cooldown' => [
            'warning' => 24 * 60,  // 24 hours in minutes
            'overdraft' => 12 * 60, // 12 hours in minutes
            'critical' => 60,      // 1 hour in minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing Integration
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'auto_upgrade_required' => true,
        'grace_period_days' => 3,
        'upgrade_buffer_percentage' => 50, // Recommend plan 50% larger than current usage
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Configuration
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'sales' => [
            'name' => 'Ventas Mensuales',
            'unit' => 'count',
            'tracked_by' => 'observer', // 'observer', 'manual', 'api'
        ],

        'products' => [
            'name' => 'Productos',
            'unit' => 'count',
            'tracked_by' => 'observer',
        ],

        'users' => [
            'name' => 'Usuarios Activos',
            'unit' => 'count',
            'tracked_by' => 'observer',
            'exclude_super_admin' => true,
            'only_active' => true,
        ],

        'storage' => [
            'name' => 'Almacenamiento',
            'unit' => 'mb',
            'tracked_by' => 'observer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'async_processing' => env('USAGE_ASYNC_PROCESSING', true),
        'batch_size' => 100,
        'queue_connection' => env('USAGE_QUEUE_CONNECTION', 'default'),
        'queue_name' => env('USAGE_QUEUE_NAME', 'usage-tracking'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('USAGE_MONITORING_ENABLED', true),
        'log_level' => env('USAGE_LOG_LEVEL', 'info'),
        'track_performance' => env('USAGE_TRACK_PERFORMANCE', true),
        'alert_on_errors' => env('USAGE_ALERT_ON_ERRORS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'show_usage_in_navigation' => true,
        'show_usage_widget' => true,
        'refresh_interval_seconds' => 300, // 5 minutes
        'chart_days' => 7, // Days to show in usage charts
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    */
    'development' => [
        'debug_mode' => env('USAGE_DEBUG_MODE', false),
        'test_alerts' => env('USAGE_TEST_ALERTS', false),
        'bypass_limits' => env('USAGE_BYPASS_LIMITS', false),
        'log_all_requests' => env('USAGE_LOG_ALL_REQUESTS', false),
    ],
];
