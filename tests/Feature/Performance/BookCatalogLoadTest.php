<?php

namespace Tests\Feature\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BookCatalogLoadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Force the test to use the real PostgreSQL database instead of the in-memory SQLite
        // database defined in phpunit.xml, because we need to test against the 1M seeded records.
        $this->app['config']->set('database.default', 'pgsql');
    }

    /**
     * Test concurrent catalog requests.
     */
    public function test_catalog_load_performance(): void
    {
        // For a real load test, you'd use a tool like wrk or JMeter.
        // This is a unit-test level proxy to ensure queries don't fail under simulated loop.
        
        $start = microtime(true);
        
        for ($i = 0; $i < 50; $i++) {
            $response = app(\App\Repositories\BookRepository::class)->getActiveCatalog(100);
            $this->assertNotNull($response);
        }
        
        $end = microtime(true);
        $timeTaken = ($end - $start) * 1000; // ms
        
        // Assert 50 requests take less than 5000ms (100ms per request avg)
        $this->assertLessThan(5000, $timeTaken, "50 concurrent requests took too long: {$timeTaken}ms");
    }
}
