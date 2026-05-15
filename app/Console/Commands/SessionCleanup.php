<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SessionCleanup extends Command
{
    protected $signature = 'session:cleanup';
    protected $description = 'Clear expired sessions from database';

    public function handle(): int
    {
        try {
            $lifetime = config('session.lifetime') * 60;
            $expiresAt = now()->subSeconds($lifetime);

            $deleted = DB::table('sessions')
                ->where('last_activity', '<', $expiresAt->timestamp)
                ->delete();

            Log::info('Session cleanup completed', [
                'deleted_sessions' => $deleted,
                'expires_before' => $expiresAt,
            ]);

            $this->info("✓ Cleaned up {$deleted} expired sessions");
            return 0;
        } catch (\Exception $e) {
            Log::error('Session cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error("✗ Session cleanup failed: {$e->getMessage()}");
            return 1;
        }
    }
}
