<?php

namespace Modules\Seo\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Models\SeoMeta;

class WebhookNotificationService
{
    public function sendScoreDrop(string $pageTitle, string $pageUrl, int $previousScore, int $currentScore): void
    {
        $drop = $previousScore - $currentScore;
        $message = "⚠️ *SEO Score Drop* — *{$pageTitle}*\n"
            ."Score bajó de {$previousScore} → {$currentScore} (-{$drop} puntos)\n"
            ."URL: {$pageUrl}";

        $this->send($message, [
            'title' => 'SEO Score Drop',
            'color' => 0xFA896B,
            'fields' => [
                ['name' => 'Página', 'value' => $pageTitle, 'inline' => true],
                ['name' => 'Score anterior', 'value' => (string) $previousScore, 'inline' => true],
                ['name' => 'Score actual', 'value' => (string) $currentScore, 'inline' => true],
            ],
        ]);
    }

    public function sendRedirectChainAlert(int $chainCount): void
    {
        $message = "🔗 *SEO Alert* — {$chainCount} cadenas de redirecciones detectadas.\nRevisa el módulo SEO para más detalles.";

        $this->send($message, ['title' => 'Redirect Chains Detected', 'color' => 0xFEC90F]);
    }

    public function sendOrphanAlert(int $count, string $modelType): void
    {
        $message = "📄 *SEO Alert* — {$count} páginas de tipo `{$modelType}` sin configuración SEO.";

        $this->send($message, ['title' => 'Orphan Pages Detected', 'color' => 0xB10100]);
    }

    /**
     * @param  Collection<int, SeoMeta>  $pages
     */
    public function sendContentDecay(Collection $pages, int $daysStale): void
    {
        $count = $pages->count();
        $message = "📉 *SEO Alert* — {$count} páginas con content decay (sin actualizar hace ".$daysStale." días).\nRevisa y refresca el contenido para recuperar posición en SERP.";

        $this->send($message, [
            'title' => 'Content Decay Detected',
            'color' => 0xFA896B,
            'fields' => [
                ['name' => 'Páginas afectadas', 'value' => (string) $count, 'inline' => true],
                ['name' => 'Umbral (días)', 'value' => (string) $daysStale, 'inline' => true],
            ],
        ]);
    }

    private function send(string $text, array $embedData = []): void
    {
        $this->sendSlack($text);
        $this->sendDiscord($text, $embedData);
    }

    private function sendSlack(string $text): void
    {
        $url = (string) seo_setting('webhooks.slack_url', config('Seo.webhooks.slack_url', ''));

        if (empty($url)) {
            return;
        }

        $payload = [
            'text' => $text,
            'username' => config('app.name').' SEO',
            'icon_emoji' => ':chart_with_upwards_trend:',
        ];

        $this->postSigned($url, $payload, 'slack');
    }

    private function sendDiscord(string $text, array $embedData = []): void
    {
        $url = (string) seo_setting('webhooks.discord_url', config('Seo.webhooks.discord_url', ''));

        if (empty($url)) {
            return;
        }

        $payload = ['content' => $text];

        if (! empty($embedData)) {
            $payload = [
                'embeds' => [[
                    'title' => $embedData['title'] ?? 'SEO Alert',
                    'description' => $text,
                    'color' => $embedData['color'] ?? 0xB10100,
                    'fields' => $embedData['fields'] ?? [],
                    'footer' => ['text' => config('app.name').' — SEO Module'],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ];
        }

        $this->postSigned($url, $payload, 'discord');
    }

    /**
     * POST a webhook payload, optionally signed with HMAC-SHA256 so the
     * receiver can verify authenticity. Headers added when a signing secret
     * is configured:
     *   - X-Seo-Signature: sha256=<hex digest of raw body>
     *   - X-Seo-Timestamp: Unix timestamp (prevents replay attacks)
     */
    private function postSigned(string $url, array $payload, string $target): void
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $secret = (string) seo_setting('webhooks.signing_secret', config('Seo.webhooks.signing_secret', ''));
        $timestamp = (string) now()->timestamp;

        $headers = ['Content-Type' => 'application/json'];
        if ($secret !== '' && $body !== false) {
            $headers['X-Seo-Signature'] = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);
            $headers['X-Seo-Timestamp'] = $timestamp;
        }

        try {
            Http::timeout(5)
                ->withHeaders($headers)
                ->withBody($body ?: '{}', 'application/json')
                ->post($url);
        } catch (\Throwable $e) {
            Log::warning("SEO {$target} webhook failed: ".$e->getMessage());
        }
    }
}
