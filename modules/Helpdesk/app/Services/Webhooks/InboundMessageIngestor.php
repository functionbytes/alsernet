<?php

namespace Modules\Helpdesk\Services\Webhooks;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Jobs\DownloadConversationAttachmentsJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Services\BusinessHoursService;
use Modules\Helpdesk\Services\OffHoursAutoReplyService;
use Modules\Helpdesk\Support\ChannelMetrics;

/**
 * Pipeline compartido de ingesta de un mensaje ENTRANTE del cliente
 * (WhatsApp / Facebook / Instagram y el simulador): resuelve/crea la
 * conversación, deduplica por external_id, crea el ConversationItem, reabre la
 * ventana de servicio, emite el broadcast en tiempo real (resiliente), dispara
 * el evento de conversación nueva (auto-asignación, off-hours, workflows... via
 * ConversationCreated) y encola la descarga de adjuntos.
 *
 * La parte específica de cada canal (resolver el cliente y construir el cuerpo,
 * adjuntos y metadata del ítem) la hace quien llama; aquí vive lo común, que
 * antes estaba triplicado en ProcessSocialWebhookJob y en los *MessageProcessor.
 */
class InboundMessageIngestor
{
    /**
     * @param  array<string, mixed>  $itemAttributes  body, external_id, attachment_urls, metadata…
     * @param  array<int, array<string, mixed>>  $downloadAttachments  para DownloadConversationAttachmentsJob
     * @return ConversationItem|null null si el mensaje era un duplicado (ya ingerido)
     */
    public function ingest(
        string $channel,
        string $externalSenderId,
        Customer $customer,
        array $itemAttributes,
        array $downloadAttachments = [],
    ): ?ConversationItem {
        $conversation = $this->findOrCreateConversation($channel, $externalSenderId, $customer->id);

        $externalId = $itemAttributes['external_id'] ?? null;
        if ($externalId !== null && $this->isDuplicate($channel, $externalSenderId, $externalId)) {
            return null;
        }

        $item = ConversationItem::create(array_merge([
            'conversation_id' => $conversation->id,
            'author_id' => $customer->id,
            'type' => 'message',
        ], $itemAttributes));

        ChannelMetrics::increment('inbound', $channel);

        // La ventana de servicio (24h en WhatsApp) la reabre cualquier mensaje
        // entrante del cliente.
        $conversation->update(['last_customer_message_at' => now()]);

        $this->broadcastSafely($item, $conversation->wasRecentlyCreated);

        if ($conversation->wasRecentlyCreated) {
            $customer->incrementConversationCount();
            ConversationCreated::dispatch($conversation);
        } elseif (helpdesk_off_hours_feature_enabled() && ! app(BusinessHoursService::class)->isOpenNow()) {
            // Mensaje entrante en una conversación YA existente mientras
            // seguimos fuera de horario — antes esto no avisaba nunca (solo
            // ConversationCreated lo hacía, y eso solo dispara en la
            // primera). maybeReplyToExistingConversation() exige que la
            // conversación siga abierta y no repite si ya se avisó hace poco
            // (ver OffHoursAutoReplyService).
            app(OffHoursAutoReplyService::class)->maybeReplyToExistingConversation($conversation);
        }

        if ($downloadAttachments !== []) {
            DownloadConversationAttachmentsJob::dispatch($item->id, $downloadAttachments, $channel);
        }

        return $item;
    }

    /**
     * El find+create se serializa con un lock por remitente/canal: dos
     * reintentos del mismo webhook (Meta) o dos workers en paralelo podrían,
     * si no, pasar ambos el "no existe" y crear dos conversaciones para el
     * mismo cliente.
     */
    private function findOrCreateConversation(string $channel, string $externalSenderId, int $customerId): Conversation
    {
        return Cache::lock("helpdesk:ingest:{$channel}:{$externalSenderId}", 10)
            ->block(5, fn () => $this->resolveConversation($channel, $externalSenderId, $customerId));
    }

    private function resolveConversation(string $channel, string $externalSenderId, int $customerId): Conversation
    {
        // Abierta = estado abierto o sin estado (las creadas desde el panel
        // nacían sin estado, ver ConversationStatus::getDefault()). Con varias
        // abiertas se continúa la de actividad más reciente — p. ej. aquella
        // en la que el agente acaba de mandar una plantilla — y no la más
        // antigua.
        $existing = Conversation::query()
            ->where('channel', $channel)
            ->where('external_sender_id', $externalSenderId)
            ->where(fn ($q) => $q->whereNull('status_id')
                ->orWhereHas('status', fn ($s) => $s->where('is_open', true)))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Resuelta, cerrada o archivada: el agente ya la dio por terminada, así
        // que un mensaje nuevo del cliente abre una conversación nueva en vez
        // de reabrir aquella. (Se probó reabrir la última de las 24 h previas y
        // no es lo que se quiere: reabría la que se acababa de cerrar.)

        // Cache the default status ID and per-channel inbox ID for 30 minutes —
        // these almost never change and saved 2 DB queries per webhook.
        $statusId = Cache::remember(
            'helpdesk:default_status_id',
            now()->addMinutes(30),
            fn () => ConversationStatus::query()
                ->where('is_default', true)
                ->orWhere('is_open', true)
                ->orderByDesc('is_default')
                ->value('id') ?? 1,
        );

        $inboxId = Cache::remember(
            "helpdesk:inbox_id:{$channel}",
            now()->addMinutes(30),
            fn () => Inbox::query()
                ->where('channel_type', $channel)
                ->value('id'),
        );

        return Conversation::create([
            'customer_id' => $customerId,
            'channel' => $channel,
            'external_sender_id' => $externalSenderId,
            'inbox_id' => $inboxId,
            'status_id' => $statusId,
        ]);
    }

    /**
     * Busca el external_id en CUALQUIER conversación del mismo remitente/canal,
     * no solo en la ya resuelta: si el cliente tiene conversaciones cerradas y
     * abiertas, un reintento del webhook podría mapear a una conversación
     * distinta de aquella donde el mensaje ya se guardó.
     */
    private function isDuplicate(string $channel, string $externalSenderId, ?string $externalId): bool
    {
        if (blank($externalId)) {
            return false;
        }

        return ConversationItem::query()
            ->where('external_id', $externalId)
            ->whereHas('conversation', fn ($q) => $q
                ->where('channel', $channel)
                ->where('external_sender_id', $externalSenderId))
            ->exists();
    }

    /**
     * Broadcast resiliente: un fallo de Reverb no debe abortar la ingesta ni sus
     * efectos posteriores (el mensaje ya está persistido).
     */
    private function broadcastSafely(ConversationItem $item, bool $isNewConversation): void
    {
        try {
            broadcast(new ConversationMessageCreated($item, $isNewConversation));
        } catch (\Throwable $e) {
            Log::warning('InboundMessageIngestor: broadcast en tiempo real falló (mensaje ya persistido)', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
