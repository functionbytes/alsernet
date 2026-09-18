<?php

namespace Modules\Helpdesk\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentPresenceChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $oldState,
        public readonly string $newState,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('helpdesk.presence.global'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'presence.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'old_state' => $this->oldState,
            'new_state' => $this->newState,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
