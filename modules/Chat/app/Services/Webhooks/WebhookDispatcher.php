<?php

namespace Modules\Chat\Services\Webhooks;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Webhook;

class WebhookDispatcher
{
    /**
     * Dispatch a webhook event
     */
    public function dispatch(string $event, $resource): void
    {
        // Determine account_id and inbox_id based on resource type
        $accountId = $this->getAccountId($resource);
        $inboxId = $this->getInboxId($resource);

        if (! $accountId) {
            return;
        }

        // Get all webhooks that are subscribed to this event
        $webhooks = $this->getSubscribedWebhooks($accountId, $inboxId, $event);

        foreach ($webhooks as $webhook) {
            $this->sendWebhook($webhook, $event, $resource);
        }
    }

    /**
     * Get account ID from resource
     */
    protected function getAccountId($resource): ?int
    {
        if ($resource instanceof Conversation || $resource instanceof ConversationMessage) {
            return $resource->account_id;
        }

        return null;
    }

    /**
     * Get inbox ID from resource
     */
    protected function getInboxId($resource): ?int
    {
        if ($resource instanceof Conversation) {
            return $resource->inbox_id;
        }

        if ($resource instanceof ConversationMessage) {
            return $resource->conversation->inbox_id ?? null;
        }

        return null;
    }

    /**
     * Get webhooks subscribed to this event
     */
    protected function getSubscribedWebhooks(int $accountId, ?int $inboxId, string $event): Collection
    {
        return Webhook::where('account_id', $accountId)
            ->where(function ($query) use ($inboxId) {
                // Account-level webhooks (TYPE_ACCOUNT = 0)
                $query->where(function ($q) {
                    $q->where('webhook_type', Webhook::TYPE_ACCOUNT)
                        ->whereNull('inbox_id');
                });

                // Inbox-level webhooks (TYPE_INBOX = 1)
                if ($inboxId) {
                    $query->orWhere(function ($q) use ($inboxId) {
                        $q->where('webhook_type', Webhook::TYPE_INBOX)
                            ->where('inbox_id', $inboxId);
                    });
                }
            })
            ->where(function ($query) use ($event) {
                // Check if subscribed to this event
                // Subscriptions is a JSON array like: ["message_created", "conversation_status_changed"]
                $query->whereJsonContains('subscriptions', $event)
                    ->orWhereNull('subscriptions'); // null means subscribe to all events
            })
            ->get();
    }

    /**
     * Send webhook HTTP request
     */
    protected function sendWebhook(Webhook $webhook, string $event, $resource): void
    {
        try {
            $payload = $this->buildPayload($event, $resource);

            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Channels-Webhook/1.0',
                ])
                ->post($webhook->url, $payload);

            // Log webhook delivery
            Log::info('Webhook sent', [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'url' => $webhook->url,
                'status_code' => $response->status(),
            ]);

            // Handle failures
            if (! $response->successful()) {
                Log::warning('Webhook delivery failed', [
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'url' => $webhook->url,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Webhook dispatch error', [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'url' => $webhook->url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build webhook payload
     */
    protected function buildPayload(string $event, $resource): array
    {
        $payload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($resource instanceof Conversation) {
            $payload['conversation'] = [
                'id' => $resource->id,
                'inbox_id' => $resource->inbox_id,
                'customer_id' => $resource->customer_id,
                'status' => $resource->status,
                'priority' => $resource->priority,
                'assignee_id' => $resource->assignee_id,
                'team_id' => $resource->team_id,
                'created_at' => $resource->created_at->toIso8601String(),
                'updated_at' => $resource->updated_at->toIso8601String(),
                'last_activity_at' => $resource->last_activity_at?->toIso8601String(),
                'custom_attributes' => $resource->custom_attributes,
                'labels' => $resource->cached_label_list ? explode(',', $resource->cached_label_list) : [],
            ];
        }

        if ($resource instanceof ConversationMessage) {
            $payload['message'] = [
                'id' => $resource->id,
                'conversation_id' => $resource->conversation_id,
                'message_type' => $resource->message_type,
                'content' => $resource->content,
                'content_type' => $resource->content_type,
                'private' => $resource->private,
                'sender_id' => $resource->sender_id,
                'sender_type' => $resource->sender_type,
                'source_id' => $resource->source_id,
                'created_at' => $resource->created_at->toIso8601String(),
            ];

            // Include conversation data for message events
            if ($resource->conversation) {
                $payload['conversation'] = [
                    'id' => $resource->conversation->id,
                    'inbox_id' => $resource->conversation->inbox_id,
                    'status' => $resource->conversation->status,
                ];
            }
        }

        return $payload;
    }
}
