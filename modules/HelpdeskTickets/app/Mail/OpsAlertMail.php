<?php

namespace Modules\HelpdeskTickets\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alerta operativa del helpdesk (colas, dead-letters, breaches SLA) para
 * managers. Encolado en 'emails' como el resto de correos del módulo. El
 * cuerpo llega ya construido por CollectOpsMetricsCommand — este Mailable
 * solo transporta el HTML final, mismo criterio que SlaBreachMail.
 */
class OpsAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $emailSubject,
        public readonly string $emailContent,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->emailContent);
    }
}
