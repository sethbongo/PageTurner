<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class BackupFailureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $exception;

    public function __construct(\Throwable $exception)
    {
        $this->exception = $exception;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('⚠️ Backup Failure Alert - ' . config('app.name'))
            ->line('A backup failed to complete on ' . now()->format('Y-m-d H:i:s') . '.')
            ->line('**Error Details:**')
            ->line($this->exception->getMessage())
            ->action('View Backup Configuration', config('app.url') . '/admin/backups')
            ->line('**Application:** ' . config('app.name'))
            ->line('**Server:** ' . gethostname())
            ->line('Please investigate and ensure backups are running correctly.');
    }
}
