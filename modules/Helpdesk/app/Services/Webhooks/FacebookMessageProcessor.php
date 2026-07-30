<?php

namespace Modules\Helpdesk\Services\Webhooks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\InboxItemChanged;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;

class FacebookMessageProcessor
{
    /**
     * Process a parsed Facebook Messenger event and persist it as a ConversationItem.
     *
     * @param  array<string, mixed>  $event  Parsed event from FacebookMessengerService::parseWebhookPayload()
     */
    public function process(array $event): ?ConversationItem
    {
        if (! in_array($event['type'] ?? '', ['message', 'postback'], true)) {
            return null;
        }

        $psid = $event['psid'];
        $messageId = $event['message_id'] ?? null;
        $body = $this->resolveBody($event);

        return DB::connection('helpdesk')->transaction(function () use ($psid, $messageId, $body, $event) {
            if ($messageId && $this->isDuplicate($messageId)) {
                Log::info('Facebook duplicate message skipped', ['message_id' => $messageId]);

                return null;
            }

            $customer = Customer::firstOrCreate(
                ['facebook_psid' => $psid],
                ['name' => $psid, 'language' => 'es'],
            );

            $conversation = $this->findOrCreateConversation($customer, $psid);

            $item = ConversationItem::create([
                'conversation_id' => $conversation->id,
                'author_id' => $customer->id,
                'type' => 'message',
                'body' => $body,
                'external_id' => $messageId,
                'metadata' => array_filter([
                    'attachments' => $event['attachments'] ?? null,
                    'quick_reply' => $event['quick_reply'] ?? null,
                    'postback' => ($event['type'] === 'postback') ? ($event['payload'] ?? null) : null,
                    'platform' => 'facebook',
                    'raw' => $event,
                ]),
            ]);

            $conversation->update(['last_message_at' => now()]);

            broadcast(new ConversationMessageCreated($item));

            if ($conversation->assignee_id) {
                event(new InboxItemChanged($conversation->id, $conversation->assignee_id, 'message_added'));
            }

            return $item;
        });
    }

    private function findOrCreateConversation(Customer $customer, string $psid): Conversation
    {
        $existing = Conversation::query()
            ->where('channel', 'facebook')
            ->where('external_sender_id', $psid)
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
            'channel' => 'facebook',
            'external_sender_id' => $psid,
            'subject' => 'Messenger · '.$customer->name,
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

        if ($event['type'] === 'postback') {
            return $event['title'] ?? '[postback]';
        }

        $attachments = $event['attachments'] ?? [];

        if (! empty($attachments)) {
            $labels = array_map(fn ($a) => '['.$a['type'].']', $attachments);

            return implode(' ', $labels);
        }

        return '[mensaje]';
    }
}
