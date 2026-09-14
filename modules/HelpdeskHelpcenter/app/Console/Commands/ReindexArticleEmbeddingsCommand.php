<?php

namespace Modules\HelpdeskHelpcenter\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Services\EmbeddingsService;

class ReindexArticleEmbeddingsCommand extends Command
{
    protected $signature = 'helpcenter:embeddings:reindex
                            {--locale=es : Locale to reindex (default: es)}';

    protected $description = 'Regenerate article embeddings for semantic search';

    public function handle(EmbeddingsService $service): int
    {
        $locale = $this->option('locale');

        $articles = HelpCenterArticle::query()
            ->where('is_published', true)
            ->get();

        if ($articles->isEmpty()) {
            $this->info('No published articles found.');

            return self::SUCCESS;
        }

        $this->info("Reindexing {$articles->count()} articles for locale '{$locale}'...");
        $bar = $this->output->createProgressBar($articles->count());
        $bar->start();

        $totalChunks = 0;

        foreach ($articles as $article) {
            $chunks = $service->generateForArticle($article, $locale);
            $totalChunks += $chunks;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Created {$totalChunks} embedding chunks.");

        return self::SUCCESS;
    }
}
