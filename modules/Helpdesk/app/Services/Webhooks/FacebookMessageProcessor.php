<?php

namespace Modules\Helpdesk\Services\Webhooks;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Events\InboxItemChanged;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;

class FacebookMessageProcessor
{
    public function __construct(
        private readonly InboundMessageIngestor $ingestor,
    ) {}

    /**
     * Process a parsed Facebook Messenger event and persist it as a ConversationItem.
     *
     * Delega a InboundMessageIngestor — ver nota en WhatsAppMessageProcessor.
     *
     * @param  array<string, mixed>  $event  Parsed event from FacebookMessengerService::parseWebhookPayload()
     */
    public function process(array $event): ?ConversationItem
    {
        if (! in_array($event['type'] ?? '', ['message', 'postback'], true)) {
            return null;
        }

        $psid = $event['psid'];
        $body = $this->resolveBody($event);

        $customer = Customer::firstOrCreate(
            ['facebook_psid' => $psid],
            ['name' => $psid, 'language' => 'es'],
        );

        $item = $this->ingestor->ingest('facebook', $psid, $customer, [
            'body' => $body,
            'external_id' => $event['message_id'] ?? null,
            'metadata' => array_filter([
                'attachments' => $event['attachments'] ?? null,
                'quick_reply' => $event['quick_reply'] ?? null,
                'postback' => ($event['type'] === 'postback') ? ($event['payload'] ?? null) : null,
                'platform' => 'facebook',
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
