<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Log;

/**
 * Detects the customer's language and translates fixed bot messages into it,
 * reusing the helpdesk's CachedTranslator (DeepL/LibreTranslate) when available.
 * AI nodes answer in the customer's language natively via the prompt, so they
 * don't need this — only fixed messages do.
 */
class ChatFlowLocalizer
{
    private const BASE_LANG = 'es';

    /**
     * @param  object|null  $translator  Helpdesk CachedTranslator (optional)
     */
    public function __construct(
        private readonly ?object $translator = null,
    ) {}

    /**
     * Detect the language (ISO 639-1) of a text, or null.
     */
    public function detect(string $text): ?string
    {
        if ($this->translator === null || trim($text) === '') {
            return null;
        }

        try {
            return $this->translator->detectLanguage($text);
        } catch (\Throwable $e) {
            Log::debug('ChatFlowLocalizer: language detection failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Translate a bot message into the target language. Returns the original
     * text when no translation is needed or possible.
     */
    public function localize(string $text, ?string $targetLang): string
    {
        $targetLang = $targetLang ? strtolower(substr($targetLang, 0, 2)) : null;

        if ($this->translator === null
            || $targetLang === null
            || $targetLang === self::BASE_LANG
            || trim($text) === '') {
            return $text;
        }

        try {
            $translated = $this->translator->translate($text, $targetLang, self::BASE_LANG);

            return $translated ?: $text;
        } catch (\Throwable $e) {
            Log::debug('ChatFlowLocalizer: translation failed', ['error' => $e->getMessage()]);

            return $text;
        }
    }

    public function baseLang(): string
    {
        return self::BASE_LANG;
    }
}
