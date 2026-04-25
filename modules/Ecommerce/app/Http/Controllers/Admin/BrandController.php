<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreBrandRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateBrandRequest;
use Modules\Ecommerce\Models\Brand;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::query()->latest()->paginate(20);

        return view('ecommerce::admin.brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('ecommerce::admin.brands.create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        Brand::query()->create($request->validated());

        return redirect()->route('ecommerce.brands.index')->with('success', 'Marca creada exitosamente.');
    }

    public function edit(Brand $brand): View
    {
        return view('ecommerce::admin.brands.edit', compact('brand'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        return redirect()->route('ecommerce.brands.index')->with('success', 'Marca actualizada exitosamente.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return redirect()->route('ecommerce.brands.index')->with('success', 'Marca eliminada exitosamente.');
    }
}
