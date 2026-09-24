<?php

namespace Modules\HelpdeskErp\Services\ErpInvoice;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatService;

/**
 * Serie mensual "facturado vs cobrado" para el mini-gráfico del Balance.
 *
 * El manager no da importes por mes: se arma con lo que sí expone.
 *   - Cobrado: sección payments (amount_collected, date) de los últimos N meses.
 *   - Facturado: sección invoices de esos meses. La lista no trae importe, así
 *     que se suma el total con IVA del detalle de cada factura (cacheado 30
 *     min por ErpChatService), con un tope de detalles por petición; si se
 *     alcanza, la serie sale marcada como parcial.
 *
 * Solo lectura; si facturas o cobros están bloqueados (sin GRANT) la parte
 * correspondiente sale con su state y el front no pinta el gráfico.
 */
class ErpInvoiceMonthlyService
{
    private const MONTHS_ES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    public function __construct(
        private readonly ErpChatService $service,
    ) {}

    /**
     * @return array{state: string, message: ?string, reason: ?string, data: array<string, mixed>|null}
     */
    public function build(int $erpId, bool $fresh = false): array
    {
        $months = max(1, min(12, (int) config('helpdeskErp.ext.invoice.chart_months', 6)));
        $maxDetails = max(0, (int) config('helpdeskErp.ext.invoice.chart_max_details', 12));

        $start = CarbonImmutable::now()->startOfMonth()->subMonths($months - 1);
        $from = $start->format('Y-m-d');

        $res = $this->service->many($erpId, [
            'invoices' => ['invoices', ['limit' => 100, 'offset' => 0, 'from' => $from]],
            'payments' => ['payments', ['limit' => 100, 'offset' => 0, 'from' => $from]],
        ], $fresh);

        $invoices = $res['invoices'];
        $payments = $res['payments'];

        if ($invoices['state'] !== 'ok' && $payments['state'] !== 'ok') {
            return [
                'state' => $invoices['state'],
                'message' => $invoices['message'] ?? null,
                'reason' => $invoices['reason'] ?? null,
                'data' => null,
            ];
        }

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $m = $start->addMonths($i);
            $series[$m->format('Y-m')] = [
                'month' => $m->format('Y-m'),
                'label' => self::MONTHS_ES[$m->month - 1],
                'year' => $m->year,
                'invoiced' => $invoices['state'] === 'ok' ? 0.0 : null,
                'collected' => $payments['state'] === 'ok' ? 0.0 : null,
            ];
        }

        $partial = false;
        $count = 0;

        if ($invoices['state'] === 'ok') {
            $detailsUsed = 0;
            $list = is_array($invoices['data'] ?? null) ? $invoices['data'] : [];
            $partial = (bool) ($invoices['pagination']['has_more'] ?? false);

            foreach ($list as $inv) {
                if (! is_array($inv) || $this->isVoid($inv['status'] ?? null)) {
                    continue;
                }
                $key = $this->monthKey($inv['date'] ?? null);
                if ($key === null || ! isset($series[$key])) {
                    continue;
                }

                $amount = $this->listAmount($inv);
                if ($amount === null && isset($inv['id']) && is_numeric($inv['id'])) {
                    if ($detailsUsed >= $maxDetails) {
                        $partial = true;

                        continue;
                    }
                    $detailsUsed++;
                    $detail = $this->service->invoiceDetail($erpId, (int) $inv['id'], $fresh);
                    if ($detail['state'] !== 'ok' || ! is_array($detail['data'] ?? null)) {
                        $partial = true;

                        continue;
                    }
                    $amount = $this->detailAmount($detail['data']);
                }

                if ($amount !== null) {
                    $series[$key]['invoiced'] += $amount;
                    $count++;
                }
            }
        }

        if ($payments['state'] === 'ok') {
            foreach ((is_array($payments['data'] ?? null) ? $payments['data'] : []) as $p) {
                if (! is_array($p) || $this->isVoid($p['status'] ?? null)) {
                    continue;
                }
                $key = $this->monthKey($p['date'] ?? null);
                if ($key !== null && isset($series[$key]) && is_numeric($p['amount_collected'] ?? null)) {
                    $series[$key]['collected'] += (float) $p['amount_collected'];
                }
            }
            if ($payments['pagination']['has_more'] ?? false) {
                $partial = true;
            }
        }

        foreach ($series as &$row) {
            $row['invoiced'] = $row['invoiced'] !== null ? round($row['invoiced'], 2) : null;
            $row['collected'] = $row['collected'] !== null ? round($row['collected'], 2) : null;
        }
        unset($row);

        return [
            'state' => 'ok',
            'message' => null,
            'reason' => null,
            'data' => [
                'from' => $from,
                'months' => array_values($series),
                'partial' => $partial,
                'invoices_counted' => $count,
                'invoices' => ['state' => $invoices['state'], 'message' => $invoices['message'] ?? null],
                'payments' => ['state' => $payments['state'], 'message' => $payments['message'] ?? null],
            ],
        ];
    }

    /**
     * Por si algún día la lista trae el importe: se usa sin pedir detalle.
     *
     * @param  array<string, mixed>  $inv
     */
    private function listAmount(array $inv): ?float
    {
        foreach (['total_with_taxes', 'total', 'amount'] as $k) {
            if (is_numeric($inv[$k] ?? null)) {
                return (float) $inv[$k];
            }
        }
        if (is_numeric($inv['totals']['lines_total_with_taxes'] ?? null)) {
            return (float) $inv['totals']['lines_total_with_taxes'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function detailAmount(array $data): ?float
    {
        if (is_numeric($data['totals']['lines_total_with_taxes'] ?? null)) {
            return (float) $data['totals']['lines_total_with_taxes'];
        }

        return ErpInvoiceDocument::fromDetail($data)['totals']['total'];
    }

    private function monthKey(mixed $date): ?string
    {
        if (! is_string($date) || trim($date) === '') {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m');
        } catch (\Throwable) {
            return null;
        }
    }

    private function isVoid(mixed $status): bool
    {
        return $status === false || $status === 0 || $status === '0';
    }
}
