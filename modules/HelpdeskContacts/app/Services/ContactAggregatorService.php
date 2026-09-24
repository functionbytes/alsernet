<?php

namespace Modules\HelpdeskContacts\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Models\Company;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\CustomerInsightsService;
use Nwidart\Modules\Facades\Module;
use Spatie\Activitylog\Models\Activity;

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
     * Campos de la ficha que un agente reconoce en el historial.
     */
    /**
     * Zona horaria de las fechas sin desplazamiento que envían PrestaShop y el ERP.
     */
    private const EXTERNAL_TIMEZONE = 'Europe/Madrid';

    private const FICHA_FIELD_LABELS = [
        'name' => 'el nombre', 'email' => 'el email', 'phone' => 'el teléfono',
        'whatsapp_phone' => 'el WhatsApp', 'banned_at' => 'el bloqueo',
        'ban_reason' => 'el motivo de bloqueo', 'internal_notes' => 'las notas internas',
        'language' => 'el idioma', 'timezone' => 'la zona horaria',
        'owner_id' => 'el responsable', 'is_vip' => 'la marca VIP',
    ];

    /**
     * Fully-qualified class names of optional-module classes, kept as plain
     * string literals (never top-of-file imports, never ::class) so they are
     * only ever resolved inside Module::find() + class_exists() guarded blocks
     * and the panel never breaks when a module is disabled.
     */
    private const ERP_CONTEXT_SERVICE = 'Modules\\HelpdeskErp\\Services\\ErpContextService';

    private const ERP_LINKER = 'Modules\\HelpdeskErp\\Services\\ErpCustomerLinkerService';

    private const PRESTASHOP_CONTEXT_SERVICE = 'Modules\\HelpdeskPrestashop\\Services\\PrestashopContextService';

    private const REMARKETING_CUSTOMER = 'Modules\\Remarketing\\Models\\Customer';

    private const REMARKETING_ORDER = 'Modules\\Remarketing\\Models\\Order';

    private const REMARKETING_CART = 'Modules\\Remarketing\\Models\\Cart';

    private const EMAIL_LOG = 'Modules\\HelpdeskEmailActivity\\Models\\EmailLog';

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
            'secondaryEmails' => array_values($customer->secondary_emails ?? []),
            'phone' => $customer->phone,
            'whatsapp' => $customer->whatsapp_phone,
            'avatarUrl' => $customer->getAvatarUrl(),
            'isVerified' => $customer->email_verified_at !== null,
            'isBanned' => $customer->banned_at !== null,
            'isVip' => $customer->isVip(),
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
                // $customer->total_conversations es un contador denormalizado
                // que solo se incrementa (Customer::incrementConversationCount())
                // y nunca se decrementa al borrar/reasignar conversaciones —
                // encontrado desincronizado en vivo (15 cacheado vs 0 real),
                // contradiciendo a la propia pestaña "Conversaciones" de al
                // lado. $lifetime ya hace el COUNT(*) real más abajo, así que
                // usarlo aquí siempre no cuesta una consulta extra.
                'totalConversations' => (int) $lifetime['conversations'],
                'openConversations' => $customer->conversations()->open()->count(),
                'totalPageVisits' => (int) ($customer->total_page_visits ?? 0),
                'healthScore' => $healthScore,
                'healthFactors' => $this->insights->healthFactors($customer),
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
            'tags' => $customer->tags->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'color' => $t->color,
            ])->all(),
            // Responsable del contacto; owner_id null no cuesta ninguna consulta.
            'owner' => $customer->owner_id !== null && $customer->owner
                ? ['id' => (int) $customer->owner->id, 'name' => trim((string) $customer->owner->full_name) ?: (string) $customer->owner->email]
                : null,
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
            ->with(['status', 'inbox', 'lastMessage', 'assignee'])
            ->withCount('messages')
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
                    'channelLabel' => $channelInfo['label'],
                    'agentName' => $conversation->assignee?->full_name,
                    'messagesCount' => (int) $conversation->messages_count,
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
        // Sin email pero con teléfono (contacto de WhatsApp) también se busca:
        // ErpContextService usa el teléfono como identidad cuando falta el email.
        $phone = $customer->phone ?: $customer->whatsapp_phone;
        if (! $this->erpAvailable()) {
            return ['available' => false];
        }
        if (! $customer->email && ! $phone) {
            return ['available' => true, 'customer' => ['found' => false], 'noIdentifiers' => true] + $this->erpLookup($customer);
        }

        // Id ERP ya vinculado (mismo criterio que prestashop()): el email del
        // contacto no tiene por qué coincidir con el de su ficha de Gestión.
        $erpId = $customer->externalIdFor('erp');

        $service = app(self::ERP_CONTEXT_SERVICE);

        // La caché del contexto va por email e ignora el id vinculado: si
        // guardó "no encontrado" (o a otro cliente) antes del vínculo, se
        // olvida para que esta vez se busque por el id.
        if ($erpId !== null && $customer->email && method_exists($service, 'peekCachedContext')) {
            $cached = $service->peekCachedContext((string) $customer->email);
            $cachedId = $cached['customer']['id'] ?? null;
            if ($cached !== null && (empty($cached['customer']['found']) || (string) $cachedId !== (string) $erpId)) {
                $service->forgetCache((string) $customer->email);
            }
        }

        $context = $service->getCustomerContext(
            (string) $customer->email,
            $customer->email ? null : $phone,
            $customer->id,
            $erpId !== null ? (int) $erpId : null,
        );

        return $context + ['available' => true] + $this->erpLookup($customer);
    }

    /**
     * Resultado de la última búsqueda automática en Gestión (vinculación por
     * email → teléfono → email de PrestaShop): la ficha distingue "no está"
     * de "el ERP no contestó" y ofrece reintentar.
     *
     * @return array{lookup: array{status: ?string, at: ?string, linked: bool}}
     */
    private function erpLookup(Customer $customer): array
    {
        return ['lookup' => [
            'status' => $customer->erp_lookup_status,
            'at' => $customer->erp_lookup_at?->toIso8601String(),
            'linked' => $customer->externalIdFor('erp') !== null,
        ]];
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

        // external_id ya vinculado (mismo patrón que erp() más arriba): el
        // email del contacto de Helpdesk no tiene por qué coincidir con el de
        // su cuenta de PrestaShop — llegó por WhatsApp/teléfono y se vinculó
        // a mano o por otra vía. Sin esto, un contacto ya vinculado explícitamente
        // seguía mostrando "sin datos" porque la búsqueda solo probaba el email.
        $externalId = $customer->externalIdFor('prestashop');

        $context = $service->getCustomerContext(
            $customer->email,
            $customer->id,
            $externalId !== null ? (int) $externalId : null
        );

        // Cupones/direcciones/devoluciones/mensajes/lista de deseos/reembolsos
        // ya vienen en $context: el bridge los agrega dentro de la misma
        // llamada a customer.helpdesk_context (ver alsernet_customer_helpdesk_context()
        // en alsernetbridge) — antes cada uno era una petición HTTP aparte al
        // bridge (7 en total para cargar la pestaña). Solo faltan cuando el
        // fallback de fetchContext() reconstruye el contexto desde
        // customer.orders (helpdesk_context caído), de ahí los '?? []'.
        return $context + [
            'available' => true,
            'external_id' => $externalId !== null ? (int) $externalId : ($context['customer']['id'] ?? null),
            'vouchers' => $context['vouchers'] ?? [],
            'addresses' => $context['addresses'] ?? [],
            'returns' => $context['returns'] ?? [],
            'messages' => $context['messages'] ?? [],
            'wishlist' => $context['wishlist'] ?? [],
            'refunds' => $context['refunds'] ?? [],
        ];
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

        $abandonedCarts = $cartModel->newQuery()
            ->whereIn('customer_id', $customerIds)
            ->where('status', 'abandoned')
            ->latest('abandoned_at')
            ->limit(5)
            ->get();

        // Resuelve todos los SKUs de los hasta 5 carritos en una sola query en
        // vez de una por línea de carrito (ver resolveLocalProductIdsMap()).
        $skuToProductId = $this->resolveLocalProductIdsMap($this->collectCartSkus($abandonedCarts));

        $carts = $abandonedCarts->map(fn ($cart): array => $this->mapAbandonedCart($cart, $skuToProductId));

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
        $base = $this->activityTimelineBase($customer);
        $ficha = $this->fichaEvents($customer);

        if ($ficha === []) {
            return $base;
        }

        $combined = [...$base, ...$ficha];
        usort($combined, fn (array $a, array $b) => strcmp($b['at'], $a['at']));

        return array_slice($combined, 0, 40);
    }

    private function activityTimelineBase(Customer $customer): array
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
     * Filtro "Ficha" del Historial: cambios reales sobre el propio registro
     * Customer, ya grabados por LogsActivity (spatie/laravel-activitylog,
     * conexión 'mysql' — la default de la app, no 'helpdesk') sin necesitar
     * ningún logging nuevo. Solo se listan campos que un agente reconocería
     * (nombre/email/teléfono/bloqueo/notas); un update que solo toca columnas
     * técnicas (updated_at, etc.) cae en el genérico "Ficha actualizada".
     *
     * @return array<int, array{type: string, title: string, detail: string, at: string, icon: string, source: string}>
     */
    private function fichaEvents(Customer $customer, int $limit = 20): array
    {
        try {
            $activities = Activity::forSubject($customer)
                ->with('causer')
                ->latest('created_at')
                ->limit($limit)
                ->get(['event', 'properties', 'created_at', 'causer_type', 'causer_id']);
        } catch (\Throwable) {
            return [];
        }

        return $activities->map(function ($activity): array {
            $title = $this->fichaEventTitle((string) $activity->event, $activity->properties);
            $causer = $activity->causer?->full_name ?? null;

            // "María García cambió el teléfono" — el autor delante cuando se
            // conoce; los cambios de jobs o del sistema quedan en impersonal.
            if ($activity->event === 'updated' && str_starts_with($title, 'Cambió ')) {
                $title = $causer ? $causer.' c'.substr($title, 1) : 'Se c'.mb_substr($title, 1);
            } elseif ($causer && $activity->event === 'created') {
                $title .= ' por '.$causer;
            }

            return [
                'type' => 'ficha_'.$activity->event,
                'title' => $title,
                'detail' => $this->fichaEventDetail((string) $activity->event, $activity->properties),
                'at' => $activity->created_at->toIso8601String(),
                'icon' => $this->sourceIcon('ficha'),
                'source' => 'ficha',
            ];
        })->all();
    }

    /**
     * "antes +52 55 6154 9000": valor anterior cuando cambió un único campo
     * legible — la única forma de auditar quién tocó qué dato del contacto.
     *
     * @param  Collection<string, mixed>|null  $properties
     */
    private function fichaEventDetail(string $event, $properties): string
    {
        if ($event !== 'updated') {
            return '';
        }

        $old = collect($properties?->get('old') ?? [])->only(array_keys(self::FICHA_FIELD_LABELS));

        if ($old->count() !== 1) {
            return '';
        }

        $field = $old->keys()->first();
        $value = $old->first();

        if ($field === 'owner_id') {
            $value = $value ? (User::query()->find($value)?->full_name ?? 'Agente #'.$value) : null;
        } elseif ($field === 'is_vip') {
            $value = $value ? 'VIP' : 'no VIP';
        } elseif ($field === 'banned_at') {
            $value = $value ? 'bloqueado' : 'activo';
        }

        return $value === null || $value === '' ? 'antes vacío' : 'antes '.mb_strimwidth((string) $value, 0, 60, '…');
    }

    /**
     * @param  Collection<string, mixed>|null  $properties
     */
    private function fichaEventTitle(string $event, $properties): string
    {
        if ($event === 'created') {
            return 'Ficha creada';
        }

        if ($event === 'deleted') {
            return 'Contacto eliminado';
        }

        $labels = self::FICHA_FIELD_LABELS;

        $changed = collect($properties?->get('attributes') ?? [])
            ->keys()
            ->map(fn ($key) => $labels[$key] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($changed->isEmpty()) {
            return 'Ficha actualizada';
        }

        return 'Cambió '.$changed->implode(', ');
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
            'ficha' => 'fas fa-user-pen',
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
            // PrestaShop y el ERP devuelven fechas sin zona ("2026-09-24 13:26:08")
            // en hora local de la tienda; la app corre en UTC y las adelantaba 2 h.
            $hasOffset = (bool) preg_match('/(Z|[+-]\d{2}:?\d{2})$/', trim($date));

            return ($hasOffset ? Carbon::parse($date) : Carbon::parse($date, self::EXTERNAL_TIMEZONE))->toIso8601String();
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
                'agentName' => $ticket->assignee?->full_name,
                'closedAt' => ($ticket->closed_at ?? $ticket->resolved_at)?->toIso8601String(),
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
            'priority' => $payload['priority'] ?? null,
            'internal_note' => ! empty($payload['attach_context']) ? $this->ticketContextNote($customer) : null,
        ]);
    }

    /**
     * "Adjuntar el contexto de la ficha 360 al ticket": resumen en texto de la
     * ficha para la nota interna del ticket (solo agentes). Sale del resumen
     * ya cacheado, sin llamadas nuevas a ERP/PrestaShop.
     */
    private function ticketContextNote(Customer $customer): string
    {
        $resumen = $this->resumen($customer);
        $stats = $resumen['stats'] ?? [];
        $lifetime = $stats['lifetime'] ?? [];

        $lines = ['Contexto de la ficha 360 (CT-'.$customer->id.')'];
        $lines[] = 'Contacto: '.collect([$customer->name, $customer->email, $customer->phone ?: $customer->whatsapp_phone])->filter()->implode(' · ');

        if (! empty($lifetime['totalSpent'])) {
            $lines[] = 'Valor de vida: '.number_format((float) $lifetime['totalSpent'], 2, ',', '.').' € · '.($lifetime['ordersCount'] ?? 0).' pedidos';
        }

        $lines[] = 'Conversaciones: '.($stats['totalConversations'] ?? 0).' ('.($stats['openConversations'] ?? 0).' abiertas) · tickets: '.($stats['ticketsCount'] ?? 0);

        $linked = collect($resumen['integrations'] ?? [])
            ->filter(fn ($it) => ($it['connected'] ?? false) && ! empty($it['externalId']))
            ->map(fn ($it) => ($it['label'] ?? $it['platform']).' '.$it['externalId']);
        if ($linked->isNotEmpty()) {
            $lines[] = 'Vinculado: '.$linked->implode(' · ');
        }

        $tags = collect($resumen['tags'] ?? [])->pluck('name');
        if ($tags->isNotEmpty()) {
            $lines[] = 'Etiquetas: '.$tags->implode(', ');
        }

        if (! empty($resumen['owner']['name'])) {
            $lines[] = 'Responsable: '.$resumen['owner']['name'];
        }

        $lines[] = 'Ficha: '.route('contacts.show', $customer);

        return implode("\n", $lines);
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
    /**
     * @param  ?string  $platform  'erp'|'prestashop' para sincronizar solo esa
     *                             fuente (botón "Reintentar" del modal de sync,
     *                             cuando solo una tiene error), null para ambas
     *                             (comportamiento por defecto, "Sincronizar todo").
     */
    public function syncIntegrations(Customer $customer, ?string $platform = null): array
    {
        if ($this->erpAvailable() && ($platform === null || $platform === 'erp')) {
            // Mismo buscador que la vinculación automática del chat: email →
            // teléfono → email de PrestaShop (antes solo email). Guarda el
            // vínculo o deja registrado not_found/error en el contacto.
            if (class_exists(self::ERP_LINKER)) {
                $customer->unsetRelation('externalIds');
                app(self::ERP_LINKER)->linkCustomer($customer);
            } elseif ($customer->email) {
                app(self::ERP_CONTEXT_SERVICE)->getCustomerContext($customer->email, null, $customer->id);
            }
        }

        if ($customer->email && $this->prestashopAvailable() && ($platform === null || $platform === 'prestashop')) {
            // Si ya estaba vinculado por external_id (email del contacto
            // distinto del de su cuenta PrestaShop), reverificar SOLO por
            // email lo desvincularía de facto al no encontrar nada — se pasa
            // el id ya conocido para que la reverificación lo confirme.
            $knownExternalId = $customer->externalIdFor('prestashop');

            $context = app(self::PRESTASHOP_CONTEXT_SERVICE)
                ->getCustomerContext($customer->email, null, $knownExternalId !== null ? (int) $knownExternalId : null);

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
     * @param  array<string, int>  $skuToProductId  precomputed by resolveLocalProductIdsMap()
     * @return array{updatedAt: ?string, itemsCount: int, total: float, recoverable: bool, lines: array<int, array{productId: ?int, name: string, qty: int}>}
     */
    private function mapAbandonedCart(mixed $cart, array $skuToProductId): array
    {
        $rawItems = is_array($cart->items ?? null) ? $cart->items : [];
        $lines = [];
        $recoverable = $rawItems !== [];

        foreach ($rawItems as $item) {
            $sku = is_array($item) ? ($item['sku'] ?? null) : null;
            $productId = is_string($sku) ? ($skuToProductId[$sku] ?? null) : null;

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
     * Collects every line-item SKU across a batch of abandoned carts, so they
     * can be resolved to local product ids with a single query instead of one
     * per line (see resolveLocalProductIdsMap()).
     *
     * @param  iterable<int, mixed>  $carts
     * @return array<int, string>
     */
    private function collectCartSkus(iterable $carts): array
    {
        $skus = [];

        foreach ($carts as $cart) {
            $rawItems = is_array($cart->items ?? null) ? $cart->items : [];

            foreach ($rawItems as $item) {
                $sku = is_array($item) ? ($item['sku'] ?? null) : null;
                if (is_string($sku) && $sku !== '') {
                    $skus[] = $sku;
                }
            }
        }

        return array_values(array_unique($skus));
    }

    /**
     * Resolve a batch of Remarketing item SKUs to local Ecommerce product ids
     * in one query, matching either the products.sku or products.reference
     * column. Guarded — returns an empty map when Ecommerce is unavailable or
     * no SKU was given.
     *
     * @param  array<int, string>  $skus
     * @return array<string, int> the requested value (sku or reference) => product id
     */
    private function resolveLocalProductIdsMap(array $skus): array
    {
        if ($skus === [] || ! $this->ecommerceAvailable()) {
            return [];
        }

        try {
            $rows = app(self::ECOMMERCE_PRODUCT)->newQuery()
                ->whereIn('sku', $skus)
                ->orWhereIn('reference', $skus)
                ->get(['id', 'sku', 'reference']);
        } catch (\Throwable) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            if ($row->sku !== null && in_array($row->sku, $skus, true)) {
                $map[$row->sku] = (int) $row->id;
            }
            if ($row->reference !== null && in_array($row->reference, $skus, true)) {
                $map[$row->reference] = (int) $row->id;
            }
        }

        return $map;
    }

    private function ecommerceAvailable(): bool
    {
        return $this->moduleEnabled('Ecommerce')
            && class_exists(self::ECOMMERCE_PRODUCT);
    }

    /* ── Resumen helpers ──────────────────────────────────────────────────── */

    /**
     * Valor de vida del cliente ("Valor" del listado, "Valor de vida" de la
     * ficha, columna Valor del export). Cascada SIN red, en este orden:
     *
     *   1. Remarketing (espejo local por email) — solo si el módulo existe y
     *      el cliente tiene pedidos ahí.
     *   2. Contexto PrestaShop YA CACHEADO — customer.ltv del bridge (misma
     *      base que la suma de sus pedidos) o, si el contexto viene del
     *      fallback customer.orders (sin ltv), la suma de orders[].totals.total.
     *   3. Contexto ERP YA CACHEADO — customer.balance_invoiced (facturado
     *      total histórico con impuestos, sin filtro de fecha en el ERP).
     *
     * Nunca lanza HTTP: el listado lo llama por fila (hasta 100) y el ERP
     * tarda hasta 15 s cuando está caído, así que solo se LEE lo que otra
     * petición ya cacheó (PS ~5 min, ERP ~10 min, ver los peekCachedContext()
     * de cada servicio). Sin dato cacheado devuelve 0 con source null: nunca
     * se inventa un total. La ficha completa el hueco en cliente con los
     * payloads de /tab/prestashop y /tab/erp (renderCtfMetrics).
     *
     * Público para que el listado y el export lo reutilicen tal cual.
     *
     * `partial` = el total es la suma de menos pedidos de los que el cliente
     * tiene realmente (solo ocurre con el contexto PrestaShop de respaldo).
     *
     * @return array{ordersCount: int, totalSpent: float, currency: string, source: 'remarketing'|'prestashop'|'erp'|null, partial: bool}
     */
    public function lifetimeOrders(Customer $customer): array
    {
        $empty = ['ordersCount' => 0, 'totalSpent' => 0.0, 'currency' => 'EUR', 'source' => null, 'partial' => false];

        if (! $customer->email) {
            return $empty;
        }

        return $this->lifetimeFromRemarketing($customer)
            ?? $this->lifetimeFromCachedPrestashop($customer)
            ?? $this->lifetimeFromCachedErp($customer)
            ?? $empty;
    }

    /**
     * @return array{ordersCount: int, totalSpent: float, currency: string, source: string, partial: bool}|null
     */
    private function lifetimeFromRemarketing(Customer $customer): ?array
    {
        if (! $this->remarketingAvailable()) {
            return null;
        }

        $remarketingCustomer = app(self::REMARKETING_CUSTOMER);
        $orderModel = app(self::REMARKETING_ORDER);

        $customerIds = $remarketingCustomer->newQuery()
            ->where('email', strtolower($customer->email))
            ->pluck('id');

        if ($customerIds->isEmpty()) {
            return null;
        }

        $stats = $orderModel->newQuery()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('COUNT(*) as orders_count, SUM(total) as total_spent')
            ->first();

        $ordersCount = (int) ($stats->orders_count ?? 0);

        // Sin pedidos en Remarketing → seguir la cascada en vez de fijar un 0.
        if ($ordersCount === 0) {
            return null;
        }

        $latestCurrency = $orderModel->newQuery()
            ->whereIn('customer_id', $customerIds)
            ->latest('placed_at')
            ->value('currency');

        return [
            'ordersCount' => $ordersCount,
            'totalSpent' => (float) ($stats->total_spent ?? 0.0),
            'currency' => $latestCurrency ?? 'EUR',
            'source' => 'remarketing',
            'partial' => false,
        ];
    }

    /**
     * @return array{ordersCount: int, totalSpent: float, currency: string, source: string, partial: bool}|null
     */
    private function lifetimeFromCachedPrestashop(Customer $customer): ?array
    {
        if (! $this->prestashopAvailable()) {
            return null;
        }

        try {
            $context = app(self::PRESTASHOP_CONTEXT_SERVICE)->peekCachedContext($customer->email);
        } catch (\Throwable) {
            return null; // Redis caído o similar: degradar a "sin dato", no romper el listado.
        }

        if (! is_array($context) || ! ($context['customer']['found'] ?? false)) {
            return null;
        }

        $orders = is_array($context['orders'] ?? null) ? $context['orders'] : [];
        $ordersCount = (int) ($context['customer']['orders_count'] ?? count($orders));
        $ltv = $context['customer']['ltv'] ?? null;

        if (is_numeric($ltv)) {
            $total = (float) $ltv;
            $partial = false;
        } else {
            // Contexto reconstruido desde customer.orders (helpdesk_context
            // caído): sin ltv, solo llegan hasta 10 pedidos — la suma puede
            // quedarse corta si el cliente tiene más, y se marca como parcial.
            $total = array_sum(array_map(fn ($o): float => (float) ($o['totals']['total'] ?? 0), $orders));
            $partial = $ordersCount > count($orders);
        }

        if ($total <= 0 && $ordersCount === 0) {
            return null;
        }

        $currency = (string) ($orders[0]['currency'] ?? 'EUR');

        return [
            'ordersCount' => $ordersCount,
            'totalSpent' => round($total, 2),
            'currency' => strlen($currency) === 3 ? strtoupper($currency) : 'EUR',
            'source' => 'prestashop',
            'partial' => $partial,
        ];
    }

    /**
     * @return array{ordersCount: int, totalSpent: float, currency: string, source: string, partial: bool}|null
     */
    private function lifetimeFromCachedErp(Customer $customer): ?array
    {
        if (! $this->erpAvailable()) {
            return null;
        }

        try {
            $context = app(self::ERP_CONTEXT_SERVICE)->peekCachedContext($customer->email);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($context) || ! ($context['customer']['found'] ?? false)) {
            return null;
        }

        $invoiced = $context['customer']['balance_invoiced'] ?? null;

        if (! is_numeric($invoiced) || (float) $invoiced <= 0) {
            return null;
        }

        return [
            'ordersCount' => is_array($context['orders'] ?? null) ? count($context['orders']) : 0,
            'totalSpent' => round((float) $invoiced, 2),
            'currency' => 'EUR',
            'source' => 'erp',
            'partial' => false,
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
        return [...$this->externalIntegrationStatuses($customer), $this->webchatStatus($customer)];
    }

    /**
     * "Chat web" como fuente de la ficha (mockup "Fuentes vinculadas"): a
     * diferencia de ERP/PrestaShop no es una plataforma externa vinculable
     * (sin external_id ni sync — por eso el JS no le ofrece "Reintentar"), es la
     * huella real del widget del propio Helpdesk: visitas de página
     * (helpdesk_page_visits) y conversaciones con channel='web'. Conectado si
     * hay alguna de las dos; lastSyncedAt = la más reciente. Cada consulta es un
     * MAX() indexado por customer_id, y cualquier fallo (tabla ausente en
     * algún entorno) degrada a "sin actividad" en vez de romper el resumen.
     *
     * @return array{platform: string, label: string, connected: bool, externalId: null, syncStatus: null, lastSyncedAt: ?string}
     */
    private function webchatStatus(Customer $customer): array
    {
        $dates = [];

        try {
            $dates[] = $customer->pageVisits()->max('created_at');
            $dates[] = $customer->conversations()->where('channel', 'web')->max('created_at');
        } catch (\Throwable) {
            // sin datos de widget en este entorno
        }

        $latest = collect($dates)->filter()->map(fn ($d) => Carbon::parse($d))->max();

        return [
            'platform' => 'webchat',
            'label' => 'Chat web',
            'connected' => $latest !== null,
            'externalId' => null,
            'syncStatus' => null,
            'lastSyncedAt' => $latest?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array{platform: string, label: string, connected: bool, externalId: ?string, syncStatus: ?string, lastSyncedAt: ?string}>
     */
    private function externalIntegrationStatuses(Customer $customer): array
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
     * Fallback local (sin ERP): conversaciones/CSAT/etiquetas de conversación
     * son todos eventos del propio Helpdesk, así que llevan source 'helpdesk'
     * — sin él quedarían fuera del filtro "Mensajes" del Historial.
     *
     * @return array<int, array{type: string, title: string, detail: string, at: string, icon: string, source: string}>
     */
    private function timeline(Customer $customer): array
    {
        return array_map(fn (array $event): array => [
            'type' => $event['type'],
            'title' => $event['label'],
            'detail' => $event['description'],
            'at' => $event['occurred_at'],
            'icon' => $this->timelineIcon($event['type']),
            'source' => 'helpdesk',
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
            || ! $this->moduleEnabled('HelpdeskEmailActivity')
            || ! class_exists(self::EMAIL_LOG)) {
            return [];
        }

        $email = strtolower($customer->email);
        $model = app(self::EMAIL_LOG);

        // MATCH AGAINST usa el indice FULLTEXT de recipients_index en modo
        // BOOLEAN con el email entre comillas (frase exacta): el modo NATURAL
        // LANGUAGE por defecto puntua coincidencias parciales de cualquier
        // destinatario y el LIKE '%...%' que llevaba de fallback anulaba el
        // indice por completo, degradando a listar los 20 email logs mas
        // recientes de TODOS los clientes cuando no habia match exacto.
        return $model->newQuery()
            ->whereRaw('MATCH(recipients_index) AGAINST (? IN BOOLEAN MODE)', ['"'.$email.'"'])
            ->latest('created_at')
            ->limit(20)
            ->get()
            // Cinturon de seguridad: BOOLEAN MODE con frase exacta ya no
            // deberia devolver destinatarios ajenos, pero se descarta
            // explicitamente cualquier fila cuyo recipients_index no
            // contenga el email literal antes de exponerla.
            ->filter(fn ($log): bool => str_contains(strtolower((string) $log->recipients_index), $email))
            ->map(fn ($log): array => [
                'subject' => $log->subject ?? '(sin asunto)',
                'status' => $log->status_label,
                'statusClass' => $log->status_color,
                'at' => $log->display_date?->toIso8601String(),
                'url' => $log->entity_url,
            ])->values()->all();
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

    /**
     * IDs de contactos con un posible duplicado dentro del scope visible del
     * agente (forAgent() ya aplicado antes de agrupar, no después — un
     * agente restringido no debe ver "duplicado" a un contacto que ni
     * siquiera puede abrir). Coincidencia por email exacto (case-insensitive)
     * o por los últimos 9 dígitos del teléfono/whatsapp — mismo criterio de
     * "cola de dígitos" que ya usa Customer::findByWhatsappPhone() para
     * tolerar los tres formatos reales que conviven en la tabla (con/sin
     * prefijo de país, con/sin espacios). Nunca carga la tabla completa en
     * PHP: son 3 consultas agregadas (GROUP BY ... HAVING COUNT(*) > 1),
     * cada una acotada al scope del agente antes de agrupar.
     *
     * @return array<int, int>
     */
    public function duplicateCustomerIds(User $agent): array
    {
        return Cache::remember(
            "helpdeskcontacts:duplicates:{$agent->id}",
            now()->addMinutes(5),
            function () use ($agent): array {
                $scoped = fn () => Customer::query()->forAgent($agent);

                $byEmail = $scoped()
                    ->whereNotNull('email')->where('email', '!=', '')
                    ->selectRaw('LOWER(email) as key_value, GROUP_CONCAT(id) as ids')
                    ->groupBy('key_value')
                    ->havingRaw('COUNT(*) > 1')
                    ->pluck('ids');

                $byPhone = $scoped()
                    ->whereNotNull('phone')->where('phone', '!=', '')
                    ->selectRaw("RIGHT(REGEXP_REPLACE(phone, '[^0-9]', ''), 9) as key_value, GROUP_CONCAT(id) as ids")
                    ->groupBy('key_value')
                    ->havingRaw("key_value != '' AND COUNT(*) > 1")
                    ->pluck('ids');

                $byWhatsapp = $scoped()
                    ->whereNotNull('whatsapp_phone')->where('whatsapp_phone', '!=', '')
                    ->selectRaw("RIGHT(REGEXP_REPLACE(whatsapp_phone, '[^0-9]', ''), 9) as key_value, GROUP_CONCAT(id) as ids")
                    ->groupBy('key_value')
                    ->havingRaw("key_value != '' AND COUNT(*) > 1")
                    ->pluck('ids');

                $ids = [];
                foreach ($byEmail->merge($byPhone)->merge($byWhatsapp) as $csv) {
                    foreach (explode(',', (string) $csv) as $id) {
                        $ids[(int) $id] = true;
                    }
                }

                return array_keys($ids);
            },
        );
    }

    /**
     * "Con quién" es duplicado cada contacto de la página actual del listado
     * (vista "Posibles duplicados": "Mismo teléfono que X" / "Mismo email que X").
     *
     * Número FIJO de consultas sea cual sea el tamaño de la página (la de
     * forAgent() + UNA sola búsqueda de los "otros" contactos que comparten
     * email o cola de 9 dígitos con alguno de los de la página); el resto se
     * resuelve en memoria — nunca una consulta por fila.
     *
     * Mismo criterio que duplicateCustomerIds(), para que el motivo sea
     * coherente con qué contactos entran en la vista: email exacto
     * (case-insensitive), y los últimos 9 dígitos comparados COLUMNA CON
     * COLUMNA (phone con phone, whatsapp_phone con whatsapp_phone — igual que
     * los dos GROUP BY de allí, sin cruzar una con otra). Prioridad: email,
     * luego teléfono, luego WhatsApp (que se etiqueta también como "teléfono").
     * Si varios contactos coinciden, se cita el de menor id (orden estable).
     *
     * Un id puede faltar en el resultado (p. ej. la caché de 5 min de
     * duplicateCustomerIds() aún lo incluye pero su duplicado ya se borró o
     * cambió): quien lo pinte debe tolerarlo con un texto genérico.
     *
     * @param  iterable<int, Customer>  $customers  solo los de la página actual
     * @return array<int, string> [customer_id => motivo]
     */
    public function duplicateReasonsFor(iterable $customers, User $agent): array
    {
        return array_map(fn (array $m): string => $m['reason'], $this->duplicateMatchesFor($customers, $agent));
    }

    /**
     * Igual que duplicateReasonsFor() pero con el id del contacto con el que
     * coincide (el de menor id, que es el que se conserva al fusionar desde el
     * botón "Fusionar" de la fila del listado).
     *
     * @param  iterable<int, Customer>  $customers
     * @return array<int, array{reason: string, partnerId: int}>
     */
    public function duplicateMatchesFor(iterable $customers, User $agent): array
    {
        $emails = [];
        $phoneTails = [];
        $whatsappTails = [];
        $page = [];

        foreach ($customers as $customer) {
            $page[] = $customer;

            $email = mb_strtolower(trim((string) $customer->email));
            if ($email !== '') {
                $emails[$email] = true;
            }
            if (($tail = $this->phoneTail($customer->phone)) !== '') {
                $phoneTails[$tail] = true;
            }
            if (($tail = $this->phoneTail($customer->whatsapp_phone)) !== '') {
                $whatsappTails[$tail] = true;
            }
        }

        if ($emails === [] && $phoneTails === [] && $whatsappTails === []) {
            return [];
        }

        $phoneSql = "RIGHT(REGEXP_REPLACE(phone, '[^0-9]', ''), 9)";
        $whatsappSql = "RIGHT(REGEXP_REPLACE(whatsapp_phone, '[^0-9]', ''), 9)";
        $marks = fn (array $keys): string => implode(',', array_fill(0, count($keys), '?'));

        $others = Customer::query()
            ->forAgent($agent)
            ->where(function ($q) use ($emails, $phoneTails, $whatsappTails, $phoneSql, $whatsappSql, $marks): void {
                if ($emails !== []) {
                    $keys = array_map('strval', array_keys($emails));
                    $q->orWhereRaw('LOWER(email) IN ('.$marks($keys).')', $keys);
                }
                if ($phoneTails !== []) {
                    $keys = array_map('strval', array_keys($phoneTails));
                    $q->orWhereRaw("{$phoneSql} IN (".$marks($keys).')', $keys);
                }
                if ($whatsappTails !== []) {
                    $keys = array_map('strval', array_keys($whatsappTails));
                    $q->orWhereRaw("{$whatsappSql} IN (".$marks($keys).')', $keys);
                }
            })
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'phone', 'whatsapp_phone']);

        // Índices en memoria: clave de coincidencia → contactos que la tienen.
        $byEmail = [];
        $byPhone = [];
        $byWhatsapp = [];
        foreach ($others as $other) {
            if (($key = mb_strtolower(trim((string) $other->email))) !== '') {
                $byEmail[$key][] = $other;
            }
            if (($key = $this->phoneTail($other->phone)) !== '') {
                $byPhone[$key][] = $other;
            }
            if (($key = $this->phoneTail($other->whatsapp_phone)) !== '') {
                $byWhatsapp[$key][] = $other;
            }
        }

        $reasons = [];
        foreach ($page as $customer) {
            $candidates = [
                ['email', $byEmail[mb_strtolower(trim((string) $customer->email))] ?? []],
                ['phone', $byPhone[$this->phoneTail($customer->phone)] ?? []],
                ['phone', $byWhatsapp[$this->phoneTail($customer->whatsapp_phone)] ?? []],
            ];

            foreach ($candidates as [$kind, $matches]) {
                $match = collect($matches)->first(fn (Customer $m): bool => $m->id !== $customer->id);

                if ($match !== null) {
                    $who = trim((string) $match->name) !== '' ? $match->name : 'otro contacto';
                    $reasons[$customer->id] = [
                        'reason' => ($kind === 'email' ? 'Mismo email que ' : 'Mismo teléfono que ').$who,
                        'partnerId' => (int) $match->id,
                    ];

                    break;
                }
            }
        }

        return $reasons;
    }

    /**
     * Últimos 9 dígitos de un teléfono — la misma "cola de dígitos" que calcula
     * en SQL duplicateCustomerIds() con RIGHT(REGEXP_REPLACE(col,'[^0-9]',''),9).
     */
    private function phoneTail(?string $value): string
    {
        return substr((string) preg_replace('/\D/', '', (string) $value), -9);
    }
}
