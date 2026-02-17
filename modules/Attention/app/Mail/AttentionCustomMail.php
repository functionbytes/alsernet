<?php

namespace Modules\Attention\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Attention\Models\Attention;
use Modules\Mailer\Models\MailerTemplate;

class AttentionCustomMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new mail instance.
     *
     * @param  Attention  $attention  Attention (used only for metadata, not for rendering)
     * @param  string  $emailSubject  Email subject (already processed with variables)
     * @param  string  $emailContent  Complete HTML content already rendered by AttentionEmailTemplateService
     * @param  MailerTemplate|null  $emailTemplate  Template used (optional, only for logging)
     */
    public function __construct(
        protected Attention $attention,
        protected string $emailSubject,
        protected string $emailContent,
        protected ?MailerTemplate $emailTemplate = null
    ) {
        // No need to prepare variables - everything comes already rendered
    }

    /**
     * Build the message.
     *
     * Content ALWAYS comes already rendered as complete HTML from AttentionEmailTemplateService.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->emailSubject)
            ->html($this->emailContent);
    }
}
