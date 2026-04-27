<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\ProductCollection;

class ProductCollectionController extends Controller
{
    public function index(Request $request): View
    {
        $collections = ProductCollection::query()
            ->when($request->input('search'), function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->paginate(20);

        return view('ecommerce::collections.index', compact('collections'));
    }

    public function create(): View
    {
        return view('ecommerce::collections.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'unique:ecommerce_product_collections,slug'],
            'description' => ['nullable', 'string', 'max:400'],
            'status' => ['required', 'in:published,draft'],
        ]);

        ProductCollection::query()->create($validated);

        return redirect()->route('ecommerce.collections.index')->with('success', 'Coleccion creada.');
    }

    public function edit(ProductCollection $collection): View
    {
        return view('ecommerce::collections.edit', compact('collection'));
    }

    public function update(Request $request, ProductCollection $collection): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'unique:ecommerce_product_collections,slug,'.$collection->id],
            'description' => ['nullable', 'string', 'max:400'],
            'status' => ['required', 'in:published,draft'],
        ]);

        $collection->update($validated);

        return redirect()->route('ecommerce.collections.index')->with('success', 'Coleccion actualizada.');
    }

    public function destroy(ProductCollection $collection): RedirectResponse
    {
        $collection->delete();

        return redirect()->route('ecommerce.collections.index')->with('success', 'Coleccion eliminada.');
    }
}
