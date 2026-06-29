# Queue Module

Job queue management, rate limiting, and background task processing.

## Overview

The Queue module provides centralized configuration and utilities for managing asynchronous job processing and queue operations across all modules.

## Features

- **Job Queue Management** - Process jobs asynchronously (database, Redis, sync)
- **Rate Limiting** - Limit job execution rate using Spatie's rate-limited middleware
- **Failed Job Handling** - Track and retry failed jobs
- **Configuration** - Centralized queue configuration management
- **Multiple Drivers** - Support for database, Redis, and sync drivers

## Installation

The module is automatically included via Composer's merge-plugin system.

Dependencies:
- `spatie/laravel-rate-limited-job-middleware` (^2.8)

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=queue-config
```

The configuration file is located at `config/queue.php`.

## Usage

### Creating Rate-Limited Jobs

Use the `RateLimitedMiddleware` in your job class:

```php
namespace Modules\Supplier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\RateLimitedMiddleware\RateLimitedMiddleware;

class GenerateAiContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function middleware(): array
    {
        return [new RateLimitedMiddleware()];
    }

    public function handle(): void
    {
        // Job logic...
    }
}
```

### Dispatching Jobs

```php
// Queue for later processing
GenerateAiContentJob::dispatch($data);

// Queue on specific queue
GenerateAiContentJob::dispatch($data)->onQueue('high');

// Delay execution
GenerateAiContentJob::dispatch($data)->delay(now()->addMinutes(5));
```

### Monitoring Queues

Monitor job execution with Laravel Horizon:

```bash
php artisan horizon
```

View failed jobs:

```bash
php artisan queue:failed
php artisan queue:retry all
```

## Queue Drivers

### Database
```
Default driver. Uses `jobs` table. Good for small to medium volumes.
```

### Redis
```
High-performance driver. Requires Redis. Best for high-volume operations.
```

### Sync
```
Executes jobs synchronously. Useful for development and testing.
```

## Rate Limiting

Control job execution rate to prevent server overload:

```php
// In queue.php
'rate_limited' => [
    'enabled' => true,
    'per_minute' => 10,  // 10 jobs per minute
],
```

## Database Migrations

Jobs table migration is included. Run:

```bash
php artisan migrate
```

This creates:
- `jobs` table - Queued jobs
- `failed_jobs` table - Failed job records

## Common Jobs in Other Modules

This module is used by:
- **Document** - Mail template and SLA checking jobs
- **Mailer** - Email sending jobs
- **Supplier** - AI content generation and extraction jobs
- **Webhook** - Event delivery and processing jobs
- **Campaign** - Campaign update jobs
- **Subscriber** - Import and export jobs
- **Prestashop** - Content sync jobs

## References

- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Horizon Documentation](https://laravel.com/docs/horizon)
- [Spatie Rate Limited Middleware](https://github.com/spatie/laravel-rate-limited-job-middleware)

## Authors

Alsernet Development Team
