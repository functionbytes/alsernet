<?php

namespace Modules\HelpdeskLivechat\Concerns;

/**
 * Resolves a browser-connectable Reverb host for the widget.
 *
 * The configured Reverb host is frequently the *bind* address (0.0.0.0 / ::),
 * which a browser cannot open a WebSocket to. Fall back to the request host so
 * the widget targets a reachable hostname instead of silently failing to
 * connect to realtime.
 */
trait ResolvesReverbHost
{
    protected function connectableReverbHost(): string
    {
        $host = (string) config('broadcasting.connections.reverb.options.host', '');

        // 'reverb' is the docker-compose service name used for server-to-server
        // publishing between containers — also unreachable from a browser.
        if ($host === '' || in_array($host, ['0.0.0.0', '::', '[::]', 'reverb'], true)) {
            return request()->getHost();
        }

        return $host;
    }

    /**
     * The 'options.port'/'options.scheme' config is for server-to-server calls
     * (backend -> reverb container over the internal Docker network) and is
     * never reachable from a browser. 'public_port'/'public_scheme' are the
     * values a browser (admin inbox or the embeddable widget) should use —
     * e.g. the nginx-proxied port on the same host the request came in on.
     */
    protected function connectableReverbPort(): int
    {
        $port = config('broadcasting.connections.reverb.public_port');

        return $port !== null && $port !== ''
            ? (int) $port
            : (int) config('broadcasting.connections.reverb.options.port', 8080);
    }

    protected function connectableReverbScheme(): string
    {
        $scheme = config('broadcasting.connections.reverb.public_scheme');

        return $scheme ?: config('broadcasting.connections.reverb.options.scheme', 'http');
    }
}
