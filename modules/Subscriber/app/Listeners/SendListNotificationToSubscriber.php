<?php

namespace App\Listeners\Subscribers;

use Modules\Campaign\Events\MailListSubscription;
use Modules\Campaign\Events\MailListUnsubscription;

class SendListNotificationToSubscriber
{
    public function __construct() {}

    public function handleMailListSubscription(MailListSubscription $event)
    {
        $subscriber = $event->subscriber;
        $list = $subscriber->mailList;

        if ($list->send_welcome_email) {
            $list->sendSubscriptionWelcomeEmail($subscriber);
        }
    }

    public function handleMailListUnsubscription(MailListUnsubscription $event)
    {
        $subscriber = $event->subscriber;
        $list = $subscriber->mailList;

        if ($list->unsubscribe_notification) {
            $list->sendUnsubscriptionNotificationEmail($subscriber);
        }
    }

    public function subscribe($events)
    {
        $events->listen(
            MailListSubscription::class,
            [SendListNotificationToSubscriber::class, 'handleMailListSubscription']
        );

        $events->listen(
            MailListUnsubscription::class,
            [SendListNotificationToSubscriber::class, 'handleMailListUnsubscription']
        );
    }
}
