<?php

namespace Modules\Chat\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Chat\Events\ConversationAssigned;
use Modules\Chat\Notifications\ConversationAssignedNotification;

class NotifyAgentsListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ConversationAssigned $event): void
    {
        $agent = $event->assignedAgent;

        if (! $agent) {
            return;
        }

        $agent->notify(new ConversationAssignedNotification($event->conversation));
    }
}
