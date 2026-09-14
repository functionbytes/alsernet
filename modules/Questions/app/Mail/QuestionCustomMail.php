<?php

namespace Modules\Questions\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskEmailActivity\Contracts\TracksEmailLog;
use Modules\HelpdeskEmailActivity\Mail\AddsEmailLogHeaders;
use Modules\Questions\Models\Question;

/**
 * Transporta un correo de consulta ya renderizado desde su MailerTemplate.
 *
 * No trae vista propia a propósito: el diseño vive en el catálogo de Mailer
 * (ver QuestionsEmailTemplatesSeeder) y QuestionMailer es quien lo resuelve.
 */
class QuestionCustomMail extends Mailable implements ShouldQueue, TracksEmailLog
{
    use AddsEmailLogHeaders, Queueable, SerializesModels;

    public function __construct(
        protected Question $question,
        protected string $emailSubject,
        protected string $emailContent
    ) {}

    /** Correlación con el log central de correos (modules/HelpdeskEmailActivity). */
    public function getEmailLogModule(): string
    {
        return 'Questions';
    }

    public function getEmailLogEntityType(): string
    {
        return Question::class;
    }

    public function getEmailLogEntityId(): int|string
    {
        return $this->question->id;
    }

    public function build()
    {
        return $this->subject($this->emailSubject)
            ->html($this->emailContent);
    }
}
