<?php

namespace Modules\HelpdeskContacts\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Nwidart\Modules\Facades\Module;
use RuntimeException;
use Throwable;

/**
 * Thin proxy that exposes the HelpdeskPrestashop assisted-cart actions under
 * the contacts.* permission gate.
 *
 * The native cart routes (manager.helpdesk.customers.cart.*) authorize against
 * the Customer policy, which requires helpdesk.customers.* permissions. A
 * Contacts 360 agent only holds contacts.* permissions, so those routes would
 * 403. This controller re-gates on contacts.view / contacts.update (applied by
 * the route middleware) and forwards to AssistedCartService directly.
 *
 * Every method degrades gracefully (422) when HelpdeskPrestashop is disabled so
 * the Contacts panel never 500s.
 */
class ContactCartController extends Controller
{
    private const SERVICE = 'Modules\\HelpdeskPrestashop\\Services\\AssistedCartService';

    private const ITEM_MODEL = 'Modules\\HelpdeskPrestashop\\Models\\AssistedCartItem';

    /**
     * Current building cart for the customer (creates one if none exists).
     */
    public function show(Request $request, Customer $customer): JsonResponse
    {
        return $this->withService(function (object $service) use ($request, $customer): JsonResponse {
            $cart = $service->getOrCreateCart(
                $customer,
                $request->integer('conversation_id') ?: null,
                $request->user()->id,
            );

            return $this->cartResponse($service, $cart);
        });
    }

    public function addItem(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        return $this->withService(function (object $service) use ($request, $customer, $validated): JsonResponse {
            $cart = $service->getOrCreateCart($customer, null, $request->user()->id);

            try {
                $service->addItem($cart, (int) $validated['product_id'], (int) ($validated['quantity'] ?? 1));
            } catch (RuntimeException $e) {
                return $this->businessError($e);
            }

            return $this->cartResponse($service, $cart->refresh());
        });
    }

    public function removeItem(Request $request, Customer $customer, int $item): JsonResponse
    {
        return $this->withService(function (object $service) use ($request, $customer, $item): JsonResponse {
            $cart = $service->getOrCreateCart($customer, null, $request->user()->id);

            $cartItem = app(self::ITEM_MODEL)->newQuery()
                ->where('cart_id', $cart->id)
                ->whereKey($item)
                ->first();

            if ($cartItem === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'El producto no existe en el carrito.',
                ], 404);
            }

            try {
                $service->removeItem($cartItem);
            } catch (RuntimeException $e) {
                return $this->businessError($e);
            }

            return $this->cartResponse($service, $cart->refresh());
        });
    }

    public function applyDiscount(Request $request, Customer $customer): JsonResponse
    {
        $request->validate(['code' => ['nullable', 'string', 'max:191']]);

        return $this->withService(function (object $service) use ($request, $customer): JsonResponse {
            $cart = $service->getOrCreateCart($customer, null, $request->user()->id);

            try {
                $result = $service->applyDiscount($cart, $request->input('code'));
            } catch (RuntimeException $e) {
                return $this->businessError($e);
            }

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'cart' => $service->summarize($cart->refresh()),
            ], $result['success'] ? 200 : 422);
        });
    }

    public function generateOrder(Request $request, Customer $customer): JsonResponse
    {
        $validated = $this->validateCustomerData($request);

        return $this->withService(function (object $service) use ($customer, $validated): JsonResponse {
            $cart = $service->getOrCreateCart($customer);

            try {
                $order = $service->generateOrder($cart, $validated);
            } catch (RuntimeException $e) {
                return $this->businessError($e);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido '.$order->code.' generado correctamente.',
                'order' => [
                    'id' => $order->id,
                    'code' => $order->code,
                    'total' => (float) $order->total,
                ],
                'cart' => $service->summarize($cart->refresh()),
            ]);
        });
    }

    public function sendPaymentLink(Request $request, Customer $customer): JsonResponse
    {
        $validated = $this->validateCustomerData($request);

        return $this->withService(function (object $service) use ($customer, $validated): JsonResponse {
            $cart = $service->getOrCreateCart($customer);

            try {
                $url = $service->sendPaymentLink($cart, $validated);
            } catch (RuntimeException $e) {
                return $this->businessError($e);
            }

            return response()->json([
                'success' => true,
                'message' => 'Link de pago enviado al cliente.',
                'payment_url' => $url,
                'cart' => $service->summarize($cart->refresh()),
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCustomerData(Request $request): array
    {
        return $request->validate([
            'name' => ['nullable', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:191'],
            'state' => ['nullable', 'string', 'max:191'],
            'country' => ['nullable', 'string', 'max:2'],
            'zip_code' => ['nullable', 'string', 'max:20'],
        ]);
    }

    private function cartResponse(object $service, object $cart): JsonResponse
    {
        return response()->json([
            'success' => true,
            'cart' => $service->summarize($cart),
        ]);
    }

    /**
     * Resolve the assisted-cart service behind the module guard, 422 when off.
     *
     * @param  callable(object): JsonResponse  $resolver
     */
    private function withService(callable $resolver): JsonResponse
    {
        if (! $this->prestashopAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'El carrito asistido no está disponible.',
            ], 422);
        }

        try {
            return $resolver(app(self::SERVICE));
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar la operación del carrito.',
            ], 422);
        }
    }

    private function prestashopAvailable(): bool
    {
        return (Module::find('HelpdeskPrestashop')?->isEnabled() ?? false)
            && class_exists(self::SERVICE);
    }

    private function businessError(RuntimeException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}
