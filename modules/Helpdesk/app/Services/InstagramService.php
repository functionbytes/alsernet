<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    private const API_VERSION = 'v19.0';

    // When using a Facebook Page Access Token with instagram_manage_messages scope,
    // the Send API endpoint lives under graph.facebook.com (not graph.instagram.com,
    // which requires a separate Instagram Login token).
    private const BASE_URL = 'https://graph.facebook.com';

    private string $accessToken;

    private string $businessAccountId;

    private string $appSecret;

    public function __construct()
    {
        $this->accessToken = config('helpdesk.integrations.instagram.access_token', '');
        $this->businessAccountId = config('helpdesk.integrations.instagram.business_account_id', '');
        $this->appSecret = config('helpdesk.integrations.facebook.app_secret', ''); // Shared
    }

    public function isEnabled(): bool
    {
        return config('helpdesk.integrations.instagram.enabled', false)
            && filled($this->accessToken)
            && filled($this->businessAccountId);
    }

    // ─── Sending ──────────────────────────────────────────────────────────────

    /**
     * Send a text message to an Instagram user (by IGSID).
     */
    public function sendText(string $igUserId, string $text): ?string
    {
        return $this->send($igUserId, ['text' => $text]);
    }

    /**
     * Send an image, video, audio or file attachment.
     *
     * @param  'image'|'video'|'audio'|'file'  $type
     */
    public function sendAttachment(string $igUserId, string $type, string $url): ?string
    {
        return $this->send($igUserId, [
            'attachment' => [
                'type' => $type,
                'payload' => ['url' => $url],
            ],
        ]);
    }

    // ─── Webhook ──────────────────────────────────────────────────────────────

    /**
     * Verify X-Hub-Signature-256 header.
     * Format: "sha256=<hex_hash>"
     */
    public function verifySignature(string $rawBody, string $signatureHeader): bool
    {
        if (! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $provided = substr($signatureHeader, 7);
        $expected = hash_hmac('sha256', $rawBody, $this->appSecret);

        return hash_equals($expected, $provided);
    }

    /**
     * Parse an Instagram webhook payload (object: "instagram").
     *
     * Returns array of events:
     *   type: 'message' | 'story_reply' | 'story_mention' | 'reaction' | 'delivery' | 'read' | 'deleted'
     *
     * Message event keys: ig_user_id, message_id, timestamp, body, attachments[], is_ephemeral
     * Story reply: ig_user_id, message_id, timestamp, body, story_url
     * Reaction: ig_user_id, message_id, action (react|unreact), emoji
     * Deleted: ig_user_id, message_id
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseWebhookPayload(array $payload): array
    {
        $events = [];

        if (($payload['object'] ?? '') !== 'instagram') {
            return $events;
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['messaging'] ?? [] as $messaging) {
                $igUserId = $messaging['sender']['id'];
                $timestamp = (int) ($messaging['timestamp'] ?? 0);

                // Skip echo messages
                if ($messaging['message']['is_echo'] ?? false) {
                    continue;
                }

                // Deleted message
                if ($messaging['message']['is_deleted'] ?? false) {
                    $events[] = [
                        'type' => 'deleted',
                        'ig_user_id' => $igUserId,
                        'message_id' => $messaging['message']['mid'],
                    ];

                    continue;
                }

                // Regular message
                if (isset($messaging['message'])) {
                    $msg = $messaging['message'];
                    $isEph = false;

                    // Check for ephemeral content (stories, etc.)
                    foreach ($msg['attachments'] ?? [] as $att) {
                        if ($att['payload']['is_ephemeral'] ?? false) {
                            $isEph = true;
                        }
                    }

                    $events[] = [
                        'type' => isset($messaging['referral']) ? 'story_reply' : 'message',
                        'ig_user_id' => $igUserId,
                        'message_id' => $msg['mid'],
                        'timestamp' => $timestamp,
                        'body' => $msg['text'] ?? null,
                        'attachments' => $this->parseAttachments($msg['attachments'] ?? []),
                        'is_ephemeral' => $isEph,
                        'story_url' => $messaging['referral']['url'] ?? null,
                    ];
                } elseif (isset($messaging['reaction'])) {
                    $r = $messaging['reaction'];
                    $events[] = [
                        'type' => 'reaction',
                        'ig_user_id' => $igUserId,
                        'message_id' => $r['mid'],
                        'action' => $r['action'],   // react|unreact
                        'emoji' => $r['emoji'] ?? null,
                    ];
                } elseif (isset($messaging['message_status'])) {
                    $s = $messaging['message_status'];
                    $events[] = [
                        'type' => $s['status'] === 'READ' ? 'read' : 'delivery',
                        'ig_user_id' => $igUserId,
                        'message_id' => $s['mid'],
                        'status' => $s['status'],
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Send a quick replies message (text + up to 13 reply buttons).
     *
     * @param  array<int, array{title: string, payload: string}>  $replies
     */
    public function sendQuickReplies(string $igUserId, string $text, array $replies): ?string
    {
        return $this->send($igUserId, [
            'text' => $text,
            'quick_replies' => array_map(fn ($r) => [
                'content_type' => 'text',
                'title' => $r['title'],
                'payload' => $r['payload'],
            ], array_slice($replies, 0, 13)),
        ]);
    }

    /**
     * Send sender_action (mark_seen / typing_on / typing_off) to the IG conversation.
     */
    public function sendSenderAction(string $igUserId, string $action = 'mark_seen'): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(8)
                ->post(self::BASE_URL.'/'.self::API_VERSION.'/me/messages', [
                    'recipient' => ['id' => $igUserId],
                    'sender_action' => $action,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Instagram sender_action failed', ['ig_user_id' => $igUserId, 'action' => $action, 'error' => $e->getMessage()]);

            return false;
        }
    }

    // ─── Internals ────────────────────────────────────────────────────────────

    private function send(string $igUserId, array $message, string $messagingType = 'RESPONSE'): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            // Instagram Messaging via Facebook Page Access Token uses /me/messages,
            // identical to Messenger. Recipient is the Instagram-Scoped User ID (IGSID).
            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->retry(2, 500)
                ->post(self::BASE_URL.'/'.self::API_VERSION.'/me/messages', [
                    'recipient' => ['id' => $igUserId],
                    'messaging_type' => $messagingType,
                    'message' => $message,
                ]);

            if (! $response->successful()) {
                Log::error('Instagram send failed', [
                    'ig_user_id' => $igUserId,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            return $response->json('message_id');
        } catch (\Throwable $e) {
            Log::error('Instagram service exception', ['ig_user_id' => $igUserId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $raw
     * @return array<int, array{type: string, url: string|null, is_ephemeral: bool}>
     */
    private function parseAttachments(array $raw): array
    {
        return array_map(fn ($a) => [
            'type' => $a['type'],
            'url' => $a['payload']['url'] ?? null,
            'is_ephemeral' => $a['payload']['is_ephemeral'] ?? false,
        ], $raw);
    }
}
