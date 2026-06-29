<?php

namespace Modules\Blog\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Blog\Models\BlogPost;

class TranslationCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly BlogPost $post,
        public readonly array $results,
        public readonly array $translations,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('blog.post.'.$this->post->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'translation.completed';
    }

    public function broadcastWith(): array
    {
        $translationData = [];
        foreach ($this->translations as $locale => $trans) {
            $translationData[$locale] = [
                'title' => $trans->title,
                'slug' => $trans->slug,
                'description' => $trans->description,
                'content' => $trans->content,
                'seo_title' => $trans->seo_title,
                'seo_description' => $trans->seo_description,
                'seo_keywords' => $trans->seo_keywords,
                'status' => $trans->status?->value,
            ];
        }

        return [
            'post_id' => $this->post->id,
            'results' => $this->results,
            'translations' => $translationData,
        ];
    }
}
