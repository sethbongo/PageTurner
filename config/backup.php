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
            ],
            'databases' => [
                'mysql',
            ],
        ],

        'database_dump' => [
            'mysql' => [
                'dump_command_path' => env('MYSQLDUMP_PATH', 'mysqldump'),
                'restore_command_path' => env('MYSQL_RESTORE_PATH', 'mysql'),
                'ignore_tables' => [
                    'sessions',
                    'failed_jobs',
                ],
                'use_single_transaction' => true,
                'timeout' => 60,
                'set_names_utf8' => true,
            ],
        ],

        'destination' => [
            'disks' => [
                'local',
                's3',
            ],
        ],

        'password' => env('BACKUP_ENCRYPTION_PASSWORD'),

        'compression' => 'gzip',

        'notifications' => [
            'notifications' => [
                \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class,
                \Spatie\Backup\Notifications\Notifications\UnhealthyBackupFoundNotification::class,
            ],

            'notifiable' => \App\Notifications\BackupNotifiable::class,

            'mail' => [
                'to' => env('BACKUP_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),
            ],

            'slack' => [
                'webhook_url' => env('BACKUP_SLACK_WEBHOOK'),
                'channel' => '#backups',
                'username' => 'Backup Bot',
                'icon' => ':package:',
            ],
        ],

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
        'enabled' => true,

        'monitoring_jobs' => [
            \Spatie\Backup\Tasks\Monitor\HealthChecks\HealthCheck::class,
        ],

        'notification_channels' => ['mail'],

        'mail' => [
            'to' => env('BACKUP_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),
        ],

        'slack' => [
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK'),
        ],

        'checks' => [
            \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageUsedOnAnyDisk::class => [
                'max_storage_used' => 5000, // in MB
            ],
            \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeOfTheOldestBackupInDays::class => [
                'max_age_in_days' => 1,
            ],
        ],
    ],
];
