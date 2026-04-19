<?php

namespace Modules\Health\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Health\Models\AlertThreshold;

class AlertThresholdMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AlertThreshold $threshold,
        public readonly array $data,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Alerta] '.$this->threshold->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.alert-threshold',
        );
    }
}
