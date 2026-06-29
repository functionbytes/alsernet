<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Ecommerce\Http\Requests\Admin\StoreShippingRuleItemRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateShippingRuleItemRequest;
use Modules\Ecommerce\Models\ShippingRule;
use Modules\Ecommerce\Models\ShippingRuleItem;

class ShippingRuleItemController extends Controller
{
    public function index(ShippingRule $rule): JsonResponse
    {
        $items = $rule->items()->orderBy('state')->orderBy('city')->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(StoreShippingRuleItemRequest $request, ShippingRule $rule): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['is_enabled'] = $request->has('is_enabled');

        $rule->items()->create($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Item de regla creado.']);
        }

        return redirect()->route('ecommerce.shipping.edit', $rule->shipping_id)
            ->with('success', 'Item de regla de envio creado.');
    }

    public function update(UpdateShippingRuleItemRequest $request, ShippingRule $rule, ShippingRuleItem $item): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['is_enabled'] = $request->has('is_enabled');

        $item->update($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Item de regla actualizado.']);
        }

        return redirect()->route('ecommerce.shipping.edit', $rule->shipping_id)
            ->with('success', 'Item de regla de envio actualizado.');
    }

    public function destroy(ShippingRule $rule, ShippingRuleItem $item): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $rule->shipping);

        $item->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Item de regla eliminado.']);
        }

        return redirect()->route('ecommerce.shipping.edit', $rule->shipping_id)
            ->with('success', 'Item de regla de envio eliminado.');
    }
}
