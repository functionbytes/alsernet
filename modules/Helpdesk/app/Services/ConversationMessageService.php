<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Http\UploadedFile;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

class ConversationMessageService
{
    public function __construct(private OutboundMessageService $outbound) {}

    /**
     * Store a new message in a conversation.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: ConversationItem, 1: string}
     */
    public function store(Conversation $conversation, array $data): array
    {
        $attachmentUrls = $this->processAttachments($data['attachments'] ?? []);

        $item = $conversation->items()->create([
            'user_id' => auth()->id(),
            'type' => 'message',
            'body' => $data['body'],
            'html_body' => nl2br(e($data['body'])),
            'is_internal' => $data['is_internal'] ?? false,
            'attachment_urls' => ! empty($attachmentUrls) ? $attachmentUrls : null,
        ]);

        if (empty($data['is_internal'])) {
            $externalMessageId = $this->outbound->sendReply($conversation, strip_tags($data['body']));

            if ($externalMessageId) {
                $item->update(['metadata' => array_merge($item->metadata ?? [], [
                    'outbound_message_id' => $externalMessageId,
                    'sent_via' => $conversation->channel,
                ])]);
            }
        }

        $this->updateConversationTimestamps($conversation);

        $shouldClose = ($data['action'] ?? null) === 'send_and_close';

        if ($shouldClose) {
            $conversation->close();
        }

        $successMessage = $shouldClose
            ? __('helpdesk::helpdesk.messages.conversation_message_sent_and_closed')
            : __('helpdesk::helpdesk.messages.conversation_message_sent');

        return [$item, $successMessage];
    }

    /**
     * @param  UploadedFile[]  $files
     * @return array<int, array{name: string, url: string, size: int, mime: string}>
     */
    private function processAttachments(array $files): array
    {
        $urls = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('helpdesk/attachments', 'public');
            $urls[] = [
                'name' => $file->getClientOriginalName(),
                'url' => asset('storage/'.$path),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        return $urls;
    }

    private function updateConversationTimestamps(Conversation $conversation): void
    {
        $data = ['last_message_at' => now()];
        if (! $conversation->first_response_at) {
            $data['first_response_at'] = now();
        }
        $conversation->update($data);
    }
}
