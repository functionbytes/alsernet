<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreProductAttributeSetRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateProductAttributeSetRequest;
use Modules\Ecommerce\Models\ProductAttributeSet;

class ProductAttributeSetController extends Controller
{
    private const CHECKBOX_FIELDS = ['is_searchable', 'is_comparable', 'is_use_in_product_listing'];

    public function index(): View
    {
        $productAttributeSets = ProductAttributeSet::query()
            ->withCount('attributes')
            ->latest()
            ->paginate(20);

        return view('ecommerce::attribute-sets.index', compact('productAttributeSets'));
    }

    public function create(): View
    {
        return view('ecommerce::attribute-sets.create');
    }

    public function store(StoreProductAttributeSetRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach (self::CHECKBOX_FIELDS as $field) {
            $data[$field] = $request->has($field);
        }

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        ProductAttributeSet::query()->create($data);

        return redirect()->route('ecommerce.product-attribute-sets.index')
            ->with('success', 'Conjunto de atributos creado exitosamente.');
    }

    public function edit(ProductAttributeSet $productAttributeSet): View
    {
        $productAttributeSet->load(['attributes' => fn ($q) => $q->orderBy('order')]);

        return view('ecommerce::attribute-sets.edit', compact('productAttributeSet'));
    }

    public function update(UpdateProductAttributeSetRequest $request, ProductAttributeSet $productAttributeSet): RedirectResponse
    {
        $data = $request->validated();

        foreach (self::CHECKBOX_FIELDS as $field) {
            $data[$field] = $request->has($field);
        }

        $productAttributeSet->update($data);

        return redirect()->route('ecommerce.product-attribute-sets.index')
            ->with('success', 'Conjunto de atributos actualizado exitosamente.');
    }

    public function destroy(ProductAttributeSet $productAttributeSet): RedirectResponse
    {
        if ($productAttributeSet->products()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar un conjunto de atributos que tiene productos asociados.');
        }

        $productAttributeSet->delete();

        return redirect()->route('ecommerce.product-attribute-sets.index')
            ->with('success', 'Conjunto de atributos eliminado exitosamente.');
    }
}
