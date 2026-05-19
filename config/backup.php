<?php

return [
    'backup' => [
        'name' => env('APP_NAME', 'pageturner-bookstore'),

        'source' => [
            'files' => [
                'include' => [
                    base_path(),
                ],
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    base_path('.git'),
                    base_path('bootstrap/cache'),
                    base_path('storage/framework/cache'),
                    base_path('storage/logs'),
                    base_path('.env.*.php'),
                    base_path('storage/framework/views'),
                ],
                'follow_links' => false,
                'timeout' => 1800,
                'relative_path' => base_path(),
            ],
            'databases' => [
                'pgsql',
            ],
        ],

        'database_dump' => [
            'pgsql' => [
                'dump_command_path' => env('PG_DUMP_PATH', 'pg_dump'),
                'restore_command_path' => env('PG_RESTORE_PATH', 'pg_restore'),
                'ignore_tables' => [
                    'sessions',
                    'failed_jobs',
                ],
                'timeout' => 60,
                'use_custom_binary' => true,
            ],
        ],

        'destination' => [
            'disks' => [
                'local',
            ],
        ],

        'password' => env('BACKUP_ENCRYPTION_PASSWORD'),

        'compression' => 'gzip',

        'cleanup' => [
            'default_strategy' => 'keep_all',

            'strategies' => [
                'keep_all' => \Spatie\Backup\Tasks\Cleanup\Strategies\KeepAllBackupsStrategy::class,
                'keep_recent' => [
                    'amount_of_backups_to_keep' => 10,
                    'delete_oldest_backups_instead_of_recent' => false,
                ],
                'keep_period' => \Spatie\Backup\Tasks\Cleanup\Strategies\KeepBackupsFromPeriodStrategy::class,
                'keep_count' => [
                    'keep_daily_backups_for_days' => 7,
                    'keep_weekly_backups_for_weeks' => 4,
                    'keep_monthly_backups_for_months' => 12,
                    'delete_oldest_backups_instead_of_recent' => false,
                ],
            ],
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'show_console_output' => false,

        'log_channel' => null,
    ],


    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'pageturner-bookstore'),
            'disks' => ['local'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],
];
