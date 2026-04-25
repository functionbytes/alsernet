<?php

namespace Modules\Shortcode\Compiler;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\Shortcode\Events\ShortcodeCompiled;
use Modules\Shortcode\Events\ShortcodeCompiling;
use Modules\Shortcode\Events\ShortcodeRegistered;
use Modules\Shortcode\Exceptions\ShortcodeCompilationException;

class ShortcodeCompiler
{
    public const MAX_CONTENT_SIZE = 1048576; // 1 MB

    /** Placeholder interno para `[[...]]` (escape literal). */
    protected const PH_ESCAPE_OPEN = "\x01SCOPEN\x01";

    protected const PH_ESCAPE_CLOSE = "\x01SCCLOSE\x01";

    /** Placeholder interno para comentarios HTML preservados. */
    protected const PH_COMMENT_PREFIX = "\x01SCCOMMENT";

    protected const PH_COMMENT_SUFFIX = "\x01";

    /** Placeholder interno para bloques raw no re-procesables. */
    protected const PH_RAW_PREFIX = "\x01SCRAW";

    protected const PH_RAW_SUFFIX = "\x01";

    /** @var array<string, callable> */
    protected array $shortcodes = [];

    /** @var array<string, array<string, mixed>> */
    protected array $metadata = [];

    /** @var array<string, string>  alias => target */
    protected array $aliases = [];

    protected ?string $cspNonce = null;

    /**
     * Fija un nonce CSP para que los shortcodes puedan usarlo en scripts inline.
     * Accesible desde el callback mediante Shortcode::getCspNonce() si se necesita.
     */
    public function withCspNonce(?string $nonce): self
    {
        $this->cspNonce = $nonce;

        return $this;
    }

    public function getCspNonce(): ?string
    {
        return $this->cspNonce;
    }

    /**
     * Register a new shortcode with optional metadata.
     *
     * Meta keys reconocidos:
     *  - description, example, attributes   (documentación/picker)
     *  - raw         (bool)   content no se re-procesa como shortcode
     *  - cacheable   (bool)   default true; si false, bypass de cache
     *  - deprecated  (string) versión; emite E_USER_DEPRECATED al usarse
     *  - alias_of    (string) redirige al handler del shortcode objetivo
     *  - since       (string) metadata informativa
     *
     * @param  array<string, mixed>  $meta
     */
    public function register(string $name, callable $callback, array $meta = []): void
    {
        if (isset($meta['alias_of']) && is_string($meta['alias_of'])) {
            $this->aliases[$name] = $meta['alias_of'];
        }

        $this->shortcodes[$name] = $callback;
        $this->metadata[$name] = $meta;

        Event::dispatch(new ShortcodeRegistered($name, $meta));
    }

    public function compile(string $content): string
    {
        return $this->compileWithContext($content);
    }

    /**
     * @param  array<int, string>|null  $allowed  Whitelist; null = todos.
     */
    public function compileWithContext(string $content, ?array $allowed = null): string
    {
        if ($content === '' || ! str_contains($content, '[')) {
            return $content;
        }

        if (strlen($content) > self::MAX_CONTENT_SIZE) {
            Log::warning('Shortcode compile: contenido excede MAX_CONTENT_SIZE', [
                'size' => strlen($content),
                'limit' => self::MAX_CONTENT_SIZE,
            ]);

            return $content;
        }

        $original = $content;
        Event::dispatch(new ShortcodeCompiling($original));

        // Cache: sólo sin whitelist y si TODOS los shortcodes usados son cacheables.
        $cacheEnabled = (bool) config('shortcode.cache', true)
            && $allowed === null
            && $this->contentIsCacheable($original);

        $cacheKey = $cacheEnabled ? $this->cacheKey($original) : null;

        if ($cacheKey !== null) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                Event::dispatch(new ShortcodeCompiled($original, $cached, 0, true));

                return $cached;
            }
        }

        // Protección ReDoS: backtrack_limit temporal.
        $prevBacktrack = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '100000');

        try {
            // 1. Preservar comentarios HTML (se restauran al final).
            $comments = [];
            $content = $this->maskComments($content, $comments);

            // 2. Escape `[[...]]` → placeholder; se restaura como `[...]` al final.
            $content = $this->maskEscapes($content);

            // 3. Pases iterativos.
            $rawBlocks = [];
            $compiled = $this->runCompilationPasses($content, $allowed, $rawBlocks, $passes);

            // 4. Restaurar raws, escapes, comentarios.
            $compiled = $this->unmaskRaws($compiled, $rawBlocks);
            $compiled = $this->unmaskEscapes($compiled);
            $compiled = $this->unmaskComments($compiled, $comments);
        } finally {
            ini_set('pcre.backtrack_limit', (string) $prevBacktrack);
        }

        if ($cacheKey !== null) {
            $ttl = (int) config('shortcode.cache_duration', 3600);
            Cache::put($cacheKey, $compiled, $ttl > 0 ? $ttl : null);
        }

        Event::dispatch(new ShortcodeCompiled($original, $compiled, $passes, false));

        return $compiled;
    }

    // -----------------------------------------------------------------------
    // Masking helpers
    // -----------------------------------------------------------------------

    /**
     * Sustituye `[[...]]` por placeholders opacos para que el parser no los vea.
     */
    protected function maskEscapes(string $content): string
    {
        $content = str_replace('[[', self::PH_ESCAPE_OPEN, $content);
        $content = str_replace(']]', self::PH_ESCAPE_CLOSE, $content);

        return $content;
    }

    protected function unmaskEscapes(string $content): string
    {
        $content = str_replace(self::PH_ESCAPE_OPEN, '[', $content);
        $content = str_replace(self::PH_ESCAPE_CLOSE, ']', $content);

        return $content;
    }

    /**
     * Sustituye comentarios HTML por placeholders antes del parse.
     *
     * @param  array<string, string>  $store  (out) placeholder => original
     */
    protected function maskComments(string $content, array &$store): string
    {
        return preg_replace_callback(
            '/<!--.*?-->/s',
            function ($m) use (&$store) {
                $key = self::PH_COMMENT_PREFIX.count($store).self::PH_COMMENT_SUFFIX;
                $store[$key] = $m[0];

                return $key;
            },
            $content
        ) ?? $content;
    }

    /**
     * @param  array<string, string>  $store
     */
    protected function unmaskComments(string $content, array $store): string
    {
        if (empty($store)) {
            return $content;
        }

        return strtr($content, $store);
    }

    /**
     * @param  array<string, string>  $store
     */
    protected function unmaskRaws(string $content, array $store): string
    {
        if (empty($store)) {
            return $content;
        }

        return strtr($content, $store);
    }

    // -----------------------------------------------------------------------
    // Pipeline
    // -----------------------------------------------------------------------

    /**
     * @param  array<int, string>|null  $allowed
     * @param  array<string, string>  $rawBlocks  (out)
     */
    protected function runCompilationPasses(string $content, ?array $allowed, array &$rawBlocks, ?int &$passes = null): string
    {
        $max = max(1, (int) config('shortcode.max_nesting_level', 10));

        $passes = 0;
        $previous = null;

        while ($passes < $max && $previous !== $content) {
            $previous = $content;
            $content = $this->pairedPass($content, $allowed, $rawBlocks);
            $content = $this->selfClosingPass($content, $allowed);
            $passes++;
        }

        $content = $this->barePass($content, $allowed);

        return $content;
    }

    /**
     * Parser stack-based para pares balanceados, compatible con nested same-tag.
     *
     * @param  array<int, string>|null  $allowed
     * @param  array<string, string>  $rawBlocks  (out)
     */
    protected function pairedPass(string $content, ?array $allowed, array &$rawBlocks): string
    {
        $result = '';
        $i = 0;
        $len = strlen($content);

        while ($i < $len) {
            $bracket = strpos($content, '[', $i);
            if ($bracket === false) {
                $result .= substr($content, $i);
                break;
            }

            $result .= substr($content, $i, $bracket - $i);

            // Intenta matchear apertura [name attrs] (ni cierre ni self-closing).
            if (! preg_match('/\G\[([\w-]+)([^\]]*)\]/A', $content, $m, 0, $bracket)) {
                $result .= '[';
                $i = $bracket + 1;

                continue;
            }

            $name = $m[1];
            $attrsText = $m[2];
            $openLen = strlen($m[0]);

            // Si termina en ` /` es self-closing → no es nuestro.
            if (preg_match('/\/\s*$/', $attrsText)) {
                $result .= $m[0];
                $i = $bracket + $openLen;

                continue;
            }

            // No registrado → dejar tal cual.
            if (! isset($this->shortcodes[$name]) && ! isset($this->aliases[$name])) {
                $result .= $m[0];
                $i = $bracket + $openLen;

                continue;
            }

            // Busca cierre balanceado (mismo $name) respetando anidamiento.
            $close = $this->findBalancedClose($content, $name, $bracket + $openLen);

            if ($close === null) {
                $result .= $m[0];
                $i = $bracket + $openLen;

                continue;
            }

            $innerContent = substr($content, $bracket + $openLen, $close['start'] - ($bracket + $openLen));
            $raw = substr($content, $bracket, ($close['end'] - $bracket));

            $output = $this->invoke($name, $this->parseAttributes($attrsText), $innerContent, $raw, $allowed);

            // Si es raw, encapsular output en placeholder para que siguientes pases no lo re-toquen.
            if ($this->isRaw($name) && $output !== $raw) {
                $key = self::PH_RAW_PREFIX.count($rawBlocks).self::PH_RAW_SUFFIX;
                $rawBlocks[$key] = $output;
                $output = $key;
            }

            $result .= $output;
            $i = $close['end'];
        }

        return $result;
    }

    /**
     * Busca el cierre balanceado `[/name]` a partir de $from, contando anidamiento
     * del mismo nombre (ignora self-closing del mismo nombre).
     *
     * @return array{start: int, end: int}|null start = posición del `[`, end = posición tras `]`
     */
    protected function findBalancedClose(string $content, string $name, int $from): ?array
    {
        $depth = 1;
        $pattern = '/\[(\/?)'.preg_quote($name, '/').'\b([^\]]*)\]/';
        $offset = $from;

        while ($depth > 0) {
            if (! preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE, $offset)) {
                return null;
            }

            $matchStart = $m[0][1];
            $matchEnd = $matchStart + strlen($m[0][0]);
            $isClose = $m[1][0] === '/';
            $attrs = $m[2][0];
            $isSelfClosing = ! $isClose && preg_match('/\/\s*$/', $attrs);

            if ($isClose) {
                $depth--;
                if ($depth === 0) {
                    return ['start' => $matchStart, 'end' => $matchEnd];
                }
            } elseif (! $isSelfClosing) {
                $depth++;
            }

            $offset = $matchEnd;
        }

        return null;
    }

    /**
     * @param  array<int, string>|null  $allowed
     */
    protected function selfClosingPass(string $content, ?array $allowed): string
    {
        return preg_replace_callback(
            '/\[([\w-]+)([^\]]*?)\/\]/',
            fn ($m) => $this->invoke($m[1], $this->parseAttributes($m[2]), '', $m[0], $allowed),
            $content
        ) ?? $content;
    }

    /**
     * @param  array<int, string>|null  $allowed
     */
    protected function barePass(string $content, ?array $allowed): string
    {
        return preg_replace_callback(
            '/\[([\w-]+)([^\]]*)\]/',
            function ($m) use ($allowed) {
                $name = $m[1];
                if (! isset($this->shortcodes[$name]) && ! isset($this->aliases[$name])) {
                    return $m[0];
                }

                return $this->invoke($name, $this->parseAttributes($m[2]), '', $m[0], $allowed);
            },
            $content
        ) ?? $content;
    }

    /**
     * @param  array<string, string>  $attributes
     * @param  array<int, string>|null  $allowed
     */
    protected function invoke(string $name, array $attributes, string $content, string $raw, ?array $allowed): string
    {
        // Resolver aliases.
        $targetName = $this->aliases[$name] ?? $name;

        if (! isset($this->shortcodes[$targetName])) {
            return $raw;
        }

        if ($allowed !== null && ! in_array($name, $allowed, true) && ! in_array($targetName, $allowed, true)) {
            return $raw;
        }

        $this->warnIfDeprecated($name);

        try {
            $output = (string) call_user_func($this->shortcodes[$targetName], $attributes, $content);

            return $this->maybePurify($targetName, $output);
        } catch (\Throwable $e) {
            return $this->handleError($name, $e, $raw);
        }
    }

    /**
     * Si el shortcode declara meta['purify'] = true y HTMLPurifier está instalado,
     * sanitiza el output antes de retornarlo. Defensa-en-profundidad opcional.
     */
    protected function maybePurify(string $name, string $output): string
    {
        if (! (bool) config('shortcode.purify_enabled', true)) {
            return $output;
        }

        $shouldPurify = (bool) ($this->metadata[$name]['purify'] ?? false);
        if (! $shouldPurify) {
            return $output;
        }

        if (! class_exists(\HTMLPurifier::class)) {
            return $output;
        }

        try {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Cache.DefinitionImpl', null);
            $purifier = new \HTMLPurifier($config);

            return (string) $purifier->purify($output);
        } catch (\Throwable $e) {
            Log::warning('HTMLPurifier falló en shortcode ['.$name.']: '.$e->getMessage());

            return $output;
        }
    }

    protected function warnIfDeprecated(string $name): void
    {
        $deprecated = $this->metadata[$name]['deprecated'] ?? null;
        if (! $deprecated) {
            return;
        }

        $message = "Shortcode [{$name}] está deprecado desde {$deprecated}.";
        Log::notice($message);
        if (function_exists('trigger_error')) {
            @trigger_error($message, E_USER_DEPRECATED);
        }
    }

    protected function isRaw(string $name): bool
    {
        $target = $this->aliases[$name] ?? $name;

        return (bool) ($this->metadata[$target]['raw'] ?? false);
    }

    protected function handleError(string $name, \Throwable $e, string $raw): string
    {
        $mode = (string) config('shortcode.error_handling', 'log');

        if ($mode === 'display') {
            Log::error("Shortcode [{$name}]: ".$e->getMessage(), ['exception' => $e]);

            return sprintf(
                '<span class="shortcode-error text-danger" data-shortcode="%s">[Error en %s: %s]</span>',
                htmlspecialchars($name),
                htmlspecialchars($name),
                htmlspecialchars($e->getMessage())
            );
        }

        if ($mode === 'silent') {
            return $raw;
        }

        if ($mode === 'throw') {
            throw new ShortcodeCompilationException(
                "Error compilando shortcode [{$name}]: ".$e->getMessage(),
                $name,
                $e
            );
        }

        Log::error("Shortcode [{$name}]: ".$e->getMessage(), ['exception' => $e]);

        return $raw;
    }

    /**
     * Escanea el contenido y determina si TODOS los shortcodes presentes son cacheables.
     */
    protected function contentIsCacheable(string $content): bool
    {
        if (! preg_match_all('/\[([\w-]+)/', $content, $matches)) {
            return true;
        }

        foreach ($matches[1] as $name) {
            $target = $this->aliases[$name] ?? $name;
            $meta = $this->metadata[$target] ?? [];
            if (($meta['cacheable'] ?? true) === false) {
                return false;
            }
        }

        return true;
    }

    protected function cacheKey(string $content): string
    {
        $version = Cache::get('shortcode:cache-version', 1);

        return "shortcode:compiled:v{$version}:".md5($content);
    }

    /**
     * Parse shortcode attributes. Soporta:
     *  - Atributos con comillas dobles:  key="value"
     *  - Atributos con comillas simples: key='value'
     *  - Atributos sin comillas:         key=value  (sin espacios)
     *  - Atributos booleanos:            disabled     (sin `=`, valor = "true")
     *  - Atributos posicionales:         "valor"  o  'valor'  → keys 0, 1, 2...
     *  - Nombres con guiones/dos-puntos: data-id, aria-label, xmlns:og
     *
     * @return array<string|int, string>
     */
    protected function parseAttributes(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $attributes = [];
        $positional = 0;

        // Token groups:
        //   $1 name (solo)                         → booleano
        //   $2 name + $3 valor-dobles             → key="value"
        //   $4 name + $5 valor-simples            → key='value'
        //   $6 name + $7 valor-sin-comillas       → key=value
        //   $8 posicional-dobles                  → "valor"
        //   $9 posicional-simples                 → 'valor'
        $pattern = '/
            ([a-zA-Z_][\w:-]*)="([^"]*)"           # $1=$2
            |
            ([a-zA-Z_][\w:-]*)=\'([^\']*)\'        # $3=$4
            |
            ([a-zA-Z_][\w:-]*)=([^\s"\'=<>`]+)     # $5=$6
            |
            ([a-zA-Z_][\w:-]*)(?=\s|$|\/)          # $7 booleano
            |
            "([^"]*)"                              # $8 posicional dobles
            |
            \'([^\']*)\'                           # $9 posicional simples
        /x';

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            if (! empty($m[1])) {
                $attributes[$m[1]] = $m[2];
            } elseif (! empty($m[3])) {
                $attributes[$m[3]] = $m[4];
            } elseif (! empty($m[5])) {
                $attributes[$m[5]] = $m[6];
            } elseif (! empty($m[7])) {
                $attributes[$m[7]] = 'true';
            } elseif (! empty($m[8])) {
                $attributes[$positional++] = $m[8];
            } elseif (! empty($m[9])) {
                $attributes[$positional++] = $m[9];
            }
        }

        return $attributes;
    }

    public function strip(string $content): string
    {
        // Self-closing primero para que `[x /]` no sea confundido con apertura.
        $content = preg_replace('/\[[\w-]+[^\]]*\/\]/', '', $content);
        $content = preg_replace('/\[[\w-]+[^\]]*\].*?\[\/[\w-]+\]/s', '', $content);

        return $content ?? '';
    }

    public function has(string $name): bool
    {
        return isset($this->shortcodes[$name]) || isset($this->aliases[$name]);
    }

    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        return array_keys($this->shortcodes);
    }

    /**
     * @return array<int, array{name: string, description: string, example: string, attributes: array<string, string>, deprecated?: string, alias_of?: string}>
     */
    public function getRegistered(): array
    {
        $result = [];

        foreach ($this->shortcodes as $name => $_callback) {
            $meta = $this->metadata[$name] ?? [];
            $entry = [
                'name' => $name,
                'description' => $meta['description'] ?? '',
                'example' => $meta['example'] ?? "[{$name}][/{$name}]",
                'attributes' => $meta['attributes'] ?? [],
            ];
            if (isset($meta['deprecated'])) {
                $entry['deprecated'] = (string) $meta['deprecated'];
            }
            if (isset($meta['alias_of'])) {
                $entry['alias_of'] = (string) $meta['alias_of'];
            }
            $result[] = $entry;
        }

        return $result;
    }

    public function clearCache(): void
    {
        $current = (int) Cache::get('shortcode:cache-version', 1);
        Cache::forever('shortcode:cache-version', $current + 1);
    }

    public function forgetCachedContent(string $content): bool
    {
        return Cache::forget($this->cacheKey($content));
    }

    public function unregister(string $name): void
    {
        unset($this->shortcodes[$name], $this->metadata[$name], $this->aliases[$name]);
    }

    /**
     * Elimina todos los shortcodes registrados (útil en tests).
     */
    public function removeAll(): void
    {
        $this->shortcodes = [];
        $this->metadata = [];
        $this->aliases = [];
    }

    /**
     * Devuelve la regex para detectar shortcodes registrados en un contenido
     * SIN ejecutarlos. Útil para scanners/auditors.
     */
    public function getRegex(): string
    {
        $names = array_map(
            fn ($n) => preg_quote($n, '/'),
            array_unique(array_merge(array_keys($this->shortcodes), array_keys($this->aliases)))
        );

        if (empty($names)) {
            return '/(?!)/'; // nunca matchea
        }

        $alternation = implode('|', $names);

        return '/\[('.$alternation.')\b([^\]]*?)(?:\/\]|\](?:(.*?)\[\/\1\])?)/s';
    }

    /**
     * Lista shortcodes presentes en un contenido (sin compilarlos).
     *
     * @return array<int, array{name: string, attributes: array<string, string>, content: string|null, offset: int}>
     */
    public function find(string $content): array
    {
        $regex = $this->getRegex();
        if (! preg_match_all($regex, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $result = [];
        foreach ($matches as $m) {
            $result[] = [
                'name' => $m[1][0],
                'attributes' => $this->parseAttributes($m[2][0] ?? ''),
                'content' => isset($m[3]) ? $m[3][0] : null,
                'offset' => $m[0][1],
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $atts
     * @return array<string, mixed>
     */
    public function atts(array $defaults, array $atts): array
    {
        return array_merge($defaults, array_intersect_key($atts, $defaults));
    }

    /**
     * Stub de compatibilidad con temas Botble.
     * No-op en el frontend; el admin usa su propio picker.
     */
    public function setAdminConfig(string $name, callable $callback): void
    {
        // No-op
    }
}
