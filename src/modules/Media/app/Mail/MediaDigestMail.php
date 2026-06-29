<?php

namespace Modules\Media\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MediaDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly array $stats
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu resumen de actividad Media');
    }

    public function content(): Content
    {
        return new Content(
            view: 'media::emails.digest',
            with: [
                'user' => $this->user,
                'stats' => $this->stats,
            ]
        );
    }
}
