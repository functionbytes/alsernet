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
        $translator->translatePost($this->post, $this->languages);
    }

    public function tags(): array
    {
        return ['translation', 'post:'.$this->post->id];
    }

    public function failed(\Throwable $exception): void
    {
        // translatePost() es idempotente (updateOrCreate por idioma), así que
        // antes el catch-and-fail() inmediato anulaba por completo el
        // tries=3/backoff configurados: cualquier error (incl. uno transitorio
        // de la API de traducción) se marcaba fallido en el primer intento sin
        // reintentar nunca.
        \Log::error('TranslatePostJob failed permanently for post '.$this->post->id, [
            'error' => $exception->getMessage(),
            'languages' => $this->languages,
        ]);
    }
}
