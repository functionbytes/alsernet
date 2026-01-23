<?php

namespace Modules\Campaign\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Entities\MailList;

/**
 * Event triggered when a mail list is imported.
 *
 * This event is dispatched whenever subscribers are imported into a mail list.
 * It can be listened to by event listeners for logging, notifications, or other actions.
 */
class MailListImported
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param MailList $mailList The mail list that was imported
     * @param string $importBatchId The batch ID for tracking the import
     */
    public function __construct(public MailList $mailList, public string $importBatchId)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('maillists'),
        ];
    }
}
