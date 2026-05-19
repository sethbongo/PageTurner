<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class IndexBooksBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:import-batch {--chunk=5000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chunked import of books for Scout to provide better observability';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $this->info("Starting Scout import in chunks of {$chunkSize}...");

        \App\Models\Book::chunk($chunkSize, function ($books) use (&$count) {
            $books->searchable();
            $count += $books->count();
            $this->info("Imported {$count} records into Scout.");
        });

        $this->info('Scout import completed successfully.');
    }
}
