<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshMaterializedViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-materialized-views';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh PostgreSQL materialized views';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Refreshing materialized view mv_bestseller_stats...');
        // CONCURRENTLY requires a unique index on the materialized view
        \Illuminate\Support\Facades\DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY mv_bestseller_stats');
        $this->info('Materialized view refreshed successfully.');
    }
}
