<?php

namespace App\Mail\Subscribers;

use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class SubscriberCheckMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscriber;
    public $email;

    public function __construct($subscriber)
    {
        $this->subscriber = $subscriber;
        $this->email = $subscriber->email ?? 'default@example.com';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->email],
            subject: 'Aquí tienes tu cheque regalo 10€!!!'
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mailers.subscribers.check',
            with: [
                'listname' => 'acas',
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
