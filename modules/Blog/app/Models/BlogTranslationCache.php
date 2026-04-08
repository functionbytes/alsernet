<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class BlogTranslationCache extends Model
{
    protected $table = 'blog_translation_cache';

    protected $fillable = [
        'source_hash',
        'source_locale',
        'target_locale',
        'field_name',
        'translated_text',
        'provider',
    ];

    /**
     * Look up a cached translation for a specific field.
     */
    public static function lookup(string $text, string $sourceLocale, string $targetLocale, string $fieldName, string $provider = 'deepl'): ?string
    {
        $hash = hash('sha256', $text);

        return static::query()
            ->where('source_hash', $hash)
            ->where('source_locale', $sourceLocale)
            ->where('target_locale', $targetLocale)
            ->where('field_name', $fieldName)
            ->where('provider', $provider)
            ->value('translated_text');
    }

    /**
     * Store a translated text in cache.
     */
    public static function store(string $text, string $sourceLocale, string $targetLocale, string $fieldName, string $translatedText, string $provider = 'deepl'): void
    {
        static::query()->updateOrCreate(
            [
                'source_hash' => hash('sha256', $text),
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'field_name' => $fieldName,
            ],
            [
                'translated_text' => $translatedText,
                'provider' => $provider,
            ]
        );
    }
}
