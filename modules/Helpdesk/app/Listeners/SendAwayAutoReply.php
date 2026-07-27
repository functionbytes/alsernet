<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\AgentPresenceService;
use Modules\Helpdesk\Services\OutboundMessageService;

/**
 * When a customer writes to a conversation whose assigned agent is away (and has
 * an away message configured), auto-reply with that message on external channels
 * (WhatsApp/Facebook/Instagram). Throttled to one auto-reply per conversation per
 * hour so the customer isn't spammed on every inbound message.
 */
class SendAwayAutoReply implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk';

    public int $tries = 2;

    public int $backoff = 10;

    private const THROTTLE_TTL_MINUTES = 60;

    public function handle(MessageReceived $event): void
    {
        // Solo se auto-responde a mensajes entrantes del lado del cliente. Un mensaje
        // saliente del agente (user_id presente) —incluida la propia auto-respuesta,
        // que se re-emite como MessageReceived— y los eventos de sistema (type != message)
        // no deben disparar la respuesta de ausencia ni provocar un bucle. No se exige
        // author_id para no excluir a visitantes anónimos del widget.
        if (! $event->message->isMessage() || $event->message->isFromAgent()) {
            return;
        }

        $conversation = $event->conversation;
        $assigneeId = $conversation->assignee_id;

        if (! $assigneeId) {
            return;
        }

        $settings = AgentSettings::query()->where('user_id', $assigneeId)->first();

        if (! $settings) {
            return;
        }

        $awayMessage = trim($settings->preferences['away_message'] ?? '');

        if ($awayMessage === '') {
            return;
        }

        // El agente asignado debe estar realmente ausente (considera el heartbeat).
        $presence = app(AgentPresenceService::class);
        if ($presence->getState($assigneeId) === AgentSettings::PRESENCE_AVAILABLE) {
            return;
        }

        // Anti-spam: una sola auto-respuesta por conversación por ventana.
        $cacheKey = 'helpdesk:away-reply:'.$conversation->id;
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, now()->addMinutes(self::THROTTLE_TTL_MINUTES));

        // En canales externos (WhatsApp/FB/IG) empuja por la API; en web/widget
        // devuelve null y el cliente recibe el mensaje a través del ConversationItem
        // (el widget lo recoge por su propio canal en tiempo real / polling).
        $externalId = app(OutboundMessageService::class)->sendReply($conversation, $awayMessage);

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'user_id' => $assigneeId,
            'type' => 'message',
            'body' => $awayMessage,
            'is_internal' => false,
            'external_id' => $externalId,
            'metadata' => ['auto_reply' => 'away'],
        ]);

        $conversation->update(['last_message_at' => now()]);
    }

    public function failed(MessageReceived $event, \Throwable $exception): void
    {
        Log::error('SendAwayAutoReply failed', [
            'conversation_id' => $event->conversation->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
