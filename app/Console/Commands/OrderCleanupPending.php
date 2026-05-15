<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OrderCleanupPending extends Command
{
    protected $signature = 'order:cleanup-pending';
    protected $description = 'Cancel pending orders older than 24 hours';

    public function handle(): int
    {
        try {
            $cutoffTime = now()->subHours(24);

            $cancelled = Order::where('status', 'pending')
                ->where('created_at', '<', $cutoffTime)
                ->update(['status' => 'cancelled']);

            Log::info('Order cleanup completed', [
                'cancelled_orders' => $cancelled,
                'cutoff_time' => $cutoffTime,
            ]);

            $this->info("✓ Cancelled {$cancelled} pending orders older than 24 hours");
            return 0;
        } catch (\Exception $e) {
            Log::error('Order cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error("✗ Order cleanup failed: {$e->getMessage()}");
            return 1;
        }
    }
}
