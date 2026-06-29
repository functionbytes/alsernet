<?php

namespace Modules\Engagement\Contracts;

use Modules\Engagement\Models\PlatformIntegration;

/**
 * Contrato común para conectores de datos de cliente.
 *
 * Cada plataforma (PrestaShop, ERP, Shopify, WooCommerce) implementa este
 * contrato y expone los datos de un cliente identificado por un lookup
 * (email, external_id, etc.). Los métodos opcionales devuelven `null`
 * cuando la plataforma no soporta esa categoría de datos.
 *
 * Estructura del lookup:
 *   ['email' => string|null, 'external_id' => string|null]
 *
 * Estructura de respuesta:
 *   ['ok' => bool, 'data' => array|null, 'error' => string|null,
 *    'cached_at' => string|null, 'platform' => string]
 */
interface CustomerDataConnector
{
    /**
     * Identificador estable de la plataforma (prestashop, erp, shopify, ...).
     */
    public function platform(): string;

    /**
     * Indica si esta plataforma soporta una acción dada.
     * Permite al orchestrator filtrar antes de invocar.
     */
    public function supports(string $action): bool;

    /**
     * Perfil del cliente: nombre, email, métricas (total_spent, orders_count,
     * first/last order), grupo, status. Es la "card de cliente".
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     * @return array{ok: bool, data: array|null, error: string|null, cached_at: string|null, platform: string}
     */
    public function profile(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Listado de pedidos del cliente.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     * @return array{ok: bool, data: array|null, error: string|null, cached_at: string|null, platform: string}
     */
    public function orders(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Detalle completo de un pedido.
     */
    public function orderDetail(PlatformIntegration $integration, string|int $orderId, bool $force = false): array;

    /**
     * Direcciones del cliente.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function addresses(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Devoluciones del cliente. Devuelve `['ok' => false, 'data' => null]` si no soporta.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function returns(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Vouchers / cupones / vales del cliente.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function vouchers(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Carrito actual / abandonado.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function cart(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Mensajes intercambiados sobre pedidos (no chat — postventa).
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function messages(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Albaranes / delivery notes (sólo ERP por ahora).
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function deliveryNotes(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Facturas (sólo ERP por ahora).
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function invoices(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Cobros / payments del cliente.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function payments(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Deudas pendientes.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function debts(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Balance financiero global.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function balance(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Bonos promocionales activos / usados.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function bonuses(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Puntos de fidelización.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function loyaltyPoints(PlatformIntegration $integration, array $lookup, bool $force = false): array;

    /**
     * Invalida cache de todas las acciones para un lookup dado.
     * Llamado por PlatformWebhookHandler tras recibir un evento.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function invalidateCache(PlatformIntegration $integration, array $lookup): void;
}
