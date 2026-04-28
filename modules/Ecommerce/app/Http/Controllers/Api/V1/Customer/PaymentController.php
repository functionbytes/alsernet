<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Http\Requests\Api\V1\Order\InitiatePaymentRequest;
use Modules\Ecommerce\Http\Resources\Api\V1\PaymentMethodResource;
use Modules\Ecommerce\Models\Order;
use Modules\EcommercePayment\Services\PaymentGatewayManager;

class PaymentController extends BaseApiController
{
    public function methods(Request $request): JsonResponse
    {
        $manager = app(PaymentGatewayManager::class);
        $gateways = collect($manager->all())
            ->map(fn ($class, $name) => $manager->get($name))
            ->filter(fn ($gateway) => $gateway->isEnabled())
            ->values();

        return $this->ok(PaymentMethodResource::collection($gateways)->toArray($request));
    }

    public function initiate(InitiatePaymentRequest $request, Order $order): JsonResponse
    {
        if ($order->customer_id !== auth()->id()) {
            return $this->errorResponse('Pedido no encontrado.', 'NOT_FOUND', 404);
        }

        if ($order->payment_status === 'paid') {
            return $this->errorResponse('El pedido ya está pagado.', 'ALREADY_PAID', 422);
        }

        $manager = app(PaymentGatewayManager::class);
        $channel = $request->input('channel');

        if (! $manager->has($channel)) {
            return $this->errorResponse('Pasarela no disponible.', 'GATEWAY_NOT_FOUND', 422);
        }

        $gateway = $manager->get($channel);
        $returnUrl = $request->input('return_url');

        return $this->ok([
            'paymentUrl' => $returnUrl ? $returnUrl.'?order='.$order->code : null,
            'channel' => $channel,
            'gateway' => $gateway->getName(),
            'instructions' => match ($channel) {
                'cod' => ['type' => 'cod', 'message' => 'Pago contra entrega confirmado.'],
                'bank_transfer' => ['type' => 'bank_transfer', 'message' => 'Sigue las instrucciones de transferencia bancaria.'],
                default => ['type' => 'redirect', 'message' => 'Abre la URL de pago para completar la transacción.'],
            },
        ]);
    }

    public function status(Order $order, $payment): JsonResponse
    {
        if ($order->customer_id !== auth()->id()) {
            return $this->errorResponse('Pedido no encontrado.', 'NOT_FOUND', 404);
        }

        return $this->ok([
            'orderId' => $order->id,
            'paymentStatus' => $order->payment_status,
            'paymentMethod' => $order->payment_method,
            'transactionId' => $order->transaction_id,
        ]);
    }
}
