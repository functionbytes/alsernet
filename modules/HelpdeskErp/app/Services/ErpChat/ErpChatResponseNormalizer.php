<?php

namespace Modules\HelpdeskErp\Services\ErpChat;

use Illuminate\Http\Client\Response;

/**
 * Traduce cualquier respuesta del manager (o su ausencia) a un estado único
 * que la interfaz sabe pintar:
 *
 *   ok          datos válidos
 *   loading     el manager lanzó el escaneo Oracle en segundo plano (pedidos)
 *   blocked     falta GRANT SELECT en Oracle (ORA-00942 / ORA-01031)
 *   unavailable 404 / endpoint inexistente / dato ajeno al cliente
 *   down        sin conexión, timeout, 5xx o error Oracle genérico
 *
 * El manager devuelve muchos errores con HTTP 200 y success:false, así que
 * se mira SIEMPRE el campo success antes que el código HTTP.
 */
class ErpChatResponseNormalizer
{
    public const STATES = ['ok', 'loading', 'blocked', 'unavailable', 'down'];

    public const MSG_BLOCKED = 'Pendiente de permiso en Oracle';

    public const MSG_DOWN = 'Gestión no responde ahora mismo.';

    public const MSG_UNAVAILABLE = 'No disponible en Gestión.';

    /**
     * @param  Response|\Throwable|null  $response
     * @param  int|string|null  $expectId  id que debe traer data.id (detalles)
     * @return array<string, mixed>
     */
    public function normalize(mixed $response, int|string|null $expectId = null, bool $soft = false): array
    {
        $result = $this->classify($response, $expectId);

        // Endpoints nuevos del manager (historial, envío, devoluciones): pueden
        // no existir aún o no estar expuestos. Un fallo que no sea de conexión
        // ni de permisos se pinta como "no disponible", nunca como error.
        if ($soft && $result['state'] === 'down' && in_array($result['reason'], ['server_error', 'oracle_error'], true)) {
            return $this->make('unavailable', null, self::MSG_UNAVAILABLE, 'not_exposed');
        }

        return $result;
    }

    /**
     * @param  Response|\Throwable|null  $response
     * @return array<string, mixed>
     */
    private function classify(mixed $response, int|string|null $expectId): array
    {
        if ($response === null || $response instanceof \Throwable) {
            return $this->make('down', null, self::MSG_DOWN, 'connection');
        }

        if (! $response instanceof Response) {
            return $this->make('down', null, self::MSG_DOWN, 'connection');
        }

        $status = $response->status();
        $json = $this->decode($response);

        if ($json === null) {
            if ($status === 404 || $status === 405) {
                return $this->make('unavailable', null, self::MSG_UNAVAILABLE, 'endpoint_missing');
            }

            if ($status >= 500) {
                return $this->make('down', null, self::MSG_DOWN, 'server_error');
            }

            return $this->make('unavailable', null, self::MSG_UNAVAILABLE, 'invalid_response');
        }

        $success = $json['success'] ?? null;
        $error = $this->errorText($json);

        if ($success === false || $status >= 400) {
            if ($this->isBlocked($error)) {
                return $this->make('blocked', null, self::MSG_BLOCKED, 'grant');
            }

            // El manager avisa de que no expone ese dato (p. ej. el historial
            // de estados del pedido al usuario de lectura).
            if (preg_match('/^(not_available|not_implemented|unavailable)$/i', trim((string) ($json['error'] ?? '')))) {
                $message = is_string($json['message'] ?? null) && trim($json['message']) !== '' ? trim($json['message']) : self::MSG_UNAVAILABLE;

                return $this->make('unavailable', null, $message, 'not_exposed');
            }

            if ($this->isLostConnection($error)) {
                return $this->make('down', null, self::MSG_DOWN, 'lost_connection');
            }

            if ($status === 404) {
                $reason = stripos($error, 'not found') !== false ? 'not_found' : 'endpoint_missing';

                return $this->make('unavailable', null, self::MSG_UNAVAILABLE, $reason);
            }

            if ($status === 405) {
                return $this->make('unavailable', null, self::MSG_UNAVAILABLE, 'endpoint_missing');
            }

            return $this->make('down', null, self::MSG_DOWN, $status >= 500 ? 'server_error' : 'oracle_error');
        }

        $meta = is_array($json['meta'] ?? null) ? $json['meta'] : [];

        if (! empty($meta['loading'])) {
            $result = $this->make('loading', [], 'Buscando en Gestión…', 'scanning');
            $result['retry_after'] = max(5, (int) ($meta['retry_after'] ?? 35));

            return $result;
        }

        $data = $json['data'] ?? null;

        if ($expectId !== null && ! $this->belongs($data, $expectId)) {
            return $this->make('unavailable', null, 'No pertenece a este cliente.', 'foreign');
        }

        $result = $this->make('ok', $data, null, null);

        if (is_array($json['pagination'] ?? null)) {
            $result['pagination'] = [
                'limit' => (int) ($json['pagination']['limit'] ?? 0),
                'offset' => (int) ($json['pagination']['offset'] ?? 0),
                'count' => (int) ($json['pagination']['count'] ?? 0),
                'has_more' => (bool) ($json['pagination']['hasMore'] ?? $json['pagination']['has_more'] ?? false),
            ];
        }

        return $result;
    }

    /**
     * Estado construido sin respuesta HTTP (configuración, cortacircuitos…).
     *
     * @return array<string, mixed>
     */
    public function make(string $state, mixed $data, ?string $message, ?string $reason): array
    {
        return [
            'state' => $state,
            'data' => $data,
            'message' => $message,
            'reason' => $reason,
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    public function isBlocked(string $error): bool
    {
        return $error !== '' && (bool) preg_match('/GRANT|Acceso denegado|ORA-00942|ORA-01031|insufficient privileges|table or view does not exist/i', $error);
    }

    public function isLostConnection(string $error): bool
    {
        return $error !== '' && (bool) preg_match('/Lost connection|no reconnector/i', $error);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(Response $response): ?array
    {
        try {
            $json = $response->json();
        } catch (\Throwable) {
            return null;
        }

        return is_array($json) ? $json : null;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function errorText(array $json): string
    {
        $error = $json['error'] ?? '';
        if (is_string($error) && isset($json['message']) && is_string($json['message'])) {
            $error = trim($error.' '.$json['message']);
        }
        $error = $error === '' ? ($json['message'] ?? '') : $error;

        if (is_array($error)) {
            $error = (string) ($error['message'] ?? json_encode($error));
        }

        return (string) $error;
    }

    private function belongs(mixed $data, int|string $expectId): bool
    {
        if (! is_array($data) || $data === []) {
            return false;
        }

        return isset($data['id']) && (string) $data['id'] === (string) $expectId;
    }
}
