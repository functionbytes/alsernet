<?php

namespace Modules\Blog\Services;

use Modules\Blog\Models\BlogPost;

class BlogTranslationExportService
{
    /**
     * Export translations to a CSV-ready array.
     *
     * @return array<int, array<string, string>>
     */
    public function exportToCsv(?string $locale = null): array
    {
        $posts = BlogPost::query()
            ->with('translations')
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($posts as $post) {
            $translations = $locale
                ? $post->translations->where('locale', $locale)
                : $post->translations;

            foreach ($translations as $trans) {
                $rows[] = [
                    'post_id' => $post->id,
                    'source_title' => $post->title,
                    'locale' => $trans->locale,
                    'title' => $trans->title ?? '',
                    'slug' => $trans->slug ?? '',
                    'description' => $trans->description ?? '',
                    'content' => $trans->content ?? '',
                    'seo_title' => $trans->seo_title ?? '',
                    'seo_description' => $trans->seo_description ?? '',
                    'seo_keywords' => $trans->seo_keywords ?? '',
                    'status' => $trans->status?->value ?? '',
                    'translated_at' => $trans->translated_at?->toDateTimeString() ?? '',
                ];
            }
        }

        return $rows;
    }

    /**
     * Import translations from a CSV-ready array.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array{imported: int, skipped: int, errors: string[]}
     */
    public function importFromCsv(array $rows): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $postId = $row['post_id'] ?? null;
            $locale = $row['locale'] ?? null;

            if (! $postId || ! $locale) {
                $errors[] = "Fila {$i}: faltan post_id o locale";
                $skipped++;

                continue;
            }

            $post = BlogPost::query()->find($postId);
            if (! $post) {
                $errors[] = "Fila {$i}: post {$postId} no encontrado";
                $skipped++;

                continue;
            }

            $fillable = array_filter([
                'title' => $row['title'] ?? null,
                'slug' => $row['slug'] ?? null,
                'description' => $row['description'] ?? null,
                'content' => $row['content'] ?? null,
                'seo_title' => $row['seo_title'] ?? null,
                'seo_description' => $row['seo_description'] ?? null,
                'seo_keywords' => $row['seo_keywords'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            if (empty($fillable)) {
                $skipped++;

                continue;
            }

            $fillable['status'] = 'manual';

            $post->translations()->updateOrCreate(
                ['locale' => $locale],
                $fillable
            );

            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }
}
