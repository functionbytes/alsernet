<?php

namespace Modules\HelpdeskChatFlow\Services\Nodes;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowDocumentLink;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\Concerns\FormatsNumberedOptions;
use Modules\HelpdeskChatFlow\Services\Concerns\PostsBotMessages;
use Modules\HelpdeskChatFlow\Services\Concerns\RendersNodeMessages;

/**
 * Nodes that deliver rich content: cards/carousels, files and the secure
 * document-portal link.
 */
class RichContentNodeHandler implements NodeHandler
{
    use FormatsNumberedOptions, PostsBotMessages, RendersNodeMessages;

    public const TYPES = ['rich_message', 'send_file', 'document_link'];

    public function __construct(
        private readonly ChatFlowLocalizer $localizer,
        private readonly ChatFlowDocumentLink $documentLink,
    ) {}

    public function types(): array
    {
        return self::TYPES;
    }

    public function handle(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        return match ($node['type']) {
            'rich_message' => $this->executeRichMessage($node, $session, $conversation),
            'send_file' => $this->executeSendFile($node, $session, $conversation),
            'document_link' => $this->executeDocumentLink($node, $session, $conversation),
        };
    }

    private function executeRichMessage(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $ctx = $session->context ?? [];
        $options = array_values($data['options'] ?? []);

        $cards = $this->normalizeCards($data['cards'] ?? [], $ctx);
        if (count($cards) > 1) {
            return $this->executeCarousel($node, $session, $conversation, $cards, $options);
        }

        $title = $this->interpolateContext($data['title'] ?? '', $ctx);
        $subtitle = $this->interpolateContext($data['subtitle'] ?? '', $ctx);
        $imageUrl = $data['image_url'] ?? null;

        $bodyParts = array_filter([$title, $subtitle]);
        $body = implode("\n", $bodyParts);

        $labels = $this->localizeOptions($options, $session);

        if ($labels) {
            $body = $this->numberedPrompt($body, $labels, $this->optionsHint($session));
        }

        $this->postBotMessage($conversation, $node['id'], $body !== '' ? $body : ($imageUrl ?? ''), array_filter([
            'card' => array_filter([
                'title' => $title,
                'subtitle' => $subtitle,
                'image_url' => $imageUrl,
            ]),
            'image_url' => $imageUrl, // text channels send it as an attachment
            'bot_options' => $labels ?: null,
            'bot_prompt' => trim($title.' '.$subtitle) ?: null,
        ]));

        // With options it waits for the selection; otherwise it continues.
        return $options ? null : $this->getFirstChildId($node, $session);
    }

    /**
     * Carousel of product cards. Delivered with each channel's native format
     * (Messenger generic template, WhatsApp/Instagram image cards); the text
     * body lists them numbered so web/email and fallbacks still work.
     *
     * Card contract (metadata `cards`, also consumed by the widget and
     * ChatFlowCardDelivery): title, subtitle, image_url, url (opens a link) and
     * button_label (optional; only kept when url is set — shows a per-card button
     * instead of making the whole card a link).
     *
     * @param  array<int, array{title: string, subtitle: string, image_url: ?string, url: ?string, button_label: ?string}>  $cards
     * @param  array<int, string>  $options
     */
    private function executeCarousel(array $node, ChatFlowSession $session, Conversation $conversation, array $cards, array $options): ?string
    {
        $ctx = $session->context ?? [];
        $header = $this->interpolateContext($node['data']['title'] ?? '', $ctx);

        $lines = array_map(
            fn ($c) => trim($c['title'].($c['subtitle'] !== '' ? ' — '.$c['subtitle'] : '')),
            $cards,
        );

        $labels = $this->localizeOptions($options, $session);

        if ($labels) {
            $body = $this->numberedPrompt($header, $labels, $this->optionsHint($session));
        } else {
            $list = $this->numberedList($lines);
            $body = $header !== '' ? $header."\n\n".$list : $list;
        }

        $this->postBotMessage($conversation, $node['id'], $body, array_filter([
            'cards' => $cards,
            'bot_options' => $labels ?: null,
            'bot_prompt' => $header ?: null,
        ], fn ($v) => $v !== null && $v !== ''));

        return $options ? null : $this->getFirstChildId($node, $session);
    }

    /**
     * Normalize and interpolate a list of carousel cards, dropping empty ones.
     *
     * @param  array<int, mixed>  $cards
     * @param  array<string, mixed>  $ctx
     * @return array<int, array{title: string, subtitle: string, image_url: ?string, url: ?string, button_label: ?string}>
     */
    private function normalizeCards(array $cards, array $ctx): array
    {
        return array_values(array_filter(array_map(function ($card) use ($ctx) {
            if (! is_array($card)) {
                return null;
            }

            $title = $this->interpolateContext((string) ($card['title'] ?? ''), $ctx);
            $subtitle = $this->interpolateContext((string) ($card['subtitle'] ?? ''), $ctx);
            $image = $card['image_url'] ?? null;
            $url = $card['url'] ?? null;
            $buttonLabel = $this->interpolateContext((string) ($card['button_label'] ?? ''), $ctx);

            if ($title === '' && $subtitle === '' && empty($image)) {
                return null;
            }

            return [
                'title' => $title,
                'subtitle' => $subtitle,
                'image_url' => $image ?: null,
                'url' => $url ?: null,
                'button_label' => $url && $buttonLabel !== '' ? $buttonLabel : null,
            ];
        }, $cards)));
    }

    /**
     * Send a file (PDF/image/video) to the customer. Delivered as a native
     * attachment on WhatsApp/Messenger/Instagram and as an attachment item on web.
     */
    private function executeSendFile(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $url = $this->interpolateContext((string) ($data['file_url'] ?? ''), $session->context ?? []);

        if ($url === '') {
            return $this->getFirstChildId($node, $session);
        }

        $caption = $this->localizeForCustomer($data['caption'] ?? '', $session);
        $type = in_array($data['file_type'] ?? '', ['image', 'video', 'document'], true) ? $data['file_type'] : 'document';

        $this->postBotMessage($conversation, $node['id'], $caption, array_filter([
            'attachment' => ['url' => $url, 'type' => $type, 'caption' => $caption ?: null],
        ], fn ($v) => $v !== null && $v !== ''), ['attachment_urls' => [$url]]);

        return $this->getFirstChildId($node, $session);
    }

    /**
     * Resolves the conversation's document request (HelpdeskDocument) and seeds
     * context variables so later message nodes can send the secure portal link:
     * {{doc_upload_url}} (subir/consultar) and {{doc_missing}} (documentos que faltan).
     */
    private function executeDocumentLink(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $info = $this->documentLink->resolve($conversation);

        $session->setContextValues([
            'doc_upload_url' => $info['upload_url'] ?? '',
            'doc_missing' => $info['missing'] ?? '',
            'doc_found' => $info['found'] ? '1' : '',
        ]);

        return $this->getFirstChildId($node, $session);
    }
}
