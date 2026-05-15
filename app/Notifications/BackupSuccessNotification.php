<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackupSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $backupName;
    protected $size;
    protected $timestamp;

    public function __construct($backupName = null, $size = null)
    {
        $this->backupName = $backupName ?? config('backup.backup.name');
        $this->size = $size ?? 'Unknown';
        $this->timestamp = now();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ Weekly Backup Summary - ' . config('app.name'))
            ->line('Your weekly backup summary for ' . config('app.name') . ':')
            ->line('')
            ->line('**Backup Details:**')
            ->line('• Backup Name: ' . $this->backupName)
            ->line('• Backup Size: ' . $this->formatBytes($this->size))
            ->line('• Completed At: ' . $this->timestamp->format('Y-m-d H:i:s'))
            ->line('• Server: ' . gethostname())
            ->line('')
            ->line('**Backup Status:** ✓ Successful')
            ->line('Your data is safely backed up and protected.')
            ->action('View Backup Dashboard', config('app.url') . '/admin/backups')
            ->line('Thank you for using ' . config('app.name'));
    }

    private function formatBytes($bytes)
    {
        if (is_numeric($bytes)) {
            $units = ['B', 'KB', 'MB', 'GB'];
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= (1 << (10 * $pow));
            return round($bytes, 2) . ' ' . $units[$pow];
        }
        return $bytes;
    }
}
