<?php

namespace Modules\Helpdesk\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Models\OffHoursResponse;
use Modules\Helpdesk\Services\BusinessHoursService;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\OutboundMessageService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;

class ProcessSocialWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

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
        WhatsAppBusinessService $whatsappService,
        FacebookMessengerService $facebookService,
        OutboundMessageService $outboundService,
        BusinessHoursService $businessHoursService,
    ): void {
        // Status events (read/delivery/reaction) are channel-agnostic.
        if (in_array($this->eventType, ['read', 'delivery', 'reaction'], true)) {
            $this->processStatusEvent();

            return;
        }

        match ($this->channel) {
            'whatsapp' => $this->processWhatsApp($whatsappService, $outboundService, $businessHoursService),
            'facebook' => $this->processFacebook($facebookService, $outboundService, $businessHoursService),
            'instagram' => $this->processInstagram($outboundService, $businessHoursService),
            default => Log::warning("Unknown webhook channel: {$this->channel}"),
        };
    }

    /**
     * Mark agent-sent items as delivered/read or attach reactions, then
     * broadcast the change so the thread updates the receipt UI live.
     */
    private function processStatusEvent(): void
    {
        $event = $this->event;
        $externalSenderId = $event['psid'] ?? $event['ig_user_id'] ?? null;
        if (! $externalSenderId) {
            return;
        }

        $conversation = Conversation::query()
            ->where('channel', $this->channel)
            ->where('external_sender_id', $externalSenderId)
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

            broadcast(new ConversationMessageCreated($item, false));

            return;
        }

        // For read / delivery: mark all our outbound items up to the watermark.
        $field = $this->eventType === 'read' ? 'customer_read_at' : 'customer_delivered_at';
        $now = now()->toIso8601String();

        $items = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->whereNotNull('user_id')
            ->when($watermark, fn ($q) => $q->where('created_at', '<=', Carbon::createFromTimestampMs($watermark)))
            ->get();

        foreach ($items as $item) {
            $meta = is_array($item->metadata) ? $item->metadata : [];
            if (isset($meta[$field])) {
                continue;
            }
            $meta[$field] = $now;
            $item->metadata = $meta;
            $item->save();

            broadcast(new ConversationMessageCreated($item, false));
        }
    }

    // ─── WhatsApp ─────────────────────────────────────────────────────────────

    private function processWhatsApp(
        WhatsAppBusinessService $whatsappService,
        OutboundMessageService $outboundService,
        BusinessHoursService $businessHoursService,
    ): void {
        if ($this->eventType !== 'message') {
            return;
        }

        $event = $this->event;

        try {
            $customer = Customer::firstOrCreate(
                ['whatsapp_phone' => $event['phone']],
                ['name' => $event['name'], 'whatsapp_phone' => $event['phone'], 'email' => null],
            );

            $conversation = $this->findOrCreateConversation('whatsapp', $event['phone'], $customer->id);

            if ($this->isDuplicate($conversation->id, $event['message_id'])) {
                return;
            }

            // Build body label only — media downloads happen in the background.
            [$body, $pendingAttachment] = $this->buildWhatsAppBody($event);

            $item = ConversationItem::create([
                'conversation_id' => $conversation->id,
                'author_id' => $customer->id,
                'type' => 'message',
                'body' => $body,
                'external_id' => $event['message_id'],
                'attachment_urls' => [],
                'metadata' => $this->buildWhatsAppMetadata($event),
            ]);

            broadcast(new ConversationMessageCreated($item, $conversation->wasRecentlyCreated));

            if ($conversation->wasRecentlyCreated) {
                ConversationCreated::dispatch($conversation);
                $this->handleNewConversationAutomations($conversation, $item, $outboundService, $businessHoursService);
            }

            $whatsappService->markAsRead($event['message_id']);

            // Hand off media resolution + download to the background job.
            if ($pendingAttachment) {
                DownloadConversationAttachmentsJob::dispatch(
                    $item->id,
                    [$pendingAttachment],
                    'whatsapp',
                );
            }

        } catch (\Throwable $e) {
            Log::error('ProcessSocialWebhookJob: WhatsApp failed', [
                'phone' => $event['phone'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─── Facebook ─────────────────────────────────────────────────────────────

    private function processFacebook(
        FacebookMessengerService $facebookService,
        OutboundMessageService $outboundService,
        BusinessHoursService $businessHoursService,
    ): void {
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

            $conversation = $this->findOrCreateConversation('facebook', $event['psid'], $customer->id);

            if (isset($event['message_id']) && $this->isDuplicate($conversation->id, $event['message_id'])) {
                return;
            }

            $rawAttachments = $event['attachments'] ?? [];
            [$body, $downloadedAttachments] = $this->resolveBodyAndAttachments(
                $event['body'] ?? null,
                $rawAttachments,
                'facebook',
                $this->eventType === 'postback' ? ($event['title'] ?? '[postback]') : '[mensaje]'
            );

            $item = ConversationItem::create([
                'conversation_id' => $conversation->id,
                'author_id' => $customer->id,
                'type' => 'message',
                'body' => $body,
                'external_id' => $event['message_id'] ?? null,
                'attachment_urls' => $this->toAttachmentUrls($downloadedAttachments),
                'metadata' => array_filter([
                    'attachments' => $downloadedAttachments ?: null,
                    'quick_reply' => $event['quick_reply'] ?? null,
                    'postback' => $this->eventType === 'postback' ? ($event['payload'] ?? null) : null,
                    'platform' => 'facebook',
                ]),
            ]);

            broadcast(new ConversationMessageCreated($item, $conversation->wasRecentlyCreated));

            if ($conversation->wasRecentlyCreated) {
                ConversationCreated::dispatch($conversation);
                $this->handleNewConversationAutomations($conversation, $item, $outboundService, $businessHoursService);
            }

            if ($downloadedAttachments) {
                DownloadConversationAttachmentsJob::dispatch($item->id, $downloadedAttachments, 'facebook');
            }

        } catch (\Throwable $e) {
            Log::error('ProcessSocialWebhookJob: Facebook failed', [
                'psid' => $event['psid'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─── Instagram ────────────────────────────────────────────────────────────

    private function processInstagram(
        OutboundMessageService $outboundService,
        BusinessHoursService $businessHoursService,
    ): void {
        $event = $this->event;

        try {
            $customer = Customer::firstOrCreate(
                ['instagram_id' => $event['ig_user_id']],
                ['name' => $event['ig_user_id'], 'instagram_id' => $event['ig_user_id']],
            );

            $conversation = $this->findOrCreateConversation('instagram', $event['ig_user_id'], $customer->id);

            if ($this->isDuplicate($conversation->id, $event['message_id'])) {
                return;
            }

            [$body, $downloadedAttachments] = $this->resolveBodyAndAttachments(
                filled($event['body'] ?? null) ? $event['body'] : null,
                $event['attachments'] ?? [],
                'instagram',
                '[media]'
            );

            $item = ConversationItem::create([
                'conversation_id' => $conversation->id,
                'author_id' => $customer->id,
                'type' => 'message',
                'body' => $body,
                'external_id' => $event['message_id'],
                'attachment_urls' => $this->toAttachmentUrls($downloadedAttachments),
                'metadata' => array_filter([
                    'attachments' => $downloadedAttachments ?: null,
                    'is_ephemeral' => $event['is_ephemeral'] ?? null,
                    'story_url' => $event['story_url'] ?? null,
                    'platform' => 'instagram',
                ]),
            ]);

            broadcast(new ConversationMessageCreated($item, $conversation->wasRecentlyCreated));

            if ($conversation->wasRecentlyCreated) {
                ConversationCreated::dispatch($conversation);
                $this->handleNewConversationAutomations($conversation, $item, $outboundService, $businessHoursService);
            }

            if ($downloadedAttachments) {
                DownloadConversationAttachmentsJob::dispatch($item->id, $downloadedAttachments, 'instagram');
            }

        } catch (\Throwable $e) {
            Log::error('ProcessSocialWebhookJob: Instagram failed', [
                'ig_user_id' => $event['ig_user_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Run post-creation automations for new conversations:
     * 1. Send off-hours auto-reply when business is closed.
     *
     * Routing/auto-assignment is no longer triggered here: the
     * ConversationCreated event dispatched by the caller drives it via the
     * AutoAssignNewConversation listener (gated by config), avoiding double
     * routing.
     */
    private function handleNewConversationAutomations(
        Conversation $conversation,
        ConversationItem $item,
        OutboundMessageService $outboundService,
        BusinessHoursService $businessHoursService,
    ): void {
        if ($businessHoursService->isOpenNow()) {
            return;
        }

        try {
            $response = OffHoursResponse::findForChannel($conversation->channel);

            if ($response) {
                $outboundService->sendReply($conversation, $response->message);
            }
        } catch (\Throwable $e) {
            Log::warning('ProcessSocialWebhookJob: off-hours reply failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function findOrCreateConversation(string $channel, string $externalSenderId, int $customerId): Conversation
    {
        $existing = Conversation::query()
            ->where('channel', $channel)
            ->where('external_sender_id', $externalSenderId)
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

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

        $conversation = Conversation::create([
            'customer_id' => $customerId,
            'channel' => $channel,
            'external_sender_id' => $externalSenderId,
            'inbox_id' => $inboxId,
            'status_id' => $statusId,
        ]);

        return $conversation;
    }

    private function isDuplicate(int $conversationId, ?string $externalId): bool
    {
        if (blank($externalId)) {
            return false;
        }

        return ConversationItem::query()
            ->where('conversation_id', $conversationId)
            ->where('external_id', $externalId)
            ->exists();
    }

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
     * Resolve a WhatsApp media ID to its actual download URL via the Graph API.
     */
    private function resolveWhatsAppMediaUrl(string $mediaId): ?string
    {
        $token = config('helpdesk.integrations.whatsapp.access_token');
        $apiUrl = config('helpdesk.integrations.whatsapp.api_url', 'https://graph.facebook.com/v19.0');

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$apiUrl}/{$mediaId}");

            if ($response->failed()) {
                Log::warning('Failed to resolve WhatsApp media URL', ['media_id' => $mediaId, 'status' => $response->status()]);

                return null;
            }

            return $response->json('url');
        } catch (\Throwable $e) {
            Log::error('WhatsApp media URL resolution failed', ['media_id' => $mediaId, 'error' => $e->getMessage()]);

            return null;
        }
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

            if ($url) {
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

    private function labelForType(string $type): string
    {
        return match ($type) {
            'image' => '[imagen]',
            'audio' => '[audio]',
            'video' => '[video]',
            'document', 'file' => '[documento]',
            'sticker' => '[sticker]',
            'location' => '[ubicación]',
            'contact' => '[contacto]',
            'template' => '[plantilla]',
            default => "[{$type}]",
        };
    }

    /**
     * Download a media file from the given URL and store it on disk.
     * Detects the real mime type from response headers to pick the correct extension.
     *
     * @return array{path: string, url: string, name: string, mime_type: ?string, size: int}|null
     */
    private function downloadMedia(string $url, string $mediaType, string $platform, ?string $bearerToken = null): ?array
    {
        try {
            $request = Http::timeout(30);

            if (filled($bearerToken)) {
                $request = $request->withToken($bearerToken);
            }

            $response = $request->get($url);

            if ($response->failed()) {
                Log::warning("Failed to download media from {$platform}", ['url' => $url, 'status' => $response->status()]);

                return null;
            }

            $body = $response->body();
            $mime = $response->header('Content-Type') ?: null;
            $extension = $this->extensionFromMime($mime, $mediaType);
            $name = $platform.'_'.$mediaType.'_'.date('Ymd_His').'.'.$extension;
            $filename = 'helpdesk/social-media/'.$platform.'/'.date('Y/m/d').'/'.uniqid('', true).'.'.$extension;

            $disk = config('helpdesk.attachments.disk', 'public');
            Storage::disk($disk)->put($filename, $body);

            try {
                $publicUrl = Storage::disk($disk)->url($filename);
            } catch (\Throwable) {
                $publicUrl = $filename;
            }

            return [
                'path' => $filename,
                'url' => $publicUrl,
                'name' => $name,
                'mime_type' => $mime,
                'size' => strlen($body),
            ];
        } catch (\Throwable $e) {
            Log::error("Media download failed for {$platform}", ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Determine the file extension from the Content-Type header, falling back to
     * a sensible default based on the declared media type.
     */
    private function extensionFromMime(?string $mime, string $fallbackType): string
    {
        $mime = $mime ? strtolower(explode(';', $mime, 2)[0]) : '';
        $mime = trim($mime);

        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'audio/mpeg' => 'mp3',
            'audio/mp3' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/opus' => 'opus',
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
            'audio/aac' => 'aac',
            'audio/mp4' => 'm4a',
            'audio/x-m4a' => 'm4a',
            'audio/webm' => 'webm',
            'audio/3gpp' => '3gp',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
            'video/3gpp' => '3gp',
            'video/x-matroska' => 'mkv',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
        ];

        if (isset($map[$mime])) {
            return $map[$mime];
        }

        return match ($fallbackType) {
            'image' => 'jpg',
            'audio', 'voice' => 'ogg',
            'video' => 'mp4',
            'document', 'file' => 'bin',
            'sticker' => 'webp',
            default => 'bin',
        };
    }
}
