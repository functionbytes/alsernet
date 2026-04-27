<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Shipping;

class ShippingController extends Controller
{
    public function index(): View
    {
        $shippings = Shipping::query()->with('rules')->paginate(20);

        return view('ecommerce::shipping.index', compact('shippings'));
    }

    public function create(): View
    {
        return view('ecommerce::shipping.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        $shipping = Shipping::query()->create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Zona creada.',
                'data' => $shipping->load('rules'),
            ], 201);
        }

        return redirect()->route('ecommerce.shipping.index')->with('success', 'Metodo de envio creado.');
    }

    public function edit(Shipping $shipping): View
    {
        $shipping->load('rules');

        return view('ecommerce::shipping.edit', compact('shipping'));
    }

    public function update(Request $request, Shipping $shipping): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        $shipping->update($validated);

        return redirect()->route('ecommerce.shipping.index')->with('success', 'Metodo de envio actualizado.');
    }

    public function destroy(Shipping $shipping): RedirectResponse|JsonResponse
    {
        $shipping->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Zona eliminada.']);
        }

        return redirect()->route('ecommerce.shipping.index')->with('success', 'Metodo de envio eliminado.');
    }

    public function toggleStatus(Shipping $shipping): JsonResponse
    {
        $shipping->update(['is_active' => ! $shipping->is_active]);

        return response()->json([
            'success' => true,
            'active' => (bool) $shipping->is_active,
        ]);
    }
}
