<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use App\Models\Campaign\CampaignMaillist;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\Channel;

class MailListImport
{
    use Dispatchable ,InteractsWithSockets ,SerializesModels;

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
