<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Models\Customer;
use Nwidart\Modules\Facades\Module;

/**
 * Agrega los datos de "Cliente 360" para el panel derecho del inbox: perfil,
 * resumen de soporte (Helpdesk) y pedidos reales vía ERP/PrestaShop.
 *
 * No depende del módulo Engagement: no existe en este proyecto (solo
 * degradaba con gracia a secciones vacías). Los pedidos se obtienen
 * directamente de ErpContextService/PrestashopContextService — los mismos
 * servicios que ya alimentan las pestañas "Gestión" y "Pedidos de tienda" —
 * en vez de pasar por el CustomerDataOrchestrator de Engagement.
 *
 * Los nombres de clase de los módulos opcionales se guardan como literales
 * de texto (nunca `use` ni `::class` al principio del archivo), igual que
 * en ContactAggregatorService, para que este módulo nunca se rompa si
 * HelpdeskErp/HelpdeskPrestashop están desactivados.
 */
class Customer360Service
{
    private const ERP_CONTEXT_SERVICE = 'Modules\\HelpdeskErp\\Services\\ErpContextService';

    private const PRESTASHOP_CONTEXT_SERVICE = 'Modules\\HelpdeskPrestashop\\Services\\PrestashopContextService';

    private const ERP_STATUS_LABELS = [
        0 => 'Pendiente',
        1 => 'Confirmado',
        2 => 'En preparación',
        3 => 'Enviado',
        5 => 'Entregado',
        7 => 'Servido',
        9 => 'Cancelado',
    ];

    /**
     * @return array{customer: array, helpdesk: array, orders: array}
     */
    public function aggregate(Customer $customer, bool $force = false): array
    {
        return [
            'customer' => $this->customerData($customer),
            'helpdesk' => $this->helpdeskData($customer),
            'orders' => $this->ordersData($customer, $force),
        ];
    }

    // ── Customer ──────────────────────────────────────────────────────────────

    /**
     * @return array{id: int, name: string, email: string|null, phone: string|null, avatar_url: string, created_at: string}
     */
    private function customerData(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'avatar_url' => $customer->getAvatarUrl(),
            'created_at' => $customer->created_at->toIso8601String(),
        ];
    }

    // ── Helpdesk ──────────────────────────────────────────────────────────────

    /**
     * @return array{total_conversations: int, open_conversations: int, avg_csat: float|null, recent_tickets: array}
     */
    private function helpdeskData(Customer $customer): array
    {
        $conversationStats = DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->where('customer_id', $customer->id)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN closed_at IS NULL THEN 1 ELSE 0 END) as open_count')
            ->first();

        $avgCsat = CsatRating::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('answered_at')
            ->avg('rating');

        return [
            'total_conversations' => (int) ($conversationStats->total ?? 0),
            'open_conversations' => (int) ($conversationStats->open_count ?? 0),
            'avg_csat' => $avgCsat !== null ? round((float) $avgCsat, 2) : null,
            'recent_tickets' => $this->recentTickets($customer),
        ];
    }

    /**
     * Carga los tickets recientes si la integración HelpdeskTickets está
     * activa. Pasa por TicketServiceContract — sin dependencia directa del
     * módulo HelpdeskTickets a nivel de import.
     *
     * @return array<int, array{id: int, ticket_number: string, subject: string, status: string, priority: string, created_at_human: string}>
     */
    private function recentTickets(Customer $customer): array
    {
        if (! helpdesk_tickets_enabled()) {
            return [];
        }

        return app(TicketServiceContract::class)
            ->getCustomerTickets($customer, 5)
            ->map(fn ($t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'status' => $t->status?->name ?? '—',
                'priority' => $t->priority,
                'created_at_human' => $t->created_at->diffForHumans(),
            ])
            ->all();
    }

    // ── Pedidos (ERP / PrestaShop) ───────────────────────────────────────────

    /**
     * @return array<int, array{platform: string, label: string, connected: bool, orders: array}>
     */
    private function ordersData(Customer $customer, bool $force): array
    {
        if (! $customer->email) {
            return [];
        }

        $customer->loadMissing('externalIds');

        $platforms = [];

        if ($this->erpAvailable()) {
            $platforms[] = $this->erpPlatform($customer, $force);
        }

        if ($this->prestashopAvailable()) {
            $platforms[] = $this->prestashopPlatform($customer, $force);
        }

        return $platforms;
    }

    private function erpPlatform(Customer $customer, bool $force): array
    {
        $service = app(self::ERP_CONTEXT_SERVICE);

        if ($force) {
            $service->forgetCache($customer->email);
        }

        $context = $service->getCustomerContext($customer->email, null, $customer->id);
        $externalId = $customer->externalIdFor('erp');

        return [
            'platform' => 'erp',
            'label' => 'Gestión (ERP)',
            'connected' => (bool) $externalId,
            'external_id' => $externalId,
            'orders' => $this->normalizeErpOrders($context['orders'] ?? []),
        ];
    }

    private function prestashopPlatform(Customer $customer, bool $force): array
    {
        $service = app(self::PRESTASHOP_CONTEXT_SERVICE);

        if ($force) {
            $service->forgetCache($customer->email);
        }

        $context = $service->getCustomerContext($customer->email);
        $externalId = $customer->externalIdFor('prestashop');

        return [
            'platform' => 'prestashop',
            'label' => 'PrestaShop',
            'connected' => (bool) $externalId,
            'external_id' => $externalId,
            'orders' => $this->normalizePrestashopOrders($context['orders'] ?? []),
        ];
    }

    /**
     * Mismo mapeo de campos que erp-inbox.js (pedido ERP: sin total, el
     * status es un código numérico de Oracle).
     */
    private function normalizeErpOrders(array $orders): array
    {
        return collect($orders)
            ->take(5)
            ->map(function ($o) {
                $statusCode = $o['status'] ?? null;
                $status = is_numeric($statusCode)
                    ? (self::ERP_STATUS_LABELS[(int) $statusCode] ?? 'Estado '.$statusCode)
                    : ($statusCode ?: 'Pedido');

                return [
                    'reference' => $o['number'] ?? $o['id'] ?? '—',
                    'status' => $status,
                    'date_human' => $this->formatOrderDate($o['date'] ?? null),
                    'total' => null,
                ];
            })
            ->all();
    }

    /**
     * Mismo mapeo de campos que right-panel-prestashop-tabs.blade.php (ya
     * verificado en producción: reference/state.name, totals.total con
     * fallback a totals.products para pedidos con descuento completo).
     */
    private function normalizePrestashopOrders(array $orders): array
    {
        return collect($orders)
            ->take(5)
            ->map(function ($o) {
                $status = $o['state']['name'] ?? ($o['status'] ?? 'Pendiente');

                $total = (float) ($o['totals']['total'] ?? $o['total'] ?? 0);
                if ($total <= 0 && isset($o['totals']['products'])) {
                    $total = (float) $o['totals']['products'];
                }

                return [
                    'reference' => $o['reference'] ?? $o['id'] ?? '—',
                    'status' => $status,
                    'date_human' => $this->formatOrderDate($o['placed_at'] ?? null),
                    'total' => $total > 0 ? number_format($total, 2, ',', '.') : null,
                ];
            })
            ->all();
    }

    private function formatOrderDate(?string $date): string
    {
        if (! $date) {
            return '—';
        }

        try {
            return Carbon::parse($date)->translatedFormat('d M Y');
        } catch (\Throwable) {
            return '—';
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function erpAvailable(): bool
    {
        return Module::find('HelpdeskErp')?->isEnabled() === true
            && class_exists(self::ERP_CONTEXT_SERVICE);
    }

    private function prestashopAvailable(): bool
    {
        return Module::find('HelpdeskPrestashop')?->isEnabled() === true
            && class_exists(self::PRESTASHOP_CONTEXT_SERVICE);
    }
}
