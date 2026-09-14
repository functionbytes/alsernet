<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Concerns\LocalizesAutoReplyMessage;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Models\ConversationFarewell;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\OutboundMessageService;
use Throwable;

/**
 * Envía un mensaje de despedida cuando se cierra una conversación (cualquier
 * canal). A diferencia de los listeners de bienvenida/fuera-de-horario, no
 * depende del horario de atención: cerrar es una acción del agente (o de un
 * flujo automático), no algo que dependa de si el negocio está abierto.
 *
 * Mismo patrón que RespondOffHoursOnConversationCreated (sendReply externo +
 * ConversationItem propio); ver esa clase para el porqué de no usar
 * ConversationMessageService::store().
 *
 * Ojo: ConversationClosed se dispara manualmente en los controllers/servicios
 * que cierran conversaciones (ConversationsController, BulkConversationsController,
 * ConversationsApiController, ConversationMessageService), no dentro de
 * Conversation::close() — cualquier código que llame a close() directamente sin
 * pasar por esos puntos no disparará este listener.
 */
class SendFarewellOnConversationClosed implements ShouldQueue
{
    use InteractsWithQueue;
    use LocalizesAutoReplyMessage;

    public string $queue = 'helpdesk';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(ConversationClosed $event): void
    {
        if (! helpdesk_farewell_feature_enabled()) {
            return;
        }

        $conversation = $event->conversation;
        $source = strtolower(substr((string) config('app.locale', 'es'), 0, 2));
        $customerLanguage = $this->resolveCustomerLanguage($conversation, $source);

        $response = ConversationFarewell::findForChannel($conversation->channel, $customerLanguage);

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
                'metadata' => ['auto_reply' => 'farewell'],
            ]);

            $conversation->update(['last_message_at' => now()]);
        } catch (Throwable $e) {
            Log::warning('SendFarewellOnConversationClosed: failed to send farewell', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(ConversationClosed $event, Throwable $exception): void
    {
        Log::error('SendFarewellOnConversationClosed failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
