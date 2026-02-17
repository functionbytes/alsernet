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
 * Email enviado cuando se cierra un PQRSF
 *
 * Confirma el cierre del caso e informa que el proceso ha finalizado
 */
class AttentionClosedMail extends Mailable implements ShouldQueue
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
            subject: "PQRSF Cerrado - Radicado: {$this->attention->radicado}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'attention::emails.closed',
            with: [
                'attention' => $this->attention,
                'radicado' => $this->attention->radicado,
                'tipo' => $this->attention->type->name,
                'asunto' => $this->attention->subject,
                'resolucion' => $this->attention->resolution,
                'nombreCompleto' => $this->attention->full_name,
                'trackingUrl' => $this->trackingUrl,
                'fechaRadicacion' => $this->attention->created_at->format('d/m/Y H:i'),
                'fechaResolucion' => $this->attention->resolved_at?->format('d/m/Y H:i'),
                'fechaCierre' => $this->attention->closed_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
                'tieneCalificacion' => $this->attention->satisfaction_rating !== null,
                'calificacion' => $this->attention->satisfaction_rating,
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
