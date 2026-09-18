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

    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $recipientEmail,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Transcript de tu conversación');
    }

    public function content(): Content
    {
        return new Content(view: 'helpdesklivechat::emails.conversation-transcript');
    }
}
