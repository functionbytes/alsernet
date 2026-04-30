<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Conversations;

use App\Http\Requests\StoreFileRequest;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Jobs\Channels\SendEmailMessage;
use Modules\Chat\Jobs\Channels\SendFacebookMessage;
use Modules\Chat\Jobs\Channels\SendInstagramMessage;
use Modules\Chat\Jobs\Channels\SendWhatsappMessage;
use Modules\Chat\Models\Channels\Email;
use Modules\Chat\Models\Channels\Facebook;
use Modules\Chat\Models\Channels\Instagram;
use Modules\Chat\Models\Channels\Whatsapp;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Services\Messages\TemplateRenderer;

class MessageController extends Controller
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
    ) {}

    /**
     * Store a new message in a conversation.
     */
    public function store(StoreFileRequest $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();

        $isPrivate = (bool) ($validated['private'] ?? false);

        $contentType = $this->resolveContentType($request);

        $content = $this->templateRenderer->renderContent(
            $validated['content'] ?? '',
            $conversation,
            $conversation->customer,
            $conversation->assignee,
            $conversation->account
        );

        $message = ConversationMessage::create([
            'account_id' => $conversation->account_id,
            'inbox_id' => $conversation->inbox_id,
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'sender_type' => get_class($request->user()),
            'message_type' => 'outgoing',
            'content_type' => $contentType,
            'content' => $content,
            'private' => $isPrivate,
            'status' => $isPrivate ? 'sent' : 'pending',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $message->addMedia($file)
                    ->withCustomProperties(['uploaded_by' => $request->user()->id])
                    ->toMediaCollection('attachments');
            }
        }

        $conversation->updateLastActivity();

        if ($conversation->isResolved()) {
            $conversation->markAsOpen();
        }

        if (! $isPrivate) {
            $this->dispatchMessageJob($message, $conversation);
        }

        $message->load('sender', 'media');
        broadcast(new MessageSent($message))->toOthers();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->formatMessageForJson($message),
                'success' => true,
            ]);
        }

        return back()->with('success', 'Message sent successfully!');
    }

    /**
     * Resolve the content type from the first uploaded attachment's MIME type.
     */
    private function resolveContentType(StoreFileRequest $request): string
    {
        if (! $request->hasFile('attachments')) {
            return 'text';
        }

        $mimeType = $request->file('attachments')[0]->getMimeType();

        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'audio/') => 'audio',
            str_starts_with($mimeType, 'video/') => 'video',
            default => 'file',
        };
    }

    /**
     * Format message for JSON response with attachments in the expected format.
     */
    private function formatMessageForJson(ConversationMessage $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_type' => $message->sender_type,
            'message_type' => $message->message_type,
            'content' => $message->content,
            'content_type' => $message->content_type,
            'status' => $message->status,
            'private' => $message->private,
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
            'sender' => $message->sender ? [
                'id' => $message->sender->id,
                'name' => $message->sender->name ?? $message->sender->firstname.' '.$message->sender->lastname,
            ] : null,
            'attachments' => $message->getMedia('attachments')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
            ])->values()->all(),
        ];
    }

    /**
     * Dispatch the appropriate job based on channel type.
     */
    protected function dispatchMessageJob(ConversationMessage $message, Conversation $conversation): void
    {
        $inbox = $conversation->inbox;
        $channel = $inbox->channel;
        $customerInbox = $conversation->customerInbox;

        if (! $customerInbox) {
            Log::warning('Cannot send message: CustomerInbox not found - marking as sent', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'customer_id' => $conversation->customer_id,
            ]);
            // Mark as sent since we can't dispatch but message was created successfully
            $message->update(['status' => 'sent']);

            return;
        }

        $recipientId = $customerInbox->source_id;

        try {
            match ($inbox->channel_type) {
                Facebook::class => SendFacebookMessage::dispatch($message, $channel, $recipientId),
                Instagram::class => SendInstagramMessage::dispatch($message, $channel, $recipientId),
                Whatsapp::class => SendWhatsappMessage::dispatch($message, $channel, $recipientId),
                Email::class => SendEmailMessage::dispatch(
                    $message,
                    $channel,
                    $customerInbox->source_id,
                    $conversation->custom_attributes['in_reply_to'] ?? null
                ),
                default => Log::warning('Unsupported channel type for message dispatch', [
                    'channel_type' => $inbox->channel_type,
                    'message_id' => $message->id,
                ]),
            };
        } catch (\Exception $e) {
            Log::error('Failed to dispatch message job', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
            $message->update(['status' => 'failed']);
        }
    }
}
