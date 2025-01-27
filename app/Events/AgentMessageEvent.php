<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class AgentMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $receiverId;
    public $senderId;
    public $senderName;
    public $typingMessage;
    public $openedUser;
    public $senderImage;
    public $groupInclude;
    public $messageType;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($message,$receiverId,$senderId,$senderName,$typingMessage=null,$openedUser=null,$senderImage=null,$groupInclude=null,$messageType=null)
    {
        //
        $this->message = $message;
        $this->receiverId = $receiverId;
        $this->senderId = $senderId;
        $this->senderName = $senderName;
        $this->typingMessage = $typingMessage;
        $this->openedUser = $openedUser;
        $this->senderImage = $senderImage;
        $this->groupInclude = $groupInclude;
        $this->messageType = $messageType;

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('agentMessage');
    }
}
