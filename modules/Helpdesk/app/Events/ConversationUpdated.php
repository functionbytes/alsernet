<?php

namespace Modules\Helpdesk\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Group;

/**
 * Event fired when a conversation is updated
 */
class ConversationUpdated implements ShouldBroadcast
{
    use BroadcastsOnServedQueue, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public readonly ?int $byUserId = null,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * BUG (QA tiempo real, 18-sep-2026): antes emitia en
     * PrivateChannel('conversations.'.$id) — un canal que nadie autoriza en
     * ningun routes/channels.php y que el JS del hilo nunca suscribe (este
     * usa 'helpdesk.conversation.'.$id). El resultado: cuando un agente
     * cambiaba estado/prioridad/agente/equipo, el panel derecho de OTRO
     * agente con la misma conversacion abierta se quedaba con los valores
     * viejos hasta recargar la pagina — el evento se disparaba pero jamas
     * llegaba a ningun navegador. Se corrige para usar el mismo canal ya
     * autorizado (ConversationPolicy::view) al que conversations-thread.js
     * ya esta suscrito para '.item.created'.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('helpdesk.conversation.'.$this->conversation->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $this->conversation->loadMissing(['status', 'assignee:id,firstname,lastname']);

        $groupName = $this->conversation->group_id
            ? Group::query()->whereKey($this->conversation->group_id)->value('name')
            : null;

        return [
            'conversation_id' => $this->conversation->id,
            'subject' => $this->conversation->subject,
            'priority' => $this->conversation->priority,
            'status' => $this->conversation->status ? [
                'id' => $this->conversation->status->id,
                'name' => $this->conversation->status->name,
            ] : null,
            'assignee' => $this->conversation->assignee ? [
                'id' => $this->conversation->assignee->id,
                'name' => trim($this->conversation->assignee->firstname.' '.$this->conversation->assignee->lastname),
            ] : null,
            'group' => $this->conversation->group_id ? [
                'id' => $this->conversation->group_id,
                'name' => $groupName,
            ] : null,
            'by_user_id' => $this->byUserId,
            'updated_at' => $this->conversation->updated_at?->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }
}
