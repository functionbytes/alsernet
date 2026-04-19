<?php

namespace Modules\Shortcode\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Shortcode\Events\ShortcodeCompiled;
use Modules\Shortcode\Listeners\RecordShortcodeUsage;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        ShortcodeCompiled::class => [
            RecordShortcodeUsage::class,
        ],
    ];

    /**
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
