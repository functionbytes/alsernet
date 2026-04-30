<?php

namespace Modules\Chat\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Chat\Events\ConversationAssigned;

class AssignConversationNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ConversationAssigned $event): void
    {
        // Reserved for additional assignment side-effects (workload tracking, customer notification, etc.)
    }
}
