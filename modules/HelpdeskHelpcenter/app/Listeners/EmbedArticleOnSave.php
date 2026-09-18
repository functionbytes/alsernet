<?php

namespace Modules\HelpdeskHelpcenter\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskHelpcenter\Jobs\RegenerateArticleEmbeddingsJob;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;

class EmbedArticleOnSave
{
    public function handle(HelpCenterArticle $article): void
    {
        if (! $article->is_published) {
            return;
        }

        $bodyChanged = $article->wasChanged('body')
            || $article->wasChanged('content')
            || $article->wasChanged('is_published');

        if (! $bodyChanged) {
            return;
        }

        // config('app.locale'), no app()->getLocale(): este listener corre en
        // el ciclo de guardado del artículo, así que el locale "actual" es el
        // de la request que lo disparó (p.ej. un admin con locale=en editando
        // el cuerpo base en es), no el idioma del contenido guardado.
        $locale = config('app.locale', 'es');

        RegenerateArticleEmbeddingsJob::dispatch($article, $locale);

        Log::info('EmbedArticleOnSave: queued embedding regeneration', [
            'article_id' => $article->id,
            'locale' => $locale,
        ]);
    }
}
