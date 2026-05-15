<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\CriticalAuditEventNotification;
use App\Services\AuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

class AuthenticationEventListener
{
    protected $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle user login
     */
    public function handleLogin(Login $event)
    {
        $this->auditService->logLogin($event->user->id);
    }

    /**
     * Handle user logout
     */
    public function handleLogout(Logout $event)
    {
        $this->auditService->logLogout($event->user->id);
    }

    /**
     * Handle failed login attempt
     */
    public function handleFailed(Failed $event)
    {
        $this->auditService->logFailedLogin($event->credentials['email'] ?? 'unknown');
    }

    /**
     * Handle password reset
     */
    public function handlePasswordReset(PasswordReset $event)
    {
        $this->auditService->logPasswordChange($event->user->id);

        // Send notification for password change
        $event->user->notify(new CriticalAuditEventNotification(
            \App\Models\AuditLog::where('event', 'password_changed')
                ->where('user_id', $event->user->id)
                ->latest('created_at')
                ->first()
        ));
    }

    public function subscribe($events)
    {
        $events->listen(Login::class, [$this, 'handleLogin']);
        $events->listen(Logout::class, [$this, 'handleLogout']);
        $events->listen(Failed::class, [$this, 'handleFailed']);
        $events->listen(PasswordReset::class, [$this, 'handlePasswordReset']);
    }
}
