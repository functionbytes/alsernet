# Eventos en Modulos

## Crear evento y listener

```bash
php artisan module:make-event PostCreated Blog
php artisan module:make-listener SendNotification Blog --event=PostCreated --queued
```

## Registrar eventos

### Opcion 1: EventServiceProvider dedicado (recomendado)

```php
<?php

namespace Modules\Blog\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Nwidart\Modules\Facades\Module;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Modules\Blog\Events\PostCreated::class => [
            \Modules\Blog\Listeners\SendNotification::class,
        ],
        \Modules\Blog\Events\PostUpdated::class => [
            \Modules\Blog\Listeners\UpdateSearchIndex::class,
        ],
    ];

    public function boot(): void
    {
        if (Module::find('Blog')?->isDisabled()) {
            return;
        }
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
```

Registrar en ServiceProvider principal:
```php
public function register(): void
{
    $this->app->register(EventServiceProvider::class);
}
```

### Opcion 2: Registro inline en ServiceProvider

```php
public function boot(): void
{
    Event::listen(PostCreated::class, SendNotification::class);
}
```

### Opcion 3: Model Observers

```php
public function boot(): void
{
    BlogPost::observe(BlogPostObserver::class);
}
```

## Patron del proyecto

Los modulos con eventos complejos (Attention, Blog, Helpdesk) usan EventServiceProvider dedicado.
Los modulos simples usan Event::listen() inline o observers.
