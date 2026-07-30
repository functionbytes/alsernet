<?php

namespace Modules\HelpdeskEmailLog\Tests\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskEmailLog\Contracts\TracksEmailLog;
use Modules\HelpdeskEmailLog\Mail\AddsEmailLogHeaders;

class TrackedTestMail extends Mailable implements TracksEmailLog
{
    use AddsEmailLogHeaders;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $ticketId = 42) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tracked test email');
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Hello from the tracked test email.</p>');
    }

    public function getEmailLogModule(): string
    {
        return 'HelpdeskTickets';
    }

    public function getEmailLogEntityType(): string
    {
        return 'Ticket';
    }

    public function getEmailLogEntityId(): int|string
    {
        return $this->ticketId;
    }
}
