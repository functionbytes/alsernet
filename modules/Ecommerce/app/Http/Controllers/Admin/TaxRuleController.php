<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Ecommerce\Http\Requests\Admin\StoreTaxRuleRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateTaxRuleRequest;
use Modules\Ecommerce\Models\Tax;
use Modules\Ecommerce\Models\TaxRule;

class TaxRuleController extends Controller
{
    public function store(StoreTaxRuleRequest $request, Tax $tax): RedirectResponse
    {
        $tax->rules()->create($request->validated());

        return redirect()->route('ecommerce.taxes.edit', $tax)->with('success', 'Regla creada.');
    }

    public function update(UpdateTaxRuleRequest $request, Tax $tax, TaxRule $taxRule): RedirectResponse
    {
        $taxRule->update($request->validated());

        return redirect()->route('ecommerce.taxes.edit', $tax)->with('success', 'Regla actualizada.');
    }

    public function destroy(Tax $tax, TaxRule $taxRule): RedirectResponse
    {
        $this->authorize('update', $tax);

        $taxRule->delete();

        return redirect()->route('ecommerce.taxes.edit', $tax)->with('success', 'Regla eliminada.');
    }
}
