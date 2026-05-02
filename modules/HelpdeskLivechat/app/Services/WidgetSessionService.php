<?php

namespace Modules\HelpdeskLivechat\Services;

use Illuminate\Http\Request;
use Modules\HelpdeskLivechat\Models\WidgetPageView;
use Modules\HelpdeskLivechat\Models\WidgetSession;

class WidgetSessionService
{
    private const HEARTBEAT_COOLDOWN_SECONDS = 10;

    /**
     * Process a heartbeat from the widget JS.
     * Creates or updates the session, appends a page view if URL changed.
     */
    public function heartbeat(string $token, string $url, ?string $title, Request $request): WidgetSession
    {
        $session = WidgetSession::where('session_token', $token)->first();

        if (! $session) {
            $session = $this->createSession($token, $url, $title, $request);
        } else {
            $this->updateSession($session, $url, $title);
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

    private function updateSession(WidgetSession $session, string $url, ?string $title): void
    {
        $urlChanged = $session->current_url !== $url;
        $lastActivity = $session->last_activity_at;
        $cooldownPassed = $lastActivity === null
            || $lastActivity->diffInSeconds(now()) >= self::HEARTBEAT_COOLDOWN_SECONDS;

        $session->last_activity_at = now();

        if ($urlChanged) {
            $session->current_url = $url;
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
        // Basic resolution — extend with GeoIP service if needed
        if (in_array($ip, ['127.0.0.1', '::1'], true) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return null;
        }

        return null;
    }
}
