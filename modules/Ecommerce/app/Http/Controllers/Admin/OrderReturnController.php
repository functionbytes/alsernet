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
            ->when($request->input('search'), fn ($q, $s) => $q->whereHas('order', fn ($oq) => $oq->where('code', 'like', "%{$s}%"))
            )
            ->when($request->input('status'), fn ($q, $s) => $q->where('return_status', $s))
            ->latest()
            ->paginate(20);

        $counts = OrderReturn::query()
            ->selectRaw('return_status, count(*) as total')
            ->groupBy('return_status')
            ->pluck('total', 'return_status');

        return view('ecommerce::returns.index', compact('returns', 'counts'));
    }

    public function show(OrderReturn $return): View
    {
        $return->load(['order.items.product', 'customer', 'items', 'histories']);

        return view('ecommerce::returns.show', compact('return'));
    }

    public function edit(OrderReturn $return): View
    {
        $return->load(['order', 'customer']);

        return view('ecommerce::returns.edit', compact('return'));
    }

    public function update(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
            'return_status' => ['required', 'string', 'in:pending,approved,rejected,completed'],
            'order_status' => ['nullable', 'string'],
        ], [
            'return_status.required' => 'El estado es obligatorio.',
            'return_status.in' => 'Estado invalido.',
        ]);

        $return->update($validated);

        return redirect()->route('ecommerce.returns.show', $return)->with('success', 'Devolucion actualizada exitosamente.');
    }

    public function updateStatus(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'return_status' => ['required', 'string', 'in:pending,approved,rejected,completed'],
        ]);

        $return->update(['return_status' => $validated['return_status']]);

        $return->histories()->create([
            'action' => $validated['return_status'],
            'description' => 'Estado actualizado a: '.$validated['return_status'],
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Estado de devolución actualizado.');
    }

    public function destroy(OrderReturn $return): RedirectResponse
    {
        $return->delete();

        return redirect()->route('ecommerce.returns.index')->with('success', 'Devolución eliminada.');
    }
}
