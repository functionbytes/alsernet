<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CriticalErrorMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public readonly string $exceptionMessage;

    public readonly string $exceptionFile;

    public readonly int $exceptionLine;

    public readonly string $exceptionClass;

    public function __construct(
        Throwable $exception,
        public readonly string $url,
        public readonly string $timestamp,
    ) {
        $this->exceptionMessage = $exception->getMessage();
        $this->exceptionFile = $exception->getFile();
        $this->exceptionLine = $exception->getLine();
        $this->exceptionClass = get_class($exception);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ALERT] Error crítico en '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.critical-error',
        );
    }
}
