<?php

namespace Modules\Page\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Page\Models\Page;

class PageUnpublishedNotification extends Notification
{
    use Queueable;

    protected Page $page;

    protected string $unpublishType;

    /**
     * Create a new notification instance.
     *
     * @param  string  $unpublishType  'manual' or 'scheduled'
     */
    public function __construct(Page $page, string $unpublishType = 'manual')
    {
        $this->page = $page;
        $this->unpublishType = $unpublishType;
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
        $subject = $this->unpublishType === 'scheduled'
            ? 'Página Despublicada Automáticamente'
            : 'Página Despublicada';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hola '.$notifiable->name.',')
            ->line($this->getNotificationMessage())
            ->line('Título: '.$this->page->title)
            ->action('Ver Página (Admin)', route('pages.edit', $this->page->id))
            ->line('La página ya no es accesible públicamente.');
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
            'unpublish_type' => $this->unpublishType,
            'unpublished_at' => now()->toDateTimeString(),
            'message' => $this->getNotificationMessage(),
        ];
    }

    /**
     * Get the notification message based on unpublish type.
     */
    protected function getNotificationMessage(): string
    {
        if ($this->unpublishType === 'scheduled') {
            return 'Tu página ha sido despublicada automáticamente según la programación establecida.';
        }

        return 'Tu página ha sido despublicada.';
    }
}
