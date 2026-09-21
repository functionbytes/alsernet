<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Concerns\LocalizesAutoReplyMessage;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\OffHoursResponse;
use Throwable;

/**
 * Lógica compartida del aviso automático de "fuera de horario", extraída de
 * RespondOffHoursOnConversationCreated para poder dispararla también en
 * mensajes de una conversación YA EXISTENTE (no solo al crearla) — ver
 * maybeReplyToExistingConversation(). El listener de ConversationCreated
 * sigue siendo el único punto para conversaciones nuevas
 * (maybeReplyToNewConversation()); ambos comparten el envío real
 * (sendReply()).
 */
class OffHoursAutoReplyService
{
    use LocalizesAutoReplyMessage;

    /**
     * Ventana de repetición para una conversación YA existente: si ya se le
     * mandó el aviso de fuera de horario hace menos de esto, no se repite en
     * cada mensaje nuevo del mismo intercambio — pasada la ventana, un
     * cliente que sigue escribiendo (o vuelve más tarde) sí vuelve a
     * recibirlo. Mismo valor que RECENT_CONTACT_WINDOW_MINUTES del trait, por
     * consistencia (misma semántica: "¿ya se avisó hace poco?").
     */
    private const int REPLY_COOLDOWN_MINUTES = 60;

    /**
     * Conversación NUEVA (primer mensaje del cliente) — comportamiento
     * histórico de RespondOffHoursOnConversationCreated, sin tocar: solo
     * mira customerRecentlyContacted() (agrupa por CLIENTE, no por
     * conversación, porque aún no hay historial en esta).
     */
    public function maybeReplyToNewConversation(Conversation $conversation): void
    {
        if ($this->customerRecentlyContacted($conversation)) {
            return;
        }

        $this->send($conversation);
    }

    /**
     * Conversación YA ABIERTA que recibe otro mensaje del cliente mientras
     * seguimos fuera de horario. Antes esto no pasaba nunca (solo
     * ConversationCreated dispara el aviso) — un cliente que reabre un chat
     * de días atrás, o que sigue escribiendo de madrugada en una
     * conversación en curso, no se enteraba de que está fuera de horario.
     * Dos guardas propias (no las del caso "nueva"): la conversación debe
     * seguir abierta (is_open del status — cerrada/resuelta/archivada no
     * cuenta) y no habérsele mandado ya el aviso hace menos de
     * REPLY_COOLDOWN_MINUTES.
     */
    public function maybeReplyToExistingConversation(Conversation $conversation): void
    {
        if (! $conversation->status?->is_open) {
            return;
        }

        if ($this->repliedRecently($conversation)) {
            return;
        }

        $this->send($conversation);
    }

    protected function repliedRecently(Conversation $conversation): bool
    {
        return ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('created_at', '>=', now()->subMinutes(self::REPLY_COOLDOWN_MINUTES))
            ->whereJsonContains('metadata->auto_reply', 'off_hours')
            ->exists();
    }

    protected function send(Conversation $conversation): void
    {
        $source = strtolower(substr((string) config('app.locale', 'es'), 0, 2));
        $customerLanguage = $this->resolveCustomerLanguage($conversation, $source);

        $response = OffHoursResponse::findForChannel($conversation->channel, $customerLanguage);

        if (! $response) {
            return;
        }

        // Un OffHoursResponse con `language` propio fue redactado a mano para
        // ese idioma exacto — se envía tal cual. Solo el genérico (sin
        // idioma asignado) pasa por traducción automática al vuelo.
        $message = $response->language
            ? $response->message
            : $this->localize($response->message, $customerLanguage, $source);

        try {
            // En canales externos (WhatsApp/FB/IG) empuja por la API; en
            // web/widget devuelve null y el cliente recibe el mensaje a
            // través del ConversationItem (el widget lo recoge por su propio
            // canal/polling).
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
            Log::warning('OffHoursAutoReplyService: failed to send off-hours reply', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
