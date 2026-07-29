<?php

namespace Modules\HelpdeskTranslate\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTranslate\Models\TranslateUsage;
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
     *
     * $feature identifica quién pidió la traducción (manual | auto_incoming |
     * auto_outgoing) para el reporte de consumo — ver helpdesk_translate_usage.
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null, string $feature = 'other'): ?string
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

        $translated = $this->callProvider($provider, $text, $target, $sourceLang, $feature);

        // Fallback: if the configured provider failed (returned null), try the
        // other one before giving up. This makes the system resilient to
        // LibreTranslate container being down or DeepL key missing.
        if ($translated === null) {
            $fallbackProvider = $provider === 'deepl' ? 'libretranslate' : 'deepl';
            $translated = $this->callProvider($fallbackProvider, $text, $target, $sourceLang, $feature);
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
    public function detectLanguage(string $text, string $feature = 'other'): ?string
    {
        $text = trim($text);
        if (mb_strlen($text) < 3) {
            return null;
        }

        $primary = $this->resolveProvider();
        $fallback = $primary === 'deepl' ? 'libretranslate' : 'deepl';

        $detected = $this->detectViaProvider($primary, $text, $feature);
        if ($detected) {
            return strtolower($detected);
        }

        $detected = $this->detectViaProvider($fallback, $text, $feature);

        return $detected ? strtolower($detected) : null;
    }

    private function detectViaProvider(string $provider, string $text, string $feature = 'other'): ?string
    {
        if ($provider === 'libretranslate') {
            $detected = $this->libretranslate->detectLanguage($text);
            $this->logUsage($provider, 'detect', $feature, mb_strlen($text), null, null, $detected !== null);

            return $detected;
        }

        // DeepL doesn't expose a dedicated /detect endpoint — translating a
        // short snippet returns `detected_source_language` in the response.
        // The cache key inside translateWithDetection uses the snippet, so
        // repeats are free. 40 chars is enough for reliable detection and
        // reduces billable characters when DeepL is the primary provider.
        $sample = mb_substr($text, 0, 40);
        $result = $this->deepl->translateWithDetection($sample, 'ES');
        $detected = $result['detected_source_language'] ?? null;
        $this->logUsage($provider, 'detect', $feature, mb_strlen($sample), null, null, $detected !== null);

        return $detected;
    }

    private function callProvider(string $provider, string $text, string $target, ?string $source, string $feature = 'other'): ?string
    {
        // Circuit breaker: si el proveedor viene fallando (caído / sin clave),
        // saltarlo al instante en vez de comerse su timeout (10-15s). Con ambos
        // proveedores abiertos, translate() devuelve null sin colgar el request.
        if ($this->isCircuitOpen($provider)) {
            return null;
        }

        $translated = $this->invokeProvider($provider, $text, $target, $source);

        if ($translated === null) {
            $this->recordFailure($provider);
        } else {
            $this->recordSuccess($provider);
        }

        $this->logUsage($provider, 'translate', $feature, mb_strlen($text), $source, $target, $translated !== null);

        return $translated;
    }

    /**
     * Registra una llamada real confirmada al proveedor — solo se invoca
     * desde callProvider()/detectViaProvider(), nunca en un cache-hit. Un
     * fallo al escribir el ledger no debe romper la traducción en sí.
     */
    private function logUsage(string $provider, string $operation, string $feature, int $characters, ?string $sourceLang, ?string $targetLang, bool $success): void
    {
        try {
            TranslateUsage::query()->create([
                'provider' => $provider,
                'operation' => $operation,
                'feature' => $feature,
                'characters' => $characters,
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang,
                'success' => $success,
            ]);
        } catch (QueryException) {
            // Observabilidad, no debe tumbar la traducción real.
        }
    }

    private function invokeProvider(string $provider, string $text, string $target, ?string $source): ?string
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

    /* ── Circuit breaker (por proveedor) ──────────────────────────────────── */

    private function circuitKey(string $provider): string
    {
        return "helpdesktranslate:circuit:{$provider}";
    }

    private function isCircuitOpen(string $provider): bool
    {
        $threshold = (int) config('helpdesktranslate.circuit_failure_threshold', 3);

        return (int) Cache::get($this->circuitKey($provider), 0) >= $threshold;
    }

    private function recordFailure(string $provider): void
    {
        $openSeconds = (int) config('helpdesktranslate.circuit_open_seconds', 60);
        $key = $this->circuitKey($provider);

        // Cache::add fija el TTL de la ventana solo en el primer fallo; los
        // increments posteriores lo mantienen (Redis conserva el TTL en INCR).
        Cache::add($key, 0, $openSeconds);
        Cache::increment($key);
    }

    private function recordSuccess(string $provider): void
    {
        Cache::forget($this->circuitKey($provider));
    }
}
