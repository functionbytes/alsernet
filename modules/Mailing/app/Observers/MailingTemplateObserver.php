<?php

namespace Modules\Mailer\Observers;

use Modules\Mailer\Models\MailingTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;
use Modules\Mailer\Services\MailerVariableValueService;

class MailerTemplateObserver
{
    /**
     * Handle the MailerTemplate "created" event.
     */
    public function created(MailingTemplate $mailerTemplate): void
    {
        $this->clearAllCaches();
    }

    /**
     * Handle the MailerTemplate "updated" event.
     */
    public function updated(MailingTemplate $mailerTemplate): void
    {
        $this->clearAllCaches();
    }

    /**
     * Handle the MailerTemplate "deleted" event.
     */
    public function deleted(MailingTemplate $mailerTemplate): void
    {
        $this->clearAllCaches();
    }

    /**
     * Limpiar todos los cachés relacionados con templates
     */
    private function clearAllCaches(): void
    {
        MailerTemplateRendererService::clearCache();
        MailerVariableValueService::clearCache();
    }
}
