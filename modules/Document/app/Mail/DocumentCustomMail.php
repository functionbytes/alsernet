<?php

namespace Modules\Document\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Document\Entities\Document;
use Modules\HelpdeskEmailLog\Contracts\TracksEmailLog;
use Modules\HelpdeskEmailLog\Mail\AddsEmailLogHeaders;
use Modules\Mailer\Models\MailerTemplate;

class DocumentCustomMail extends Mailable implements TracksEmailLog
{
    use AddsEmailLogHeaders, Queueable, SerializesModels;

    /**
     * Crear nueva instancia del mail.
     *
     * @param  Document  $document  Documento (usado solo para metadatos, no para renderizado)
     * @param  string  $emailSubject  Asunto del email (ya procesado con variables)
     * @param  string  $emailContent  Contenido HTML completo ya renderizado por DocumentEmailTemplateService
     * @param  MailerTemplate|null  $emailTemplate  Template usado (opcional, solo para logging)
     * @param  string|null  $documentMailUid  uid de la fila document_mails de ESTE envío concreto
     *                                        (no del documento). Permite correlación exacta 1:1 con
     *                                        el EmailLog central; null si el caller aún no lo crea antes.
     */
    public function __construct(
        protected Document $document,
        protected string $emailSubject,
        protected string $emailContent,
        protected ?MailerTemplate $emailTemplate = null,
        protected ?string $documentMailUid = null
    ) {
        // No es necesario preparar variables - todo viene ya renderizado
    }

    /**
     * Correlación con el log central de emails (modules/HelpdeskEmailLog).
     * Permite filtrar/ver desde EmailLog todos los correos enviados por un documento
     * concreto (module=Document, entity_id=document->id), sin depender de document_mails.
     */
    public function getEmailLogModule(): string
    {
        return 'Document';
    }

    public function getEmailLogEntityType(): string
    {
        return Document::class;
    }

    public function getEmailLogEntityId(): int|string
    {
        return $this->document->id;
    }

    /**
     * uid de la fila document_mails de este envío concreto (correlación exacta 1:1).
     * Si no se pasó (caller antiguo), cae al uid del documento como antes.
     * Cast explícito: un uid recién creado (HasUid, sin recargar de BD) es un objeto
     * Ramsey\Uuid, no un string; Symfony Mime lo stringifica igual al construir el
     * mensaje, pero forzarlo aquí evita depender de esa conversión implícita.
     */
    public function getEmailLogExternalId(): ?string
    {
        return (string) ($this->documentMailUid ?? $this->document->uid);
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
