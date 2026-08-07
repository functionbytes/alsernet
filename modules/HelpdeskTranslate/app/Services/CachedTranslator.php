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
    /**
     * DeepL's language-detection heuristic (a short-text sample) is unreliable
     * below this many characters — a short message like "Ok" or a code-like
     * snippet can get misdetected as an unrelated language. Below this floor,
     * detectLanguage() returns null instead of guessing. Mirrors the same
     * threshold LocalizesAutoReplyMessage uses for greeting/off-hours replies.
     */
    private const MIN_DETECTABLE_LENGTH = 12;

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

    /**
     * Salud para paneles de administración: true si el proveedor principal
     * está disponible ahora mismo, o si su fallback SÍ lo está (circuito
     * cerrado y realmente configurado). Cuando ambos fallan (o el fallback ni
     * siquiera tiene clave/endpoint configurados), toda traducción automática
     * cae en silencio al texto original (ver LocalizesAutoReplyMessage::localize)
     * sin que quede ningún rastro visible salvo este chequeo — no hay alerta ni
     * notificación cuando eso pasa, solo se refleja aquí en caliente.
     *
     * Antes esto se limitaba a mirar el circuit breaker: con un fallback sin
     * configurar (p.ej. LIBRETRANSLATE_ENDPOINT vacío) el circuito arranca en
     * 0 fallos, así que justo tras un reinicio/flush de caché isHealthy()
     * podía devolver true por un fallback que en realidad nunca traduciría
     * nada — falso positivo hasta que fallara las 3 veces necesarias para
     * abrir su propio circuito. isProviderConfigured() cierra ese hueco.
     */
    public function isHealthy(): bool
    {
        $primary = $this->resolveProvider();

        if (! $this->isCircuitOpen($primary)) {
            return true;
        }

        $fallback = $primary === 'deepl' ? 'libretranslate' : 'deepl';

        return $this->isProviderConfigured($fallback) && ! $this->isCircuitOpen($fallback);
    }

    /**
     * Si el proveedor tiene credenciales/endpoint real, más allá de si su
     * circuito está abierto o cerrado ahora mismo.
     */
    private function isProviderConfigured(string $provider): bool
    {
        if ($provider === 'libretranslate') {
            return filled(config('helpdesktranslate.libretranslate.endpoint'));
        }

        return filled(
            Setting::get('helpdesktranslate.deepl.key')
                ?: config('helpdesktranslate.deepl.key')
        );
    }

    /* ── Cupo diario de caracteres ─────────────────────────────────────────
     *
     * Dos cubos independientes bajo la misma config `daily_char_limit`:
     *  - 'manual' → por usuario autenticado (agente pulsando "Traducir").
     *  - cualquier otro feature → GLOBAL compartido (listeners en cola de
     *    entrada/salida, envío ya-traducido a WhatsApp/redes, detección de
     *    idioma) — no tienen un usuario/request al que atribuir un cupo
     *    individual.
     *
     * El cargo real (chargeQuota) se aplica DENTRO de callProvider()/
     * detectViaProvider(), es decir SOLO cuando ya se sabe que va a haber un
     * cache-miss real contra el proveedor — antes el cupo se comprobaba
     * antes de mirar la caché, así que un acierto de caché (gratis) seguía
     * descontando cupo como si hubiera costado dinero. isQuotaAlreadyExhausted()
     * es un "peek" de solo lectura para que los controllers HTTP puedan
     * fallar rápido con un 429 claro sin tocar caché ni el proveedor.
     */

    /**
     * Peek de solo lectura: true si el cupo del día YA está agotado, sin
     * cargar nada. Pensado para que los endpoints manuales devuelvan un 429
     * inmediato antes de intentar nada (aunque la petición concreta fuera a
     * resolver por caché y no costara nada — no vale la pena la complejidad
     * de comprobar la caché aquí solo para ese caso límite).
     */
    public function isQuotaAlreadyExhausted(string $feature = 'manual'): bool
    {
        $limit = (int) config('helpdesktranslate.daily_char_limit', 0);

        if ($limit <= 0) {
            return false;
        }

        return (int) Cache::get($this->quotaKey($feature), 0) >= $limit;
    }

    /**
     * Cargo atómico: incrementa primero y comprueba después (mismo patrón que
     * el circuit breaker — Cache::add fija el TTL solo la primera vez,
     * Cache::increment es atómico en Redis). Si el incremento se pasa del
     * límite, se revierte (decrement) antes de rechazar, para que el
     * contador refleje el gasto real y no penalice la siguiente petición por
     * una llamada que finalmente no se hizo.
     */
    private function quotaExceededForCall(string $feature, int $chars): bool
    {
        $limit = (int) config('helpdesktranslate.daily_char_limit', 0);

        if ($limit <= 0 || $chars <= 0) {
            return false;
        }

        $key = $this->quotaKey($feature);

        Cache::add($key, 0, now()->endOfDay());
        $used = Cache::increment($key, $chars);

        if ($used > $limit) {
            Cache::decrement($key, $chars);

            return true;
        }

        return false;
    }

    private function quotaKey(string $feature): string
    {
        if ($feature === 'manual' && auth()->check()) {
            return 'helpdesktranslate:chars:'.auth()->id().':'.now()->format('Ymd');
        }

        return 'helpdesktranslate:chars:auto:'.now()->format('Ymd');
    }

    /**
     * Como translate(), pero cuando el idioma de origen es desconocido
     * devuelve también el idioma detectado — en la MISMA llamada al
     * proveedor, no en una segunda petición aparte. Antes
     * TranslateItemController llamaba translate() y luego detectLanguage()
     * por separado para una sola acción de agente (botón "Traducir" sobre un
     * mensaje): dos round-trips HTTP (hasta 2× la latencia y 2× los
     * caracteres facturados/contados contra el cupo) cuando DeepL ya
     * devuelve `detected_source_language` en la respuesta de /v2/translate,
     * y LibreTranslate ya devuelve `detectedLanguage` en la de /translate.
     *
     * @return array{translated: ?string, detected_source_language: ?string}
     */
    public function translateWithDetectedSource(string $text, string $targetLang, string $feature = 'manual'): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['translated' => null, 'detected_source_language' => null];
        }

        $provider = $this->resolveProvider();
        $target = strtolower($targetLang);
        $hash = TranslationCache::makeHash($text, 'auto', $target, $provider);

        $hit = TranslationCache::query()->where('text_hash', $hash)->first();
        if ($hit) {
            TranslationCache::query()->where('id', $hit->id)->update([
                'hits' => DB::raw('hits + 1'),
                'chars_saved' => DB::raw('chars_saved + '.mb_strlen($text)),
                'last_used_at' => now(),
            ]);

            // El hash de esta consulta usa source='auto' (idioma desconocido
            // por el llamador) — si en un cache-miss anterior se guardó el
            // idioma REALMENTE detectado en source_lang (ver abajo), se
            // devuelve gratis aquí también; si esa vez no hubo detección
            // (p.ej. vino de LibreTranslate mock), source_lang sigue 'auto'.
            return [
                'translated' => $hit->text_translated,
                'detected_source_language' => $hit->source_lang !== 'auto' ? $hit->source_lang : null,
            ];
        }

        $result = $this->callProviderWithDetection($provider, $text, $target, $feature);

        if ($result['translated'] === null) {
            $fallbackProvider = $provider === 'deepl' ? 'libretranslate' : 'deepl';
            $result = $this->callProviderWithDetection($fallbackProvider, $text, $target, $feature);
            if ($result['translated'] !== null) {
                $provider = $fallbackProvider;
                $hash = TranslationCache::makeHash($text, 'auto', $target, $provider);
            }
        }

        $translated = $result['translated'];
        $detected = $result['detected_source_language'];

        if ($translated === null || $translated === '' || $translated === $text) {
            return ['translated' => $translated, 'detected_source_language' => $detected];
        }

        try {
            TranslationCache::query()->create([
                'text_hash' => $hash,
                'text_source' => $text,
                'text_translated' => $translated,
                // Guardamos el idioma REAL detectado (no 'auto') cuando se
                // conoce — así un cache-hit futuro también puede devolverlo.
                'source_lang' => $detected ?: 'auto',
                'target_lang' => $target,
                'provider' => $provider,
                'hits' => 1,
                'chars_saved' => 0,
                'last_used_at' => now(),
            ]);
        } catch (QueryException) {
            // Race condition: another process inserted the same hash. Ignore.
        }

        return ['translated' => $translated, 'detected_source_language' => $detected];
    }

    /**
     * @return array{translated: ?string, detected_source_language: ?string}
     */
    private function callProviderWithDetection(string $provider, string $text, string $target, string $feature): array
    {
        if ($this->isCircuitOpen($provider)) {
            return ['translated' => null, 'detected_source_language' => null];
        }

        if ($this->quotaExceededForCall($feature, mb_strlen($text))) {
            return ['translated' => null, 'detected_source_language' => null];
        }

        if ($provider === 'libretranslate') {
            $result = $this->libretranslate->translate($text, 'auto', $target);
            $failed = ! is_array($result) || ($result['mocked'] ?? false) || ($result['failed'] ?? false);
            $translated = $failed ? null : ($result['translated'] ?? null);
            $detected = $failed ? null : ($result['detected'] ?? null);
        } else {
            $out = $this->deepl->translateWithSource($text, strtoupper($target), null);
            $translated = $out['translated'] ?? null;
            $detected = $out['detected_source_language'] ?? null;
        }

        if ($translated === null) {
            $this->recordFailure($provider);
        } else {
            $this->recordSuccess($provider);
        }

        $this->logUsage($provider, 'translate', $feature, mb_strlen($text), 'auto', $target, $translated !== null);

        return [
            'translated' => $translated,
            'detected_source_language' => $detected ? strtolower($detected) : null,
        ];
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
        if (mb_strlen($text) < self::MIN_DETECTABLE_LENGTH) {
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
        [$timeout, $retries] = $this->httpBudgetFor($feature);

        if ($provider === 'libretranslate') {
            if ($this->quotaExceededForCall($feature, mb_strlen($text))) {
                return null;
            }

            $detected = $this->libretranslate->detectLanguage($text, $timeout, $retries);
            $this->logUsage($provider, 'detect', $feature, mb_strlen($text), null, null, $detected !== null);

            return $detected;
        }

        // DeepL doesn't expose a dedicated /detect endpoint — translating a
        // short snippet returns `detected_source_language` in the response.
        // The cache key inside translateWithDetection uses the snippet, so
        // repeats are free. 40 chars is enough for reliable detection and
        // reduces billable characters when DeepL is the primary provider.
        // ESA llamada es real y facturable (pega a /v2/translate), así que
        // pasa por el mismo cupo que una traducción — antes se saltaba por
        // completo, dejando la detección de idioma sin ningún techo de gasto.
        $sample = mb_substr($text, 0, 40);

        if ($this->quotaExceededForCall($feature, mb_strlen($sample))) {
            return null;
        }

        $result = $this->deepl->translateWithDetection($sample, 'ES', $timeout, $retries);
        $detected = $result['detected_source_language'] ?? null;
        $this->logUsage($provider, 'detect', $feature, mb_strlen($sample), null, null, $detected !== null);

        return $detected;
    }

    /**
     * Presupuesto de tiempo HTTP por llamada al proveedor, según quién pide la
     * traducción/detección. Los flujos automáticos (auto_incoming/auto_outgoing
     * — bienvenida, fuera de horario, despedida) corren dentro de un job de
     * cola compartido con otras 25 colas: un timeout largo (10-15s) + 2
     * reintentos ahí puede sumar decenas de segundos y retrasar en fila todo lo
     * demás. Con circuito abierto (ver isCircuitOpen) ya se salta la llamada
     * entera, pero los primeros fallos antes de que abra pagan el timeout
     * completo — por eso también se acorta aquí, no solo se confía en el
     * circuit breaker. La traducción interactiva del agente (feature 'manual')
     * conserva el timeout largo: ahí sí vale la pena esperar por un resultado
     * real en vez de degradar rápido.
     *
     * `null` deja que cada servicio use su propio valor por defecto (15s DeepL,
     * 10s/5s LibreTranslate translate/detect) — no se toca ese comportamiento
     * para el resto de llamadores (traducción interactiva, otros features).
     *
     * @return array{0: ?int, 1: ?int} [timeoutSeconds, retries]
     */
    private function httpBudgetFor(string $feature): array
    {
        return str_starts_with($feature, 'auto_') ? [4, 0] : [null, null];
    }

    private function callProvider(string $provider, string $text, string $target, ?string $source, string $feature = 'other'): ?string
    {
        // Circuit breaker: si el proveedor viene fallando (caído / sin clave),
        // saltarlo al instante en vez de comerse su timeout (10-15s). Con ambos
        // proveedores abiertos, translate() devuelve null sin colgar el request.
        if ($this->isCircuitOpen($provider)) {
            return null;
        }

        // Cupo diario: solo se llega aquí en un cache-miss real (translate()
        // ya comprobó la caché antes de invocar callProvider()), así que un
        // acierto de caché nunca descuenta cupo ni puede bloquearse por él.
        if ($this->quotaExceededForCall($feature, mb_strlen($text))) {
            return null;
        }

        [$timeout, $retries] = $this->httpBudgetFor($feature);
        $translated = $this->invokeProvider($provider, $text, $target, $source, $timeout, $retries);

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

    private function invokeProvider(string $provider, string $text, string $target, ?string $source, ?int $timeoutSeconds = null, ?int $retries = null): ?string
    {
        if ($provider === 'libretranslate') {
            $result = $this->libretranslate->translate($text, $source ?? 'auto', $target, $timeoutSeconds, $retries);

            if (! is_array($result) || ($result['mocked'] ?? false) || ($result['failed'] ?? false)) {
                return null;
            }

            return $result['translated'] ?? null;
        }

        return $this->deepl->translate($text, $target, $source, $timeoutSeconds, $retries);
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
