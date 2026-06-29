<?php

namespace Modules\Social\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Social\Models\Post;
use Modules\Social\Services\TranslationService;

class TranslatePostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public $backoff = [60, 300, 900];

    public function __construct(
        public Post $post,
        public array $languages
    ) {}

    public function handle(TranslationService $translator): void
    {
        try {
            $translator->translatePost($this->post, $this->languages);
        } catch (\Exception $e) {
            \Log::error('Translation failed for post '.$this->post->id, [
                'error' => $e->getMessage(),
                'languages' => $this->languages,
            ]);

            $this->fail($e);
        }
    }

    public function tags(): array
    {
        return ['translation', 'post:'.$this->post->id];
    }
}
