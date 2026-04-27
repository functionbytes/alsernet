<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Shipment;

class ConfirmDeliveryController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $shipment = Shipment::query()
            ->where('delivery_token', $token)
            ->with(['order.customer'])
            ->first();

        if (! $shipment) {
            return redirect()->route('shop.index')->with('error', 'Enlace de confirmacion invalido o expirado.');
        }

        if ($shipment->delivered_at) {
            return view('ecommerce::deliveries.already-confirmed', compact('shipment'));
        }

        return view('ecommerce::deliveries.confirm', compact('shipment'));
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $shipment = Shipment::query()
            ->where('delivery_token', $token)
            ->first();

        if (! $shipment || $shipment->delivered_at) {
            return redirect()->route('shop.index');
        }

        $shipment->update([
            'delivered_at' => now(),
            'delivered_by' => $request->ip(),
            'status' => 'delivered',
        ]);

        $shipment->order?->update(['status' => 'completed']);

        return redirect()->route('ecommerce.delivery.confirmed', $token);
    }

    public function confirmed(string $token): View
    {
        $shipment = Shipment::query()
            ->where('delivery_token', $token)
            ->with(['order.customer'])
            ->firstOrFail();

        return view('ecommerce::deliveries.confirmed', compact('shipment'));
    }
}
