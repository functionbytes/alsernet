<?php

namespace Modules\Media\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Modules\Media\Models\MediaComment;

class UserMentionedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly MediaComment $comment,
        public readonly User $mentionedBy
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'media.mention',
            'title' => "{$this->mentionedBy->name} te mencionó",
            'message' => Str::limit($this->comment->content, 100),
            'action_url' => '/panel/media?highlight='.$this->comment->commentable_id,
            'comment_id' => $this->comment->id,
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Te mencionaron en un comentario')
            ->greeting("Hola {$notifiable->name},")
            ->line("{$this->mentionedBy->name} te mencionó en un comentario:")
            ->line(Str::limit($this->comment->content, 200))
            ->action('Ver comentario', url('/panel/media'));
    }
}
