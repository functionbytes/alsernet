<?php

namespace Modules\Seo\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SeoWeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly array $stats) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reporte SEO semanal — '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'Seo::emails.weekly-report');
    }
}
