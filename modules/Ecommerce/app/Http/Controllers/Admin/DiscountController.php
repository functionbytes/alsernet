<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreDiscountRequest;
use Modules\Ecommerce\Models\Discount;

class DiscountController extends Controller
{
    public function index(): View
    {
        $discounts = Discount::query()->latest()->paginate(20);

        return view('ecommerce::discounts.index', compact('discounts'));
    }

    public function create(): View
    {
        return view('ecommerce::discounts.create');
    }

    public function store(StoreDiscountRequest $request): RedirectResponse
    {
        Discount::query()->create($request->validated());

        return redirect()->route('ecommerce.discounts.index')->with('success', 'Descuento creado exitosamente.');
    }

    public function edit(Discount $discount): View
    {
        return view('ecommerce::discounts.edit', compact('discount'));
    }

    public function update(StoreDiscountRequest $request, Discount $discount): RedirectResponse
    {
        $discount->update($request->validated());

        return redirect()->route('ecommerce.discounts.index')->with('success', 'Descuento actualizado exitosamente.');
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        $discount->delete();

        return redirect()->route('ecommerce.discounts.index')->with('success', 'Descuento eliminado exitosamente.');
    }

    public function generateCode(): JsonResponse
    {
        $code = strtoupper(substr(md5(uniqid()), 0, 8));

        return response()->json(['code' => $code]);
    }
}
