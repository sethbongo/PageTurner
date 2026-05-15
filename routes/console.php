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

Schedule::command('report:daily-sales')->dailyAt('23:55');
