<?php

namespace App\Events\Helpdesk;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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

    public function __construct($message, $receiverId, $senderId, $senderName, $typingMessage = null, $openedUser = null, $senderImage = null, $groupInclude = null, $messageType = null)
    {
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

    public function broadcastOn()
    {
        return new PresenceChannel('agentMessage');
    }
}
