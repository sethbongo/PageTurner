<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LogRotate extends Command
{
    protected $signature = 'log:rotate';
    protected $description = 'Archive and compress old logs';

    public function handle(): int
    {
        try {
            $logsPath = storage_path('logs');
            $archivePath = storage_path('logs/archives');

            if (!is_dir($archivePath)) {
                mkdir($archivePath, 0755, true);
            }

            $files = glob($logsPath . '/laravel-*.log');
            $archived = 0;

            foreach ($files as $file) {
                $filename = basename($file);
                $modifiedTime = filemtime($file);

                // Archive logs older than 7 days
                if ((time() - $modifiedTime) > (7 * 24 * 60 * 60)) {
                    $zipName = $archivePath . '/' . str_replace('.log', '', $filename) . '-' . date('Y-m-d-His', $modifiedTime) . '.gz';

                    $command = "gzip -c '{$file}' > '{$zipName}'";
                    shell_exec($command);

                    // Keep original for current week
                    if ((time() - $modifiedTime) > (14 * 24 * 60 * 60)) {
                        unlink($file);
                    }

                    $archived++;
                }
            }

            Log::info('Log rotation completed', [
                'archived_logs' => $archived,
            ]);

            $this->info("✓ Archived and compressed {$archived} old log files");
            return 0;
        } catch (\Exception $e) {
            Log::error('Log rotation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error("✗ Log rotation failed: {$e->getMessage()}");
            return 1;
        }
    }
}
