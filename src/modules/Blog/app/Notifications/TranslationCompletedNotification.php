<?php

namespace Modules\Blog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Blog\Models\BlogPost;

class TranslationCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BlogPost $post,
        private readonly array $results,
    ) {}

    /**
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $failed = array_filter($this->results, fn ($v) => str_starts_with($v, 'error'));
        $succeeded = array_filter($this->results, fn ($v) => $v === 'ok');

        return [
            'type' => 'translation_completed',
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'succeeded' => count($succeeded),
            'failed' => count($failed),
            'results' => $this->results,
            'message' => empty($failed)
                ? "Traducciones completadas para \"{$this->post->title}\""
                : "Traducciones con errores para \"{$this->post->title}\"",
            'url' => route('blog.posts.edit', $this->post->id),
        ];
    }
}
