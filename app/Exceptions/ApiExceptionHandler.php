<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Throwable;

class ApiExceptionHandler
{
    /**
     * Render an exception as JSON for API responses
     */
    public static function render(Throwable $exception): JsonResponse
    {
        $code = self::getStatusCode($exception);
        $error = self::getErrorType($exception);

        $response = [
            'message' => $exception->getMessage(),
            'error' => $error,
            'status_code' => $code,
        ];

        // Add validation errors
        if ($exception instanceof ValidationException) {
            $response['errors'] = $exception->errors();
        }

        // Add debug info in development
        if (config('app.debug')) {
            $response['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        }

        return response()->json($response, $code);
    }

    /**
     * Get HTTP status code from exception
     */
    protected static function getStatusCode(Throwable $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof AuthenticationException) {
            return 401;
        }

        if ($exception instanceof ValidationException) {
            return 422;
        }

        return 500;
    }

    /**
     * Get error type identifier
     */
    protected static function getErrorType(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return 'VALIDATION_ERROR';
        }

        if ($exception instanceof AuthenticationException) {
            return 'AUTHENTICATION_ERROR';
        }

        return 'SERVER_ERROR';
    }
}
