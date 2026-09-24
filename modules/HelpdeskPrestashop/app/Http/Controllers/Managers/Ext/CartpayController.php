<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\CartpayConvertRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\CartpayEmptyRequest;
use Modules\HelpdeskPrestashop\Services\Ext\CartpayService;

/**
 * Extensión "cartpay".
 *
 * Pieza 02 — cobro de un pedido pendiente: la tienda no tiene ningún módulo
 * que dé un enlace para pagar un pedido ya creado, así que lo que se ofrece
 * es lo real: los datos de transferencia de ps_wirepayment con la referencia
 * como concepto, para pegarlos en el chat.
 *
 * Pieza 32 — convertir el carrito en vivo en pedido (validateOrder de
 * ps_wirepayment, como el back-office) o vaciarlo. Permisos propios y altos:
 * crear un pedido dispara ERP, stock y el correo de confirmación; marcarlo
 * como pagado (convert_paid) es dar por cobrado un dinero que PrestaShop no
 * ha visto.
 */
class CartpayController extends Controller
{
    /** Motivos de bloqueo del puente → texto para el agente. */
    private const BLOCKING = [
        'already_ordered' => 'Este carrito ya es un pedido.',
        'empty' => 'El carrito está vacío.',
        'no_delivery_address' => 'El carrito no tiene dirección de envío del cliente.',
        'no_invoice_address' => 'El carrito no tiene dirección de facturación del cliente.',
        'delivery_country_disabled' => 'El país de envío no está activo en la tienda.',
        'invoice_country_disabled' => 'El país de facturación no está activo en la tienda.',
        'no_carrier' => 'Ningún transportista sirve a la dirección de envío.',
        'out_of_stock' => 'Hay productos sin stock suficiente.',
        'zero_total' => 'Los productos del carrito suman 0,00 €: no se crea un pedido gratis.',
        'payment_module_inactive' => 'El pago por transferencia no está activo en la tienda.',
        'invalid_voucher' => 'Hay un cupón en el carrito que ya no es válido: quítalo antes de convertir.',
    ];

    public function __construct(
        private readonly CartpayService $service
    ) {}

    public function orderPayment(Request $request, Customer $customer, int $order): JsonResponse
    {
        $user = $request->user();

        if (! $user?->can('helpdeskprestashop.orders.view') || ! $user->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        try {
            $data = $this->service->orderPayment($customer, $order);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha devuelto el cobro de este pedido.'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'El pedido no es de este cliente o el cliente no está vinculado.'], 404);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function preview(Request $request, Customer $customer, int $cart): JsonResponse
    {
        $user = $request->user();

        $canConvert = (bool) $user?->can('helpdeskprestashop.cartpay.convert');
        $canEmpty = (bool) $user?->can('helpdeskprestashop.cartpay.empty');

        if ((! $canConvert && ! $canEmpty) || ! $user->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        try {
            $data = $this->service->convertPreview($customer, $cart);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha devuelto el carrito.'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'El carrito no es de este cliente o el cliente no está vinculado.'], 404);
        }

        $data['blocking_messages'] = $this->blockingMessages((array) ($data['blocking'] ?? []));
        // Solo con el hook actionEmailSendBefore del puente (1.2.5) se puede
        // desmarcar el correo; un puente anterior no lo informa = no se puede.
        $data['confirmation_email_optional'] = (bool) ($data['confirmation_email_optional'] ?? false);
        $data['can'] = [
            'convert' => $canConvert,
            'convert_paid' => $canConvert && $user->can('helpdeskprestashop.cartpay.convert_paid'),
            'empty' => $canEmpty,
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function convert(CartpayConvertRequest $request, Customer $customer, int $cart): JsonResponse
    {
        $user = $request->user();

        if (($resp = $this->denyUnlessCanWrite($user, $customer)) !== null) {
            return $resp;
        }

        $data = $request->validated();
        $state = (string) $data['state'];
        $sendConfirmation = $request->sendConfirmation();
        $paid = (bool) config('helpdeskprestashop.ext.cartpay.convert.states.'.$state.'.paid', true);

        if ($paid && ! $user->can('helpdeskprestashop.cartpay.convert_paid')) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para crear pedidos ya pagados.'], 403);
        }

        // Mismo agente + carrito + estado en el mismo minuto = doble clic. El
        // puente además no convierte un carrito que ya es pedido.
        $idempotencyKey = $this->idempotencyKey($request, $customer, $cart, 'cartpay.convert:'.$state.($sendConfirmation ? '' : ':no-mail'));

        try {
            $result = $this->service->convert($customer, $cart, $state, (string) ($user->name ?? $user->email ?? ''), $idempotencyKey, $sendConfirmation);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha podido crear el pedido ahora mismo.'], 503);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'El carrito no es de este cliente o el cliente no está vinculado.'], 404);
        }

        if (($result['created'] ?? false) !== true) {
            $messages = $this->blockingMessages((array) ($result['blocking'] ?? []));

            return response()->json([
                'success' => false,
                'message' => $messages[0] ?? match ($result['error'] ?? null) {
                    'busy' => 'Otro agente está convirtiendo este carrito. Espera unos segundos.',
                    'invalid_state' => 'Ese estado no existe en la tienda.',
                    default => 'PrestaShop no ha podido crear el pedido.',
                },
                'blocking' => $messages,
            ], 422);
        }

        $this->log($user, $customer, 'ps.cart.converted', [
            'cart_id' => $cart,
            'order_id' => $result['order_id'] ?? null,
            'reference' => $result['reference'] ?? null,
            'state' => $state,
            'state_name' => $result['state_name'] ?? null,
            'total' => $result['total'] ?? null,
            'state_error' => (bool) ($result['state_error'] ?? false),
            'completed_with_errors' => (bool) ($result['completed_with_errors'] ?? false),
            'send_confirmation' => $sendConfirmation,
            // Un puente anterior a 1.2.5 no lo devuelve: siempre enviaba.
            'confirmation_email_sent' => (bool) ($result['confirmation_email_sent'] ?? true),
            'conversation_id' => $data['conversation_id'] ?? null,
        ]);

        // El pedido existe aunque algo fallara a medias (o PrestaShop lo dejara
        // en "Error en el pago"): se informa como creado, con aviso.
        $warning = match (true) {
            (bool) ($result['state_error'] ?? false) => 'PrestaShop ha dejado el pedido en estado de error de pago: revísalo en el back-office.',
            (bool) ($result['completed_with_errors'] ?? false) => 'El pedido se ha creado, pero PrestaShop registró un error al terminar (correo o módulos). Revísalo antes de avisar al cliente.',
            ! $sendConfirmation && (bool) ($result['confirmation_email_sent'] ?? true) => 'La tienda ha enviado igualmente el correo de confirmación: el puente de esta tienda todavía no permite omitirlo.',
            default => null,
        };

        return response()->json(['success' => true, 'data' => $result, 'warning' => $warning]);
    }

    public function empty(CartpayEmptyRequest $request, Customer $customer, int $cart): JsonResponse
    {
        $user = $request->user();

        if (($resp = $this->denyUnlessCanWrite($user, $customer)) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->empty($customer, $cart, $this->idempotencyKey($request, $customer, $cart, 'cartpay.empty'));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha podido vaciar el carrito ahora mismo.'], 503);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'El carrito no es de este cliente o el cliente no está vinculado.'], 404);
        }

        if (($result['ok_semantic'] ?? true) === false || ($result['emptied'] ?? false) !== true) {
            $message = ($result['error'] ?? null) === 'already_ordered'
                ? 'Este carrito ya es un pedido: no se vacía.'
                : 'PrestaShop no ha podido quitar todos los productos del carrito.';

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $this->log($user, $customer, 'ps.cart.emptied', [
            'cart_id' => $cart,
            'removed_products' => $result['removed_products'] ?? null,
            'removed_vouchers' => $result['removed_vouchers'] ?? null,
            'conversation_id' => $data['conversation_id'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Escribir en la tienda exige poder modificar a ESTE cliente
     * (CustomerPolicy::update, igual que PsCartActionsController) y que el
     * cliente sea identificable en el puente.
     */
    private function denyUnlessCanWrite($user, Customer $customer): ?JsonResponse
    {
        if (! $user?->can('update', $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (trim((string) $customer->email) === '' && $this->service->externalId($customer) === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no está vinculado a PrestaShop.'], 422);
        }

        return null;
    }

    private function idempotencyKey(Request $request, Customer $customer, int $cart, string $action): string
    {
        $header = trim((string) $request->header('Idempotency-Key', ''));

        // La clave del navegador se ata a usuario, cliente, carrito y acción:
        // el puente guarda las respuestas por clave, así que una clave
        // reutilizada nunca devuelve el resultado de otra operación.
        if ($header !== '') {
            return sha1(implode(':', [
                $request->user()?->getAuthIdentifier() ?? 'anon', $customer->id, $cart, $action, $header,
            ]));
        }

        // Con minuto: vaciar hoy y volver a vaciar mañana el mismo carrito
        // son dos operaciones, no un reintento.
        return sha1(implode(':', [
            $request->user()?->getAuthIdentifier() ?? 'anon', $customer->id, $cart, $action, now()->format('YmdHi'),
        ]));
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    private function blockingMessages(array $codes): array
    {
        return array_values(array_map(
            fn ($code) => self::BLOCKING[$code] ?? 'PrestaShop no permite convertir este carrito ('.$code.').',
            $codes
        ));
    }

    private function log($user, Customer $customer, string $event, array $properties): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('helpdeskprestashop')
            ->causedBy($user)
            ->performedOn($customer)
            ->withProperties($properties)
            ->log($event);
    }
}
