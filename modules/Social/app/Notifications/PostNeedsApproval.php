<?php

namespace Modules\Social\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Modules\Social\Models\Post;
use NotificationChannels\Telegram\TelegramMessage;

class PostNeedsApproval extends Notification
{
    use Queueable;

    protected Post $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function via($notifiable): array
    {
        $channels = ['mail', 'database'];

        if ($notifiable->telegram_chat_id) {
            $channels[] = 'telegram';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Post Necesita Aprobación')
            ->greeting('Hola '.$notifiable->name)
            ->line('Un nuevo post está esperando tu aprobación.')
            ->line('Contenido: '.Str::limit($this->post->content, 100))
            ->line('Red Social: '.$this->post->socialAccount->name)
            ->line('Campaña: '.($this->post->campaign?->name ?? 'Sin campaña'))
            ->action('Revisar Post', route('admin.social.posts.approve', $this->post))
            ->line('Gracias por mantener la calidad de nuestro contenido!');
    }

    public function toTelegram($notifiable): TelegramMessage
    {
        $url = route('admin.social.posts.approve', $this->post);

        return TelegramMessage::create()
            ->to($notifiable->telegram_chat_id)
            ->content("📝 *Nuevo post necesita aprobación*\n\n"
                ."*Red:* {$this->post->socialAccount->name}\n"
                .'*Campaña:* '.($this->post->campaign?->name ?? 'Sin campaña')."\n\n"
                ."*Contenido:*\n".Str::limit($this->post->content, 200))
            ->button('✅ Aprobar', $url.'?action=approve')
            ->button('❌ Rechazar', $url.'?action=reject')
            ->button('👁️ Ver Detalles', $url);
    }

    public function toArray($notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'message' => 'Nuevo post necesita aprobación',
            'content_preview' => Str::limit($this->post->content, 100),
            'social_account' => $this->post->socialAccount->name,
            'campaign' => $this->post->campaign?->name,
        ];
    }
}
