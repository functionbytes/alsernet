<?php

namespace Modules\HelpdeskErp\Services\ErpCross;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;

/**
 * Lectura mínima de la API REST de Gestión (InterGes, XML) para cruzar
 * pedidos de Gestión con pedidos de la tienda. SOLO GET.
 *
 * `pedido-cliente/` solo acepta dos filtros (probado contra producción; el
 * resto — idpedidocli, id, npedidocli, numero, cliente… — responde 501
 * "Not Implemented"):
 *   ?idcliente={IDCLIENTE}              todos los pedidos del cliente
 *   ?identificadororigen={id_order PS}  el pedido que vino de la tienda
 * Cada recurso trae idpedidocli (el id "central" del manager es "10" delante),
 * npedidocli, fpedido, total_con_impuestos, identificadororigen (id_order de
 * PrestaShop, lo manda AlvarezERP al pasar el pedido a Gestión) y
 * cliente.idcliente.
 *
 * Devuelve siempre ['ok' => bool, 'data' => list<array>, 'error' => ?string].
 */
class ErpCrossGestionClient
{
    private const CACHE_PREFIX = 'helpdeskerp:cross:gestion:';

    /**
     * Pedidos del cliente de Gestión, reducidos a lo que hace falta para el
     * cruce: idpedidocli => {order_id, number, date, total, origin_id, customer_id}.
     *
     * @return array{ok: bool, data: array<string, array<string, mixed>>, error: ?string}
     */
    public function ordersByCustomer(int $erpId, bool $fresh = false): array
    {
        $key = self::CACHE_PREFIX.'customer:'.$erpId;

        if (! $fresh && is_array($cached = Cache::get($key))) {
            return $cached;
        }

        $resp = $this->get('pedido-cliente/', ['idcliente' => $erpId]);

        if (! $resp['ok']) {
            return ['ok' => false, 'data' => [], 'error' => $resp['error']];
        }

        $map = [];
        foreach ($resp['data'] as $row) {
            $slim = $this->slim($row);
            if ($slim['order_id'] !== '' && $slim['customer_id'] === (string) $erpId) {
                $map[$slim['order_id']] = $slim;
            }
        }

        $out = ['ok' => true, 'data' => $map, 'error' => null];
        $ttl = (int) config('helpdeskErp.ext.cross.gestion_cache_ttl', 600);
        if ($ttl > 0) {
            Cache::put($key, $out, $ttl);
        }

        return $out;
    }

    /**
     * Pedidos de Gestión con ese identificador de origen (id_order de PrestaShop).
     *
     * @return array{ok: bool, data: list<array<string, mixed>>, error: ?string}
     */
    public function ordersByOrigin(int $psOrderId): array
    {
        $resp = $this->get('pedido-cliente/', ['identificadororigen' => $psOrderId]);

        if (! $resp['ok']) {
            return ['ok' => false, 'data' => [], 'error' => $resp['error']];
        }

        return ['ok' => true, 'data' => array_map(fn (array $r): array => $this->slim($r), $resp['data']), 'error' => null];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{order_id: string, number: ?string, date: ?string, total: ?float, origin_id: ?int, customer_id: string}
     */
    private function slim(array $row): array
    {
        $origin = $row['identificadororigen'] ?? null;
        $origin = is_scalar($origin) && ctype_digit(trim((string) $origin)) && (int) $origin > 0 ? (int) $origin : null;

        return [
            'order_id' => is_scalar($row['idpedidocli'] ?? null) ? trim((string) $row['idpedidocli']) : '',
            'number' => is_scalar($row['npedidocli'] ?? null) ? (string) $row['npedidocli'] : null,
            'date' => is_scalar($row['fpedido'] ?? null) ? (string) $row['fpedido'] : null,
            'total' => is_numeric($row['total_con_impuestos'] ?? null) ? (float) $row['total_con_impuestos'] : null,
            'origin_id' => $origin,
            'customer_id' => is_scalar($row['cliente']['idcliente'] ?? null) ? trim((string) $row['cliente']['idcliente']) : '',
        ];
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array{ok: bool, data: list<array<string, mixed>>, error: ?string}
     */
    private function get(string $path, array $query): array
    {
        $base = $this->base();
        if ($base === null) {
            return ['ok' => false, 'data' => [], 'error' => 'La API de Gestión no está configurada.'];
        }

        try {
            $resp = Http::timeout((int) config('helpdeskErp.ext.cross.gestion_timeout', 8))
                ->connectTimeout(4)
                ->accept('application/xml')
                // Gestión enruta por Host (vhost por nombre): con "host:puerto"
                // contesta un vhost por defecto con 200 vacíos.
                ->withHeaders(['Host' => $base['host']])
                ->get($base['url'].$path, $query);
        } catch (\Throwable $e) {
            Log::info('HelpdeskErp cross: sin conexión con la API de Gestión.', ['path' => $path, 'error' => $e->getMessage()]);

            return ['ok' => false, 'data' => [], 'error' => 'No se pudo conectar con la API de Gestión.'];
        }

        if ($resp->status() === 404) {
            return ['ok' => true, 'data' => [], 'error' => null];
        }

        if (! $resp->successful()) {
            return ['ok' => false, 'data' => [], 'error' => 'La API de Gestión respondió con el código '.$resp->status().'.'];
        }

        $rows = $this->parse((string) $resp->body());
        if ($rows === null) {
            return ['ok' => false, 'data' => [], 'error' => 'La API de Gestión devolvió una respuesta que no es XML.'];
        }

        return ['ok' => true, 'data' => $rows, 'error' => null];
    }

    /**
     * @return array{url: string, host: string}|null
     */
    private function base(): ?array
    {
        $configured = trim((string) config('helpdeskErp.ext.cross.gestion_url', ''));

        if ($configured === '') {
            $configured = trim((string) config('helpdeskprestashop.ext.erpbridge.gestion_url', ''));
        }

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

        return ['url' => $scheme.'://'.$parts['host'].$port.'/api-gestion/', 'host' => (string) $parts['host']];
    }

    /**
     * <response><resource>…</resource>…</response> → lista de arrays.
     *
     * @return list<array<string, mixed>>|null
     */
    private function parse(string $body): ?array
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
        if (! is_array($data) || ! isset($data['resource']) || ! is_array($data['resource'])) {
            return [];
        }

        $node = $data['resource'];

        return array_is_list($node) ? array_values(array_filter($node, 'is_array')) : [$node];
    }
}
