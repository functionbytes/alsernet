<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\ProductTag;

class ProductTagController extends Controller
{
    public function index(Request $request): View
    {
        $tags = ProductTag::query()
            ->when($request->input('search'), function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->paginate(20);

        return view('ecommerce::admin.tags.index', compact('tags'));
    }

    public function create(): View
    {
        return view('ecommerce::admin.tags.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:400'],
            'status' => ['required', 'in:published,draft'],
        ]);

        ProductTag::query()->create($validated);

        return redirect()->route('ecommerce.tags.index')->with('success', 'Etiqueta creada.');
    }

    public function edit(ProductTag $tag): View
    {
        return view('ecommerce::admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, ProductTag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:400'],
            'status' => ['required', 'in:published,draft'],
        ]);

        $tag->update($validated);

        return redirect()->route('ecommerce.tags.index')->with('success', 'Etiqueta actualizada.');
    }

    public function destroy(ProductTag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect()->route('ecommerce.tags.index')->with('success', 'Etiqueta eliminada.');
    }
}
