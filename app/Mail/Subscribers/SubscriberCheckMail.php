<?php

namespace App\Mail\Subscribers;

use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

class SubscriberCheckMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscriber;
    public $layout;

    public function __construct($subscriber, $layout)
    {
        $this->subscriber = $subscriber;
        $this->layout = $layout;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->subscriber->email],
            subject: $this->layout->subject
        );
    }

    public function content(): Content
    {
        $content = $this->replaceTags($this->layout->content);
        return new Content(
            markdown: 'mailers.subscribers.check',
            with: [
                'subscriber' => $this->subscriber,
                'content' => $content,
            ]
        );
    }

    protected function replaceTags(string $content): string
    {
        $replacements = [
            '{FIRSTNAME}' => $this->subscriber->firstname,
            '{LASTNAME}' => $this->subscriber->lastname,
            '{EMAIL}' => $this->subscriber->email,
            '{FULLNAME}' => $this->subscriber->getFullName(),
        ];

        foreach ($replacements as $tag => $value) {
            $content = str_replace($tag, $value, $content);
        }

        return $content;
    }

    public function attachments(): array
    {
        return [];
    }
}
