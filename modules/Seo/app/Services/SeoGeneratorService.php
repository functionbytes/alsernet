<?php

namespace Modules\Seo\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Models\SeoMeta;

class SeoGeneratorService
{
    /**
     * Generate SEO meta from model attributes and save it.
     */
    public function generateFromModel(Model $model): SeoMeta
    {
        if (! method_exists($model, 'seoMeta')) {
            throw new \InvalidArgumentException('Model must use the HasSeo trait');
        }

        $title = $this->extractTitle($model);
        $description = $this->extractDescription($model);
        $keywords = $this->extractKeywords($title, $description);

        return $model->seoMeta()->updateOrCreate(
            ['seoable_id' => $model->id, 'seoable_type' => get_class($model)],
            array_filter([
                'title' => $this->optimizeTitle($title),
                'description' => $this->optimizeDescription($description),
                'keywords' => $keywords,
                'og_title' => $this->optimizeTitle($title),
                'og_description' => $this->optimizeDescription($description),
                'og_type' => 'website',
                'twitter_card' => 'summary_large_image',
                'robots' => 'index,follow',
            ], fn ($v) => ! is_null($v))
        );
    }

    /**
     * Bulk generate SEO for multiple models.
     *
     * @param  array<int>  $modelIds
     * @return array{generated: int, skipped: int, errors: int}
     */
    public function bulkGenerate(string $modelClass, array $modelIds, bool $overwrite = false): array
    {
        $generated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($modelIds as $id) {
            try {
                $model = $modelClass::find($id);

                if (! $model) {
                    $errors++;

                    continue;
                }

                if (! $overwrite && $model->seoMeta) {
                    $skipped++;

                    continue;
                }

                $this->generateFromModel($model);
                $generated++;
            } catch (\Throwable $e) {
                Log::warning("SeoGeneratorService: failed for {$modelClass}#{$id}: ".$e->getMessage());
                $errors++;
            }
        }

        return compact('generated', 'skipped', 'errors');
    }

    private function extractTitle(Model $model): ?string
    {
        foreach (['title', 'name', 'subject', 'heading'] as $field) {
            if (! empty($model->$field)) {
                return (string) $model->$field;
            }
        }

        return null;
    }

    private function extractDescription(Model $model): ?string
    {
        foreach (['description', 'excerpt', 'summary', 'body', 'content'] as $field) {
            if (! empty($model->$field)) {
                $text = strip_tags((string) $model->$field);

                return $text ?: null;
            }
        }

        return null;
    }

    private function optimizeTitle(?string $title): ?string
    {
        if (! $title) {
            return null;
        }

        $title = strip_tags($title);

        if (mb_strlen($title) > 60) {
            $title = mb_substr($title, 0, 57);
            $title = mb_substr($title, 0, mb_strrpos($title, ' ') ?: mb_strlen($title)).'...';
        }

        return $title;
    }

    private function optimizeDescription(?string $description): ?string
    {
        if (! $description) {
            return null;
        }

        $description = strip_tags($description);
        $description = preg_replace('/\s+/', ' ', trim($description));

        if (mb_strlen($description) > 155) {
            $description = mb_substr($description, 0, 152);
            $description = mb_substr($description, 0, mb_strrpos($description, ' ') ?: mb_strlen($description)).'...';
        }

        return $description;
    }

    private function extractKeywords(?string $title, ?string $description): ?string
    {
        if (! $title && ! $description) {
            return null;
        }

        $stopwords = [
            'para', 'como', 'este', 'esta', 'esto', 'estos', 'estas', 'pero', 'más', 'muy',
            'todo', 'toda', 'todos', 'todas', 'cuando', 'donde', 'quien', 'cuya', 'cuyo',
            'sobre', 'entre', 'desde', 'hasta', 'hacia', 'aunque', 'porque', 'pues', 'sino',
            'cada', 'otro', 'otra', 'otros', 'otras', 'mismo', 'misma', 'bien', 'entonces',
            'puede', 'tiene', 'hacer', 'también', 'después', 'antes', 'siempre', 'nunca',
            'solo', 'algo', 'nada', 'mucho', 'poco', 'gran', 'mejor',
            'their', 'that', 'with', 'from', 'have', 'this', 'will', 'your', 'what',
        ];

        $text = strtolower(($title ?? '').' '.($description ?? ''));
        $text = preg_replace('/[^a-záéíóúüña-z0-9\s]/u', '', $text);
        $words = array_filter(
            explode(' ', $text),
            fn ($w) => mb_strlen($w) > 4 && ! in_array($w, $stopwords, true)
        );

        $frequency = array_count_values($words);
        arsort($frequency);
        $topKeywords = array_slice(array_keys($frequency), 0, 8);

        return implode(', ', $topKeywords) ?: null;
    }
}
