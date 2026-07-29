<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\WhatsAppUsage;

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
        $channel = $conversation->channel ?? 'web';
        $externalId = $conversation->external_sender_id;

        if (blank($externalId) || $channel === 'web') {
            return null; // Widget conversations don't need outbound API calls
        }

        try {
            $messageId = match ($channel) {
                'whatsapp' => $this->whatsapp->sendText($externalId, $text),
                'facebook' => $this->facebook->sendText($externalId, $text),
                'instagram' => $this->instagram->sendText($externalId, $text),
                default => null,
            };

            if ($channel === 'whatsapp') {
                $this->logWhatsAppUsage($conversation->id, 'text', $messageId !== null);
            }

            return $messageId;
        } catch (\Throwable $e) {
            Log::error('OutboundMessageService: failed to send reply', [
                'channel' => $channel,
                'external_id' => $externalId,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            if ($channel === 'whatsapp') {
                $this->logWhatsAppUsage($conversation->id, 'text', false);
            }

            return null;
        }
    }

    /**
     * Registra una respuesta de texto por WhatsApp en el ledger de gasto
     * (categoría "service": gratis, dentro de la ventana de 24h). Un fallo al
     * escribir el ledger no debe tumbar el envío real ya intentado.
     */
    private function logWhatsAppUsage(int $conversationId, string $messageType, bool $success): void
    {
        try {
            WhatsAppUsage::query()->create([
                'conversation_id' => $conversationId,
                'template_name' => null,
                'category' => 'service',
                'message_type' => $messageType,
                'success' => $success,
            ]);
        } catch (\Throwable) {
            // Observabilidad, no debe tumbar el envío real.
        }
    }

    /**
     * Whether the given conversation supports outbound sending via an external API.
     */
    public function supports(Conversation $conversation): bool
    {
        return in_array($conversation->channel ?? 'web', ['whatsapp', 'facebook', 'instagram'], true)
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
        $channel = $conversation->channel ?? 'web';
        $externalId = $conversation->external_sender_id;

        if (blank($externalId) || $channel === 'web') {
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
        $channel = $conversation->channel ?? 'web';
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
     * Send a prompt with selectable options as the channel's NATIVE controls
     * (WhatsApp reply buttons ≤3, Messenger/Instagram quick replies). Returns the
     * external message id on success, or null when the channel can't render them
     * within its limit (the caller should then fall back to a numbered text list).
     *
     * @param  array<int, string>  $options  Option labels
     */
    public function sendOptions(Conversation $conversation, string $text, array $options): ?string
    {
        $channel = $conversation->channel ?? 'web';
        $externalId = $conversation->external_sender_id;
        $options = array_values($options);

        if (blank($externalId) || $channel === 'web' || empty($options)) {
            return null;
        }

        $limit = match ($channel) {
            'whatsapp' => 3,
            'facebook' => 11,
            'instagram' => 13,
            default => 0,
        };

        if ($limit === 0 || count($options) > $limit) {
            return null; // too many options for native controls → caller uses text
        }

        try {
            return match ($channel) {
                'whatsapp' => $this->whatsapp->sendButtons($externalId, $text, $this->mapButtons($options, 'id')),
                'facebook' => $this->facebook->sendQuickReplies($externalId, $text, $this->mapButtons($options, 'payload')),
                'instagram' => $this->instagram->sendQuickReplies($externalId, $text, $this->mapButtons($options, 'payload')),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('OutboundMessageService: sendOptions failed', [
                'channel' => $channel,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send a carousel of cards (products, articles) using each channel's native
     * format: Messenger's generic template (horizontal cards), or — for WhatsApp
     * and Instagram, which lack a session carousel — one image-with-caption per
     * card. Returns the last external message id, or null when nothing was sent
     * (the caller then falls back to a numbered text list).
     *
     * @param  array<int, array{title?: string, subtitle?: string, image_url?: ?string, url?: ?string}>  $cards
     */
    public function sendCarousel(Conversation $conversation, array $cards): ?string
    {
        $channel = $conversation->channel ?? 'web';
        $externalId = $conversation->external_sender_id;
        $cards = array_values(array_filter($cards, 'is_array'));

        if (blank($externalId) || $channel === 'web' || empty($cards)) {
            return null;
        }

        try {
            return match ($channel) {
                'facebook' => $this->facebook->sendGenericTemplate($externalId, $this->mapCardElements($cards)),
                'whatsapp', 'instagram' => $this->sendCardsAsImages($conversation, $cards),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('OutboundMessageService: sendCarousel failed', [
                'channel' => $channel,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Map cards to Messenger generic-template elements (max 10, with an optional
     * "web_url" button when the card has a link).
     *
     * @param  array<int, array<string, mixed>>  $cards
     * @return array<int, array<string, mixed>>
     */
    private function mapCardElements(array $cards): array
    {
        return array_map(function ($card) {
            $element = array_filter([
                'title' => mb_substr(trim((string) ($card['title'] ?? 'Producto')), 0, 80),
                'subtitle' => mb_substr(trim((string) ($card['subtitle'] ?? '')), 0, 80),
                'image_url' => $card['image_url'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            if (! empty($card['url'])) {
                $element['buttons'] = [[
                    'type' => 'web_url',
                    'url' => $card['url'],
                    'title' => mb_substr(trim((string) ($card['button'] ?? 'Ver')), 0, 20),
                ]];
            }

            return $element;
        }, array_slice($cards, 0, 10));
    }

    /**
     * WhatsApp/Instagram fallback: send each card as an image (with caption on
     * WhatsApp, image + text on Instagram). Returns the last message id.
     *
     * @param  array<int, array<string, mixed>>  $cards
     */
    private function sendCardsAsImages(Conversation $conversation, array $cards): ?string
    {
        $channel = $conversation->channel ?? 'web';
        $last = null;

        foreach (array_slice($cards, 0, 10) as $card) {
            $caption = trim(implode("\n", array_filter([
                $card['title'] ?? null,
                $card['subtitle'] ?? null,
                $card['url'] ?? null,
            ])));
            $image = $card['image_url'] ?? null;

            if ($image && $channel === 'whatsapp') {
                $last = $this->sendAttachment($conversation, 'image', $image, $caption ?: null);

                continue;
            }

            if ($image) { // instagram: image first, then the caption as text
                $this->sendAttachment($conversation, 'image', $image);
                $last = $caption !== '' ? $this->sendReply($conversation, $caption) : $last;

                continue;
            }

            if ($caption !== '') {
                $last = $this->sendReply($conversation, $caption);
            }
        }

        return $last;
    }

    /**
     * @param  array<int, string>  $options
     * @param  string  $idKey  'id' for WhatsApp buttons, 'payload' for FB/IG quick replies
     * @return array<int, array{title: string, id?: string, payload?: string}>
     */
    private function mapButtons(array $options, string $idKey): array
    {
        return array_map(fn ($label, $i) => [
            'title' => mb_substr(trim((string) $label), 0, 20), // channel title limit
            $idKey => 'opt_'.($i + 1),
        ], $options, array_keys($options));
    }

    /**
     * Send a typing indicator to the customer's channel.
     * Only Facebook/Instagram support typing_on/off; WhatsApp ignores it.
     */
    public function setTyping(Conversation $conversation, bool $on): bool
    {
        $channel = $conversation->channel ?? 'web';
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
        $channel = $conversation->channel ?? 'web';
        $externalId = $conversation->external_sender_id;

        if (blank($externalId) || $channel === 'web') {
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
