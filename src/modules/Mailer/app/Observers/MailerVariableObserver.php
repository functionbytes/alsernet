<?php

namespace Modules\Mailer\Observers;

use Modules\Mailer\Models\MailerVariable;
use Modules\Mailer\Services\MailerVariableValueService;

class MailerVariableObserver
{
    /**
     * Handle the MailerVariable "created" event.
     */
    public function created(MailerVariable $mailerVariable): void
    {
        MailerVariableValueService::clearCache();
    }

    /**
     * Handle the MailerVariable "updated" event.
     */
    public function updated(MailerVariable $mailerVariable): void
    {
        MailerVariableValueService::clearCache();
    }

    /**
     * Handle the MailerVariable "deleted" event.
     */
    public function deleted(MailerVariable $mailerVariable): void
    {
        MailerVariableValueService::clearCache();
    }

    /**
     * Handle the MailerVariable "restored" event.
     */
    public function restored(MailerVariable $mailerVariable): void
    {
        MailerVariableValueService::clearCache();
    }

    /**
     * Handle the MailerVariable "force deleted" event.
     */
    public function forceDeleted(MailerVariable $mailerVariable): void
    {
        MailerVariableValueService::clearCache();
    }
}
