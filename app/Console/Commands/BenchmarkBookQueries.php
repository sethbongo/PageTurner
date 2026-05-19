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
        ];

        // Ensure we have some data
        $book = \App\Models\Book::first();
        if (!$book) {
            $this->error('No books found for benchmarking.');
            return 1;
        }

        // 1. Catalog Listing
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            app(\App\Repositories\BookRepository::class)->getActiveCatalog(100);
        }
        $end = microtime(true);
        $avgCatalog = (($end - $start) * 1000) / $iterations;
        $this->info(sprintf("Catalog Listing Avg: %.2f ms (Target: < %d ms)", $avgCatalog, $targets['Catalog Listing']));

        // 2. Search by ISBN
        $isbn = $book->isbn;
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            \App\Models\Book::where('isbn', $isbn)->first();
        }
        $end = microtime(true);
        $avgIsbn = (($end - $start) * 1000) / $iterations;
        $this->info(sprintf("Search by ISBN Avg: %.2f ms (Target: < %d ms)", $avgIsbn, $targets['Search by ISBN']));

        if ($avgCatalog > $targets['Catalog Listing'] || $avgIsbn > $targets['Search by ISBN']) {
            $this->error('One or more benchmarks failed to meet targets.');
            return 1;
        }

        $this->info('All benchmarks passed!');
        return 0;
    }
}
