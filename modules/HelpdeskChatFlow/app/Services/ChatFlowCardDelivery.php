<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\OutboundMessageService;

/**
 * Delivers the `cards` metadata of a bot message on external channels.
 *
 * Card contract (each entry of metadata `cards`):
 *  - title        string  required-ish (a card needs title, subtitle or image)
 *  - subtitle     string  optional
 *  - image_url    ?string optional, public https URL
 *  - url          ?string optional, link the card opens
 *  - button_label ?string optional, only meaningful with url: per-card button
 *                         text. Without it the whole card is the link (widget)
 *                         or a generic "Ver" button (Messenger).
 *
 * Per channel:
 *  - Messenger: generic template (max 10) with a web_url button when url is set.
 *  - WhatsApp: image + caption (or plain text) "Title / Subtitle / 👉 url". The
 *    WhatsApp wrapper has no `interactive cta_url` sender yet (its send() is
 *    private in the Helpdesk module), so the link goes as text, which WhatsApp
 *    renders as a tappable link.
 *  - Instagram: no carousel/CTA support in InstagramService → image then text.
 */
class ChatFlowCardDelivery
{
    private const MAX_CARDS = 10;

    private const DEFAULT_BUTTON = 'Ver';

    public function __construct(
        private readonly OutboundMessageService $outbound,
        private readonly FacebookMessengerService $facebook,
    ) {}

    /**
     * @param  array<int, mixed>  $cards
     * @return ?string last external message id, or null when nothing was sent
     */
    public function send(Conversation $conversation, array $cards): ?string
    {
        $channel = $conversation->channel ?? 'web';
        $externalId = $conversation->external_sender_id;
        $cards = array_slice(array_values(array_filter($cards, 'is_array')), 0, self::MAX_CARDS);

        if (blank($externalId) || $channel === 'web' || $cards === []) {
            return null;
        }

        try {
            return match ($channel) {
                'facebook' => $this->facebook->sendGenericTemplate($externalId, $this->messengerElements($cards)),
                'whatsapp', 'instagram' => $this->sendAsMessages($conversation, $cards),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('ChatFlowCardDelivery: send failed', [
                'channel' => $channel,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $cards
     * @return array<int, array<string, mixed>>
     */
    private function messengerElements(array $cards): array
    {
        return array_map(function (array $card): array {
            $element = array_filter([
                'title' => mb_substr(trim((string) ($card['title'] ?? '')) ?: self::DEFAULT_BUTTON, 0, 80),
                'subtitle' => mb_substr(trim((string) ($card['subtitle'] ?? '')), 0, 80),
                'image_url' => $card['image_url'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            if (! empty($card['url'])) {
                $label = trim((string) ($card['button_label'] ?? '')) ?: self::DEFAULT_BUTTON;
                $element['buttons'] = [[
                    'type' => 'web_url',
                    'url' => $card['url'],
                    'title' => mb_substr($label, 0, 20),
                ]];
            }

            return $element;
        }, $cards);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cards
     */
    private function sendAsMessages(Conversation $conversation, array $cards): ?string
    {
        $isWhatsApp = ($conversation->channel ?? '') === 'whatsapp';
        $last = null;

        foreach ($cards as $card) {
            $text = $this->cardText($card);
            $image = $card['image_url'] ?? null;

            if ($image && $isWhatsApp) {
                $last = $this->outbound->sendAttachment($conversation, 'image', $image, $text ?: null) ?? $last;

                continue;
            }

            if ($image) { // Instagram: image first, then the text
                $this->outbound->sendAttachment($conversation, 'image', $image);
            }

            if ($text !== '') {
                $last = $this->outbound->sendReply($conversation, $text) ?? $last;
            }
        }

        return $last;
    }

    /**
     * "Title\nSubtitle\n👉 url" (with the button label when there is one).
     *
     * @param  array<string, mixed>  $card
     */
    private function cardText(array $card): string
    {
        $lines = [trim((string) ($card['title'] ?? '')), trim((string) ($card['subtitle'] ?? ''))];

        if (! empty($card['url'])) {
            $label = trim((string) ($card['button_label'] ?? ''));
            $lines[] = '👉 '.($label !== '' ? $label.': ' : '').$card['url'];
        }

        return implode("\n", array_filter($lines, fn ($l) => $l !== ''));
    }
}
