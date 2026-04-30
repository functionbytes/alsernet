<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\Template\Template;

/**
 * Fetch un feed RSS y genera/actualiza una plantilla de campaña con los últimos items.
 */
class RssToEmailCommand extends Command
{
    protected $signature = 'campaign:rss-to-email
                            {campaign_uid : UID de la campaña tipo rss}
                            {--limit=5 : Número de items a incluir}
                            {--dry-run : Solo mostrar, no guardar}';

    protected $description = 'Genera contenido de campaña desde un feed RSS';

    public function handle(): int
    {
        $campaign = Campaign::where('uid', $this->argument('campaign_uid'))->firstOrFail();

        if ($campaign->type !== 'rss') {
            $this->error('La campaña no es de tipo rss.');

            return self::FAILURE;
        }

        $feedUrl = $campaign->rss_url ?? config('campaign.rss.default_feed');
        if (empty($feedUrl)) {
            $this->error('No hay URL RSS configurada.');

            return self::FAILURE;
        }

        try {
            $response = Http::timeout(10)->get($feedUrl);
            if (! $response->successful()) {
                $this->error('Error al fetch RSS: '.$response->status());

                return self::FAILURE;
            }

            $xml = simplexml_load_string($response->body());
            $items = [];
            $count = 0;
            $limit = (int) $this->option('limit');

            foreach ($xml->channel->item ?? $xml->entry ?? [] as $item) {
                if ($count >= $limit) {
                    break;
                }
                $items[] = [
                    'title' => (string) ($item->title ?? ''),
                    'link' => (string) ($item->link ?? $item->url ?? ''),
                    'description' => strip_tags((string) ($item->description ?? $item->summary ?? '')),
                    'pubDate' => (string) ($item->pubDate ?? $item->published ?? ''),
                ];
                $count++;
            }

            $html = $this->renderRssTemplate($items, $campaign);

            if ($this->option('dry-run')) {
                $this->info('HTML generado ('.strlen($html).' chars):');
                $this->line(substr($html, 0, 500).'...');

                return self::SUCCESS;
            }

            $template = $campaign->template ?? new Template;
            $template->content = $html;
            $template->html = $html;
            $template->save();

            if (! $campaign->template_id) {
                $campaign->template_id = $template->id;
                $campaign->save();
            }

            Log::info('RSS-to-email generado', ['campaign_uid' => $campaign->uid, 'items' => count($items)]);
            $this->info(count($items).' items procesados. Plantilla actualizada.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());
            Log::error('RSS-to-email fallo', ['campaign_uid' => $campaign->uid, 'error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }

    protected function renderRssTemplate(array $items, Campaign $campaign): string
    {
        $html = '<h1>'.e($campaign->subject)."</h1>\n";
        foreach ($items as $item) {
            $html .= '<div style="margin-bottom:24px;">';
            $html .= '<h2><a href="'.e($item['link']).'">'.e($item['title'])."</a></h2>\n";
            $html .= '<p style="color:#666;font-size:12px;">'.e($item['pubDate'])."</p>\n";
            $html .= '<p>'.e($item['description'])."</p>\n";
            $html .= '</div>';
        }

        return $html;
    }
}
