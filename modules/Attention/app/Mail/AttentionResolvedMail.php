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
 * Email enviado al ciudadano cuando su PQRSF es resuelto
 *
 * Incluye la respuesta oficial e invitación a encuesta de satisfacción
 */
class AttentionResolvedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Attention $attention;

    public string $trackingUrl;

    public string $surveyUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Attention $attention)
    {
        $this->attention = $attention;
        $this->trackingUrl = route('attention.tracking', ['radicado' => $attention->radicado]);
        $this->surveyUrl = route('attention.survey', [
            'radicado' => $attention->radicado,
            'token' => $attention->uid,
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "PQRSF Resuelto - Radicado: {$this->attention->radicado}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'attention::emails.resolved',
            with: [
                'attention' => $this->attention,
                'radicado' => $this->attention->radicado,
                'tipo' => $this->attention->type->name,
                'asunto' => $this->attention->subject,
                'resolucion' => $this->attention->resolution,
                'tipoRespuesta' => $this->attention->response_type?->label() ?? 'Correo Electrónico',
                'nombreCompleto' => $this->attention->full_name,
                'trackingUrl' => $this->trackingUrl,
                'surveyUrl' => $this->surveyUrl,
                'fechaRadicacion' => $this->attention->created_at->format('d/m/Y H:i'),
                'fechaResolucion' => $this->attention->resolved_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        // Aquí se pueden adjuntar documentos de respuesta oficial si existen
        return [];
    }
}
