<?php

namespace Modules\Mailing\Observers;

use Modules\Mailing\Models\Mailing\MailingVariable;
use Modules\Mailing\Services\MailingVariableValueService;

class MailingVariableObserver
{
    /**
     * Handle the MailerVariable "created" event.
     */
    public function created(MailingVariable $mailerVariable): void
    {
        MailingVariableValueService::clearCache();
    }

    /**
     * Handle the MailerVariable "updated" event.
     */
    public function updated(MailingVariable $mailerVariable): void
    {
        MailingVariableValueService::clearCache();
    }

    /**
     * Handle the MailerVariable "deleted" event.
     */
    public function deleted(MailingVariable $mailerVariable): void
    {
        MailingVariableValueService::clearCache();
    }

    /**
     * Handle the MailerVariable "restored" event.
     */
    public function restored(MailingVariable $mailerVariable): void
    {
        MailingVariableValueService::clearCache();
    }

    /**
     * Handle the MailerVariable "force deleted" event.
     */
    public function forceDeleted(MailingVariable $mailerVariable): void
    {
        MailingVariableValueService::clearCache();
    }
}
