<?php

namespace Modules\HelpdeskLivechat\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Models\Inbox;
use Nwidart\Modules\Facades\Module;

class EngagementBridgeListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesklivechat';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function handleConversationCreated(ConversationCreated $event): void
    {
        $conversation = $event->conversation;

        if (! $this->isEngagementActive() || ! $this->isWebChannel($conversation)) {
            return;
        }

        $sessionToken = $this->resolveSessionToken($conversation);

        if (! $sessionToken) {
            return;
        }

        $inbox = $this->resolveInbox($conversation);

        if (! $inbox) {
            return;
        }

        $this->track($sessionToken, $inbox, 'conversation.started', [
            'conversation_id' => $conversation->id,
            'inbox_id' => $conversation->inbox_id,
            'channel_type' => $conversation->channel ?? 'web',
            'customer_id' => $conversation->customer_id,
        ]);
    }

    public function handleMessageCreated(ConversationMessageCreated $event): void
    {
        $item = $event->item;

        // Only track messages from the customer (author set, no user_id).
        if (! $item->author_id || $item->user_id) {
            return;
        }

        $conversation = $item->conversation;

        if (! $conversation || ! $this->isEngagementActive() || ! $this->isWebChannel($conversation)) {
            return;
        }

        $sessionToken = $this->resolveSessionToken($conversation);

        if (! $sessionToken) {
            return;
        }

        $inbox = $this->resolveInbox($conversation);

        if (! $inbox) {
            return;
        }

        $attachments = is_array($item->attachment_urls) ? $item->attachment_urls : [];

        $this->track($sessionToken, $inbox, 'conversation.message_sent', [
            'conversation_id' => $conversation->id,
            'message_length' => mb_strlen((string) ($item->body ?? '')),
            'has_attachments' => count($attachments) > 0,
        ]);
    }

    public function handleConversationClosed(ConversationClosed $event): void
    {
        $conversation = $event->conversation;

        if (! $this->isEngagementActive() || ! $this->isWebChannel($conversation)) {
            return;
        }

        $sessionToken = $this->resolveSessionToken($conversation);

        if (! $sessionToken) {
            return;
        }

        $inbox = $this->resolveInbox($conversation);

        if (! $inbox) {
            return;
        }

        $durationMinutes = (int) ($conversation->created_at?->diffInMinutes($conversation->closed_at ?? now()) ?? 0);
        $messageCount = $conversation->messages()->count();
        $csatRating = CsatRating::query()
            ->where('conversation_id', $conversation->id)
            ->whereNotNull('answered_at')
            ->value('rating');

        $this->track($sessionToken, $inbox, 'conversation.closed', [
            'conversation_id' => $conversation->id,
            'duration_minutes' => $durationMinutes,
            'message_count' => $messageCount,
            'rating' => $csatRating,
            'feedback_provided' => $csatRating !== null,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EngagementBridgeListener failed', [
            'error' => $exception->getMessage(),
        ]);
    }

    private function isEngagementActive(): bool
    {
        return Module::find('Engagement')?->isEnabled() ?? false;
    }

    private function isWebChannel(Conversation $conversation): bool
    {
        return ($conversation->channel ?? '') === 'web';
    }

    /**
     * Resolve the engagement session token from the conversation's metadata or
     * visitor session table, falling back to a synthetic token keyed by customer.
     */
    private function resolveSessionToken(Conversation $conversation): ?string
    {
        $visitorSessionClass = '\\Modules\\Engagement\\Models\\VisitorSession';

        if (! class_exists($visitorSessionClass)) {
            return null;
        }

        // The widget stores a pubsub_token in metadata — this doubles as the
        // engagement session identifier when the visitor session row exists.
        $meta = is_array($conversation->metadata)
            ? $conversation->metadata
            : (json_decode((string) ($conversation->metadata ?? '{}'), true) ?? []);

        $widgetToken = $meta['widget_pubsub_token'] ?? null;

        if ($widgetToken) {
            $session = $visitorSessionClass::query()
                ->where('session_token', $widgetToken)
                ->first();

            if ($session) {
                return $widgetToken;
            }
        }

        // Fall back to finding any active session tied to the customer.
        if ($conversation->customer_id) {
            $session = $visitorSessionClass::query()
                ->where('customer_id', $conversation->customer_id)
                ->where('inbox_id', $conversation->inbox_id)
                ->latest('last_activity_at')
                ->first();

            if ($session) {
                return $session->session_token;
            }
        }

        // No session found — nothing to bridge.
        return null;
    }

    private function resolveInbox(Conversation $conversation): ?Inbox
    {
        return $conversation->relationLoaded('inbox')
            ? $conversation->inbox
            : Inbox::find($conversation->inbox_id);
    }

    /**
     * Delegate to TrackingIngestService using a single-event payload.
     */
    private function track(string $sessionToken, Inbox $inbox, string $eventName, array $properties): void
    {
        $ingestServiceClass = '\\Modules\\Engagement\\Services\\TrackingIngestService';

        if (! class_exists($ingestServiceClass)) {
            return;
        }

        try {
            $service = app($ingestServiceClass);

            $service->ingest(
                [['name' => $eventName, 'ts' => now()->toIso8601String(), 'properties' => $properties]],
                $sessionToken,
                $inbox,
            );
        } catch (\Throwable $e) {
            Log::warning('EngagementBridgeListener: tracking failed', [
                'event' => $eventName,
                'session_token' => substr($sessionToken, 0, 8).'…',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
