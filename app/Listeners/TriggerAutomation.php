<?php

namespace App\Listeners;

use App\Events\MailListSubscription;
use App\Events\MailListUnsubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Models\Automation\Automation;

class TriggerAutomation
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
     * @param  MailListSubscription  $event
     * @return void
     */
    public function handleMailListSubscription(MailListSubscription $event)
    {
        $automations = $event->subscriber->mailList->automations;
        $automations = $automations->filter(function ($auto, $key) {
            return $auto->isActive() && (
                $auto->getTriggerType() == Automation::TRIGGER_TYPE_WELCOME_NEW_SUBSCRIBER
            );
        });

        foreach ($automations as $auto) {
            if (is_null($auto->getAutoTriggerFor($event->subscriber))) {
                $segments = $auto->getSegments();

                // If there is no segment condition, trigger the contact
                if ($segments->isEmpty()) {
                    $auto->initTrigger($event->subscriber);
                    return;
                }

                $matched = false;
                foreach ($segments as $segment) {
                    if ($segment->isSubscriberIncluded($event->subscriber)) {
                        $matched = true;
                        break;
                    }
                }

                if ($matched) {
                    $auto->initTrigger($event->subscriber);
                }
            }
        }
    }

    /**
     * Handle the event.
     *
     * @param  MailListSubscription  $event
     * @return void
     */
    public function handleMailListUnsubscription(MailListUnsubscription $event)
    {
        $automations = $event->subscriber->mailList->automations;
        $automations = $automations->filter(function ($auto, $key) {
            return $auto->isActive() && (
                $auto->getTriggerType() == Automation::TRIGGER_TYPE_SAY_GOODBYE_TO_SUBSCRIBER
            );
        });

        foreach ($automations as $auto) {
            if (is_null($auto->getAutoTriggerFor($event->subscriber))) {
                $forceTriggerUnsubscribedContact = true;
                $auto->initTrigger($event->subscriber, $forceTriggerUnsubscribedContact);
            }
        }
    }

    // Subscribe to many events
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\MailListSubscription',
            [TriggerAutomation::class, 'handleMailListSubscription']
        );

        $events->listen(
            'App\Events\MailListUnsubscription',
            [TriggerAutomation::class, 'handleMailListUnsubscription']
        );
    }
}
