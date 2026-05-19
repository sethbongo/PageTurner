<?php

namespace App\Repositories;

use App\Models\Book;
use Illuminate\Pagination\CursorPaginator;

class BookRepository
{
    /**
     * Get optimized active catalog list using cursor pagination and covered index hits.
     *
     * @param int $perPage
     * @return CursorPaginator
     */
    public function getActiveCatalog(int $perPage = 100): CursorPaginator
    {
        $page = request()->query('cursor', '');
        
        return \Illuminate\Support\Facades\Cache::tags(['catalog', 'books'])
            ->remember("catalog:active:{$perPage}:{$page}", 600, function () use ($perPage) {
                return Book::select([
                    'books.id', 'books.isbn', 'books.title', 'books.author',
                    'books.price', 'books.stock_quantity', 'books.published_at',
                    'books.category_id', 'books.description'
                ])
                ->with(['category:id,name'])
                ->where('is_active', 'true')
                ->orderBy('published_at', 'desc')
                ->orderBy('id', 'desc')
                ->cursorPaginate($perPage);
            });
    }

    /**
     * Get books by category using covered index and cache.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return CursorPaginator
     */
    public function getByCategory(int $categoryId, int $perPage = 100): CursorPaginator
    {
        $page = request()->query('cursor', '');

        return \Illuminate\Support\Facades\Cache::tags(["category:{$categoryId}", 'books'])
            ->remember("catalog:category:{$categoryId}:{$perPage}:{$page}", 3600, function () use ($categoryId, $perPage) {
                return Book::select([
                    'books.id', 'books.isbn', 'books.title', 'books.author',
                    'books.price', 'books.stock_quantity', 'books.published_at',
                    'books.category_id', 'books.description'
                ])
                ->with(['category:id,name'])
                ->where('category_id', $categoryId)
                ->where('is_active', 'true')
                ->orderBy('published_at', 'desc')
                ->orderBy('id', 'desc')
                ->cursorPaginate($perPage);
            });
    }

    /**
     * Find book by ISBN using unique index and cache.
     *
     * @param string $isbn
     * @return Book|null
     */
    public function findByIsbn(string $isbn): ?Book
    {
        return \Illuminate\Support\Facades\Cache::tags(['books'])
            ->remember("book:isbn:{$isbn}", 3600, function () use ($isbn) {
                return Book::with(['category', 'reviews.user'])
                    ->where('isbn', $isbn)
                    ->first();
            });
    }
}
