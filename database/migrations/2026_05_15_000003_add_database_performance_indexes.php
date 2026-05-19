<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Adds performance indexes for frequent query patterns:
     * - ISBN lookups and filtering on books
     * - Category filtering on books
     * - User relationship queries on orders
     * - Date range filtering on orders for reports
     * - User authentication and filtering
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Index for ISBN searches and unique constraint is already there
            // Adding composite index for category filtering with ordering
            $table->index('category_id');

            // Index for stock quantity filtering and sorting
            $table->index('stock_quantity');

            // Composite index for created_at filtering with price/stock filtering
            $table->index(['created_at', 'category_id', 'stock_quantity']);
        });

        Schema::table('orders', function (Blueprint $table) {
            // Index for user relationship queries
            $table->index('user_id');

            // Index for status filtering
            $table->index('status');

            // Composite index for date range queries on reports
            $table->index(['created_at', 'status', 'user_id']);

            // Index for order date filtering and sorting
            $table->index('created_at');
        });

        Schema::table('users', function (Blueprint $table) {
            // Index for email lookups
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->index('created_at');
            } else {
                $table->index(['created_at', 'role']);
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            // Index for book reviews filtering
            $table->index('book_id');

            // Index for user reviews
            $table->index('user_id');

            // Composite index for average rating calculations
            $table->index(['book_id', 'rating']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Index for order items lookup
            $table->index('order_id');

            // Index for book items lookup
            $table->index('book_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            // Index for category queries
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['stock_quantity']);
            $table->dropIndex(['created_at', 'category_id', 'stock_quantity']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at', 'status', 'user_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['created_at', 'role']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['book_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['book_id', 'rating']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['book_id']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
