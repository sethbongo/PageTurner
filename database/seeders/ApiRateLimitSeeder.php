<?php

namespace Database\Seeders;

use App\Models\ApiRateLimit;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApiRateLimitSeeder extends Seeder
{
    public function run(): void
    {
        // Get sample users
        $users = User::where('role', 'customer')->limit(10)->get();

        $endpoints = [
            ['endpoint' => '/api/books', 'method' => 'GET'],
            ['endpoint' => '/api/books/{id}', 'method' => 'GET'],
            ['endpoint' => '/api/orders', 'method' => 'GET'],
            ['endpoint' => '/api/orders', 'method' => 'POST'],
            ['endpoint' => '/api/reviews', 'method' => 'POST'],
            ['endpoint' => '/api/cart', 'method' => 'GET'],
            ['endpoint' => '/api/cart', 'method' => 'POST'],
            ['endpoint' => '/api/profile', 'method' => 'GET'],
            ['endpoint' => '/api/profile', 'method' => 'PUT'],
            ['endpoint' => '/api/data/export', 'method' => 'POST'],
        ];

        foreach ($users as $user) {
            foreach ($endpoints as $endpoint) {
                ApiRateLimit::create([
                    'user_id' => $user->id,
                    'endpoint' => $endpoint['endpoint'],
                    'method' => $endpoint['method'],
                    'ip_address' => fake()->ipv4(),
                    'api_key_prefix' => substr(bin2hex(random_bytes(8)), 0, 10),
                    'requests_count' => rand(5, 150),
                    'limit' => 60,
                    'window_seconds' => 60,
                    'rate_limited' => rand(0, 1) === 1,
                    'reset_at' => rand(0, 1) === 1 ? now()->addMinutes(rand(1, 30)) : null,
                    'metadata' => [
                        'country' => fake()->country(),
                        'user_agent' => fake()->userAgent(),
                    ],
                ]);
            }
        }

        // Add some anonymous rate limit entries (IPs without users)
        for ($i = 0; $i < 20; $i++) {
            ApiRateLimit::create([
                'user_id' => null,
                'endpoint' => $endpoints[array_rand($endpoints)]['endpoint'],
                'method' => 'GET',
                'ip_address' => fake()->ipv4(),
                'api_key_prefix' => null,
                'requests_count' => rand(10, 500),
                'limit' => 60,
                'window_seconds' => 60,
                'rate_limited' => rand(0, 1) === 1,
                'reset_at' => rand(0, 1) === 1 ? now()->addMinutes(rand(1, 30)) : null,
                'metadata' => [
                    'country' => fake()->country(),
                    'user_agent' => fake()->userAgent(),
                ],
            ]);
        }
    }
}
