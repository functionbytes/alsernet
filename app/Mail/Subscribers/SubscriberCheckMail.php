<?php

namespace App\Mail\Subscribers;

use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class SubscriberCheckMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscriber;
    public $layout;
    public $url;

    public function __construct($subscriber, $layout)
    {
        $this->url = 'https://a-alvarez.com/';
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
        $baseUrl = rtrim($this->url, '/');
        $token = $this->generateVerificationToken();

        $replacements = [
            '{URLCHECK}' => "{$baseUrl}/module/alsernetforms/verification?token={$token}"
        ];

        foreach ($replacements as $tag => $value) {
            $content = str_replace($tag, $value, $content);
        }

        return $content;
    }

    public function generateVerificationToken()
    {
        $token = Crypt::encryptString($this->subscriber->email);
        return rtrim($token, '=');
    }

    public function attachments(): array
    {
        return [];
    }
}

