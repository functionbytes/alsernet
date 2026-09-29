<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Lecturas de la cuenta PrestaShop del cliente para el tab "Tienda" del
 * inbox: pedidos/carritos, direcciones, devoluciones, cupones, mensajes,
 * lista de deseos y reembolsos, más las provincias del formulario de
 * dirección. La búsqueda de productos vive en ProductSearchController.
 */
class PsCustomerDataController extends Controller
{
    public function __construct(
        private readonly PrestashopContextService $ps,
    ) {}

    /**
     * Returns PS shipping addresses for a customer.
     */
    public function addresses(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);

        $addresses = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerAddresses((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'addresses' => $addresses]);
    }

    /**
     * external_id ya vinculado del cliente (mismo patrón que
     * ContactAggregatorService::prestashop()): sin él, un contacto cuyo email
     * de Helpdesk no coincide con el de su cuenta de PrestaShop no resolvía
     * ninguna dirección/devolución/cupón/mensaje.
     */
    private function externalId(Customer $customer): ?int
    {
        $externalId = $customer->externalIdFor('prestashop');

        return $externalId !== null ? (int) $externalId : null;
    }

    /**
     * Returns PS orders + carritos abandonados/en curso para un cliente. Carga
     * diferida (AJAX) desde el tab "Tienda" del inbox — evita el bloqueo
     * síncrono del bridge de PrestaShop (hasta 12s) en el render del panel
     * derecho. Ambos salen de la misma llamada a getCustomerContext() (ya
     * cacheada), así que devolver también los carritos no cuesta una llamada
     * extra al bridge.
     */
    public function orders(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $email = (string) $customer->email;

        if ($email === '' && $externalId === null) {
            return response()->json(['success' => true, 'bridge' => 'ok', 'fetched_at' => time()] + $this->contextPayload([]));
        }

        // "Actualizar" salta la caché, pero se guarda antes lo cacheado: si el
        // puente no responde, es lo que se enseña (marcado como caché).
        $previous = $email !== '' ? $this->ps->peekCachedContext($email) : null;

        if ($request->boolean('fresh') && $email !== '') {
            $this->ps->forgetCache($email);
        }

        try {
            $context = $this->ps->getCustomerContextOrFail($email, $customer->id, $externalId);
        } catch (PsUpstreamException) {
            // El puente no responde: se devuelve lo último cacheado (si lo hay)
            // marcado como tal, para que el panel diga "mostrando caché" en vez
            // de pintar "Sin pedidos" como si el cliente no tuviera nada.
            $stale = ($previous['customer']['found'] ?? false) ? $previous : null;

            return response()->json([
                'success' => false,
                'bridge' => 'down',
                'stale' => $stale !== null,
                'message' => 'PrestaShop no responde ahora mismo.',
            ] + $this->contextPayload($stale ?? []), 503);
        }

        return response()->json([
            'success' => true,
            'bridge' => 'ok',
            'fetched_at' => $context['fetched_at'] ?? time(),
        ] + $this->contextPayload($context));
    }

    /**
     * Todo lo que el tab "Tienda" necesita sale de customer.helpdesk_context
     * (una sola llamada al bridge): cliente, pedidos, carritos, direcciones,
     * devoluciones, cupones, reembolsos, mensajes y lista de deseos.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function contextPayload(array $context): array
    {
        return [
            'customer' => $context['customer'] ?? null,
            'orders' => $context['orders'] ?? [],
            'carts' => $context['carts'] ?? [],
            'addresses' => $context['addresses'] ?? null,
            'returns' => $context['returns'] ?? null,
            'vouchers' => $context['vouchers'] ?? null,
            'refunds' => $context['refunds'] ?? null,
            'messages' => $context['messages'] ?? null,
            'wishlist' => $context['wishlist'] ?? null,
        ];
    }

    /**
     * Returns PS return (RMA) history for a customer.
     */
    public function returns(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $returns = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerReturns((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'returns' => $returns]);
    }

    /**
     * Returns the customer's own PS vouchers/cart rules.
     */
    public function vouchers(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $vouchers = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerVouchers((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'vouchers' => $vouchers]);
    }

    /**
     * Returns the customer's native PrestaShop message threads.
     */
    public function messages(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $messages = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerMessages((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    /**
     * Returns the customer's wishlist products.
     */
    public function wishlist(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $items = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerWishlist((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'items' => $items]);
    }

    /**
     * Returns the customer's real refunds (order_slip) — money actually
     * returned, not RMA requests (see returns()).
     */
    public function refunds(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $refunds = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerRefunds((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'refunds' => $refunds]);
    }

    /**
     * Provincias/estados de un país — desplegable del formulario de
     * dirección (crear/editar). España (6) por defecto si no se especifica.
     */
    public function countryStates(Request $request): JsonResponse
    {
        // Las provincias hacen falta para crear direcciones: también con los
        // permisos de direcciones o de carrito, no solo con el de ver.
        $user = $request->user();
        abort_unless($user && ($user->can('helpdeskprestashop.view') || $user->can('helpdeskprestashop.addresses.manage') || $user->can('helpdeskprestashop.carts.manage')), 403);

        $countryId = (int) $request->query('id_country', 6);
        $states = $this->ps->getCountryStates($countryId);

        return response()->json(['success' => true, 'states' => $states]);
    }
}
