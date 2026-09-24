<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Controllers\Concerns\BuildsIdempotencyKey;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\AddCartProductRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\ApplyCartVoucherRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\RemoveCartProductRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\RemoveCartVoucherRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\SetCartAddressRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\UpdateCartQuantityRequest;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Acciones mutadoras sobre el carrito REAL (en vivo) del cliente en
 * PrestaShop — dirección, productos, cantidades, cupón. Mismo patrón de
 * propiedad que PsOrderActionsController: el cart_id se ata al {customer} de
 * la ruta, el email que verifica la propiedad en el bridge se resuelve
 * server-side desde $customer->email, nunca de un campo del body.
 *
 * Requiere el permiso dedicado helpdeskprestashop.carts.manage — separado de
 * orders.manage porque tocar un carrito activo (antes de que el cliente
 * pague) es más sensible que un pedido ya cerrado.
 */
class PsCartActionsController extends Controller
{
    use BuildsIdempotencyKey;

    public function __construct(
        private readonly PrestashopContextService $service
    ) {}

    public function setAddress(SetCartAddressRequest $request, Customer $customer, int $cart): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer)) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->setCartAddress($cart, (int) $data['address_id'], $data['type'] ?? 'delivery', $customer->email, $this->externalId($customer), $this->idempotencyKey($request, $cart, 'cart.set_address', $data));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó el cambio (carrito/dirección no válidos o sin acceso).'], 422);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo cambiar la dirección del carrito.'], 422);
        }

        $this->service->forgetCache($customer->email);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function addProduct(AddCartProductRequest $request, Customer $customer, int $cart): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer)) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->addCartProduct(
                $cart,
                (int) $data['product_id'],
                (int) ($data['quantity'] ?? 1),
                isset($data['attribute_id']) ? (int) $data['attribute_id'] : null,
                $customer->email,
                $this->externalId($customer),
                $this->idempotencyKey($request, $cart, 'cart.add_product', $data),
            );
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó la operación (carrito/producto no válidos o sin acceso).'], 422);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo añadir el producto al carrito.'], 422);
        }

        $this->service->forgetCache($customer->email);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function removeProduct(RemoveCartProductRequest $request, Customer $customer, int $cart): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer)) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->removeCartProduct(
                $cart,
                (int) $data['product_id'],
                isset($data['attribute_id']) ? (int) $data['attribute_id'] : null,
                $customer->email,
                $this->externalId($customer),
                $this->idempotencyKey($request, $cart, 'cart.remove_product', $data),
            );
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó la operación (carrito/producto no válidos o sin acceso).'], 422);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo quitar el producto del carrito.'], 422);
        }

        $this->service->forgetCache($customer->email);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function updateQuantity(UpdateCartQuantityRequest $request, Customer $customer, int $cart): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer)) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->updateCartProductQuantity(
                $cart,
                (int) $data['product_id'],
                (int) $data['quantity'],
                isset($data['attribute_id']) ? (int) $data['attribute_id'] : null,
                $customer->email,
                $this->externalId($customer),
                $this->idempotencyKey($request, $cart, 'cart.update_quantity', $data),
            );
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó la operación (carrito/producto no válidos o sin acceso).'], 422);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo actualizar la cantidad.'], 422);
        }

        $this->service->forgetCache($customer->email);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function applyVoucher(ApplyCartVoucherRequest $request, Customer $customer, int $cart): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer)) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->applyCartVoucher($cart, (string) $data['code'], $customer->email, $this->externalId($customer), $this->idempotencyKey($request, $cart, 'cart.apply_voucher', $data));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó la operación (carrito no válido o sin acceso).'], 422);
        }

        if ($result === null || ! ($result['applied'] ?? false)) {
            $message = ($result['error'] ?? null) === 'voucher_not_found'
                ? 'El código no existe.'
                : 'El cupón no aplica a este carrito.';

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $this->service->forgetCache($customer->email);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function removeVoucher(RemoveCartVoucherRequest $request, Customer $customer, int $cart): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer)) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->removeCartVoucher($cart, (string) $data['code'], $customer->email, $this->externalId($customer), $this->idempotencyKey($request, $cart, 'cart.remove_voucher', $data));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó la operación (carrito no válido o sin acceso).'], 422);
        }

        if ($result === null || ! ($result['removed'] ?? false)) {
            $message = match ($result['error'] ?? null) {
                'voucher_not_found' => 'El código no existe.',
                'voucher_not_applied' => 'Ese cupón ya no está aplicado al carrito.',
                default => 'No se pudo quitar el cupón.',
            };

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $this->service->forgetCache($customer->email);

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * external_id ya vinculado del cliente (mismo patrón que
     * ContactAggregatorService::prestashop()): permite resolver la propiedad
     * del carrito en el bridge aunque $customer->email no coincida con el de
     * su cuenta de PrestaShop — necesario para quien llama desde Contacts 360.
     */
    private function externalId(Customer $customer): ?int
    {
        $externalId = $customer->externalIdFor('prestashop');

        return $externalId !== null ? (int) $externalId : null;
    }

    /**
     * Verifica que el usuario puede acceder a ese cliente (CustomerPolicy) y
     * que tiene email (necesario para resolver la propiedad del carrito en
     * el bridge). El permiso PS ya lo comprueba el Form Request (authorize).
     */
    private function denyUnlessOwnsCustomer(Request $request, Customer $customer): ?JsonResponse
    {
        $user = $request->user();

        if (! $user?->can('update', $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (trim((string) $customer->email) === '') {
            return response()->json(['success' => false, 'message' => 'El cliente no tiene correo para verificar la propiedad del carrito.'], 422);
        }

        return null;
    }
}
