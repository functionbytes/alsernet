<?php

namespace Modules\Seo\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SeoContentDecayMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Collection $pages,
        public readonly int $daysStale
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SEO · '.$this->pages->count().' páginas con content decay'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'Seo::emails.content-decay',
            with: [
                'pages' => $this->pages,
                'daysStale' => $this->daysStale,
            ]
        );
    }
}
