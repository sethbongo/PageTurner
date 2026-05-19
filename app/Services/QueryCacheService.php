<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * QueryCacheService - Handles caching of frequent read queries
 * 
 * This service caches common query results to reduce database load and improve
 * response times for frequently accessed data like categories and bestsellers.
 * 
 * Cache TTLs:
 * - Categories: 1 hour (updated when new categories added)
 * - Bestsellers: 30 minutes (daily sales fluctuate)
 * - Featured Books: 2 hours (less frequently updated)
 */
class QueryCacheService
{
    private const CACHE_PREFIX = 'bookstore_';

    private const CACHE_DURATIONS = [
        'categories' => 3600,           // 1 hour
        'bestsellers' => 1800,          // 30 minutes
        'featured_books' => 7200,       // 2 hours
        'categories_with_count' => 3600, // 1 hour
    ];

    /**
     * Get all categories with caching
     * 
     * @param bool $withCounts - Include book counts for each category
     * @return Collection
     */
    public function getCategories(bool $withCounts = false): Collection
    {
        $cacheKey = self::CACHE_PREFIX . ($withCounts ? 'categories_with_count' : 'categories');

        return Cache::remember(
            $cacheKey,
            self::CACHE_DURATIONS[$withCounts ? 'categories_with_count' : 'categories'],
            function () use ($withCounts) {
                $query = Category::query();

                if ($withCounts) {
                    $query->withCount('books');
                }

                return $query->orderBy('name')->get();
            }
        );
    }

    /**
     * Get bestselling books by order count
     * 
     * Caches the top selling books to avoid expensive grouping queries
     * 
     * @param int $limit - Number of books to return
     * @return Collection
     */
    public function getBestsellingBooks(int $limit = 10): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "bestsellers_{$limit}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_DURATIONS['bestsellers'],
            function () use ($limit) {
                return Book::query()
                    ->withCount('orderItems')
                    ->with('category')
                    ->orderByDesc('order_items_count')
                    ->limit($limit)
                    ->get();
            }
        );
    }

    /**
     * Get featured books (high rating, in stock)
     * 
     * @param int $limit - Number of books to return
     * @return Collection
     */
    public function getFeaturedBooks(int $limit = 8): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "featured_books_{$limit}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_DURATIONS['featured_books'],
            function () use ($limit) {
                return Book::query()
                    ->where('stock_quantity', '>', 0)
                    ->with(['category', 'reviews'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->filter(function ($book) {
                        return $book->averageRating >= 3.5; // Only high-rated books
                    })
                    ->values();
            }
        );
    }

    /**
     * Get category with book count by ID
     * 
     * @param int $categoryId
     * @return mixed
     */
    public function getCategoryWithCount(int $categoryId)
    {
        $cacheKey = self::CACHE_PREFIX . "category_{$categoryId}_count";

        return Cache::remember(
            $cacheKey,
            self::CACHE_DURATIONS['categories_with_count'],
            function () use ($categoryId) {
                return Category::query()
                    ->withCount('books')
                    ->where('id', $categoryId)
                    ->first();
            }
        );
    }

    /**
     * Invalidate category cache when categories change
     * 
     * @return void
     */
    public function invalidateCategoryCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'categories');
        Cache::forget(self::CACHE_PREFIX . 'categories_with_count');

        // Invalidate all category-specific caches
        foreach (Category::pluck('id') as $categoryId) {
            Cache::forget(self::CACHE_PREFIX . "category_{$categoryId}_count");
        }
    }

    /**
     * Invalidate bestseller cache when orders change
     * 
     * @return void
     */
    public function invalidateBestsellerCache(): void
    {
        // Invalidate all bestseller caches with different limits
        for ($limit = 5; $limit <= 20; $limit += 5) {
            Cache::forget(self::CACHE_PREFIX . "bestsellers_{$limit}");
        }
    }

    /**
     * Invalidate featured books cache when books change
     * 
     * @return void
     */
    public function invalidateFeaturedBooksCache(): void
    {
        // Invalidate all featured books caches with different limits
        for ($limit = 5; $limit <= 15; $limit += 1) {
            Cache::forget(self::CACHE_PREFIX . "featured_books_{$limit}");
        }
    }

    /**
     * Invalidate all caches
     * 
     * Useful for full cache clearing or during testing
     * 
     * @return void
     */
    public function invalidateAll(): void
    {
        $this->invalidateCategoryCache();
        $this->invalidateBestsellerCache();
        $this->invalidateFeaturedBooksCache();
    }

    /**
     * Get cache stats for monitoring
     * 
     * @return array
     */
    public function getCacheStats(): array
    {
        return [
            'prefix' => self::CACHE_PREFIX,
            'durations' => self::CACHE_DURATIONS,
            'cached_keys' => [
                'categories' => self::CACHE_PREFIX . 'categories',
                'bestsellers' => self::CACHE_PREFIX . 'bestsellers_*',
                'featured_books' => self::CACHE_PREFIX . 'featured_books_*',
            ],
        ];
    }
}
