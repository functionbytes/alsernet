<?php

namespace Modules\Helpdesk\Services\Channels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base común para los canales que envían vía la Graph API de Meta a través del
 * endpoint /me/messages (Facebook Messenger e Instagram Messaging comparten el
 * mismo contrato de envío, firma de webhook y sender_action).
 *
 * Las subclases aportan las credenciales y metadatos específicos del canal; el
 * parseo de webhooks entrantes (que sí difiere entre canales) queda en cada una.
 */
abstract class MetaGraphChannelDriver
{
    protected const BASE_URL = 'https://graph.facebook.com';

    abstract protected function apiVersion(): string;

    abstract protected function accessToken(): string;

    abstract protected function appSecret(): string;

    /** Etiqueta usada en los logs ("Facebook Messenger", "Instagram", …). */
    abstract protected function channelLabel(): string;

    abstract public function isEnabled(): bool;

    /**
     * Send a text message to a recipient id (PSID / IGSID).
     */
    public function sendText(string $recipientId, string $text): ?string
    {
        return $this->send($recipientId, ['text' => $text]);
    }

    /**
     * Send an image, video, audio or file attachment by URL.
     *
     * @param  'image'|'video'|'audio'|'file'  $type
     */
    public function sendAttachment(string $recipientId, string $type, string $url): ?string
    {
        return $this->send($recipientId, [
            'attachment' => [
                'type' => $type,
                'payload' => ['url' => $url],
            ],
        ]);
    }

    /**
     * Verify the X-Hub-Signature-256 header. Format: "sha256=<hex_hash>".
     */
    public function verifySignature(string $rawBody, string $signatureHeader): bool
    {
        if (! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $provided = substr($signatureHeader, 7);
        $expected = hash_hmac('sha256', $rawBody, $this->appSecret());

        return hash_equals($expected, $provided);
    }

    /**
     * Send a sender_action (mark_seen / typing_on / typing_off).
     */
    public function sendSenderAction(string $recipientId, string $action = 'mark_seen'): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::withToken($this->accessToken())
                ->timeout(8)
                ->post(self::BASE_URL.'/'.$this->apiVersion().'/me/messages', [
                    'recipient' => ['id' => $recipientId],
                    'sender_action' => $action,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning($this->channelLabel().' sender_action failed', [
                'recipient' => $recipientId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a quick replies message (text + reply buttons, capped at $max).
     *
     * @param  array<int, array{title: string, payload: string}>  $replies
     */
    protected function sendQuickRepliesMessage(string $recipientId, string $text, array $replies, int $max): ?string
    {
        return $this->send($recipientId, [
            'text' => $text,
            'quick_replies' => array_map(fn ($r) => [
                'content_type' => 'text',
                'title' => $r['title'],
                'payload' => $r['payload'],
            ], array_slice($replies, 0, $max)),
        ]);
    }

    /**
     * POST a message envelope to /me/messages.
     *
     * @return string|null The message_id on success
     */
    protected function send(string $recipientId, array $message, string $messagingType = 'RESPONSE'): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $response = Http::withToken($this->accessToken())
                ->timeout(15)
                ->retry(2, 500)
                ->post(self::BASE_URL.'/'.$this->apiVersion().'/me/messages', [
                    'recipient' => ['id' => $recipientId],
                    'messaging_type' => $messagingType,
                    'message' => $message,
                ]);

            if (! $response->successful()) {
                Log::error($this->channelLabel().' send failed', [
                    'recipient' => $recipientId,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            return $response->json('message_id');
        } catch (\Throwable $e) {
            Log::error($this->channelLabel().' exception', [
                'recipient' => $recipientId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
