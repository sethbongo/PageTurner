<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BenchmarkBookQueries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benchmark:books {--iterations=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark database queries against performance targets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $iterations = (int) $this->option('iterations');
        $this->info("Benchmarking with {$iterations} iterations...");

        $targets = [
            'Catalog Listing' => 100, // < 100ms
            'Search by ISBN' => 50,  // < 50ms
            'Category Filter' => 150, // < 150ms
            'Full-text Search' => 300, // < 300ms
            'Cache Validation' => 10,  // < 10ms
        ];

        // Ensure we have some data
        $book = \App\Models\Book::first();
        if (!$book) {
            $this->error('No books found for benchmarking.');
            return 1;
        }

        // 1. Catalog Listing (Uncached)
        \Illuminate\Support\Facades\Cache::tags(['catalog', 'books'])->flush();
        $start = microtime(true);
        app(\App\Repositories\BookRepository::class)->getActiveCatalog(100);
        $end = microtime(true);
        $avgCatalog = ($end - $start) * 1000;
        $this->info(sprintf("Catalog Listing (Uncached): %.2f ms (Target: < %d ms)", $avgCatalog, $targets['Catalog Listing']));

        // 2. Cache Validation (Cached)
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            app(\App\Repositories\BookRepository::class)->getActiveCatalog(100);
        }
        $end = microtime(true);
        $avgCached = (($end - $start) * 1000) / $iterations;
        $this->info(sprintf("Catalog Listing (Cached) Avg: %.2f ms (Target: < %d ms)", $avgCached, $targets['Cache Validation']));

        // 3. Search by ISBN
        $isbn = $book->isbn;
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            app(\App\Repositories\BookRepository::class)->findByIsbn($isbn);
        }
        $end = microtime(true);
        $avgIsbn = (($end - $start) * 1000) / $iterations;
        $this->info(sprintf("Search by ISBN (Cached) Avg: %.2f ms (Target: < %d ms)", $avgIsbn, $targets['Search by ISBN']));

        // 4. Category Filter
        $categoryId = $book->category_id;
        \Illuminate\Support\Facades\Cache::tags(["category:{$categoryId}", 'books'])->flush();
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            app(\App\Repositories\BookRepository::class)->getByCategory($categoryId, 100);
        }
        $end = microtime(true);
        $avgCategory = (($end - $start) * 1000) / $iterations;
        $this->info(sprintf("Category Filter Avg: %.2f ms (Target: < %d ms)", $avgCategory, $targets['Category Filter']));

        // 5. Full-text Search
        $searchTerm = explode(' ', $book->title)[0];
        $start = microtime(true);
        for ($i = 0; $i < 50; $i++) { // 50 iterations as per spec
            \App\Models\Book::search($searchTerm)->take(10)->get();
        }
        $end = microtime(true);
        $avgScout = (($end - $start) * 1000) / 50;
        $this->info(sprintf("Full-text Search Avg: %.2f ms (Target: < %d ms)", $avgScout, $targets['Full-text Search']));

        $failed = $avgCatalog > $targets['Catalog Listing'] || 
                  $avgCached > $targets['Cache Validation'] || 
                  $avgIsbn > $targets['Search by ISBN'] || 
                  $avgCategory > $targets['Category Filter'] || 
                  $avgScout > $targets['Full-text Search'];

        if ($failed) {
            $this->error('One or more benchmarks failed to meet targets.');
            return 1;
        }

        $this->info('All benchmarks passed!');
        return 0;
    }
}
