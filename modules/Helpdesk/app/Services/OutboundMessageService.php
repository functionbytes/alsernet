<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;

class OutboundMessageService
{
    public function __construct(
        private readonly WhatsAppBusinessService $whatsapp,
        private readonly FacebookMessengerService $facebook,
        private readonly InstagramService $instagram,
    ) {}

    /**
     * Send a plain-text reply from an agent to the customer via the conversation's channel.
     *
     * Returns the external message ID (wamid / message_id) or null if not applicable
     * (widget conversations, disabled channels, or errors).
     */
    public function sendReply(Conversation $conversation, string $text): ?string
    {
        $channel = $conversation->channel ?? 'widget';
        $externalId = $conversation->external_sender_id;

        if (blank($externalId) || $channel === 'widget') {
            return null; // Widget conversations don't need outbound API calls
        }

        try {
            return match ($channel) {
                'whatsapp' => $this->whatsapp->sendText($externalId, $text),
                'facebook' => $this->facebook->sendText($externalId, $text),
                'instagram' => $this->instagram->sendText($externalId, $text),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('OutboundMessageService: failed to send reply', [
                'channel' => $channel,
                'external_id' => $externalId,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Whether the given conversation supports outbound sending via an external API.
     */
    public function supports(Conversation $conversation): bool
    {
        return in_array($conversation->channel ?? 'widget', ['whatsapp', 'facebook', 'instagram'], true)
            && filled($conversation->external_sender_id);
    }

    /**
     * Send an attachment (image/audio/video/document) to the customer via the
     * conversation's channel. Returns the external message ID on success.
     *
     * @param  string  $type  'image' | 'audio' | 'video' | 'document' | 'file'
     * @param  string  $url  Publicly-reachable URL for Meta to fetch
     */
    public function sendAttachment(Conversation $conversation, string $type, string $url, ?string $caption = null, ?string $filename = null): ?string
    {
        $channel = $conversation->channel ?? 'widget';
        $externalId = $conversation->external_sender_id;

        if (blank($externalId) || $channel === 'widget') {
            return null;
        }

        $normalized = $this->normalizeAttachmentType($channel, $type);

        try {
            return match ($channel) {
                'whatsapp' => $this->sendWhatsAppAttachment($externalId, $normalized, $url, $caption, $filename),
                'facebook' => $this->facebook->sendAttachment($externalId, $normalized, $url),
                'instagram' => $this->instagram->sendAttachment($externalId, $normalized, $url),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('OutboundMessageService: sendAttachment failed', [
                'channel' => $channel,
                'type' => $type,
                'url' => $url,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Normalize attachment type to what each channel API accepts.
     * FB/IG: image|video|audio|file. WhatsApp: image|video|audio|document|sticker.
     */
    private function normalizeAttachmentType(string $channel, string $type): string
    {
        return match ($channel) {
            'whatsapp' => match ($type) {
                'image' => 'image',
                'video' => 'video',
                'audio', 'voice' => 'audio',
                'sticker' => 'sticker',
                default => 'document',
            },
            'facebook', 'instagram' => match ($type) {
                'image' => 'image',
                'video' => 'video',
                'audio', 'voice' => 'audio',
                default => 'file',
            },
            default => $type,
        };
    }

    private function sendWhatsAppAttachment(string $to, string $type, string $url, ?string $caption, ?string $filename): ?string
    {
        return match ($type) {
            'image' => $this->whatsapp->sendImage($to, $url, $caption),
            'video' => $this->whatsapp->sendVideo($to, $url, $caption),
            'audio' => $this->whatsapp->sendAudio($to, $url),
            'document' => $this->whatsapp->sendDocument($to, $url, $filename, $caption),
            default => null,
        };
    }

    /**
     * Send a message with quick-reply buttons. Customer taps a button and we
     * receive a postback webhook. Only Facebook/Instagram support quick replies.
     *
     * @param  array<int, array{title: string, payload: string}>  $buttons
     */
    public function sendQuickReplies(Conversation $conversation, string $text, array $buttons): ?string
    {
        $channel = $conversation->channel ?? 'widget';
        $externalId = $conversation->external_sender_id;

        if (blank($externalId)) {
            return null;
        }

        try {
            return match ($channel) {
                'facebook' => $this->facebook->sendQuickReplies($externalId, $text, $buttons),
                'instagram' => $this->instagram->sendQuickReplies($externalId, $text, $buttons),
                default => $this->sendReply($conversation, $text), // fallback
            };
        } catch (\Throwable $e) {
            Log::error('OutboundMessageService: sendQuickReplies failed', [
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send a typing indicator to the customer's channel.
     * Only Facebook/Instagram support typing_on/off; WhatsApp ignores it.
     */
    public function setTyping(Conversation $conversation, bool $on): bool
    {
        $channel = $conversation->channel ?? 'widget';
        $externalId = $conversation->external_sender_id;

        if (blank($externalId)) {
            return false;
        }

        $action = $on ? 'typing_on' : 'typing_off';

        try {
            return match ($channel) {
                'facebook' => $this->facebook->sendSenderAction($externalId, $action),
                'instagram' => $this->instagram->sendSenderAction($externalId, $action),
                default => false,
            };
        } catch (\Throwable $e) {
            Log::warning('OutboundMessageService: setTyping failed', [
                'channel' => $channel,
                'on' => $on,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a "seen / read" receipt to the customer for the given conversation.
     * Returns true if the API accepted the action, false otherwise.
     */
    public function markSeen(Conversation $conversation, ?string $externalMessageId = null): bool
    {
        $channel = $conversation->channel ?? 'widget';
        $externalId = $conversation->external_sender_id;

        if (blank($externalId) || $channel === 'widget') {
            return false;
        }

        try {
            return match ($channel) {
                'whatsapp' => $externalMessageId
                    ? $this->whatsapp->markAsRead($externalMessageId)
                    : false,
                'facebook' => $this->facebook->sendSenderAction($externalId, 'mark_seen'),
                'instagram' => $this->instagram->sendSenderAction($externalId, 'mark_seen'),
                default => false,
            };
        } catch (\Throwable $e) {
            Log::warning('OutboundMessageService: markSeen failed', [
                'channel' => $channel,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
