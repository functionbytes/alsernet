<?php

namespace Modules\Attention\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\Attention\Models\Attention;

/**
 * Email sent to citizen confirming PQRSF submission
 */
class AttentionConfirmationMail extends Mailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(
        public Attention $attention
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Confirmación de radicado {$this->attention->radicado}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'attention::emails.confirmation',
            with: [
                'radicado' => $this->attention->radicado,
                'type' => $this->attention->type->name ?? 'N/A',
                'subject' => $this->attention->subject,
                'created_at' => $this->attention->created_at->format('d/m/Y H:i'),
                'tracking_url' => route('pqrsf.tracking.result', $this->attention->radicado),
            ],
        );
    }
}
