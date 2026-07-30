<?php

namespace Modules\HelpdeskAgents\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskAgents\Models\AiAgentSession;
use Modules\HelpdeskAgents\Models\AiAgentTool;

class ToolExecutionService
{
    public function execute(AiAgentTool $tool, array $arguments, AiAgentSession $session): array
    {
        $startedAt = hrtime(true);

        try {
            $result = $this->dispatch($tool, $arguments);
            $durationMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

            $this->logExecution($tool, $session, $arguments, $result, true, null, $durationMs);

            return ['success' => true, 'result' => $result, 'error' => null];
        } catch (\Throwable $e) {
            $durationMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

            Log::warning('ToolExecutionService: tool execution failed', [
                'tool_id' => $tool->id,
                'tool_name' => $tool->name,
                'tool_type' => $tool->type,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            $this->logExecution($tool, $session, $arguments, null, false, $e->getMessage(), $durationMs);

            return ['success' => false, 'result' => null, 'error' => $e->getMessage()];
        }
    }

    private function dispatch(AiAgentTool $tool, array $arguments): mixed
    {
        return match ($tool->type) {
            'api' => $this->executeApiTool($tool, $arguments),
            'database' => $this->executeDatabaseTool($tool, $arguments),
            'function' => $this->executeFunctionTool($tool, $arguments),
            default => throw new \RuntimeException("Unsupported tool type: {$tool->type}"),
        };
    }

    /**
     * Execute an HTTP API tool.
     * URL template supports {key} and {{key}} placeholders from arguments.
     */
    private function executeApiTool(AiAgentTool $tool, array $arguments): array|string
    {
        if (! config('helpdeskagents.tools.allow_api', false)) {
            throw new \RuntimeException('API tools are disabled by configuration');
        }

        $template = $tool->implementation ?? '';
        $authConfig = $tool->auth_config ?? [];
        $timeout = (int) config('helpdeskagents.tools.api_timeout', 15);

        // Esquema/host/puerto de la plantilla deben ser estáticos: los
        // placeholders (controlados por el LLM) solo pueden aparecer en
        // path/query, nunca decidir a qué host se conecta la tool.
        $templateParts = parse_url($template) ?: [];
        $templateHost = strtolower($templateParts['host'] ?? '');

        if ($templateHost === '' || str_contains($templateHost, '{')) {
            throw new \RuntimeException('API tool URL template must have a static host (placeholders are not allowed in the host)');
        }

        // Replace {key} and {{key}} template placeholders with argument values.
        // Los valores van SIEMPRE URL-encoded: un argumento del LLM no puede
        // introducir '/', '@', '?', '#' ni saltar a otro host/esquema.
        $url = $template;
        foreach ($arguments as $key => $value) {
            $encoded = rawurlencode((string) $value);
            $url = str_replace(["{{{$key}}}", "{{$key}}"], [$encoded, $encoded], $url);
        }

        $this->validateApiUrl($url);

        // Defensa en profundidad: la URL final debe conservar exactamente el
        // esquema/host/puerto de la plantilla validada.
        $finalParts = parse_url($url) ?: [];

        if (strtolower($finalParts['scheme'] ?? '') !== strtolower($templateParts['scheme'] ?? '')
            || strtolower($finalParts['host'] ?? '') !== $templateHost
            || ($finalParts['port'] ?? null) !== ($templateParts['port'] ?? null)) {
            throw new \RuntimeException('API tool arguments may not change the URL scheme, host or port');
        }

        $method = strtoupper($authConfig['method'] ?? 'GET');
        // Sin esto, validateApiUrl() solo protege la URL inicial: un host HTTPS
        // externo podría responder 3xx hacia una IP interna (metadata/loopback)
        // y Guzzle seguiría el redirect, bypaseando el guard SSRF.
        $client = Http::timeout($timeout)->withoutRedirecting()->acceptJson();

        match ($authConfig['type'] ?? '') {
            'bearer' => $client = $client->withToken($authConfig['token'] ?? ''),
            'basic' => $client = $client->withBasicAuth($authConfig['user'] ?? '', $authConfig['pass'] ?? ''),
            'header' => $client = $client->withHeader($authConfig['name'] ?? 'X-API-Key', $authConfig['value'] ?? ''),
            default => null,
        };

        $response = $method === 'POST'
            ? $client->post($url, $arguments)
            : $client->get($url);

        if ($response->failed()) {
            throw new \RuntimeException("API call failed with status {$response->status()}");
        }

        return $response->json() ?? ['body' => $response->body()];
    }

    /**
     * Validate that a URL is safe to call (SSRF prevention).
     * - Must use HTTPS scheme
     * - Must not resolve to a private/loopback/link-local IP
     * - If allowed_hosts is configured, host must be in the allowlist
     *
     * @throws \RuntimeException
     */
    private function validateApiUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (! $parsed || ($parsed['scheme'] ?? '') !== 'https') {
            throw new \RuntimeException('API tool URL must use HTTPS scheme');
        }

        $host = $parsed['host'] ?? '';

        if ($host === '') {
            throw new \RuntimeException('API tool URL has no host');
        }

        $allowedHosts = config('helpdeskagents.tools.allowed_hosts', []);
        if (! empty($allowedHosts) && ! in_array($host, $allowedHosts, true)) {
            throw new \RuntimeException("Host '{$host}' is not in the allowed hosts list");
        }

        $ip = @gethostbyname($host);

        if ($ip && $this->isPrivateIp($ip)) {
            throw new \RuntimeException('API tool URL resolves to a private or reserved IP address');
        }
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * Execute a read-only database query tool.
     * Only SELECT statements are allowed for safety.
     */
    private function executeDatabaseTool(AiAgentTool $tool, array $arguments): array
    {
        if (! config('helpdeskagents.tools.allow_database', false)) {
            throw new \RuntimeException('Database tools are disabled by configuration');
        }

        $sql = trim($tool->implementation ?? '');

        if (! str_starts_with(strtoupper($sql), 'SELECT')) {
            throw new \RuntimeException('Only SELECT queries are allowed for database tools');
        }

        // Bloquea multi-statement e inyección por comentarios (incl. `#` de
        // MySQL) que podrían esquivar la extracción de tablas de abajo.
        if (str_contains($sql, ';') || str_contains($sql, '--') || str_contains($sql, '/*') || str_contains($sql, '#')) {
            throw new \RuntimeException('Database tool queries may not contain multiple statements or comments');
        }

        // RIESGO: aun siendo "solo SELECT", MySQL permite escribir/leer
        // ficheros del servidor desde un SELECT — `SELECT ... INTO OUTFILE`
        // (webshell/RCE) y `LOAD_FILE()` (lectura arbitraria de ficheros).
        // Se rechazan explícitamente; la allowlist de tablas de abajo no
        // protege contra esto porque no cambia las tablas referenciadas.
        if (preg_match('/\binto\s+(?:outfile|dumpfile)\b|\bload_file\s*\(/i', $sql)) {
            throw new \RuntimeException('Database tool queries may not use INTO OUTFILE/DUMPFILE or LOAD_FILE');
        }

        // Rechaza cross-joins por coma (FROM a, b): extractReferencedTables()
        // solo captura la tabla inmediatamente tras FROM/JOIN, así que una
        // segunda tabla separada por coma se colaría sin pasar la allowlist
        // (p.ej. `FROM migrations, users`). Las tools legítimas usan JOIN
        // explícito; fail-closed: se bloquea la coma en la cláusula FROM.
        if (preg_match('/\bfrom\b(.*?)(?:\bwhere\b|\bgroup\b|\border\b|\bhaving\b|\blimit\b|\bjoin\b|$)/is', $sql, $fromClause)
            && str_contains($fromClause[1], ',')) {
            throw new \RuntimeException('Database tool queries may not use comma-separated (implicit) joins');
        }

        // Allowlist de tablas (fail-closed): aunque los db-tools estén
        // habilitados y quien crea la tool tenga el permiso, un SELECT solo
        // puede leer tablas explícitamente permitidas — nunca `users`,
        // `password_reset_tokens`, `information_schema`, etc.
        $allowedTables = array_map('strtolower', (array) config('helpdeskagents.tools.allowed_tables', []));
        $referenced = $this->extractReferencedTables($sql);

        if ($referenced === []) {
            throw new \RuntimeException('Could not determine the tables referenced by the database tool query');
        }

        $forbidden = array_values(array_diff($referenced, $allowedTables));
        if ($forbidden !== []) {
            throw new \RuntimeException('Database tool query references tables not in the allow-list: '.implode(', ', $forbidden));
        }

        $rows = DB::select($sql, $arguments);

        return array_map(fn ($row) => (array) $row, $rows);
    }

    /**
     * Nombres de tabla (minúsculas) referenciados tras FROM/JOIN.
     *
     * @return array<int, string>
     */
    private function extractReferencedTables(string $sql): array
    {
        preg_match_all('/\b(?:from|join)\s+`?([a-zA-Z_][a-zA-Z0-9_.]*)`?/i', $sql, $m);

        return array_values(array_unique(array_map('strtolower', $m[1] ?? [])));
    }

    /**
     * Execute a registered PHP function tool.
     * Only functions in the explicit allow-list in config may run.
     */
    private function executeFunctionTool(AiAgentTool $tool, array $arguments): mixed
    {
        if (! config('helpdeskagents.tools.allow_function', false)) {
            throw new \RuntimeException('Function tools are disabled by configuration');
        }

        $allowedFunctions = config('helpdeskagents.tools.allowed_functions', []);
        $functionName = $tool->implementation ?? '';

        if (! in_array($functionName, $allowedFunctions, true)) {
            throw new \RuntimeException("Function '{$functionName}' is not in the allowed functions list");
        }

        return app()->call($functionName, $arguments);
    }

    private function logExecution(
        AiAgentTool $tool,
        AiAgentSession $session,
        array $arguments,
        mixed $result,
        bool $success,
        ?string $error,
        int $durationMs
    ): void {
        try {
            DB::connection('helpdesk')->table('helpdesk_ai_tool_executions')->insert([
                'tool_id' => $tool->id,
                'session_id' => $session->id,
                'arguments' => json_encode($arguments),
                'result' => json_encode($result),
                'success' => $success,
                'error_message' => $error,
                'duration_ms' => $durationMs,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ToolExecutionService: failed to persist execution log', [
                'tool_id' => $tool->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
