# Testing Scripts - STM2 Warehouse System

## Overview
Collection of testing scripts untuk simulasi operasional gudang dan stress testing sistem Slot Time Management.

## Scripts Available

### 1. `test_warehouse_simulation.ps1`
**Purpose**: Simulasi operasional gudang sibuk dengan realistic workflow

**Scenario**:
- 20 slot creation dalam 3 batch (morning, mid-day, afternoon)
- Concurrent arrivals, starts, dan completions
- Multiple warehouse operations
- Real-time performance monitoring

**Usage**:
```powershell
.\test_warehouse_simulation.ps1
```

**Features**:
- ✅ Login authentication
- ✅ Batch processing (8+7+5 slots)
- ✅ Concurrent operations
- ✅ Performance metrics
- ✅ Error handling

### 2. `stress_test.ps1`
**Purpose**: Stress testing dengan multiple concurrent users

**Scenario**:
- Multiple users bersamaan (default 10 users)
- Setiap user create 5 slots
- Full slot lifecycle progression
- Performance benchmarking

**Usage**:
```powershell
# Default: 10 users, 5 slots each
.\stress_test.ps1

# Custom parameters
.\stress_test.ps1 -ConcurrentUsers 20 -SlotsPerUser 10

# Heavy load test
.\stress_test.ps1 -ConcurrentUsers 50 -SlotsPerUser 5
```

**Parameters**:
- `ConcurrentUsers`: Jumlah simultaneous users (default: 10)
- `SlotsPerUser`: Slots per user (default: 5)
- `BaseUrl`: Application URL (default: http://localhost:8000)

### 3. `test_warehouse_simulation.sh`
**Purpose**: Bash version untuk Linux/macOS

**Usage**:
```bash
chmod +x test_warehouse_simulation.sh
./test_warehouse_simulation.sh
```

## Prerequisites

### Server Setup
1. **Start Laravel server**:
```bash
php artisan serve
```

2. **Database ready**:
```bash
php artisan migrate:fresh --seed
```

3. **Environment**: `.env` configured dengan MySQL

### Client Requirements
- **PowerShell 5.1+** (Windows)
- **Curl** (Linux/macOS)
- **Network access** ke localhost:8000

## Test Scenarios

### 🏭 Normal Operations
```powershell
.\test_warehouse_simulation.ps1
```
- 20 slots dalam realistic timeframes
- Sequential batch processing
- Performance baseline

### 🚀 Peak Hours Simulation
```powershell
.\stress_test.ps1 -ConcurrentUsers 15 -SlotsPerUser 8
```
- 120 concurrent operations
- Rush hour stress test
- System limits identification

### ⚡ Extreme Load Test
```powershell
.\stress_test.ps1 -ConcurrentUsers 50 -SlotsPerUser 10
```
- 500+ concurrent operations
- Stress testing limits
- Performance degradation analysis

## Performance Metrics

### Key Indicators
- **Throughput**: Slots per second
- **Success Rate**: Percentage of completed operations
- **Response Time**: Average operation duration
- **Error Rate**: Failed operations percentage

### Benchmarks
| Metric | Excellent | Good | Acceptable | Poor |
|--------|-----------|-------|------------|------|
| Throughput | >5/s | 3-5/s | 1-3/s | <1/s |
| Success Rate | ≥95% | 90-95% | 85-90% | <85% |
| Response Time | <2s | 2-5s | 5-10s | >10s |

## Test Results Interpretation

### ✅ Passing Criteria
- Success rate ≥95%
- Throughput ≥3 slots/second
- No database deadlocks
- Memory usage stable

### ⚠️ Warning Signs
- Success rate 90-95%
- Response time degradation
- Intermittent timeouts
- High memory usage

### ❌ Failing Indicators
- Success rate <90%
- Database deadlocks
- Server crashes
- Memory leaks

## Troubleshooting

### Common Issues

#### Login Failures
```powershell
# Check admin user exists
php artisan tinker
>>> User::where('username', 'admin')->first();
```

#### Database Connection
```powershell
# Verify database
php artisan migrate:status
```

#### Server Not Responding
```powershell
# Check server status
curl -I http://localhost:8000
```

#### Permission Errors
```powershell
# Check user permissions
php artisan tinker
>>> $user = User::find(1);
>>> $user->getAllPermissions()->pluck('name');
```

### Performance Optimization

#### Database Indexes
```sql
-- Check slow queries
SHOW FULL PROCESSLIST;

-- Add indexes if needed
CREATE INDEX idx_slots_status_created ON slots(status, created_at);
```

#### PHP Configuration
```ini
; php.ini
max_execution_time = 300
memory_limit = 512M
```

#### Laravel Optimization
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Advanced Testing

### Custom Scenarios
Create custom test scripts dengan modifying existing templates:

```powershell
# Custom high-frequency test
for ($i = 1; $i -le 100; $i++) {
    Start-Job -ScriptBlock { ... } -ArgumentList $i
}
```

### Database Monitoring
```bash
# Monitor MySQL
mysqladmin -u root -p processlist

# Monitor connections
SHOW STATUS LIKE 'Threads_connected';
```

### Application Logs
```bash
# Monitor Laravel logs
tail -f storage/logs/laravel.log

# Monitor PHP errors
tail -f /var/log/php_errors.log
```

## Reporting

### Generate Test Report
Test results automatically include:
- Performance metrics
- Error summaries
- User-specific results
- Benchmark comparisons

### Export Results
```powershell
# Save to file
.\stress_test.ps1 > test_results_$(date +%Y%m%d_%H%M%S).txt
```

## Best Practices

### Before Testing
1. **Backup database**
2. **Clear caches**: `php artisan cache:clear`
3. **Monitor resources**: CPU, RAM, Disk I/O
4. **Set baseline**: Run single user test first

### During Testing
1. **Monitor logs** in real-time
2. **Watch database connections**
3. **Track memory usage**
4. **Record response times**

### After Testing
1. **Analyze error patterns**
2. **Compare with baseline**
3. **Document findings**
4. **Plan optimizations**

## Continuous Integration

### Automated Testing
```yaml
# .github/workflows/stress-test.yml
name: Stress Test
on: [push, pull_request]
jobs:
  stress-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Run Stress Test
        run: ./stress_test.sh
```

### Performance Monitoring
Integrate dengan:
- **New Relic** untuk APM
- **Datadog** untuk metrics
- **Grafana** untuk dashboards
- **Prometheus** untuk alerting

---

## Support

For issues atau questions:
1. Check logs: `storage/logs/laravel.log`
2. Verify configuration: `.env`
3. Test manually via browser
4. Review system resources

**Happy Testing! 🚀**
