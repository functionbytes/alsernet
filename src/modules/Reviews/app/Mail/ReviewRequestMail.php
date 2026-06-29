<?php

namespace Modules\Reviews\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Reviews\Models\ReviewRequestCampaign;
use Modules\Reviews\Models\ReviewRequestSend;

class ReviewRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ReviewRequestCampaign $campaign,
        public readonly ReviewRequestSend $send,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'reviews::emails.review-request',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
