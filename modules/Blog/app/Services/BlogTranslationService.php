<?php

namespace Modules\Blog\Services;

use App\Services\DeepLTranslationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Blog\Enums\TranslationStatus;
use Modules\Blog\Http\Controllers\BlogPostTranslationController;
use Modules\Blog\Models\BlogGlossaryTerm;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogPostTranslation;
use Modules\Blog\Models\BlogTranslationCache;
use Modules\Blog\Models\BlogTranslationLog;

class BlogTranslationService
{
    private const TRANSLATABLE_FIELDS = ['title', 'description', 'content', 'seo_title', 'seo_description', 'seo_keywords'];

    public function __construct(
        private readonly DeepLTranslationService $deepl
    ) {}

    public function isConfigured(): bool
    {
        return $this->deepl->isConfigured();
    }

    /**
     * Translate a post to a target locale, with caching and glossary support.
     *
     * @param  string[]|null  $onlyFields  If provided, only translate these fields
     */
    public function translatePost(
        BlogPost $post,
        string $targetLocale,
        ?string $sourceLocale = null,
        ?array $onlyFields = null,
        string $provider = 'deepl'
    ): BlogPostTranslation {
        $fields = $onlyFields ?? self::TRANSLATABLE_FIELDS;
        $sourceLocale = $sourceLocale ?? app()->getLocale();

        $sourceTexts = $this->getSourceTexts($post, $sourceLocale, $fields);
        $translated = $this->translateTexts($sourceTexts, $sourceLocale, $targetLocale, $fields, $provider);
        $translated = $this->applyGlossary($translated, $sourceLocale, $targetLocale);

        $fillable = [];
        foreach ($fields as $field) {
            if (isset($translated[$field]) && $translated[$field] !== '') {
                $fillable[$field] = $translated[$field];
            }
        }

        if (isset($fillable['title'])) {
            $fillable['slug'] = Str::slug($fillable['title']);
        }

        $fillable['status'] = TranslationStatus::AutoTranslated->value;
        $fillable['translated_at'] = now();
        $fillable['source_hash'] = BlogPostTranslationController::computeSourceHash($post);

        $existing = $post->translations()->where('locale', $targetLocale)->first();

        if ($existing && $onlyFields) {
            $existing->update($fillable);
            $translation = $existing->fresh();
        } else {
            $translation = $post->translations()->updateOrCreate(
                ['locale' => $targetLocale],
                $fillable
            );
        }

        $charactersUsed = collect($sourceTexts)->sum(fn ($text) => mb_strlen($text));

        $this->logTranslation($post, $targetLocale, $onlyFields ? 'fields_translated' : 'auto_translated', [
            'fields' => $fields,
            'provider' => $provider,
            'characters_used' => $charactersUsed,
        ]);

        return $translation;
    }

    /**
     * Translate all supported locales for a post.
     *
     * @return array{results: array<string, string>, translations: array<string, BlogPostTranslation>}
     */
    public function translateAllLocales(
        BlogPost $post,
        ?string $sourceLocale = null,
        string $provider = 'deepl'
    ): array {
        $supported = BlogPostService::getSupportedLocales();
        $results = [];
        $translations = [];

        foreach ($supported as $locale) {
            try {
                $translations[$locale] = $this->translatePost($post, $locale, $sourceLocale, null, $provider);
                $results[$locale] = 'ok';
            } catch (\RuntimeException $e) {
                Log::error('Blog translation failed for locale', [
                    'error' => $e->getMessage(),
                    'post_id' => $post->id,
                    'locale' => $locale,
                ]);
                $results[$locale] = 'error: '.$e->getMessage();
            }
        }

        return ['results' => $results, 'translations' => $translations];
    }

    /**
     * Get the source texts from the post (or from a translation if translating from a non-default locale).
     *
     * @return array<string, string>
     */
    private function getSourceTexts(BlogPost $post, string $sourceLocale, array $fields): array
    {
        $defaultLocale = app()->getLocale();
        $texts = [];

        if ($sourceLocale === $defaultLocale) {
            foreach ($fields as $field) {
                $texts[$field] = $post->{$field} ?? '';
            }
        } else {
            $sourceTrans = $post->translation($sourceLocale);
            foreach ($fields as $field) {
                $texts[$field] = $sourceTrans?->{$field} ?? $post->{$field} ?? '';
            }
        }

        return $texts;
    }

    /**
     * Translate texts using the configured provider, with cache support.
     *
     * @return array<string, string>
     */
    private function translateTexts(array $sourceTexts, string $sourceLocale, string $targetLocale, array $fields, string $provider): array
    {
        $toTranslate = [];
        $cached = [];

        foreach ($fields as $field) {
            $text = $sourceTexts[$field] ?? '';
            if ($text === '') {
                $cached[$field] = '';

                continue;
            }

            $cachedText = BlogTranslationCache::lookup($text, $sourceLocale, $targetLocale, $field, $provider);
            if ($cachedText !== null) {
                $cached[$field] = $cachedText;
            } else {
                $toTranslate[$field] = $text;
            }
        }

        if (! empty($toTranslate)) {
            $textsArray = array_values($toTranslate);
            $fieldsOrder = array_keys($toTranslate);

            $translatedTexts = $this->deepl->translate($textsArray, $targetLocale, $sourceLocale);

            foreach ($fieldsOrder as $i => $field) {
                $cached[$field] = $translatedTexts[$i];

                BlogTranslationCache::store(
                    $toTranslate[$field],
                    $sourceLocale,
                    $targetLocale,
                    $field,
                    $translatedTexts[$i],
                    $provider
                );
            }
        }

        return $cached;
    }

    /**
     * Apply glossary replacements to translated texts.
     *
     * @return array<string, string>
     */
    private function applyGlossary(array $translated, string $sourceLocale, string $targetLocale): array
    {
        $terms = BlogGlossaryTerm::query()
            ->where('source_locale', $sourceLocale)
            ->where('target_locale', $targetLocale)
            ->get();

        if ($terms->isEmpty()) {
            return $translated;
        }

        foreach ($translated as $field => $text) {
            if ($text === '') {
                continue;
            }

            foreach ($terms as $term) {
                $replacement = $term->do_not_translate ? $term->source_term : $term->target_term;
                $translated[$field] = str_ireplace($term->source_term, $replacement, $text);
            }
        }

        return $translated;
    }

    /**
     * Log a translation action.
     */
    public function logTranslation(BlogPost $post, string $locale, string $action, array $extra = []): void
    {
        BlogTranslationLog::query()->create([
            'blog_post_id' => $post->id,
            'locale' => $locale,
            'action' => $action,
            'user_id' => auth()->id(),
            'fields_changed' => $extra['fields'] ?? null,
            'provider' => $extra['provider'] ?? 'deepl',
            'characters_used' => $extra['characters_used'] ?? null,
            'metadata' => $extra['metadata'] ?? null,
        ]);
    }

    /**
     * Validate translation quality — detect suspiciously short or broken HTML translations.
     *
     * @return array<string, string[]> Warnings keyed by field name
     */
    public function validateQuality(BlogPost $post, BlogPostTranslation $translation): array
    {
        $warnings = [];

        foreach (self::TRANSLATABLE_FIELDS as $field) {
            $source = $post->{$field} ?? '';
            $translated = $translation->{$field} ?? '';

            if ($source === '' || $translated === '') {
                continue;
            }

            $sourceLen = mb_strlen(strip_tags($source));
            $transLen = mb_strlen(strip_tags($translated));

            if ($sourceLen > 50 && $transLen < $sourceLen * 0.3) {
                $warnings[$field][] = 'Traducción sospechosamente corta ('.$transLen.' vs '.$sourceLen.' caracteres fuente)';
            }

            if ($field === 'content') {
                $sourceOpenTags = preg_match_all('/<[a-z][^>]*>/i', $source);
                $transOpenTags = preg_match_all('/<[a-z][^>]*>/i', $translated);
                $sourceCloseTags = preg_match_all('/<\/[a-z]+>/i', $source);
                $transCloseTags = preg_match_all('/<\/[a-z]+>/i', $translated);

                if (abs($sourceOpenTags - $transOpenTags) > 2 || abs($sourceCloseTags - $transCloseTags) > 2) {
                    $warnings[$field][] = 'Estructura HTML difiere significativamente del contenido fuente';
                }
            }
        }

        return $warnings;
    }

    /**
     * Get translation coverage stats for a post.
     *
     * @return array<string, array{status: string, completeness: int, is_stale: bool}>
     */
    public function getCoverageStats(BlogPost $post): array
    {
        $supported = BlogPostService::getSupportedLocales();
        $stats = [];

        foreach ($supported as $locale) {
            $trans = $post->translation($locale);

            if (! $trans) {
                $stats[$locale] = [
                    'status' => 'empty',
                    'completeness' => 0,
                    'is_stale' => false,
                ];

                continue;
            }

            $filledFields = 0;
            foreach (self::TRANSLATABLE_FIELDS as $field) {
                if (! empty($trans->{$field})) {
                    $filledFields++;
                }
            }

            $completeness = (int) round(($filledFields / count(self::TRANSLATABLE_FIELDS)) * 100);
            $currentHash = BlogPostTranslationController::computeSourceHash($post);
            $isStale = $trans->source_hash !== null && $trans->source_hash !== $currentHash;

            $stats[$locale] = [
                'status' => $trans->status?->value ?? 'manual',
                'completeness' => $completeness,
                'is_stale' => $isStale,
            ];
        }

        return $stats;
    }

    /**
     * Get usage metrics for DeepL translations.
     *
     * @return array{total_characters: int, total_translations: int, by_locale: array, by_date: array}
     */
    public static function getUsageMetrics(?string $fromDate = null, ?string $toDate = null): array
    {
        $query = BlogTranslationLog::query();

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        $logs = $query->get();

        return [
            'total_characters' => $logs->sum('characters_used'),
            'total_translations' => $logs->count(),
            'by_locale' => $logs->groupBy('locale')->map(fn ($group) => [
                'count' => $group->count(),
                'characters' => $group->sum('characters_used'),
            ])->toArray(),
            'by_action' => $logs->groupBy('action')->map->count()->toArray(),
        ];
    }
}
