<?php

namespace Modules\HelpdeskLivechat\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Events\WidgetSessionUpdated;
use Modules\HelpdeskLivechat\Models\WidgetPageView;
use Modules\HelpdeskLivechat\Models\WidgetSession;

class WidgetSessionService
{
    private const HEARTBEAT_COOLDOWN_DEFAULT_SECONDS = 5;

    /**
     * Process a heartbeat from the widget JS.
     * Creates or updates the session, appends a page view if URL changed.
     *
     * Uses a Redis gate to skip DB reads/writes when the session is within the
     * cooldown window and the URL has not changed. The gate stores
     * `url|session_id` so the response can be built without touching the DB.
     */
    public function heartbeat(string $token, string $url, ?string $title, Request $request): WidgetSession
    {
        $cooldown = $this->resolveCooldownSeconds();
        $cacheKey = 'helpdesklivechat:hb:'.$token;

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            [$cachedUrl, $cachedSessionId] = explode('|', $cached, 2) + [null, null];

            if ($cachedUrl === $url && $cachedSessionId !== null) {
                // Fast-path: same URL within cooldown — return a minimal stub without hitting the DB.
                $stub = new WidgetSession(['session_token' => $token]);
                $stub->id = (int) $cachedSessionId;

                return $stub;
            }
        }

        $session = WidgetSession::where('session_token', $token)->first();

        if (! $session) {
            $session = $this->createSession($token, $url, $title, $request);
        } else {
            $this->updateSession($session, $url, $title, $request);
        }

        // Refresh the Redis gate with current URL and session id.
        Cache::put($cacheKey, $url.'|'.$session->id, $cooldown);

        $conversationId = Cache::get('helpdesklivechat:session_conv:'.$token);
        if ($conversationId) {
            WidgetSessionUpdated::dispatch($session, (int) $conversationId);
        }

        return $session;
    }

    private function createSession(string $token, string $url, ?string $title, Request $request): WidgetSession
    {
        $session = WidgetSession::create([
            'session_token' => $token,
            'current_url' => $url,
            'referrer' => $request->header('Referer'),
            'device' => $this->extractDevice($request),
            'ip_address' => $request->ip(),
            'country_code' => $this->resolveCountry($request->ip()),
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);

        WidgetPageView::create([
            'session_id' => $session->id,
            'url' => $url,
            'title' => $title,
            'viewed_at' => now(),
        ]);

        return $session;
    }

    private function updateSession(WidgetSession $session, string $url, ?string $title, Request $request): void
    {
        $urlChanged = $session->current_url !== $url;
        $lastActivity = $session->last_activity_at;
        $cooldown = $this->resolveCooldownSeconds();
        $cooldownPassed = $lastActivity === null
            || $lastActivity->diffInSeconds(now()) >= $cooldown;

        $session->last_activity_at = now();

        if ($urlChanged) {
            $session->current_url = $url;
        }

        // Refresh device info if it was missing (e.g. session bootstrapped without a real UA).
        $device = $session->device ?? [];
        if (empty($device['browser']) || $device['browser'] === 'Unknown') {
            $session->device = $this->extractDevice($request);
        }

        $session->saveQuietly();

        if ($urlChanged || $cooldownPassed) {
            WidgetPageView::create([
                'session_id' => $session->id,
                'url' => $url,
                'title' => $title,
                'viewed_at' => now(),
            ]);
        }
    }

    private function resolveCooldownSeconds(): int
    {
        return Cache::remember(
            'helpdesklivechat:heartbeat_cooldown',
            now()->addMinutes(5),
            fn (): int => (int) (Setting::get('livechat.tracking.heartbeat_cooldown_seconds')
                ?? self::HEARTBEAT_COOLDOWN_DEFAULT_SECONDS)
        );
    }

    private function extractDevice(Request $request): array
    {
        $userAgent = $request->userAgent() ?? '';

        return [
            'user_agent' => $userAgent,
            'browser' => $this->detectBrowser($userAgent),
            'os' => $this->detectOs($userAgent),
        ];
    }

    private function detectBrowser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Chrome') && ! str_contains($ua, 'Edg') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome') => 'Safari',
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'MSIE') || str_contains($ua, 'Trident') => 'IE',
            default => 'Unknown',
        };
    }

    private function detectOs(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') && ! str_contains($ua, 'Android') => 'Linux',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            default => 'Unknown',
        };
    }

    private function resolveCountry(string $ip): ?string
    {
        if (in_array($ip, ['127.0.0.1', '::1'], true)
            || str_starts_with($ip, '192.168.')
            || str_starts_with($ip, '10.')
            || str_starts_with($ip, '172.')) {
            return null;
        }

        // Cache by IP for 24h to avoid hitting the geoip database on every heartbeat.
        return Cache::remember(
            'helpdesklivechat:geoip:'.md5($ip),
            now()->addDay(),
            function () use ($ip): ?string {
                try {
                    $location = @geoip($ip);

                    return $location?->iso_code ?: null;
                } catch (\Throwable) {
                    return null;
                }
            }
        );
    }
}
