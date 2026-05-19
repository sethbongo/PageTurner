<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsCollection;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
        'checksum',
        'archived',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'metadata' => 'json',
        'archived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model
     */
    public function auditable()
    {
        if (!$this->auditable_type || !$this->auditable_id) {
            return null;
        }

        return $this->auditable_type::findOrNull($this->auditable_id);
    }

    /**
     * Get sensitive fields that should never be logged
     */
    public static function getSensitiveFields(): array
    {
        return [
            'password',
            'password_confirmation',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'api_token',
            'access_token',
            'refresh_token',
            'card_number',
            'cvv',
            'stripe_token',
            'payment_method_id',
            'credit_card',
            'phone',
            'social_security_number',
        ];
    }

    /**
     * Filter out sensitive data from attributes
     */
    public static function filterSensitiveData(array $data): array
    {
        $sensitiveFields = self::getSensitiveFields();

        foreach ($sensitiveFields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Get changes as a readable diff
     */
    public function getDiff(): array
    {
        return [
            'old' => $this->old_values,
            'new' => $this->new_values,
        ];
    }

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

    /**
     * Scope: Filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Only non-archived logs
     */
    public function scopeActive($query)
    {
        return $query->where('archived', false);
    }

    /**
     * Scope: Only archived logs
     */
    public function scopeArchived($query)
    {
        return $query->where('archived', true);
    }
}

