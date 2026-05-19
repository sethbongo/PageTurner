<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MassBookSeeder extends Seeder
{
    private const CHUNK_SIZE = 5000; // Optimal for PostgreSQL
    private const TOTAL_RECORDS = 1000000;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inserted = 0;

        $this->command->info('Starting mass book seeding...');
        $this->command->getOutput()->progressStart(self::TOTAL_RECORDS);

        while ($inserted < self::TOTAL_RECORDS) {
            $batchSize = min(self::CHUNK_SIZE, self::TOTAL_RECORDS - $inserted);
            
            // Generate raw arrays directly to completely avoid Eloquent model hydration memory leaks
            $books = \App\Models\Book::factory()->count($batchSize)->raw();
            
            // Cast the boolean to a string representation for PostgreSQL PDO
            $books = array_map(function ($book) {
                $book['is_active'] = $book['is_active'] ? 'true' : 'false';
                return $book;
            }, $books);
            
            // Raw batch insert for maximum throughput
            \Illuminate\Support\Facades\DB::table('books')->insert($books);
            
            $inserted += $batchSize;
            $this->command->getOutput()->progressAdvance($batchSize);

            // Force garbage collection every 10 chunks
            if ($inserted % (self::CHUNK_SIZE * 10) === 0) {
                unset($books);
                gc_collect_cycles();
            }
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info('Successfully seeded ' . self::TOTAL_RECORDS . ' books.');
    }
}
