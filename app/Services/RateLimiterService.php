<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class RateLimiterService
{
    /**
     * Get the rate limit tier for a request
     */
    public function getTier(): string
    {
        if (!Auth::check()) {
            return 'public';
        }

        $user = Auth::user();

        // Check if user is admin
        if ($user->role === 'admin') {
            return 'admin';
        }

        // Check if user is premium/VIP
        if ($this->isPremiumUser($user)) {
            return 'premium';
        }

        // Standard for authenticated customers
        return 'standard';
    }

    /**
     * Get rate limit configuration for tier
     */
    public function getLimitConfig(string $tier): array
    {
        return config("api.rate_limits.{$tier}", config('api.rate_limits.public'));
    }

    /**
     * Get the identifier for rate limiting (user ID or IP)
     */
    public function getIdentifier(): string
    {
        if (Auth::check()) {
            return 'user:' . Auth::id();
        }

        return 'ip:' . request()->ip();
    }

    /**
     * Check if request exceeds rate limit
     */
    public function isLimited(string $tier, string $action = 'api'): bool
    {
        $config = $this->getLimitConfig($tier);
        $identifier = $this->getIdentifier();

        $cacheKey = $this->getCacheKey($identifier, $action);
        $secondKey = $this->getSecondCacheKey($identifier, $action);

        // Get current request count for this minute
        $minuteCount = Cache::get($cacheKey, 0);

        // Get current request count for this second
        $secondCount = Cache::get($secondKey, 0);

        // Check per-second limit (burst protection)
        if ($secondCount >= $config['per_second']) {
            return true;
        }

        // Check per-minute limit
        if ($minuteCount >= $config['requests']) {
            return true;
        }

        return false;
    }

    /**
     * Increment the rate limit counter
     */
    public function hit(string $tier, string $action = 'api'): void
    {
        $identifier = $this->getIdentifier();
        $cacheKey = $this->getCacheKey($identifier, $action);
        $secondKey = $this->getSecondCacheKey($identifier, $action);

        // Increment minute counter (1 minute TTL)
        Cache::increment($cacheKey, 1, 60);

        // Increment second counter (1 second TTL)
        Cache::increment($secondKey, 1, 1);
    }

    /**
     * Get remaining requests
     */
    public function getRemaining(string $tier, string $action = 'api'): int
    {
        $config = $this->getLimitConfig($tier);
        $identifier = $this->getIdentifier();
        $cacheKey = $this->getCacheKey($identifier, $action);

        $count = Cache::get($cacheKey, 0);

        return max(0, $config['requests'] - $count);
    }

    /**
     * Get total limit
     */
    public function getLimit(string $tier): int
    {
        return $this->getLimitConfig($tier)['requests'];
    }

    /**
     * Get reset time in seconds
     */
    public function getResetTime(string $action = 'api'): int
    {
        $identifier = $this->getIdentifier();
        $cacheKey = $this->getCacheKey($identifier, $action);

        $ttl = Cache::getStore()->connection()->ttl($cacheKey);

        return max(0, $ttl);
    }

    /**
     * Check if action requires strict auth limiting
     */
    public function isAuthAction(string $action): bool
    {
        return in_array($action, ['login', 'register', 'password_reset', 'password_confirm']);
    }

    /**
     * Apply strict auth limiting
     */
    public function applyAuthLimiting(string $action): void
    {
        // Auth actions use IP-based limiting regardless of auth status
        $identifier = 'ip:' . request()->ip();
        $cacheKey = $this->getCacheKey($identifier, $action);

        Cache::increment($cacheKey, 1, 60);
    }

    /**
     * Clear rate limit for user/IP
     */
    public function clearLimit(string $identifier, string $action = 'api'): void
    {
        $cacheKey = $this->getCacheKey($identifier, $action);
        $secondKey = $this->getSecondCacheKey($identifier, $action);

        Cache::forget($cacheKey);
        Cache::forget($secondKey);
    }

    /**
     * Get cache key for minute counter
     */
    protected function getCacheKey(string $identifier, string $action = 'api'): string
    {
        return config('api.cache_prefix') . "{$identifier}:{$action}:minute";
    }

    /**
     * Get cache key for second counter
     */
    protected function getSecondCacheKey(string $identifier, string $action = 'api'): string
    {
        return config('api.cache_prefix') . "{$identifier}:{$action}:second";
    }

    /**
     * Check if user is premium/VIP
     */
    protected function isPremiumUser($user): bool
    {
        // Check if user has premium attribute or subscription
        if (isset($user->is_premium) && $user->is_premium) {
            return true;
        }

        if (isset($user->subscription_tier) && $user->subscription_tier === 'premium') {
            return true;
        }

        return false;
    }
}
