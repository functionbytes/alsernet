<?php

namespace App\Events;

use App\Models\Campaign\CampaignMaillist;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MailListImport
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $list;
    public $importBatchId;

    public function __construct(CampaignMaillist $list, $importBatchId)
    {
        $this->list = $list;
        $this->importBatchId = $importBatchId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

}
