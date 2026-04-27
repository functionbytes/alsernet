<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariation;
use Modules\Ecommerce\Models\ProductVariationItem;

class ProductVariationController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'attribute_ids' => ['nullable', 'array'],
            'attribute_ids.*' => ['integer', 'exists:ecommerce_product_attributes,id'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'price.required' => 'El precio es obligatorio.',
        ]);

        DB::transaction(function () use ($request, $product) {
            $variationProduct = Product::query()->create([
                'name' => $request->input('name'),
                'slug' => Str::slug($request->input('name')).'-'.Str::random(6),
                'sku' => $request->input('sku'),
                'price' => $request->input('price'),
                'quantity' => $request->input('quantity', 0),
                'is_variation' => true,
                'status' => $product->status,
                'with_storehouse_management' => $product->with_storehouse_management,
            ]);

            if ($request->boolean('is_default')) {
                ProductVariation::query()
                    ->where('configurable_product_id', $product->id)
                    ->update(['is_default' => false]);
            }

            $variation = ProductVariation::query()->create([
                'product_id' => $variationProduct->id,
                'configurable_product_id' => $product->id,
                'is_default' => $request->boolean('is_default'),
            ]);

            foreach ($request->input('attribute_ids', []) as $attributeId) {
                ProductVariationItem::query()->create([
                    'variation_id' => $variation->id,
                    'attribute_id' => $attributeId,
                ]);
            }
        });

        return back()->with('success', 'Variacion agregada exitosamente.');
    }

    public function setDefault(Product $product, ProductVariation $variation): RedirectResponse
    {
        ProductVariation::query()
            ->where('configurable_product_id', $product->id)
            ->update(['is_default' => false]);

        $variation->update(['is_default' => true]);

        return back()->with('success', 'Variacion predeterminada actualizada.');
    }

    public function destroy(Product $product, ProductVariation $variation): RedirectResponse
    {
        DB::transaction(function () use ($variation) {
            $variation->variationItems()->delete();

            if ($variation->product_id) {
                Product::query()->where('id', $variation->product_id)->delete();
            }

            $variation->delete();
        });

        return back()->with('success', 'Variacion eliminada exitosamente.');
    }
}
