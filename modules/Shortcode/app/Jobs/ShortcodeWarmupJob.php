<?php

namespace Modules\Shortcode\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Shortcode\Facades\Shortcode;

/**
 * Precompila un conjunto de contenidos y los deja en cache.
 *
 * Uso típico tras un clearCache masivo: enviar a la cola los contenidos
 * más visitados para que el primer request del usuario ya los encuentre
 * calientes.
 *
 * Ejemplo:
 *     ShortcodeWarmupJob::dispatch($page->pluck('content')->all());
 */
class ShortcodeWarmupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 15;

    /**
     * @param  array<int, string>  $contents
     */
    public function __construct(private readonly array $contents)
    {
        $this->onQueue('shortcode');
    }

    public function handle(): void
    {
        $compiled = 0;

        foreach ($this->contents as $content) {
            if (! is_string($content) || $content === '') {
                continue;
            }

            try {
                Shortcode::compile($content);
                $compiled++;
            } catch (\Throwable $e) {
                Log::warning('ShortcodeWarmupJob: fallo compilando un contenido: '.$e->getMessage());
            }
        }

        Log::info("ShortcodeWarmupJob: {$compiled} contenidos precompilados en cache.");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ShortcodeWarmupJob falló', [
            'error' => $exception->getMessage(),
            'batch_size' => count($this->contents),
        ]);
    }
}
