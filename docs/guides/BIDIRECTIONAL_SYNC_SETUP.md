# Bidirectional ERP ↔ Supplier Sync Setup Guide

This guide covers the setup and configuration of the **real-time bidirectional synchronization** between the Oracle ERP system and the Supplier module.

## Overview

The synchronization works in **two directions**:

```
┌──────────────────────────────────────────────────────────────┐
│                   ERP → SUPPLIER (REAL-TIME)                │
├──────────────────────────────────────────────────────────────┤
│ Continuous monitoring job detects Oracle changes             │
│ Syncs categories, providers, products, prices in real-time   │
│ Uses cache-based timestamps for efficient incremental sync   │
└──────────────────────────────────────────────────────────────┘
                           ↕
┌──────────────────────────────────────────────────────────────┐
│                 SUPPLIER → ERP (REAL-TIME)                   │
├──────────────────────────────────────────────────────────────┤
│ Model observers detect changes in Supplier module            │
│ Dispatch events to enqueued listeners                        │
│ Listeners sync changes back to Oracle asynchronously         │
│ Dead Letter Queue for failed syncs with manual retry         │
└──────────────────────────────────────────────────────────────┘
```

## Configuration

### 1. Environment Variables

Add to `.env`:

```bash
# ERP Oracle Monitoring
ORACLE_MONITOR_INTERVAL=30        # Seconds between monitor cycles (default: 30)
ORACLE_MONITOR_CHUNK_SIZE=100     # Records per transaction (default: 100)

# Queue Configuration for Bidirectional Sync
QUEUE_CONNECTION=redis            # Use Redis for queue
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default

# Supplier Sync Queue
ERP_SYNC_QUEUE=erp-sync          # Queue for Supplier → ERP sync jobs
```

### 2. Configuration Files

**File:** `config/queue.php`

The queue configuration is already set up. The oracle monitoring uses two queues:

- **`oracle-monitor`** - For continuous ERP monitoring job
- **`erp-sync`** - For Supplier → ERP event-driven listeners

Both queues use Redis as the default driver (configured via `QUEUE_CONNECTION`).

**File:** `modules/Erp/config/erp.php`

Configuration section `supplier_sync` controls:

```php
'supplier_sync' => [
    'oracle_monitor_interval' => 30,      // Seconds between cycles
    'oracle_monitor_chunk_size' => 100,   // Records per transaction
    'sync_queue' => 'erp-sync',           // Queue for Supplier → ERP
    'sync_timeout' => 30,                 // Job timeout in seconds
    'sync_retries' => 3,                  // Retry attempts
    'sync_backoff' => [5, 15, 30],        // Backoff delays in seconds
    'dlq_max_retries' => 5,               // Max retries in Dead Letter Queue
    'dlq_retry_delay' => 30,              // Minutes before auto-retry
],
```

## Startup & Operation

### Starting the Real-Time Monitor

To start the continuous ERP monitoring job:

```bash
# Start the monitor (dispatches to oracle-monitor queue)
php artisan erp:monitor-oracle

# For testing - run once synchronously
php artisan erp:monitor-oracle --once
```

This command:
1. Dispatches the `MonitorOracleChanges` job to the `oracle-monitor` queue
2. Provides setup instructions for running the worker
3. Logs the job dispatch

### Running Queue Workers

You **must** run queue workers to process the jobs. The system uses **two dedicated queues**:

#### 1. Oracle Monitor Worker (Continuous ERP Sync)

```bash
# Start the oracle-monitor worker
php artisan queue:work redis --queue=oracle-monitor --tries=2 --timeout=3600
```

**Configuration:**
- **Queue:** `oracle-monitor`
- **Tries:** 2 (rarely needed, job redespatches itself)
- **Timeout:** 3600 seconds (1 hour max)
- **Behavior:** Runs indefinitely, monitoring Oracle for changes

#### 2. ERP Sync Worker (Supplier → ERP Sync)

```bash
# Start the erp-sync worker for bidirectional sync
php artisan queue:work redis --queue=erp-sync --tries=3 --timeout=30 --backoff=5,15,30
```

**Configuration:**
- **Queue:** `erp-sync`
- **Tries:** 3 (with exponential backoff)
- **Timeout:** 30 seconds per job
- **Behavior:** Processes Supplier changes queued by model observers

#### Optional: Single Worker for All Queues

To run both queues with a single worker:

```bash
# Process both queues in priority order
php artisan queue:work redis --queues=oracle-monitor,erp-sync --tries=3 --timeout=3600
```

**Note:** This is simpler for development but not recommended for production due to timeout conflicts (oracle-monitor needs 3600s, erp-sync needs 30s).

### Supervisor/Systemd Configuration

For production, use Supervisor or Systemd to keep workers running.

#### Supervisor Configuration

**File:** `/etc/supervisor/conf.d/alsernet-queues.conf`

```ini
[program:oracle-monitor-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --queue=oracle-monitor --tries=2 --timeout=3600
directory=/path/to/project
numprocs=1
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/oracle-monitor-worker.log
stopwaitsecs=60

[program:erp-sync-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --queue=erp-sync --tries=3 --timeout=30 --backoff=5,15,30
directory=/path/to/project
numprocs=1
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/erp-sync-worker.log
stopwaitsecs=60
```

Then reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start oracle-monitor-worker:*
sudo supervisorctl start erp-sync-worker:*
```

#### Systemd Configuration

**File:** `/etc/systemd/system/alsernet-oracle-monitor.service`

```ini
[Unit]
Description=Alsernet Oracle Monitor Worker
After=network.target
Wants=redis-server.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/project
ExecStart=/usr/bin/php /path/to/artisan queue:work redis --queue=oracle-monitor --tries=2 --timeout=3600
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**File:** `/etc/systemd/system/alsernet-erp-sync.service`

```ini
[Unit]
Description=Alsernet ERP Sync Worker
After=network.target
Wants=redis-server.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/project
ExecStart=/usr/bin/php /path/to/artisan queue:work redis --queue=erp-sync --tries=3 --timeout=30 --backoff=5,15,30
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable alsernet-oracle-monitor.service
sudo systemctl enable alsernet-erp-sync.service
sudo systemctl start alsernet-oracle-monitor.service
sudo systemctl start alsernet-erp-sync.service
```

## Monitoring & Troubleshooting

### View Sync Logs

```bash
# Real-time log tail
tail -f storage/logs/laravel.log

# Search for Oracle monitor logs
grep "MonitorOracleChanges" storage/logs/laravel.log

# Search for sync errors
grep "Error in MonitorOracleChanges\|Failed to sync" storage/logs/laravel.log
```

### Check Queue Status

```bash
# List failed jobs
php artisan queue:failed:list

# Retry specific failed job
php artisan queue:retry <id>

# Retry all failed jobs in batch
php artisan queue:retry --all

# Monitor queue with Horizon (if installed)
php artisan horizon
```

### View Dead Letter Queue

The Dead Letter Queue stores failed syncs from **Supplier → ERP** that require manual intervention:

```bash
php artisan tinker

# Check failed syncs
>>> Modules\Supplier\Entities\SupplierSyncFailure::latest()->limit(10)->get()

# Retry failed syncs
>>> php artisan erp:retry-sync-failures --max-retries=5

# Retry only price syncs
>>> php artisan erp:retry-sync-failures --type=price
```

### Monitor Worker Health

```bash
# Check if workers are running
ps aux | grep "queue:work"

# Check supervisor status
sudo supervisorctl status

# Check systemd status
sudo systemctl status alsernet-oracle-monitor.service
sudo systemctl status alsernet-erp-sync.service
```

### Common Issues

#### Jobs Not Processing

**Issue:** Jobs are enqueued but not processing

**Solution:**
1. Check if worker is running: `ps aux | grep "queue:work"`
2. If not, start the worker: `php artisan queue:work redis --queue=oracle-monitor`
3. Check Redis connection: `redis-cli ping` (should return PONG)
4. Check logs for connection errors: `tail -f storage/logs/laravel.log`

#### Infinite Sync Loops

**Issue:** Changes in Supplier keep syncing to ERP and back

**Solution:**
- This is prevented by cache flags (`sync_in_progress_*`)
- Check logs for "sync in progress" messages
- If still happening, clear cache: `php artisan cache:clear`

#### High Memory Usage

**Issue:** Oracle monitor job consuming too much memory

**Solution:**
1. Reduce `ORACLE_MONITOR_CHUNK_SIZE`: `ORACLE_MONITOR_CHUNK_SIZE=50`
2. Increase monitor interval: `ORACLE_MONITOR_INTERVAL=60`
3. Check logs for memory issues: `grep "memory_mb" storage/logs/laravel.log`

#### Sync Lag

**Issue:** Changes not syncing in real-time

**Solution:**
1. Check ERP → Supplier: Is `oracle-monitor` worker running?
2. Check Supplier → ERP: Is `erp-sync` worker running?
3. Check Redis: `redis-cli info stats` to see activity
4. Check logs for sync errors: `grep "Error\|Failed" storage/logs/laravel.log`

## Synchronization Details

### ERP → Supplier (Continuous Monitoring)

**Job:** `MonitorOracleChanges` (namespace: `Modules\Erp\Jobs`)

**Operation:**
1. Runs in infinite loop (one cycle per 30-60 seconds)
2. Checks 9 entity types for changes:
   - Sports (DeporteCl)
   - Categories (CategoriaCl)
   - Families (FamiliaCl)
   - Subfamilies (SubfamiliaCl)
   - Groups (GrupoCl)
   - Providers (Proveedor)
   - Products (Articulo)
   - Provider Products (Artiprov)
   - Prices (ArtiprovTarifapro)
3. Detects changes via `fmodificacion > last_synced_at` comparison
4. Processes in chunks of 100 with per-chunk transactions
5. Updates `last_synced_at` timestamp in cache

**Command:** `php artisan erp:monitor-oracle`

### Supplier → ERP (Event-Driven)

**Operation:**
1. Model observers detect changes (`updated()` hook)
2. Observers filter for syncable fields only
3. Events dispatched: `SupplierProductPriceChanged`, `SupplierErpProviderUpdated`, `SupplierProductUpdated`
4. Listeners process events asynchronously via queue
5. Listeners call `ErpSyncService` to update Oracle
6. Conflict detection: ERP always wins
7. On failure: Stored in Dead Letter Queue (`supplier_sync_failures`)

**Retry Command:** `php artisan erp:retry-sync-failures`

## Conflict Resolution

When **both ERP and Supplier** are modified simultaneously:

```
ERP: Updated at 2025-01-12 10:00:00
Supplier: Updated at 2025-01-12 09:59:00  (before ERP change)
Listener attempts to sync Supplier → ERP at 2025-01-12 10:00:15
```

**What happens:**
1. Listener detects: `eRP.fmodificacion (10:00:00) > supplier.last_synced_at (09:59:00)`
2. **Conflict detected!**
3. ERP value wins (re-sync FROM Oracle)
4. Supplier is updated with ERP values
5. Log entry records the conflict and resolution

This ensures data consistency and prevents merge conflicts.

## Testing

### Test Real-Time Sync

```bash
# Terminal 1: Start the oracle monitor worker
php artisan queue:work redis --queue=oracle-monitor --tries=2 --timeout=3600

# Terminal 2: Start the erp-sync worker
php artisan queue:work redis --queue=erp-sync --tries=3 --timeout=30

# Terminal 3: Dispatch the monitor job
php artisan erp:monitor-oracle

# Terminal 4: Make a change in Oracle or Supplier and watch logs
tail -f storage/logs/laravel.log | grep -i "sync\|monitor"
```

### Test Supplier → ERP

```bash
# In Laravel Tinker
>>> php artisan tinker
Psy Shell v0.12.1 -- Copyright (c) 2012-2024 Justin Hibberd

# Find a price to update
>>> $price = \Modules\Supplier\Entities\SupplierProductPrice::first();
>>> $price->cost = 99.99;
>>> $price->save();

# Watch logs for the sync event
# tail -f storage/logs/laravel.log | grep "Price synced to ERP"
```

### Test Dead Letter Queue

```bash
# Force a sync failure by providing invalid data
>>> $price = \Modules\Supplier\Entities\SupplierProductPrice::first();
>>> $price->erp_price_id = 99999999; // Invalid ID
>>> $price->cost = 123.45;
>>> $price->save(); // Should fail and go to DLQ

# Check DLQ
>>> \Modules\Supplier\Entities\SupplierSyncFailure::latest()->first();

# Retry
>>> php artisan erp:retry-sync-failures
```

## Performance Considerations

### Monitoring Frequency

The monitor runs in a loop with a configurable interval:

- **Too fast (5s):** High CPU/database usage, Redis pressure
- **Too slow (300s):** Delayed sync, large batches
- **Recommended (30s):** Balances real-time response with resource efficiency

Adjust via `.env`:
```bash
ORACLE_MONITOR_INTERVAL=30
```

### Chunk Size

Processing records in chunks:

- **Too small (10):** Many transactions, slower overall
- **Too large (500):** Large memory footprint, rollback more records on error
- **Recommended (100):** Good balance for most cases

Adjust via `.env`:
```bash
ORACLE_MONITOR_CHUNK_SIZE=100
```

### Queue Concurrency

Number of queue workers:

- **Single worker:** Simpler, but queues may back up
- **Multiple workers:** Better concurrency, more resource usage
- **Recommended:** 1 oracle-monitor + 2-4 erp-sync workers

### Caching

Last sync timestamps are cached per entity:

- **Cache TTL:** 24 hours (can run out of sync if cache cleared)
- **Cache driver:** `config/cache.php` (default: Redis)
- **Issue:** If cache driver fails, monitor uses `now()->subYears(10)` fallback (full sync)

## FAQ

**Q: What happens if the monitor job crashes?**
A: The worker will retry up to 2 times. If it fails permanently, it goes to failed jobs table. Restart with `php artisan erp:monitor-oracle`.

**Q: How long does a full sync take?**
A: Depends on data volume. For 100k records with 30s monitor interval, approximately 30-60 minutes.

**Q: Can I have multiple oracle-monitor workers?**
A: No, only 1. Multiple workers would process the same changes twice. The job `redespatches()` itself, so it doesn't timeout.

**Q: What's the maximum transaction size?**
A: 100 records per transaction (configurable). Larger transactions risk rolling back more data on error.

**Q: Do I need Horizon installed?**
A: No, it's optional. Horizon provides a nice UI for monitoring. Without it, use `queue:failed:list` and logs.

**Q: How do I troubleshoot sync failures?**
A: Check `supplier_sync_failures` table (Dead Letter Queue). Use `erp:retry-sync-failures` command to retry.

---

**Last Updated:** 2025-01-12
**Version:** 2.0 (Bidirectional Real-Time Sync)
