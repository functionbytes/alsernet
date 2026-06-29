<?php

namespace Modules\HelpdeskTranslate\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTranslate\Models\TranslationCache;

/**
 * Translation orchestrator with persistent BD-level caching.
 *
 * Keeps every successful translation in `helpdesk_translation_cache` so
 * recurring messages (e.g. "¿cuándo llega mi pedido?") never hit the paid
 * provider twice. Source/target/provider are part of the hash so identical
 * text translated to two languages or by two providers stays separate.
 */
class CachedTranslator
{
    /** Memoized provider name for the duration of this container instance. */
    private ?string $resolvedProvider = null;

    public function __construct(
        private readonly DeepLTranslationService $deepl,
        private readonly TranslationService $libretranslate,
    ) {}

    /**
     * Translate a single string. Returns the translated text or null when
     * neither cache nor provider could produce a result.
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $provider = $this->resolveProvider();
        $s = strtolower(trim((string) $sourceLang));
        $source = in_array($s, ['', 'auto'], true) ? 'auto' : $s;
        $target = strtolower($targetLang);
        $hash = TranslationCache::makeHash($text, $source, $target, $provider);

        $hit = TranslationCache::query()->where('text_hash', $hash)->first();
        if ($hit) {
            TranslationCache::query()->where('id', $hit->id)->update([
                'hits' => DB::raw('hits + 1'),
                'chars_saved' => DB::raw('chars_saved + '.mb_strlen($text)),
                'last_used_at' => now(),
            ]);

            return $hit->text_translated;
        }

        $translated = $this->callProvider($provider, $text, $target, $sourceLang);

        // Fallback: if the configured provider failed (returned null), try the
        // other one before giving up. This makes the system resilient to
        // LibreTranslate container being down or DeepL key missing.
        if ($translated === null) {
            $fallbackProvider = $provider === 'deepl' ? 'libretranslate' : 'deepl';
            $translated = $this->callProvider($fallbackProvider, $text, $target, $sourceLang);
            if ($translated !== null) {
                $provider = $fallbackProvider;
                $hash = TranslationCache::makeHash($text, $source, $target, $provider);
            }
        }

        if ($translated === null || $translated === '' || $translated === $text) {
            return $translated;
        }

        try {
            TranslationCache::query()->create([
                'text_hash' => $hash,
                'text_source' => $text,
                'text_translated' => $translated,
                'source_lang' => $source,
                'target_lang' => $target,
                'provider' => $provider,
                'hits' => 1,
                'chars_saved' => 0,
                'last_used_at' => now(),
            ]);
        } catch (QueryException) {
            // Race condition: another process inserted the same hash. Ignore.
        }

        return $translated;
    }

    public function resolveProvider(): string
    {
        if ($this->resolvedProvider !== null) {
            return $this->resolvedProvider;
        }

        $stored = Setting::get('helpdesktranslate.provider');
        $configured = ($stored === null || $stored === '')
            ? config('helpdesktranslate.provider', 'deepl')
            : (string) $stored;

        $this->resolvedProvider = in_array($configured, ['deepl', 'libretranslate'], true)
            ? $configured
            : 'deepl';

        return $this->resolvedProvider;
    }

    /**
     * Best-effort language detection. Returns an ISO 639-1 code or null.
     * Used to identify the visitor's language from their first message so
     * the agent panel can auto-translate without manual configuration.
     *
     * Honours the configured provider: if DeepL is the primary, ask DeepL
     * first and fall back to LibreTranslate only when DeepL fails, and vice
     * versa. Previously this method always asked LibreTranslate first, which
     * meant DeepL operators paid the 5s LibreTranslate timeout on every
     * message when their LibreTranslate container was down.
     */
    public function detectLanguage(string $text): ?string
    {
        $text = trim($text);
        if (mb_strlen($text) < 3) {
            return null;
        }

        $primary = $this->resolveProvider();
        $fallback = $primary === 'deepl' ? 'libretranslate' : 'deepl';

        $detected = $this->detectViaProvider($primary, $text);
        if ($detected) {
            return strtolower($detected);
        }

        $detected = $this->detectViaProvider($fallback, $text);

        return $detected ? strtolower($detected) : null;
    }

    private function detectViaProvider(string $provider, string $text): ?string
    {
        if ($provider === 'libretranslate') {
            return $this->libretranslate->detectLanguage($text);
        }

        // DeepL doesn't expose a dedicated /detect endpoint — translating a
        // short snippet returns `detected_source_language` in the response.
        // The cache key inside translateWithDetection uses the snippet, so
        // repeats are free. 40 chars is enough for reliable detection and
        // reduces billable characters when DeepL is the primary provider.
        $sample = mb_substr($text, 0, 40);
        $result = $this->deepl->translateWithDetection($sample, 'ES');

        return $result['detected_source_language'] ?? null;
    }

    private function callProvider(string $provider, string $text, string $target, ?string $source): ?string
    {
        if ($provider === 'libretranslate') {
            $result = $this->libretranslate->translate($text, $source ?? 'auto', $target);

            if (! is_array($result) || ($result['mocked'] ?? false) || ($result['failed'] ?? false)) {
                return null;
            }

            return $result['translated'] ?? null;
        }

        return $this->deepl->translate($text, $target, $source);
    }
}
