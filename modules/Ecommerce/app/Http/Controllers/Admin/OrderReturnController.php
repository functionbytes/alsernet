<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\OrderReturn;

class OrderReturnController extends Controller
{
    public function index(Request $request): View
    {
        $returns = OrderReturn::query()
            ->with(['order', 'customer'])
            ->latest()
            ->paginate(20);

        return view('ecommerce::admin.returns.index', compact('returns'));
    }

    public function show(OrderReturn $return): View
    {
        return view('ecommerce::admin.returns.show', compact('return'));
    }

    public function updateStatus(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'return_status' => ['required', 'string'],
        ]);

        $return->update(['return_status' => $validated['return_status']]);

        return back()->with('success', 'Estado de devolucion actualizado.');
    }
}
