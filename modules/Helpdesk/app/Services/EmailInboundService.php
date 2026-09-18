<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;

class EmailInboundService
{
    /**
     * Process a parsed email payload and create or update a conversation.
     *
     * @param  array{from: string, from_name: ?string, subject: string, body: string, message_id: ?string}  $parsed
     */
    public function process(array $parsed): Conversation
    {
        $customer = Customer::firstOrCreate(
            ['email' => $parsed['from']],
            ['name' => $parsed['from_name'] ?? $parsed['from']],
        );

        $conversation = $this->resolveConversation($parsed['subject'], $customer->id);

        $this->addMessage($conversation, $customer->id, $parsed['body'], $parsed['message_id'] ?? null);

        $conversation->update(['last_message_at' => now()]);

        return $conversation;
    }

    private function resolveConversation(string $subject, int $customerId): Conversation
    {
        // Try to thread by [CONV-{id}] tag in subject
        if (preg_match('/\[CONV-(\d+)\]/', $subject, $m)) {
            $conversation = Conversation::query()
                ->where('id', (int) $m[1])
                ->where('customer_id', $customerId)
                ->whereHas('status', fn ($q) => $q->where('is_open', true))
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        return $this->createConversation($subject, $customerId);
    }

    private function createConversation(string $subject, int $customerId): Conversation
    {
        $statusId = ConversationStatus::query()
            ->where('is_default', true)
            ->orWhere('is_open', true)
            ->orderByDesc('is_default')
            ->value('id') ?? 1;

        $conversation = Conversation::create([
            'customer_id' => $customerId,
            'channel' => 'email',
            'subject' => $subject ?: 'Email sin asunto',
            'last_message_at' => now(),
        ]);
        $conversation->status_id = $statusId;
        $conversation->save();

        return $conversation;
    }

    private function addMessage(Conversation $conversation, int $customerId, string $body, ?string $externalId): void
    {
        if ($externalId && $this->isDuplicate($conversation->id, $externalId)) {
            Log::info('EmailInboundService: duplicate message skipped', ['external_id' => $externalId]);

            return;
        }

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'author_id' => $customerId,
            'type' => 'message',
            'body' => $body ?: '[sin contenido]',
            'external_id' => $externalId,
            'is_internal' => false,
            'metadata' => ['platform' => 'email'],
        ]);
    }

    private function isDuplicate(int $conversationId, string $externalId): bool
    {
        return ConversationItem::query()
            ->where('conversation_id', $conversationId)
            ->where('external_id', $externalId)
            ->exists();
    }
}
