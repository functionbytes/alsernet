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

class WhatsAppMessageProcessor
{
    /**
     * Process a parsed WhatsApp message event and persist it as a ConversationItem.
     *
     * @param  array<string, mixed>  $event  Parsed event from WhatsAppBusinessService::parseWebhookPayload()
     */
    public function process(array $event): ?ConversationItem
    {
        if (($event['type'] ?? '') !== 'message') {
            return null;
        }

        $phone = $event['phone'];
        $name = $event['name'] ?? $phone;
        $messageId = $event['message_id'];
        $body = $event['body'] ?? $this->fallbackBody($event);

        return DB::connection('helpdesk')->transaction(function () use ($phone, $name, $messageId, $body, $event) {
            if ($this->isDuplicate($messageId)) {
                Log::info('WhatsApp duplicate message skipped', ['message_id' => $messageId]);

                return null;
            }

            $customer = Customer::firstOrCreate(
                ['whatsapp_phone' => $phone],
                ['name' => $name, 'phone' => $phone, 'language' => 'es'],
            );

            $conversation = $this->findOrCreateConversation($customer);

            $item = ConversationItem::create([
                'conversation_id' => $conversation->id,
                'author_id' => $customer->id,
                'type' => 'message',
                'body' => $body,
                'external_id' => $messageId,
                'metadata' => array_filter([
                    'media_id' => $event['media_id'] ?? null,
                    'mime_type' => $event['mime_type'] ?? null,
                    'filename' => $event['filename'] ?? null,
                    'caption' => $event['caption'] ?? null,
                    'message_type' => $event['message_type'] ?? null,
                    'platform' => 'whatsapp',
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

    private function findOrCreateConversation(Customer $customer): Conversation
    {
        $existing = Conversation::query()
            ->where('channel', 'whatsapp')
            ->where('customer_id', $customer->id)
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
            'channel' => 'whatsapp',
            'external_sender_id' => $customer->whatsapp_phone,
            'subject' => 'WhatsApp · '.$customer->name,
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

    private function fallbackBody(array $event): string
    {
        $type = $event['message_type'] ?? 'unknown';
        $caption = $event['caption'] ?? null;

        return match ($type) {
            'image' => $caption ? "[imagen]: {$caption}" : '[imagen]',
            'document' => '[documento'.($event['filename'] ? ": {$event['filename']}" : '').']',
            'audio' => '[audio]',
            'video' => $caption ? "[video]: {$caption}" : '[video]',
            'sticker' => '[sticker]',
            default => "[{$type}]",
        };
    }
}
