<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Concerns\LocalizesAutoReplyMessage;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\ConversationGreeting;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\BusinessHoursService;
use Modules\Helpdesk\Services\OutboundMessageService;
use Throwable;

/**
 * Responde con un mensaje de bienvenida a la primera conversación de un
 * cliente, cuando SÍ está en horario de atención — mutuamente excluyente con
 * RespondOffHoursOnConversationCreated (que solo actúa fuera de horario), para
 * no mandar dos mensajes automáticos a la vez en una conversación nueva.
 *
 * "Primera conversación" es literal a nivel de fila (una por cada vez que se
 * crea/reabre un hilo), no del cliente entero: cerrar y reabrir SIEMPRE crea
 * una Conversation nueva en cualquier canal, así que sin el guard de
 * customerRecentlyContacted() (ver LocalizesAutoReplyMessage) un cliente
 * recurrente que reabre el chat momentos después de cerrarlo recibía la
 * bienvenida de nuevo, como si fuera la primera vez.
 *
 * Mismo patrón que RespondOffHoursOnConversationCreated (sendReply externo +
 * ConversationItem propio); ver esa clase para el porqué de no usar
 * ConversationMessageService::store().
 */
class SendGreetingOnConversationCreated implements ShouldQueue
{
    use InteractsWithQueue;
    use LocalizesAutoReplyMessage;

    public string $queue = 'helpdesk';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(ConversationCreated $event): void
    {
        if (! helpdesk_greeting_feature_enabled()) {
            return;
        }

        if (! app(BusinessHoursService::class)->isOpenNow()) {
            return;
        }

        $conversation = $event->conversation;

        if ($this->customerRecentlyContacted($conversation)) {
            return;
        }

        $source = strtolower(substr((string) config('app.locale', 'es'), 0, 2));
        $customerLanguage = $this->resolveCustomerLanguage($conversation, $source);

        $response = ConversationGreeting::findForChannel($conversation->channel, $customerLanguage);

        if (! $response) {
            return;
        }

        $message = $response->language
            ? $response->message
            : $this->localize($response->message, $customerLanguage, $source);

        try {
            $externalId = app(OutboundMessageService::class)->sendReply($conversation, $message, fast: true);

            ConversationItem::create([
                'conversation_id' => $conversation->id,
                'user_id' => null,
                'type' => 'message',
                'body' => $message,
                'is_internal' => false,
                'external_id' => $externalId,
                'metadata' => ['auto_reply' => 'greeting'],
            ]);

            $conversation->update(['last_message_at' => now()]);
        } catch (Throwable $e) {
            Log::warning('SendGreetingOnConversationCreated: failed to send greeting', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(ConversationCreated $event, Throwable $exception): void
    {
        Log::error('SendGreetingOnConversationCreated failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
