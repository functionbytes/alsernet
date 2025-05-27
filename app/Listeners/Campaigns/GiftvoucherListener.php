<?php

namespace App\Listeners\Campaigns;

use App\Mail\Campaigns\Giftvoucher\GiftvoucherMail;
use App\Events\Campaigns\GiftvoucherCreated;
use Illuminate\Support\Facades\Mail;

class GiftvoucherListener
{
    public function handle(GiftvoucherCreated $event): void
    {
        $this->handleMailGiftvoucher($event);
    }

    public function handleMailGiftvoucher(GiftvoucherCreated $event)
    {
        $newsletter = $event->newsletter;
        $email = $newsletter->email;
        $mail = new GiftvoucherMail($newsletter);
        Mail::to($email)->queue($mail);
    }

}
