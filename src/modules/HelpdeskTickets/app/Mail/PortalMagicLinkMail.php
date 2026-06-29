<?php

namespace Modules\HelpdeskTickets\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Customer;

class PortalMagicLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Customer $customer,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your portal login link',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'helpdesktickets::emails.portal-magic-link',
        );
    }
}
