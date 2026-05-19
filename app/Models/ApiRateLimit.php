<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiRateLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'endpoint',
        'method',
        'ip_address',
        'api_key_prefix',
        'requests_count',
        'limit',
        'window_seconds',
        'rate_limited',
        'reset_at',
        'metadata',
    ];

    protected $casts = [
        'requests_count' => 'integer',
        'limit' => 'integer',
        'window_seconds' => 'integer',
        'rate_limited' => 'boolean',
        'reset_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isRateLimited(): bool
    {
        return $this->rate_limited && $this->reset_at && $this->reset_at->isFuture();
    }

    public function resetLimit(): void
    {
        $this->update([
            'requests_count' => 0,
            'rate_limited' => false,
            'reset_at' => null,
        ]);
    }
}
