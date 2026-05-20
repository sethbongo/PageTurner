<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id'=> Category::inRandomOrder()->first()->id,
            'title' => fake()->randomElement(['Game of Thrones', 'Harry Potter', 'Birds and Fire', 'The Hobbit']),
            'author' => fake()->name(),
            'isbn' => fake()->creditCardNumber(),
            'price' => fake()->randomFloat(2, 10, 100),
            'stock_quantity' => fake()->numberBetween(1,10),
            'description' => fake()->sentence(),
            'cover_image'=> fake()->imageUrl(640, 480, 'products', true),
        ];
    }
}
