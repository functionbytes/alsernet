<?php

namespace Modules\Mailing\Observers;

use Modules\Mailing\Models\Mailing\MailingLayout;
use Modules\Mailing\Services\MailingTemplateRendererService;
use Modules\Mailing\Services\MailingVariableValueService;

class MailingLayoutObserver
{
    /**
     * Handle the MailerLayout "created" event.
     */
    public function created(MailingLayout $mailerLayout): void
    {
        $this->clearAllCaches();
    }

    /**
     * Handle the MailerLayout "updated" event.
     */
    public function updated(MailingLayout $mailerLayout): void
    {
        $this->clearAllCaches();
    }

    /**
     * Handle the MailerLayout "deleted" event.
     */
    public function deleted(MailingLayout $mailerLayout): void
    {
        $this->clearAllCaches();
    }

    /**
     * Limpiar todos los cachés relacionados con layouts
     */
    private function clearAllCaches(): void
    {
        MailingTemplateRendererService::clearCache();
        MailingVariableValueService::clearCache();
    }
}
