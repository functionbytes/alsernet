<?php

namespace Modules\Seo\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Seo\Models\SeoMeta;

class SeoScoreDropMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SeoMeta $meta,
        public readonly int $previousScore,
        public readonly int $currentScore,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Alerta SEO: bajada de puntuación en {$this->meta->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'Seo::emails.score-drop',
        );
    }
}
