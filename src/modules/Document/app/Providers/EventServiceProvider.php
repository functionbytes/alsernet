<?php

namespace Modules\Document\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Document\Events\DocumentCreated;
use Modules\Document\Listeners\SendDocumentCreatedNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event listeners registry
     */
    protected $listen = [
        DocumentCreated::class => [
            SendDocumentCreatedNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

    }

    private function registerBackupEventHandlers(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
