<?php

namespace Modules\Seo\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SeoOrphanAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly int $count,
        public readonly string $modelType,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Alerta SEO: {$this->count} páginas sin SEO detectadas",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'Seo::emails.orphan-alert',
        );
    }
}
