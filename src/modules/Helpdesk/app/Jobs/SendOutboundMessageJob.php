<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\OutboundMessageService;

/**
 * Send an agent reply (text + attachments) to the customer's external channel
 * (WhatsApp / Facebook / Instagram) off the HTTP request thread.
 *
 * The Graph/WhatsApp APIs use 15s timeouts with retries; running them inline
 * exhausts PHP-FPM workers under load. Text and attachments are pre-resolved by
 * the caller (absolute URLs, stripped body) so this job only performs the I/O
 * and correlates the returned external message id back onto the item.
 */
class SendOutboundMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 10;

    /**
     * @param  array<int, array{type?: string, url: string, name?: ?string}>  $attachments
     * @param  string  $correlation  'external_id' (item column) or 'metadata' (outbound_message_id)
     */
    public function __construct(
        private readonly int $conversationId,
        private readonly int $itemId,
        private readonly ?string $body,
        private readonly array $attachments = [],
        private readonly string $correlation = 'external_id',
    ) {
        $this->onQueue('helpdesk');
    }

    public function handle(OutboundMessageService $outbound): void
    {
        $conversation = Conversation::find($this->conversationId);
        $item = ConversationItem::find($this->itemId);

        if (! $conversation || ! $item || ! $outbound->supports($conversation)) {
            return;
        }

        $externalId = null;

        if (filled($this->body)) {
            $externalId = $outbound->sendReply($conversation, $this->body);
        }

        foreach ($this->attachments as $attachment) {
            $url = (string) ($attachment['url'] ?? '');

            if (blank($url)) {
                continue;
            }

            $attExternalId = $outbound->sendAttachment(
                $conversation,
                (string) ($attachment['type'] ?? 'file'),
                $url,
                null,
                $attachment['name'] ?? null,
            );

            $externalId = $externalId ?: $attExternalId;
        }

        if (! $externalId) {
            return;
        }

        if ($this->correlation === 'metadata') {
            $item->update(['metadata' => array_merge($item->metadata ?? [], [
                'outbound_message_id' => $externalId,
                'sent_via' => $conversation->channel,
            ])]);

            return;
        }

        $item->external_id = $externalId;
        $item->save();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendOutboundMessageJob failed', [
            'conversation_id' => $this->conversationId,
            'item_id' => $this->itemId,
            'error' => $exception->getMessage(),
        ]);
    }
}
