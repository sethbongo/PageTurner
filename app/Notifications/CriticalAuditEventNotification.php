<?php

namespace App\Notifications;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CriticalAuditEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $auditLog;

    public function __construct(AuditLog $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventLabel = $this->getEventLabel($this->auditLog->event);
        $severity = $this->getEventSeverity($this->auditLog->event);

        return (new MailMessage)
            ->subject("🚨 Critical Security Event: {$eventLabel}")
            ->view('emails.critical-audit-event', [
                'auditLog' => $this->auditLog,
                'eventLabel' => $eventLabel,
                'severity' => $severity,
            ]);
    }

    protected function getEventLabel(string $event): string
    {
        $labels = [
            'role_changed' => 'Role Changed',
            '2fa_disabled' => '2FA Disabled',
            'password_changed' => 'Password Changed',
            'force_deleted' => 'Record Force Deleted',
            'backup_failed' => 'Backup Failed',
            'failed_login' => 'Failed Login Attempt',
            'permission_granted' => 'Permission Granted',
            'permission_revoked' => 'Permission Revoked',
        ];

        return $labels[$event] ?? ucfirst(str_replace('_', ' ', $event));
    }

    protected function getEventSeverity(string $event): string
    {
        $critical = ['role_changed', 'force_deleted', 'backup_failed'];
        $high = ['2fa_disabled', 'password_changed', 'failed_login'];

        if (in_array($event, $critical)) {
            return 'CRITICAL';
        }

        if (in_array($event, $high)) {
            return 'HIGH';
        }

        return 'MEDIUM';
    }
}
