<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Support\OutboundUrlGuard;

class LinkPreviewService
{
    private const CACHE_TTL_HOURS = 24;

    private const FETCH_TIMEOUT_SECONDS = 6;

    private const MAX_BYTES = 2097152; // 2 MB — needed for SPA pages (YouTube, etc.) that ship heavy JSON before <head>

    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /**
     * Detect the first http(s) URL in a message body and return its OG metadata.
     *
     * Returns null if no URL was found, the request failed, or the response
     * did not include any usable metadata.
     *
     * @return array{url:string,site:?string,title:?string,description:?string,image:?string,favicon:?string}|null
     */
    public function previewFromBody(string $body): ?array
    {
        $url = $this->extractFirstUrl($body);

        if ($url === null) {
            return null;
        }

        return $this->preview($url);
    }

    /**
     * @return array{url:string,site:?string,title:?string,description:?string,image:?string,favicon:?string}|null
     */
    public function preview(string $url): ?array
    {
        // SSRF guard: el body del mensaje/ticket es contenido de un cliente, así
        // que la URL no es de confianza. Se rechaza si no resuelve exclusivamente
        // a IPs públicas y, si es segura, la conexión se fija (CURLOPT_RESOLVE) a
        // esas IPs ya validadas para cerrar la ventana de DNS rebinding entre el
        // chequeo y la petición real.
        $curlOptions = $this->ssrfSafeCurlOptions($url);

        if ($curlOptions === null) {
            Log::debug('LinkPreview blocked by SSRF guard', ['url' => $url]);

            return null;
        }

        $cacheKey = 'helpdesk.link_preview.'.md5($url);

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($url, $curlOptions) {
            try {
                $response = Http::timeout(self::FETCH_TIMEOUT_SECONDS)
                    ->withUserAgent(self::USER_AGENT)
                    ->withHeaders(['Accept' => 'text/html,application/xhtml+xml'])
                    ->withOptions([
                        'allow_redirects' => ['max' => 3],
                        'curl' => $curlOptions,
                    ])
                    ->get($url);

                if (! $response->ok()) {
                    return null;
                }

                $fullBody = (string) $response->body();
                $finalUrl = (string) ($response->effectiveUri() ?? $url);

                // Most metadata lives inside <head>. If we can find </head> within
                // the byte limit, slice up to that point — otherwise take the
                // first MAX_BYTES.
                $headEnd = stripos($fullBody, '</head>');
                $body = $headEnd !== false
                    ? substr($fullBody, 0, $headEnd + 7)
                    : substr($fullBody, 0, self::MAX_BYTES);

                return $this->parseHtml($body, $finalUrl);
            } catch (\Throwable $e) {
                Log::debug('LinkPreview fetch failed', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Validates the URL resolves exclusively to public IPs and returns curl
     * options that pin the connection to those validated IPs (defence against
     * DNS rebinding between the check and the request). Returns null when the
     * URL must be rejected (bad scheme/host or private/reserved target).
     *
     * @return array<int, array<int, string>>|null
     */
    private function ssrfSafeCurlOptions(string $url): ?array
    {
        $parts = parse_url($url);

        if (! $parts || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            return null;
        }

        $ips = OutboundUrlGuard::publicIps($url);

        if ($ips === []) {
            return null;
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);

        // libcurl accepts multiple pinned addresses (comma separated); IPv6
        // literals go in brackets so the colons don't break the entry format.
        $pinned = implode(',', array_map(
            fn (string $ip): string => str_contains($ip, ':') ? '['.$ip.']' : $ip,
            $ips
        ));

        return [CURLOPT_RESOLVE => ["{$host}:{$port}:{$pinned}"]];
    }

    private function extractFirstUrl(string $text): ?string
    {
        if (! preg_match('#https?://[^\s<>"\'`]+#iu', $text, $m)) {
            return null;
        }

        return rtrim($m[0], ".,;:!?)]\u{200B}");
    }

    /**
     * @return array{url:string,site:?string,title:?string,description:?string,image:?string,favicon:?string}|null
     */
    private function parseHtml(string $html, string $finalUrl): ?array
    {
        $title = $this->meta($html, 'og:title')
            ?? $this->meta($html, 'twitter:title')
            ?? $this->htmlTag($html, 'title');

        $description = $this->meta($html, 'og:description')
            ?? $this->meta($html, 'twitter:description')
            ?? $this->metaName($html, 'description');

        $image = $this->meta($html, 'og:image:secure_url')
            ?? $this->meta($html, 'og:image')
            ?? $this->meta($html, 'twitter:image');

        $site = $this->meta($html, 'og:site_name');

        $favicon = $this->faviconUrl($html, $finalUrl);

        if ($title === null && $description === null && $image === null) {
            return null;
        }

        $host = parse_url($finalUrl, PHP_URL_HOST) ?: '';

        return [
            'url' => $finalUrl,
            'site' => $site ?: $host,
            'title' => $title,
            'description' => $description,
            'image' => $image ? $this->absolute($image, $finalUrl) : null,
            'favicon' => $favicon,
        ];
    }

    private function meta(string $html, string $property): ?string
    {
        $pattern = '#<meta[^>]+(?:property|name)\s*=\s*["\']'.preg_quote($property, '#').'["\'][^>]*content\s*=\s*["\']([^"\']+)["\']#i';
        if (preg_match($pattern, $html, $m)) {
            return $this->decode($m[1]);
        }

        $pattern = '#<meta[^>]+content\s*=\s*["\']([^"\']+)["\'][^>]*(?:property|name)\s*=\s*["\']'.preg_quote($property, '#').'["\']#i';
        if (preg_match($pattern, $html, $m)) {
            return $this->decode($m[1]);
        }

        return null;
    }

    private function metaName(string $html, string $name): ?string
    {
        $pattern = '#<meta[^>]+name\s*=\s*["\']'.preg_quote($name, '#').'["\'][^>]*content\s*=\s*["\']([^"\']+)["\']#i';
        if (preg_match($pattern, $html, $m)) {
            return $this->decode($m[1]);
        }

        return null;
    }

    private function htmlTag(string $html, string $tag): ?string
    {
        $pattern = '#<'.$tag.'[^>]*>([^<]+)</'.$tag.'>#i';
        if (preg_match($pattern, $html, $m)) {
            return $this->decode(trim($m[1]));
        }

        return null;
    }

    private function faviconUrl(string $html, string $finalUrl): ?string
    {
        $pattern = '#<link[^>]+rel\s*=\s*["\'](?:shortcut )?icon["\'][^>]+href\s*=\s*["\']([^"\']+)["\']#i';
        if (preg_match($pattern, $html, $m)) {
            return $this->absolute($m[1], $finalUrl);
        }

        $base = parse_url($finalUrl, PHP_URL_SCHEME).'://'.parse_url($finalUrl, PHP_URL_HOST);

        return $base.'/favicon.ico';
    }

    private function absolute(string $maybeRelative, string $base): string
    {
        if (preg_match('#^https?://#i', $maybeRelative)) {
            return $maybeRelative;
        }

        if (str_starts_with($maybeRelative, '//')) {
            return (parse_url($base, PHP_URL_SCHEME) ?: 'https').':'.$maybeRelative;
        }

        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($base, PHP_URL_HOST);
        $port = parse_url($base, PHP_URL_PORT);
        $portStr = $port ? ':'.$port : '';
        $origin = $scheme.'://'.$host.$portStr;

        if (str_starts_with($maybeRelative, '/')) {
            return $origin.$maybeRelative;
        }

        $path = parse_url($base, PHP_URL_PATH) ?: '/';
        $dir = rtrim(substr($path, 0, strrpos($path, '/') ?: 0), '/');

        return $origin.$dir.'/'.$maybeRelative;
    }

    private function decode(string $value): string
    {
        return trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
