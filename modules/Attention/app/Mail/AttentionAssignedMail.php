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
 * Email enviado al usuario/departamento cuando se le asigna un peticiones
 *
 * Notifica la asignación y proporciona acceso directo al caso
 */
class AttentionAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Attention $attention;

    public string $manageUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Attention $attention)
    {
        $this->attention = $attention;
        $this->manageUrl = route('attention.show', ['attention' => $attention->uid]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nuevo peticiones Asignado - Radicado: {$this->attention->radicado}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $assignedTo = null;

        if ($this->attention->assigned_user_id) {
            $assignedTo = $this->attention->assignedUser->name;
        } elseif ($this->attention->department_id) {
            $assignedTo = $this->attention->department->name;
        }

        return new Content(
            markdown: 'attention::emails.assigned',
            with: [
                'attention' => $this->attention,
                'radicado' => $this->attention->radicado,
                'tipo' => $this->attention->type->name,
                'categoria' => $this->attention->category->name,
                'asunto' => $this->attention->subject,
                'descripcion' => $this->attention->description,
                'ciudadano' => $this->attention->full_name,
                'asignadoA' => $assignedTo,
                'manageUrl' => $this->manageUrl,
                'fechaRadicacion' => $this->attention->created_at->format('d/m/Y H:i'),
                'prioridad' => $this->attention->type->name === 'QUEJA' || $this->attention->type->name === 'RECLAMO' ? 'Alta' : 'Normal',
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
