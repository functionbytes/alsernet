<?php

namespace Modules\HelpdeskLivechat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validate that the widget request originates from a trusted domain.
 *
 * SECURITY NOTE — this is NOT an authentication control. The Origin/Referer
 * headers it inspects are set freely by non-browser clients (curl, scripts),
 * so a forged header passes this check trivially; and when no trusted_domains
 * are configured the middleware allows every origin (permissive mode, kept
 * for retro-compatibility). Treat it purely as a browser-side abuse dampener
 * (blocks casual embedding of a store's widget on foreign sites). Actual
 * abuse control on the public widget endpoints relies on the per-IP
 * `throttle:` middleware plus the per-website_token quota enforced by
 * ThrottleByWebsiteToken; per-conversation access relies on the conversation
 * token (VerifiesConversationToken) and widget HMAC (VerifyWidgetHmac).
 *
 * Per-inbox trusted_domains (stored on helpdesk_channel_webs) take precedence.
 * Falls back to the global livechat.trusted_domains setting.
 * When no domains are configured the middleware is permissive for retro-compatibility.
 * CORS preflight OPTIONS requests always pass through.
 *
 * When trusted_domains ARE configured, requests without Origin/Referer headers
 * (e.g. server-side cURL, sandboxed iframes with Origin: null) are rejected —
 * the absence of an Origin header cannot be treated as trusted.
 *
 * Supports exact match and wildcard subdomain patterns (*.example.com).
 */
class ValidateTrustedOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Always allow CORS preflight requests.
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $trustedDomains = $this->getTrustedDomains($request);
        $origin = $this->resolveOriginHost($request);

        // Permissive mode: no domains configured → allow all origins regardless of header.
        if (empty($trustedDomains)) {
            return $next($request);
        }

        // Strict mode: domains ARE configured — Origin header is mandatory.
        if ($origin === null) {
            return response()->json(['error' => 'Origin required'], Response::HTTP_FORBIDDEN);
        }

        foreach ($trustedDomains as $pattern) {
            if ($this->matchesDomain($origin, $pattern)) {
                return $next($request);
            }
        }

        return response()->json(['error' => 'Origin not allowed'], Response::HTTP_FORBIDDEN);
    }

    /**
     * Extract the host from the Origin header (preferred) or Referer as fallback.
     */
    private function resolveOriginHost(Request $request): ?string
    {
        $origin = $request->headers->get('Origin');

        if ($origin) {
            $host = parse_url($origin, PHP_URL_HOST);

            return $host ? strtolower($host) : null;
        }

        $referer = $request->headers->get('Referer');

        if ($referer) {
            $host = parse_url($referer, PHP_URL_HOST);

            return $host ? strtolower($host) : null;
        }

        return null;
    }

    /**
     * Load trusted domains — per-inbox first, global setting as fallback.
     *
     * @return array<string>
     */
    private function getTrustedDomains(Request $request): array
    {
        $websiteToken = $request->input('website_token')
            ?? $request->header('X-Website-Token');

        if ($websiteToken) {
            $raw = Cache::remember(
                'helpdesklivechat:trusted_domains:'.$websiteToken,
                now()->addMinutes(5),
                fn (): string => (string) (Web::where('website_token', $websiteToken)->value('trusted_domains') ?? '')
            );

            if (! empty($raw)) {
                return $this->parseDomainList($raw);
            }
        }

        // Fall back to global setting.
        $raw = Setting::get('livechat.trusted_domains', '');

        return empty($raw) ? [] : $this->parseDomainList((string) $raw);
    }

    /**
     * @return array<string>
     */
    private function parseDomainList(string $raw): array
    {
        $entries = preg_split('/[\r\n,]+/', $raw);

        return array_values(array_filter(array_map('trim', $entries)));
    }

    /**
     * Check whether $host matches the $pattern.
     * Supports exact match and wildcard subdomain (*.example.com).
     */
    private function matchesDomain(string $host, string $pattern): bool
    {
        $pattern = strtolower(trim($pattern));

        if ($pattern === '') {
            return false;
        }

        // Exact match.
        if ($host === $pattern) {
            return true;
        }

        // Wildcard subdomain: *.example.com matches sub.example.com
        if (str_starts_with($pattern, '*.')) {
            $base = substr($pattern, 2);

            return $host === $base || str_ends_with($host, '.'.$base);
        }

        return false;
    }
}
