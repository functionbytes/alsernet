<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP de las acciones de tipo http, con guarda SSRF: solo hosts de
 * config('ai-actions.http_allowed_hosts'), https (http únicamente en local),
 * sin IPs privadas/loopback tras resolver DNS, sin redirecciones, timeout
 * <= 10 s y respuesta <= 256 KB. Los secretos van solo en cabeceras.
 */
class HttpActionClient
{
    private const FORBIDDEN_HEADERS = ['host', 'cookie', 'authorization', 'proxy-authorization', 'content-length'];

    public function __construct(
        private readonly TemplateResolver $templates,
        private readonly HostResolver $resolver,
    ) {}

    /**
     * @param  array<string, mixed>  $config  method, url, headers, body, auth
     * @param  array<string, mixed>  $vars
     * @param  array<string, mixed>  $secrets
     * @return array<int|string, mixed>|null
     *
     * @throws ActionRefusal
     */
    public function request(array $config, array $vars, array $secrets, int $timeout): ?array
    {
        $method = strtoupper((string) ($config['method'] ?? 'GET'));
        if (! in_array($method, ['GET', 'POST'], true)) {
            throw ActionRefusal::error('http_method_not_allowed');
        }

        $url = (string) $this->templates->resolve((string) ($config['url'] ?? ''), $vars, true);
        $target = $this->assertUrlAllowed($url);

        $headers = $this->buildHeaders($config, $vars, $secrets);
        $body = (array) $this->templates->resolve((array) ($config['body'] ?? []), $vars);
        $maxBytes = (int) config('ai-actions.http_max_response_bytes', 262144);

        $options = [
            'allow_redirects' => false,
            'on_headers' => function ($response) use ($maxBytes): void {
                if ((int) $response->getHeaderLine('Content-Length') > $maxBytes) {
                    throw new \RuntimeException('response_too_large');
                }
            },
        ];

        if ($target['ip'] !== null) {
            // Se fija la IP ya validada: evita que un DNS rebinding cambie el destino.
            $options['curl'] = [CURLOPT_RESOLVE => ["{$target['host']}:{$target['port']}:{$target['ip']}"]];
        }

        $pending = Http::withHeaders($headers)
            ->withOptions($options)
            ->timeout(min(max($timeout, 1), (int) config('ai-actions.max_timeout', 10)))
            ->acceptJson();

        // En GET, $body son parámetros extra de query: se añaden a la URL ya
        // resuelta (pasar un array a get() descartaría la query de la URL).
        if ($method === 'GET' && $body !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($body);
        }

        $response = $method === 'GET'
            ? $pending->get($url)
            : $pending->asJson()->post($url, $body);

        if (! $response->successful()) {
            throw ActionRefusal::error('http_status_'.$response->status());
        }

        if (strlen($response->body()) > $maxBytes) {
            throw ActionRefusal::error('response_too_large');
        }

        $decoded = $response->json();

        if (is_array($decoded)) {
            return $decoded;
        }

        $text = trim($response->body());

        return $text === '' ? null : ['text' => $text];
    }

    /**
     * Valida un destino sin enviar nada (también lo usa el validador al guardar).
     *
     * @return array{host: string, port: int, ip: ?string}
     *
     * @throws ActionRefusal
     */
    public function assertUrlAllowed(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw ActionRefusal::error('url_invalid');
        }

        $isLocalHttp = $scheme === 'http' && app()->environment('local');
        if ($scheme !== 'https' && ! $isLocalHttp) {
            throw ActionRefusal::error('url_scheme_not_allowed');
        }

        $allowed = array_map('strtolower', (array) config('ai-actions.http_allowed_hosts', []));
        if (! in_array($host, $allowed, true)) {
            throw ActionRefusal::error('host_not_allowed');
        }

        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        if (! in_array($port, [80, 443], true)) {
            throw ActionRefusal::error('port_not_allowed');
        }

        $ips = $this->resolver->resolve($host);
        if ($ips === []) {
            throw ActionRefusal::error('host_unresolvable');
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw ActionRefusal::error('host_private_ip');
            }
        }

        $literal = filter_var($host, FILTER_VALIDATE_IP) !== false;

        return ['host' => $host, 'port' => $port, 'ip' => $literal ? null : $ips[0]];
    }

    private function isPublicIp(string $ip): bool
    {
        if (str_starts_with(strtolower($ip), '::ffff:')) {
            $ip = substr($ip, 7); // IPv4 mapeada en IPv6
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        // CGNAT 100.64.0.0/10 no está cubierto por los flags de PHP.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $long = ip2long($ip);

            return ! ($long >= ip2long('100.64.0.0') && $long <= ip2long('100.127.255.255'));
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $vars
     * @param  array<string, mixed>  $secrets
     * @return array<string, string>
     */
    private function buildHeaders(array $config, array $vars, array $secrets): array
    {
        $headers = [];

        foreach ((array) $this->templates->resolve((array) ($config['headers'] ?? []), $vars) as $name => $value) {
            if (in_array(strtolower((string) $name), self::FORBIDDEN_HEADERS, true)) {
                continue;
            }
            $headers[(string) $name] = str_replace(["\r", "\n"], '', (string) $value);
        }

        $auth = (array) ($config['auth'] ?? []);
        $secret = (string) ($secrets[$auth['secret'] ?? ''] ?? '');
        $type = $auth['type'] ?? null;

        if ($type !== null && $secret === '') {
            throw ActionRefusal::error('secret_missing');
        }

        if ($type === 'bearer') {
            $headers['Authorization'] = 'Bearer '.str_replace(["\r", "\n"], '', $secret);
        }

        if ($type === 'header') {
            $headerName = (string) ($auth['header'] ?? 'X-Api-Key');
            $headers[$headerName] = str_replace(["\r", "\n"], '', $secret);
        }

        return $headers;
    }
}
