<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class RateLimiterService
{
    private const RESET_SUFFIX = ':reset';

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
        $result = $this->getLimitStatus($tier, $action);

        return $result['limited'];
    }

    /**
     * Determine which limit was exceeded (second/minute) and reset window.
     */
    public function getLimitStatus(string $tier, string $action = 'api'): array
    {
        $config = $this->getLimitConfig($tier);
        $identifier = $this->getIdentifier();

        $cacheKey = $this->getCacheKey($identifier, $action);
        $secondKey = $this->getSecondCacheKey($identifier, $action);

        $store = $this->getStore();

        $minuteCount = (int) $store->get($cacheKey, 0);
        $secondCount = (int) $store->get($secondKey, 0);

        if ($secondCount >= $config['per_second']) {
            return [
                'limited' => true,
                'scope' => 'second',
                'reset_in' => $this->getResetTimeForSecond($action),
            ];
        }

        if ($minuteCount >= $config['requests']) {
            return [
                'limited' => true,
                'scope' => 'minute',
                'reset_in' => $this->getResetTime($action),
            ];
        }

        return [
            'limited' => false,
            'scope' => null,
            'reset_in' => 0,
        ];
    }

    /**
     * Increment the rate limit counter
     */
    public function hit(string $tier, string $action = 'api'): void
    {
        $identifier = $this->getIdentifier();
        $cacheKey = $this->getCacheKey($identifier, $action);
        $secondKey = $this->getSecondCacheKey($identifier, $action);

        $store = $this->getStore();

        // Ensure TTLs are set before incrementing
        $store->add($cacheKey, 0, 60);
        $store->add($this->getResetKey($cacheKey), now()->addSeconds(60)->timestamp, 60);
        $store->increment($cacheKey);

        $store->add($secondKey, 0, 1);
        $store->add($this->getResetKey($secondKey), now()->addSecond()->timestamp, 1);
        $store->increment($secondKey);
    }

    /**
     * Get remaining requests
     */
    public function getRemaining(string $tier, string $action = 'api'): int
    {
        $config = $this->getLimitConfig($tier);
        $identifier = $this->getIdentifier();
        $cacheKey = $this->getCacheKey($identifier, $action);

        $count = (int) $this->getStore()->get($cacheKey, 0);

        return max(0, $config['requests'] - $count);
    }

    public function getRemainingPerSecond(string $tier, string $action = 'api'): int
    {
        $config = $this->getLimitConfig($tier);
        $identifier = $this->getIdentifier();
        $cacheKey = $this->getSecondCacheKey($identifier, $action);

        $count = (int) $this->getStore()->get($cacheKey, 0);

        return max(0, ($config['per_second'] ?? 0) - $count);
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
        $reset = (int) $this->getStore()->get($this->getResetKey($cacheKey), 0);

        return $this->secondsUntil($reset);
    }

    public function getResetTimeForSecond(string $action = 'api'): int
    {
        $identifier = $this->getIdentifier();
        $secondKey = $this->getSecondCacheKey($identifier, $action);
        $reset = (int) $this->getStore()->get($this->getResetKey($secondKey), 0);

        return $this->secondsUntil($reset);
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
        $store = $this->getStore();
        $store->add($cacheKey, 0, 60);
        $store->add($this->getResetKey($cacheKey), now()->addSeconds(60)->timestamp, 60);
        $store->increment($cacheKey);
    }

    /**
     * Clear rate limit for user/IP
     */
    public function clearLimit(string $identifier, string $action = 'api'): void
    {
        $cacheKey = $this->getCacheKey($identifier, $action);
        $secondKey = $this->getSecondCacheKey($identifier, $action);
        $store = $this->getStore();

        $store->forget($cacheKey);
        $store->forget($secondKey);
        $store->forget($this->getResetKey($cacheKey));
        $store->forget($this->getResetKey($secondKey));
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

    protected function getResetKey(string $cacheKey): string
    {
        return $cacheKey . self::RESET_SUFFIX;
    }

    protected function secondsUntil(int $timestamp): int
    {
        if ($timestamp <= 0) {
            return 0;
        }

        return max(0, $timestamp - now()->timestamp);
    }

    protected function getStore()
    {
        return Cache::store(config('api.cache_store'));
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
