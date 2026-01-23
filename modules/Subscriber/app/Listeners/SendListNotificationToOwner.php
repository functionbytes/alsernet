<?php

namespace App\Listeners\Subscribers;

use Modules\Campaign\Events\MailListSubscription;
use Modules\Campaign\Events\MailListUnsubscription;
use Modules\Core\Models\Setting;

class SendListNotificationToOwner
{
    public function __construct() {}

    public function handleMailListSubscription(MailListSubscription $event)
    {
        $subscriber = $event->subscriber;
        $list = $subscriber->mailList;
        $user = $list->customer->user;

        if (Setting::isYes('send_notification_mail_for_list_subscription')) {
            $list->sendSubscriptionNotificationEmailToListOwner($subscriber);
        }
    }

    public function handleMailListUnsubscription(MailListUnsubscription $event)
    {
        $subscriber = $event->subscriber;
        $list = $subscriber->mailList;

        if (Setting::isYes('send_notification_mail_for_list_subscription')) {
            $list->sendUnsubscriptionNotificationEmailToListOwner($subscriber);
        }
    }

    public function subscribe($events)
    {
        $events->listen(
            MailListSubscription::class,
            [SendListNotificationToOwner::class, 'handleMailListSubscription']
        );

        $events->listen(
            MailListUnsubscription::class,
            [SendListNotificationToOwner::class, 'handleMailListUnsubscription']
        );
    }
}
