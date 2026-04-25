<?php

namespace Modules\EcommercePayment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Order;
use Modules\EcommercePayment\Services\PaymentGatewayManager;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
    ) {}

    public function process(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:ecommerce_orders,id'],
            'payment_method' => ['required', 'string'],
        ]);

        $order = Order::query()->findOrFail($validated['order_id']);
        $gatewayName = $validated['payment_method'];

        if (! $this->gatewayManager->has($gatewayName)) {
            return redirect()->route('checkout.index')
                ->with('error', 'El método de pago seleccionado no está disponible.');
        }

        $gateway = $this->gatewayManager->get($gatewayName);

        if ($gatewayName === 'wompi' && method_exists($gateway, 'isEnabled') && ! $gateway->isEnabled()) {
            return redirect()->route('checkout.index')
                ->with('error', 'El método de pago Wompi no está habilitado.');
        }

        $customerData = [
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'region' => $request->input('region'),
        ];

        return $gateway->makePayment($order, $customerData);
    }
}
