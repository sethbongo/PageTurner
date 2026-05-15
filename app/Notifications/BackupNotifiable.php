<?php

namespace App\Notifications;

use Spatie\Backup\Notifications\BaseNotifiable;

class BackupNotifiable extends BaseNotifiable
{
    public function shouldNotify(string $channelName): bool
    {
        return config('backup.backup.notifications.notifications');
    }

    public function routeNotificationForMail(): string
    {
        return config('backup.backup.notifications.mail.to');
    }

    public function routeNotificationForSlack(): ?string
    {
        return config('backup.backup.notifications.slack.webhook_url');
    }
}
