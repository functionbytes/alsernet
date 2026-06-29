<?php

namespace Modules\Analytics\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Analytics\Models\AnalyticsReportSchedule;

class AnalyticsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AnalyticsReportSchedule $schedule,
        public readonly string $reportPath,
        public readonly array $summary = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('analytics::analytics.reports.subject_scheduled', [
                'name' => $this->schedule->name,
                'date' => now()->format('d/m/Y'),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'analytics::mail.report',
        );
    }

    public function attachments(): array
    {
        $extension = pathinfo($this->reportPath, PATHINFO_EXTENSION);
        $mime = $this->getMimeForExtension($extension);
        $filename = 'reporte-analytics-'.$this->schedule->name.'-'.now()->format('Y-m-d').'.'.$extension;

        return [
            Attachment::fromPath($this->reportPath)
                ->as($filename)
                ->withMime($mime),
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
