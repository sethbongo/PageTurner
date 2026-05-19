<?php

namespace App\Listeners;

use App\Models\BackupMonitoring;
use Illuminate\Support\Facades\Cache;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Events\CleanupHasFailed;
use Spatie\Backup\Events\CleanupWasSuccessful;
use Spatie\Backup\Events\HealthyBackupWasFound;
use Spatie\Backup\Events\UnhealthyBackupWasFound;

class BackupEventListener
{
    public function handleBackupWasSuccessful(BackupWasSuccessful $event): void
    {
        $destination = $event->backupDestination;
        $backup = $destination->newestBackup();
        $path = $backup?->path() ?? $destination->backupName();
        $size = $backup?->sizeInBytes();
        $verified = $this->verifyBackup($backup);

        BackupMonitoring::create([
            'backup_name' => $destination->backupName(),
            'disk' => $destination->diskName(),
            'path' => $path,
            'size_bytes' => $size,
            'status' => 'completed',
            'type' => 'full',
            'started_at' => now(),
            'completed_at' => now(),
            'verified' => $verified,
            'verified_at' => $verified ? now() : null,
            'health_status' => $verified ? 'healthy' : 'warning',
            'next_backup_scheduled_at' => $this->nextBackupScheduledAt(),
            'metadata' => [
                'filesystem' => $destination->filesystemType(),
            ],
        ]);

        Cache::forget('admin_backup_status');
    }

    public function handleBackupHasFailed(BackupHasFailed $event): void
    {
        $destination = $event->backupDestination;

        BackupMonitoring::create([
            'backup_name' => $destination?->backupName() ?? config('backup.backup.name'),
            'disk' => $destination?->diskName() ?? 'unknown',
            'path' => $destination?->backupName() ?? 'unknown',
            'status' => 'failed',
            'type' => 'full',
            'started_at' => now(),
            'completed_at' => now(),
            'verified' => false,
            'error_message' => $event->exception->getMessage(),
            'health_status' => 'unhealthy',
            'next_backup_scheduled_at' => $this->nextBackupScheduledAt(),
        ]);

        Cache::forget('admin_backup_status');
    }

    public function handleHealthyBackupWasFound(HealthyBackupWasFound $event): void
    {
        $destination = $event->backupDestinationStatus->backupDestination();

        $record = BackupMonitoring::where('backup_name', $destination->backupName())
            ->where('disk', $destination->diskName())
            ->orderBy('completed_at', 'desc')
            ->first();

        if ($record) {
            $record->update([
                'health_status' => 'healthy',
                'verified' => true,
                'verified_at' => now(),
            ]);
        }

        Cache::forget('admin_backup_status');
    }

    public function handleUnhealthyBackupWasFound(UnhealthyBackupWasFound $event): void
    {
        $destination = $event->backupDestinationStatus->backupDestination();
        $failure = $event->backupDestinationStatus->getHealthCheckFailure();
        $errorMessage = $failure?->exception()->getMessage();

        $record = BackupMonitoring::where('backup_name', $destination->backupName())
            ->where('disk', $destination->diskName())
            ->orderBy('completed_at', 'desc')
            ->first();

        if ($record) {
            $record->update([
                'health_status' => 'unhealthy',
                'verified' => false,
                'error_message' => $errorMessage,
            ]);
        }

        Cache::forget('admin_backup_status');
    }

    public function handleCleanupWasSuccessful(CleanupWasSuccessful $event): void
    {
        $destination = $event->backupDestination;

        BackupMonitoring::create([
            'backup_name' => $destination->backupName(),
            'disk' => $destination->diskName(),
            'path' => $destination->backupName(),
            'status' => 'completed',
            'type' => 'cleanup',
            'started_at' => now(),
            'completed_at' => now(),
            'verified' => true,
            'health_status' => 'maintenance',
        ]);

        Cache::forget('admin_backup_status');
    }

    public function handleCleanupHasFailed(CleanupHasFailed $event): void
    {
        $destination = $event->backupDestination;

        BackupMonitoring::create([
            'backup_name' => $destination?->backupName() ?? config('backup.backup.name'),
            'disk' => $destination?->diskName() ?? 'unknown',
            'path' => $destination?->backupName() ?? 'unknown',
            'status' => 'failed',
            'type' => 'cleanup',
            'started_at' => now(),
            'completed_at' => now(),
            'verified' => false,
            'error_message' => $event->exception->getMessage(),
            'health_status' => 'maintenance',
        ]);

        Cache::forget('admin_backup_status');
    }

    private function verifyBackup(?Backup $backup): bool
    {
        if (!$backup || !$backup->exists()) {
            return false;
        }

        if ($backup->sizeInBytes() <= 0) {
            return false;
        }

        try {
            $stream = $backup->stream();
            if (is_resource($stream)) {
                fread($stream, 1);
                fclose($stream);
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function nextBackupScheduledAt(): \Illuminate\Support\Carbon
    {
        $next = now('UTC')->setTime(2, 0, 0);

        if ($next->isPast()) {
            $next->addDay();
        }

        return $next;
    }
}
