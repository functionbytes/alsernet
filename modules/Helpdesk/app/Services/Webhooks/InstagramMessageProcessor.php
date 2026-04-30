<?php

namespace Modules\Helpdesk\Services\Webhooks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationItemCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\InboxItemChanged;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;

class InstagramMessageProcessor
{
    /**
     * Process a parsed Instagram DM event and persist it as a ConversationItem.
     *
     * @param  array<string, mixed>  $event  Parsed event from InstagramService::parseWebhookPayload()
     */
    public function process(array $event): ?ConversationItem
    {
        if (! in_array($event['type'] ?? '', ['message', 'story_reply'], true)) {
            return null;
        }

        $igUserId = $event['ig_user_id'];
        $messageId = $event['message_id'];
        $body = $this->resolveBody($event);

        return DB::connection('helpdesk')->transaction(function () use ($igUserId, $messageId, $body, $event) {
            if ($this->isDuplicate($messageId)) {
                Log::info('Instagram duplicate message skipped', ['message_id' => $messageId]);

                return null;
            }

            $customer = Customer::firstOrCreate(
                ['instagram_id' => $igUserId],
                ['name' => $igUserId, 'language' => 'es'],
            );

            $conversation = $this->findOrCreateConversation($customer, $igUserId);

            $item = ConversationItem::create([
                'conversation_id' => $conversation->id,
                'author_id' => $customer->id,
                'type' => 'message',
                'body' => $body,
                'external_id' => $messageId,
                'metadata' => array_filter([
                    'attachments' => $event['attachments'] ?? null,
                    'is_ephemeral' => $event['is_ephemeral'] ?? null,
                    'story_url' => $event['story_url'] ?? null,
                    'platform' => 'instagram',
                    'raw' => $event,
                ]),
            ]);

            $conversation->update(['last_message_at' => now()]);

            event(new ConversationMessageCreated($item));
            event(new ConversationItemCreated($item));

            if ($conversation->assignee_id) {
                event(new InboxItemChanged($conversation->id, $conversation->assignee_id, 'message_added'));
            }

            return $item;
        });
    }

    private function findOrCreateConversation(Customer $customer, string $igUserId): Conversation
    {
        $existing = Conversation::query()
            ->where('channel', 'instagram')
            ->where('external_sender_id', $igUserId)
            ->whereNull('closed_at')
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        $statusId = ConversationStatus::query()
            ->where('is_default', true)
            ->orWhere('is_open', true)
            ->orderByDesc('is_default')
            ->value('id');

        return Conversation::create([
            'customer_id' => $customer->id,
            'channel' => 'instagram',
            'external_sender_id' => $igUserId,
            'subject' => 'Instagram · '.$customer->name,
            'priority' => 'normal',
            'status_id' => $statusId,
            'last_message_at' => now(),
        ]);
    }

    private function isDuplicate(string $messageId): bool
    {
        return ConversationItem::query()
            ->where('external_id', $messageId)
            ->exists();
    }

    private function resolveBody(array $event): string
    {
        if (filled($event['body'] ?? null)) {
            return $event['body'];
        }

        $attachments = $event['attachments'] ?? [];

        if (! empty($attachments)) {
            $labels = array_map(fn ($a) => '['.$a['type'].']', $attachments);

            return implode(' ', $labels);
        }

        return '[media]';
    }
}
