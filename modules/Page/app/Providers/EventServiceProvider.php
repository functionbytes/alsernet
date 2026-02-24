<?php

namespace Modules\Page\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Page\Models\Page;
use Modules\Page\Observers\PageObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Page\Events\PagePublished::class => [
            \Modules\Page\Listeners\WarmPageCacheOnPublish::class,
        ],
    ];

    /**
     * The model observers for the application.
     *
     * @var array
     */
    protected $observers = [
        Page::class => [PageObserver::class],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
