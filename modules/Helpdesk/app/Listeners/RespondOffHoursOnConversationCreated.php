<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Concerns\LocalizesAutoReplyMessage;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\OffHoursResponse;
use Modules\Helpdesk\Services\BusinessHoursService;
use Modules\Helpdesk\Services\OutboundMessageService;
use Throwable;

/**
 * Responde automáticamente fuera de horario a la primera conversación de un
 * cliente, sea cual sea el canal (WhatsApp/Facebook/Instagram/web). Mismo
 * patrón que SendAwayAutoReply (sendReply externo + ConversationItem propio),
 * no ConversationMessageService::store(): no hay un agente al que atribuir el
 * mensaje y no queremos encolar un SendOutboundMessageJob duplicado.
 *
 * Antes esto vivía como una llamada suelta a OutboundMessageService::sendReply()
 * dentro de InboundMessageIngestor: nunca dejaba rastro en el hilo (el agente no
 * veía que se había respondido), no hacía nada en absoluto para conversaciones
 * web (sendReply() no-opea ese canal) y el widget/livechat real ni siquiera pasa
 * por InboundMessageIngestor. Engancharse a ConversationCreated —que SÍ disparan
 * tanto el pipeline de webhooks como el widget— resuelve ambos canales de una vez.
 *
 * Mutuamente excluyente con SendGreetingOnConversationCreated: este solo actúa
 * fuera de horario, aquel solo dentro — nunca se envían los dos a la vez.
 *
 * Respeta customerRecentlyContacted() (ver LocalizesAutoReplyMessage): si el
 * cliente reabrió el chat momentos después de cerrarlo, no se repite el aviso
 * de fuera de horario — pasada esa ventana corta sí se vuelve a enviar,
 * porque un cliente recurrente escribiendo de madrugada igual necesita saber
 * que está cerrado.
 */
class RespondOffHoursOnConversationCreated implements ShouldQueue
{
    use InteractsWithQueue;
    use LocalizesAutoReplyMessage;

    public string $queue = 'helpdesk';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(ConversationCreated $event): void
    {
        if (! helpdesk_off_hours_feature_enabled()) {
            return;
        }

        if (app(BusinessHoursService::class)->isOpenNow()) {
            return;
        }

        $conversation = $event->conversation;

        if ($this->customerRecentlyContacted($conversation)) {
            return;
        }

        $source = strtolower(substr((string) config('app.locale', 'es'), 0, 2));
        $customerLanguage = $this->resolveCustomerLanguage($conversation, $source);

        $response = OffHoursResponse::findForChannel($conversation->channel, $customerLanguage);

        if (! $response) {
            return;
        }

        // Un OffHoursResponse con `language` propio fue redactado a mano para
        // ese idioma exacto — se envía tal cual. Solo el genérico (sin idioma
        // asignado) pasa por traducción automática al vuelo.
        $message = $response->language
            ? $response->message
            : $this->localize($response->message, $customerLanguage, $source);

        try {
            // En canales externos (WhatsApp/FB/IG) empuja por la API; en web/widget
            // devuelve null y el cliente recibe el mensaje a través del
            // ConversationItem (el widget lo recoge por su propio canal/polling).
            $externalId = app(OutboundMessageService::class)->sendReply($conversation, $message, fast: true);

            ConversationItem::create([
                'conversation_id' => $conversation->id,
                'user_id' => null,
                'type' => 'message',
                'body' => $message,
                'is_internal' => false,
                'external_id' => $externalId,
                'metadata' => ['auto_reply' => 'off_hours'],
            ]);

            $conversation->update(['last_message_at' => now()]);
        } catch (Throwable $e) {
            Log::warning('RespondOffHoursOnConversationCreated: failed to send off-hours reply', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(ConversationCreated $event, Throwable $exception): void
    {
        Log::error('RespondOffHoursOnConversationCreated failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
