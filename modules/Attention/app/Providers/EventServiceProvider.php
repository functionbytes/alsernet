<?php

namespace Modules\Attention\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Attention\Events\AttentionAssigned;
use Modules\Attention\Events\AttentionClosed;
use Modules\Attention\Events\AttentionCreated;
use Modules\Attention\Events\AttentionResolved;
use Modules\Attention\Events\AttentionStatusChanged;
use Modules\Attention\Listeners\SendAttentionAssignedNotification;
use Modules\Attention\Listeners\SendAttentionClosedNotification;
use Modules\Attention\Listeners\SendAttentionCreatedNotification;
use Modules\Attention\Listeners\SendAttentionResolvedNotification;
use Modules\Attention\Listeners\SendAttentionStatusChangedNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event listeners registry
     */
    protected $listen = [
        AttentionCreated::class => [
            SendAttentionCreatedNotification::class,
        ],
        AttentionAssigned::class => [
            SendAttentionAssignedNotification::class,
        ],
        AttentionResolved::class => [
            SendAttentionResolvedNotification::class,
        ],
        AttentionClosed::class => [
            SendAttentionClosedNotification::class,
        ],
        AttentionStatusChanged::class => [
            SendAttentionStatusChangedNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
