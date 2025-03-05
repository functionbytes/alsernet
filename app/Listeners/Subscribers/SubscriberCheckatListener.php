<?php

namespace App\Listeners\Subscribers;

use App\Jobs\Subscribers\SubscriberCheckatJob;
use App\Mail\Subscribers\SubscriberCheckMail;
use Illuminate\Support\Facades\Mail;

class SubscriberCheckatListener
{
    public function handle(SubscriberCheckatJob $event): void
    {
        $this->handleMailSubscriberCheckat($event);
    }

    public function handleMailSubscriberCheckat(SubscriberCheckatJob $event)
    {
        $subscriber = $event->subscriber;
        $email = $subscriber->email;
        $mail = new SubscriberCheckMail($subscriber);
        Mail::to($email)->queue($mail);
    }

}
