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
        Schema::table('books', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('books', 'published_at')) {
                $table->date('published_at')->nullable();
            }
            if (!Schema::hasColumn('books', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('books', 'publisher')) {
                $table->string('publisher')->nullable();
            }
            if (!Schema::hasColumn('books', 'format')) {
                $table->string('format')->nullable();
            }

            // Composite index for common filtering patterns
            $table->index(
                ['category_id', 'published_at', 'is_active'],
                'idx_books_catalog_filter'
            );

            // Covering index for price range queries
            $table->index(
                ['price', 'stock_quantity', 'id'],
                'idx_books_price_stock'
            );

            // Full-text index (PostgreSQL handles this nicely in Laravel)
            $table->fullText(['title', 'description'], 'idx_books_fulltext');

            // Index for active-book filtering
            $table->index('is_active', 'idx_books_active');

            // ISBN lookup index (ISBN is already unique, but an explicit index can be added if needed,
            // though unique already creates an index. We'll add it per instructions).
            $table->index('isbn', 'idx_books_isbn_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex('idx_books_catalog_filter');
            $table->dropIndex('idx_books_price_stock');
            $table->dropIndex('idx_books_fulltext');
            $table->dropIndex('idx_books_active');
            $table->dropIndex('idx_books_isbn_lookup');
            
            $table->dropColumn(['published_at', 'is_active', 'publisher', 'format']);
        });
    }
};
