<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationPrune extends Command
{
    protected $signature = 'notification:prune';
    protected $description = 'Delete old notification records older than 90 days';

    public function handle(): int
    {
        try {
            $cutoffDate = now()->subDays(90);

            $deleted = DB::table('notifications')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            Log::info('Notification prune completed', [
                'deleted_notifications' => $deleted,
                'cutoff_date' => $cutoffDate,
            ]);

            $this->info("✓ Deleted {$deleted} old notification records");
            return 0;
        } catch (\Exception $e) {
            Log::error('Notification prune failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error("✗ Notification prune failed: {$e->getMessage()}");
            return 1;
        }
    }
}
