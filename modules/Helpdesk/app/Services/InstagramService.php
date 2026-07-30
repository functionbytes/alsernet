<?php

namespace Modules\Helpdesk\Services;

use Modules\Helpdesk\Services\Channels\MetaGraphChannelDriver;

class InstagramService extends MetaGraphChannelDriver
{
    private const API_VERSION = 'v19.0';

    private string $igAccessToken;

    private string $businessAccountId;

    private string $igAppSecret;

    public function __construct()
    {
        $this->igAccessToken = (string) config('helpdesk.integrations.instagram.access_token', '');
        $this->businessAccountId = (string) config('helpdesk.integrations.instagram.business_account_id', '');
        $this->igAppSecret = (string) config('helpdesk.integrations.facebook.app_secret', ''); // Shared
    }

    protected function apiVersion(): string
    {
        return self::API_VERSION;
    }

    protected function accessToken(): string
    {
        return $this->igAccessToken;
    }

    protected function appSecret(): string
    {
        return $this->igAppSecret;
    }

    protected function channelLabel(): string
    {
        return 'Instagram';
    }

    public function isEnabled(): bool
    {
        return config('helpdesk.integrations.instagram.enabled', false)
            && filled($this->igAccessToken)
            && filled($this->businessAccountId);
    }

    // ─── Sending (channel-specific) ─────────────────────────────────────────────

    /**
     * Send a quick replies message (text + up to 13 reply buttons).
     *
     * @param  array<int, array{title: string, payload: string}>  $replies
     */
    public function sendQuickReplies(string $igUserId, string $text, array $replies): ?string
    {
        return $this->sendQuickRepliesMessage($igUserId, $text, $replies, 13);
    }

    // ─── Webhook ──────────────────────────────────────────────────────────────

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
                $igUserId = $messaging['sender']['id'] ?? null;

                if ($igUserId === null) {
                    continue;
                }

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
                        'message_id' => $messaging['message']['mid'] ?? null,
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
                        'message_id' => $msg['mid'] ?? null,
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
                        'message_id' => $r['mid'] ?? null,
                        'action' => $r['action'] ?? 'react',   // react|unreact
                        'emoji' => $r['emoji'] ?? null,
                    ];
                } elseif (isset($messaging['message_status'])) {
                    $s = $messaging['message_status'];
                    $events[] = [
                        'type' => ($s['status'] ?? null) === 'READ' ? 'read' : 'delivery',
                        'ig_user_id' => $igUserId,
                        'message_id' => $s['mid'] ?? null,
                        'status' => $s['status'] ?? null,
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * @param  array<int, array<string, mixed>>  $raw
     * @return array<int, array{type: string, url: string|null, is_ephemeral: bool}>
     */
    private function parseAttachments(array $raw): array
    {
        return array_map(fn ($a) => [
            'type' => $a['type'] ?? 'file',
            'url' => $a['payload']['url'] ?? null,
            'is_ephemeral' => $a['payload']['is_ephemeral'] ?? false,
        ], $raw);
    }
}
