<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Conversation;

use App\Models\Channels\Email;
use App\Models\Channels\FacebookPage;
use App\Models\Channels\Instagram;
use App\Models\Channels\Whatsapp;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Jobs\Channels\SendEmailMessage;
use Modules\HelpdeskChat\Jobs\Channels\SendFacebookMessage;
use Modules\HelpdeskChat\Jobs\Channels\SendInstagramMessage;
use Modules\HelpdeskChat\Jobs\Channels\SendWhatsappMessage;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class MessageController extends Controller
{
    /**
     * Store a new message in a conversation.
     */
    public function store(\App\Http\Requests\StoreFileRequest $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'content' => 'nullable|string|max:10000',
            'private' => 'boolean',
        ]);

        // At least content or attachments must be present
        if (empty($validated['content']) && ! $request->hasFile('attachments')) {
            return back()->withErrors(['content' => 'Message content or attachments are required.']);
        }

        // Private notes don't get sent to external channels
        $isPrivate = $validated['private'] ?? false;

        // Determine content type based on first attachment's MIME type
        $contentType = 'text';
        if ($request->hasFile('attachments')) {
            $firstFile = $request->file('attachments')[0];
            $mimeType = $firstFile->getMimeType();

            if (str_starts_with($mimeType, 'image/')) {
                $contentType = 'image';
            } elseif (str_starts_with($mimeType, 'audio/')) {
                $contentType = 'audio';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $contentType = 'video';
            } else {
                $contentType = 'file';
            }
        }

        $message = Message::create([
            'account_id' => $conversation->account_id,
            'inbox_id' => $conversation->inbox_id,
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'sender_type' => get_class($request->user()),
            'message_type' => 'outgoing',
            'content_type' => $contentType,
            'content' => $validated['content'] ?? '',
            'private' => $isPrivate,
            'status' => $isPrivate ? 'sent' : 'pending',
        ]);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $message->addMedia($file)
                    ->withCustomProperties(['uploaded_by' => $request->user()->id])
                    ->toMediaCollection('attachments');
            }
        }

        // Update conversation last activity
        $conversation->updateLastActivity();

        // Reopen conversation if it was resolved
        if ($conversation->isResolved()) {
            $conversation->markAsOpen();
        }

        // Dispatch job to send message to external channel (unless it's a private note)
        if (! $isPrivate) {
            $this->dispatchMessageJob($message, $conversation);
        }

        // Broadcast the message to connected clients (with media loaded)
        broadcast(new \App\Events\Messages\MessageSent($message->load('sender', 'media')))->toOthers();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message->load('sender', 'media'),
                'success' => true,
            ]);
        }

        return back()->with('success', 'Message sent successfully!');
    }

    /**
     * Dispatch the appropriate job based on channel type.
     */
    protected function dispatchMessageJob(Message $message, Conversation $conversation): void
    {
        $inbox = $conversation->inbox;
        $channel = $inbox->channel;
        $contactInbox = $conversation->contactInbox;

        if (! $contactInbox) {
            Log::error('Cannot send message: ContactInbox not found', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
            ]);
            $message->update(['status' => 'failed']);

            return;
        }

        $recipientId = $contactInbox->source_id;

        try {
            match ($inbox->channel_type) {
                FacebookPage::class => SendFacebookMessage::dispatch($message, $channel, $recipientId),
                Instagram::class => SendInstagramMessage::dispatch($message, $channel, $recipientId),
                Whatsapp::class => SendWhatsappMessage::dispatch($message, $channel, $recipientId),
                Email::class => SendEmailMessage::dispatch(
                    $message,
                    $channel,
                    $contactInbox->source_id, // recipient email address
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
