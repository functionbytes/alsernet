<?php

namespace Modules\HelpdeskContacts\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Models\Company;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\CustomerInsightsService;
use Nwidart\Modules\Facades\Module;

/**
 * Aggregates all data shown on the Contacts 360 tab-shell.
 *
 * Each public method maps 1:1 to a tab endpoint and returns the exact
 * camelCase array shape the ContactTabsController wraps in
 * { success: true, data: <here> }.
 *
 * Optional modules (ERP, PrestaShop, Remarketing, Tickets, EmailLog) are
 * always resolved behind Module::find() + class_exists() guards so the
 * Helpdesk panel never breaks when one of them is disabled.
 */
class ContactAggregatorService
{
    /**
     * Fully-qualified class names of optional-module classes, kept as plain
     * string literals (never top-of-file imports, never ::class) so they are
     * only ever resolved inside Module::find() + class_exists() guarded blocks
     * and the panel never breaks when a module is disabled.
     */
    private const ERP_CONTEXT_SERVICE = 'Modules\\HelpdeskErp\\Services\\ErpContextService';

    private const PRESTASHOP_CONTEXT_SERVICE = 'Modules\\HelpdeskPrestashop\\Services\\PrestashopContextService';

    private const REMARKETING_CUSTOMER = 'Modules\\Remarketing\\Models\\Customer';

    private const REMARKETING_ORDER = 'Modules\\Remarketing\\Models\\Order';

    private const REMARKETING_CART = 'Modules\\Remarketing\\Models\\Cart';

    private const EMAIL_LOG = 'Modules\\HelpdeskEmailLog\\Models\\EmailLog';

    private const TICKET = 'Modules\\HelpdeskTickets\\Models\\Ticket';

    private const ERP_TIMELINE_SERVICE = 'Modules\\HelpdeskErp\\Services\\CustomerTimelineService';

    private const TICKET_BRIDGE_SERVICE = 'Modules\\HelpdeskTickets\\Services\\HelpdeskTicketBridgeService';

    private const ECOMMERCE_PRODUCT = 'Modules\\Ecommerce\\Models\\Product';

    private const CUSTOMER_INTEGRATION_SERVICE = 'Modules\\HelpdeskIntegration\\Services\\CustomerIntegrationService';

    public function __construct(
        private readonly CustomerInsightsService $insights,
    ) {}

    /**
     * Resumen tab — identity, location, lifetime stats and integration links.
     *
     * @return array<string, mixed>
     */
    public function resumen(Customer $customer): array
    {
        // Caché corta del panel Resumen: reúne ~10 consultas (métricas, health,
        // sentiment, integraciones, pedidos, tickets). La clave incluye el
        // updated_at del cliente, así que cualquier edición/ban lo invalida al
        // instante; el TTL acota a 60s la frescura de los datos externos
        // (pedidos/sentiment) que no tocan la fila del cliente.
        $key = "helpdeskcontacts:resumen:{$customer->id}:".($customer->updated_at?->timestamp ?? 0);

        return Cache::remember($key, now()->addSeconds(60), fn (): array => $this->buildResumen($customer));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResumen(Customer $customer): array
    {
        $lifetime = $this->insights->lifetimeMetrics($customer);
        $healthScore = $this->insights->healthScore($customer);

        return [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'whatsapp' => $customer->whatsapp_phone,
            'avatarUrl' => $customer->getAvatarUrl(),
            'isVerified' => $customer->email_verified_at !== null,
            'isBanned' => $customer->banned_at !== null,
            'banReason' => $customer->ban_reason,
            'location' => [
                'country' => $customer->country,
                'state' => $customer->state,
                'city' => $customer->city,
                'postalCode' => $customer->postal_code,
            ],
            'language' => $customer->language,
            'timezone' => $customer->timezone,
            'lastSeenAt' => $customer->last_seen_at?->toIso8601String(),
            'stats' => [
                'totalConversations' => (int) ($customer->total_conversations ?? $lifetime['conversations']),
                'totalPageVisits' => (int) ($customer->total_page_visits ?? 0),
                'healthScore' => $healthScore,
                // null real (nunca encuestado) preservado, no colapsado a
                // 0.0 — contacts-360.js YA esperaba avgCsat nullable
                // (`!= null ? ... : '—'`) y Customer360Service::avg_csat
                // usa el mismo patrón; el cast (float)(... ?? 0.0) de aquí
                // rompía esa nulabilidad y mostraba "0.0"/"CSAT 0" para
                // clientes sin ninguna valoración real.
                'avgCsat' => $lifetime['csat_avg'] ?? null,
                'ticketsCount' => $this->countTickets($customer),
                'lifetime' => $this->lifetimeOrders($customer),
            ],
            'integrations' => $this->integrationStatuses($customer),
            'sentiment' => $this->sentiment($customer),
            'customAttributes' => $this->customAttributes($customer),
            'notes' => $customer->internal_notes,
        ];
    }

    /**
     * Sentiment signal derived from already-persisted ConversationTag pivot
     * rows applied to the customer's conversations in the last 90 days.
     * Never calls the live AI — only reads tags the sentiment listener stored.
     *
     * @return array{label: 'positive'|'neutral'|'negative', positive: int, negative: int}
     */
    public function sentiment(Customer $customer): array
    {
        $counts = ['positive' => 0, 'negative' => 0];

        try {
            $rows = DB::connection('helpdesk')
                ->table('helpdesk_conversation_tag_pivot as pivot')
                ->join('helpdesk_conversation_tags as t', 't.id', '=', 'pivot.tag_id')
                ->join('helpdesk_conversations as c', 'c.id', '=', 'pivot.conversation_id')
                ->where('c.customer_id', $customer->id)
                ->whereIn('t.slug', [
                    'sentiment-negative', 'sentiment_negative',
                    'sentiment-positive', 'sentiment_positive',
                ])
                ->where('pivot.created_at', '>=', now()->subDays(90))
                ->selectRaw('t.slug as slug, COUNT(*) as total')
                ->groupBy('t.slug')
                ->get();

            foreach ($rows as $row) {
                if (str_contains((string) $row->slug, 'negative')) {
                    $counts['negative'] += (int) $row->total;
                } elseif (str_contains((string) $row->slug, 'positive')) {
                    $counts['positive'] += (int) $row->total;
                }
            }
        } catch (\Throwable) {
            // Pivot/tags tables may not exist — degrade to neutral.
        }

        return [
            'label' => $this->sentimentLabel($counts['positive'], $counts['negative']),
            'positive' => $counts['positive'],
            'negative' => $counts['negative'],
        ];
    }

    /**
     * @return 'positive'|'neutral'|'negative'
     */
    private function sentimentLabel(int $positive, int $negative): string
    {
        if ($negative > $positive) {
            return 'negative';
        }

        if ($positive > $negative) {
            return 'positive';
        }

        return 'neutral';
    }

    /**
     * Custom attributes — resilient: the polymorphic pivot table may not exist
     * in every environment, so degrade to an empty map instead of throwing.
     *
     * @return array<string, mixed>
     */
    private function customAttributes(Customer $customer): array
    {
        try {
            return $customer->getAllCustomAttributes();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Conversaciones tab — the customer's helpdesk conversations.
     *
     * @return array{conversations: array<int, array<string, mixed>>}
     */
    public function conversaciones(Customer $customer): array
    {
        $conversations = $customer->conversations()
            ->with(['status', 'inbox', 'lastMessage'])
            ->latest('last_message_at')
            ->limit(50)
            ->get();

        return [
            'conversations' => $conversations->map(function (Conversation $conversation): array {
                $channelInfo = $conversation->channel_info;
                $status = $conversation->status;
                // Relación precargada arriba con with(['lastMessage']) — acceso
                // directo (sin getLatestMessage()) para que sea explícito que
                // no hay una query por fila.
                $latest = $conversation->lastMessage;
                $preview = trim(strip_tags((string) ($latest?->body ?? $conversation->subject ?? '')));

                return [
                    'id' => $conversation->id,
                    'subject' => $conversation->subject ?? 'Sin asunto',
                    'channel' => $conversation->channel ?? 'web',
                    'channelIcon' => $channelInfo['icon'],
                    'channelColor' => $channelInfo['color'],
                    'statusLabel' => $status?->name ?? 'Desconocido',
                    'statusClass' => ($status?->is_open ?? true) ? 'success' : 'secondary',
                    'preview' => mb_strimwidth($preview, 0, 120, '…'),
                    'lastAt' => ($conversation->last_message_at ?? $conversation->created_at)?->toIso8601String(),
                    'url' => $this->conversationUrl($conversation->id),
                ];
            })->all(),
        ];
    }

    /**
     * ERP tab — passthrough of ErpContextService context (guarded).
     *
     * @return array<string, mixed>
     */
    public function erp(Customer $customer): array
    {
        if (! $customer->email || ! $this->erpAvailable()) {
            return ['available' => false];
        }

        $service = app(self::ERP_CONTEXT_SERVICE);
        $context = $service->getCustomerContext($customer->email, null, $customer->id);

        return $context + ['available' => true];
    }

    /**
     * PrestaShop tab — passthrough of PrestashopContextService context (guarded).
     *
     * @return array<string, mixed>
     */
    public function prestashop(Customer $customer): array
    {
        if (! $customer->email || ! $this->prestashopAvailable()) {
            return ['available' => false];
        }

        $service = app(self::PRESTASHOP_CONTEXT_SERVICE);
        $context = $service->getCustomerContext($customer->email);

        return $context + ['available' => true];
    }

    /**
     * Tienda tab — local Remarketing mirror, matched by lowercased email.
     * Mirrors CustomerEcommerceController's exact Remarketing-by-email logic.
     *
     * @return array<string, mixed>
     */
    public function tienda(Customer $customer): array
    {
        if (! $customer->email || ! $this->remarketingAvailable()) {
            return ['available' => false, 'orders' => [], 'carts' => [], 'stats' => null];
        }

        $remarketingCustomer = app(self::REMARKETING_CUSTOMER);
        $orderModel = app(self::REMARKETING_ORDER);
        $cartModel = app(self::REMARKETING_CART);

        $customerIds = $remarketingCustomer->newQuery()
            ->where('email', strtolower($customer->email))
            ->pluck('id');

        if ($customerIds->isEmpty()) {
            return ['available' => true, 'orders' => [], 'carts' => [], 'stats' => null];
        }

        $orders = $orderModel->newQuery()
            ->whereIn('customer_id', $customerIds)
            ->with('items')
            ->latest('placed_at')
            ->limit(10)
            ->get()
            ->map(fn ($order): array => [
                'number' => $order->order_number,
                'status' => $order->status,
                'total' => (float) $order->total,
                'currency' => $order->currency ?? 'EUR',
                'placedAt' => $order->placed_at?->toIso8601String(),
                'items' => $order->items->map(fn ($item): array => [
                    'name' => $item->title,
                    'qty' => (int) $item->quantity,
                    'price' => (float) $item->price,
                ])->all(),
            ]);

        $carts = $cartModel->newQuery()
            ->whereIn('customer_id', $customerIds)
            ->where('status', 'abandoned')
            ->latest('abandoned_at')
            ->limit(5)
            ->get()
            ->map(fn ($cart): array => $this->mapAbandonedCart($cart));

        // Estadísticas sobre TODOS los pedidos del cliente, no sobre los 10
        // que se muestran arriba: calcularlas desde la colección ya limitada
        // sub-reportaba conteo y gasto total de clientes con más de 10 pedidos.
        // Mismo agregado que lifetimeOrders().
        $stats = $orderModel->newQuery()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('COUNT(*) as orders_count, SUM(total) as total_spent')
            ->first();

        return [
            'available' => true,
            'orders' => $orders->all(),
            'carts' => $carts->all(),
            'stats' => [
                'ordersCount' => (int) ($stats->orders_count ?? 0),
                'totalSpent' => (float) ($stats->total_spent ?? 0.0),
            ],
        ];
    }

    /**
     * Actividad tab — timeline, CSAT, page visits, emails, tickets and company.
     *
     * @return array<string, mixed>
     */
    public function actividad(Customer $customer): array
    {
        return [
            'timeline' => $this->activityTimeline($customer),
            'csat' => $this->csat($customer),
            'pageVisits' => $this->pageVisits($customer),
            'emails' => $this->emails($customer),
            'tickets' => $this->tickets($customer),
            'company' => $this->company($customer),
        ];
    }

    /**
     * Activity timeline: prefer the ERP cross-source feed (ERP + PS + Helpdesk)
     * when HelpdeskErp is enabled and the customer has an email. Falls back to
     * the local journeyTimeline when ERP is off or the cross-source call throws.
     *
     * @return array<int, array{type: string, title: string, detail: string, at: string, icon: string, source?: string}>
     */
    private function activityTimeline(Customer $customer): array
    {
        if (! $customer->email || ! $this->erpTimelineAvailable()) {
            return $this->timeline($customer);
        }

        try {
            $events = app(self::ERP_TIMELINE_SERVICE)->getTimeline($customer->email, 30);

            if (empty($events)) {
                return $this->timeline($customer);
            }

            return array_map(fn (array $event): array => [
                'type' => (string) ($event['type'] ?? 'event'),
                'title' => (string) ($event['title'] ?? ''),
                'detail' => $this->timelineDetail($event),
                'at' => $this->normalizeDate($event['date'] ?? null),
                'icon' => $this->sourceIcon((string) ($event['source'] ?? '')),
                'source' => (string) ($event['source'] ?? 'helpdesk'),
            ], $events);
        } catch (\Throwable) {
            return $this->timeline($customer);
        }
    }

    /**
     * Best-effort human detail line for an ERP timeline event.
     *
     * @param  array<string, mixed>  $event
     */
    private function timelineDetail(array $event): string
    {
        $data = $event['data'] ?? [];

        if (! is_array($data)) {
            return '';
        }

        foreach (['status', 'total', 'reference', 'number', 'subject'] as $key) {
            if (isset($data[$key]) && is_scalar($data[$key])) {
                return (string) $data[$key];
            }
        }

        return '';
    }

    private function sourceIcon(string $source): string
    {
        return match ($source) {
            'erp' => 'fas fa-database',
            'prestashop' => 'fas fa-bag-shopping',
            'helpdesk' => 'fas fa-comment',
            default => 'fas fa-circle',
        };
    }

    /**
     * Normalize a heterogeneous date string to ISO8601, tolerating bad input.
     */
    private function normalizeDate(mixed $date): string
    {
        if (! is_string($date) || $date === '') {
            return now()->toIso8601String();
        }

        try {
            return Carbon::parse($date)->toIso8601String();
        } catch (\Throwable) {
            return now()->toIso8601String();
        }
    }

    /**
     * Tickets tab — customer tickets plus available categories, sourced through
     * the HelpdeskTicketBridgeService so the contacts module never imports the
     * HelpdeskTickets symbols directly. Guarded by the bridge availability.
     *
     * @return array{available: bool, tickets: array<int, array<string, mixed>>, categories: array<int, array{id: int, name: string}>}
     */
    public function ticketsTab(Customer $customer): array
    {
        $bridge = $this->ticketBridge();

        if (! $bridge) {
            return ['available' => false, 'tickets' => [], 'categories' => []];
        }

        $tickets = $bridge->getCustomerTickets($customer, 20)
            ->map(fn ($ticket): array => [
                'id' => $ticket->id,
                'number' => $ticket->ticket_number,
                'subject' => $ticket->subject ?? 'Sin asunto',
                'status' => $ticket->status?->name ?? 'Abierto',
                'statusClass' => $this->ticketStatusClass($ticket->status?->is_open ?? true),
                'priority' => $ticket->priority,
                'priorityClass' => $this->priorityClass((string) $ticket->priority),
                'slaBadge' => $this->slaBadge($ticket),
                'createdAt' => $ticket->created_at?->toIso8601String(),
                'url' => $this->ticketUrl($ticket->id),
            ])
            ->values()
            ->all();

        $categories = $bridge->getCategories()
            ->map(fn ($category): array => [
                'id' => (int) ($category['id'] ?? 0),
                'name' => (string) ($category['name'] ?? ''),
            ])
            ->values()
            ->all();

        return [
            'available' => true,
            'tickets' => $tickets,
            'categories' => $categories,
        ];
    }

    /**
     * Create a ticket for the customer through the tickets bridge.
     *
     * @param  array{subject?: string, category_id?: int|null, message?: string}  $payload
     * @return array<string, mixed>|null The created-ticket shape, or null when tickets are unavailable.
     */
    public function createTicket(Customer $customer, array $payload): ?array
    {
        $bridge = $this->ticketBridge();

        if (! $bridge || ! method_exists($bridge, 'createForCustomer')) {
            return null;
        }

        return $bridge->createForCustomer($customer, [
            'subject' => $payload['subject'] ?? null,
            'category_id' => $payload['category_id'] ?? null,
            'message' => $payload['message'] ?? null,
        ]);
    }

    private function ticketStatusClass(bool $isOpen): string
    {
        return $isOpen ? 'success' : 'secondary';
    }

    private function priorityClass(string $priority): string
    {
        return match (strtolower($priority)) {
            'urgent', 'critical' => 'danger',
            'high' => 'warning',
            'low' => 'secondary',
            default => 'info',
        };
    }

    /**
     * SLA badge derived from already-persisted resolution SLA flags.
     *
     * @return array{label: string, class: string}|null
     */
    private function slaBadge(mixed $ticket): ?array
    {
        if ($ticket->sla_resolution_breached ?? false) {
            return ['label' => 'SLA incumplido', 'class' => 'danger'];
        }

        $dueAt = $ticket->sla_resolution_due_at ?? null;

        if (! $dueAt) {
            return null;
        }

        if ($dueAt->isPast()) {
            return ['label' => 'SLA vencido', 'class' => 'danger'];
        }

        if ($dueAt->lte(now()->addHours(2))) {
            return ['label' => 'SLA próximo', 'class' => 'warning'];
        }

        return ['label' => 'En SLA', 'class' => 'success'];
    }

    /**
     * Re-link a customer to its external ERP / PrestaShop IDs by email.
     *
     * @return array{integrations: array<int, array{platform: string, label: string, connected: bool, externalId: ?string, syncStatus: ?string, lastSyncedAt: ?string}>}
     */
    public function syncIntegrations(Customer $customer): array
    {
        if ($customer->email && $this->erpAvailable()) {
            // getCustomerContext persists the link via linkExternalId when found.
            app(self::ERP_CONTEXT_SERVICE)
                ->getCustomerContext($customer->email, null, $customer->id);
        }

        if ($customer->email && $this->prestashopAvailable()) {
            $context = app(self::PRESTASHOP_CONTEXT_SERVICE)
                ->getCustomerContext($customer->email);

            $externalId = $context['customer']['id']
                ?? ($context['customer']['external_id'] ?? null);

            if (($context['customer']['found'] ?? false) && $externalId) {
                $customer->linkExternalId('prestashop', (string) $externalId, [
                    'linked_at' => now()->toIso8601String(),
                    'linked_by' => 'sync',
                ]);
            }
        }

        $customer->load('externalIds');

        return ['integrations' => $this->integrationStatuses($customer)];
    }

    /* ── Tienda helpers ───────────────────────────────────────────────────── */

    /**
     * Map a Remarketing abandoned cart to the tab shape, resolving each line to
     * a local Ecommerce product id (by SKU) so the JS 'Recuperar' button can
     * POST the lines to the assisted cart. A cart is only 'recoverable' when
     * every line maps to a usable local product id.
     *
     * @return array{updatedAt: ?string, itemsCount: int, total: float, recoverable: bool, lines: array<int, array{productId: ?int, name: string, qty: int}>}
     */
    private function mapAbandonedCart(mixed $cart): array
    {
        $rawItems = is_array($cart->items ?? null) ? $cart->items : [];
        $lines = [];
        $recoverable = $rawItems !== [];

        foreach ($rawItems as $item) {
            $sku = is_array($item) ? ($item['sku'] ?? null) : null;
            $productId = $this->resolveLocalProductId(is_string($sku) ? $sku : null);

            if ($productId === null) {
                $recoverable = false;
            }

            $lines[] = [
                'productId' => $productId,
                'name' => (string) (is_array($item) ? ($item['title'] ?? $item['name'] ?? 'Producto') : 'Producto'),
                'qty' => (int) (is_array($item) ? ($item['quantity'] ?? 1) : 1),
            ];
        }

        return [
            'updatedAt' => ($cart->abandoned_at ?? $cart->updated_at)?->toIso8601String(),
            'itemsCount' => count($rawItems),
            'total' => (float) $cart->total,
            'recoverable' => $recoverable,
            'lines' => $lines,
        ];
    }

    /**
     * Resolve a Remarketing item SKU to a local Ecommerce product id, matching
     * either the products.sku or products.reference column. Guarded — returns
     * null when Ecommerce is unavailable or no product matches.
     */
    private function resolveLocalProductId(?string $sku): ?int
    {
        if ($sku === null || $sku === '' || ! $this->ecommerceAvailable()) {
            return null;
        }

        try {
            $id = app(self::ECOMMERCE_PRODUCT)->newQuery()
                ->where('sku', $sku)
                ->orWhere('reference', $sku)
                ->value('id');

            return $id !== null ? (int) $id : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function ecommerceAvailable(): bool
    {
        return $this->moduleEnabled('Ecommerce')
            && class_exists(self::ECOMMERCE_PRODUCT);
    }

    /* ── Resumen helpers ──────────────────────────────────────────────────── */

    /**
     * Lifetime order metrics sourced from the Remarketing mirror (guarded).
     *
     * El tab "Resumen" (por defecto al abrir un contacto) solo necesita el
     * conteo/total/moneda — antes llamaba a tienda() completo, que hace
     * with('items') sobre hasta 10 pedidos + hasta 5 carritos abandonados
     * solo para descartar casi todo el resultado.
     *
     * @return array{ordersCount: int, totalSpent: float, currency: string}
     */
    private function lifetimeOrders(Customer $customer): array
    {
        if (! $customer->email || ! $this->remarketingAvailable()) {
            return ['ordersCount' => 0, 'totalSpent' => 0.0, 'currency' => 'EUR'];
        }

        $remarketingCustomer = app(self::REMARKETING_CUSTOMER);
        $orderModel = app(self::REMARKETING_ORDER);

        $customerIds = $remarketingCustomer->newQuery()
            ->where('email', strtolower($customer->email))
            ->pluck('id');

        if ($customerIds->isEmpty()) {
            return ['ordersCount' => 0, 'totalSpent' => 0.0, 'currency' => 'EUR'];
        }

        $stats = $orderModel->newQuery()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('COUNT(*) as orders_count, SUM(total) as total_spent')
            ->first();

        $latestCurrency = $orderModel->newQuery()
            ->whereIn('customer_id', $customerIds)
            ->latest('placed_at')
            ->value('currency');

        return [
            'ordersCount' => (int) ($stats->orders_count ?? 0),
            'totalSpent' => (float) ($stats->total_spent ?? 0.0),
            'currency' => $latestCurrency ?? 'EUR',
        ];
    }

    /**
     * Integration connection statuses.
     *
     * Cuando el módulo HelpdeskIntegration está activo se delega en
     * CustomerIntegrationService::buildPayload() — el mismo servicio que usa
     * el panel derecho del inbox — para traer TODAS las plataformas del
     * catálogo (no solo erp/prestashop) con su estado real de sync
     * (ok/not_found/pending/error) y última sincronización. Si el módulo
     * está desactivado se degrada al criterio anterior (solo erp/prestashop
     * desde los vínculos ya guardados, sin estado de sync) — esos vínculos
     * viven en el core (Customer::externalIds) y no dependen de que
     * HelpdeskIntegration esté activo.
     *
     * @return array<int, array{platform: string, label: string, connected: bool, externalId: ?string, syncStatus: ?string, lastSyncedAt: ?string}>
     */
    private function integrationStatuses(Customer $customer): array
    {
        if ($this->integrationModuleAvailable()) {
            $payload = app(self::CUSTOMER_INTEGRATION_SERVICE)->buildPayload($customer);

            return collect($payload['integrations'] ?? [])
                ->map(fn (array $it): array => [
                    'platform' => $it['platform'],
                    'label' => $it['label'],
                    'connected' => $it['connected'],
                    'externalId' => $it['external_id'],
                    'syncStatus' => $it['sync_status'],
                    'lastSyncedAt' => $it['last_synced_at'],
                ])
                ->values()
                ->all();
        }

        $erpId = $customer->externalIdFor('erp');
        $prestashopId = $customer->externalIdFor('prestashop');

        return [
            ['platform' => 'erp', 'label' => 'Gestión (ERP)', 'connected' => $erpId !== null, 'externalId' => $erpId, 'syncStatus' => null, 'lastSyncedAt' => null],
            ['platform' => 'prestashop', 'label' => 'PrestaShop', 'connected' => $prestashopId !== null, 'externalId' => $prestashopId, 'syncStatus' => null, 'lastSyncedAt' => null],
        ];
    }

    private function integrationModuleAvailable(): bool
    {
        $enabled = function_exists('helpdesk_integration_enabled')
            ? helpdesk_integration_enabled()
            : $this->moduleEnabled('HelpdeskIntegration');

        return $enabled && class_exists(self::CUSTOMER_INTEGRATION_SERVICE);
    }

    /* ── Actividad helpers ────────────────────────────────────────────────── */

    /**
     * @return array<int, array{type: string, title: string, detail: string, at: string, icon: string}>
     */
    private function timeline(Customer $customer): array
    {
        return array_map(fn (array $event): array => [
            'type' => $event['type'],
            'title' => $event['label'],
            'detail' => $event['description'],
            'at' => $event['occurred_at'],
            'icon' => $this->timelineIcon($event['type']),
        ], $this->insights->journeyTimeline($customer, 30));
    }

    private function timelineIcon(string $type): string
    {
        return match ($type) {
            'conversation_started' => 'fas fa-comment',
            'conversation_closed' => 'fas fa-circle-check',
            'csat_submitted' => 'fas fa-star',
            'tag_applied' => 'fas fa-tag',
            default => 'fas fa-circle',
        };
    }

    /**
     * @return array<int, array{score: int, comment: ?string, agent: ?string, at: ?string}>
     */
    private function csat(Customer $customer): array
    {
        return CsatRating::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('answered_at')
            ->latest('answered_at')
            ->limit(20)
            ->get()
            ->map(fn (CsatRating $rating): array => [
                'score' => (int) $rating->rating,
                'comment' => $rating->comment,
                'agent' => null,
                'at' => $rating->answered_at?->toIso8601String(),
            ])->all();
    }

    /**
     * @return array<int, array{url: string, title: ?string, timeSpent: int, at: ?string}>
     */
    private function pageVisits(Customer $customer): array
    {
        return $customer->pageVisits()
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($visit): array => [
                'url' => $visit->page_url,
                'title' => $visit->referrer,
                'timeSpent' => (int) ($visit->duration_seconds ?? 0),
                'at' => $visit->created_at?->toIso8601String(),
            ])->all();
    }

    /**
     * Email logs addressed to this customer (guarded by module + class).
     *
     * @return array<int, array{subject: string, status: string, statusClass: string, at: ?string, url: ?string}>
     */
    private function emails(Customer $customer): array
    {
        if (! $customer->email
            || ! $this->moduleEnabled('HelpdeskEmailLog')
            || ! class_exists(self::EMAIL_LOG)) {
            return [];
        }

        $email = strtolower($customer->email);
        $model = app(self::EMAIL_LOG);

        // MATCH AGAINST usa el indice FULLTEXT de recipients_index (ver
        // EmailLogController::index()); el LIKE '%...%' puro forzaba un full
        // table scan en cada carga del tab Actividad. Se mantiene el LIKE
        // como fallback para el mismo caso (tokens cortos/parciales).
        return $model->newQuery()
            ->where(fn ($q) => $q
                ->whereRaw('MATCH(recipients_index) AGAINST (?)', [$email])
                ->orWhere('recipients_index', 'like', '%'.$email.'%'))
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($log): array => [
                'subject' => $log->subject ?? '(sin asunto)',
                'status' => $log->status_label,
                'statusClass' => $log->status_color,
                'at' => $log->display_date?->toIso8601String(),
                'url' => $log->entity_url,
            ])->all();
    }

    /**
     * Tickets for this customer (guarded by tickets enabled + class).
     *
     * @return array<int, array{number: string, subject: string, status: ?string, priority: ?string, at: ?string, url: ?string}>
     */
    private function tickets(Customer $customer): array
    {
        if (! $this->ticketsAvailable()) {
            return [];
        }

        $model = app(self::TICKET);

        return $model->newQuery()
            ->where('customer_id', $customer->id)
            ->with('status')
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($ticket): array => [
                'number' => $ticket->ticket_number,
                'subject' => $ticket->subject ?? 'Sin asunto',
                'status' => $ticket->status?->name,
                'priority' => $ticket->priority,
                'at' => $ticket->created_at?->toIso8601String(),
                'url' => $this->ticketUrl($ticket->id),
            ])->all();
    }

    /**
     * Company record for the customer, when the company_id column exists.
     *
     * @return array{name: ?string, domain: ?string, industry: ?string, size: ?string, healthScore: ?int, contactsCount: int}|null
     */
    private function company(Customer $customer): ?array
    {
        if (! Schema::connection('helpdesk')->hasColumn('helpdesk_customers', 'company_id')) {
            return null;
        }

        $companyId = $customer->getAttribute('company_id');

        if (! $companyId) {
            return null;
        }

        $company = Company::query()
            ->withCount('customers')
            ->find($companyId);

        if (! $company) {
            return null;
        }

        return [
            'name' => $company->name,
            'domain' => $company->domain,
            'industry' => $company->industry,
            'size' => $company->size,
            'healthScore' => $company->health_score,
            'contactsCount' => (int) $company->customers_count,
        ];
    }

    /* ── URL helpers ──────────────────────────────────────────────────────── */

    private function conversationUrl(int $conversationId): ?string
    {
        return $this->routeUrl('manager.helpdesk.conversations.show', $conversationId);
    }

    private function ticketUrl(int $ticketId): ?string
    {
        return $this->routeUrl('manager.helpdesk.tickets.show', $ticketId);
    }

    private function routeUrl(string $name, mixed $parameter): ?string
    {
        if (! app('router')->has($name)) {
            return null;
        }

        try {
            return route($name, $parameter);
        } catch (\Throwable) {
            return null;
        }
    }

    /* ── Module guards ────────────────────────────────────────────────────── */

    private function erpAvailable(): bool
    {
        return $this->moduleEnabled('HelpdeskErp')
            && class_exists(self::ERP_CONTEXT_SERVICE);
    }

    private function prestashopAvailable(): bool
    {
        return $this->moduleEnabled('HelpdeskPrestashop')
            && class_exists(self::PRESTASHOP_CONTEXT_SERVICE);
    }

    private function remarketingAvailable(): bool
    {
        return $this->moduleEnabled('Remarketing')
            && class_exists(self::REMARKETING_CUSTOMER);
    }

    private function ticketsAvailable(): bool
    {
        $enabled = function_exists('helpdesk_tickets_enabled')
            ? helpdesk_tickets_enabled()
            : $this->moduleEnabled('HelpdeskTickets');

        return $enabled && class_exists(self::TICKET);
    }

    private function erpTimelineAvailable(): bool
    {
        return $this->moduleEnabled('HelpdeskErp')
            && class_exists(self::ERP_TIMELINE_SERVICE);
    }

    /**
     * Resolve the tickets bridge only when the module is enabled, the class
     * exists and the bridge itself reports availability. Returns null otherwise.
     */
    private function ticketBridge(): ?object
    {
        if (! $this->ticketsAvailable() || ! class_exists(self::TICKET_BRIDGE_SERVICE)) {
            return null;
        }

        try {
            $bridge = app(self::TICKET_BRIDGE_SERVICE);

            return $bridge->isAvailable() ? $bridge : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function moduleEnabled(string $name): bool
    {
        return Module::find($name)?->isEnabled() ?? false;
    }

    private function countTickets(Customer $customer): int
    {
        if (! $this->ticketsAvailable()) {
            return 0;
        }

        try {
            $ticketClass = self::TICKET;

            return (int) $ticketClass::query()->where('customer_id', $customer->id)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
