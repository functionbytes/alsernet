<?php

namespace Modules\Helpdesk\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;

class ContinueConversationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $token,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        $inboxName = $this->conversation->relationLoaded('inbox')
            ? ($this->conversation->inbox?->name ?? config('app.name'))
            : config('app.name');

        return new Envelope(
            subject: 'Continúa tu conversación con '.$inboxName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'helpdesk::emails.continue-conversation',
            with: [
                'conversation' => $this->conversation,
                'token' => $this->token,
                'continueUrl' => route('public.helpdesk.continue', ['token' => $this->token]),
            ],
        );
    }
}
