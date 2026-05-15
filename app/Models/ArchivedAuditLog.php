<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivedAuditLog extends Model
{
    protected $table = 'archived_audit_logs';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
        'checksum',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: Filter by event type
     */
    public function scopeByEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope: Filter by model type
     */
    public function scopeByModel($query, $modelType)
    {
        return $query->where('auditable_type', $modelType);
    }
}
