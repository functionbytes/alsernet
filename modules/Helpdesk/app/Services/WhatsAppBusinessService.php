<?php

namespace Modules\Helpdesk\Services;

use App\Services\CircuitBreaker;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppBusinessService
{
    private string $apiUrl;

    private string $phoneNumberId;

    private string $accessToken;

    private string $apiVersion;

    private CircuitBreaker $circuitBreaker;

    public function __construct()
    {
        $this->apiVersion = 'v19.0';
        $this->apiUrl = config('helpdesk.integrations.whatsapp.api_url', 'https://graph.facebook.com/v19.0');
        $this->phoneNumberId = config('helpdesk.integrations.whatsapp.phone_number_id', '');
        $this->accessToken = config('helpdesk.integrations.whatsapp.access_token', '');
        $this->circuitBreaker = new CircuitBreaker('whatsapp', 5, 60);
    }

    public function isEnabled(): bool
    {
        return config('helpdesk.integrations.whatsapp.enabled', false)
            && filled($this->phoneNumberId)
            && filled($this->accessToken);
    }

    // ─── Sending Messages ─────────────────────────────────────────────────────

    /**
     * Send a plain text message.
     */
    public function sendText(string $to, string $body, bool $previewUrl = false): ?string
    {
        return $this->send($to, [
            'type' => 'text',
            'text' => [
                'preview_url' => $previewUrl,
                'body' => $body,
            ],
        ]);
    }

    /**
     * Send an image by URL or WhatsApp media ID.
     */
    public function sendImage(string $to, string $urlOrId, ?string $caption = null): ?string
    {
        $payload = $this->isMediaId($urlOrId) ? ['id' => $urlOrId] : ['link' => $urlOrId];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        return $this->send($to, ['type' => 'image', 'image' => $payload]);
    }

    /**
     * Send a document by URL or WhatsApp media ID.
     */
    public function sendDocument(string $to, string $urlOrId, ?string $filename = null, ?string $caption = null): ?string
    {
        $payload = $this->isMediaId($urlOrId) ? ['id' => $urlOrId] : ['link' => $urlOrId];

        if ($filename !== null) {
            $payload['filename'] = $filename;
        }
        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        return $this->send($to, ['type' => 'document', 'document' => $payload]);
    }

    /**
     * Send an audio file by URL or WhatsApp media ID.
     */
    public function sendAudio(string $to, string $urlOrId): ?string
    {
        $payload = $this->isMediaId($urlOrId) ? ['id' => $urlOrId] : ['link' => $urlOrId];

        return $this->send($to, ['type' => 'audio', 'audio' => $payload]);
    }

    /**
     * Send a video by URL or WhatsApp media ID.
     */
    public function sendVideo(string $to, string $urlOrId, ?string $caption = null): ?string
    {
        $payload = $this->isMediaId($urlOrId) ? ['id' => $urlOrId] : ['link' => $urlOrId];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        return $this->send($to, ['type' => 'video', 'video' => $payload]);
    }

    /**
     * Send interactive reply buttons (max 3 buttons).
     *
     * @param  array<int, array{id: string, title: string}>  $buttons
     */
    public function sendButtons(string $to, string $bodyText, array $buttons): ?string
    {
        return $this->send($to, [
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $bodyText],
                'action' => [
                    'buttons' => array_map(fn ($b) => [
                        'type' => 'reply',
                        'reply' => ['id' => $b['id'], 'title' => $b['title']],
                    ], array_slice($buttons, 0, 3)),
                ],
            ],
        ]);
    }

    /**
     * Send a template message (required for first contact outside 24h window).
     *
     * @param  array<int, array{type: string, text: string}>  $components
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode = 'es', array $components = []): ?string
    {
        $template = [
            'name' => $templateName,
            'language' => ['code' => $languageCode],
        ];

        if (! empty($components)) {
            $template['components'] = $components;
        }

        return $this->send($to, ['type' => 'template', 'template' => $template]);
    }

    /**
     * Mark an incoming message as read (sends read receipt).
     */
    public function markAsRead(string $messageId): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if (! $this->circuitBreaker->isAvailable()) {
            Log::warning('WhatsApp circuit breaker is open — markAsRead skipped', ['message_id' => $messageId]);

            return false;
        }

        try {
            $response = $this->client()->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId,
            ]);

            if ($response->successful()) {
                $this->circuitBreaker->recordSuccess();

                return true;
            }

            $this->circuitBreaker->recordFailure();

            return false;
        } catch (\Throwable $e) {
            $this->circuitBreaker->recordFailure();
            Log::warning('WhatsApp markAsRead failed', ['message_id' => $messageId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    // ─── Webhook ──────────────────────────────────────────────────────────────

    /**
     * Verify the X-Hub-Signature-256 header on incoming webhook requests.
     * Uses the WhatsApp app secret (same Facebook App Secret used by Meta).
     * Returns true when no secret is configured (development mode).
     */
    public function verifySignature(string $rawBody, string $signatureHeader): bool
    {
        $appSecret = config('helpdesk.integrations.whatsapp.app_secret', '');

        if (! filled($appSecret)) {
            return true;
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $signatureHeader);
    }

    /**
     * Verify a Meta webhook challenge request.
     * Returns the challenge string on success, false on failure.
     */
    public function verifyWebhook(string $mode, string $challenge, string $verifyToken): string|false
    {
        $expected = config('helpdesk.integrations.whatsapp.verify_token', '');

        if ($mode === 'subscribe' && filled($expected) && hash_equals($expected, $verifyToken)) {
            return $challenge;
        }

        return false;
    }

    /**
     * Parse a WhatsApp webhook payload.
     *
     * Returns an array of events. Each event has a 'type' key:
     *   - 'message'  → incoming message from customer
     *   - 'status'   → delivery/read status update
     *
     * Message event keys:
     *   phone, name, message_id, timestamp, message_type (text|image|document|audio|video|interactive),
     *   body (text content), media_id (for media types), caption, filename, mime_type
     *
     * Status event keys:
     *   message_id, status (sent|delivered|read|failed), recipient_id, timestamp
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseWebhookPayload(array $payload): array
    {
        $events = [];

        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            return $events;
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                if (($value['messaging_product'] ?? '') !== 'whatsapp') {
                    continue;
                }

                // Incoming messages
                foreach ($value['messages'] ?? [] as $msg) {
                    $contact = collect($value['contacts'] ?? [])
                        ->firstWhere('wa_id', $msg['from']);

                    $event = [
                        'type' => 'message',
                        'phone' => $msg['from'],
                        'name' => $contact['profile']['name'] ?? $msg['from'],
                        'message_id' => $msg['id'],
                        'timestamp' => (int) $msg['timestamp'],
                        'message_type' => $msg['type'],
                        'body' => null,
                        'media_id' => null,
                        'caption' => null,
                        'filename' => null,
                        'mime_type' => null,
                    ];

                    match ($msg['type']) {
                        'text' => $event['body'] = $msg['text']['body'] ?? null,
                        'image' => $this->fillMediaEvent($event, $msg['image'] ?? []),
                        'document' => $this->fillMediaEvent($event, $msg['document'] ?? [], withFilename: true),
                        'audio' => $this->fillMediaEvent($event, $msg['audio'] ?? []),
                        'video' => $this->fillMediaEvent($event, $msg['video'] ?? []),
                        'interactive' => $event['body'] = $this->parseInteractiveReply($msg['interactive'] ?? []),
                        default => $event['body'] = "[{$msg['type']}]",
                    };

                    $events[] = $event;
                }

                // Status updates (delivered, read, failed)
                foreach ($value['statuses'] ?? [] as $status) {
                    $events[] = [
                        'type' => 'status',
                        'message_id' => $status['id'],
                        'status' => $status['status'],       // sent|delivered|read|failed
                        'recipient_id' => $status['recipient_id'],
                        'timestamp' => (int) $status['timestamp'],
                        'errors' => $status['errors'] ?? [],
                    ];
                }
            }
        }

        return $events;
    }

    // ─── Media ────────────────────────────────────────────────────────────────

    /**
     * Resolve a WhatsApp media ID to a temporary download URL.
     * The URL returned is valid for approximately 5 minutes.
     *
     * @return string|null The download URL, or null on failure
     */
    public function getMediaUrl(string $mediaId): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $response = $this->client()
                ->get("{$this->apiUrl}/{$mediaId}");

            if (! $response->successful()) {
                Log::warning('WhatsApp getMediaUrl failed', [
                    'media_id' => $mediaId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->json('url');
        } catch (\Throwable $e) {
            Log::error('WhatsApp getMediaUrl exception', ['media_id' => $mediaId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Download a WhatsApp media file and return its binary contents.
     * Use getMediaUrl() first to get the URL, then fetch its contents.
     *
     * @return string|null Binary file contents, or null on failure
     */
    public function downloadMedia(string $mediaId): ?string
    {
        $url = $this->getMediaUrl($mediaId);

        if (! $url) {
            return null;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(30)
                ->get($url);

            if (! $response->successful()) {
                Log::warning('WhatsApp downloadMedia failed', ['media_id' => $mediaId]);

                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::error('WhatsApp downloadMedia exception', ['media_id' => $mediaId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    // ─── Internals ────────────────────────────────────────────────────────────

    /**
     * Core send method. Returns the WhatsApp message ID (wamid) or null on failure.
     */
    private function send(string $to, array $messageFields): ?string
    {
        if (! $this->isEnabled()) {
            Log::debug('WhatsApp not enabled — message not sent', ['to' => $to]);

            return null;
        }

        if (! $this->circuitBreaker->isAvailable()) {
            Log::warning('WhatsApp circuit breaker is open — send skipped', ['to' => $to]);

            return null;
        }

        $body = array_merge([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
        ], $messageFields);

        try {
            $response = $this->client()->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $body);

            if (! $response->successful()) {
                $this->circuitBreaker->recordFailure();
                $this->logApiError('send', $response->json(), $to);

                return null;
            }

            $this->circuitBreaker->recordSuccess();

            return $response->json('messages.0.id');
        } catch (\Throwable $e) {
            $this->circuitBreaker->recordFailure();
            Log::error('WhatsApp send exception', ['to' => $to, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->accessToken)
            ->timeout(15)
            ->retry(2, 500, fn (\Exception $e) => $e instanceof RequestException && $e->response?->status() >= 500);
    }

    private function isMediaId(string $value): bool
    {
        // WhatsApp media IDs are numeric strings; URLs start with http
        return ctype_digit($value);
    }

    /** @param array<string, mixed> $event */
    private function fillMediaEvent(array &$event, array $media, bool $withFilename = false): void
    {
        $event['media_id'] = $media['id'] ?? null;
        $event['caption'] = $media['caption'] ?? null;
        $event['mime_type'] = $media['mime_type'] ?? null;

        if ($withFilename) {
            $event['filename'] = $media['filename'] ?? null;
        }
    }

    private function parseInteractiveReply(array $interactive): string
    {
        $type = $interactive['type'] ?? '';

        return match ($type) {
            'button_reply' => $interactive['button_reply']['title'] ?? '[button]',
            'list_reply' => $interactive['list_reply']['title'] ?? '[list item]',
            default => '[interactive]',
        };
    }

    private function logApiError(string $action, ?array $body, string $recipient): void
    {
        $code = $body['error']['code'] ?? 'unknown';
        $message = $body['error']['message'] ?? 'unknown error';

        Log::error("WhatsApp API error on {$action}", [
            'recipient' => $recipient,
            'code' => $code,
            'message' => $message,
            'body' => $body,
        ]);
    }
}
