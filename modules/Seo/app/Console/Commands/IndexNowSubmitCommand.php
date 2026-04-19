<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Modules\Seo\Jobs\SubmitUrlsToIndexNowJob;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Services\IndexNowService;

class IndexNowSubmitCommand extends Command
{
    protected $signature = 'seo:indexnow-submit
        {--urls=* : URLs absolutas a enviar (se puede repetir)}
        {--recent= : Enviar URLs de SeoMeta actualizadas en las últimas N horas}
        {--queue : Enviar por queue en lugar de sincrónicamente}';

    protected $description = 'Enviar URLs al protocolo IndexNow (Bing/Yandex/Seznam)';

    public function handle(IndexNowService $service): int
    {
        if (! $service->enabled()) {
            $this->warn('IndexNow no está activo. Revisa SEO_INDEXNOW_ENABLED / SEO_INDEXNOW_KEY / APP_URL.');

            return self::FAILURE;
        }

        $urls = (array) $this->option('urls');

        if ($recent = $this->option('recent')) {
            $urls = array_merge($urls, $this->recentUrls((int) $recent));
        }

        $urls = array_values(array_unique(array_filter($urls)));

        if (empty($urls)) {
            $this->warn('No hay URLs que enviar.');

            return self::SUCCESS;
        }

        $this->info('URLs a enviar: '.count($urls));

        if ($this->option('queue')) {
            SubmitUrlsToIndexNowJob::dispatch($urls);
            $this->info('Job encolado (queue=seo).');

            return self::SUCCESS;
        }

        $result = $service->submit($urls);

        $this->line($result['message']);

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    private function recentUrls(int $hours): array
    {
        return SeoMeta::query()
            ->where('updated_at', '>=', now()->subHours($hours))
            ->whereNotNull('canonical_url')
            ->pluck('canonical_url')
            ->filter()
            ->values()
            ->all();
    }
}
