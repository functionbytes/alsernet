<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use App\Events\MailListSubscription;
use App\Events\MailListUnsubscription;
use App\Models\Setting;

class SendListNotificationToSubscriber
{

    public function __construct()
    {
        //
    }

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
            'App\Events\MailListSubscription',
            [SendListNotificationToSubscriber::class, 'handleMailListSubscription']
        );

        $events->listen(
            'App\Events\MailListUnsubscription',
            [SendListNotificationToSubscriber::class, 'handleMailListUnsubscription']
        );
    }

}
