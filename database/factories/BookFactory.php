<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    private static array $categoryIds = [];
    private static array $publishers = [
        'Penguin Random House', 'Simon & Schuster', 'Hachette Livre',
        'HarperCollins', 'Macmillan Publishers', 'Scholastic',
        'Disney Publishing Worldwide', 'Houghton Mifflin Harcourt',
        'Pearson Education', 'Oxford University Press', 'Wiley',
        'Springer Nature', 'McGraw-Hill Education', 'Cengage Learning',
        'Routledge'
    ];
    private static array $formats = ['Hardcover', 'Paperback', 'E-book', 'Audiobook'];

    private static ?int $isbnCounter = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        if (empty(self::$categoryIds)) {
            self::$categoryIds = \App\Models\Category::pluck('id')->toArray();
        }

        $format = $this->faker->randomElement(self::$formats);
        
        $basePrice = match ($format) {
            'Hardcover' => $this->faker->randomFloat(2, 20, 50),
            'Paperback' => $this->faker->randomFloat(2, 10, 25),
            'E-book' => $this->faker->randomFloat(2, 5, 15),
            'Audiobook' => $this->faker->randomFloat(2, 15, 30),
            default => 15.99,
        };

        return [
            'isbn' => $this->generateValidIsbn13(),
            // Removed unique() from title to save memory when seeding 1,000,000 records
            'title' => $this->faker->sentence(rand(2, 6)),
            'author' => $this->faker->name(),
            'publisher' => $this->faker->randomElement(self::$publishers),
            'price' => $basePrice,
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'category_id' => $this->faker->randomElement(self::$categoryIds),
            'format' => $format,
            'is_active' => $this->faker->boolean(85),
            'description' => $this->faker->paragraph(2),
            'cover_image' => null, // Speed up seeding by not generating image URLs
            'published_at' => $this->faker->dateTimeBetween('-50 years', 'now')->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Generate a valid ISBN-13 sequentially to guarantee uniqueness and avoid collisions.
     */
    private function generateValidIsbn13(): string
    {
        // Dynamically offset the counter based on max ID in the database 
        // to prevent collisions if the seeder is restarted
        if (self::$isbnCounter === null) {
            $maxId = \Illuminate\Support\Facades\DB::table('books')->max('id') ?? 0;
            self::$isbnCounter = 100000000 + $maxId;
        }

        // Use a static counter instead of faker to guarantee uniqueness
        $sequence = (string) self::$isbnCounter++;
        $isbn = '978' . str_pad($sequence, 9, '0', STR_PAD_LEFT);
        
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ($i % 2 === 0) ? (int)$isbn[$i] : (int)$isbn[$i] * 3;
        }
        $checksum = (10 - ($sum % 10)) % 10;
        return $isbn . $checksum;
    }

    /**
     * Indicate that the book is a bestseller.
     */
    public function bestseller(): self
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => $this->faker->numberBetween(500, 1000),
            'is_active' => true,
        ]);
    }
}
