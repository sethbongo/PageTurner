<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ArchiveAuditLogs extends Command
{
    protected $signature = 'audit:archive {--days=365 : Number of days to keep active logs}';

    protected $description = 'Archive audit logs older than specified days and delete very old archived logs';

    public function handle()
    {
        $daysToKeep = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        $archiveDeleteDate = Carbon::now()->subYears(5);

        $this->info("Archiving logs older than {$daysToKeep} days ({$cutoffDate->format('Y-m-d')})...");

        // Count logs to archive
        $logsToArchive = AuditLog::where('archived', false)
            ->where('created_at', '<', $cutoffDate)
            ->count();

        if ($logsToArchive === 0) {
            $this->info('No logs to archive.');
            return;
        }

        // Archive logs in batches
        AuditLog::where('archived', false)
            ->where('created_at', '<', $cutoffDate)
            ->chunkById(1000, function ($logs) {
                foreach ($logs as $log) {
                    \App\Models\ArchivedAuditLog::create([
                        'id' => $log->id,
                        'user_id' => $log->user_id,
                        'event' => $log->event,
                        'auditable_type' => $log->auditable_type,
                        'auditable_id' => $log->auditable_id,
                        'old_values' => $log->old_values,
                        'new_values' => $log->new_values,
                        'metadata' => $log->metadata,
                        'checksum' => $log->checksum,
                        'created_at' => $log->created_at,
                        'updated_at' => $log->updated_at,
                    ]);

                    $log->delete();
                }
            });

        $this->info("Archived {$logsToArchive} logs.");

        // Delete very old archived logs (5+ years)
        $this->info("Deleting archived logs older than 5 years...");

        $archivedDeleted = \App\Models\ArchivedAuditLog::where('created_at', '<', $archiveDeleteDate)
            ->delete();

        $this->info("Deleted {$archivedDeleted} archived logs older than 5 years.");

        $this->info('Audit log archival complete.');
    }
}
