<?php

namespace App\Services;

use Illuminate\Pagination\Paginator;
use Illuminate\Http\Request;

class CursorPaginationService
{
    /**
     * Paginate a query using cursor-based pagination
     */
    public function paginate($query, Request $request = null, int $perPage = null)
    {
        if ($request === null) {
            $request = request();
        }

        if ($perPage === null) {
            $perPage = (int) $request->query('per_page', config('api.pagination_size'));
        }

        // Validate pagination size
        $maxSize = config('api.max_pagination_size');
        if ($perPage > $maxSize) {
            $perPage = $maxSize;
        }

        $perPage = max(1, $perPage);

        // Get the cursor from request
        $cursor = $request->query('cursor');
        $direction = $request->query('direction', 'after'); // 'after' or 'before'

        // Get the ordering column (default: id)
        $orderBy = $request->query('order_by', 'id');
        $sort = $request->query('sort', 'asc');

        // Validate sort direction
        if (!in_array($sort, ['asc', 'desc'])) {
            $sort = 'asc';
        }

        // Clone query for counting
        $countQuery = clone $query;

        if ($cursor) {
            $query = $this->applyCursor($query, $cursor, $orderBy, $direction, $sort);
        }

        // Fetch one extra to check if there are more results
        $items = $query->orderBy($orderBy, $sort)
            ->limit($perPage + 1)
            ->get();

        // Check if there are more results
        $hasMore = $items->count() > $perPage;

        if ($hasMore) {
            $items = $items->slice(0, $perPage);
        }

        // Get cursor for next and previous pages
        $nextCursor = $hasMore ? $this->encodeCursor($items->last()->{$orderBy}) : null;
        $prevCursor = $cursor ? $this->encodeCursor($items->first()->{$orderBy}) : null;

        return [
            'data' => $items,
            'pagination' => [
                'per_page' => $perPage,
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
                'prev_cursor' => $prevCursor,
                'order_by' => $orderBy,
                'sort' => $sort,
            ],
        ];
    }

    /**
     * Apply cursor to query
     */
    protected function applyCursor($query, string $cursor, string $orderBy, string $direction, string $sort)
    {
        $decodedCursor = $this->decodeCursor($cursor);

        if ($direction === 'after') {
            if ($sort === 'asc') {
                $query = $query->where($orderBy, '>', $decodedCursor);
            } else {
                $query = $query->where($orderBy, '<', $decodedCursor);
            }
        } else { // before
            if ($sort === 'asc') {
                $query = $query->where($orderBy, '<', $decodedCursor);
            } else {
                $query = $query->where($orderBy, '>', $decodedCursor);
            }
        }

        return $query;
    }

    /**
     * Encode cursor value (base64)
     */
    public function encodeCursor($value): string
    {
        return base64_encode((string) $value);
    }

    /**
     * Decode cursor value
     */
    public function decodeCursor(string $cursor)
    {
        $decoded = base64_decode($cursor, true);

        if ($decoded === false) {
            return null;
        }

        // Try to determine type
        if (is_numeric($decoded)) {
            return (int) $decoded;
        }

        return $decoded;
    }

    /**
     * Get pagination meta information
     */
    public function getPaginationMeta(array $paginationData, int $total = null): array
    {
        return [
            'current_page' => 1,
            'per_page' => $paginationData['pagination']['per_page'],
            'has_more' => $paginationData['pagination']['has_more'],
            'next_cursor' => $paginationData['pagination']['next_cursor'],
            'prev_cursor' => $paginationData['pagination']['prev_cursor'],
            'order_by' => $paginationData['pagination']['order_by'],
            'sort' => $paginationData['pagination']['sort'],
            'total' => $total,
            'path' => request()->path(),
            'links' => [
                'self' => $this->buildLink(),
                'next' => $paginationData['pagination']['next_cursor']
                    ? $this->buildLink(['cursor' => $paginationData['pagination']['next_cursor']])
                    : null,
                'prev' => $paginationData['pagination']['prev_cursor']
                    ? $this->buildLink(['cursor' => $paginationData['pagination']['prev_cursor']])
                    : null,
            ],
        ];
    }

    /**
     * Build pagination link
     */
    protected function buildLink(array $queryParams = []): string
    {
        $params = array_merge(request()->query(), $queryParams);

        return request()->fullUrlWithQuery($params);
    }
}
