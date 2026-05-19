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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Observers\BookObserver;
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

        // Register Observers
        Book::observe(BookObserver::class);

        // Intelligent Rate Limiting with Redis
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            $tier = $user ? ($user->role === 'admin' ? 'admin' : 'standard') : 'public';
            
            $limits = [
                'public' => 30,
                'standard' => 60,
                'premium' => 300,
                'admin' => 1000,
            ];
            
            return Limit::perMinute($limits[$tier] ?? 30)
                ->by($user?->id ?: $request->ip());
        });
    }
}

