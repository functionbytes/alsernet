<?php

namespace Modules\Helpdesk\Jobs;

use App\Helpers\PiiMasker;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\ConversationReceiptsUpdated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\Webhooks\InboundMessageIngestor;
use Modules\Helpdesk\Support\OutboundMediaUrlGuard;

class ProcessSocialWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    /**
     * Tope de ítems marcados como entregados/leídos por evento de recibo. Un
     * watermark de Messenger/Instagram normalmente cubre unos pocos mensajes
     * salientes; el límite evita un UPDATE desmedido si algo se acumula.
     */
    private const RECEIPT_BATCH_LIMIT = 500;

    /**
     * @param  string  $channel  'whatsapp'|'facebook'|'instagram'
     * @param  string  $eventType  'message'|'postback'|'story_reply'
     * @param  array<string, mixed>  $event  Parsed event from service->parseWebhookPayload()
     */
    public function __construct(
        public readonly string $channel,
        public readonly string $eventType,
        public readonly array $event,
    ) {
        $this->onQueue('helpdesk-webhooks');
    }

    public function middleware(): array
    {
        // Duplicate protection is handled at the application layer by
        // isDuplicate() which checks external_id; no queue lock needed.
        return [];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessSocialWebhookJob permanently failed', [
            'channel' => $this->channel,
            'event_type' => $this->eventType,
            'error' => $exception->getMessage(),
        ]);
    }

    public function handle(
        FacebookMessengerService $facebookService,
    ): void {
        // Status events (read/delivery/reaction) are channel-agnostic.
        if (in_array($this->eventType, ['read', 'delivery', 'reaction'], true)) {
            $this->processStatusEvent();

            return;
        }

        // El pipeline común de ingesta (conversación, dedup, ítem, broadcast,
        // automatizaciones, media) vive en InboundMessageIngestor; aquí solo queda
        // el parseo específico de cada canal.
        $ingestor = app(InboundMessageIngestor::class);

        match ($this->channel) {
            'whatsapp' => $this->processWhatsApp($ingestor),
            'facebook' => $this->processFacebook($ingestor, $facebookService),
            'instagram' => $this->processInstagram($ingestor),
            default => Log::warning("Unknown webhook channel: {$this->channel}"),
        };
    }

    /**
     * Emite el broadcast en tiempo real de forma resiliente: un fallo de Reverb
     * (p. ej. servidor caído) no debe abortar la recepción del mensaje ni sus
     * efectos posteriores (ConversationCreated, automatizaciones, descarga de media).
     */
    private function broadcastMessageSafely(ConversationItem $item, bool $isNewConversation): void
    {
        try {
            broadcast(new ConversationMessageCreated($item, $isNewConversation));
        } catch (\Throwable $e) {
            Log::warning('ProcessSocialWebhookJob: broadcast en tiempo real falló (mensaje ya persistido)', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * PERF-05: agregado — un único broadcast con los ids marcados en vez de
     * uno por ítem (cada uno con el payload completo de ConversationMessageCreated,
     * que hace loadMissing de conversación+cliente+autor+usuario).
     *
     * @param  array<int, int>  $itemIds
     */
    private function broadcastReceiptsUpdatedSafely(int $conversationId, string $field, array $itemIds, ?int $watermark): void
    {
        try {
            broadcast(new ConversationReceiptsUpdated($conversationId, $field, $itemIds, $watermark));
        } catch (\Throwable $e) {
            Log::warning('ProcessSocialWebhookJob: broadcast de recibos falló (metadata ya persistida)', [
                'conversation_id' => $conversationId,
                'item_ids' => $itemIds,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark agent-sent items as delivered/read or attach reactions, then
     * broadcast the change so the thread updates the receipt UI live.
     */
    private function processStatusEvent(): void
    {
        $event = $this->event;
        // WhatsApp identifica al cliente por 'recipient_id' (su teléfono); Messenger
        // por 'psid' e Instagram por 'ig_user_id'.
        $externalSenderId = $event['psid'] ?? $event['ig_user_id'] ?? $event['recipient_id'] ?? null;
        if (! $externalSenderId) {
            return;
        }

        // Alineado con InboundMessageIngestor::resolveConversation(): sin el
        // filtro de estado abierto ni el orden, un ->first() podía aplicar el
        // recibo a una conversación antigua y cerrada distinta de la que el
        // ingestor real usa para el mensaje saliente.
        $conversation = Conversation::query()
            ->where('channel', $this->channel)
            ->where('external_sender_id', $externalSenderId)
            ->open()
            ->latest()
            ->first();

        if (! $conversation) {
            return;
        }

        $watermark = $event['watermark'] ?? null;
        $messageId = $event['message_id'] ?? null;

        if ($this->eventType === 'reaction' && $messageId) {
            $item = ConversationItem::query()
                ->where('conversation_id', $conversation->id)
                ->where('external_id', $messageId)
                ->first();

            if (! $item) {
                return;
            }

            $reactions = is_array($item->metadata['customer_reactions'] ?? null) ? $item->metadata['customer_reactions'] : [];
            $action = $event['action'] ?? 'react';
            if ($action === 'unreact') {
                $reactions = [];
            } else {
                $reactions = [['emoji' => $event['emoji'] ?? '❤️', 'at' => now()->toIso8601String()]];
            }

            $meta = is_array($item->metadata) ? $item->metadata : [];
            $meta['customer_reactions'] = $reactions;
            $item->metadata = $meta;
            $item->save();

            $this->broadcastMessageSafely($item, false);

            return;
        }

        // Recibos de lectura/entrega: Messenger/Instagram entregan un watermark
        // (marca todos los ítems salientes hasta ese instante); WhatsApp entrega el
        // estado por mensaje individual (marca solo el ítem con ese external_id).
        $field = $this->eventType === 'read' ? 'customer_read_at' : 'customer_delivered_at';

        if (! $watermark && ! $messageId) {
            return;
        }

        // Idempotencia: si un reintento (o un duplicado del webhook de Meta) trae
        // un watermark ya cubierto por el último procesado, no hay nada nuevo que
        // marcar — nos ahorramos el escaneo de la tabla de ítems.
        $watermarkKey = "receipt_watermark_{$this->eventType}";
        $lastWatermark = $conversation->metadata[$watermarkKey] ?? null;

        if ($watermark && $lastWatermark !== null && $watermark <= $lastWatermark) {
            return;
        }

        $this->markItemsAsReceipted($conversation, $field, $watermark, $messageId);

        if ($watermark) {
            $conversation->forceFill([
                'metadata' => array_merge($conversation->metadata ?? [], [$watermarkKey => $watermark]),
            ])->save();
        }
    }

    /**
     * Marca en bloque los ítems salientes que aún no tienen `$field` en su
     * metadata y emite un único broadcast agregado con los ids afectados —
     * antes esto era un foreach que cargaba TODOS los ítems salientes de la
     * conversación (sin límite), filtraba los ya marcados en PHP y emitía un
     * broadcast con el payload completo del mensaje por cada uno.
     */
    private function markItemsAsReceipted(Conversation $conversation, string $field, ?int $watermark, ?string $messageId): void
    {
        $itemsQuery = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->whereNotNull('user_id')
            ->whereRaw("JSON_EXTRACT(metadata, '$.{$field}') IS NULL");

        if ($watermark) {
            $itemsQuery->where('created_at', '<=', Carbon::createFromTimestampMs($watermark));
        } else {
            $itemsQuery->where('external_id', $messageId);
        }

        $ids = $itemsQuery->orderByDesc('id')->limit(self::RECEIPT_BATCH_LIMIT)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $table = (new ConversationItem)->getTable();
        $now = now()->toIso8601String();

        ConversationItem::query()->getConnection()->update(
            "UPDATE `{$table}` SET metadata = JSON_SET(COALESCE(metadata, JSON_OBJECT()), ?, ?) WHERE id IN (".implode(',', array_fill(0, $ids->count(), '?')).')',
            array_merge(["$.{$field}"], [$now], $ids->all())
        );

        $this->broadcastReceiptsUpdatedSafely($conversation->id, $field, $ids->all(), $watermark);
    }

    // ─── WhatsApp ─────────────────────────────────────────────────────────────

    private function processWhatsApp(InboundMessageIngestor $ingestor): void
    {
        if ($this->eventType !== 'message') {
            return;
        }

        $event = $this->event;

        try {
            $customer = Customer::firstOrCreate(
                ['whatsapp_phone' => $event['phone']],
                ['name' => $event['name'], 'phone' => $event['phone'], 'whatsapp_phone' => $event['phone'], 'email' => null],
            );

            // Build body label only — media downloads happen in the background.
            [$body, $pendingAttachment] = $this->buildWhatsAppBody($event);

            $item = $ingestor->ingest('whatsapp', $event['phone'], $customer, [
                'body' => $body,
                'external_id' => $event['message_id'],
                'attachment_urls' => [],
                'metadata' => $this->buildWhatsAppMetadata($event),
            ], $pendingAttachment ? [$pendingAttachment] : []);

            if (! $item) {
                return; // duplicado
            }

            MarkWhatsAppMessageReadJob::dispatch($event['message_id']);

        } catch (\Throwable $e) {
            Log::error('ProcessSocialWebhookJob: WhatsApp failed', [
                'phone' => PiiMasker::phone($event['phone'] ?? null),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─── Facebook ─────────────────────────────────────────────────────────────

    private function processFacebook(InboundMessageIngestor $ingestor, FacebookMessengerService $facebookService): void
    {
        $event = $this->event;

        try {
            // Try to find existing customer first (avoids Graph API call entirely
            // for known PSIDs — the slowest step in the pipeline at 200-400ms).
            $customer = Customer::query()->where('facebook_psid', $event['psid'])->first();

            if (! $customer) {
                $profile = Cache::remember(
                    "fb_profile:{$event['psid']}",
                    now()->addHours(6),
                    fn () => $facebookService->getUserProfile($event['psid'])
                );

                $name = filled($profile['name'] ?? null) ? $profile['name'] : $event['psid'];

                $customer = Customer::firstOrCreate(
                    ['facebook_psid' => $event['psid']],
                    ['name' => $name, 'facebook_psid' => $event['psid']],
                );
            }

            [$body, $downloadedAttachments] = $this->resolveBodyAndAttachments(
                $event['body'] ?? null,
                $event['attachments'] ?? [],
                'facebook',
                $this->eventType === 'postback' ? ($event['title'] ?? '[postback]') : '[mensaje]'
            );

            $ingestor->ingest('facebook', $event['psid'], $customer, [
                'body' => $body,
                'external_id' => $event['message_id'] ?? null,
                'attachment_urls' => $this->toAttachmentUrls($downloadedAttachments),
                'metadata' => array_filter([
                    'attachments' => $downloadedAttachments ?: null,
                    'quick_reply' => $event['quick_reply'] ?? null,
                    'postback' => $this->eventType === 'postback' ? ($event['payload'] ?? null) : null,
                    'referral' => $event['referral'] ?? null,
                    'platform' => 'facebook',
                ]),
            ], $downloadedAttachments);

        } catch (\Throwable $e) {
            Log::error('ProcessSocialWebhookJob: Facebook failed', [
                'psid' => $event['psid'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─── Instagram ────────────────────────────────────────────────────────────

    private function processInstagram(InboundMessageIngestor $ingestor): void
    {
        $event = $this->event;

        try {
            $customer = Customer::firstOrCreate(
                ['instagram_id' => $event['ig_user_id']],
                ['name' => $event['ig_user_id'], 'instagram_id' => $event['ig_user_id']],
            );

            [$body, $downloadedAttachments] = $this->resolveBodyAndAttachments(
                filled($event['body'] ?? null) ? $event['body'] : null,
                $event['attachments'] ?? [],
                'instagram',
                '[media]'
            );

            $ingestor->ingest('instagram', $event['ig_user_id'], $customer, [
                'body' => $body,
                'external_id' => $event['message_id'],
                'attachment_urls' => $this->toAttachmentUrls($downloadedAttachments),
                'metadata' => array_filter([
                    'attachments' => $downloadedAttachments ?: null,
                    'is_ephemeral' => $event['is_ephemeral'] ?? null,
                    'story_url' => $event['story_url'] ?? null,
                    'platform' => 'instagram',
                ]),
            ], $downloadedAttachments);

        } catch (\Throwable $e) {
            Log::error('ProcessSocialWebhookJob: Instagram failed', [
                'ig_user_id' => $event['ig_user_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    // handleNewConversationAutomations(), findOrCreateConversation() e
    // isDuplicate() se movieron a InboundMessageIngestor (pipeline compartido de
    // ingesta usado también por los *MessageProcessor del simulador).

    /**
     * Build the display body and structured attachments for a WhatsApp message event.
     * Returns [$body, $attachments] where $attachments is an array for metadata.
     *
     * @return array{string, array<int, array{type: string, path: string, original_url: string}>}
     */
    /**
     * Build the body label for a WhatsApp message and a "pending" attachment
     * descriptor for the background download job (no HTTP calls done here).
     *
     * @return array{0: string, 1: array{type: string, media_id: string}|null}
     */
    private function buildWhatsAppBody(array $event): array
    {
        $messageType = $event['message_type'] ?? 'text';
        $mediaId = $event['media_id'] ?? null;
        $caption = filled($event['caption'] ?? null) ? $event['caption'] : '';
        $body = filled($event['body'] ?? null) ? $event['body'] : null;

        $pending = null;
        if ($mediaId && in_array($messageType, ['image', 'audio', 'voice', 'video', 'document', 'sticker'])) {
            $pending = [
                'type' => $messageType,
                'media_id' => $mediaId,
            ];
        }

        // Body = user-provided text/caption only. The media bubble renders
        // separately. No "[imagen]" or "[audio]" placeholders.
        if ($body !== null) {
            return [$body, $pending];
        }

        if (filled($caption)) {
            return [$caption, $pending];
        }

        if ($pending !== null) {
            return ['', $pending];
        }

        return ['[mensaje]', null];
    }

    /**
     * Build the metadata array for a WhatsApp message event.
     * Empty/null values are stripped to keep the JSON lean.
     */
    private function buildWhatsAppMetadata(array $event): array
    {
        return array_filter([
            'media_id' => $event['media_id'] ?? null,
            'mime_type' => $event['mime_type'] ?? null,
            'filename' => $event['filename'] ?? null,
            'caption' => $event['caption'] ?? null,
            'message_type' => $event['message_type'] ?? null,
            'referral' => $event['referral'] ?? null,
            'platform' => 'whatsapp',
        ]);
    }

    /**
     * Resolve body text and structured attachment metadata from a raw body and attachment list.
     * Downloads each attachment and stores its path in the returned attachments array.
     *
     * @param  array<int, array{type: string, url?: string}>  $rawAttachments
     * @return array{string, array<int, array{type: string, path: string, original_url: string, mime_type: ?string, size: ?int}>}
     */
    private function resolveBodyAndAttachments(?string $text, array $rawAttachments, string $platform, string $fallbackBody): array
    {
        // FAST PATH: do NOT download here — return the external CDN URL so the
        // broadcast happens immediately. DownloadConversationAttachmentsJob
        // replaces these URLs with locally-stored copies in the background.
        $attachments = [];

        foreach ($rawAttachments as $attachment) {
            $type = $attachment['type'] ?? 'file';
            $url = $attachment['url'] ?? $attachment['payload']['url'] ?? null;

            if ($url && OutboundMediaUrlGuard::isAllowed($url)) {
                $attachments[] = [
                    'type' => $type,
                    'original_url' => $url,
                    'url' => $url,
                    'name' => basename(parse_url($url, PHP_URL_PATH) ?: $url),
                    'mime_type' => null,
                    'size' => 0,
                ];
            }
        }

        // Body shows ONLY the user's text. Attachments render on their own
        // (image/audio/video bubble in the thread). If there's no text and no
        // attachments, fall back to the caller-provided placeholder.
        if (filled($text)) {
            return [$text, $attachments];
        }

        if ($attachments !== []) {
            return ['', $attachments];
        }

        return [$fallbackBody, $attachments];
    }

    /**
     * Convert internal attachment metadata into the {url, name, size, mime_type}
     * format expected by ConversationItem.attachment_urls (matches widget output).
     *
     * @param  array<int, array<string, mixed>>  $attachments
     * @return array<int, array{url: string, name: string, size: int, mime_type: string}>
     */
    private function toAttachmentUrls(array $attachments): array
    {
        $out = [];

        foreach ($attachments as $a) {
            $url = $a['url'] ?? null;

            if (! $url) {
                continue;
            }

            $out[] = [
                'url' => $url,
                'name' => $a['name'] ?? basename(parse_url($url, PHP_URL_PATH) ?: $url),
                'size' => (int) ($a['size'] ?? 0),
                'mime_type' => $a['mime_type'] ?? 'application/octet-stream',
            ];
        }

        return $out;
    }
}
