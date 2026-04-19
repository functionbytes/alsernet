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

        $this->send($message, ['title' => 'Orphan Pages Detected', 'color' => 0x90BB13]);
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
        $url = config('Seo.webhooks.slack_url', '');

        if (empty($url)) {
            return;
        }

        try {
            Http::timeout(5)->post($url, [
                'text' => $text,
                'username' => config('app.name').' SEO',
                'icon_emoji' => ':chart_with_upwards_trend:',
            ]);
        } catch (\Throwable $e) {
            Log::warning('SEO Slack webhook failed: '.$e->getMessage());
        }
    }

    private function sendDiscord(string $text, array $embedData = []): void
    {
        $url = config('Seo.webhooks.discord_url', '');

        if (empty($url)) {
            return;
        }

        $payload = ['content' => $text];

        if (! empty($embedData)) {
            $payload = [
                'embeds' => [[
                    'title' => $embedData['title'] ?? 'SEO Alert',
                    'description' => $text,
                    'color' => $embedData['color'] ?? 0x90BB13,
                    'fields' => $embedData['fields'] ?? [],
                    'footer' => ['text' => config('app.name').' — SEO Module'],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ];
        }

        try {
            Http::timeout(5)->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('SEO Discord webhook failed: '.$e->getMessage());
        }
    }
}
