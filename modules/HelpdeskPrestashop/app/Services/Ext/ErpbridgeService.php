<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Tarjeta "En Gestión (ERP)" del workspace de pedido: para un pedido de
 * PrestaShop, su pedido en Gestión y la factura fiscal si Gestión la expone.
 *
 * Cadena de propiedad (fail-closed en cada eslabón):
 *   1. El pedido PS es de este cliente: order.detail del bridge con lookup
 *      de email/external_id (si no, null → 404).
 *   2. El pedido de Gestión se busca por identificadororigen = id_order PS.
 *   3. Su cliente de Gestión (cliente.idcliente) tiene que ser el cliente
 *      ERP de este cliente: o el vínculo erp ya guardado en el helpdesk, o el
 *      cliente de Gestión cuyo CODIGO_INTERNET es el id_customer PS. Si no
 *      coincide, no se enseña nada del pedido de Gestión.
 *
 * El albarán no tiene en la API ningún campo que lo ate al pedido: se toma
 * el del mismo cliente creado a la misma hora a la que Gestión pasó el
 * pedido a "Servido" (pedido-cliente-hist), y se confirma por artículos
 * cuando el manager deja leer sus líneas. La respuesta dice cuál de las dos
 * comprobaciones se hizo.
 *
 * Nada de esto escribe en ningún sitio.
 */
class ErpbridgeService
{
    public function __construct(
        private readonly PrestashopContextService $ps,
        private readonly ErpbridgeGestionClient $gestion,
    ) {}

    /**
     * @return array<string, mixed>|null null = el pedido no existe o no es de este cliente
     *
     * @throws PsUpstreamException
     */
    public function forOrder(Customer $customer, int $orderId): ?array
    {
        $email = trim((string) $customer->email) !== '' ? (string) $customer->email : null;
        $externalId = $customer->externalIdFor('prestashop');
        $externalId = $externalId !== null ? (int) $externalId : null;

        // 1. Propiedad en PrestaShop (el bridge verifica el lookup).
        $psOrder = $this->ps->getOrderDetail($orderId, $email, $externalId);
        if (! is_array($psOrder) || (int) ($psOrder['id'] ?? 0) !== $orderId) {
            return null;
        }

        $ttl = (int) config('helpdeskprestashop.ext.erpbridge.cache_ttl', 120);
        $key = 'hdps:erpbridge:'.$customer->id.':'.$orderId;

        if ($ttl > 0 && is_array($cached = Cache::get($key))) {
            return $cached;
        }

        $result = $this->build($customer, $psOrder);

        // Solo se cachea lo estable: una caída o un "aún no está" tienen que
        // poder resolverse en la siguiente apertura.
        if ($ttl > 0 && $result['status'] === 'found') {
            Cache::put($key, $result, $ttl);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $psOrder
     * @return array<string, mixed>
     */
    private function build(Customer $customer, array $psOrder): array
    {
        $orderId = (int) $psOrder['id'];
        $base = [
            'ps' => [
                'id' => $orderId,
                'reference' => (string) ($psOrder['reference'] ?? ''),
                'date' => $psOrder['created_at'] ?? null,
                'total' => $psOrder['totals']['total'] ?? null,
                'state_name' => $psOrder['state_name'] ?? null,
            ],
            'status' => 'error',
            'message' => null,
            'erp' => null,
            'delivery_note' => null,
            'invoice' => null,
        ];

        if (function_exists('helpdesk_erp_enabled') && ! helpdesk_erp_enabled()) {
            return array_merge($base, ['status' => 'disabled', 'message' => 'La integración con Gestión está desactivada.']);
        }

        // 2. Pedido de Gestión por identificador de origen.
        $lookup = $this->gestion->orderByOrigin($orderId);
        if (! $lookup['ok']) {
            return array_merge($base, ['status' => 'error', 'message' => $lookup['error'] ?? 'No se pudo consultar Gestión.']);
        }
        if ($lookup['data'] === []) {
            return array_merge($base, [
                'status' => 'not_found',
                'message' => 'Gestión no tiene ningún pedido con identificador de origen '.$orderId.'. Si el pedido acaba de entrar, puede que aún no se haya pasado al ERP.',
            ]);
        }

        // 3. El cliente de Gestión tiene que ser el de este cliente.
        $allowed = $this->allowedErpCustomerIds($customer, (int) ($psOrder['customer_id'] ?? 0));
        if ($allowed['ids'] === []) {
            return array_merge($base, [
                'status' => 'unverified',
                'message' => 'No se pudo identificar al cliente en Gestión para comprobar que el pedido es suyo'.($allowed['error'] ? ' ('.$allowed['error'].')' : '').'. No se muestran sus datos.',
            ]);
        }

        $erpOrder = null;
        foreach ($lookup['data'] as $candidate) {
            $cid = (int) ($candidate['cliente']['idcliente'] ?? 0);
            if ($cid > 0 && in_array($cid, $allowed['ids'], true)) {
                $erpOrder = $candidate;
                break;
            }
        }

        if ($erpOrder === null) {
            return array_merge($base, [
                'status' => 'mismatch',
                'message' => 'El pedido de Gestión con ese identificador de origen es de otro cliente de Gestión. No se muestran sus datos.',
            ]);
        }

        $erpCustomerId = (int) $erpOrder['cliente']['idcliente'];
        $erp = $this->normalizeOrder($erpOrder);

        // Historial y seguimiento: complementos, su fallo no tumba la tarjeta.
        $hist = $this->gestion->orderHistoryByOrigin($orderId);
        $erp['history'] = $hist['ok'] ? $this->normalizeHistory($hist['data'], $erp['id']) : [];
        $erp['history_error'] = $hist['ok'] ? null : $hist['error'];

        $track = $this->gestion->orderTrackingByOrigin($orderId);
        $erp['tracking'] = $track['ok'] ? $this->normalizeTracking($track['data'], $erp['id']) : [];

        $erp = array_merge($erp, $this->managerHeader($erpCustomerId, $erp['id']));

        $servedAt = $this->servedAt($erp['history']);
        if ($erp['served_date'] === null && $servedAt !== null) {
            $erp['served_date'] = $servedAt;
        }

        [$note, $noteMessage] = $this->deliveryNote($erpCustomerId, $erp, $servedAt);

        return array_merge($base, [
            'status' => 'found',
            'message' => null,
            'erp' => $erp,
            'delivery_note' => $note,
            'delivery_note_message' => $noteMessage,
            'invoice' => $this->invoice($erpCustomerId, $erp, $note),
        ]);
    }

    /**
     * Ids de cliente de Gestión que valen para este cliente del helpdesk.
     *
     * @return array{ids: array<int, int>, error: ?string}
     */
    private function allowedErpCustomerIds(Customer $customer, int $psCustomerId): array
    {
        $ids = [];
        $error = null;

        $linked = $customer->externalIdFor('erp');
        if ($linked !== null && ctype_digit((string) $linked)) {
            $ids[] = (int) $linked;
        }

        if ($psCustomerId > 0) {
            $byWeb = $this->gestion->customerByWebId($psCustomerId);
            $data = is_array($byWeb['data'] ?? null) ? $byWeb['data'] : [];

            // Solo la coincidencia por CODIGO_INTERNET: el endpoint cae a email
            // o id si se le pasan, y aquí no se le pasan.
            if ($byWeb['ok'] && (string) ($data['code_internet'] ?? '') === (string) $psCustomerId && ctype_digit((string) ($data['id'] ?? ''))) {
                $ids[] = (int) $data['id'];
            } elseif (! $byWeb['ok']) {
                $error = $byWeb['error'];
            }
        }

        return ['ids' => array_values(array_unique($ids)), 'error' => $error];
    }

    /**
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    private function normalizeOrder(array $o): array
    {
        $stateId = isset($o['estado']['idestado']) && is_numeric($o['estado']['idestado']) ? (int) $o['estado']['idestado'] : null;

        $lines = array_map(fn (array $l) => [
            'code' => $l['articulo']['codigo'] ?? null,
            'description' => $l['articulo']['descripcion'] ?? null,
            'article_id' => $l['articulo']['idarticulo'] ?? null,
            'units' => is_numeric($l['unidades'] ?? null) ? (float) $l['unidades'] : null,
            'total' => is_numeric($l['total_con_impuestos'] ?? null) ? (float) $l['total_con_impuestos'] : null,
        ], ErpbridgeGestionClient::listOf($o['lineas_pedido_cliente']['resource'] ?? null));

        return [
            'id' => (string) ($o['idpedidocli'] ?? ''),
            'number' => $o['npedidocli'] ?? null,
            'series' => $o['serie']['descripcorta'] ?? null,
            'date' => $o['fpedido'] ?? null,
            'total' => is_numeric($o['total_con_impuestos'] ?? null) ? (float) $o['total_con_impuestos'] : null,
            'shipping_cost' => is_numeric($o['envio']['coste'] ?? null) ? (float) $o['envio']['coste'] : null,
            'state' => [
                'id' => $stateId,
                // La descripción la da Gestión; el catálogo solo si no viene.
                'name' => $o['estado']['descripcion'] ?? $this->stateName($stateId),
            ],
            'warehouse' => $o['almacen']['descripcion'] ?? null,
            'has_incident' => ErpbridgeGestionClient::listOf($o['incidencia_pedido_cliente']['resource'] ?? null) !== [],
            'lines' => $lines,
            'expected_date' => null,
            'served_date' => null,
            'origin' => null,
            'invoiced' => null,
            'requested_invoice' => null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{state:int|null, name:string, date:?string}>
     */
    private function normalizeHistory(array $rows, string $erpOrderId): array
    {
        $out = [];
        foreach ($rows as $r) {
            if ((string) ($r['idpedidocli'] ?? '') !== $erpOrderId) {
                continue;
            }
            $state = is_numeric($r['estado'] ?? null) ? (int) $r['estado'] : null;
            $out[] = ['state' => $state, 'name' => $this->stateName($state), 'date' => $r['fecha'] ?? null];
        }

        usort($out, fn ($a, $b) => strcmp((string) $a['date'], (string) $b['date']));

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{number:?string, date:?string, url:?string}>
     */
    private function normalizeTracking(array $rows, string $erpOrderId): array
    {
        $out = [];
        foreach ($rows as $r) {
            if ((string) ($r['idpedidocli'] ?? '') !== $erpOrderId || empty($r['codtracking'])) {
                continue;
            }
            $url = (string) ($r['url_tracking'] ?? '');
            $out[] = [
                'number' => (string) $r['codtracking'],
                'date' => $r['fenvio'] ?? null,
                'url' => preg_match('#^https?://#i', $url) ? $url : null,
            ];
        }

        return $out;
    }

    /**
     * Fechas, origen y marca de facturado desde el manager. La API REST no
     * da el id "central" del pedido; en PEDIDOCLI_CENTRAL es "10" delante
     * del idpedidocli (10102183373 ↔ 102183373) y aquí solo se acepta la
     * respuesta si su order_id es el idpedidocli pedido.
     *
     * @return array<string, mixed>
     */
    private function managerHeader(int $erpCustomerId, string $erpOrderId): array
    {
        if (! ctype_digit($erpOrderId)) {
            return [];
        }

        $detail = $this->gestion->managerOrderDetail($erpCustomerId, '10'.$erpOrderId);
        $d = is_array($detail['data'] ?? null) ? $detail['data'] : null;

        if (! $detail['ok'] || $d === null || (string) ($d['order_id'] ?? '') !== $erpOrderId) {
            return [];
        }

        return [
            'central_id' => (string) ($d['id'] ?? ''),
            'expected_date' => $d['expected_date'] ?? null,
            'served_date' => $d['served_date'] ?? null,
            'origin' => $d['origin']['description'] ?? null,
            'invoiced' => isset($d['invoiced']) ? (bool) $d['invoiced'] : null,
            'requested_invoice' => isset($d['requested_invoice']) ? (bool) $d['requested_invoice'] : null,
            'date' => $d['date'] ?? null,
        ];
    }

    /**
     * @param  array<int, array{state:int|null, name:string, date:?string}>  $history
     */
    private function servedAt(array $history): ?string
    {
        $served = (array) config('helpdeskprestashop.ext.erpbridge.served_states', [7, 10]);
        $at = null;
        foreach ($history as $h) {
            if (in_array($h['state'], $served, true) && $h['date']) {
                $at = $h['date'];
            }
        }

        return $at;
    }

    /**
     * @param  array<string, mixed>  $erp
     * @return array{0: array<string, mixed>|null, 1: ?string}
     */
    private function deliveryNote(int $erpCustomerId, array $erp, ?string $servedAt): array
    {
        if ($servedAt === null) {
            return [null, in_array($erp['state']['id'], (array) config('helpdeskprestashop.ext.erpbridge.served_states', [7, 10]), true)
                ? 'Gestión no da la hora a la que se sirvió: no se puede localizar el albarán.'
                : 'El pedido aún no se ha servido en Gestión: todavía no hay albarán.'];
        }

        $list = $this->gestion->deliveryNotes($erpCustomerId, 10);
        if (! $list['ok']) {
            return [null, 'No se pudieron leer los albaranes de Gestión: '.$list['error']];
        }

        $window = (int) config('helpdeskprestashop.ext.erpbridge.delivery_note_match_seconds', 180);
        try {
            $served = Carbon::parse($servedAt);
        } catch (\Throwable) {
            return [null, 'Gestión devolvió una fecha de servido que no se puede leer.'];
        }

        $candidates = array_values(array_filter((array) $list['data'], function ($n) use ($served, $window) {
            if (! is_array($n) || empty($n['created'])) {
                return false;
            }
            try {
                return abs(Carbon::parse($n['created'])->diffInSeconds($served, false)) <= $window;
            } catch (\Throwable) {
                return false;
            }
        }));

        if (count($candidates) !== 1) {
            return [null, $candidates === []
                ? 'No hay en Gestión un albarán de este cliente creado al servirse el pedido.'
                : 'Hay varios albaranes creados a esa hora: no se puede saber cuál es el de este pedido.'];
        }

        $n = $candidates[0];
        $note = [
            'id' => (string) ($n['id'] ?? ''),
            'number' => $n['number'] ?? null,
            'date' => $n['date'] ?? null,
            'created' => $n['created'] ?? null,
            'invoice_id' => isset($n['invoice_id']) && $n['invoice_id'] !== '' ? (string) $n['invoice_id'] : null,
            'lines' => [],
            'lines_error' => null,
            'matched_by' => 'served_time',
        ];

        // Confirmación por artículos cuando el manager deja leer las líneas.
        $detail = $this->gestion->deliveryNoteDetail($erpCustomerId, $note['id']);
        if ($detail['ok'] && is_array($detail['data'])) {
            $lines = array_map(fn ($l) => [
                'code' => $l['article']['code'] ?? null,
                'description' => $l['article']['description'] ?? null,
                'article_id' => isset($l['article']['id']) ? (string) $l['article']['id'] : null,
                'units' => isset($l['units']) ? (float) $l['units'] : null,
                'total' => $l['total_with_taxes'] ?? null,
            ], array_filter((array) ($detail['data']['lines'] ?? []), 'is_array'));

            $orderArticles = array_filter(array_map(fn ($l) => (string) ($l['article_id'] ?? ''), $erp['lines']));
            $noteArticles = array_filter(array_map(fn ($l) => (string) ($l['article_id'] ?? ''), $lines));

            if ($orderArticles !== [] && $noteArticles !== [] && array_intersect($orderArticles, $noteArticles) === []) {
                return [null, 'El albarán creado a esa hora no lleva ningún artículo de este pedido: no se asocia.'];
            }

            $note['lines'] = array_values($lines);
            $note['matched_by'] = 'served_time_and_articles';
        } else {
            $note['lines_error'] = $detail['error'];
        }

        return [$note, null];
    }

    /**
     * Factura fiscal de Gestión: la del albarán (ALBARANCLI.IDFACTURACLI),
     * leída de FACTURACLI_CENTRAL. El PDF no lo expone ninguna de las dos
     * APIs (ni el manager ni la API REST de Gestión tienen un documento de
     * factura): pdf_available siempre false mientras no exista.
     *
     * @param  array<string, mixed>  $erp
     * @param  array<string, mixed>|null  $note
     * @return array<string, mixed>
     */
    private function invoice(int $erpCustomerId, array $erp, ?array $note): array
    {
        $out = [
            'status' => 'none',
            'id' => null,
            'number' => null,
            'date' => null,
            'amount' => null,
            'message' => null,
            'marked_invoiced' => $erp['invoiced'] ?? null,
            'pdf_available' => false,
            'pdf_message' => 'Gestión no expone el PDF de sus facturas: ni el manager ni la API REST de Gestión tienen un endpoint de documento de factura.',
        ];

        $invoiceId = $note['invoice_id'] ?? null;

        if ($invoiceId === null) {
            // Sin factura en el albarán; se comprueba además si la tabla de
            // facturas es legible, porque si no lo es "no hay factura" no se
            // puede afirmar.
            $probe = $this->gestion->invoicesProbe($erpCustomerId);
            $out['message'] = $note === null
                ? 'Sin albarán localizado no hay factura de Gestión que asociar al pedido.'
                : 'El albarán de este pedido no tiene factura asociada en Gestión.';

            if (! $probe['ok']) {
                $out['status'] = 'denied';
                $out['message'] .= ' Además, las facturas de Gestión no se pueden consultar: '.$probe['error'];
            }

            return $out;
        }

        $out['id'] = $invoiceId;
        $detail = $this->gestion->invoiceDetail($erpCustomerId, $invoiceId);

        if (! $detail['ok'] || ! is_array($detail['data'])) {
            $out['status'] = $detail['status'] === 404 ? 'none' : 'denied';
            $out['message'] = 'El albarán tiene la factura interna '.$invoiceId.' en Gestión, pero no se puede leer: '.$detail['error'];

            return $out;
        }

        $d = $detail['data'];
        $number = trim(implode('/', array_filter([
            $d['series'] ?? null,
            $d['number'] ?? null,
        ], fn ($v) => $v !== null && $v !== '')));

        $out['status'] = 'found';
        $out['number'] = $number !== '' ? $number : (string) ($d['number'] ?? $invoiceId);
        $out['year'] = $d['year'] ?? null;
        $out['date'] = $d['date'] ?? null;
        $out['amount'] = isset($d['totals']['lines_total_with_taxes']) ? (float) $d['totals']['lines_total_with_taxes'] : null;
        $out['simplified'] = (bool) ($d['simplified'] ?? false);

        return $out;
    }

    private function stateName(?int $state): string
    {
        $states = (array) config('helpdeskprestashop.ext.erpbridge.states', []);

        return $state !== null && isset($states[$state]) ? (string) $states[$state] : 'Estado '.($state ?? '?');
    }
}
