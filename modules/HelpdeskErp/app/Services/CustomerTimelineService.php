<?php

namespace Modules\HelpdeskErp\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatSections;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatService;
use Modules\HelpdeskErp\Services\ErpCross\ErpCrossShopMatcher;
use Nwidart\Modules\Facades\Module;

/**
 * Línea de tiempo del cliente.
 *
 * - getTimeline($email): la API antigua (/api/helpdeskErp/customers/{email}/
 *   timeline), a partir del contexto ERP por email.
 * - forCustomer($customer, …): la de la ficha de Gestión en el chat (pane
 *   "Actividad"). Junta, del cliente del helpdesk: pedidos, albaranes,
 *   devoluciones/abonos, facturas y movimientos de puntos de Gestión
 *   (ErpChatService, respetando bloqueado/cargando/caído y los permisos de
 *   cada sección), pedidos y carritos de la tienda y sus conversaciones.
 *   Cada fuente informa de su estado en `sources`.
 */
class CustomerTimelineService
{
    /** Tipos de evento de forCustomer(), en el orden de los filtros. */
    public const CHAT_TYPES = [
        'erp_order', 'erp_delivery_note', 'erp_return', 'erp_invoice', 'erp_points',
        'ps_order', 'ps_cart', 'conversation',
    ];

    private const PS_SERVICE = 'Modules\\HelpdeskPrestashop\\Services\\PrestashopContextService';

    public function __construct(
        private readonly ErpContextService $erpService,
        private readonly ?ErpChatService $chat = null,
    ) {}

    /**
     * Returns chronological events for a customer (ERP + PS + Helpdesk).
     *
     * @return array<int, array{type: string, source: string, date: string, title: string, data: array<string, mixed>}>
     */
    public function getTimeline(string $email, int $limit = 50): array
    {
        return array_slice($this->collectEvents($email), 0, $limit);
    }

    public function forgetCache(string $email): void
    {
        Cache::forget($this->cacheKey($email));
    }

    /**
     * Aggregates and caches the full (unsliced) event list for a customer.
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectEvents(string $email): array
    {
        $ttl = (int) config('helpdeskErp.timeline_cache_ttl', 120);

        if ($ttl <= 0) {
            return $this->buildEvents($email);
        }

        return Cache::remember($this->cacheKey($email), $ttl, fn () => $this->buildEvents($email));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildEvents(string $email): array
    {
        $erp = $this->erpService->getCustomerContext($email);

        $events = array_merge(
            $this->erpOrderEvents($erp),
            $this->erpInvoiceEvents($erp),
            $this->psEvents($email),
            $this->collectHelpdeskEvents($email),
        );

        usort($events, fn ($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return $events;
    }

    private function cacheKey(string $email): string
    {
        return 'erp_timeline_'.md5(strtolower(trim($email)));
    }

    /**
     * Resolve PS events defensively — only if the module is available and loaded.
     *
     * @return array<int, array<string, mixed>>
     */
    private function psEvents(string $email): array
    {
        $psClass = 'Modules\HelpdeskPrestashop\Services\PrestashopContextService';

        if (! Module::has('HelpdeskPrestashop') || ! class_exists($psClass)) {
            return [];
        }

        try {
            /** @var object $psService */
            $psService = app($psClass);
            $ps = $psService->getCustomerContext($email);

            return array_merge(
                $this->psOrderEvents($ps),
                $this->psCartEvents($ps),
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $erp
     * @return array<int, array<string, mixed>>
     */
    private function erpOrderEvents(array $erp): array
    {
        $events = [];

        foreach (($erp['orders'] ?? []) as $o) {
            if (empty($o['date'])) {
                continue;
            }

            $events[] = [
                'type' => 'erp_order',
                'source' => 'erp',
                'date' => (string) $o['date'],
                'title' => 'Pedido ERP '.($o['number'] ?? '#'),
                'data' => $o,
            ];
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $erp
     * @return array<int, array<string, mixed>>
     */
    private function erpInvoiceEvents(array $erp): array
    {
        $events = [];

        foreach (($erp['invoices'] ?? []) as $i) {
            if (empty($i['date'])) {
                continue;
            }

            $events[] = [
                'type' => 'erp_invoice',
                'source' => 'erp',
                'date' => (string) $i['date'],
                'title' => 'Factura '.($i['number'] ?? '#'),
                'data' => $i,
            ];
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $ps
     * @return array<int, array<string, mixed>>
     */
    private function psOrderEvents(array $ps): array
    {
        $events = [];

        foreach (($ps['orders'] ?? []) as $o) {
            $date = $o['placed_at'] ?? $o['date_add'] ?? null;

            if (! $date) {
                continue;
            }

            $events[] = [
                'type' => 'ps_order',
                'source' => 'prestashop',
                'date' => (string) $date,
                'title' => 'Pedido PS '.($o['reference'] ?? ''),
                'data' => $o,
            ];
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $ps
     * @return array<int, array<string, mixed>>
     */
    private function psCartEvents(array $ps): array
    {
        $events = [];

        foreach (($ps['carts'] ?? []) as $c) {
            $date = $c['updated_at'] ?? $c['date_upd'] ?? null;

            if (! $date) {
                continue;
            }

            $events[] = [
                'type' => 'ps_cart',
                'source' => 'prestashop',
                'date' => (string) $date,
                'title' => 'Carrito abandonado',
                'data' => $c,
            ];
        }

        return $events;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectHelpdeskEvents(string $email): array
    {
        $events = [];

        try {
            if (! Schema::connection('helpdesk')->hasTable('helpdesk_conversations')) {
                return $events;
            }

            $candidates = array_filter(
                ['email', 'from_email'],
                fn ($col) => Schema::connection('helpdesk')->hasColumn('helpdesk_conversations', $col)
            );

            if (empty($candidates)) {
                return $events;
            }

            // Columnas explícitas, igual que erpOrderEvents()/erpInvoiceEvents():
            // (array) $r sobre la fila completa filtraba a este JSON columnas
            // internas de helpdesk_conversations (asignación, tags, ids de
            // agente...) que no pertenecen a la ficha de cliente del ERP.
            // Se filtra por hasColumn() porque el set exacto de columnas
            // varía entre despliegues.
            $safeColumns = array_values(array_filter(
                ['id', 'subject', 'title', 'status_id', 'priority', 'created_at', 'last_message_at', 'closed_at'],
                fn ($col) => Schema::connection('helpdesk')->hasColumn('helpdesk_conversations', $col)
            ));

            $rows = DB::connection('helpdesk')->table('helpdesk_conversations')
                ->select($safeColumns)
                ->where(function ($q) use ($email, $candidates) {
                    foreach ($candidates as $col) {
                        $q->orWhere($col, $email);
                    }
                })
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            foreach ($rows as $r) {
                $events[] = [
                    'type' => 'helpdesk_conversation',
                    'source' => 'helpdesk',
                    'date' => (string) ($r->created_at ?? ''),
                    'title' => 'Conversación: '.substr((string) ($r->subject ?? $r->title ?? 'Sin asunto'), 0, 80),
                    'data' => (array) $r,
                ];
            }
        } catch (\Throwable) {
            // best effort — helpdesk tables may not exist
        }

        return $events;
    }

    /* ══ Línea de tiempo del chat (pane "Actividad" de la ficha ERP) ═════ */

    /**
     * @param  int|null  $erpId  IDCLIENTE de Gestión (null = sin vínculo)
     * @param  callable(string): bool  $can  permisos del agente
     * @param  callable(Conversation): bool|null  $canViewConversation
     * @return array{items: list<array<string, mixed>>, sources: array<string, array<string, mixed>>, counts: array<string, int>, erp_id: ?int, truncated: bool}
     */
    public function forCustomer(Customer $customer, ?int $erpId, callable $can, ?callable $canViewConversation = null, bool $fresh = false, int $limit = 200): array
    {
        $items = [];
        $sources = [];

        [$erpItems, $erpSources] = $this->chatErpItems($erpId, $can, $fresh);
        $items = array_merge($items, $erpItems);
        $sources += $erpSources;

        [$psItems, $psSources] = $this->chatPsItems($customer, $erpId, $can, $fresh);
        $items = array_merge($items, $psItems);
        $sources += $psSources;

        [$convItems, $convSource] = $this->chatConversationItems($customer, $canViewConversation);
        $items = array_merge($items, $convItems);
        $sources['conversations'] = $convSource;

        usort($items, function (array $a, array $b): int {
            $cmp = strcmp((string) $b['date'], (string) $a['date']);

            return $cmp !== 0 ? $cmp : strcmp((string) $a['id'], (string) $b['id']);
        });

        $counts = array_fill_keys(self::CHAT_TYPES, 0);
        foreach ($items as $item) {
            $counts[$item['type']] = ($counts[$item['type']] ?? 0) + 1;
        }

        $limit = max(1, $limit);

        return [
            'items' => array_slice($items, 0, $limit),
            'sources' => $sources,
            'counts' => $counts,
            'erp_id' => $erpId,
            'truncated' => count($items) > $limit,
        ];
    }

    /**
     * @param  callable(string): bool  $can
     * @return array{0: list<array<string, mixed>>, 1: array<string, array<string, mixed>>}
     */
    private function chatErpItems(?int $erpId, callable $can, bool $fresh): array
    {
        $canOrders = $can(ErpChatSections::PERM_ORDERS);
        $canFinance = $can(ErpChatSections::PERM_FINANCE);
        $canLoyalty = $can(ErpChatSections::PERM_LOYALTY);

        $plan = [
            'erp_orders' => [$canOrders, 'orders', ['limit' => 50]],
            'erp_delivery_notes' => [$canOrders || $canFinance, 'delivery-notes', ['limit' => 50]],
            'erp_returns' => [$canOrders || $canFinance, 'returns', ['limit' => 50]],
            'erp_invoices' => [$canFinance, 'invoices', ['limit' => 50]],
            'erp_points' => [$canLoyalty, 'loyalty-points', []],
        ];

        $sources = [];
        $requests = [];

        foreach ($plan as $key => [$allowed, $section, $params]) {
            if (! $allowed) {
                $sources[$key] = $this->source('forbidden', 'Sin permiso para ver esta sección de Gestión.', 'forbidden');
            } elseif ($erpId === null) {
                $sources[$key] = $this->source('unlinked', 'Este cliente no está vinculado con Gestión.', null);
            } elseif (function_exists('helpdesk_erp_enabled') && ! helpdesk_erp_enabled()) {
                $sources[$key] = $this->source('unavailable', 'La integración con Gestión está desactivada.', 'disabled');
            } else {
                $requests[$key] = [$section, $params];
            }
        }

        if ($requests === [] || $erpId === null) {
            return [[], $sources];
        }

        $results = ($this->chat ?? app(ErpChatService::class))->many($erpId, $requests, $fresh);

        $items = [];
        $noteIds = [];   // delivery_id (sin "10") → id central del albarán

        foreach ($results as $key => $r) {
            $state = (string) ($r['state'] ?? 'down');
            $sources[$key] = $this->source($state, $r['message'] ?? null, $r['reason'] ?? null, $r['retry_after'] ?? null);

            if ($state !== 'ok') {
                continue;
            }

            $rows = match ($key) {
                'erp_points' => is_array($r['data']['movements'] ?? null) ? $r['data']['movements'] : [],
                default => is_array($r['data'] ?? null) && array_is_list($r['data']) ? $r['data'] : [],
            };

            if ($key === 'erp_delivery_notes') {
                foreach ($rows as $n) {
                    if (is_array($n) && isset($n['delivery_id'], $n['id']) && is_scalar($n['delivery_id'])) {
                        $noteIds[(string) $n['delivery_id']] = (string) $n['id'];
                    }
                }
            }

            $sources[$key]['count'] = count($rows);
            $sources[$key]['has_more'] = (bool) ($r['pagination']['has_more'] ?? false);
            $results[$key]['_rows'] = $rows;
        }

        // Una devolución ES un albarán de devolución (mismo id central): se
        // pinta una sola vez, como devolución. Y el número de pedido para
        // el subtítulo de las devoluciones.
        $returnIds = [];
        foreach ($results['erp_returns']['_rows'] ?? [] as $row) {
            if (is_array($row) && ($rid = $this->scalarString($row['id'] ?? null)) !== null) {
                $returnIds[$rid] = true;
            }
        }
        $orderNumbers = [];
        foreach ($results['erp_orders']['_rows'] ?? [] as $row) {
            if (is_array($row) && ($oid = $this->scalarString($row['id'] ?? null)) !== null) {
                $orderNumbers[$oid] = $this->scalarString($row['number'] ?? null) ?? $oid;
            }
        }

        foreach (['erp_orders', 'erp_delivery_notes', 'erp_returns', 'erp_invoices', 'erp_points'] as $key) {
            foreach ($results[$key]['_rows'] ?? [] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ($key === 'erp_delivery_notes' && isset($returnIds[(string) $this->scalarString($row['id'] ?? null)])) {
                    continue;
                }
                $item = match ($key) {
                    'erp_orders' => $this->erpOrderItem($row),
                    'erp_delivery_notes' => $this->erpDeliveryNoteItem($row),
                    'erp_returns' => $this->erpReturnItem($row, $orderNumbers),
                    'erp_invoices' => $this->erpInvoiceItem($row),
                    'erp_points' => $this->erpPointsItem($row, $noteIds),
                };
                if ($item !== null) {
                    $items[] = $item;
                }
            }
        }

        return [$items, $sources];
    }

    /**
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>|null
     */
    private function erpOrderItem(array $o): ?array
    {
        $date = $this->isoDate($o['date'] ?? null);
        $id = $this->scalarString($o['id'] ?? null);
        if ($date === null || $id === null) {
            return null;
        }

        $number = $this->scalarString($o['number'] ?? null) ?? $id;
        $served = $this->isoDate($o['served_date'] ?? null);

        return $this->item('erp_order:'.$id, 'erp_order', 'erp', $date, 'Pedido '.$number, $served ? 'Servido el '.substr($served, 0, 10) : 'En Gestión', [
            'status' => $this->scalarString($o['status'] ?? null),
            'open' => ['kind' => 'erp_order', 'id' => $id],
            'meta' => array_filter([
                'Nº pedido' => $number,
                'Id central' => $id,
                'Fecha prevista' => $this->scalarString($o['expected_date'] ?? null),
                'Servido' => $served,
                'Observaciones' => $this->scalarString($o['observations'] ?? null),
            ], fn ($v) => $v !== null && $v !== ''),
        ]);
    }

    /**
     * @param  array<string, mixed>  $n
     * @return array<string, mixed>|null
     */
    private function erpDeliveryNoteItem(array $n): ?array
    {
        $date = $this->isoDate($n['created'] ?? null) ?? $this->isoDate($n['date'] ?? null);
        $id = $this->scalarString($n['id'] ?? null);
        if ($date === null || $id === null) {
            return null;
        }

        $number = $this->scalarString($n['number'] ?? null) ?? $id;
        $invoice = $this->scalarString($n['invoice_id'] ?? null);
        $points = is_numeric($n['loyalty_points'] ?? null) ? (int) $n['loyalty_points'] : null;

        return $this->item('erp_delivery_note:'.$id, 'erp_delivery_note', 'erp', $date, 'Albarán '.$number,
            $invoice ? 'Facturado' : (($n['status'] ?? true) === false ? 'Anulado' : 'Sin factura'), [
                'points' => $points ?: null,
                'open' => ['kind' => 'delivery_note', 'id' => $id, 'number' => $number],
            ]);
    }

    /**
     * @param  array<string, mixed>  $r
     * @param  array<string, string>  $orderNumbers  id central → número
     * @return array<string, mixed>|null
     */
    private function erpReturnItem(array $r, array $orderNumbers = []): ?array
    {
        $date = $this->isoDate($r['date'] ?? null);
        $id = $this->scalarString($r['id'] ?? null);
        if ($date === null || $id === null) {
            return null;
        }

        $isCredit = strtolower((string) ($r['kind'] ?? '')) === 'abono';
        $number = $this->scalarString($r['number'] ?? null) ?? $id;
        $note = $this->scalarString($r['delivery_note_id'] ?? null);
        $order = $this->scalarString($r['order_id'] ?? null);

        // Una devolución es un albarán de devolución: su propio id es el del
        // albarán (el delivery_note_id es el albarán de la VENTA original).
        $open = match (true) {
            ! $isCredit => ['kind' => 'delivery_note', 'id' => $id, 'number' => $number],
            $note !== null => ['kind' => 'delivery_note', 'id' => $note, 'number' => null],
            $order !== null => ['kind' => 'erp_order', 'id' => $order],
            default => ['kind' => 'info'],
        };

        return $this->item('erp_return:'.$id, 'erp_return', 'erp', $date, ($isCredit ? 'Abono ' : 'Devolución ').$number,
            $order ? 'Del pedido '.($orderNumbers[$order] ?? $order) : ($isCredit ? 'Abono en Gestión' : 'Devolución en Gestión'), [
                'order_id' => $order,
                'amount' => is_numeric($r['amount'] ?? null) ? (float) $r['amount'] : null,
                'open' => $open,
                'meta' => array_filter([
                    'Tipo' => $isCredit ? 'Abono' : 'Devolución',
                    'Número' => $number,
                    'Albarán de la venta' => $note,
                    'Factura' => $this->scalarString($r['invoice_id'] ?? null),
                    'Pedido' => $order,
                ], fn ($v) => $v !== null && $v !== ''),
            ]);
    }

    /**
     * @param  array<string, mixed>  $i
     * @return array<string, mixed>|null
     */
    private function erpInvoiceItem(array $i): ?array
    {
        $date = $this->isoDate($i['date'] ?? null);
        $id = $this->scalarString($i['id'] ?? null);
        if ($date === null || $id === null) {
            return null;
        }

        $ref = trim(implode('-', array_filter([$this->scalarString($i['series'] ?? null), $this->scalarString($i['number'] ?? null)])));

        return $this->item('erp_invoice:'.$id, 'erp_invoice', 'erp', $date, 'Factura '.($ref !== '' ? $ref : $id),
            ($i['simplified'] ?? false) ? 'Factura simplificada' : 'Factura', [
                'amount' => is_numeric($i['amount'] ?? ($i['total'] ?? null)) ? (float) ($i['amount'] ?? $i['total']) : null,
                'open' => ['kind' => 'invoice', 'id' => $id],
            ]);
    }

    /**
     * @param  array<string, mixed>  $m
     * @param  array<string, string>  $noteIds
     * @return array<string, mixed>|null
     */
    private function erpPointsItem(array $m, array $noteIds): ?array
    {
        $date = $this->isoDate($m['date'] ?? null);
        $id = $this->scalarString($m['id'] ?? null);
        if ($date === null || $id === null || ! is_numeric($m['points'] ?? null)) {
            return null;
        }

        $points = (int) $m['points'];
        $delivery = $this->scalarString($m['delivery_id'] ?? null);
        // El movimiento apunta al albarán por su id local (101961890); el id
        // central del albarán es "10" delante (10101961890).
        $noteCentral = $delivery !== null ? ($noteIds[$delivery] ?? (ctype_digit($delivery) ? '10'.$delivery : null)) : null;

        return $this->item('erp_points:'.$id, 'erp_points', 'erp', $date, ($points >= 0 ? 'Puntos ganados' : 'Puntos canjeados'),
            'Tarjeta '.($this->scalarString($m['card'] ?? null) ?? '—'), [
                'points' => $points,
                'open' => $noteCentral !== null ? ['kind' => 'delivery_note', 'id' => $noteCentral, 'number' => null] : ['kind' => 'info'],
                'meta' => array_filter([
                    'Puntos' => (string) $points,
                    'Tarjeta' => $this->scalarString($m['card'] ?? null),
                    'Liquidación' => $this->scalarString($m['liquidation'] ?? null),
                    'Albarán' => $noteCentral,
                ], fn ($v) => $v !== null && $v !== ''),
            ]);
    }

    /**
     * Pedidos y carritos de la tienda (solo si PrestaShop está activo y el
     * agente tiene sus permisos). Se cachea un rato corto por cliente.
     *
     * @param  callable(string): bool  $can
     * @return array{0: list<array<string, mixed>>, 1: array<string, array<string, mixed>>}
     */
    private function chatPsItems(Customer $customer, ?int $erpId, callable $can, bool $fresh): array
    {
        $canOrders = $can('helpdeskprestashop.orders.view');
        $canCarts = $can('helpdeskprestashop.view');

        if (! ErpCrossShopMatcher::prestashopAvailable()) {
            $off = $this->source('unavailable', 'La tienda (PrestaShop) no está activa.', 'prestashop_disabled');

            return [[], ['ps_orders' => $off, 'ps_carts' => $off]];
        }

        $sources = [
            'ps_orders' => $canOrders ? null : $this->source('forbidden', 'Sin permiso para ver pedidos de la tienda.', 'forbidden'),
            'ps_carts' => $canCarts ? null : $this->source('forbidden', 'Sin permiso para ver la tienda.', 'forbidden'),
        ];

        if (! $canOrders && ! $canCarts) {
            return [[], $sources];
        }

        $email = trim((string) $customer->email);
        $email = $email === '' || str_ends_with($email, '@anonymous.local') ? '' : $email;
        $psId = $this->psCustomerIdFor($customer, $erpId);

        if ($email === '' && $psId === null) {
            $none = $this->source('unlinked', 'El cliente no tiene cuenta en la tienda.', 'no_shop_customer');

            return [[], ['ps_orders' => $sources['ps_orders'] ?? $none, 'ps_carts' => $sources['ps_carts'] ?? $none]];
        }

        $key = 'helpdeskerp:cross:tl-ps:'.$customer->id.':'.($canOrders ? 'o' : '').($canCarts ? 'c' : '');
        $ttl = (int) config('helpdeskErp.ext.cross.timeline_ps_ttl', 120);

        if (! $fresh && $ttl > 0 && is_array($cached = Cache::get($key))) {
            return $cached;
        }

        $ps = app(self::PS_SERVICE);
        $items = [];
        $failed = false;

        if ($canOrders) {
            try {
                $list = $ps->getCustomerOrders($email, $psId, (int) config('helpdeskErp.ext.cross.timeline_ps_orders', 50));
                $orders = is_array($list['data'] ?? null) ? $list['data'] : (is_array($list['orders'] ?? null) ? $list['orders'] : []);
                foreach ($orders as $o) {
                    if (is_array($o) && ($item = $this->psOrderItem($o)) !== null) {
                        $items[] = $item;
                    }
                }
                $sources['ps_orders'] = $this->source('ok', null, null) + ['count' => count($orders), 'has_more' => (bool) ($list['pagination']['has_more'] ?? false)];
            } catch (\Throwable) {
                $failed = true;
                $sources['ps_orders'] = $this->source('down', 'La tienda no responde ahora mismo.', 'prestashop_down');
            }
        }

        if ($canCarts) {
            // Solo lo que la pestaña Tienda ya tiene en caché: leer el contexto
            // completo de la tienda en frío tarda ~30 s (medido), demasiado
            // para una línea de tiempo.
            try {
                $ctx = $email !== '' ? $ps->peekCachedContext($email) : null;
            } catch (\Throwable) {
                $ctx = null;
            }

            if (is_array($ctx)) {
                $carts = is_array($ctx['carts'] ?? null) ? $ctx['carts'] : [];
                foreach ($carts as $c) {
                    if (is_array($c) && ($item = $this->psCartItem($c)) !== null) {
                        $items[] = $item;
                    }
                }
                $sources['ps_carts'] = $this->source('ok', null, null) + ['count' => count($carts)];
            } else {
                $sources['ps_carts'] = $this->source('unavailable', 'Se ven al abrir la pestaña Tienda del cliente.', 'not_loaded');
            }
        }

        $out = [$items, array_filter($sources)];

        if (! $failed && $ttl > 0) {
            Cache::put($key, $out, $ttl);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>|null
     */
    private function psOrderItem(array $o): ?array
    {
        $date = $this->isoDate($o['created_at'] ?? ($o['placed_at'] ?? ($o['date_add'] ?? null)));
        $id = $this->scalarString($o['id'] ?? ($o['id_order'] ?? null));
        if ($date === null || $id === null) {
            return null;
        }

        $ref = $this->scalarString($o['reference'] ?? null);

        return $this->item('ps_order:'.$id, 'ps_order', 'prestashop', $date, 'Pedido tienda '.($ref ?? '#'.$id),
            $this->scalarString($o['payment'] ?? null) ?? 'Tienda online', [
                'amount' => is_numeric($o['total'] ?? ($o['total_paid'] ?? null)) ? (float) ($o['total'] ?? $o['total_paid']) : null,
                'status' => $this->scalarString($o['state_name'] ?? ($o['status'] ?? null)),
                'open' => ['kind' => 'ps_order', 'id' => $id],
            ]);
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<string, mixed>|null
     */
    private function psCartItem(array $c): ?array
    {
        $date = $this->isoDate($c['updated_at'] ?? ($c['date_upd'] ?? ($c['created_at'] ?? null)));
        $id = $this->scalarString($c['id'] ?? ($c['id_cart'] ?? null));
        if ($date === null || $id === null) {
            return null;
        }

        $products = is_array($c['products'] ?? null) ? count($c['products']) : (is_numeric($c['products_count'] ?? ($c['items_count'] ?? null)) ? (int) ($c['products_count'] ?? $c['items_count']) : null);
        $total = is_numeric($c['total'] ?? ($c['total_products'] ?? null)) ? (float) ($c['total'] ?? $c['total_products']) : null;

        return $this->item('ps_cart:'.$id, 'ps_cart', 'prestashop', $date, 'Carrito sin comprar',
            $products !== null ? $products.($products === 1 ? ' producto' : ' productos') : 'Carrito de la tienda', [
                'amount' => $total,
                'open' => ['kind' => 'info'],
                'meta' => array_filter([
                    'Carrito' => '#'.$id,
                    'Productos' => $products !== null ? (string) $products : null,
                    'Última actividad' => $date,
                ], fn ($v) => $v !== null && $v !== ''),
            ]);
    }

    /**
     * Conversaciones del cliente que el agente puede ver (ConversationPolicy).
     *
     * @param  callable(Conversation): bool|null  $canView
     * @return array{0: list<array<string, mixed>>, 1: array<string, mixed>}
     */
    private function chatConversationItems(Customer $customer, ?callable $canView): array
    {
        try {
            $rows = Conversation::query()
                ->where('customer_id', $customer->id)
                ->orderByDesc('created_at')
                ->limit((int) config('helpdeskErp.ext.cross.timeline_conversations', 30))
                ->get();
        } catch (\Throwable) {
            return [[], $this->source('down', 'No se pudieron leer las conversaciones.', 'server_error')];
        }

        $items = [];
        foreach ($rows as $conv) {
            if ($canView !== null && ! $canView($conv)) {
                continue;
            }

            $date = $this->isoDate($conv->created_at?->format('Y-m-d H:i:s'));
            if ($date === null) {
                continue;
            }

            $subject = trim((string) ($conv->subject ?? ''));
            $url = null;
            try {
                $url = route('manager.helpdesk.conversations.show', $conv->id, false);
            } catch (\Throwable) {
                $url = null;
            }

            $items[] = $this->item('conversation:'.$conv->id, 'conversation', 'helpdesk', $date,
                $subject !== '' ? mb_substr($subject, 0, 80) : 'Conversación #'.$conv->id,
                $this->channelLabel((string) ($conv->channel ?? '')), [
                    'status' => $conv->closed_at ? 'Cerrada' : 'Abierta',
                    'open' => $url ? ['kind' => 'url', 'url' => $url] : ['kind' => 'info'],
                ]);
        }

        return [$items, $this->source('ok', null, null) + ['count' => count($items)]];
    }

    private function channelLabel(string $channel): string
    {
        return match (strtolower($channel)) {
            'email', 'mail' => 'Correo',
            'whatsapp' => 'WhatsApp',
            'facebook', 'messenger' => 'Facebook',
            'instagram' => 'Instagram',
            'livechat', 'chat', 'widget' => 'Chat web',
            'phone', 'call' => 'Teléfono',
            '' => 'Conversación',
            default => ucfirst($channel),
        };
    }

    private function psCustomerIdFor(Customer $customer, ?int $erpId): ?int
    {
        try {
            $linked = $customer->externalIdFor('prestashop');
        } catch (\Throwable) {
            $linked = null;
        }

        if ($linked !== null && ctype_digit((string) $linked) && (int) $linked > 0) {
            return (int) $linked;
        }

        if ($erpId === null) {
            return null;
        }

        $summary = ($this->chat ?? app(ErpChatService::class))->summary($erpId);
        $code = ($summary['state'] ?? null) === 'ok' ? ($summary['data']['code_internet'] ?? null) : null;

        return is_scalar($code) && ctype_digit(trim((string) $code)) && (int) $code > 0 ? (int) $code : null;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function item(string $id, string $type, string $source, string $date, string $title, ?string $subtitle, array $extra = []): array
    {
        return array_merge([
            'id' => $id,
            'type' => $type,
            'source' => $source,
            'date' => $date,
            'day' => substr($date, 0, 10),
            'title' => $title,
            'subtitle' => $subtitle,
            'amount' => null,
            'points' => null,
            'status' => null,
            'open' => ['kind' => 'info'],
            'meta' => [],
        ], $extra);
    }

    /**
     * @return array<string, mixed>
     */
    private function source(string $state, ?string $message, ?string $reason, mixed $retryAfter = null): array
    {
        return array_filter([
            'state' => $state,
            'message' => $message,
            'reason' => $reason,
            'retry_after' => is_numeric($retryAfter) ? (int) $retryAfter : null,
        ], fn ($v) => $v !== null);
    }

    /**
     * "2025-09-16 20:06:39" / "2025-09-16" / ISO → "Y-m-d H:i:s" (null si no es fecha).
     */
    private function isoDate(mixed $value): ?string
    {
        if (! is_scalar($value) || ! preg_match('/^(\d{4}-\d{2}-\d{2})(?:[ T](\d{2}:\d{2}(?::\d{2})?))?/', trim((string) $value), $m)) {
            return null;
        }

        $time = $m[2] ?? '';
        if ($time === '') {
            $time = '00:00:00';
        } elseif (strlen($time) === 5) {
            $time .= ':00';
        }

        return $m[1].' '.$time;
    }

    private function scalarString(mixed $value): ?string
    {
        if (! is_scalar($value) || is_bool($value)) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }
}
