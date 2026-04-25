<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Tax;

class TaxController extends Controller
{
    public function index(Request $request): View
    {
        $taxes = Tax::query()->paginate(20);

        return view('ecommerce::admin.taxes.index', compact('taxes'));
    }

    public function create(): View
    {
        return view('ecommerce::admin.taxes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'priority' => ['nullable', 'integer'],
            'status' => ['required', 'in:published,draft'],
        ]);

        Tax::query()->create($validated);

        return redirect()->route('ecommerce.taxes.index')->with('success', 'Impuesto creado.');
    }

    public function edit(Tax $tax): View
    {
        return view('ecommerce::admin.taxes.edit', compact('tax'));
    }

    public function update(Request $request, Tax $tax): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'priority' => ['nullable', 'integer'],
            'status' => ['required', 'in:published,draft'],
        ]);

        $tax->update($validated);

        return redirect()->route('ecommerce.taxes.index')->with('success', 'Impuesto actualizado.');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        $tax->delete();

        return redirect()->route('ecommerce.taxes.index')->with('success', 'Impuesto eliminado.');
    }
}
