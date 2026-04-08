<?php

namespace Modules\Page\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Page\Models\Page;

class PagePublishedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Page $page,
        public readonly mixed $subscriber = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva publicación: '.$this->page->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'page::emails.page-published',
        );
    }
}
