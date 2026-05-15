<?php

namespace App\Console\Commands;

use App\Exports\RevenueSummaryExport;
use App\Mail\DailySalesReportMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SendDailySalesReport extends Command
{
    protected $signature = 'report:daily-sales';
    protected $description = 'Send daily sales report to administrators.';

    public function handle(): int
    {
        $date = now()->subDay()->toDateString();
        $filters = [
            'date_from' => $date,
            'date_to' => $date,
        ];

        $filename = 'reports/daily-sales-' . now()->format('Ymd') . '.csv';
        Excel::store(new RevenueSummaryExport($filters), $filename, 'local');

        $ordersQuery = Order::query()
            ->whereNot('status', 'Cart')
            ->whereDate('created_at', $date);

        $totalOrders = $ordersQuery->count();
        $totalRevenue = (float) $ordersQuery->sum('total_amount');

        $admins = User::query()->where('role', 'admin')->pluck('email')->filter()->all();

        if (empty($admins)) {
            $this->warn('No admin email addresses found.');
            return Command::SUCCESS;
        }

        $attachmentPath = Storage::disk('local')->path($filename);

        Mail::to($admins)->send(new DailySalesReportMail(
            $date,
            $totalOrders,
            $totalRevenue,
            $attachmentPath
        ));

        $this->info('Daily sales report sent to administrators.');

        return Command::SUCCESS;
    }
}
