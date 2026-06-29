<?php

namespace Modules\Mailer\Observers;

use Modules\Mailer\Models\MailerLayout;
use Modules\Mailer\Services\MailerTemplateRendererService;
use Modules\Mailer\Services\MailerVariableValueService;

class MailerLayoutObserver
{
    /**
     * Handle the MailerLayout "created" event.
     */
    public function created(MailerLayout $mailerLayout): void
    {
        $this->clearAllCaches();
    }

    /**
     * Handle the MailerLayout "updated" event.
     */
    public function updated(MailerLayout $mailerLayout): void
    {
        $this->clearAllCaches();
    }

    /**
     * Handle the MailerLayout "deleted" event.
     */
    public function deleted(MailerLayout $mailerLayout): void
    {
        $this->clearAllCaches();
    }

    /**
     * Limpiar todos los cachés relacionados con layouts
     */
    private function clearAllCaches(): void
    {
        MailerTemplateRendererService::clearCache();
        MailerVariableValueService::clearCache();
    }
}
