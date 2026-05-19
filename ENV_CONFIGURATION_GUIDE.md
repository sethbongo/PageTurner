# Environment Configuration for Database Architecture Enhancements

This file documents all environment variables needed for the database architecture features.

## Read/Write Splitting Configuration

```env
# Primary Database (Write Master)
DB_HOST=192.168.1.1              # Master database IP
DB_PORT=3306                      # MySQL port
DB_USERNAME=root                  # Database user
DB_PASSWORD=secure_password       # Database password
DB_DATABASE=pageturner_db         # Database name

# Read Replicas (Comma-separated list)
# Optional: If not set, defaults to same as DB_HOST
DB_READ_HOSTS=192.168.1.2,192.168.1.3

# Sticky Reads (Read-after-write consistency)
DB_STICKY_READS=true              # Ensures writes are read from master
```

### Example Production Configuration

```env
# Master (primary-db.example.com)
DB_HOST=primary-db.example.com
DB_PORT=3306
DB_USERNAME=bookstore_user
DB_PASSWORD=secure_password_here

# Replicas (read-only instances)
DB_READ_HOSTS=replica1-db.example.com,replica2-db.example.com

# Ensure sticky reads are enabled
DB_STICKY_READS=true
```

### Example Development Configuration

```env
# Single local database (no replicas)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=pageturner_dev

# Empty read hosts will fall back to master
DB_READ_HOSTS=

# Sticky reads still works with single database
DB_STICKY_READS=true
```

## Cache Configuration

For the QueryCacheService to work effectively, configure a cache store:

```env
# Cache driver: file, redis, memcached, etc.
CACHE_DRIVER=redis

# Redis Configuration (if using Redis)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Or use file cache for development
CACHE_DRIVER=file
```

### Cache Store Comparison

| Driver    | Speed     | Distributed | Persistence | Recommended      |
| --------- | --------- | ----------- | ----------- | ---------------- |
| file      | Slow      | No          | Yes         | Development only |
| redis     | Very Fast | Yes         | Optional    | Production       |
| memcached | Very Fast | Yes         | No          | High traffic     |
| database  | Slow      | Yes         | Yes         | Fallback         |

## Database Logging (Development Only)

```env
# Enable query logging to debug performance
DB_LOG_QUERIES=false  # Set to true to log all queries

# Log slow queries (MySQL server setting)
# Edit /etc/my.cnf or my.ini
# slow_query_log = 1
# long_query_time = 2
```

## Connection Pool Settings

While Laravel doesn't expose direct connection pool configuration in .env, PDO persistent connections are recommended:

```env
# In config/database.php, uncomment:
# \PDO::ATTR_PERSISTENT => true,
```

## Complete .env Template

```env
# ========================================
# DATABASE CONFIGURATION
# ========================================

# Connection Type
DB_CONNECTION=mysql

# Master (Write) Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=pageturner_db

# Read Replicas (optional)
DB_READ_HOSTS=

# Read-after-write consistency
DB_STICKY_READS=true

# ========================================
# CACHE CONFIGURATION
# ========================================

# Cache Driver: file, redis, memcached, database
CACHE_DRIVER=file
CACHE_PREFIX=pageturner_cache

# Redis Cache
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session Configuration
SESSION_DRIVER=file
SESSION_LIFETIME=120

# ========================================
# PERFORMANCE MONITORING
# ========================================

# Query logging
DB_LOG_QUERIES=false

# Application debugging
APP_DEBUG=false

# ========================================
# OTHER SETTINGS
# ========================================
APP_NAME=PageTurner
APP_ENV=production
APP_KEY=base64:generated_app_key
APP_URL=https://bookstore.example.com
```

## Deployment Steps

### 1. Development Environment

```bash
# Copy template
cp .env.example .env

# Keep defaults (single local database)
DB_HOST=127.0.0.1
DB_STICKY_READS=true

# Use file cache
CACHE_DRIVER=file
```

### 2. Staging Environment

```bash
# Configure read replicas if available
DB_HOST=staging-master.internal
DB_READ_HOSTS=staging-replica1.internal,staging-replica2.internal

# Use Redis cache if available
CACHE_DRIVER=redis
REDIS_HOST=staging-cache.internal
```

### 3. Production Environment

```bash
# Configure production databases
DB_HOST=prod-master.aws.internal
DB_READ_HOSTS=prod-replica1.aws.internal,prod-replica2.aws.internal,prod-replica3.aws.internal

# Use Redis for caching
CACHE_DRIVER=redis
REDIS_HOST=prod-cache.aws.internal
REDIS_PASSWORD=secure_password

# Disable debug mode
APP_DEBUG=false
```

## Verification

### Test Read/Write Splitting

```bash
# Connect to Laravel Tinker
php artisan tinker

# Test master connection (write)
>>> DB::connection('mysql')->table('books')->count()
// Should connect to master

# Test read operation
>>> DB::select("SELECT * FROM books LIMIT 1")
// May connect to replica if configured

# Verify sticky reads
>>> DB::insert("INSERT INTO test_table ...")
>>> DB::select("SELECT * FROM test_table")
// Should read from master after write
```

### Test Cache

```bash
php artisan tinker

# Test QueryCacheService
>>> $service = app('App\Services\QueryCacheService')
>>> $categories = $service->getCategories()
>>> Cache::get('bookstore_categories')  // Should return cached data
```

### Verify Indexes

```bash
php artisan tinker

# Check if indexes exist
>>> DB::select("SHOW INDEX FROM books WHERE Column_name = 'isbn'")
>>> DB::select("SHOW INDEX FROM orders WHERE Column_name = 'created_at'")
```

## Troubleshooting

### Issue: Read hosts not connecting

```env
# Verify read hosts format (comma-separated, no spaces)
DB_READ_HOSTS=192.168.1.2,192.168.1.3  # Correct
DB_READ_HOSTS=192.168.1.2, 192.168.1.3 # Wrong - has space
```

### Issue: Cache not working

```bash
# Clear all caches
php artisan cache:clear

# Test cache driver
php artisan cache:forget test_key
php artisan tinker
>>> Cache::put('test_key', 'test_value', 60)
>>> Cache::get('test_key')
```

### Issue: Stale reads after writes

```env
# Ensure sticky reads is enabled
DB_STICKY_READS=true
```

## Performance Tuning

### For High Traffic

```env
# Use multiple replicas
DB_READ_HOSTS=replica1.internal,replica2.internal,replica3.internal,replica4.internal

# Use Redis cache with persistence
CACHE_DRIVER=redis
```

### For Limited Resources

```env
# Single database
DB_READ_HOSTS=

# File cache (less memory)
CACHE_DRIVER=file
```

## Security Considerations

1. **Never commit `.env` to version control**
2. **Use different credentials for master and replicas**
3. **Restrict replica access to read-only user**
4. **Use SSH tunnels for remote database connections**
5. **Enable SSL for database connections**

```env
# Add SSL configuration if needed
DB_CERT=/path/to/ca-cert.pem
MYSQL_ATTR_SSL_CA=/path/to/ca-cert.pem
```

## References

- [Laravel Configuration](https://laravel.com/docs/configuration)
- [Database Configuration](https://laravel.com/docs/database#configuration)
- [Cache Configuration](https://laravel.com/docs/cache#configuration)
