<?php

namespace App\Mail\Return;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReturnStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $emailData;

    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailData['subject'] ?? 'Actualización de Devolución',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->emailData['template'] ?? 'emails.returns.default',
            with: $this->emailData
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        // Adjuntar etiqueta de devolución si existe
        if (isset($this->emailData['return_label_path'])) {
            $attachments[] = Attachment::fromPath($this->emailData['return_label_path'])
                ->as('etiqueta_devolucion.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
