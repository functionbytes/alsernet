<?php

namespace Modules\HelpdeskHelpcenter\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HelpdeskHelpcenterTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $articles = DB::connection('helpdesk')
            ->table('helpdesk_helpcenter_articles')
            ->limit(7)
            ->get();

        if ($articles->isEmpty()) {
            $this->command->warn('No hay articles — saltando translations');

            return;
        }

        $created = 0;
        foreach ($articles as $article) {
            foreach (['es', 'en'] as $locale) {
                $title = $article->title ?? 'Artículo sin título';
                $body = $article->body ?? $article->content ?? 'Contenido del artículo.';

                if ($locale === 'en') {
                    $title = '[EN] '.$title;
                    $body = '[Translated to English] '.$body;
                }

                DB::connection('helpdesk')->table('helpdesk_helpcenter_article_translations')->updateOrInsert(
                    ['article_id' => $article->id, 'locale' => $locale],
                    [
                        'title' => Str::limit($title, 250, ''),
                        'slug' => Str::slug($title.'-'.$locale).'-'.$article->id,
                        'body' => $body,
                        'meta_description' => Str::limit(strip_tags($body), 160, ''),
                        'is_published' => true,
                        'published_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $created++;
            }
        }

        $this->command->info("Translations demo creadas ({$created})");
    }
}
