<?php

namespace Modules\Page\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Page\Models\Page;

class PagePublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $publishType  'manual' or 'scheduled'
     */
    public function __construct(
        public readonly Page $page,
        public readonly string $publishedBy = 'Sistema',
        public readonly string $publishType = 'manual',
    ) {}

    public function via(object $notifiable): array
    {
        return array_values(array_filter(
            ['mail', 'database'],
            fn ($ch) => $notifiable->canReceiveNotification($ch, 'page.published')
        )) ?: ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->publishType === 'scheduled'
            ? 'Página publicada automáticamente'
            : "Página publicada: {$this->page->title}";

        $greeting = property_exists($notifiable, 'name') && $notifiable->name
            ? 'Hola '.$notifiable->name.','
            : 'Hola,';

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($this->getNotificationMessage())
            ->line('Título: '.$this->page->title)
            ->action('Ver página', $this->page->url)
            ->line('Este es un mensaje automático del sistema de gestión de contenidos.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'page_published',
            'page_id' => $this->page->id,
            'page_title' => $this->page->title,
            'page_slug' => $this->page->slug,
            'page_url' => $this->page->url,
            'published_by' => $this->publishedBy,
            'publish_type' => $this->publishType,
            'published_at' => $this->page->published_at?->toDateTimeString(),
        ];
    }

    protected function getNotificationMessage(): string
    {
        if ($this->publishType === 'scheduled') {
            return 'La página ha sido publicada automáticamente según la programación establecida.';
        }

        return "La página '{$this->page->title}' ha sido publicada por {$this->publishedBy}.";
    }
}
