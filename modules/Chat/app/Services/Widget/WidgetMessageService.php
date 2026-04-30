<?php

namespace Modules\Chat\Services\Widget;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Events\NewMessageEvent;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Customers\Customer;

class WidgetMessageService
{
    /**
     * Get messages for a conversation.
     */
    public function getMessages(int $conversationId): array
    {
        $conversation = Conversation::find($conversationId);

        if (! $conversation) {
            throw new \Exception('Conversation not found');
        }

        $messages = ConversationMessage::where('conversation_id', $conversationId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'content' => $message->content,
                'message_type' => $message->message_type,
                'content_type' => $message->content_type,
                'content_attributes' => $message->content_attributes,
                'created_at' => $message->created_at->toIso8601String(),
                'sender' => [
                    'id' => $message->sender_id,
                    'type' => class_basename($message->sender_type),
                    'name' => $message->sender?->name ?? 'Unknown',
                ],
            ]);

        return [
            'conversation_id' => $conversationId,
            'messages' => $messages,
            'total' => $messages->count(),
        ];
    }

    /**
     * Send a text message.
     */
    public function sendMessage(int $conversationId, array $data): array
    {
        $conversation = Conversation::with(['customer', 'inbox'])->find($conversationId);

        if (! $conversation) {
            throw new \Exception('Conversation not found');
        }

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'inbox_id' => $conversation->inbox_id,
            'account_id' => $conversation->account_id,
            'sender_id' => $conversation->customer_id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content' => $data['content'],
            'content_type' => $data['content_type'] ?? 'text',
            'content_attributes' => $data['attachments'] ?? [],
        ]);

        $conversation->update([
            'last_activity_at' => now(),
            'last_message_at' => now(),
        ]);

        event(new NewMessageEvent($message));

        return [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * Upload file and create file message.
     */
    public function uploadFile(int $conversationId, UploadedFile $file): array
    {
        $conversation = Conversation::find($conversationId);

        if (! $conversation) {
            throw new \Exception('Conversation not found');
        }

        $filename = time().'_'.$file->getClientOriginalName();
        $path = $file->storeAs('widget-uploads/'.$conversation->account_id, $filename, 'public');
        $fileUrl = Storage::url($path);
        $fileType = $this->getFileType($file->getMimeType());

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'inbox_id' => $conversation->inbox_id,
            'account_id' => $conversation->account_id,
            'sender_id' => $conversation->customer_id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content' => $file->getClientOriginalName(),
            'content_type' => $fileType,
            'content_attributes' => [
                'file_url' => $fileUrl,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ],
        ]);

        $conversation->update(['last_activity_at' => now()]);

        return [
            'message_id' => $message->id,
            'file_url' => $fileUrl,
            'file_name' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Mark conversation as read from customer side.
     */
    public function markAsRead(int $conversationId): void
    {
        $conversation = Conversation::find($conversationId);

        if (! $conversation) {
            throw new \Exception('Conversation not found');
        }

        ConversationMessage::where('conversation_id', $conversation->id)
            ->where('message_type', 'outgoing')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Determine file type from MIME type.
     */
    protected function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'file';
    }
}
