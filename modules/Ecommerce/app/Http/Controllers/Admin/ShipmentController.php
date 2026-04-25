<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Shipment;

class ShipmentController extends Controller
{
    public function index(Request $request): View
    {
        $shipments = Shipment::query()
            ->with('order')
            ->latest()
            ->paginate(20);

        return view('ecommerce::admin.shipments.index', compact('shipments'));
    }

    public function show(Shipment $shipment): View
    {
        return view('ecommerce::admin.shipments.show', compact('shipment'));
    }

    public function updateStatus(Request $request, Shipment $shipment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $shipment->update(['status' => $validated['status']]);

        return back()->with('success', 'Estado de envio actualizado.');
    }
}
