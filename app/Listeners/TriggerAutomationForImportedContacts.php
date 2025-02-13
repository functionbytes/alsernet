<?php

namespace App\Listeners;

use App\Events\MailListImported;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Models\Automation\Automation;
use App\Models\Setting;

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
     * @param  MailListImported  $event
     * @return void
     */
    public function handle(MailListImported $event)
    {
        $trigger = Setting::isYes('automation.trigger_imported_contacts');

        $automations = $event->list->automations;
        foreach ($automations as $auto) {
            if ($auto->getTriggerType() != Automation::TRIGGER_TYPE_WELCOME_NEW_SUBSCRIBER) {
                continue;
            }

            if (!$trigger) {
                $auto->logger()->warning("Do not trigger automation for imported contacts");
                continue;
            }

            if (!$auto->isActive()) {
                $auto->logger()->warning("Automation is INACTIVE");
                continue;
            }

            $auto->triggerImportedContacts($event->importBatchId);
        };
    }
}
