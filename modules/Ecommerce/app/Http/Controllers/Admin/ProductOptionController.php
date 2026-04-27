<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreProductOptionRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateProductOptionRequest;
use Modules\Ecommerce\Models\Option;

class ProductOptionController extends Controller
{
    public function index(): View
    {
        $options = Option::query()
            ->withCount('values')
            ->when(request('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(20);

        return view('ecommerce::product-options.index', compact('options'));
    }

    public function create(): View
    {
        return view('ecommerce::product-options.form');
    }

    public function store(StoreProductOptionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $option = Option::query()->create($request->safe()->except('values'));

            foreach ($request->input('values', []) as $value) {
                $option->values()->create(['option_value' => $value]);
            }
        });

        return redirect()->route('ecommerce.product-options.index')
            ->with('success', 'Opcion de producto creada exitosamente.');
    }

    public function edit(Option $option): View
    {
        $option->load('values');

        return view('ecommerce::product-options.form', compact('option'));
    }

    public function update(UpdateProductOptionRequest $request, Option $option): RedirectResponse
    {
        DB::transaction(function () use ($request, $option) {
            $option->update($request->safe()->except('values'));

            $option->values()->delete();

            foreach ($request->input('values', []) as $value) {
                $option->values()->create(['option_value' => $value]);
            }
        });

        return redirect()->route('ecommerce.product-options.index')
            ->with('success', 'Opcion de producto actualizada exitosamente.');
    }

    public function destroy(Option $option): RedirectResponse
    {
        $option->delete();

        return redirect()->route('ecommerce.product-options.index')
            ->with('success', 'Opcion de producto eliminada exitosamente.');
    }
}
