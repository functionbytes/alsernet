<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\MailListUnsubscription;
use Illuminate\Support\Facades\Mail;
use App\Events\MailListSubscription;
use App\Models\Setting;

class SendListNotificationToOwner
{

    public function __construct()
    {
    }

    public function handleMailListSubscription(MailListSubscription $event)
    {
        $subscriber = $event->subscriber;
        $list = $subscriber->mailList;
        $user = $list->customer->user;

        if (Setting::isYes('send_notification_email_for_list_subscription')) {
            $list->sendSubscriptionNotificationEmailToListOwner($subscriber);
        }
    }

    public function handleMailListUnsubscription(MailListUnsubscription $event)
    {
        $subscriber = $event->subscriber;
        $list = $subscriber->mailList;

        if (Setting::isYes('send_notification_email_for_list_subscription')) {
            $list->sendUnsubscriptionNotificationEmailToListOwner($subscriber);
        }
    }

    public function subscribe($events)
    {
        $events->listen(
            'App\Events\MailListSubscription',
            [SendListNotificationToOwner::class, 'handleMailListSubscription']
        );

        $events->listen(
            'App\Events\MailListUnsubscription',
            [SendListNotificationToOwner::class, 'handleMailListUnsubscription']
        );
    }

}
