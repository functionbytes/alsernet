<?php

namespace Modules\Blog\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Blog\Models\BlogPostComment;

class CommentApprovedNotification extends Notification
{
    public function __construct(
        private readonly BlogPostComment $comment
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $post = $this->comment->post;
        $postUrl = route('blog.public.post', $post->slug);

        return (new MailMessage)
            ->subject('Tu comentario ha sido aprobado')
            ->greeting('Hola, '.$this->comment->author_name.'!')
            ->line('Tu comentario en la publicacion **'.$post->title.'** ha sido aprobado.')
            ->action('Ver publicacion', $postUrl)
            ->line('Gracias por participar en nuestra comunidad.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'post_id' => $this->comment->blog_post_id,
        ];
    }
}
