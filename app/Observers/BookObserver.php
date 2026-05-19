<?php

namespace App\Observers;

use App\Models\Book;

class BookObserver
{
    /**
     * Handle the Book "created" event.
     */
    public function created(Book $book): void
    {
        //
    }

    /**
     * Handle the Book "saved" event.
     */
    public function saved(Book $book): void
    {
        $this->invalidateCache($book);
    }

    /**
     * Handle the Book "deleted" event.
     */
    public function deleted(Book $book): void
    {
        $this->invalidateCache($book);
    }

    /**
     * Invalidate the related caches for the given book.
     */
    protected function invalidateCache(Book $book): void
    {
        \Illuminate\Support\Facades\Cache::forget("book:isbn:{$book->isbn}");
        \Illuminate\Support\Facades\Cache::tags(['books'])->flush();
    }
}
