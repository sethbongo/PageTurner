<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduledTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'command',
        'expression',
        'enabled',
        'last_run_at',
        'next_run_at',
        'last_status',
        'last_output',
        'run_count',
        'failure_count',
        'duration_ms',
        'metadata',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'run_count' => 'integer',
        'failure_count' => 'integer',
        'duration_ms' => 'integer',
        'metadata' => 'array',
    ];

    public function recordRun(string $status, ?string $output = null, ?int $duration = null): void
    {
        $this->update([
            'last_run_at' => now(),
            'last_status' => $status,
            'last_output' => $output,
            'duration_ms' => $duration,
            'run_count' => $this->run_count + 1,
            'failure_count' => $status === 'failed' ? $this->failure_count + 1 : $this->failure_count,
        ]);
    }

    public function getSuccessRate(): float
    {
        if ($this->run_count === 0) {
            return 0;
        }
        return (($this->run_count - $this->failure_count) / $this->run_count) * 100;
    }
}
