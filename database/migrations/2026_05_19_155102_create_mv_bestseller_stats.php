<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement("
            CREATE MATERIALIZED VIEW mv_bestseller_stats AS
            SELECT
                category_id,
                COUNT(*) as total_books,
                AVG(price) as avg_price,
                SUM(stock_quantity) as total_inventory,
                COUNT(CASE WHEN stock_quantity > 500 THEN 1 END) as bestseller_count,
                MAX(published_at) as latest_publication
            FROM books
            WHERE is_active = true
            GROUP BY category_id;
        ");
        
        // Add a unique index so we can use CONCURRENTLY when refreshing
        \Illuminate\Support\Facades\DB::statement("
            CREATE UNIQUE INDEX idx_mv_bestseller_category_id ON mv_bestseller_stats(category_id);
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("DROP MATERIALIZED VIEW IF EXISTS mv_bestseller_stats;");
    }
};
