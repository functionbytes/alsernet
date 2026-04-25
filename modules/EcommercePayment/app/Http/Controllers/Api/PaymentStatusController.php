<?php

namespace Modules\EcommercePayment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Order;

class PaymentStatusController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $order = Order::query()
            ->where('token', $validated['token'])
            ->with('payment')
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_code' => $order->code,
                'order_status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'amount' => $order->total,
                'payment' => $order->payment ? [
                    'charge_id' => $order->payment->charge_id,
                    'status' => $order->payment->status->value,
                    'status_label' => $order->payment->status->label(),
                    'amount' => $order->payment->amount,
                    'currency' => $order->payment->currency,
                    'created_at' => $order->payment->created_at->toIso8601String(),
                ] : null,
            ],
        ]);
    }
}
