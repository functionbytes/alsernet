<?php

namespace Modules\HelpdeskEmailActivity\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Support\OutboundUrlGuard;
use Modules\HelpdeskEmailActivity\Support\CanIEmailDataset;
use Modules\HelpdeskEmailActivity\Support\HtmlCheckTests;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\ExceptionInterface as CssSelectorException;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Compatibilidad del HTML de un correo con los clientes de correo reales,
 * evaluada contra el dataset de caniemail.com (ver CanIEmailDataset).
 *
 * Qué hace, en orden: cuenta los nodos del documento, funde el CSS de los
 * bloques <style> dentro del atributo style="" de cada nodo (sin eso, una
 * propiedad declarada en <style> no se vería en ningún nodo), y ejecuta cuatro
 * familias de comprobaciones —elementos HTML, formato de imágenes, propiedades
 * CSS en línea, y at-rules/pseudo-selectores en los bloques <style>—.
 *
 * De cada hallazgo se saca la feature de caniemail y se calcula su porcentaje
 * de clientes que la soportan / la soportan a medias / no la soportan.
 *
 * La puntuación global NO es una media de los avisos: es el PEOR caso ponderado
 * por cuántos nodos afecta, igual que Mailpit — un fallo que toca la mitad del
 * documento pesa la mitad, y dos fallos del mismo tamaño no se suman. Sin eso,
 * un correo con muchos avisos leves puntuaría peor que uno con un fallo grave.
 */
class EmailHtmlCheckService
{
    /**
     * Clave de caché del análisis de un envío.
     *
     * Lleva dentro la versión del dataset de caniemail: el resultado depende
     * tanto del cuerpo guardado como de los datos de compatibilidad, así que al
     * actualizar el dataset todas las claves cambian de golpe y los informes se
     * recalculan solos. La alternativa —recorrer y borrar las claves viejas—
     * exigiría tags de caché, que no todos los stores soportan.
     *
     * El cuerpo, en cambio, sí puede cambiar dentro de la misma versión (se
     * purga), y eso lo cubre EmailLogObserver.
     */
    public static function cacheKey(string $uid): string
    {
        $dataset = substr(md5((string) CanIEmailDataset::lastUpdate()), 0, 8);

        return "helpdeskemailactivity:html-check:{$dataset}:{$uid}";
    }

    /**
     * @param  list<string>  $platforms  Plataformas a considerar (vacío = todas)
     * @return array{Warnings: list<array<string, mixed>>, Platforms: array<string, list<string>>, Total: array{Tests: int, Nodes: int, Supported: float, Partial: float, Unsupported: float}}
     */
    public function run(string $html, array $platforms = []): array
    {
        $warnings = [];
        $totalTests = 0;

        $nodes = $this->countNodes($html);

        $doc = $this->parse($html);

        [$htmlWarnings, $htmlTests] = $this->runHtmlTests($html, $doc, $platforms);
        $warnings = array_merge($warnings, $htmlWarnings);
        $totalTests += $htmlTests;

        [$cssWarnings, $cssTests] = $this->runCssTests($html, $platforms);
        $warnings = array_merge($warnings, $cssWarnings);
        $totalTests += $cssTests;

        $total = $this->scoreTotal($warnings, $nodes);
        $total['Tests'] = $totalTests;
        $total['Nodes'] = $nodes;

        $warnings = $this->sortWarnings($warnings, $nodes);

        $platformList = CanIEmailDataset::platforms();

        return [
            'Warnings' => array_values($warnings),
            'Platforms' => $platformList,
            // Nombre legible de cada plataforma tal como lo publica el propio
            // dataset ("iOS", "macOS", "Outlook.com"): derivarlo del slug en el
            // cliente da capitalizaciones falsas como "Ios" o "Outlook com".
            'PlatformNames' => array_combine(
                array_keys($platformList),
                array_map(CanIEmailDataset::platformName(...), array_keys($platformList)),
            ),
            'Total' => $total,
        ];
    }

    /**
     * Nodos del documento: la unidad con la que se pondera cada aviso.
     *
     * Se excluyen html/head/meta porque no se renderizan — contarlos inflaría
     * el denominador y haría parecer menos grave un fallo que sí afecta al
     * contenido visible.
     */
    private function countNodes(string $html): int
    {
        $doc = $this->parse($html);
        $xpath = new DOMXPath($doc);

        $query = preg_match('~</body>~i', $html) === 1
            ? '//*[not(self::html) and not(self::head) and not(self::meta)] | //script'
            : '//body//* | //script';

        $found = $xpath->query($query);
        $count = $found ? $found->count() : 0;

        // Nunca 0: es el denominador de la ponderación.
        return max(1, $count + $this->implicitTbodyCount($xpath));
    }

    /**
     * Un cliente de correo parsea el HTML con las reglas de HTML5, que insertan
     * un <tbody> en toda <table> que tenga filas sueltas — y las plantillas de
     * correo, hechas a base de tablas anidadas sin <tbody>, acumulan muchos.
     *
     * libxml (DOMDocument) no los inventa, así que sin este ajuste el
     * denominador quedaría corto y todos los avisos saldrían más graves de lo
     * que un cliente real vería.
     */
    private function implicitTbodyCount(DOMXPath $xpath): int
    {
        $tables = $xpath->query('//table[tr]');

        return $tables ? $tables->count() : 0;
    }

    /**
     * @param  list<string>  $platforms
     * @return array{0: list<array<string, mixed>>, 1: int}
     */
    private function runHtmlTests(string $html, DOMDocument $doc, array $platforms): array
    {
        $results = [];
        $totalTests = 0;

        // JavaScript no lo ejecuta ningún cliente de correo: no es una feature
        // de caniemail con matices por cliente, es un 0 % directo. Se excluyen
        // los <script type="application/ld+json"> porque son datos
        // estructurados (Gmail annotations), no código.
        $scripts = $this->countMatches($doc, 'script:not([type="application/ld+json"]):not([type="application/json"])');

        if ($scripts > 0) {
            $results[] = [
                'Slug' => 'html-script',
                'Title' => '<script>',
                'Description' => __('helpdeskemailactivity::emaillog.preview.inspector.html_check.script_warning'),
                'URL' => '',
                'Category' => 'html',
                'Tags' => [],
                'Results' => [],
                'NotesByNumber' => [],
                'Score' => [
                    'Found' => $scripts,
                    'Supported' => 0.0,
                    'Partial' => 0.0,
                    'Unsupported' => 100.0,
                ],
            ];
            $totalTests++;
        }

        foreach (HtmlCheckTests::html() as $slug => $selector) {
            $totalTests++;

            if ($selector === 'body') {
                // <body> siempre existe tras parsear, aunque el correo sea un
                // fragmento: la única señal fiable es la fuente original.
                if (preg_match('~</body>~i', $html) !== 1) {
                    continue;
                }

                $found = 1;
            } else {
                $found = $this->countMatches($doc, $selector);

                if ($found === 0) {
                    continue;
                }

                $totalTests++;
            }

            if ($warning = $this->buildWarning($slug, $found, $platforms)) {
                $results[] = $warning;
            }
        }

        $imageTests = HtmlCheckTests::imageRegex();
        $totalTests += count($imageTests);
        $imageMatches = [];

        foreach ($this->elements($doc, 'img[src]') as $img) {
            $src = $img->getAttribute('src');

            foreach ($imageTests as $slug => $pattern) {
                if (preg_match($pattern, $src) === 1) {
                    $imageMatches[$slug] = ($imageMatches[$slug] ?? 0) + 1;
                }
            }
        }

        foreach ($imageMatches as $slug => $found) {
            if ($warning = $this->buildWarning($slug, $found, $platforms)) {
                $results[] = $warning;
            }
        }

        return [$results, $totalTests];
    }

    /**
     * @param  list<string>  $platforms
     * @return array{0: list<array<string, mixed>>, 1: int}
     */
    private function runCssTests(string $html, array $platforms): array
    {
        $results = [];

        $html = $this->inlineRemoteStylesheets($html);
        $merged = $this->mergeInlineCss($html);
        $doc = $this->parse($merged);

        $inlineTests = HtmlCheckTests::cssInlineRegex();
        $attributeTests = HtmlCheckTests::styleAttributes();
        $totalTests = count($inlineTests) + count($attributeTests);

        $matches = [];
        $allStyles = [];

        foreach ($this->elements($doc, '[style]') as $node) {
            $style = $node->getAttribute('style');
            $allStyles[] = $style;

            foreach ($inlineTests as $slug => $pattern) {
                if (preg_match($pattern, $style) === 1) {
                    $matches[$slug] = ($matches[$slug] ?? 0) + 1;
                }
            }
        }

        foreach ($attributeTests as $slug => $selector) {
            if ($this->countMatches($doc, $selector) > 0) {
                $matches[$slug] = ($matches[$slug] ?? 0) + 1;
            }
        }

        foreach ($matches as $slug => $found) {
            if ($warning = $this->buildWarning($slug, $found, $platforms)) {
                $results[] = $warning;
            }
        }

        // Unidades: se cuentan TODAS las apariciones (no una por nodo), porque
        // un mismo style="" puede traer varias y cada una es un riesgo aparte.
        foreach (HtmlCheckTests::cssUnitRegex() as $slug => $pattern) {
            $totalTests++;
            $found = 0;

            foreach ($allStyles as $style) {
                $found += preg_match_all($pattern, $style);
            }

            if ($found > 0 && $warning = $this->buildWarning($slug, $found, $platforms)) {
                $results[] = $warning;
            }
        }

        // At-rules y pseudo-selectores: solo pueden vivir en un bloque <style>,
        // que aquí se lee del HTML ORIGINAL — fundirlo inline no los conserva.
        $cssCode = '';

        foreach ($this->elements($this->parse($html), 'style') as $styleNode) {
            $cssCode .= $styleNode->textContent;
        }

        foreach (HtmlCheckTests::cssBlockRegex() as $slug => $pattern) {
            $totalTests++;
            $found = preg_match_all($pattern, $cssCode);

            if ($found > 0 && $warning = $this->buildWarning($slug, $found, $platforms)) {
                $results[] = $warning;
            }
        }

        return [$results, $totalTests];
    }

    /**
     * Ficha de una feature de caniemail con su puntuación por cliente.
     *
     * @param  list<string>  $platforms
     * @return array<string, mixed>|null
     */
    private function buildWarning(string $slug, int $found, array $platforms): ?array
    {
        $feature = CanIEmailDataset::feature($slug);

        if ($feature === null) {
            return null;
        }

        $results = [];
        $yes = 0;
        $no = 0;
        $partial = 0;

        foreach ($feature['stats'] ?? [] as $family => $byPlatform) {
            foreach ($byPlatform as $platform => $versions) {
                if ($platforms !== [] && ! in_array($platform, $platforms, true)) {
                    continue;
                }

                foreach ($versions as $version => $support) {
                    $support = (string) $support;
                    $noteNumber = '';

                    // Solo un "y" o un "n" pelados son un sí o un no rotundos.
                    // Cualquier otra cosa —"a" (parcial), y también "y #1" o
                    // "n #2"— lleva matiz y cuenta como soporte parcial: el
                    // número remite a la nota al pie que lo explica (ver
                    // notes_by_num del dataset).
                    if ($support === 'y') {
                        $yes++;
                        $level = 'yes';
                    } elseif ($support === 'n') {
                        $no++;
                        $level = 'no';
                    } else {
                        $partial++;
                        $level = 'partial';

                        if (preg_match('~#(\d+)~', $support, $note) === 1) {
                            $noteNumber = $note[1];
                        }
                    }

                    $results[] = [
                        'Name' => sprintf(
                            '%s %s (%s)',
                            CanIEmailDataset::familyName($family),
                            CanIEmailDataset::platformName($platform),
                            $version,
                        ),
                        'Platform' => $platform,
                        'Family' => $family,
                        'Version' => (string) $version,
                        'Support' => $level,
                        'NoteNumber' => $noteNumber,
                    ];
                }
            }
        }

        $total = $yes + $no + $partial;

        if ($total === 0) {
            return null;
        }

        return [
            'Slug' => $feature['slug'],
            'Title' => $feature['title'] ?? $slug,
            'Description' => $this->markdownish($feature['description'] ?? ''),
            'URL' => $feature['url'] ?? '',
            'Category' => $feature['category'] ?? 'css',
            'Tags' => $feature['tags'] ?? [],
            'Results' => $results,
            'NotesByNumber' => array_map(
                fn ($note) => $this->markdownish((string) $note),
                $feature['notes_by_num'] ?? [],
            ),
            'Score' => [
                'Found' => $found,
                'Supported' => round($yes / $total * 100, 5),
                'Partial' => round($partial / $total * 100, 5),
                'Unsupported' => round($no / $total * 100, 5),
            ],
        ];
    }

    /**
     * Puntuación global: el peor aviso parcial y el peor no soportado, cada uno
     * ponderado por la fracción de nodos que afecta. Ver la nota de clase.
     *
     * @param  list<array<string, mixed>>  $warnings
     * @return array{Supported: float, Partial: float, Unsupported: float}
     */
    private function scoreTotal(array $warnings, int $nodes): array
    {
        $partial = 0.0;
        $unsupported = 0.0;

        foreach ($warnings as $warning) {
            $found = (int) $warning['Score']['Found'];

            if ($found === 0) {
                continue;
            }

            $weight = $found / $nodes;

            $partial = max($partial, (float) $warning['Score']['Partial'] * $weight);
            $unsupported = max($unsupported, (float) $warning['Score']['Unsupported'] * $weight);
        }

        return [
            'Supported' => round(100 - $partial - $unsupported, 5),
            'Partial' => round($partial, 5),
            'Unsupported' => round($unsupported, 5),
        ];
    }

    /**
     * Peores primero: lo que más superficie del correo rompe, arriba.
     *
     * @param  list<array<string, mixed>>  $warnings
     * @return list<array<string, mixed>>
     */
    private function sortWarnings(array $warnings, int $nodes): array
    {
        usort($warnings, function (array $a, array $b) use ($nodes) {
            $weight = fn (array $w) => ($w['Score']['Unsupported'] + $w['Score']['Partial']) * $w['Score']['Found'] / $nodes;

            return $weight($b) <=> $weight($a);
        });

        return $warnings;
    }

    /**
     * Funde el CSS de los bloques <style> en el atributo style="" de cada nodo
     * (lo que premailer hace en Mailpit). Sin esto, un correo que declare todo
     * su estilo en un <style> daría cero avisos de CSS.
     *
     * Nunca lanza: si el HTML es demasiado roto para CssToInlineStyles, se
     * evalúa el original — peor cobertura, pero cobertura.
     */
    /**
     * Trae el contenido de las hojas de estilo enlazadas y lo inyecta como un
     * bloque <style> más, para que sus propiedades entren en el análisis.
     *
     * Apagado por defecto (helpdeskemailactivity.html_check_remote_css): sin
     * esto, analizar un correo no genera ni una sola petición saliente. Ver la
     * nota del config para por qué el valor por defecto es ese.
     */
    private function inlineRemoteStylesheets(string $html): string
    {
        if (! config('helpdeskemailactivity.html_check_remote_css', false)) {
            return $html;
        }

        $doc = $this->parse($html);
        $extra = '';

        foreach ($this->elements($doc, 'link[rel="stylesheet"]') as $link) {
            $href = trim($link->getAttribute('href'));

            // Mismo criterio SSRF que el resto del módulo: nada de direcciones
            // internas ni de esquemas que no sean http(s).
            if ($href === '' || ! OutboundUrlGuard::isSafe($href)) {
                continue;
            }

            try {
                $response = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'Webadmin Email HTML Checker'])
                    ->get($href);
            } catch (\Throwable) {
                continue;
            }

            // Un 404 que devuelve una página de error en HTML no es CSS: sin
            // esta comprobación, su marcado entraría en el análisis como si
            // fueran estilos del correo.
            if (! $response->successful() || ! str_contains(strtolower($response->header('content-type') ?? ''), 'text/css')) {
                continue;
            }

            $max = (int) config('helpdeskemailactivity.html_check_remote_css_max_bytes', 1048576);
            $extra .= '<style>'.substr($response->body(), 0, $max).'</style>';
        }

        return $extra === '' ? $html : $html.$extra;
    }

    private function mergeInlineCss(string $html): string
    {
        try {
            return (new CssToInlineStyles)->convert($html);
        } catch (\Throwable) {
            return $html;
        }
    }

    private function parse(string $html): DOMDocument
    {
        $doc = new DOMDocument;

        $previous = libxml_use_internal_errors(true);

        // El HTML de un correo real trae de todo (etiquetas sin cerrar,
        // atributos duplicados, HTML condicional de Outlook): se parsea en modo
        // tolerante y se descartan los avisos de libxml.
        $doc->loadHTML(
            '<?xml encoding="utf-8" ?>'.$html,
            LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $doc;
    }

    /**
     * @return list<DOMElement>
     */
    private function elements(DOMDocument $doc, string $selector): array
    {
        $xpath = $this->selectorToXPath($selector);

        if ($xpath === null) {
            return [];
        }

        $found = (new DOMXPath($doc))->query($xpath);

        if ($found === false) {
            return [];
        }

        $elements = [];

        foreach ($found as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = $node;
            }
        }

        return $elements;
    }

    private function countMatches(DOMDocument $doc, string $selector): int
    {
        return count($this->elements($doc, $selector));
    }

    /**
     * Los selectores del catálogo son CSS; DOMXPath solo entiende XPath.
     * Un selector no traducible (los hay: ":not()" encadenado) devuelve null y
     * su test se salta en vez de romper la comprobación entera.
     */
    private function selectorToXPath(string $selector): ?string
    {
        static $cache = [];

        if (array_key_exists($selector, $cache)) {
            return $cache[$selector];
        }

        try {
            return $cache[$selector] = (new CssSelectorConverter)->toXPath($selector, '//');
        } catch (CssSelectorException) {
            return $cache[$selector] = null;
        }
    }

    /**
     * Las descripciones y notas del dataset vienen en Markdown ligero: solo
     * usan `code` y algún enlace. Se convierte a HTML lo justo y se escapa
     * TODO lo demás — este texto acaba pintándose sin escapar en la vista.
     */
    private function markdownish(string $text): string
    {
        $escaped = e($text);

        $escaped = preg_replace('~`([^`]+)`~', '<code>$1</code>', $escaped) ?? $escaped;

        return preg_replace(
            '~\[([^\]]+)\]\((https?://[^\s)]+)\)~',
            '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>',
            $escaped,
        ) ?? $escaped;
    }
}
