<?php

namespace Modules\Campaign\Services;

use Illuminate\Support\Facades\Http;

/**
 * Verifica que todos los links de una plantilla respondan HTTP 200.
 */
class LinkRotChecker
{
    /**
     * @return array<int, array{url: string, status: int|null, ok: bool}>
     */
    public function check(string $html, int $timeout = 10): array
    {
        $urls = $this->extractUrls($html);
        $results = [];

        foreach ($urls as $url) {
            try {
                $response = Http::timeout($timeout)
                    ->withOptions(['allow_redirects' => true])
                    ->head($url);
                $status = $response->status();
                $results[] = [
                    'url' => $url,
                    'status' => $status,
                    'ok' => $status >= 200 && $status < 400,
                ];
            } catch (\Throwable) {
                $results[] = [
                    'url' => $url,
                    'status' => null,
                    'ok' => false,
                ];
            }
        }

        return $results;
    }

    public function hasBrokenLinks(string $html): bool
    {
        return collect($this->check($html))->contains(fn ($r) => ! $r['ok']);
    }

    /**
     * @return list<string>
     */
    protected function extractUrls(string $html): array
    {
        preg_match_all('/href=["\'](https?:\/\/[^"\']+)["\']/i', $html, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
