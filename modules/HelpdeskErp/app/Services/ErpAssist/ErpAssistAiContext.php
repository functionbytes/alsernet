<?php

namespace Modules\HelpdeskErp\Services\ErpAssist;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatCustomerResolver;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatOverview;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatSections;

/**
 * Resumen breve de Gestión (ERP) para el prompt de las sugerencias de IA.
 *
 * - SOLO caché (ErpAssistCachedData): nunca hace esperar a la sugerencia.
 *   Si el inbox aún no calentó el resumen del cliente, no se añade nada.
 * - Exige helpdeskerp.view + helpdeskerp.orders.view; los puntos, además,
 *   helpdeskerp.loyalty.view.
 * - Sin datos sensibles: ni NIF, ni tarjetas (bancarias ni de fidelización),
 *   ni IBAN, ni email/teléfono, ni importes de deuda o riesgo, ni
 *   observaciones libres del ERP.
 */
class ErpAssistAiContext
{
    /** Estados reales de pedido en Oracle (mismos que ErpChat.STATUS en erp-chat.js). */
    public const STATUS = [
        0 => 'Anulado', 1 => 'Creación', 2 => 'Revisión transportista', 3 => 'Aceptación financiera',
        4 => 'Pendiente de mercancía', 5 => 'Listo para servir', 6 => 'Sirviéndose', 7 => 'Servido',
        8 => 'Incidencia', 9 => 'Aceptación financiera reservando', 10 => 'Servido parcialmente', 11 => 'Pendiente transferencia',
    ];

    /** Avisos del resumen que se pueden contar a la IA (nada financiero). */
    private const SAFE_ALERTS = ['inactive', 'no_commercial_consent', 'order_served', 'voucher_expiring', 'bonus_expiring'];

    public function __construct(
        private readonly ErpAssistCachedData $cache,
        private readonly ErpChatCustomerResolver $resolver,
        private readonly ErpChatOverview $overview,
    ) {}

    public function forConversation(Conversation $conversation, mixed $agent): ?string
    {
        if (! function_exists('helpdesk_erp_enabled') || ! helpdesk_erp_enabled()) {
            return null;
        }

        if (! $agent instanceof Authorizable
            || ! $agent->can(ErpChatSections::PERM_VIEW)
            || ! $agent->can(ErpChatSections::PERM_ORDERS)) {
            return null;
        }

        $customer = $conversation->customer;
        if ($customer === null) {
            return null;
        }

        try {
            $erpId = $this->resolver->erpIdFor($customer);
        } catch (\Throwable) {
            return null;
        }

        if ($erpId === null) {
            return null;
        }

        $canLoyalty = $agent->can(ErpChatSections::PERM_LOYALTY);

        return $this->build($erpId, $canLoyalty);
    }

    /**
     * Texto del contexto a partir de lo cacheado, o null si no hay nada útil.
     */
    public function build(int $erpId, bool $canLoyalty): ?string
    {
        $summaryRaw = $this->cache->peek($erpId, 'summary');
        $ordersRaw = $this->cache->peek($erpId, 'orders', $this->cache->overviewOrdersParams());
        $pointsRaw = $canLoyalty ? $this->cache->peek($erpId, 'loyalty-points') : null;

        $lines = [];

        $summary = ($summaryRaw['state'] ?? null) === 'ok' && is_array($summaryRaw['data'] ?? null) ? $summaryRaw['data'] : null;
        if ($summary !== null) {
            $name = trim(((string) ($summary['label'] ?? '')).' '.((string) ($summary['surnames'] ?? '')));
            if ($name !== '') {
                $lines[] = '- Nombre en Gestión: '.$this->titleCase($name);
            }
        }

        $orders = ($ordersRaw['state'] ?? null) === 'ok' && is_array($ordersRaw['data'] ?? null) ? array_values(array_filter($ordersRaw['data'], 'is_array')) : null;
        if ($orders !== null) {
            $hasMore = (bool) ($ordersRaw['pagination']['has_more'] ?? $ordersRaw['pagination']['hasMore'] ?? false);
            $count = count($orders);
            $lines[] = '- Pedidos en Gestión: '.($count === 0 ? 'ninguno' : ($hasMore ? 'más de '.$count : (string) $count));

            $last = $this->latestOrder($orders);
            if ($last !== null) {
                $lines[] = '- '.$this->orderLine($last);
            }
        } elseif (($ordersRaw['state'] ?? null) === 'loading') {
            $lines[] = '- Pedidos en Gestión: consultándose todavía.';
        }

        $points = ($pointsRaw['state'] ?? null) === 'ok' && is_array($pointsRaw['data'] ?? null) ? $pointsRaw['data'] : null;
        if ($points !== null && is_numeric($points['balance'] ?? null)) {
            $lines[] = '- Puntos de fidelización: '.(int) $points['balance'];
        }

        $alerts = $this->alerts($summaryRaw, $ordersRaw, $canLoyalty ? $pointsRaw : null);
        if ($alerts !== []) {
            $lines[] = '- Avisos: '.implode(' ', $alerts);
        }

        if ($lines === []) {
            return null;
        }

        return "Datos de Gestión (ERP) del cliente, solo como contexto interno (no inventes datos que no estén aquí):\n"
            .implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $orders
     * @return array<string, mixed>|null
     */
    public function latestOrder(array $orders): ?array
    {
        $best = null;
        $bestTs = null;

        foreach ($orders as $order) {
            $ts = $this->parseDate($order['date'] ?? null)?->getTimestamp() ?? 0;
            if ($best === null || $ts > $bestTs) {
                $best = $order;
                $bestTs = $ts;
            }
        }

        return $best;
    }

    public static function statusLabel(mixed $code): string
    {
        if ($code === null || $code === '') {
            return 'sin estado';
        }

        if (is_bool($code)) {
            return $code ? 'Activo' : 'Anulado';
        }

        $s = trim((string) $code);

        if (ctype_digit($s)) {
            return self::STATUS[(int) $s] ?? 'Estado '.$s;
        }

        return mb_convert_case(mb_strtolower($s), MB_CASE_TITLE);
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function orderLine(array $order): string
    {
        $number = (string) ($order['number'] ?? $order['id'] ?? '');
        $date = $this->parseDate($order['date'] ?? null);
        $served = $this->parseDate($order['served_date'] ?? null);

        $text = 'Último pedido: nº '.$number
            .($date ? ' del '.$date->format('d/m/Y') : '')
            .', estado '.(is_string($order['status_description'] ?? null) && trim($order['status_description']) !== ''
                ? self::statusLabel($order['status_description'])
                : self::statusLabel($order['status'] ?? null));

        $text .= $served ? ', servido el '.$served->format('d/m/Y') : ', todavía sin fecha de servido';

        $expected = $this->parseDate($order['expected_date'] ?? null);
        if (! $served && $expected) {
            $text .= ' (previsto el '.$expected->format('d/m/Y').')';
        }

        return $text.'.';
    }

    /**
     * @return list<string>
     */
    private function alerts(?array $summary, ?array $orders, ?array $points): array
    {
        try {
            $all = $this->overview->alerts([
                'summary' => $summary,
                'orders' => $orders,
                'loyalty_points' => $points,
                'balance' => null,
                'debts' => null,
                'vouchers' => null,
                'bonuses' => null,
            ]);
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        foreach ($all as $alert) {
            if (in_array($alert['code'] ?? null, self::SAFE_ALERTS, true) && filled($alert['text'] ?? null)) {
                $out[] = (string) $alert['text'];
            }
        }

        return array_values(array_unique($out));
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function titleCase(string $name): string
    {
        return mb_strtoupper($name) === $name ? mb_convert_case(mb_strtolower($name), MB_CASE_TITLE) : $name;
    }
}
