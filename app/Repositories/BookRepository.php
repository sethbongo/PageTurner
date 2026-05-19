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
        return Book::select([
            'books.id', 'books.isbn', 'books.title', 'books.author',
            'books.price', 'books.stock_quantity', 'books.published_at',
            'books.category_id', 'books.description'
        ])
        ->with(['category:id,name']) // In PostgreSQL, we can use category:id,name if slug doesn't exist
        ->where('is_active', true)
        ->orderBy('published_at', 'desc')
        ->orderBy('id', 'desc') // Secondary sort for stable pagination
        ->cursorPaginate($perPage);
    }
}
