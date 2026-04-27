<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Bundle;
use Modules\Ecommerce\Models\Product;

class BundleController extends Controller
{
    public function index(Request $request): View
    {
        $bundles = Bundle::query()
            ->withCount('products')
            ->when($request->input('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(20);

        return view('ecommerce::bundles.index', compact('bundles'));
    }

    public function create(): View
    {
        $products = Product::query()
            ->where('status', 'published')
            ->whereNull('is_variation')
            ->orWhere('is_variation', false)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'sale_price']);

        return view('ecommerce::bundles.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:ecommerce_bundles,slug'],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:published,draft'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'exists:ecommerce_products,id'],
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $bundle = Bundle::query()->create($validated);

            $syncData = [];
            foreach ($request->input('products', []) as $productId) {
                $syncData[$productId] = ['qty' => $request->input("quantities.{$productId}", 1)];
            }
            $bundle->products()->sync($syncData);
        });

        return redirect()->route('ecommerce.bundles.index')->with('success', 'Bundle creado correctamente.');
    }

    public function edit(Bundle $bundle): View
    {
        $bundle->load('products');

        $products = Product::query()
            ->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('is_variation')->orWhere('is_variation', false))
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'sale_price']);

        return view('ecommerce::bundles.edit', compact('bundle', 'products'));
    }

    public function update(Request $request, Bundle $bundle): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', "unique:ecommerce_bundles,slug,{$bundle->id}"],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:published,draft'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'exists:ecommerce_products,id'],
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $bundle, $request) {
            $bundle->update($validated);

            $syncData = [];
            foreach ($request->input('products', []) as $productId) {
                $syncData[$productId] = ['qty' => $request->input("quantities.{$productId}", 1)];
            }
            $bundle->products()->sync($syncData);
        });

        return redirect()->route('ecommerce.bundles.index')->with('success', 'Bundle actualizado correctamente.');
    }

    public function destroy(Bundle $bundle): RedirectResponse
    {
        $bundle->delete();

        return redirect()->route('ecommerce.bundles.index')->with('success', 'Bundle eliminado correctamente.');
    }
}
