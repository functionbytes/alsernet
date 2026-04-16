# Queue Jobs Rules

## Job Structure

```php
<?php

namespace Modules\{ModuleName}\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\{ModuleName}\Models\{Entity};

class Process{Entity}Job implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 10;

    public function __construct(
        private readonly {Entity} $entity
    ) {
        $this->onQueue('{alias}');
    }

    public function handle(): void
    {
        // Business logic here
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('{Entity} job failed', [
            'id' => $this->entity->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

## Rules

- **Implement** `ShouldQueue` — all jobs must be queued (never synchronous heavy ops)
- **Use** `Queueable` trait (not the old `InteractsWithQueue`)
- **Properties required**:
  - `$tries` — always set (default 3)
  - `$timeout` — always set in seconds (default 60)
  - `$backoff` — seconds before retry (default 10)
- **`failed()` method**: ALWAYS define — log error with enough context to debug
- **Constructor**: use `readonly` for injected models/data; call `$this->onQueue()` for named queues
- **Queue names** by type:
  - `emails` — email sending jobs
  - `notifications` — push/broadcast notifications
  - `exports` — file generation (Excel, PDF, CSV)
  - `{alias}` — module-specific heavy jobs
  - `default` — general purpose
- **Model injection**: pass Eloquent models directly (SerializesModels handles re-fetching)
- **Dispatch from services**: `Process{Entity}Job::dispatch($entity)` — never from controllers
- **DB transactions**: wrap writes in `DB::transaction()` when multiple writes
- **No `env()` calls** inside jobs — use `config()` only
- **Testing**: use `Queue::fake()` + `Queue::assertPushed()` to assert jobs dispatched

## Scheduled Jobs (Artisan Commands as jobs)

For recurring background tasks, use Console Commands dispatched via scheduler in ServiceProvider:

```php
// In ServiceProvider::boot()
$this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
    $schedule->job(new CleanupOldRecordsJob)->daily();
});
```

## Chaining Jobs

```php
Bus::chain([
    new Step1Job($entity),
    new Step2Job($entity),
    new Step3Job($entity),
])->onQueue('{alias}')->dispatch();
```

## Batching Jobs

```php
Bus::batch([
    new ProcessItemJob($items[0]),
    new ProcessItemJob($items[1]),
])->then(function (Batch $batch) {
    // All jobs completed
})->catch(function (Batch $batch, \Throwable $e) {
    Log::error('Batch failed', ['error' => $e->getMessage()]);
})->dispatch();
```
