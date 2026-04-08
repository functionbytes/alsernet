<?php

namespace Modules\Blog\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Blog\Models\BlogPostComment;

class NewBlogCommentNotification extends Notification
{
    public function __construct(
        private readonly BlogPostComment $comment
    ) {}

    /** @return array<int, string> */
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
        $excerpt = mb_substr($this->comment->content, 0, 150);
        $moderationUrl = url('/blog/comments');

        return (new MailMessage)
            ->subject('Nuevo comentario en: '.$post->title)
            ->greeting('Hola,')
            ->line('Se ha recibido un nuevo comentario en la publicacion: **'.$post->title.'**')
            ->line('**Autor:** '.$this->comment->author_name)
            ->line('**Contenido:** '.$excerpt.(mb_strlen($this->comment->content) > 150 ? '...' : ''))
            ->action('Moderar comentario', $moderationUrl)
            ->line('Accede al panel de administracion para aprobar o rechazar este comentario.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'post_id' => $this->comment->blog_post_id,
            'author_name' => $this->comment->author_name,
            'excerpt' => mb_substr($this->comment->content, 0, 150),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
