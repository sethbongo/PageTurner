<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ETagCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only process GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Disable if not enabled
        if (!config('api.etag_caching')) {
            return $next($request);
        }

        $response = $next($request);

        // Only process successful responses
        if ($response->status() < 200 || $response->status() >= 300) {
            return $response;
        }

        // Generate ETag from response content
        $etag = $this->generateETag($response->getContent());

        // Set ETag header
        $response->header('ETag', '"' . $etag . '"');

        // Check If-None-Match header
        $ifNoneMatch = $request->header('If-None-Match');

        if ($ifNoneMatch && $ifNoneMatch === '"' . $etag . '"') {
            return response()->noContent(304)
                ->header('ETag', '"' . $etag . '"')
                ->header('Cache-Control', 'public, max-age=' . $this->getCacheDuration());
        }

        // Add cache control headers
        $response->header('Cache-Control', 'public, max-age=' . $this->getCacheDuration());

        return $response;
    }

    /**
     * Generate ETag from content
     */
    protected function generateETag(string $content): string
    {
        return hash('md5', $content);
    }

    /**
     * Get cache duration in seconds
     */
    protected function getCacheDuration(): int
    {
        return config('api.etag_cache_duration', 60) * 60;
    }
}
