<?php

namespace Modules\Helpdesk\Services\Webhooks;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Events\InboxItemChanged;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;

class InstagramMessageProcessor
{
    public function __construct(
        private readonly InboundMessageIngestor $ingestor,
    ) {}

    /**
     * Process a parsed Instagram DM event and persist it as a ConversationItem.
     *
     * Delega a InboundMessageIngestor — ver nota en WhatsAppMessageProcessor.
     *
     * @param  array<string, mixed>  $event  Parsed event from InstagramService::parseWebhookPayload()
     */
    public function process(array $event): ?ConversationItem
    {
        if (! in_array($event['type'] ?? '', ['message', 'story_reply'], true)) {
            return null;
        }

        $igUserId = $event['ig_user_id'];
        $body = $this->resolveBody($event);

        $customer = Customer::firstOrCreate(
            ['instagram_id' => $igUserId],
            ['name' => $igUserId, 'language' => 'es'],
        );

        $item = $this->ingestor->ingest('instagram', $igUserId, $customer, [
            'body' => $body,
            'external_id' => $event['message_id'],
            'metadata' => array_filter([
                'attachments' => $event['attachments'] ?? null,
                'is_ephemeral' => $event['is_ephemeral'] ?? null,
                'story_url' => $event['story_url'] ?? null,
                'platform' => 'instagram',
                'raw' => $event,
            ]),
        ]);

        if ($item === null) {
            return null;
        }

        DB::connection('helpdesk')->table('helpdesk_conversations')
            ->where('id', $item->conversation_id)
            ->update(['last_message_at' => now()]);

        $conversation = $item->conversation;
        if ($conversation->assignee_id) {
            event(new InboxItemChanged($conversation->id, $conversation->assignee_id, 'message_added'));
        }

        return $item;
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
