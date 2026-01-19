# Performance Optimization Guide - STM2 Project

## Problems Identified

### 1. N+1 Query Problem (CRITICAL)
The `SlotController::index()` method calls `calculateBlockingRisk()` for EACH slot in a loop.
With 100 slots and 5-10 queries per call = 500-1000 queries per page load!

### 2. Heavy `calculateBlockingRisk()` Function
This function makes 8-15 database queries each time it's called.

### 3. Remote Database Latency
Database is on a remote server (192.102.30.79), adding latency to every query.

---

## Solutions

### Phase 1: Quick Wins (Immediate)

#### 1.1 Install Laravel Debugbar for Profiling
```bash
composer require barryvdh/laravel-debugbar --dev
```

#### 1.2 Enable Query Logging in .env
```env
# Add these lines
DB_LOG_QUERIES=true
TELESCOPE_ENABLED=true
```

#### 1.3 Cache Blocking Risk in Database
Instead of recalculating on every page load, store `blocking_risk` in the database
and recalculate only when relevant data changes.

```php
// In SlotController::index(), REMOVE the foreach loop that calls calculateBlockingRisk()
// Use the pre-cached blocking_risk value from database instead
```

### Phase 2: Optimize Queries (Short Term)

#### 2.1 Create a Background Job for Blocking Risk Calculation
```php
// Create: app/Jobs/RecalculateBlockingRiskJob.php
// Run via scheduler: php artisan schedule:work
```

#### 2.2 Add Missing Database Indexes
```sql
-- Check and add these indexes if missing
CREATE INDEX IF NOT EXISTS idx_slots_blocking_risk ON slots (blocking_risk);
CREATE INDEX IF NOT EXISTS idx_slots_ticket_number ON slots (ticket_number);
CREATE INDEX IF NOT EXISTS idx_slots_warehouse_status ON slots (warehouse_id, status);
```

#### 2.3 Implement Eager Loading
Replace multiple queries with JOINs where possible.

### Phase 3: Caching Strategy (Medium Term)

#### 3.1 Install Redis
```bash
# Windows: Download Redis from https://github.com/microsoftarchive/redis/releases
# Or use Memurai: https://www.memurai.com/

# Linux/Docker:
docker run -d -p 6379:6379 redis:alpine
```

#### 3.2 Configure Redis in .env
```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### 3.3 Install PHP Redis Extension
```bash
# For XAMPP on Windows, download php_redis.dll from PECL
# Add to php.ini: extension=redis
```

#### 3.4 Implement Cache for Filter Options
```php
// In SlotFilterService::getFilterOptions()
public function getFilterOptions(): array
{
    return Cache::remember('slot_filter_options', now()->addMinutes(30), function () {
        return [
            'warehouses' => DB::table('warehouses')->select(['id', 'wh_name as name', 'wh_code as code'])->orderBy('wh_name')->get(),
            'gates' => DB::table('gates as g')->...->get(),
        ];
    });
}
```

### Phase 4: Pagination (Long Term)

#### 4.1 Implement Proper Pagination
Currently the code fetches ALL slots when `page_size=all`. 
Change default to paginated results:

```php
// In SlotFilterService::validatePageSize()
public function validatePageSize(string $pageSize): string
{
    $pageSizeAllowed = ['10', '25', '50', '100', 'all'];
    
    // Change default from 'all' to '50'
    if (!in_array($pageSize, $pageSizeAllowed, true)) {
        return '50';  // Changed from 'all'
    }
    
    return $pageSize;
}
```

---

## Quick Implementation Checklist

- [ ] Install Laravel Debugbar for profiling
- [ ] Remove the blocking risk recalculation loop from index()
- [ ] Add scheduler job to recalculate blocking risk every 5 minutes
- [ ] Set default page_size to 50 instead of 'all'
- [ ] Install Redis for caching
- [ ] Add cache to getFilterOptions()
- [ ] Add proper database indexes

---

## Expected Performance Improvement

| Before | After |
|--------|-------|
| 500-1000 queries per page | 5-10 queries per page |
| 20+ seconds loading | < 1 second loading |
| No caching | Redis caching |
| All records loaded | Paginated (50 per page) |
