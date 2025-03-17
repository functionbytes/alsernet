<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\MailListUnsubscription;
use App\Events\MailListSubscription;

use App\Models\Automation\Automation;

class TriggerAutomation
{

    public function __construct()
    {

    }

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
