<?php

namespace Modules\HelpdeskTickets\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Informe periódico del helpdesk (resumen de tickets, CSAT y salud operativa)
 * para managers. Encolado en 'emails' como el resto de correos del módulo. El
 * cuerpo llega ya construido por SendScheduledReportsCommand — este Mailable
 * solo transporta el HTML final y el CSV opcional, mismo criterio que
 * OpsAlertMail / SlaBreachMail.
 */
class ScheduledReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $emailSubject,
        public readonly string $emailContent,
        public readonly ?string $csvContent = null,
        public readonly ?string $csvFilename = null,
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

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->csvContent === null) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->csvContent, $this->csvFilename ?? 'tickets.csv')
                ->withMime('text/csv'),
        ];
    }
}
