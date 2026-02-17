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
 * Email enviado al ciudadano cuando radica un PQRSF
 *
 * Confirma la recepción y proporciona información de seguimiento
 */
class AttentionReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Attention $attention;

    public string $trackingUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Attention $attention)
    {
        $this->attention = $attention;
        $this->trackingUrl = route('attention.tracking', ['radicado' => $attention->radicado]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "PQRSF Recibido - Radicado: {$this->attention->radicado}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'attention::emails.received',
            with: [
                'attention' => $this->attention,
                'radicado' => $this->attention->radicado,
                'tipo' => $this->attention->type->name,
                'categoria' => $this->attention->category->name,
                'asunto' => $this->attention->subject,
                'nombreCompleto' => $this->attention->full_name,
                'trackingUrl' => $this->trackingUrl,
                'fechaRadicacion' => $this->attention->created_at->format('d/m/Y H:i'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
