<?php

namespace Modules\Helpdesk\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Notifications\NewConversationNotification;

class SendNewConversationNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(ConversationCreated $event): void
    {
        $conversation = $event->conversation->fresh();

        // Corre en su propia cola ('notifications'), separada de la de
        // AutoAssignNewConversation ('helpdesk'): si el auto-assignment ya la
        // tomo para cuando este job procesa, no tiene sentido avisar "sin
        // asignar" de una conversacion que ya tiene dueno.
        if (! $conversation || $conversation->assignee_id !== null) {
            return;
        }

        // Mismo criterio de acceso que el canal 'helpdesk.inbox.{inboxId}'
        // (routes/channels.php): agentes con capacidad explicita en esa
        // bandeja, mas quien tenga el permiso global helpdesk.manage. Antes
        // se avisaba a TODOS los helpdesk-agent del sistema sin importar si
        // veian esa bandeja.
        $inboxAgentIds = $conversation->inbox_id
            ? AgentInboxCapacity::where('inbox_id', $conversation->inbox_id)->pluck('user_id')
            : collect();

        $recipients = User::permission('helpdesk.manage')
            ->orWhereIn('id', $inboxAgentIds)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new NewConversationNotification($conversation));
    }

    public function failed(ConversationCreated $event, \Throwable $exception): void
    {
        Log::error('SendNewConversationNotification failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
