<?php

namespace Modules\HelpdeskErp\Services\ErpChat;

use Carbon\CarbonImmutable;
use Modules\Helpdesk\Models\Customer;

/**
 * Resumen de Gestión para el panel derecho en UNA ida al manager (pool):
 * cada sección con su estado, más las alertas calculadas y los vínculos con
 * otras plataformas. Solo se piden las secciones que el agente puede ver;
 * las demás salen con state 'forbidden' y sin datos.
 */
class ErpChatOverview
{
    /** clave del resumen => [sección, permisos (cualquiera)] */
    private const ITEMS = [
        'summary' => ['summary', []],
        'addresses' => ['addresses', [ErpChatSections::PERM_ADDRESSES]],
        'orders' => ['orders', [ErpChatSections::PERM_ORDERS]],
        'loyalty_points' => ['loyalty-points', [ErpChatSections::PERM_LOYALTY]],
        'balance' => ['balance', [ErpChatSections::PERM_FINANCE]],
        'debts' => ['debts', [ErpChatSections::PERM_FINANCE]],
        'vouchers' => ['vouchers', [ErpChatSections::PERM_LOYALTY]],
        'bonuses' => ['bonuses', [ErpChatSections::PERM_LOYALTY]],
    ];

    public function __construct(
        private readonly ErpChatService $service,
        private readonly ErpChatResponseNormalizer $normalizer,
    ) {}

    /**
     * @param  callable(string): bool  $can
     * @return array<string, mixed>
     */
    public function build(Customer $customer, int $erpId, callable $can, bool $fresh = false): array
    {
        $requests = [];
        $sections = [];

        foreach (self::ITEMS as $key => [$section, $perms]) {
            if ($perms !== [] && ! $this->canAny($can, $perms)) {
                $sections[$key] = $this->normalizer->make('forbidden', null, 'Sin permiso para ver esta sección.', 'forbidden');

                continue;
            }

            $params = $section === 'orders'
                ? ['limit' => (int) config('helpdeskErp.chat_overview_orders_limit', 10), 'offset' => 0]
                : [];

            $requests[$key] = [$section, $params];
        }

        $sections = array_merge($sections, $this->service->many($erpId, $requests, $fresh));

        // La caché es compartida: lo que este agente no puede ver se recorta aquí.
        foreach ($requests as $key => [$section]) {
            if (is_array($sections[$key] ?? null)) {
                $sections[$key] = ErpChatSections::redactFor($section, $sections[$key], $can);
            }
        }
        $sections = array_replace(array_fill_keys(array_keys(self::ITEMS), null), $sections);

        $summary = $sections['summary'];
        $orders = $sections['orders'];

        $realtime = null;
        if (($orders['state'] ?? null) === 'loading') {
            $email = strtolower(trim((string) $customer->email));
            $realtime = [
                'channel' => $email !== '' ? 'erp-orders-ready.'.md5($email) : null,
                'event' => '.erp.orders.ready',
                'retry_after' => (int) ($orders['retry_after'] ?? 35),
            ];
        }

        $codeInternet = $summary['state'] === 'ok' ? ($summary['data']['code_internet'] ?? null) : null;

        return [
            'state' => $summary['state'],
            'erp_id' => $erpId,
            'customer_id' => $customer->id,
            'sections' => $sections,
            'alerts' => $this->alerts($sections),
            'links' => [
                'erp_customer_id' => $erpId,
                'prestashop_customer_id' => filled($codeInternet) ? (string) $codeInternet : null,
            ],
            'realtime' => $realtime,
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Alertas para el agente. Nivel: 'warn' (requiere atención), 'info',
     * 'good'. action indica qué abrir: {open, pane} o {open: 'order', order_id}.
     *
     * @param  array<string, array<string, mixed>|null>  $s
     * @return list<array<string, mixed>>
     */
    public function alerts(array $s): array
    {
        $alerts = [];
        $ok = fn (?array $item): bool => ($item['state'] ?? null) === 'ok' && is_array($item['data'] ?? null);

        // Cliente dado de baja: el manager filtra FBAJA y responde 404.
        $summary = $s['summary'] ?? null;
        if (($summary['state'] ?? null) === 'unavailable' && ($summary['reason'] ?? null) === 'not_found') {
            $alerts[] = $this->alert('inactive', 'warn', 'El cliente está dado de baja o ya no existe en Gestión.');
        } elseif ($ok($summary) && $this->isOff($summary['data']['available'] ?? true)) {
            $alerts[] = $this->alert('inactive', 'warn', 'El cliente figura como inactivo en Gestión.');
        }

        // Riesgo superado (balance o deudas traen el riesgo del cliente).
        $risk = null;
        foreach (['balance', 'debts'] as $key) {
            if ($risk === null && $ok($s[$key] ?? null) && is_array($s[$key]['data']['risk'] ?? null)) {
                $risk = $s[$key]['data']['risk'];
            }
        }
        if ($risk !== null) {
            $current = (float) ($risk['current'] ?? 0);
            $max = (float) ($risk['max_allowed'] ?? 0);
            if ($max > 0 && $current > $max) {
                $alerts[] = $this->alert('risk_exceeded', 'warn',
                    'Riesgo superado: '.$this->money($current).' de '.$this->money($max).' permitidos.',
                    ['open' => 'finance', 'pane' => 'debts']);
            }
        }

        // Deuda pendiente.
        $debtAmount = null;
        $debtCount = null;
        if ($ok($s['debts'] ?? null)) {
            $debtAmount = (float) ($s['debts']['data']['statistics']['debts']['amount_total'] ?? 0);
            $debtCount = (int) ($s['debts']['data']['statistics']['debts']['total'] ?? 0);
        } elseif ($ok($s['balance'] ?? null)) {
            $debtAmount = (float) ($s['balance']['data']['balance']['pending'] ?? 0);
        }
        if ($debtAmount !== null && $debtAmount > 0.004) {
            $text = 'Deuda pendiente: '.$this->money($debtAmount)
                .($debtCount ? ' ('.$debtCount.' '.($debtCount === 1 ? 'albarán' : 'albaranes').')' : '').'.';
            $alerts[] = $this->alert('pending_debt', 'warn', $text, ['open' => 'finance', 'pane' => 'debts']);
        }

        // LOPD: sin consentimiento comercial.
        if ($ok($summary) && is_array($summary['data']['lopd'] ?? null)) {
            $lopd = $summary['data']['lopd'];
            if (! empty($lopd['no_commercial_info'])) {
                $alerts[] = $this->alert('no_commercial_consent', 'info', 'No acepta información comercial (LOPD): no le ofrezcas promociones.');
            } elseif (array_key_exists('accepted', $lopd) && ! $lopd['accepted']) {
                $alerts[] = $this->alert('no_commercial_consent', 'info', 'Sin aceptación de la LOPD registrada en Gestión.');
            }
        }

        // Vales y bonos que caducan pronto.
        $days = (int) config('helpdeskErp.chat_expiry_warning_days', 7);
        foreach ([
            'vouchers' => ['vouchers', 'cancelled_at', 'vale', 'vales', 'vouchers'],
            'bonuses' => ['bonuses', 'consumed_at', 'bono', 'bonos', 'bonuses'],
        ] as $key => [$listKey, $spentKey, $one, $many, $pane]) {
            if (! $ok($s[$key] ?? null)) {
                continue;
            }

            $expiring = $this->expiring((array) ($s[$key]['data'][$listKey] ?? []), $spentKey, $days);

            if ($expiring === []) {
                continue;
            }

            $first = $expiring[0];
            $text = count($expiring) === 1
                ? ucfirst($one).($first['amount'] !== null ? ' de '.$this->money((float) $first['amount']) : '').' caduca '.$this->whenText($first['until']).'.'
                : count($expiring).' '.$many.' caducan en los próximos '.$days.' días (el primero '.$this->whenText($first['until']).').';

            $alerts[] = $this->alert($key === 'vouchers' ? 'voucher_expiring' : 'bonus_expiring', 'info', $text, ['open' => 'loyalty', 'pane' => $pane]);
        }

        // Pedido servido hoy o ayer.
        if ($ok($s['orders'] ?? null)) {
            $today = CarbonImmutable::today();
            foreach ((array) $s['orders']['data'] as $order) {
                $served = $this->parseDate($order['served_date'] ?? null);
                if ($served === null) {
                    continue;
                }
                $when = $served->isSameDay($today) ? 'hoy' : ($served->isSameDay($today->subDay()) ? 'ayer' : null);
                if ($when === null) {
                    continue;
                }
                $alerts[] = $this->alert('order_served', 'good',
                    'Pedido '.($order['number'] ?? $order['id'] ?? '').' servido '.$when.'.',
                    ['open' => 'order', 'order_id' => (string) ($order['id'] ?? '')]);
            }
        }

        return $alerts;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{amount: float|null, until: CarbonImmutable}>
     */
    private function expiring(array $items, string $spentKey, int $days): array
    {
        $today = CarbonImmutable::today();
        $limit = $today->addDays($days);
        $out = [];

        foreach ($items as $item) {
            if (! is_array($item) || $this->isOff($item['available'] ?? true) || ! empty($item[$spentKey])) {
                continue;
            }

            $until = $this->parseDate($item['valid_until'] ?? null);

            if ($until === null || $until->lt($today) || $until->gt($limit)) {
                continue;
            }

            $out[] = ['amount' => isset($item['amount']) ? (float) $item['amount'] : null, 'until' => $until];
        }

        usort($out, fn ($a, $b) => $a['until'] <=> $b['until']);

        return $out;
    }

    private function whenText(CarbonImmutable $date): string
    {
        $today = CarbonImmutable::today();

        if ($date->isSameDay($today)) {
            return 'hoy';
        }

        if ($date->isSameDay($today->addDay())) {
            return 'mañana';
        }

        return 'el '.$date->format('d/m');
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function isOff(mixed $flag): bool
    {
        return $flag === false || $flag === 0 || $flag === '0';
    }

    /**
     * @param  array<string, mixed>|null  $action
     * @return array<string, mixed>
     */
    private function alert(string $code, string $level, string $text, ?array $action = null): array
    {
        return ['code' => $code, 'level' => $level, 'text' => $text, 'action' => $action];
    }

    private function money(float $n): string
    {
        return number_format($n, 2, ',', '.').' €';
    }

    /**
     * @param  callable(string): bool  $can
     * @param  list<string>  $perms
     */
    private function canAny(callable $can, array $perms): bool
    {
        foreach ($perms as $perm) {
            if ($can($perm)) {
                return true;
            }
        }

        return false;
    }
}
