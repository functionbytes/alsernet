<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreShipmentHistoryRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateShipmentRequest;
use Modules\Ecommerce\Models\Shipment;

class ShipmentController extends Controller
{
    public function index(Request $request): View
    {
        $shipments = Shipment::query()
            ->with(['order.customer'])
            ->when($request->input('search'), fn ($q, $s) => $q->whereHas('order', fn ($oq) => $oq->where('code', 'like', "%{$s}%"))
            )
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        $counts = Shipment::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('ecommerce::shipments.index', compact('shipments', 'counts'));
    }

    public function show(Shipment $shipment): View
    {
        return view('ecommerce::shipments.show', compact('shipment'));
    }

    public function edit(Shipment $shipment): View
    {
        $shipment->load(['order.customer', 'order.items.product', 'order.shippingAddress', 'histories']);

        return view('ecommerce::shipments.edit', compact('shipment'));
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        $shipment->update($request->validated());

        return back()->with('success', 'Envio actualizado.');
    }

    public function addHistory(StoreShipmentHistoryRequest $request, Shipment $shipment): RedirectResponse
    {
        $shipment->histories()->create([
            'description' => $request->validated('description'),
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Historial agregado.');
    }

    public function updateStatus(Request $request, Shipment $shipment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $shipment->update(['status' => $validated['status']]);

        return back()->with('success', 'Estado de envio actualizado.');
    }

    public function generateDeliveryToken(Shipment $shipment): JsonResponse
    {
        if (! $shipment->delivery_token) {
            $shipment->update([
                'delivery_token' => Str::random(32),
            ]);
        }

        $url = route('ecommerce.delivery.confirm', $shipment->delivery_token);

        return response()->json(['token' => $shipment->delivery_token, 'url' => $url]);
    }
}
