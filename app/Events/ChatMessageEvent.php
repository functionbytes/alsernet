<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
// use Illuminate\Broadcasting\PresenceChannel;
// use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $message;
    public $userName;
    public $id;
    public $customerId;
    public $typingMessage;
    public $onlineUserUpdated;
    public $engageUser;
    public $agentInfo;
    public $comments;
    public $userMessageStatusUpdate;
    public $messageType;
    public $onlineStatusUpdate;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        $userName=null,
        $message=null,
        $id=null,
        $customerId=null,
        $typingMessage=null,
        $onlineUserUpdated=null,
        $engageUser=null,
        $agentInfo=null,
        $comments=null,
        $userMessageStatusUpdate=null,
        $messageType=null,
        $onlineStatusUpdate=null
        )
    {
        $this->userName = $userName;
        $this->message = $message;
        $this->id = $id;
        $this->customerId = $customerId;
        $this->typingMessage = $typingMessage;
        $this->onlineUserUpdated = $onlineUserUpdated;
        $this->engageUser = $engageUser;
        $this->agentInfo = $agentInfo;
        $this->comments = $comments;
        $this->userMessageStatusUpdate = $userMessageStatusUpdate;
        $this->messageType = $messageType;
        $this->onlineStatusUpdate = $onlineStatusUpdate;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // return new PrivateChannel("livechat.{$this->customerId}");
        return new Channel('liveChat');
    }
}
