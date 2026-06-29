<?php

namespace Modules\Social\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Social\Models\Post;
use Modules\Social\Services\AIContentGenerator;

class GenerateAIContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public $backoff = [60, 300, 900];

    public function __construct(
        public Post $post,
        public string $topic,
        public string $tone = 'professional',
        public int $maxLength = 280
    ) {}

    public function handle(AIContentGenerator $aiGenerator): void
    {
        try {
            $content = $aiGenerator->generateContent(
                $this->topic,
                $this->tone,
                $this->maxLength
            );

            $this->post->update(['content' => $content]);

            // Also generate hashtags
            $hashtags = $aiGenerator->suggestHashtags($content);

            if (! empty($hashtags)) {
                // Append hashtags to content
                $this->post->update([
                    'content' => $content."\n\n".implode(' ', $hashtags),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('AI content generation failed for post '.$this->post->id, [
                'error' => $e->getMessage(),
                'topic' => $this->topic,
            ]);

            $this->fail($e);
        }
    }

    public function tags(): array
    {
        return ['ai-generation', 'post:'.$this->post->id];
    }
}
