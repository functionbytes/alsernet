<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;

class ProcessSocialWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'helpdesk-webhooks';

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
    ) {}

    public function middleware(): array
    {
        $messageId = $this->event['message_id'] ?? $this->event['mid'] ?? uniqid();

        return [(new WithoutOverlapping("webhook-{$this->channel}-{$messageId}"))->dontRelease()];
    }

    public function handle(WhatsAppBusinessService $whatsappService, FacebookMessengerService $facebookService): void
    {
        match ($this->channel) {
            'whatsapp' => $this->processWhatsApp($whatsappService),
            'facebook' => $this->processFacebook($facebookService),
            'instagram' => $this->processInstagram(),
            default => Log::warning("Unknown webhook channel: {$this->channel}"),
        };
    }

    // ─── WhatsApp ─────────────────────────────────────────────────────────────

    private function processWhatsApp(WhatsAppBusinessService $whatsappService): void
    {
        if ($this->eventType !== 'message') {
            return;
        }

        $event = $this->event;

        try {
            $customer = Customer::firstOrCreate(
                ['whatsapp_phone' => $event['phone']],
                ['name' => $event['name'], 'whatsapp_phone' => $event['phone']],
            );

            $conversation = $this->findOrCreateConversation('whatsapp', $event['phone'], $customer->id);

            if ($this->isDuplicate($conversation->id, $event['message_id'])) {
                return;
            }

            [$body, $attachments] = $this->buildWhatsAppBodyAndAttachments($event);

            $item = ConversationItem::create([
                'conversation_id' => $conversation->id,
                'author_id' => $customer->id,
                'type' => 'message',
                'body' => $body,
                'external_id' => $event['message_id'],
                'metadata' => array_merge(
                    $this->buildWhatsAppMetadata($event),
                    $attachments ? ['attachments' => $attachments] : []
                ),
            ]);

            event(new ConversationMessageCreated($item));

            $whatsappService->markAsRead($event['message_id']);

        } catch (\Throwable $e) {
            Log::error('ProcessSocialWebhookJob: WhatsApp failed', [
                'phone' => $event['phone'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─── Facebook ─────────────────────────────────────────────────────────────

    private function processFacebook(FacebookMessengerService $facebookService): void
    {
        $event = $this->event;

        try {
            $profile = $facebookService->getUserProfile($event['psid']);
            $name = filled($profile['name'] ?? null) ? $profile['name'] : $event['psid'];

            $customer = Customer::firstOrCreate(
                ['facebook_psid' => $event['psid']],
                ['name' => $name, 'facebook_psid' => $event['psid']],
            );

            if ($profile && $customer->name === $event['psid'] && filled($profile['name'])) {
                $customer->update(['name' => $profile['name']]);
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
                'metadata' => array_filter([
                    'attachments' => $downloadedAttachments ?: null,
                    'quick_reply' => $event['quick_reply'] ?? null,
                    'postback' => $this->eventType === 'postback' ? ($event['payload'] ?? null) : null,
                    'platform' => 'facebook',
                ]),
            ]);

            event(new ConversationMessageCreated($item));

        } catch (\Throwable $e) {
            Log::error('ProcessSocialWebhookJob: Facebook failed', [
                'psid' => $event['psid'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─── Instagram ────────────────────────────────────────────────────────────

    private function processInstagram(): void
    {
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
                'metadata' => array_filter([
                    'attachments' => $downloadedAttachments ?: null,
                    'is_ephemeral' => $event['is_ephemeral'] ?? null,
                    'story_url' => $event['story_url'] ?? null,
                    'platform' => 'instagram',
                ]),
            ]);

            event(new ConversationMessageCreated($item));

        } catch (\Throwable $e) {
            Log::error('ProcessSocialWebhookJob: Instagram failed', [
                'ig_user_id' => $event['ig_user_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

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

        $statusId = ConversationStatus::query()
            ->where('is_default', true)
            ->orWhere('is_open', true)
            ->orderByDesc('is_default')
            ->value('id') ?? 1;

        return Conversation::create([
            'customer_id' => $customerId,
            'channel' => $channel,
            'external_sender_id' => $externalSenderId,
            'status_id' => $statusId,
        ]);
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
    private function buildWhatsAppBodyAndAttachments(array $event): array
    {
        if (filled($event['body'] ?? null)) {
            return [$event['body'], []];
        }

        $messageType = $event['message_type'] ?? 'text';
        $mediaId = $event['media_id'] ?? null;
        $caption = filled($event['caption'] ?? null) ? $event['caption'] : '';

        if ($mediaId && in_array($messageType, ['image', 'audio', 'video', 'document', 'sticker'])) {
            $downloadUrl = $this->resolveWhatsAppMediaUrl($mediaId);

            if ($downloadUrl) {
                $path = $this->downloadMedia($downloadUrl, $messageType, 'whatsapp');

                if ($path) {
                    $attachment = ['type' => $messageType, 'path' => $path, 'original_url' => $downloadUrl];
                    $body = $caption ? "[{$messageType}]: {$caption}" : "[{$messageType}]";

                    return [$body, [$attachment]];
                }
            }
        }

        $body = match ($messageType) {
            'image' => $caption ? "[imagen]: {$caption}" : '[imagen]',
            'document' => '[documento'.($event['filename'] ? ": {$event['filename']}" : '').']',
            'audio' => '[audio]',
            'video' => $caption ? "[video]: {$caption}" : '[video]',
            default => '[mensaje]',
        };

        return [$body, []];
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
     * @return array{string, array<int, array{type: string, path: string, original_url: string}>}
     */
    private function resolveBodyAndAttachments(?string $text, array $rawAttachments, string $platform, string $fallbackBody): array
    {
        if (filled($text)) {
            return [$text, []];
        }

        if (empty($rawAttachments)) {
            return [$fallbackBody, []];
        }

        $attachments = [];
        $labels = [];

        foreach ($rawAttachments as $attachment) {
            $type = $attachment['type'] ?? 'file';
            $url = $attachment['url'] ?? $attachment['payload']['url'] ?? null;

            if ($url) {
                $path = $this->downloadMedia($url, $type, $platform);

                if ($path) {
                    $attachments[] = ['type' => $type, 'path' => $path, 'original_url' => $url];
                }
            }

            $labels[] = "[{$type}]";
        }

        $body = $labels ? implode(' ', $labels) : $fallbackBody;

        return [$body, $attachments];
    }

    /**
     * Download a media file from the given URL and store it on disk.
     * Returns the stored file path on success, or null on failure.
     */
    private function downloadMedia(string $url, string $mediaType, string $platform): ?string
    {
        try {
            $extension = match ($mediaType) {
                'image' => 'jpg',
                'audio' => 'ogg',
                'video' => 'mp4',
                'document' => 'pdf',
                'sticker' => 'webp',
                default => 'bin',
            };

            $filename = 'helpdesk/social-media/'.$platform.'/'.uniqid().'.'.$extension;
            $disk = config('helpdesk.attachments.disk', 'local');

            $response = Http::timeout(30)->get($url);

            if ($response->failed()) {
                Log::warning("Failed to download media from {$platform}", ['url' => $url, 'status' => $response->status()]);

                return null;
            }

            Storage::disk($disk)->put($filename, $response->body());

            return $filename;
        } catch (\Throwable $e) {
            Log::error("Media download failed for {$platform}", ['error' => $e->getMessage()]);

            return null;
        }
    }
}
