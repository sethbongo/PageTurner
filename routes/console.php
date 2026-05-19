<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\SendDailySalesReport;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('report:daily-sales', function () {
    return app(SendDailySalesReport::class)->handle();
})->purpose('Send daily sales report to administrators');

// ==========================================
// BACKUP & MAINTENANCE SCHEDULED TASKS (4.2)
// ==========================================

// 4.2.1 Database Backup Scheduling
Schedule::command('backup:run')
    ->dailyAt('02:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Backup failed to complete');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Backup completed successfully');
    });

// 4.2.2 Maintenance Tasks

// Daily backup cleanup (remove old backups per retention policy)
Schedule::command('backup:clean')
    ->dailyAt('03:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Backup cleanup failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Backup cleanup completed');
    });

// Hourly: Cancel pending orders older than 24 hours
Schedule::command('order:cleanup-pending')
    ->hourly()
    ->withoutOverlapping(3600)
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Order cleanup failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Order cleanup completed');
    });

// Daily: Clear expired sessions
Schedule::command('session:cleanup')
    ->daily()
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Session cleanup failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Session cleanup completed');
    });

// Weekly: Archive and compress old logs (Monday at 01:00)
Schedule::command('log:rotate')
    ->weekly()
    ->mondays()
    ->at('01:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Log rotation failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Log rotation completed');
    });

// Daily: Generate daily sales report
Schedule::command('report:daily-sales')
    ->dailyAt('06:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Daily sales report generation failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Daily sales report generated');
    });

// Weekly: Delete old notification records (Sunday at 04:00)
Schedule::command('notification:prune')
    ->weekly()
    ->sundays()
    ->at('04:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Notification prune failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Notification prune completed');
    });

// Monthly: Archive audit logs older than 1 year (1st of month at 05:00)
Schedule::command('audit:archive')
    ->monthlyOn(1, '05:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Audit archive failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Audit archive completed');
    });

// Weekly: Send backup health check and summary (Sunday at 07:00)
Schedule::command('backup:monitor')
    ->weekly()
    ->sundays()
    ->at('07:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Backup monitoring failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Backup monitoring completed');
    });

// Hourly: Refresh materialized views
Schedule::command('app:refresh-materialized-views')
    ->hourly()
    ->withoutOverlapping();
