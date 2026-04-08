<?php

namespace Modules\Reviews\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReviewDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly array $stats,
    ) {}

    public function envelope(): Envelope
    {
        $weekEnd = now()->format('d/m/Y');

        return new Envelope(
            subject: "Tu resumen semanal de reseñas — {$weekEnd}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'reviews::emails.weekly-digest',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
