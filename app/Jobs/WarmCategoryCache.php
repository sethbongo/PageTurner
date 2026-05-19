<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WarmCategoryCache implements ShouldQueue
{
    use Queueable;

    public int $categoryId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $books = \App\Models\Book::select(['id', 'title', 'author', 'price', 'stock_quantity'])
            ->where('category_id', $this->categoryId)
            ->where('is_active', true)
            ->orderBy('published_at', 'desc')
            ->limit(1000)
            ->get();

        \Illuminate\Support\Facades\Cache::tags(["category:{$this->categoryId}"])
            ->put("category:{$this->categoryId}:popular", $books, 7200);
    }
}
