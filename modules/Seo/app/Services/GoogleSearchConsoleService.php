<?php

namespace Modules\Seo\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Modules\Seo\Models\SeoMeta;

/**
 * Google Search Console API client using OAuth 2.0. Imports clicks/impressions/
 * position for all URLs in the configured property and persists them into
 * seo_metas so the dashboard can show real search-traffic data without the
 * admin manually exporting CSVs.
 *
 * Setup steps (one-time):
 *  1. Go to https://console.cloud.google.com/apis/credentials and create an
 *     OAuth 2.0 Client ID (type: Web application).
 *  2. Add redirect URI: https://<tu-sitio>/panel/setting/seo/gsc/callback
 *  3. Copy client_id + client_secret to .env or /panel/setting/seo/settings.
 *  4. Visit /panel/setting/seo/gsc to connect and grant permission.
 *
 * The refresh token is stored in core_settings (seo.gsc.refresh_token).
 */
class GoogleSearchConsoleService
{
    private const OAUTH_AUTHORIZE = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const OAUTH_TOKEN = 'https://oauth2.googleapis.com/token';

    private const API_BASE = 'https://searchconsole.googleapis.com/v1';

    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    public function isConnected(): bool
    {
        return $this->isConfigured() && (string) Setting::get('seo.gsc.refresh_token', '') !== '';
    }

    /**
     * Build the URL the admin must visit to authorize the app.
     */
    public function authorizationUrl(string $state = ''): string
    {
        return self::OAUTH_AUTHORIZE.'?'.http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    /**
     * Exchange the auth code for a refresh token and store it.
     */
    public function handleCallback(string $code): bool
    {
        $response = Http::asForm()->post(self::OAUTH_TOKEN, [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            Log::warning('GSC OAuth callback failed', ['body' => $response->body()]);

            return false;
        }

        $refreshToken = $response->json('refresh_token');

        if (! $refreshToken) {
            return false;
        }

        Setting::set('seo.gsc.refresh_token', $refreshToken);

        return true;
    }

    public function disconnect(): void
    {
        Setting::set('seo.gsc.refresh_token', '');
    }

    /**
     * Fetch search analytics for the last N days, aggregated by page URL.
     *
     * @return array<int, array{page: string, clicks: int, impressions: int, ctr: float, position: float}>
     */
    public function fetchSearchAnalytics(int $days = 28): array
    {
        $token = $this->accessToken();
        if (! $token) {
            return [];
        }

        $property = $this->propertyUrl();
        if (! $property) {
            return [];
        }

        $response = Http::withToken($token)
            ->timeout(30)
            ->post(self::API_BASE.'/sites/'.urlencode($property).'/searchAnalytics/query', [
                'startDate' => now()->subDays($days)->toDateString(),
                'endDate' => now()->toDateString(),
                'dimensions' => ['page'],
                'rowLimit' => 1000,
            ]);

        if (! $response->successful()) {
            Log::warning('GSC searchAnalytics failed', ['body' => $response->body()]);

            return [];
        }

        return array_map(fn ($row) => [
            'page' => $row['keys'][0] ?? '',
            'clicks' => (int) ($row['clicks'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
            'ctr' => (float) ($row['ctr'] ?? 0),
            'position' => (float) ($row['position'] ?? 0),
        ], $response->json('rows') ?? []);
    }

    /**
     * Import GSC data into seo_metas.gsc_* fields. Returns number of records updated.
     */
    public function importIntoMetas(int $days = 28): int
    {
        $rows = $this->fetchSearchAnalytics($days);
        $updated = 0;

        foreach ($rows as $row) {
            if (! $row['page']) {
                continue;
            }

            $meta = SeoMeta::where('canonical_url', $row['page'])->first();
            if (! $meta) {
                continue;
            }

            $meta->forceFill([
                'gsc_clicks' => $row['clicks'],
                'gsc_impressions' => $row['impressions'],
                'gsc_position' => round($row['position'], 1),
                'gsc_updated_at' => now(),
            ])->saveQuietly();

            $updated++;
        }

        return $updated;
    }

    private function accessToken(): ?string
    {
        $refreshToken = (string) Setting::get('seo.gsc.refresh_token', '');
        if ($refreshToken === '') {
            return null;
        }

        $response = Http::asForm()->post(self::OAUTH_TOKEN, [
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'grant_type' => 'refresh_token',
        ]);

        return $response->successful() ? (string) $response->json('access_token') : null;
    }

    private function clientId(): string
    {
        return (string) seo_setting('gsc.client_id', config('Seo.gsc.client_id', ''));
    }

    private function clientSecret(): string
    {
        return (string) seo_setting('gsc.client_secret', config('Seo.gsc.client_secret', ''));
    }

    private function redirectUri(): string
    {
        $configured = (string) seo_setting('gsc.redirect_uri', config('Seo.gsc.redirect_uri', ''));

        return $configured !== '' ? $configured : route('settings.seo.gsc.callback');
    }

    private function propertyUrl(): string
    {
        return (string) seo_setting('gsc.property_url', config('Seo.gsc.property_url', config('app.url', '')));
    }
}
