<?php

namespace Modules\Attention\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Attention\Models\Attention;

/**
 * Email sent to citizen with peticiones resolution
 */
class AttentionResolutionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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
            subject: "Respuesta a su solicitud {$this->attention->radicado}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'attention::emails.resolution',
            with: [
                'radicado' => $this->attention->radicado,
                'type' => $this->attention->type->name ?? 'N/A',
                'subject' => $this->attention->subject,
                'resolution' => $this->attention->resolution,
                'response_type' => $this->attention->response_type?->label(),
                'resolved_at' => $this->attention->resolved_at->format('d/m/Y H:i'),
                'satisfaction_url' => route('api.peticiones.satisfaction', ['radicado' => $this->attention->radicado]),
            ],
        );
    }
}
