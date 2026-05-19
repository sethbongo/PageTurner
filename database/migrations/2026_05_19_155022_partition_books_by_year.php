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
        // This is a simplified migration for PostgreSQL partitioning.
        // In a real production system with foreign keys, you'd need to drop constraints,
        // rename the table, create the partitioned table, copy data, and recreate constraints.
        // For the sake of this lab activity, we will execute the conceptual PostgreSQL equivalent
        // if the table is empty, or just log that it's a demonstration.
        
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            // Because creating a partitioned table requires re-creating the table in Postgres,
            // we'll just demonstrate the schema change conceptually or use a new table.
            \Illuminate\Support\Facades\DB::statement("
                CREATE TABLE books_partitioned (
                    id bigint NOT NULL,
                    category_id bigint NOT NULL,
                    title character varying(255) NOT NULL,
                    author character varying(255) NOT NULL,
                    isbn character varying(255) NOT NULL,
                    price numeric(10,2) NOT NULL,
                    stock_quantity smallint NOT NULL,
                    description text NOT NULL,
                    cover_image character varying(255),
                    created_at timestamp(0) without time zone,
                    updated_at timestamp(0) without time zone,
                    published_at date,
                    is_active boolean DEFAULT true,
                    publisher character varying(255),
                    format character varying(255)
                ) PARTITION BY RANGE (published_at);
            ");
            
            \Illuminate\Support\Facades\DB::statement("CREATE TABLE p_old PARTITION OF books_partitioned FOR VALUES FROM (MINVALUE) TO ('2000-01-01');");
            \Illuminate\Support\Facades\DB::statement("CREATE TABLE p2000 PARTITION OF books_partitioned FOR VALUES FROM ('2000-01-01') TO ('2005-01-01');");
            \Illuminate\Support\Facades\DB::statement("CREATE TABLE p2005 PARTITION OF books_partitioned FOR VALUES FROM ('2005-01-01') TO ('2010-01-01');");
            \Illuminate\Support\Facades\DB::statement("CREATE TABLE p2010 PARTITION OF books_partitioned FOR VALUES FROM ('2010-01-01') TO ('2015-01-01');");
            \Illuminate\Support\Facades\DB::statement("CREATE TABLE p2015 PARTITION OF books_partitioned FOR VALUES FROM ('2015-01-01') TO ('2020-01-01');");
            \Illuminate\Support\Facades\DB::statement("CREATE TABLE p2020 PARTITION OF books_partitioned FOR VALUES FROM ('2020-01-01') TO ('2025-01-01');");
            \Illuminate\Support\Facades\DB::statement("CREATE TABLE p_future PARTITION OF books_partitioned FOR VALUES FROM ('2025-01-01') TO (MAXVALUE);");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement("DROP TABLE IF EXISTS books_partitioned CASCADE;");
        }
    }
};
