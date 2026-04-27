<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreProductLabelRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateProductLabelRequest;
use Modules\Ecommerce\Models\ProductLabel;

class ProductLabelController extends Controller
{
    public function index(): View
    {
        $labels = ProductLabel::query()->latest()->paginate(20);

        return view('ecommerce::product-labels.index', compact('labels'));
    }

    public function create(): View
    {
        return view('ecommerce::product-labels.create');
    }

    public function store(StoreProductLabelRequest $request): RedirectResponse
    {
        ProductLabel::query()->create($request->validated());

        return redirect()->route('ecommerce.product-labels.index')
            ->with('success', 'Etiqueta creada exitosamente.');
    }

    public function edit(ProductLabel $productLabel): View
    {
        return view('ecommerce::product-labels.edit', compact('productLabel'));
    }

    public function update(UpdateProductLabelRequest $request, ProductLabel $productLabel): RedirectResponse
    {
        $productLabel->update($request->validated());

        return redirect()->route('ecommerce.product-labels.index')
            ->with('success', 'Etiqueta actualizada exitosamente.');
    }

    public function destroy(ProductLabel $productLabel): RedirectResponse
    {
        $productLabel->delete();

        return redirect()->route('ecommerce.product-labels.index')
            ->with('success', 'Etiqueta eliminada exitosamente.');
    }
}
