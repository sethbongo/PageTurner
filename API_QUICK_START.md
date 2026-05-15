# API Rate Limiting - Quick Start (4.4)

## 5-Minute Setup

### 1. Publish Configuration

```bash
# Already exists in config/api.php
php artisan config:clear
```

### 2. Register Middleware in `app/Http/Kernel.php`

Add to `$middleware` array:

```php
protected $middleware = [
    // ... existing middleware
    \App\Http\Middleware\ApiRateLimiter::class,
    \App\Http\Middleware\ApiTransformResponse::class,
    \App\Http\Middleware\ETagCaching::class,
];
```

Or register in specific routes:

```php
Route::middleware([
    'ApiRateLimiter:api',
    'ApiTransformResponse',
    'ETagCaching'
])->group(function () {
    // API routes
});
```

### 3. Configure `.env`

```env
RATE_LIMITING_ENABLED=true
API_TRANSFORM_KEYS=true
API_FIELD_FILTERING=true
API_CURSOR_PAGINATION=true
API_ETAG_CACHING=true
API_PAGINATION_SIZE=20
```

### 4. Use API Response in Controllers

```php
use App\Http\Resources\ApiResponse;

class BookApiController extends Controller
{
    public function __construct(private ApiResponse $apiResponse) {}

    public function index()
    {
        return $this->apiResponse->paginated(Book::query());
    }
}
```

---

## Quick API Calls

### Get Books (Cursor Pagination)

```bash
curl "http://localhost:8000/api/v1/books?per_page=20"
```

### Filter Fields

```bash
curl "http://localhost:8000/api/v1/books?fields=id,title,price"
```

### Next Page

```bash
curl "http://localhost:8000/api/v1/books?cursor=eyIyMCI6IjIwIn0=&direction=after"
```

### Check Rate Limits

```bash
curl -i "http://localhost:8000/api/v1/books"
# Look for: X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Tier
```

---

## Rate Limit Tiers

| Tier     | Limit    | Users          |
| -------- | -------- | -------------- |
| Public   | 30/min   | Guests         |
| Standard | 60/min   | Customers      |
| Premium  | 300/min  | Premium users  |
| Admin    | 1000/min | Admins         |
| Auth     | 10/min   | Login/Register |

---

## Features at a Glance

✅ **Rate Limiting**

- 5 tiers (public, standard, premium, admin, auth)
- Per-second burst protection
- Response headers with remaining requests
- Custom 429 error responses

✅ **Key Transformation**

- snake_case → camelCase automatic conversion
- All responses transformed
- Configurable

✅ **Field Filtering**

- `?fields=id,title,price`
- Reduces bandwidth
- Automatic validation

✅ **Cursor Pagination**

- Efficient for large datasets
- `?cursor=xyz&per_page=20`
- Forward and backward navigation

✅ **ETag Caching**

- Browser-level caching
- 304 Not Modified responses
- Automatic ETag generation

---

## Common Tasks

### Add Rate Limiting to Custom Endpoint

```php
Route::middleware('ApiRateLimiter:api')->get('/custom', function () {
    // Limited to 30/min for guests, 60/min for customers
});
```

### Strict Auth Rate Limiting

```php
Route::middleware('ApiRateLimiter:login')->post('/login', function () {
    // Limited to 10/min (IP-based)
});
```

### Disable Rate Limiting for Webhook

```php
Route::post('/webhook', function () {
    // No rate limiting on webhooks
})->withoutMiddleware('ApiRateLimiter');
```

---

## API Response Format

### Success with Pagination

```json
{
  "success": true,
  "message": "Books retrieved successfully",
  "data": [...],
  "meta": {
    "per_page": 20,
    "has_more": true,
    "next_cursor": "eyI...="
  }
}
```

### Error

```json
{
    "success": false,
    "message": "Book not found",
    "error": "NOT_FOUND",
    "status_code": 404
}
```

### Rate Limited

```json
{
    "message": "Too many requests",
    "error": "RATE_LIMIT_EXCEEDED",
    "limit": 30,
    "reset_in": "42 seconds"
}
```

---

## Monitoring

### Check Remaining Requests

After each API call, check headers:

```
X-RateLimit-Limit: 30
X-RateLimit-Remaining: 25
X-RateLimit-Reset: 1715851200
X-RateLimit-Tier: public
```

### Test Rate Limiting

```bash
# Make many rapid requests
for i in {1..35}; do
  curl "http://localhost:8000/api/v1/books"
done
# Request 31+ will get 429 error
```

---

## Need More Help?

- **Full Guide:** See `API_RATE_LIMITING_GUIDE.md`
- **Config:** See `config/api.php`
- **Services:**
    - `RateLimiterService` - Rate limiting logic
    - `CursorPaginationService` - Pagination logic
    - `FieldFilteringService` - Field filtering logic
- **Middleware:**
    - `ApiRateLimiter` - Rate limiting
    - `ApiTransformResponse` - Key transformation
    - `ETagCaching` - ETag support

---

## Production Tips

1. **Use Redis for Cache**
    - Faster rate limit checks
    - Set `RATE_LIMIT_CACHE=redis` in .env

2. **Monitor Rate Limit Hits**
    - Track 429 responses
    - Alert on abuse patterns
    - Adjust limits if needed

3. **Document Your API**
    - Include rate limit tiers
    - Explain field filtering
    - Show pagination examples

4. **Test Before Deploy**
    - Load test with concurrent requests
    - Verify cache invalidation
    - Check response times

---

**Ready to go!** Start using the API at `/api/v1/books` 🚀
