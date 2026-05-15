<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditArchive extends Command
{
    protected $signature = 'audit:archive';
    protected $description = 'Archive audit logs older than 1 year';

    public function handle(): int
    {
        try {
            $cutoffDate = now()->subYear();

            // Check if audit logs table exists
            if (!DB::connection()->getSchemaBuilder()->hasTable('audit_logs')) {
                $this->warn('Audit logs table does not exist. Skipping archive.');
                return 0;
            }

            $archived = DB::table('audit_logs')
                ->where('created_at', '<', $cutoffDate)
                ->update(['archived' => true]);

            Log::info('Audit archive completed', [
                'archived_records' => $archived,
                'cutoff_date' => $cutoffDate,
            ]);

            $this->info("✓ Archived {$archived} old audit log records");
            return 0;
        } catch (\Exception $e) {
            Log::error('Audit archive failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error("✗ Audit archive failed: {$e->getMessage()}");
            return 1;
        }
    }
}
