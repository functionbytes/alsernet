<?php

namespace Modules\HelpdeskLivechat\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;

class ConversationTranscriptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param  array<int, array{is_internal: bool, is_agent: bool, sender_name: string, body: string, created_at: ?string}>  $items
     *                                                                                                                               Plain, already-filtered (is_internal = false) snapshot of the conversation
     *                                                                                                                               messages. This is intentionally NOT read from `$conversation->items` in
     *                                                                                                                               the view: once queued, SerializesModels rehydrates $conversation on the
     *                                                                                                                               worker without the eager-load's `is_internal` constraint, which would
     *                                                                                                                               leak internal notes to the customer.
     */
    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $recipientEmail,
        public readonly array $items = [],
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Transcript de tu conversación');
    }

    public function content(): Content
    {
        return new Content(
            view: 'helpdesklivechat::emails.conversation-transcript',
            with: ['items' => $this->items],
        );
    }
}
