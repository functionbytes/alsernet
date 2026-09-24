<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;

/**
 * Lecturas HTTP de la extensión "erpbridge" contra las dos puertas que
 * tiene Gestión desde aquí:
 *
 *   - API REST de Gestión (InterGes): XML, la única que sabe buscar un pedido
 *     por `identificadororigen` (= id_order de PrestaShop).
 *   - Manager (proxy Oracle que ya usa HelpdeskErp): JSON, cliente por
 *     CODIGO_INTERNET, detalle de pedido, albaranes y facturas.
 *
 * SOLO GET. Cada método devuelve el mismo sobre:
 *   ['ok' => bool, 'status' => int, 'data' => mixed, 'error' => ?string]
 * ok=false con status=0 es "no se pudo conectar"; con status>0 es la
 * respuesta real del servidor, y `error` lleva el texto que dio (p. ej. el
 * "Acceso denegado a la tabla Oracle…" del manager), para enseñarlo tal cual.
 */
class ErpbridgeGestionClient
{
    /* ── API REST de Gestión (InterGes) ─────────────────────────────────── */

    /**
     * Pedidos de Gestión con ese identificador de origen (normalmente uno).
     *
     * @return array{ok:bool, status:int, data:array<int, array<string, mixed>>, error:?string}
     */
    public function orderByOrigin(int $psOrderId): array
    {
        return $this->gestionList('pedido-cliente/', ['identificadororigen' => $psOrderId]);
    }

    /**
     * @return array{ok:bool, status:int, data:array<int, array<string, mixed>>, error:?string}
     */
    public function orderHistoryByOrigin(int $psOrderId): array
    {
        return $this->gestionList('pedido-cliente-hist/', ['identificadororigen' => $psOrderId]);
    }

    /**
     * @return array{ok:bool, status:int, data:array<int, array<string, mixed>>, error:?string}
     */
    public function orderTrackingByOrigin(int $psOrderId): array
    {
        return $this->gestionList('pedido-cliente-tracking/', ['identificadororigen' => $psOrderId]);
    }

    /* ── Manager (proxy Oracle de HelpdeskErp) ──────────────────────────── */

    /**
     * Cliente de Gestión cuyo CODIGO_INTERNET es el id_customer de PrestaShop.
     *
     * @return array{ok:bool, status:int, data:mixed, error:?string}
     */
    public function customerByWebId(int $psCustomerId): array
    {
        return $this->managerGet("erp/customer/search/web/{$psCustomerId}");
    }

    /**
     * @return array{ok:bool, status:int, data:mixed, error:?string}
     */
    public function managerOrderDetail(int $erpCustomerId, string $centralOrderId): array
    {
        return $this->managerGet("erp/customer/{$erpCustomerId}/orders/{$centralOrderId}");
    }

    /**
     * @return array{ok:bool, status:int, data:mixed, error:?string}
     */
    public function deliveryNotes(int $erpCustomerId, int $limit = 10): array
    {
        return $this->managerGet("erp/customer/{$erpCustomerId}/delivery-notes", ['limit' => $limit]);
    }

    /**
     * @return array{ok:bool, status:int, data:mixed, error:?string}
     */
    public function deliveryNoteDetail(int $erpCustomerId, string $deliveryId): array
    {
        return $this->managerGet("erp/customer/{$erpCustomerId}/delivery-notes/{$deliveryId}");
    }

    /**
     * @return array{ok:bool, status:int, data:mixed, error:?string}
     */
    public function invoiceDetail(int $erpCustomerId, string $invoiceId): array
    {
        return $this->managerGet("erp/customer/{$erpCustomerId}/invoices/{$invoiceId}");
    }

    /**
     * Sonda barata de acceso a FACTURACLI_CENTRAL (una fila).
     *
     * @return array{ok:bool, status:int, data:mixed, error:?string}
     */
    public function invoicesProbe(int $erpCustomerId): array
    {
        return $this->managerGet("erp/customer/{$erpCustomerId}/invoices", ['limit' => 1]);
    }

    /* ── Internos ──────────────────────────────────────────────────────── */

    /**
     * @param  array<string, scalar>  $query
     * @return array{ok:bool, status:int, data:array<int, array<string, mixed>>, error:?string}
     */
    private function gestionList(string $path, array $query): array
    {
        $base = $this->gestionBase();
        if ($base === null) {
            return ['ok' => false, 'status' => 0, 'data' => [], 'error' => 'La API de Gestión no está configurada (ajuste erp_api_url).'];
        }

        try {
            $resp = Http::timeout((int) config('helpdeskprestashop.ext.erpbridge.gestion_timeout', 8))
                ->connectTimeout(4)
                ->accept('application/xml')
                // El servidor de Gestión enruta por Host (vhost por nombre): con
                // "host:puerto" contesta un vhost por defecto que da 200 vacíos.
                ->withHeaders(['Host' => $base['host']])
                ->get($base['url'].$path, $query);
        } catch (\Throwable $e) {
            Log::warning('HelpdeskPrestashop erpbridge: sin conexión con la API de Gestión.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'status' => 0, 'data' => [], 'error' => 'No se pudo conectar con la API de Gestión.'];
        }

        // pedido-cliente-hist contesta 404 "Not Found" cuando el pedido no
        // existe (pedido-cliente, en cambio, un <response/> vacío): los dos
        // son "Gestión no tiene nada con ese identificador", no una caída.
        if ($resp->status() === 404) {
            return ['ok' => true, 'status' => 404, 'data' => [], 'error' => null];
        }

        if (! $resp->successful()) {
            return ['ok' => false, 'status' => $resp->status(), 'data' => [], 'error' => 'La API de Gestión respondió con el código '.$resp->status().'.'];
        }

        $resources = $this->parseXmlResources((string) $resp->body());
        if ($resources === null) {
            return ['ok' => false, 'status' => $resp->status(), 'data' => [], 'error' => 'La API de Gestión devolvió una respuesta que no es XML.'];
        }

        return ['ok' => true, 'status' => $resp->status(), 'data' => $resources, 'error' => null];
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array{ok:bool, status:int, data:mixed, error:?string}
     */
    private function managerGet(string $path, array $query = []): array
    {
        $result = $this->managerGetOnce($path, $query);

        // El manager pierde a ratos la conexión Eloquent con Oracle en un
        // worker de PHP-FPM ("Lost connection and no reconnector available")
        // y la siguiente petición, en otro worker, funciona: un reintento.
        if (! $result['ok'] && str_contains((string) $result['error'], 'Lost connection')) {
            $result = $this->managerGetOnce($path, $query);
        }

        return $result;
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array{ok:bool, status:int, data:mixed, error:?string}
     */
    private function managerGetOnce(string $path, array $query = []): array
    {
        $base = rtrim((string) config('helpdeskErp.manager_url', ''), '/');
        if ($base === '') {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'El manager del ERP no está configurado (ERP_MANAGER_URL).'];
        }

        try {
            $resp = $this->managerRequest()->get($base.'/api/'.$path, $query);
        } catch (\Throwable $e) {
            Log::warning('HelpdeskPrestashop erpbridge: sin conexión con el manager del ERP.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'No se pudo conectar con el manager del ERP.'];
        }

        $json = $resp->json();
        $json = is_array($json) ? $json : [];

        // El manager contesta algunos fallos de Oracle con 200 y success=false
        // (p. ej. /invoices sin GRANT): se mira el cuerpo, no solo el código.
        if (! $resp->successful() || ($json['success'] ?? false) !== true) {
            $error = is_string($json['error'] ?? null) && $json['error'] !== ''
                ? $json['error']
                : 'El manager del ERP respondió con el código '.$resp->status().'.';

            return ['ok' => false, 'status' => $resp->status(), 'data' => null, 'error' => $error];
        }

        return ['ok' => true, 'status' => $resp->status(), 'data' => $json['data'] ?? null, 'error' => null, 'exists' => $json['exists'] ?? null];
    }

    private function managerRequest(): PendingRequest
    {
        $request = Http::timeout((int) config('helpdeskprestashop.ext.erpbridge.manager_timeout', 12))
            ->connectTimeout(4)
            ->acceptJson();

        $token = (string) config('helpdeskErp.bridge_token', '');
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    /**
     * @return array{url:string, host:string}|null
     */
    private function gestionBase(): ?array
    {
        $configured = trim((string) config('helpdeskprestashop.ext.erpbridge.gestion_url', ''));

        if ($configured === '') {
            try {
                $configured = trim((string) Setting::get('erp_api_url', (string) env('ERP_URL', '')));
            } catch (\Throwable) {
                $configured = trim((string) env('ERP_URL', ''));
            }
        }

        $parts = $configured !== '' ? parse_url($configured) : false;
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $scheme = in_array($parts['scheme'] ?? 'http', ['http', 'https'], true) ? ($parts['scheme'] ?? 'http') : 'http';
        $port = isset($parts['port']) ? ':'.(int) $parts['port'] : '';

        return [
            'url' => $scheme.'://'.$parts['host'].$port.'/api-gestion/',
            'host' => (string) $parts['host'],
        ];
    }

    /**
     * <response><resource>…</resource>…</response> → lista de arrays. Los
     * elementos vacíos (<num></num>) llegan como [] y se dejan en null.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function parseXmlResources(string $body): ?array
    {
        if (trim($body) === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($body, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($xml === false) {
            return null;
        }

        $data = json_decode((string) json_encode($xml), true);
        if (! is_array($data) || ! isset($data['resource'])) {
            return [];
        }

        return array_map(fn ($r) => $this->nullEmpty($r), self::listOf($data['resource']));
    }

    /**
     * Un único hijo llega como array asociativo; varios, como lista.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listOf(mixed $node): array
    {
        if (! is_array($node) || $node === []) {
            return [];
        }

        if (array_is_list($node)) {
            return array_values(array_filter($node, 'is_array'));
        }

        return [$node];
    }

    private function nullEmpty(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if ($value === []) {
            return null;
        }

        return array_map(fn ($v) => $this->nullEmpty($v), $value);
    }
}
