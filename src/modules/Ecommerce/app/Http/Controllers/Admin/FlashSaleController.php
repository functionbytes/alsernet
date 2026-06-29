<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\FlashSale;

class FlashSaleController extends Controller
{
    public function index(Request $request): View
    {
        $flashSales = FlashSale::query()
            ->when($request->input('search'), function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);

        return view('ecommerce::flash-sales.index', compact('flashSales'));
    }

    public function create(): View
    {
        return view('ecommerce::flash-sales.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:published,draft'],
        ]);

        FlashSale::query()->create($validated);

        return redirect()->route('ecommerce.flash-sales.index')->with('success', 'Venta flash creada.');
    }

    public function edit(FlashSale $flashSale): View
    {
        return view('ecommerce::flash-sales.edit', compact('flashSale'));
    }

    public function update(Request $request, FlashSale $flashSale): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:published,draft'],
        ]);

        $flashSale->update($validated);

        return redirect()->route('ecommerce.flash-sales.index')->with('success', 'Venta flash actualizada.');
    }

    public function destroy(FlashSale $flashSale): RedirectResponse
    {
        $flashSale->delete();

        return redirect()->route('ecommerce.flash-sales.index')->with('success', 'Venta flash eliminada.');
    }
}
