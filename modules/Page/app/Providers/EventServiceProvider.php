<?php

namespace Modules\Page\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Forms\Events\FormSubmitted;
use Modules\Page\Events\PagePublished;
use Modules\Page\Events\PagePublishedForSubscribers;
use Modules\Page\Listeners\InvalidatePagesOnFormSubmitted;
use Modules\Page\Listeners\NotifySubscribersOnPagePublish;
use Modules\Page\Listeners\WarmPageCacheOnPublish;
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
        PagePublished::class => [
            WarmPageCacheOnPublish::class,
        ],
        PagePublishedForSubscribers::class => [
            NotifySubscribersOnPagePublish::class,
        ],
        FormSubmitted::class => [
            InvalidatePagesOnFormSubmitted::class,
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
