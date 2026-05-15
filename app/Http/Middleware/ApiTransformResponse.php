<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiTransformResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only transform JSON responses
        if (!$this->shouldTransform($response)) {
            return $response;
        }

        // Only transform if it's a JSON response
        if (
            !$response->headers->has('Content-Type') ||
            strpos($response->headers->get('Content-Type'), 'application/json') === false
        ) {
            return $response;
        }

        // Get the content and decode
        $content = json_decode($response->getContent(), true);

        if ($content === null) {
            return $response;
        }

        // Transform keys
        $transformed = $this->transformKeys($content);

        // Update response content
        return $response->setContent(json_encode($transformed));
    }

    /**
     * Transform array keys from snake_case to camelCase
     */
    protected function transformKeys($data)
    {
        if (is_array($data)) {
            $result = [];

            foreach ($data as $key => $value) {
                $newKey = $this->snakeToCamel($key);
                $result[$newKey] = $this->transformKeys($value);
            }

            return $result;
        }

        if (is_object($data)) {
            $array = (array) $data;
            $result = [];

            foreach ($array as $key => $value) {
                $newKey = $this->snakeToCamel($key);
                $result[$newKey] = $this->transformKeys($value);
            }

            return (object) $result;
        }

        return $data;
    }

    /**
     * Convert snake_case to camelCase
     */
    protected function snakeToCamel(string $string): string
    {
        $parts = explode('_', $string);
        $camel = array_shift($parts);

        foreach ($parts as $part) {
            $camel .= ucfirst($part);
        }

        return $camel;
    }

    /**
     * Check if response should be transformed
     */
    protected function shouldTransform(Response $response): bool
    {
        // Don't transform if disabled
        if (!config('api.transform_keys')) {
            return false;
        }

        // Only transform successful responses
        if ($response->status() < 200 || $response->status() >= 300) {
            return false;
        }

        return true;
    }
}
