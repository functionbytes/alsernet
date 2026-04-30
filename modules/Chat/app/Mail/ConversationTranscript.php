<?php

namespace Modules\Chat\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Chat\Models\Conversations\Conversation;

class ConversationTranscript extends Mailable
{
    use Queueable, SerializesModels;

    public Conversation $conversation;

    public string $pdfContent;

    public ?string $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(Conversation $conversation, string $pdfContent, ?string $customMessage = null)
    {
        $this->conversation = $conversation;
        $this->pdfContent = $pdfContent;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Transcripción de Conversación #%d - %s', $this->conversation->id, config('app.name')),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.conversation-transcript',
            with: [
                'conversation' => $this->conversation,
                'customMessage' => $this->customMessage,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, sprintf('conversacion_%d.pdf', $this->conversation->id))
                ->withMime('application/pdf'),
        ];
    }
}
