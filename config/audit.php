<?php

return [
    /**
     * Audit Logging Configuration
     */

    // Number of days to keep active audit logs (1 year = 365 days)
    'retention_days' => env('AUDIT_RETENTION_DAYS', 365),

    // Number of years to keep archived audit logs (5 years)
    'archive_retention_years' => env('AUDIT_ARCHIVE_RETENTION_YEARS', 5),

    // Enable/disable audit logging globally
    'enabled' => env('AUDIT_ENABLED', true),

    // Sensitive fields to exclude from audit logs
    'sensitive_fields' => [
        'password',
        'password_confirmation',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
        'access_token',
        'refresh_token',
        'card_number',
        'cvv',
        'stripe_token',
        'payment_method_id',
        'credit_card',
        'phone',
        'social_security_number',
    ],

    // Models to audit automatically
    'auditable_models' => [
        'App\Models\Book',
        'App\Models\Order',
        'App\Models\Category',
        'App\Models\Review',
        'App\Models\User',
    ],

    // Critical events that trigger notifications
    'critical_events' => [
        'role_changed',
        'force_deleted',
        '2fa_disabled',
        'password_changed',
        'backup_failed',
        'permission_granted',
        'permission_revoked',
        'failed_login',
    ],

    // Email addresses to notify on critical events
    'notification_emails' => env('AUDIT_NOTIFICATION_EMAILS', 'admin@example.com'),

    // Whether to log to database
    'log_to_database' => true,

    // Whether to log to file
    'log_to_file' => env('AUDIT_LOG_TO_FILE', false),
    'log_file_path' => storage_path('logs/audit.log'),
];
