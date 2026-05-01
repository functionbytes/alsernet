<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookMessengerService
{
    private const API_VERSION = 'v25.0';

    private const BASE_URL = 'https://graph.facebook.com';

    private string $pageAccessToken;

    private string $appSecret;

    private string $verifyToken;

    public function __construct()
    {
        $this->pageAccessToken = config('helpdesk.integrations.facebook.page_access_token', '');
        $this->appSecret = config('helpdesk.integrations.facebook.app_secret', '');
        $this->verifyToken = config('helpdesk.integrations.facebook.verify_token', '');
    }

    public function isEnabled(): bool
    {
        return config('helpdesk.integrations.facebook.enabled', false)
            && filled($this->pageAccessToken)
            && filled($this->appSecret);
    }

    // ─── Sending ──────────────────────────────────────────────────────────────

    /**
     * Send a text message to a PSID.
     */
    public function sendText(string $psid, string $text): ?string
    {
        return $this->send($psid, ['text' => $text]);
    }

    /**
     * Send an image, file, audio, or video attachment by URL.
     *
     * @param  'image'|'video'|'audio'|'file'  $type
     */
    public function sendAttachment(string $psid, string $type, string $url): ?string
    {
        return $this->send($psid, [
            'attachment' => [
                'type' => $type,
                'payload' => ['url' => $url],
            ],
        ]);
    }

    /**
     * Send quick reply buttons (max 11).
     *
     * @param  array<int, array{title: string, payload: string}>  $replies
     */
    public function sendQuickReplies(string $psid, string $text, array $replies): ?string
    {
        return $this->send($psid, [
            'text' => $text,
            'quick_replies' => array_map(fn ($r) => [
                'content_type' => 'text',
                'title' => $r['title'],
                'payload' => $r['payload'],
            ], array_slice($replies, 0, 11)),
        ]);
    }

    /**
     * Send a Generic Template (carousel, max 10 cards).
     *
     * Each element: ['title', 'subtitle'?, 'image_url'?, 'buttons'?[]]
     *
     * @param  array<int, array<string, mixed>>  $elements
     */
    public function sendGenericTemplate(string $psid, array $elements): ?string
    {
        return $this->send($psid, [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'generic',
                    'elements' => array_slice($elements, 0, 10),
                ],
            ],
        ]);
    }

    // ─── Webhook ──────────────────────────────────────────────────────────────

    /**
     * Verify a Meta webhook challenge. Returns challenge string or false.
     */
    public function verifyWebhook(string $mode, string $challenge, string $verifyToken): string|false
    {
        if ($mode === 'subscribe' && filled($this->verifyToken) && hash_equals($this->verifyToken, $verifyToken)) {
            return $challenge;
        }

        return false;
    }

    /**
     * Verify the X-Hub-Signature-256 header.
     * Header format: "sha256=<hex_hash>"
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
     * Parse a Facebook Messenger webhook payload.
     *
     * Returns array of events. Each event has:
     *   type: 'message' | 'postback' | 'quick_reply' | 'delivery' | 'read'
     *
     * Message event keys: psid, message_id, timestamp, body, attachments[]
     * Postback event keys: psid, message_id, title, payload, timestamp
     * Delivery event keys: psid, watermark
     * Read event keys: psid, watermark
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseWebhookPayload(array $payload): array
    {
        $events = [];

        if (($payload['object'] ?? '') !== 'page') {
            return $events;
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['messaging'] ?? [] as $messaging) {
                $psid = $messaging['sender']['id'];
                $timestamp = (int) ($messaging['timestamp'] ?? 0);

                // Skip echo messages (messages we sent)
                if ($messaging['message']['is_echo'] ?? false) {
                    continue;
                }

                if (isset($messaging['message'])) {
                    $msg = $messaging['message'];
                    $events[] = [
                        'type' => 'message',
                        'psid' => $psid,
                        'message_id' => $msg['mid'],
                        'timestamp' => $timestamp,
                        'body' => $msg['text'] ?? null,
                        'attachments' => $this->parseAttachments($msg['attachments'] ?? []),
                        'quick_reply' => $msg['quick_reply']['payload'] ?? null,
                    ];
                } elseif (isset($messaging['postback'])) {
                    $pb = $messaging['postback'];
                    $events[] = [
                        'type' => 'postback',
                        'psid' => $psid,
                        'message_id' => $pb['mid'] ?? null,
                        'title' => $pb['title'],
                        'payload' => $pb['payload'],
                        'timestamp' => $timestamp,
                    ];
                } elseif (isset($messaging['delivery'])) {
                    $events[] = [
                        'type' => 'delivery',
                        'psid' => $psid,
                        'watermark' => $messaging['delivery']['watermark'],
                    ];
                } elseif (isset($messaging['read'])) {
                    $events[] = [
                        'type' => 'read',
                        'psid' => $psid,
                        'watermark' => $messaging['read']['watermark'],
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Fetch user profile (name, profile_pic) from a PSID.
     *
     * @return array{first_name: string, last_name: string, name: string, profile_pic: string|null}|null
     */
    public function getUserProfile(string $psid): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get(
                self::BASE_URL.'/'.self::API_VERSION.'/'.$psid,
                [
                    'fields' => 'first_name,last_name,profile_pic',
                    'access_token' => $this->pageAccessToken,
                ]
            );

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            return [
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
                'name' => trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
                'profile_pic' => $data['profile_pic'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Facebook getUserProfile failed', ['psid' => $psid, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Send a sender_action to update the read receipt on the customer's side.
     * Valid actions: mark_seen, typing_on, typing_off.
     */
    public function sendSenderAction(string $psid, string $action = 'mark_seen'): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::withToken($this->pageAccessToken)
                ->timeout(8)
                ->post(self::BASE_URL.'/'.self::API_VERSION.'/me/messages', [
                    'recipient' => ['id' => $psid],
                    'sender_action' => $action,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Facebook sender_action failed', ['psid' => $psid, 'action' => $action, 'error' => $e->getMessage()]);

            return false;
        }
    }

    // ─── Internals ────────────────────────────────────────────────────────────

    /** @return string|null  The message_id on success */
    private function send(string $psid, array $message, string $messagingType = 'RESPONSE'): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $response = Http::withToken($this->pageAccessToken)
                ->timeout(15)
                ->retry(2, 500)
                ->post(self::BASE_URL.'/'.self::API_VERSION.'/me/messages', [
                    'recipient' => ['id' => $psid],
                    'messaging_type' => $messagingType,
                    'message' => $message,
                ]);

            if (! $response->successful()) {
                Log::error('Facebook Messenger send failed', [
                    'psid' => $psid,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            return $response->json('message_id');
        } catch (\Throwable $e) {
            Log::error('Facebook Messenger exception', ['psid' => $psid, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $raw
     * @return array<int, array{type: string, url: string|null}>
     */
    private function parseAttachments(array $raw): array
    {
        return array_map(fn ($a) => [
            'type' => $a['type'],
            'url' => $a['payload']['url'] ?? null,
        ], $raw);
    }
}
