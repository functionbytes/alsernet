<?php

namespace Modules\Blog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Modules\Blog\Models\BlogPostComment;

class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly BlogPostComment $comment) {}

    public function via(object $notifiable): array
    {
        return array_values(array_filter(
            ['mail', 'database'],
            fn ($ch) => $notifiable->canReceiveNotification($ch, 'blog.new_comment')
        )) ?: ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $post = $this->comment->post;

        return (new MailMessage)
            ->subject('Nuevo comentario en tu artículo')
            ->greeting("Hola {$notifiable->name},")
            ->line("Han comentado en tu artículo: **{$post->title}**")
            ->line('Comentario: '.Str::limit($this->comment->content, 150))
            ->action('Ver artículo', $post->url)
            ->line('Puedes moderar este comentario desde el panel de administración.');
    }

    public function toDatabase(object $notifiable): array
    {
        $post = $this->comment->post;

        return [
            'title' => 'Nuevo comentario en tu artículo',
            'message' => Str::limit($this->comment->content, 100),
            'post_title' => $post->title ?? '',
            'url' => $post->url ?? '/blog',
            'icon' => 'fas fa-comment',
            'color' => 'info',
        ];
    }
}
