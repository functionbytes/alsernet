<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Cabeceras de seguridad en TODAS las respuestas de la app (middleware global,
 * registrado el 29-sep-2026 en bootstrap/app.php). Configuración: config/security.php.
 *
 * - X-Content-Type-Options, Referrer-Policy, Permissions-Policy, X-Robots-Tag.
 * - Content-Security-Policy-Report-Only (o bloqueante si csp.report_only=false)
 *   en respuestas HTML, con informes a POST /csp-report.
 * - X-Frame-Options y HSTS los pone Apache; aquí solo si se activan en config.
 * - Rutas incrustables (security.headers.embeddable_paths): CSP bloqueante con
 *   solo frame-ancestors para permitir el iframe desde los dominios de la tienda.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security.headers.enabled', true)) {
            return $response;
        }

        $headers = $response->headers;
        $embeddable = $this->isEmbeddable($request);

        $headers->set('X-Content-Type-Options', (string) config('security.headers.x_content_type_options', 'nosniff'));

        if ($referrer = config('security.headers.referrer_policy')) {
            $headers->set('Referrer-Policy', (string) $referrer);
        }

        if (($permissions = config('security.headers.permissions_policy')) && ! $headers->has('Permissions-Policy')) {
            $headers->set('Permissions-Policy', (string) $permissions);
        }

        if ($robots = config('security.headers.robots_tag')) {
            $headers->set('X-Robots-Tag', (string) $robots);
        }

        if (($frameOptions = config('security.headers.frame_options')) && ! $embeddable) {
            $headers->set('X-Frame-Options', (string) $frameOptions);
        }

        if (config('security.headers.hsts') && $request->secure()) {
            $headers->set('Strict-Transport-Security', (string) config('security.headers.hsts_value'));
        }

        $frameOnlyCsp = false;
        if ($embeddable && ! $headers->has('Content-Security-Policy')) {
            $headers->remove('X-Frame-Options');
            $headers->set('Content-Security-Policy', 'frame-ancestors '.$this->frameAncestors());
            $frameOnlyCsp = true;
        }

        if (config('security.csp.enabled', true) && $this->isHtml($response)) {
            $this->applyCsp($request, $response, $embeddable, $frameOnlyCsp);
        }

        return $response;
    }

    private function applyCsp(Request $request, Response $response, bool $embeddable, bool $frameOnlyCsp): void
    {
        $reportOnly = (bool) config('security.csp.report_only', true);
        $header = $reportOnly ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';

        // Si el controlador ya puso su propia política (p. ej. adjuntos con
        // "default-src 'none'; sandbox"), no la tocamos. La de solo
        // frame-ancestors que pone este middleware sí se sustituye en modo bloqueante.
        if ($response->headers->has($header) && ! (! $reportOnly && $frameOnlyCsp)) {
            return;
        }

        $directives = (array) config('security.csp.directives', []);
        if ($embeddable) {
            $directives['frame-ancestors'] = [$this->frameAncestors()];
        }

        $parts = [];
        foreach ($directives as $name => $sources) {
            $sources = array_filter(array_map('strval', (array) $sources), fn ($s) => $s !== '');
            $parts[] = trim($name.' '.implode(' ', $sources));
        }

        $reportPath = (string) config('security.csp.report_path', '/csp-report');
        if ($reportPath !== '') {
            $parts[] = 'report-uri '.$reportPath;
            $parts[] = 'report-to csp-endpoint';
            $response->headers->set(
                'Reporting-Endpoints',
                'csp-endpoint="'.$request->getSchemeAndHttpHost().$reportPath.'"'
            );
        }

        $response->headers->set($header, implode('; ', $parts));
    }

    private function isEmbeddable(Request $request): bool
    {
        $paths = (array) config('security.headers.embeddable_paths', []);

        return $paths !== [] && $request->is(...$paths);
    }

    private function frameAncestors(): string
    {
        return (string) config('security.headers.frame_ancestors', "'self'");
    }

    private function isHtml(Response $response): bool
    {
        if ($response instanceof BinaryFileResponse
            || $response instanceof StreamedResponse
            || $response instanceof JsonResponse) {
            return false;
        }

        $type = (string) $response->headers->get('Content-Type', '');

        // Sin Content-Type explícito Symfony enviará text/html.
        return $type === '' || str_contains(strtolower($type), 'text/html');
    }
}
