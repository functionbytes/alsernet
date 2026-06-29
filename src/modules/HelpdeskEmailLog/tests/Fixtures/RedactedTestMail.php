<?php

namespace Modules\HelpdeskEmailLog\Tests\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskEmailLog\Contracts\RedactsEmailLogBody;
use Modules\HelpdeskEmailLog\Contracts\TracksEmailLog;
use Modules\HelpdeskEmailLog\Mail\AddsEmailLogHeaders;

class RedactedTestMail extends Mailable implements RedactsEmailLogBody, TracksEmailLog
{
    use AddsEmailLogHeaders;
    use Queueable;
    use SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Sensitive: your reset link');
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Token: abc123-secret</p>');
    }

    public function getEmailLogModule(): string
    {
        return 'Auth';
    }

    public function getEmailLogEntityType(): string
    {
        return 'User';
    }

    public function getEmailLogEntityId(): int|string
    {
        return 7;
    }
}
