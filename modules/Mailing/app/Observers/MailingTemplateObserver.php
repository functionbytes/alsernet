<?php

namespace Modules\Mailing\Observers;

use Modules\Mailing\Models\MailingTemplate;
use Modules\Mailing\Services\MailingTemplateRendererService;
use Modules\Mailing\Services\MailingVariableValueService;

class MailingTemplateObserver
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
        MailingTemplateRendererService::clearCache();
        MailingVariableValueService::clearCache();
    }
}
