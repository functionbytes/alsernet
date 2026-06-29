<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Ecommerce\Http\Requests\Admin\StoreShippingRuleRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateShippingRuleRequest;
use Modules\Ecommerce\Models\Shipping;
use Modules\Ecommerce\Models\ShippingRule;

class ShippingRuleController extends Controller
{
    public function store(StoreShippingRuleRequest $request, Shipping $shipping): RedirectResponse|JsonResponse
    {
        $rule = $shipping->rules()->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Regla creada.',
                'data' => $rule,
            ], 201);
        }

        return redirect()->route('ecommerce.shipping.edit', $shipping)->with('success', 'Regla de envio creada.');
    }

    public function update(UpdateShippingRuleRequest $request, Shipping $shipping, ShippingRule $rule): RedirectResponse|JsonResponse
    {
        $rule->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Regla actualizada.',
                'data' => $rule->fresh(),
            ]);
        }

        return redirect()->route('ecommerce.shipping.edit', $shipping)->with('success', 'Regla de envio actualizada.');
    }

    public function destroy(Shipping $shipping, ShippingRule $rule): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $shipping);

        $rule->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Regla eliminada.']);
        }

        return redirect()->route('ecommerce.shipping.edit', $shipping)->with('success', 'Regla de envio eliminada.');
    }
}
