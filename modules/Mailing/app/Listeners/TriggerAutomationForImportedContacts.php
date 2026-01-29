<?php

namespace Modules\Mailing\Listeners;

use Modules\Mailing\Events\MailListImported;
use Modules\Mailing\Models\Automation2;
use Modules\Mailing\Models\Setting;

class TriggerAutomationForImportedContacts
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(MailListImported $event)
    {
        $trigger = Setting::isYes('automation.trigger_imported_contacts');

        $automations = $event->list->automations;
        foreach ($automations as $auto) {
            if ($auto->getTriggerType() != Automation2::TRIGGER_TYPE_WELCOME_NEW_SUBSCRIBER) {
                continue;
            }

            if (! $trigger) {
                $auto->logger()->warning('Do not trigger automation for imported contacts');

                continue;
            }

            if (! $auto->isActive()) {
                $auto->logger()->warning('Automation is INACTIVE');

                continue;
            }

            $auto->triggerImportedContacts($event->importBatchId);
        }
    }
}
