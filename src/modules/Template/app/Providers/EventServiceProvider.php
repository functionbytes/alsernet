<?php

namespace Modules\Template\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Template\Models\Menu;
use Modules\Template\Models\Template;
use Modules\Template\Observers\TemplateObserver;
use Modules\Template\Policies\MenuPolicy;
use Modules\Template\Policies\TemplatePolicy;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * The model observers for the application.
     *
     * @var array
     */
    protected $observers = [
        Template::class => [TemplateObserver::class],
    ];

    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Menu::class => MenuPolicy::class,
        Template::class => TemplatePolicy::class,
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;
}
