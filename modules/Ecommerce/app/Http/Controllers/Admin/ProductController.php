<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Features\EcommerceFeatures;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Modules\Ecommerce\Http\Requests\Admin\StoreProductRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateProductRequest;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductAttributeSet;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Models\ProductCollection;
use Modules\Ecommerce\Models\ProductLabel;
use Modules\Ecommerce\Models\ProductTag;
use Modules\Ecommerce\Models\SpecificationTable;
use Modules\Ecommerce\Services\AiContentService;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['brand', 'categories'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(20);

        return view('ecommerce::products.index', compact('products'));
    }

    public function create(): View
    {
        $brands = Brand::query()->where('status', 'published')->pluck('name', 'id');
        $categories = ProductCategory::query()->where('status', 'published')->get();
        $tags = ProductTag::query()->where('status', 'published')->pluck('name', 'id');
        $collections = ProductCollection::query()->where('status', 'published')->pluck('name', 'id');
        $labels = ProductLabel::query()->where('status', 'published')->get();
        $attributeSets = ProductAttributeSet::query()->where('status', 'published')->get();
        $specificationTables = SpecificationTable::query()->orderBy('name')->get();

        return view('ecommerce::products.create', compact('brands', 'categories', 'tags', 'collections', 'labels', 'attributeSets', 'specificationTables'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request, &$product) {
            $product = Product::query()->create($request->validated());
            $product->categories()->sync($request->input('categories', []));
            $product->tags()->sync($request->input('tags', []));
            $product->collections()->sync($request->input('collections', []));
            $product->attributeSets()->sync($request->input('attribute_sets', []));
            $product->specificationTables()->sync($request->input('specification_tables', []));

            foreach ($request->input('options', []) as $optionData) {
                $option = $product->options()->create([
                    'name' => $optionData['name'],
                    'option_type' => $optionData['option_type'] ?? 'text',
                    'required' => ! empty($optionData['required']),
                    'order' => $optionData['order'] ?? 0,
                ]);
                foreach ($optionData['values'] ?? [] as $valData) {
                    $option->values()->create([
                        'option_value' => $valData['option_value'],
                        'affect_price' => $valData['affect_price'] ?? 0,
                        'affect_type' => $valData['affect_type'] ?? 'plus',
                        'order' => $valData['order'] ?? 0,
                    ]);
                }
            }
        });

        return redirect()->route('ecommerce.products.index')->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Product $product): View
    {
        $product->load(['attributeSets.attributes', 'specificationAttributes', 'crossSales', 'relatedProducts', 'specificationTables', 'options.values', 'label', 'variations.product', 'variations.variationItems.attribute']);

        $brands = Brand::query()->where('status', 'published')->pluck('name', 'id');
        $categories = ProductCategory::query()->where('status', 'published')->get();
        $tags = ProductTag::query()->where('status', 'published')->pluck('name', 'id');
        $collections = ProductCollection::query()->where('status', 'published')->pluck('name', 'id');
        $labels = ProductLabel::query()->where('status', 'published')->get();
        $attributeSets = ProductAttributeSet::query()->where('status', 'published')->with('attributes')->get();
        $specificationTables = SpecificationTable::query()->orderBy('name')->get();

        return view('ecommerce::products.edit', compact('product', 'brands', 'categories', 'tags', 'collections', 'labels', 'attributeSets', 'specificationTables'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $product->update($request->validated());
            $product->categories()->sync($request->input('categories', []));
            $product->tags()->sync($request->input('tags', []));
            $product->collections()->sync($request->input('collections', []));
            $product->attributeSets()->sync($request->input('attribute_sets', []));
            $product->specificationTables()->sync($request->input('specification_tables', []));
            $product->crossSales()->sync($request->input('cross_sales', []));
            $product->relatedProducts()->sync($request->input('related_products', []));

            $specAttrs = [];
            foreach ($request->input('spec_attributes', []) as $attrId => $value) {
                if ($value !== null && $value !== '') {
                    $specAttrs[$attrId] = ['value' => $value, 'hidden' => 0, 'order' => 0];
                }
            }
            $product->specificationAttributes()->sync($specAttrs);

            $product->options()->each(fn ($o) => $o->delete());
            foreach ($request->input('options', []) as $optionData) {
                $option = $product->options()->create([
                    'name' => $optionData['name'],
                    'option_type' => $optionData['option_type'] ?? 'text',
                    'required' => ! empty($optionData['required']),
                    'order' => $optionData['order'] ?? 0,
                ]);
                foreach ($optionData['values'] ?? [] as $valData) {
                    $option->values()->create([
                        'option_value' => $valData['option_value'],
                        'affect_price' => $valData['affect_price'] ?? 0,
                        'affect_type' => $valData['affect_type'] ?? 'plus',
                        'order' => $valData['order'] ?? 0,
                    ]);
                }
            }
        });

        return redirect()->route('ecommerce.products.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('ecommerce.products.index')->with('success', 'Producto eliminado exitosamente.');
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $exclude = $request->input('exclude', []);

        $products = Product::query()
            ->where('status', 'published')
            ->where('name', 'like', "%{$query}%")
            ->whereNotIn('id', (array) $exclude)
            ->select(['id', 'name', 'sku', 'featured_image', 'price'])
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', 'in:publish,unpublish,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:ecommerce_products,id'],
        ]);

        $ids = $request->input('ids');

        $count = match ($request->input('action')) {
            'publish' => Product::query()->whereIn('id', $ids)->update(['status' => 'published']),
            'unpublish' => Product::query()->whereIn('id', $ids)->update(['status' => 'draft']),
            'delete' => Product::query()->whereIn('id', $ids)->delete(),
        };

        return response()->json(['message' => "{$count} productos afectados", 'affected' => $count]);
    }

    public function inlineUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:ecommerce_products,id'],
            'field' => ['required', 'string', 'in:price,quantity,sale_price,name'],
            'value' => ['required'],
        ]);

        Product::query()
            ->where('id', $request->input('id'))
            ->update([$request->input('field') => $request->input('value')]);

        return response()->json(['ok' => true]);
    }

    public function generateDescription(Product $product, AiContentService $ai): JsonResponse
    {
        if (! Feature::active(EcommerceFeatures::AI_DESCRIPTIONS)) {
            return response()->json(['error' => 'La generación automática de descripciones no está disponible.'], 503);
        }

        $description = $ai->generateDescription($product);

        if (! $description) {
            return response()->json([
                'error' => 'No se pudo generar la descripción. Verifica la clave OPENAI_API_KEY o intenta de nuevo.',
            ], 503);
        }

        return response()->json(['description' => $description]);
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $product->load(['categories', 'tags', 'collections', 'attributeSets', 'specificationTables', 'options.values']);

        DB::transaction(function () use ($product) {
            $newProduct = $product->replicate();
            $newProduct->slug = $product->slug.'-copy-'.uniqid();
            $newProduct->sku = $product->sku ? $product->sku.'-COPY-'.uniqid() : null;
            $newProduct->save();

            $newProduct->categories()->sync($product->categories->pluck('id'));
            $newProduct->tags()->sync($product->tags->pluck('id'));
            $newProduct->collections()->sync($product->collections->pluck('id'));
            $newProduct->attributeSets()->sync($product->attributeSets->pluck('id'));
            $newProduct->specificationTables()->sync($product->specificationTables->pluck('id'));

            foreach ($product->options as $option) {
                $newOption = $newProduct->options()->create([
                    'name' => $option->name,
                    'option_type' => $option->option_type,
                    'required' => $option->required,
                    'order' => $option->order,
                ]);
                foreach ($option->values as $value) {
                    $newOption->values()->create([
                        'option_value' => $value->option_value,
                        'affect_price' => $value->affect_price,
                        'affect_type' => $value->affect_type,
                        'order' => $value->order,
                    ]);
                }
            }
        });

        return redirect()->route('ecommerce.products.index')->with('success', 'Producto duplicado exitosamente.');
    }
}
