<?php

namespace Modules\Helpdesk\Observers;

use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\LinkPreviewService;

/**
 * Generates an OpenGraph link preview when an agent sends a message containing
 * a URL. Runs on every newly created ConversationItem regardless of which
 * controller / job persisted it, so the preview always lands in metadata.
 *
 * Skips visitor-authored items and internal notes (intentional: only the agent
 * gets URL unfurls in the widget, like WhatsApp/Instagram).
 */
class ConversationItemLinkPreviewObserver
{
    public function __construct(
        private readonly LinkPreviewService $linkPreview,
    ) {}

    public function created(ConversationItem $item): void
    {
        if ($item->is_internal) {
            return;
        }

        // Only enrich agent-authored items with a link preview. Visitor URLs
        // are NOT auto-unfurled (mirrors WhatsApp/Instagram behaviour).
        $isAgent = ! empty($item->user_id);

        if ($isAgent && is_string($item->body) && trim($item->body) !== '') {
            $existing = $item->metadata ?? [];
            if (! isset($existing['link_preview'])) {
                $preview = $this->linkPreview->previewFromBody($item->body);
                if ($preview !== null) {
                    $item->metadata = array_merge($existing, ['link_preview' => $preview]);
                    $item->saveQuietly();
                }
            }
        }

        // Always broadcast the widget event from the observer (single source of
        // truth) — the controller no longer dispatches MessageReceived itself.
        if ($item->conversation) {
            broadcast(new MessageReceived($item->conversation, $item->fresh()));
        }
    }
}
