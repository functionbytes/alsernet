<?php

namespace Modules\Reviews\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportReadyNotification extends Notification
{
    public function __construct(
        private readonly string $filePath
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fileName = basename($this->filePath);

        return (new MailMessage)
            ->subject('Your Reviews Export is Ready')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your reviews export has been completed successfully!')
            ->line("File: {$fileName}")
            ->action('Download Export', route('reviews.export.download', ['file' => basename($this->filePath)]))
            ->line('Thank you for using our service.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'file_path' => $this->filePath,
            'file_name' => basename($this->filePath),
            'message' => 'Your reviews export is ready for download',
        ];
    }
}
