<?php

namespace Modules\Seo\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IndexNow — notifica a Bing, Yandex y Seznam cuando publicas o
 * actualizas URLs para indexación casi inmediata.
 *
 * Docs: https://www.indexnow.org/documentation
 */
class IndexNowService
{
    public function enabled(): bool
    {
        return (bool) seo_setting('indexnow.enabled', config('seohelper.indexnow.enabled', false))
            && $this->key() !== ''
            && $this->host() !== '';
    }

    public function key(): string
    {
        return (string) seo_setting('indexnow.key', config('seohelper.indexnow.key', ''));
    }

    public function keyLocation(): ?string
    {
        $key = $this->key();

        if ($key === '') {
            return null;
        }

        return rtrim($this->baseUrl(), '/').'/'.$key.'.txt';
    }

    /**
     * Envía uno o más URLs al endpoint de IndexNow.
     *
     * @param  array<int, string>|string  $urls
     * @return array{ok: bool, status: int, submitted: int, message: string}
     */
    public function submit(array|string $urls): array
    {
        $urls = is_string($urls) ? [$urls] : array_values(array_unique(array_filter($urls)));

        if (empty($urls)) {
            return ['ok' => false, 'status' => 0, 'submitted' => 0, 'message' => 'No URLs provided'];
        }

        if (! $this->enabled()) {
            return ['ok' => false, 'status' => 0, 'submitted' => 0, 'message' => 'IndexNow disabled or misconfigured'];
        }

        $host = $this->host();

        // Todas las URLs deben compartir el mismo host configurado
        $invalid = array_filter($urls, fn ($u) => parse_url($u, PHP_URL_HOST) !== $host);
        if (! empty($invalid)) {
            return [
                'ok' => false,
                'status' => 422,
                'submitted' => 0,
                'message' => 'Some URLs do not match configured host '.$host,
            ];
        }

        $batchSize = max(1, (int) config('seohelper.indexnow.batch_size', 100));
        $chunks = array_chunk($urls, $batchSize);
        $submitted = 0;
        $lastStatus = 0;

        foreach ($chunks as $chunk) {
            try {
                $response = Http::timeout(10)->asJson()->post(
                    (string) config('seohelper.indexnow.endpoint', 'https://api.indexnow.org/indexnow'),
                    [
                        'host' => $host,
                        'key' => $this->key(),
                        'keyLocation' => $this->keyLocation(),
                        'urlList' => $chunk,
                    ]
                );

                $lastStatus = $response->status();

                if ($response->successful()) {
                    $submitted += count($chunk);

                    continue;
                }

                Log::warning('IndexNow submit returned non-2xx', [
                    'status' => $lastStatus,
                    'body' => $response->body(),
                    'urls' => $chunk,
                ]);
            } catch (\Throwable $e) {
                Log::warning('IndexNow submit failed', [
                    'error' => $e->getMessage(),
                    'urls' => $chunk,
                ]);
            }
        }

        return [
            'ok' => $submitted === count($urls),
            'status' => $lastStatus,
            'submitted' => $submitted,
            'message' => $submitted.'/'.count($urls).' URLs submitted',
        ];
    }

    private function host(): string
    {
        return (string) (parse_url($this->baseUrl(), PHP_URL_HOST) ?: '');
    }

    /**
     * Returns the canonical base URL, preferring the seo.canonical_base_url setting
     * over APP_URL so that production submissions always use the real domain.
     */
    private function baseUrl(): string
    {
        $canonical = seo_setting('canonical_base_url');

        return $canonical ?: (string) config('app.url', '');
    }
}
