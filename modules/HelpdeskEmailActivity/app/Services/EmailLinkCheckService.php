<?php

namespace Modules\HelpdeskEmailActivity\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Modules\Helpdesk\Support\OutboundUrlGuard;
use Modules\HelpdeskEmailActivity\Exceptions\BlockedRedirectException;
use Psr\Http\Message\ResponseInterface;

/**
 * Comprueba a dónde llevan de verdad los enlaces de un correo ya enviado:
 * saca las URLs únicas del HTML y del texto —incluidas las de imágenes y hojas
 * de estilo remotas— y pide cada una para ver con qué responde.
 *
 * Nunca se lanza solo. Lo dispara el operador desde la pestaña "Enlaces", igual
 * que en Mailpit: son peticiones salientes reales a terceros y algunas URLs de
 * un correo transaccional no son idempotentes (bajas, confirmaciones de un solo
 * uso). Los enlaces de seguimiento propios se detectan y NO se piden — abrirlos
 * falsearía las aperturas y clics del propio registro que se está inspeccionando.
 *
 * Direcciones internas: se bloquean con OutboundUrlGuard (mismo criterio que el
 * resto del módulo, ver SesSnsWebhookAdapter) y se reportan con estado 451, la
 * misma convención que usa Mailpit para "private/reserved address".
 */
class EmailLinkCheckService
{
    private const BLOCKED_STATUS = 451;

    private const CONCURRENCY = 5;

    private const TIMEOUT_SECONDS = 10;

    /**
     * @return array{Links: list<array{URL: string, StatusCode: int, Status: string}>, Errors: int, Skipped: int}
     */
    public function run(?string $html, ?string $text, bool $followRedirects = false): array
    {
        $urls = $this->extractUrls($html, $text);

        if ($urls === []) {
            return ['Links' => [], 'Errors' => 0, 'Skipped' => 0];
        }

        $checkable = [];
        $links = [];
        $skipped = 0;

        foreach ($urls as $url) {
            if ($this->isOwnTrackingUrl($url)) {
                $skipped++;

                $links[] = [
                    'URL' => $url,
                    'StatusCode' => 0,
                    'Status' => __('helpdeskemailactivity::emaillog.preview.inspector.link_check.status.tracking'),
                ];

                continue;
            }

            if (! OutboundUrlGuard::isSafe($url)) {
                $links[] = [
                    'URL' => $url,
                    'StatusCode' => self::BLOCKED_STATUS,
                    'Status' => __('helpdeskemailactivity::emaillog.preview.inspector.link_check.status.blocked'),
                ];

                continue;
            }

            $checkable[] = $url;
        }

        $links = array_merge($links, $this->request($checkable, $followRedirects));

        usort($links, fn (array $a, array $b) => [$a['StatusCode'], $a['URL']] <=> [$b['StatusCode'], $b['URL']]);

        $errors = count(array_filter(
            $links,
            fn (array $link) => $link['StatusCode'] >= 400 || $link['StatusCode'] === -1,
        ));

        return ['Links' => $links, 'Errors' => $errors, 'Skipped' => $skipped];
    }

    /**
     * @param  list<string>  $urls
     * @return list<array{URL: string, StatusCode: int, Status: string}>
     */
    private function request(array $urls, bool $followRedirects): array
    {
        if ($urls === []) {
            return [];
        }

        $client = new Client([
            'timeout' => self::TIMEOUT_SECONDS,
            'connect_timeout' => 5,
            // Cada SALTO se vuelve a comprobar, no solo la URL de partida.
            //
            // OutboundUrlGuard::isSafe() se evalúa arriba sobre la dirección
            // que venía en el correo, pero seguir redirecciones sin más
            // convertía esa comprobación en un trámite: basta un dominio
            // público que responda «302 Location: http://169.254.169.254/…»
            // —o cualquier IP de la red interna— para que el servidor haga esa
            // petición por su cuenta. El guard exige IP pública; el segundo
            // salto se lo saltaba entero.
            //
            // on_redirect se ejecuta ANTES de seguir cada salto, y lanzar desde
            // ahí lo aborta: el enlace acaba en el 'rejected' del pool y se
            // pinta como bloqueado, igual que si no hubiera pasado el guard.
            'allow_redirects' => $followRedirects
                ? [
                    'max' => 3,
                    'strict' => true,
                    'referer' => false,
                    'protocols' => ['http', 'https'],
                    'on_redirect' => static function ($request, $response, $uri): void {
                        if (! OutboundUrlGuard::isSafe((string) $uri)) {
                            throw new BlockedRedirectException((string) $uri);
                        }
                    },
                ]
                : false,
            'http_errors' => false,
            'verify' => true,
            'headers' => [
                'User-Agent' => 'Webadmin Email Link Checker',
                'Accept' => '*/*',
            ],
        ]);

        $results = [];

        // GET y no HEAD: hay CDNs y acortadores que responden 405 a HEAD y
        // darían un falso error. Se corta la descarga en cuanto llegan las
        // cabeceras (stream) para no traerse el cuerpo entero.
        $requests = function () use ($urls) {
            foreach ($urls as $url) {
                yield new Request('GET', $url);
            }
        };

        $pool = new Pool($client, $requests(), [
            'concurrency' => self::CONCURRENCY,
            'options' => ['stream' => true],
            'fulfilled' => function (ResponseInterface $response, int $index) use (&$results, $urls) {
                $results[$index] = [
                    'URL' => $urls[$index],
                    'StatusCode' => $response->getStatusCode(),
                    'Status' => $response->getReasonPhrase() ?: (string) $response->getStatusCode(),
                ];
            },
            'rejected' => function (mixed $reason, int $index) use (&$results, $urls) {
                $results[$index] = [
                    'URL' => $urls[$index],
                    // -1 y no 0: 0 lo usan los enlaces omitidos (seguimiento
                    // propio), y en la vista se agrupa por código.
                    'StatusCode' => match (true) {
                        $this->wasBlockedRedirect($reason) => self::BLOCKED_STATUS,
                        $reason instanceof RequestException && $reason->hasResponse() => $reason->getResponse()->getStatusCode(),
                        default => -1,
                    },
                    'Status' => $this->failureReason($reason),
                ];
            },
        ]);

        $pool->promise()->wait();

        ksort($results);

        return array_values($results);
    }

    /**
     * Guzzle envuelve lo que lance on_redirect dentro de su propia excepción,
     * así que hay que mirar también la causa encadenada.
     */
    private function wasBlockedRedirect(mixed $reason): bool
    {
        while ($reason instanceof \Throwable) {
            if ($reason instanceof BlockedRedirectException) {
                return true;
            }

            $reason = $reason->getPrevious();
        }

        return false;
    }

    private function failureReason(mixed $reason): string
    {
        if ($this->wasBlockedRedirect($reason)) {
            return __('helpdeskemailactivity::emaillog.preview.inspector.link_check.status.blocked');
        }

        if ($reason instanceof ConnectException) {
            return __('helpdeskemailactivity::emaillog.preview.inspector.link_check.status.unreachable');
        }

        if ($reason instanceof RequestException && $reason->hasResponse()) {
            return $reason->getResponse()->getReasonPhrase();
        }

        return __('helpdeskemailactivity::emaillog.preview.inspector.link_check.status.failed');
    }

    /**
     * URLs únicas del mensaje, en orden de aparición.
     *
     * @return list<string>
     */
    public function extractUrls(?string $html, ?string $text): array
    {
        $urls = [];

        foreach ($this->urlsFromHtml((string) $html) as $url) {
            $urls[$url] = true;
        }

        foreach ($this->urlsFromText((string) $text) as $url) {
            $urls[$url] = true;
        }

        return array_values(array_keys($urls));
    }

    /**
     * @return list<string>
     */
    private function urlsFromHtml(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($doc);
        $urls = [];

        // Enlaces, imágenes y hojas de estilo remotas: las tres cosas que un
        // cliente de correo intentará resolver al abrir el mensaje.
        $nodes = $xpath->query('//a[@href] | //area[@href] | //img[@src] | //link[@href] | //source[@src]');

        foreach ($nodes ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $value = $node->hasAttribute('href') ? $node->getAttribute('href') : $node->getAttribute('src');

            if ($url = $this->normalize($value)) {
                $urls[] = $url;
            }
        }

        // url(...) dentro de estilos: fondos que el cliente también descarga.
        if (preg_match_all('~url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)~i', $html, $matches)) {
            foreach ($matches[1] as $value) {
                if ($url = $this->normalize($value)) {
                    $urls[] = $url;
                }
            }
        }

        return $urls;
    }

    /**
     * @return list<string>
     */
    private function urlsFromText(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }

        if (! preg_match_all('~https?://[^\s<>"\'\)\]]+~i', $text, $matches)) {
            return [];
        }

        $urls = [];

        foreach ($matches[0] as $value) {
            // Un punto o una coma finales casi nunca son de la URL: vienen de
            // la frase que la envuelve.
            if ($url = $this->normalize(rtrim($value, '.,;:'))) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * Descarta lo que ningún navegador pediría por red: anclas, mailto:, tel:,
     * data: URIs y rutas relativas (un correo no tiene página base).
     */
    private function normalize(string $value): ?string
    {
        $value = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($value === '' || ! preg_match('~^https?://~i', $value)) {
            return null;
        }

        return $value;
    }

    /**
     * ¿Es el píxel de apertura o un enlace reescrito por este mismo módulo?
     * Se reconocen por la ruta que declaran sus propias rutas: /e/{uuid}.gif y
     * /e/{uuid}/c/{token} (ver routes/web.php).
     */
    private function isOwnTrackingUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';

        return preg_match('~^/e/[0-9a-f-]{36}(\.gif|/c/)~i', $path) === 1;
    }
}
