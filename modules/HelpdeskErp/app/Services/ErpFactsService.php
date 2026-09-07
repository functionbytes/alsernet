<?php

namespace Modules\HelpdeskErp\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;

/**
 * Traduce la ficha del cliente en el ERP a un puñado de valores planos sobre
 * los que se pueden escribir reglas.
 *
 * Existe para que los dos motores de reglas —AutomationEngine (tickets) y
 * WorkflowEngine (inbox)— vean exactamente las mismas señales con los mismos
 * nombres. Cada uno tenía su propio catálogo y ninguno sabía nada del ERP;
 * duplicar aquí la lectura de getCustomerContext() habría hecho que las dos
 * listas se separaran a la primera de cambio.
 *
 * Todos los valores son escalares porque los operadores de ambos motores
 * (equals, greater_than, contains…) comparan escalares. Un cliente sin ficha
 * en gestión devuelve la misma forma con erp_linked = false: "no está en el
 * ERP" tiene que poder ser una condición como cualquier otra.
 */
class ErpFactsService
{
    /** @var array<int, array<string, mixed>> Memoización por request. */
    private array $memo = [];

    /**
     * @return array<string, mixed>
     */
    public function forCustomer(?Customer $customer): array
    {
        if ($customer === null) {
            return $this->emptyFacts();
        }

        if (isset($this->memo[$customer->id])) {
            return $this->memo[$customer->id];
        }

        return $this->memo[$customer->id] = $this->build($customer);
    }

    /**
     * @return array<string, mixed>
     */
    private function build(Customer $customer): array
    {
        if (! helpdesk_erp_enabled()) {
            return $this->emptyFacts();
        }

        $email = $customer->email;

        if (! $email || str_ends_with($email, '@anonymous.local')) {
            $email = '';
        }

        $phone = $customer->whatsapp_phone ?: $customer->phone;

        if ($email === '' && ! $phone) {
            return $this->emptyFacts();
        }

        try {
            // getCustomerContext() ya cachea y ya tiene cortacircuitos: las
            // reglas no añaden carga al ERP más allá de la primera lectura,
            // que en la práctica ya la hizo el vínculo.
            $context = app(ErpContextService::class)->getCustomerContext($email, $phone, $customer->id);
        } catch (\Throwable $e) {
            Log::warning('ErpFactsService: no se pudo leer el contexto del ERP', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return $this->emptyFacts();
        }

        $erpCustomer = $context['customer'] ?? ['found' => false];

        if (! ($erpCustomer['found'] ?? false)) {
            return $this->emptyFacts();
        }

        $orders = is_array($context['orders'] ?? null) ? $context['orders'] : [];

        return [
            'erp_linked' => true,
            'erp_customer_id' => isset($erpCustomer['id']) ? (int) $erpCustomer['id'] : null,
            'erp_balance_pending' => $this->toFloat($erpCustomer['balance_pending'] ?? null),
            'erp_credit_limit' => $this->toFloat($erpCustomer['credit_limit'] ?? null),
            'erp_loyalty_points' => (int) ($erpCustomer['loyalty_points'] ?? 0),
            'erp_payment_terms' => $erpCustomer['payment_terms'] !== null ? (string) $erpCustomer['payment_terms'] : null,
            'erp_province' => $erpCustomer['province'] ?? null,
            'erp_orders_count' => count($orders),
            'erp_days_since_last_order' => $this->daysSinceLastOrder($orders),
        ];
    }

    /**
     * Cliente sin ficha en gestión, ERP apagado o ilegible.
     *
     * Los numéricos van a 0 y no a null a propósito: con null, una regla
     * escrita como "deuda mayor que 100" se comportaría distinto según el
     * operador, y quien la escribió espera que simplemente no dispare.
     *
     * @return array<string, mixed>
     */
    private function emptyFacts(): array
    {
        return [
            'erp_linked' => false,
            'erp_customer_id' => null,
            'erp_balance_pending' => 0.0,
            'erp_credit_limit' => 0.0,
            'erp_loyalty_points' => 0,
            'erp_payment_terms' => null,
            'erp_province' => null,
            'erp_orders_count' => 0,
            'erp_days_since_last_order' => null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $orders
     */
    private function daysSinceLastOrder(array $orders): ?int
    {
        $latest = null;

        foreach ($orders as $order) {
            $raw = $order['date'] ?? null;

            if (! $raw) {
                continue;
            }

            try {
                $date = Carbon::parse($raw);
            } catch (\Throwable) {
                continue;
            }

            if ($latest === null || $date->gt($latest)) {
                $latest = $date;
            }
        }

        return $latest?->diffInDays(now());
    }

    private function toFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
