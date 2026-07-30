<?php

namespace Modules\Helpdesk\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;

class ConversationExportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $attachmentContent,
        public readonly string $attachmentFilename,
        public readonly string $attachmentMime,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Exportación: '.($this->conversation->subject ?: 'Conversación #'.$this->conversation->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'helpdesk::emails.conversation-export',
            with: [
                'conversation' => $this->conversation,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->attachmentContent, $this->attachmentFilename)
                ->withMime($this->attachmentMime),
        ];
    }
}
