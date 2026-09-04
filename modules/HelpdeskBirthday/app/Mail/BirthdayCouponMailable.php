<?php

namespace Modules\HelpdeskBirthday\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskEmailActivity\Contracts\TracksEmailLog;
use Modules\HelpdeskEmailActivity\Mail\AddsEmailLogHeaders;

/**
 * Felicitación de cumpleaños con el cupón del día.
 *
 * El asunto y el HTML llegan ya renderizados por BirthdayMailRenderer desde la
 * plantilla del módulo Mailer; esta clase solo los transporta y aporta los
 * datos del registro de envíos.
 *
 * Implementar TracksEmailLog es lo que hace que la fila de email_logs quede con
 * módulo y entidad: el listener LogEmailQueued registra todo correo saliente de
 * todos modos, pero sin las cabeceras no sabría de quién es.
 *
 * NO es ShouldQueue, a diferencia de TicketMailable: aquí quien envía ya es un
 * job encolado y con throttle (SendBirthdayEmailJob). Si además el Mailable se
 * encolara, el envío real saldría en un segundo job que no pasa por ese
 * throttle y el ritmo calculado se perdería — justo lo que este módulo existe
 * para garantizar. Enviándolo síncrono dentro del job, un fallo de SMTP también
 * queda anotado en el destinatario en vez de perderse.
 */
class BirthdayCouponMailable extends Mailable implements TracksEmailLog
{
    use AddsEmailLogHeaders, SerializesModels;

    public function __construct(
        public readonly BirthdayRecipient $recipient,
        public readonly string $emailSubject,
        public readonly string $emailContent,
    ) {}

    public function getEmailLogModule(): string
    {
        return 'HelpdeskBirthday';
    }

    public function getEmailLogEntityType(): string
    {
        return BirthdayRecipient::class;
    }

    public function getEmailLogEntityId(): int|string
    {
        return $this->recipient->id;
    }

    public function getEmailLogExternalId(): ?string
    {
        return $this->recipient->erp_customer_id;
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
