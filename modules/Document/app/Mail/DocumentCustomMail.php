<?php

namespace Modules\Document\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Document\Entities\Document;
use Modules\Mailer\Models\MailerTemplate;

class DocumentCustomMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Crear nueva instancia del mail.
     *
     * @param  Document  $document  Documento (usado solo para metadatos, no para renderizado)
     * @param  string  $emailSubject  Asunto del email (ya procesado con variables)
     * @param  string  $emailContent  Contenido HTML completo ya renderizado por DocumentEmailTemplateService
     * @param  MailerTemplate|null  $emailTemplate  Template usado (opcional, solo para logging)
     */
    public function __construct(
        protected Document $document,
        protected string $emailSubject,
        protected string $emailContent,
        protected ?MailerTemplate $emailTemplate = null
    ) {
        // No es necesario preparar variables - todo viene ya renderizado
    }

    /**
     * Build the message.
     *
     * El contenido SIEMPRE viene ya renderizado como HTML completo desde DocumentEmailTemplateService.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->emailSubject)
            ->html($this->emailContent);
    }
}
