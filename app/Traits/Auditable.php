<?php

namespace App\Traits;

use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the auditable trait
     */
    public static function bootAuditable()
    {
        static::created(function ($model) {
            app(AuditService::class)->log(
                'created',
                $model,
                null,
                $model->getAttributesToLog()
            );
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();

            // Only log if there are actual changes
            if (!empty($changes)) {
                $oldValues = [];

                foreach (array_keys($changes) as $key) {
                    $oldValues[$key] = $model->getOriginal($key);
                }

                app(AuditService::class)->log(
                    'updated',
                    $model,
                    $oldValues,
                    $model->getAttributesToLog()
                );
            }
        });

        static::deleted(function ($model) {
            app(AuditService::class)->log(
                'deleted',
                $model,
                $model->getAttributesToLog(),
                null
            );
        });
    }

    /**
     * Get attributes to be logged (excludes sensitive fields)
     */
    public function getAttributesToLog(): array
    {
        $attributes = $this->getAttributes();
        return \App\Models\AuditLog::filterSensitiveData($attributes);
    }

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return \App\Models\AuditLog::where('auditable_type', static::class)
            ->where('auditable_id', $this->getKey())
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest audit log
     */
    public function getLatestAuditLog()
    {
        return $this->auditLogs()->first();
    }
}
