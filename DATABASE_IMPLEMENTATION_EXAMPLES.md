# Database Architecture Implementation Guide

This guide shows how to use the new database architecture features in your application.

## 1. Using the QueryCacheService

### Example 1: Category Listing in Controller

**Before** (without caching):

```php
// app/Http/Controllers/CategoryController.php
public function index()
{
    $categories = Category::orderBy('name')->get();
    return view('categories.index', compact('categories'));
}
```

**After** (with caching):

```php
use App\Services\QueryCacheService;

public function index(QueryCacheService $cacheService)
{
    $categories = $cacheService->getCategories();
    return view('categories.index', compact('categories'));
}
```

**Performance Impact**:

- First request: ~50ms (database query)
- Subsequent requests (within 1 hour): ~5ms (cache hit)
- **Improvement**: 10x faster for cached requests

### Example 2: Homepage with Bestsellers and Featured Books

```php
// app/Http/Controllers/HomeController.php
use App\Services\QueryCacheService;

public function index(QueryCacheService $cacheService)
{
    return view('home', [
        'categories' => $cacheService->getCategories(),
        'bestsellers' => $cacheService->getBestsellingBooks(limit: 10),
        'featured' => $cacheService->getFeaturedBooks(limit: 8),
    ]);
}
```

### Example 3: Category with Book Count (Dashboard)

```php
// app/Http/Controllers/Admin/DashboardController.php
public function show(QueryCacheService $cacheService)
{
    $categoriesWithCounts = $cacheService->getCategories(withCounts: true);

    return view('admin.dashboard', [
        'stats' => [
            'total_categories' => $categoriesWithCounts->count(),
            'books_by_category' => $categoriesWithCounts->map(fn($cat) => [
                'name' => $cat->name,
                'count' => $cat->books_count,
            ]),
        ],
    ]);
}
```

## 2. Cache Invalidation Patterns

### Pattern 1: Model Observers (Recommended)

```php
// app/Models/Observers/CategoryObserver.php
namespace App\Models\Observers;

use App\Models\Category;
use App\Services\QueryCacheService;

class CategoryObserver
{
    public function created(Category $category): void
    {
        $this->invalidateCache();
    }

    public function updated(Category $category): void
    {
        $this->invalidateCache();
    }

    public function deleted(Category $category): void
    {
        $this->invalidateCache();
    }

    private function invalidateCache(): void
    {
        app(QueryCacheService::class)->invalidateCategoryCache();
    }
}

// Register in app/Providers/AppServiceProvider.php
use App\Models\Category;
use App\Models\Observers\CategoryObserver;

public function boot(): void
{
    Category::observe(CategoryObserver::class);
}
```

### Pattern 2: Order Observer

```php
// app/Models/Observers/OrderObserver.php
namespace App\Models\Observers;

use App\Models\Order;
use App\Services\QueryCacheService;

class OrderObserver
{
    public function created(Order $order): void
    {
        // Bestsellers change when new orders are created
        app(QueryCacheService::class)->invalidateBestsellerCache();
    }

    public function updated(Order $order): void
    {
        if ($order->isDirty('total_amount') || $order->isDirty('status')) {
            app(QueryCacheService::class)->invalidateBestsellerCache();
        }
    }
}

// Register in app/Providers/AppServiceProvider.php
Order::observe(OrderObserver::class);
```

### Pattern 3: Batch Operations

```php
// app/Services/BookImportService.php
public function importBooks(array $booksData): void
{
    $cacheService = app(QueryCacheService::class);

    DB::transaction(function () use ($booksData, $cacheService) {
        foreach ($booksData as $bookData) {
            Book::create($bookData);
        }

        // Invalidate all caches after bulk import
        $cacheService->invalidateAll();
    });
}
```

## 3. Using Read/Write Splitting

### Automatic Routing (Default Behavior)

```php
// Automatically uses WRITE connection (master)
$book = Book::create($data);
Book::where('id', 1)->update(['price' => 29.99]);
Book::destroy($bookId);

// Automatically uses READ connection (replica if available)
$book = Book::find(1);
$books = Book::all();
$count = Book::count();
```

### Explicit Connection Usage

```php
// Force read from master (for immediate consistency)
$latestBook = Book::connection('mysql')->latest()->first();

// Use specific replica
$books = Book::connection('mysql-replica-1')->get();
```

### Example: Analytics with Read Replicas

```php
// app/Services/AnalyticsService.php
public function getDailyRevenue(Carbon $date)
{
    // Read from replica for analytics (non-critical reads)
    return Order::on('mysql')  // Could be configured to use replica
        ->whereDate('created_at', $date)
        ->sum('total_amount');
}

public function getLatestOrders(int $limit = 10)
{
    // Critical reads should use master for consistency
    return Order::connection('mysql')
        ->latest()
        ->limit($limit)
        ->get();
}
```

## 4. Optimized Export Implementation

### Example 1: Book Export with Filtering

```php
// Already optimized - eager loads category
use App\Exports\BooksExport;

public function export(Request $request)
{
    $filters = [
        'category_id' => $request->category_id,
        'price_min' => $request->price_min,
        'price_max' => $request->price_max,
        'stock_status' => $request->stock_status,
        'date_from' => $request->date_from,
        'date_to' => $request->date_to,
    ];

    return Excel::download(
        new BooksExport(filters: $filters),
        'books.xlsx'
    );
}
```

### Example 2: Revenue Report Export

```php
// Already optimized - uses aggregation queries
use App\Exports\RevenueSummaryExport;

public function exportRevenue(Request $request)
{
    $filters = [
        'date_from' => $request->date_from,
        'date_to' => $request->date_to,
    ];

    return Excel::download(
        new RevenueSummaryExport(filters: $filters),
        'revenue.xlsx'
    );
}
```

### Example 3: Custom Export with Eager Loading

```php
// app/Exports/CustomBookExport.php
use App\Models\Book;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomBookExport implements FromQuery, WithHeadings
{
    public function query()
    {
        // ALWAYS eager load relationships in exports
        return Book::query()
            ->with(['category', 'reviews']) // Eager load all needed relationships
            ->select(['id', 'category_id', 'isbn', 'title', 'price'])
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return ['ID', 'Category', 'ISBN', 'Title', 'Price'];
    }

    public function map($book): array
    {
        return [
            $book->id,
            optional($book->category)->name,
            $book->isbn,
            $book->title,
            $book->price,
        ];
    }
}
```

## 5. Database Index Usage

### Verifying Indexes are Used

```php
// Enable query debugging
DB::enableQueryLog();

// Run query
$books = Book::where('category_id', 1)
    ->where('stock_quantity', '>', 0)
    ->get();

// Check if query uses index
$queries = DB::getQueryLog();
echo $queries[0]['query'];
// Output: SELECT * FROM books WHERE category_id = ? AND stock_quantity > ?
// Check EXPLAIN: EXPLAIN SELECT ...
```

### Query Analysis

```bash
# Use MySQL EXPLAIN to verify index usage
php artisan tinker

>>> DB::table('books')->where('category_id', 1)->explain()
// Shows query execution plan and whether indexes are used
```

## 6. Performance Monitoring

### Monitor Cache Hit Rate

```php
// app/Http/Middleware/CacheStatsMiddleware.php
public function handle($request, $next)
{
    $start = microtime(true);
    $response = $next($request);

    $duration = (microtime(true) - $start) * 1000;

    if (config('app.debug')) {
        \Log::info('Request performance', [
            'path' => $request->path(),
            'duration_ms' => $duration,
            'cache_service' => app('App\Services\QueryCacheService')->getCacheStats(),
        ]);
    }

    return $response;
}
```

### Monitor Query Performance

```php
// Enable in development
if (config('app.debug')) {
    DB::listen(function ($query) {
        if ($query->time > 100) { // Log queries over 100ms
            \Log::warning('Slow query detected', [
                'query' => $query->sql,
                'time' => $query->time,
                'bindings' => $query->bindings,
            ]);
        }
    });
}
```

## 7. Common Pitfalls to Avoid

### ❌ DON'T: Lazy loading in loops

```php
// BAD - N+1 queries
$books = Book::all();
foreach ($books as $book) {
    echo $book->category->name; // Lazy loads for each book
}
```

### ✅ DO: Eager load

```php
// GOOD - Single query
$books = Book::with('category')->get();
foreach ($books as $book) {
    echo $book->category->name; // Already loaded
}
```

### ❌ DON'T: Cache invalidation in production

```php
// BAD - Cache never invalidates
$cacheService->invalidateAll(); // Commented out in production
```

### ✅ DO: Use observers

```php
// GOOD - Automatic invalidation
Category::observe(CategoryObserver::class);
// Cache automatically invalidates when category changes
```

### ❌ DON'T: Read stale data after writes

```php
// BAD - May read from replica before write replicates
$book = Book::create(['title' => 'New Book']);
$found = Book::find($book->id); // Might read from unsynced replica
```

### ✅ DO: Use sticky reads or master

```php
// GOOD - Sticky reads ensure consistency
// config/database.php has DB_STICKY_READS=true

$book = Book::create(['title' => 'New Book']);
$found = Book::find($book->id); // Reads from master
```

## 8. Testing Database Architecture

### Test Cache Service

```php
// tests/Unit/Services/QueryCacheServiceTest.php
use App\Services\QueryCacheService;
use Illuminate\Support\Facades\Cache;

class QueryCacheServiceTest extends TestCase
{
    public function test_categories_are_cached()
    {
        Cache::flush();
        $service = new QueryCacheService();

        $categories1 = $service->getCategories();
        $categories2 = $service->getCategories();

        $this->assertEquals($categories1, $categories2);
        $this->assertTrue(Cache::has('bookstore_categories'));
    }

    public function test_cache_invalidation()
    {
        $service = new QueryCacheService();

        $service->getCategories();
        $this->assertTrue(Cache::has('bookstore_categories'));

        $service->invalidateCategoryCache();
        $this->assertFalse(Cache::has('bookstore_categories'));
    }
}
```

### Test Eager Loading

```php
// tests/Unit/Exports/BooksExportTest.php
class BooksExportTest extends TestCase
{
    public function test_books_export_uses_eager_loading()
    {
        DB::enableQueryLog();

        $export = new BooksExport();
        $query = $export->query();
        $books = $query->get();

        $queries = DB::getQueryLog();
        // Should have ~2 queries: books + categories
        // NOT 1001 queries (1 book + 1000 categories)
        $this->assertLessThan(5, count($queries));
    }
}
```

## 9. Deployment Checklist

- [ ] Update `.env` with read replica hosts
- [ ] Run migration: `php artisan migrate`
- [ ] Verify indexes: `php artisan tinker`
- [ ] Test read/write splitting: `php artisan tinker`
- [ ] Configure cache driver
- [ ] Register model observers
- [ ] Test cache invalidation
- [ ] Monitor query performance
- [ ] Load test with expected traffic
- [ ] Set up slow query alerts

## 10. Quick Reference

### QueryCacheService Methods

```php
$service = app(QueryCacheService::class);

$service->getCategories();                    // All categories
$service->getCategories(withCounts: true);   // With book counts
$service->getBestsellingBooks(limit: 10);    // Top 10 bestsellers
$service->getFeaturedBooks(limit: 8);        // Featured books
$service->getCategoryWithCount(categoryId);  // Specific category
$service->invalidateCategoryCache();          // Clear category cache
$service->invalidateBestsellerCache();        // Clear bestseller cache
$service->invalidateFeaturedBooksCache();     // Clear featured cache
$service->invalidateAll();                    // Clear all caches
$service->getCacheStats();                    // Cache statistics
```

### Eager Loading Syntax

```php
// Single relationship
Model::with('relationship')->get();

// Multiple relationships
Model::with(['relation1', 'relation2'])->get();

// Nested relationships
Model::with('relation1.relation2')->get();

// Conditional loading
Model::with(['relation' => fn($q) => $q->where('active', true)])->get();

// Select specific columns
Model::with('relation:id,name')->get();
```
