<?php

namespace Modules\Document\Services;

use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;

/**
 * Adaptador entre Document y el bridge de PrestaShop (alsernetbridge), vía
 * el módulo opcional HelpdeskPrestashop — reemplaza las consultas directas
 * a la conexión Eloquent 'prestashop' (BD de PrestaShop expuesta por IP,
 * tablas aalv_*) que este módulo usaba antes.
 *
 * Usa deliberadamente PrestashopContextService::getOrderDetailUnscoped(),
 * NO getOrderDetail(): Document descubre el pedido a partir de un order_id
 * ya confiable (webhook `order-paid` firmado, o panel interno de staff ya
 * autenticado tras `auth:web` + `can:view-documents-panel`), sin conocer de
 * antemano el email del cliente — el propio flujo es "averiguar quién es el
 * cliente a partir del pedido", lo opuesto al caso de uso de
 * getOrderDetail() (agente que ya conoce al cliente y verifica su pedido).
 *
 * Dependencia SUAVE: HelpdeskPrestashop es un módulo satélite de Helpdesk
 * que puede estar deshabilitado; se resuelve por class_exists()+Module, sin
 * declarar el require en composer.json/module.json (mismo patrón que
 * HelpdeskContacts para ERP/PrestaShop).
 */
class PrestashopOrderLookupService
{
    private const SERVICE_CLASS = 'Modules\\HelpdeskPrestashop\\Services\\PrestashopContextService';

    public function available(): bool
    {
        return class_exists(self::SERVICE_CLASS)
            && (Module::find('HelpdeskPrestashop')?->isEnabled() ?? false);
    }

    /**
     * Busca un pedido de PrestaShop por su id y lo normaliza al shape que
     * ya esperan los controllers de Document (mismos nombres de campo que
     * usaban al leer PrestashopOrder::find()+relaciones directamente).
     *
     * @return array{
     *   order_id: int, reference: ?string, date_add: ?string, cart_id: int,
     *   customer_id: int, customer_email: ?string, customer_firstname: ?string,
     *   customer_lastname: ?string, customer_dni: ?string, customer_company: ?string,
     *   customer_cellphone: ?string, lang_iso: ?string,
     *   products: array<int, array{product_id: ?int, product_name: ?string, product_reference: ?string, product_quantity: ?int, unit_price_tax_incl: ?float}>
     * }|null
     */
    public function find(int $orderId): ?array
    {
        if (! $this->available()) {
            return null;
        }

        try {
            $detail = app(self::SERVICE_CLASS)->getOrderDetailUnscoped($orderId);
        } catch (\Throwable $e) {
            Log::warning('Document: fallo consultando order.detail en el bridge PrestaShop', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($detail === null) {
            return null;
        }

        $address = $detail['shipping_address'] ?? null;

        return [
            'order_id' => (int) ($detail['id'] ?? $orderId),
            'reference' => $detail['reference'] ?? null,
            'date_add' => $detail['created_at'] ?? null,
            'cart_id' => (int) ($detail['cart_id'] ?? 0),
            'customer_id' => (int) ($detail['customer_id'] ?? 0),
            'customer_email' => $detail['customer_email'] ?? null,
            // Prioridad: nombre de la dirección de entrega sobre el del cliente,
            // igual que el código original (`deliveryAddress?->firstname ?? customer->firstname`).
            'customer_firstname' => $address['firstname'] ?? $detail['customer_firstname'] ?? null,
            'customer_lastname' => $address['lastname'] ?? $detail['customer_lastname'] ?? null,
            // vat_number es el campo principal en PS (~439k registros), dni es
            // residual (~916) y a veces contiene el placeholder '-' en vez de
            // estar vacío — criterio ya usado en syncDocumentByOrderId().
            'customer_dni' => $this->normalizeDni($address['vat_number'] ?? null, $address['dni'] ?? null),
            'customer_company' => $address['company'] ?? null,
            'customer_cellphone' => $address['phone_mobile'] ?? $address['phone'] ?? null,
            'lang_iso' => $detail['lang_iso'] ?? null,
            'products' => array_map(fn (array $line) => [
                'product_id' => $line['product_id'] ?? null,
                'product_name' => $line['name'] ?? null,
                'product_reference' => $line['reference'] ?? null,
                'product_quantity' => $line['quantity'] ?? null,
                'unit_price_tax_incl' => $line['unit_price'] ?? null,
            ], $detail['lines'] ?? []),
        ];
    }

    private function normalizeDni(?string $vatNumber, ?string $dni): ?string
    {
        $raw = ($vatNumber && $vatNumber !== '-') ? $vatNumber : $dni;

        return ($raw && $raw !== '-') ? $raw : null;
    }
}
