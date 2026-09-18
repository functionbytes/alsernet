<?php

namespace Modules\Backup\Mail;

use Illuminate\Mail\Mailable;

class BackupMail extends Mailable
{
    public function __construct(
        protected string $emailSubject,
        protected string $emailContent,
    ) {}

    public function build(): static
    {
        return $this->subject($this->emailSubject)
            ->html($this->emailContent);
    }
}
