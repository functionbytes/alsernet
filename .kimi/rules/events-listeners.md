# Rule: events-listeners

# Events & Listeners Rules

## Event Structure

```php
<?php

namespace Modules\{ModuleName}\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}Created
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly {Entity} $entity
    ) {}
}
```

## Broadcastable Event

```php
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class {Entity}Created implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly {Entity} $entity) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->entity->user_id)];
    }

    public function broadcastType(): string
    {
        return '{alias}.{entity}.created';
    }

    public function toBroadcast(): array
    {
        return ['id' => $this->entity->id];
    }
}
```

## Listener Structure

```php
<?php

namespace Modules\{ModuleName}\Listeners;

use Modules\{ModuleName}\Events\{Entity}Created;

class Send{Entity}Notification
{
    public function handle({Entity}Created $event): void
    {
        // Logic here
    }
}
```

## Queued Listener

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class Send{Entity}Notification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';
    public int $tries = 3;
    public int $backoff = 5;

    public function handle({Entity}Created $event): void {}

    public function failed({Entity}Created $event, \Throwable $exception): void
    {
        Log::error('Listener failed', ['error' => $exception->getMessage()]);
    }
}
```

## Registration in EventServiceProvider

```php
protected $listen = [
    {Entity}Created::class => [
        Send{Entity}Notification::class,
        Log{Entity}Activity::class,
    ],
];
```

## Rules

- **Event naming**: `{Entity}{PastTense}` — `AttentionCreated`, `UserUpdated`, `OrderShipped`
- **Listener naming**: verb phrase — `SendAttentionNotification`, `LogUserActivity`
- **Constructor**: use `readonly` on public properties
- **Serialization**: ALWAYS include `SerializesModels` when event carries an Eloquent model
- **Broadcasting**: implement `ShouldBroadcast` only when real-time UI update is needed
- **Channel privacy**: use `PrivateChannel` for user-specific data, `Channel` for public
- **Queued listeners**: implement `ShouldQueue` for email, notifications, heavy processing
- **failed() method**: ALWAYS define when listener is queued — log the error
- **No direct DB writes in event constructors** — constructors are for data, not side effects
- **Dispatch events in services, not controllers**: `{Entity}Created::dispatch($entity)` in service layer
- **Testing**: use `Event::fake()` to assert events dispatched, `Queue::fake()` for queued listeners
