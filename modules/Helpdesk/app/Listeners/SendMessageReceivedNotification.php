<?php

namespace Modules\Helpdesk\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Notifications\MessageReceivedNotification;

class SendMessageReceivedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(MessageReceived $event): void
    {
        $conversation = $event->conversation;
        $message = $event->message;

        // MessageReceived se dispara para CUALQUIER item no interno (entrante
        // del cliente Y saliente del agente — BroadcastOutboundMessageJob lo
        // reutiliza para alimentar el widget del cliente en ambos sentidos).
        // Sin este corte, un agente que responde una conversación se
        // auto-notificaba "Nuevo mensaje del cliente" mostrando su propio
        // mensaje — más visible aún desde que responder auto-asigna la
        // conversación al propio agente (ver ConversationMessageService::store).
        if ($message->isFromAgent()) {
            return;
        }

        // Notify the assigned agent, or all admins/managers if unassigned.
        // Filtra por el permiso 'helpdesk.conversations.reply' (los 4 roles
        // de conversaciones lo dan — ver HelpdeskRolesSeeder) en vez del
        // nombre de rol 'helpdesk-agent' a secas, que dejaba fuera a
        // 'helpdesk-agent-restricted'/'helpdesk-supervisor' (perfiles,
        // 21-sep-2026).
        if ($conversation->assignee_id) {
            $recipients = User::where('id', $conversation->assignee_id)->get();
        } else {
            $recipients = User::permission('helpdesk.conversations.reply')
                ->orWhereHas('roles', fn ($q) => $q->whereIn('name', ['administrative', 'manager']))
                ->get();
        }

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new MessageReceivedNotification($conversation, $message));
    }

    public function failed(MessageReceived $event, \Throwable $exception): void
    {
        Log::error('SendMessageReceivedNotification failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'message_id' => $event->message->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
