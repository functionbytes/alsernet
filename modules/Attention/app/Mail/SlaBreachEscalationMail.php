<?php

namespace Modules\Attention\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionSlaBreach;

class SlaBreachEscalationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Attention $attention;

    public AttentionSlaBreach $breach;

    public string $recipientRole;

    public function __construct(Attention $attention, AttentionSlaBreach $breach, string $recipientRole = 'supervisor')
    {
        $this->attention = $attention;
        $this->breach = $breach;
        $this->recipientRole = $recipientRole;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ ALERTA SLA - Incumplimiento de plazo {$this->attention->radicado}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'attention::emails.sla-breach-escalation',
            with: [
                'radicado' => $this->attention->radicado,
                'tipo' => $this->attention->type->name,
                'categoria' => $this->attention->category->name ?? 'N/A',
                'ciudadano' => $this->attention->full_name,
                'asunto' => $this->attention->subject,
                'fechaRadicacion' => $this->attention->created_at->format('d/m/Y H:i'),
                'diasTranscurridos' => $this->attention->created_at->diffInDays(now()),
                'breachType' => $this->breach->breach_type,
                'breachedAt' => $this->breach->created_at->format('d/m/Y H:i'),
                'escalationLevel' => $this->breach->escalated ? 'ESCALADO' : 'PENDIENTE',
                'manageUrl' => route('attention.show', ['attention' => $this->attention->uid]),
                'urgency' => $this->getUrgencyLevel(),
            ],
        );
    }

    private function getUrgencyLevel(): string
    {
        $days = $this->attention->created_at->diffInDays(now());
        if ($days >= 20) {
            return 'CRÍTICA';
        }
        if ($days >= 15) {
            return 'ALTA';
        }

        return 'MEDIA';
    }
}
