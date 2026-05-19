<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BackupMonitoring extends Model
{
    use HasFactory;

    protected $table = 'backup_monitoring';

    protected $fillable = [
        'backup_name',
        'disk',
        'path',
        'size_bytes',
        'status',
        'type',
        'started_at',
        'completed_at',
        'duration_seconds',
        'verified',
        'verified_at',
        'error_message',
        'metadata',
        'health_status',
        'next_backup_scheduled_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'verified' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'next_backup_scheduled_at' => 'datetime',
        'duration_seconds' => 'integer',
        'metadata' => 'array',
    ];

    public function isHealthy(): bool
    {
        return $this->health_status === 'healthy' && $this->verified;
    }

    public function isStale(int $hoursThreshold = 24): bool
    {
        return $this->completed_at?->addHours($hoursThreshold)->isPast() ?? false;
    }

    public function getFormattedSize(): string
    {
        $bytes = $this->size_bytes ?? 0;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
