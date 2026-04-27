<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Order;

class OrderTrackingController extends Controller
{
    public function show(): View
    {
        return view('ecommerce::shop.order-tracking');
    }

    public function search(Request $request): View
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $order = Order::query()
            ->where('code', $request->input('code'))
            ->with('shipments')
            ->first();

        if (! $order) {
            return view('ecommerce::shop.order-tracking')
                ->with('error', 'Orden no encontrada.');
        }

        return view('ecommerce::shop.order-tracking', compact('order'));
    }
}
