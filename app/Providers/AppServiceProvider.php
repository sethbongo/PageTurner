<?php

namespace App\Providers;

use App\Listeners\AuthenticationEventListener;
use App\Listeners\BackupEventListener;
use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\Review;
use App\Policies\BookPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ReviewPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Events\CleanupHasFailed;
use Spatie\Backup\Events\CleanupWasSuccessful;
use Spatie\Backup\Events\HealthyBackupWasFound;
use Spatie\Backup\Events\UnhealthyBackupWasFound;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Book::class => BookPolicy::class,
        Category::class => CategoryPolicy::class,
        Order::class => OrderPolicy::class,
        Review::class => ReviewPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register event listeners
        Event::subscribe(AuthenticationEventListener::class);
        Event::listen(BackupWasSuccessful::class, [BackupEventListener::class, 'handleBackupWasSuccessful']);
        Event::listen(BackupHasFailed::class, [BackupEventListener::class, 'handleBackupHasFailed']);
        Event::listen(HealthyBackupWasFound::class, [BackupEventListener::class, 'handleHealthyBackupWasFound']);
        Event::listen(UnhealthyBackupWasFound::class, [BackupEventListener::class, 'handleUnhealthyBackupWasFound']);
        Event::listen(CleanupWasSuccessful::class, [BackupEventListener::class, 'handleCleanupWasSuccessful']);
        Event::listen(CleanupHasFailed::class, [BackupEventListener::class, 'handleCleanupHasFailed']);

        // Custom authorization gates
        Gate::define('isAdmin', function ($user) {
            return $user && $user->role === 'admin';
        });

        Gate::define('isCustomer', function ($user) {
            return $user && $user->role === 'customer';
        });
    }
}

