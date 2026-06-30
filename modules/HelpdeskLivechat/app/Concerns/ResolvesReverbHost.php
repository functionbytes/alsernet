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

        if ($host === '' || in_array($host, ['0.0.0.0', '::', '[::]'], true)) {
            return request()->getHost();
        }

        return $host;
    }
}
