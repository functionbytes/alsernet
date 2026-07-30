<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChatFlow\Services\Concerns\GuardsAgainstSsrf;

/**
 * Performs a generic outbound HTTP request for the `http_request` node and
 * extracts a value from the JSON response into the flow context. Includes
 * basic SSRF protection against private/loopback hosts.
 */
class ChatFlowHttpRequester
{
    use GuardsAgainstSsrf;

    /**
     * @param  array<string,mixed>  $data  Node data (method, url, headers, body, response_path)
     * @param  array<string,mixed>  $context  Session context for {{var}} interpolation
     * @return array{ok: bool, status: int|null, error: string|null, value: mixed, body: mixed}
     */
    public function send(array $data, array $context): array
    {
        $method = strtoupper($data['method'] ?? 'GET');
        // urlEncode=true: los valores de {{var}} que un cliente controla (p. ej.
        // last_input) se codifican al interpolarse en la URL, de modo que no
        // puedan escapar del path/query previsto (../, ?, &, #) hacia rutas no
        // deseadas dentro de un host que el guard SSRF sí permite.
        $url = $this->interpolate((string) ($data['url'] ?? ''), $context, urlEncode: true);

        $resolved = $this->ssrfSafeCurlOptions($url);

        if ($resolved === null) {
            return $this->fail('URL no permitida o host privado bloqueado.');
        }

        $headers = $this->buildHeaders($data['headers'] ?? [], $context);
        $timeout = (int) config('helpdeskchatflow.http.timeout', 10);

        try {
            $request = Http::timeout($timeout)
                ->withHeaders($headers)
                ->acceptJson()
                ->withoutRedirecting()
                ->withOptions(['curl' => $resolved]);

            $response = match ($method) {
                'POST' => $request->post($url, $this->buildBody($data['body'] ?? [], $context)),
                'PUT' => $request->put($url, $this->buildBody($data['body'] ?? [], $context)),
                'PATCH' => $request->patch($url, $this->buildBody($data['body'] ?? [], $context)),
                'DELETE' => $request->delete($url),
                default => $request->get($url, $this->buildBody($data['body'] ?? [], $context)),
            };
        } catch (\Throwable $e) {
            Log::warning('ChatFlowHttpRequester: request failed', ['url' => $url, 'error' => $e->getMessage()]);

            return $this->fail($e->getMessage());
        }

        $json = $response->json();
        $path = trim((string) ($data['response_path'] ?? ''));
        $value = $path !== '' ? Arr::get($json ?? [], $path) : ($json ?? $response->body());

        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'error' => $response->successful() ? null : "HTTP {$response->status()}",
            'value' => $value,
            'body' => $json,
        ];
    }

    /**
     * @param  array<int, array{key?: string, value?: string}>|array<string,string>  $rawHeaders
     * @param  array<string,mixed>  $context
     * @return array<string,string>
     */
    private function buildHeaders(array $rawHeaders, array $context): array
    {
        $headers = [];

        foreach ($rawHeaders as $key => $row) {
            if (is_array($row)) {
                $name = trim((string) ($row['key'] ?? ''));
                $val = (string) ($row['value'] ?? '');
            } else {
                $name = trim((string) $key);
                $val = (string) $row;
            }

            if ($name !== '') {
                $headers[$name] = $this->interpolate($val, $context);
            }
        }

        return $headers;
    }

    /**
     * @param  array<int, array{key?: string, value?: string}>|array<string,mixed>|string  $rawBody
     * @param  array<string,mixed>  $context
     * @return array<string,mixed>
     */
    private function buildBody(mixed $rawBody, array $context): array
    {
        if (is_string($rawBody)) {
            $decoded = json_decode($this->interpolate($rawBody, $context), true);

            return is_array($decoded) ? $decoded : [];
        }

        if (! is_array($rawBody)) {
            return [];
        }

        $body = [];

        foreach ($rawBody as $key => $row) {
            if (is_array($row) && isset($row['key'])) {
                $body[(string) $row['key']] = $this->interpolate((string) ($row['value'] ?? ''), $context);
            } else {
                $body[(string) $key] = is_string($row) ? $this->interpolate($row, $context) : $row;
            }
        }

        return $body;
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function interpolate(string $text, array $context, bool $urlEncode = false): string
    {
        return preg_replace_callback(
            '/\{\{(\w+)\}\}/',
            function ($m) use ($context, $urlEncode) {
                $value = $context[$m[1]] ?? null;

                if (! is_scalar($value)) {
                    return $m[0];
                }

                return $urlEncode ? rawurlencode((string) $value) : (string) $value;
            },
            $text
        );
    }

    private function fail(string $error): array
    {
        return ['ok' => false, 'status' => null, 'error' => $error, 'value' => null, 'body' => null];
    }
}
