<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use App\Models\Order;
use App\Models\Review;
use App\Models\Category;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'Seth',
            'middle_name' => 'Bulahao',
            'last_name' => 'Bongo',
            'suffix' => '',
            'email' => 'try@gmail.com',
            'role' => 'customer',
            'password' => Hash::make('notorious'),
        ]);

        $users = User::factory(10)->create();
        $categories = Category::factory(10)->create();
        $books = Book::factory(10)->create();
        $orders = Order::factory(10)->recycle($users)->create();
        OrderItem::factory(10)->recycle($orders)->recycle($books)->create();
        Review::factory(10)->recycle($users)->recycle($books)->create();


    }
}
