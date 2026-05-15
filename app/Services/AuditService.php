<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Log an audit event
     */
    public function log(
        string $event,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): AuditLog {
        $user = Auth::user();

        // Filter sensitive data
        if ($oldValues) {
            $oldValues = AuditLog::filterSensitiveData($oldValues);
        }
        if ($newValues) {
            $newValues = AuditLog::filterSensitiveData($newValues);
        }

        // Build metadata
        if (!$metadata) {
            $metadata = $this->buildMetadata();
        }

        // Create audit log entry
        $auditLog = new AuditLog([
            'id' => Str::uuid(),
            'event' => $event,
            'auditable_type' => $model ? $model::class : null,
            'auditable_id' => $model ? $model->getKey() : null,
            'user_id' => $user?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'checksum' => $this->generateChecksum(
                $event,
                $model,
                $oldValues,
                $newValues,
                $user?->id
            ),
            'archived' => false,
        ]);

        $auditLog->save();

        return $auditLog;
    }

    /**
     * Log authentication events
     */
    public function logLogin(int $userId): AuditLog
    {
        return $this->log('login', metadata: [
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log logout events
     */
    public function logLogout(?int $userId = null): AuditLog
    {
        $user = $userId ? \App\Models\User::find($userId) : Auth::user();

        return $this->log('logout', metadata: [
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log failed login attempt
     */
    public function logFailedLogin(string $email): AuditLog
    {
        return $this->log('failed_login', metadata: [
            'email' => $email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log password change
     */
    public function logPasswordChange(int $userId): AuditLog
    {
        return $this->log('password_changed', metadata: [
            'user_id' => $userId,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log 2FA enable/disable
     */
    public function log2FAToggle(int $userId, bool $enabled): AuditLog
    {
        return $this->log($enabled ? '2fa_enabled' : '2fa_disabled', metadata: [
            'user_id' => $userId,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log permission/role changes
     */
    public function logRoleChange(int $userId, string $oldRole, string $newRole): AuditLog
    {
        return $this->log('role_changed', metadata: [
            'user_id' => $userId,
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log backup operations
     */
    public function logBackup(string $status, ?string $message = null): AuditLog
    {
        return $this->log('backup_' . $status, metadata: [
            'message' => $message,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Log import/export operations
     */
    public function logImportExport(string $operation, string $fileType, int $recordCount, ?string $message = null): AuditLog
    {
        return $this->log($operation, metadata: [
            'file_type' => $fileType,
            'record_count' => $recordCount,
            'message' => $message,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Build metadata from current request
     */
    protected function buildMetadata(): array
    {
        return [
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'referer' => Request::header('referer'),
        ];
    }

    /**
     * Generate tamper-proof checksum for audit log entry
     */
    protected function generateChecksum(
        string $event,
        ?Model $model,
        ?array $oldValues,
        ?array $newValues,
        ?int $userId
    ): string {
        $data = [
            'event' => $event,
            'model_type' => $model?->getMorphClass() ?? null,
            'model_id' => $model?->getKey() ?? null,
            'user_id' => $userId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ];

        return hash('sha256', json_encode($data) . config('app.key'));
    }

    /**
     * Verify checksum integrity of audit log
     */
    public function verifyChecksum(AuditLog $auditLog): bool
    {
        $expectedChecksum = $this->generateChecksum(
            $auditLog->event,
            $auditLog->auditable,
            $auditLog->old_values,
            $auditLog->new_values,
            $auditLog->user_id
        );

        return hash_equals($auditLog->checksum, $expectedChecksum);
    }
}
