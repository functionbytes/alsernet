<?php

namespace Modules\Seo\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SeoRedirectChainMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array<int, string>>  $chains
     */
    public function __construct(
        public readonly array $chains,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Alerta SEO: cadenas de redirecciones detectadas',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'Seo::emails.redirect-chain',
        );
    }
}
