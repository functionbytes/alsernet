<?php

namespace Modules\Social\Services;

use Modules\Social\Models\Post;
use Modules\Social\Models\PostTranslation;
use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslationService
{
    protected GoogleTranslate $translator;

    public function __construct()
    {
        $this->translator = new GoogleTranslate;
    }

    public function translatePost(Post $post, array $languages): array
    {
        $translations = [];

        foreach ($languages as $lang) {
            $this->translator->setTarget($lang);
            $translatedContent = $this->translator->translate($post->content);

            $translation = PostTranslation::updateOrCreate(
                [
                    'post_id' => $post->id,
                    'language' => $lang,
                ],
                [
                    'content' => $translatedContent,
                    'translation_provider' => 'google',
                    'translated_at' => now(),
                ]
            );

            $translations[$lang] = $translation;
        }

        return $translations;
    }

    public function getAvailableLanguages(): array
    {
        return [
            'es' => 'Español',
            'en' => 'English',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'ru' => 'Русский',
            'ja' => '日本語',
            'ko' => '한국어',
            'zh-CN' => '中文 (简体)',
            'ar' => 'العربية',
        ];
    }

    public function detectLanguage(string $text): string
    {
        return $this->translator->translate($text) ? $this->translator->getLastDetectedSource() : 'unknown';
    }
}
