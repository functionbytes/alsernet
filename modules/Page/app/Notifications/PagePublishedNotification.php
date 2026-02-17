<?php

namespace Modules\Page\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Page\Models\Page;

class PagePublishedNotification extends Notification
{
    use Queueable;

    protected Page $page;

    protected string $publishType;

    /**
     * Create a new notification instance.
     *
     * @param  string  $publishType  'manual' or 'scheduled'
     */
    public function __construct(Page $page, string $publishType = 'manual')
    {
        $this->page = $page;
        $this->publishType = $publishType;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = $this->publishType === 'scheduled'
            ? 'Página Publicada Automáticamente'
            : 'Página Publicada';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hola '.$notifiable->name.',')
            ->line($this->getNotificationMessage())
            ->line('Título: '.$this->page->title)
            ->action('Ver Página', $this->page->url)
            ->line('Gracias por usar nuestra aplicación.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'page_id' => $this->page->id,
            'page_title' => $this->page->title,
            'page_slug' => $this->page->slug,
            'page_url' => $this->page->url,
            'publish_type' => $this->publishType,
            'published_at' => $this->page->published_at?->toDateTimeString(),
            'message' => $this->getNotificationMessage(),
        ];
    }

    /**
     * Get the notification message based on publish type.
     */
    protected function getNotificationMessage(): string
    {
        if ($this->publishType === 'scheduled') {
            return 'Tu página ha sido publicada automáticamente según la programación establecida.';
        }

        return 'Tu página ha sido publicada exitosamente.';
    }
}
