<?php

namespace Modules\Reviews\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Reviews\Models\GeneratedReport;

class ReviewReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly GeneratedReport $report,
        public readonly string $filePath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reporte de reseñas listo — '.now()->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'reviews::mail.report',
        );
    }

    public function attachments(): array
    {
        $extension = pathinfo($this->filePath, PATHINFO_EXTENSION);
        $filename = 'reporte-resenas-'.now()->format('Y-m-d').'.'.$extension;

        return [
            Attachment::fromPath($this->filePath)
                ->as($filename)
                ->withMime($this->getMimeForExtension($extension)),
        ];
    }

    private function getMimeForExtension(string $extension): string
    {
        return match ($extension) {
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }
}
