<?php

namespace Modules\Newsletter\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Newsletter\Models\Subscriber;

class NewSubscriberAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Subscriber $subscriber
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: config('mail.from.address'),
            subject: 'Nuevo suscriptor al newsletter',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Nuevo suscriptor: <strong>'.$this->subscriber->email.'</strong>'
                .($this->subscriber->name ? ' ('.$this->subscriber->name.')' : '').'</p>'
        );
    }
}
