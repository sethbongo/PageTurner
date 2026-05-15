<?php

namespace App\Http\Resources;

use App\Services\CursorPaginationService;
use App\Services\FieldFilteringService;
use Illuminate\Http\Request;

class ApiResponse
{
    protected $fieldFilter;
    protected $cursorPagination;

    public function __construct()
    {
        $this->fieldFilter = app(FieldFilteringService::class);
        $this->cursorPagination = app(CursorPaginationService::class);
    }

    /**
     * Success response with data
     */
    public function success($data, string $message = 'Success', int $statusCode = 200)
    {
        // Apply field filtering
        if (request()->has('fields')) {
            $data = $this->fieldFilter->filterFields($data);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Paginated response with cursor-based pagination
     */
    public function paginated($query, ?int $perPage = null, string $message = 'Success')
    {
        $paginationData = $this->cursorPagination->paginate($query, request(), $perPage);

        // Apply field filtering
        if (request()->has('fields')) {
            $paginationData['data'] = $this->fieldFilter->filterCollection(
                collect($paginationData['data']),
                request()
            );
        }

        $total = null;
        if (request()->has('include_total')) {
            $total = $query->count();
        }

        $meta = $this->cursorPagination->getPaginationMeta($paginationData, $total);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginationData['data'],
            'meta' => $meta,
        ], 200);
    }

    /**
     * Paginated response with offset pagination
     */
    public function offsetPaginated($query, ?int $perPage = null, string $message = 'Success')
    {
        if ($perPage === null) {
            $perPage = (int) request()->query('per_page', config('api.pagination_size'));
        }

        // Validate pagination size
        $maxSize = config('api.max_pagination_size');
        if ($perPage > $maxSize) {
            $perPage = $maxSize;
        }

        $paginated = $query->paginate($perPage);

        // Apply field filtering
        if (request()->has('fields')) {
            $paginated->getCollection()->transform(function ($item) {
                return $this->fieldFilter->filterFields($item);
            });
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
                'has_more' => $paginated->hasMorePages(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'next' => $paginated->nextPageUrl(),
                'prev' => $paginated->previousPageUrl(),
            ],
        ], 200);
    }

    /**
     * Error response
     */
    public function error(string $message, string $error = 'ERROR', int $statusCode = 400, array $details = [])
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error' => $error,
            'status_code' => $statusCode,
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     */
    public function validationError(array $errors, string $message = 'Validation failed')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'VALIDATION_ERROR',
            'status_code' => 422,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Created response (201)
     */
    public function created($data, string $message = 'Created successfully')
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Updated response
     */
    public function updated($data, string $message = 'Updated successfully')
    {
        return $this->success($data, $message, 200);
    }

    /**
     * Deleted response
     */
    public function deleted(string $message = 'Deleted successfully')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], 200);
    }

    /**
     * Not found response (404)
     */
    public function notFound(string $message = 'Resource not found')
    {
        return $this->error($message, 'NOT_FOUND', 404);
    }

    /**
     * Unauthorized response (401)
     */
    public function unauthorized(string $message = 'Unauthorized')
    {
        return $this->error($message, 'UNAUTHORIZED', 401);
    }

    /**
     * Forbidden response (403)
     */
    public function forbidden(string $message = 'Forbidden')
    {
        return $this->error($message, 'FORBIDDEN', 403);
    }
}
