<?php

namespace App\Http\Middleware;

use App\Services\RateLimiterService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiRateLimiter
{
    protected $rateLimiter;

    public function __construct(RateLimiterService $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public function handle(Request $request, Closure $next, ?string $action = 'api'): Response
    {
        // Check if rate limiting is enabled
        if (!config('api.enabled')) {
            return $next($request);
        }

        // Check if this is an auth action
        if ($this->rateLimiter->isAuthAction($action)) {
            $this->rateLimiter->applyAuthLimiting($action);
        }

        // Get the appropriate tier
        $tier = $this->rateLimiter->getTier();

        // For auth actions, always use 'auth' tier
        if ($this->rateLimiter->isAuthAction($action)) {
            $tier = 'auth';
        }

        // Check if request is rate limited
        $limitStatus = $this->rateLimiter->getLimitStatus($tier, $action);
        if ($limitStatus['limited']) {
            return $this->createTooManyRequestsResponse($tier, $action, $limitStatus);
        }

        // Record the hit
        $this->rateLimiter->hit($tier, $action);

        // Get response and add headers
        $response = $next($request);

        return $this->addRateLimitHeaders($response, $tier, $action);
    }

    /**
     * Create 429 Too Many Requests response
     */
    protected function createTooManyRequestsResponse(string $tier, string $action, array $limitStatus): Response
    {
        $resetTime = $limitStatus['reset_in'] ?? $this->rateLimiter->getResetTime($action);
        $limit = $this->rateLimiter->getLimit($tier);
        $secondLimit = $this->rateLimiter->getLimitConfig($tier)['per_second'] ?? 0;
        $secondReset = $this->rateLimiter->getResetTimeForSecond($action);

        return response()->json([
            'message' => 'Too many requests',
            'error' => 'RATE_LIMIT_EXCEEDED',
            'tier' => $tier,
            'limit' => $limit,
            'window' => '1 minute',
            'reset_in' => $resetTime . ' seconds',
            'burst_limit' => $secondLimit,
            'burst_reset_in' => $secondReset . ' seconds',
            'documentation' => 'https://api.example.com/docs/rate-limiting',
        ], 429)
            ->header('X-RateLimit-Limit', $limit)
            ->header('X-RateLimit-Remaining', 0)
            ->header('X-RateLimit-Limit-Second', $secondLimit)
            ->header('X-RateLimit-Remaining-Second', 0)
            ->header('X-RateLimit-Reset-Second', time() + $secondReset)
            ->header('Retry-After', $resetTime);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders(Response $response, string $tier, string $action): Response
    {
        $limit = $this->rateLimiter->getLimit($tier);
        $remaining = $this->rateLimiter->getRemaining($tier, $action);
        $resetTime = $this->rateLimiter->getResetTime($action);
        $secondLimit = $this->rateLimiter->getLimitConfig($tier)['per_second'] ?? 0;
        $secondRemaining = $this->rateLimiter->getRemainingPerSecond($tier, $action);
        $secondReset = $this->rateLimiter->getResetTimeForSecond($action);

        return $response
            ->header('X-RateLimit-Limit', $limit)
            ->header('X-RateLimit-Remaining', max(0, $remaining - 1))
            ->header('X-RateLimit-Reset', time() + $resetTime)
            ->header('X-RateLimit-Tier', $tier)
            ->header('X-RateLimit-Limit-Second', $secondLimit)
            ->header('X-RateLimit-Remaining-Second', max(0, $secondRemaining - 1))
            ->header('X-RateLimit-Reset-Second', time() + $secondReset);
    }
}
