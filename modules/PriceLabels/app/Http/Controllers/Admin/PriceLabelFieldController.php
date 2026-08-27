<?php

namespace Modules\PriceLabels\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\PriceLabels\Http\Requests\StorePriceLabelFieldRequest;
use Modules\PriceLabels\Models\PriceLabelTemplate;
use Modules\PriceLabels\Services\PriceLabelTemplateService;

class PriceLabelFieldController extends Controller
{
    public function __construct(
        private readonly PriceLabelTemplateService $templateService
    ) {}

    public function store(StorePriceLabelFieldRequest $request, PriceLabelTemplate $priceLabelTemplate): RedirectResponse
    {
        $key = $this->templateService->addField($priceLabelTemplate, $request->validated());

        return redirect()
            ->route('pricelabels.edit', $priceLabelTemplate)
            ->with('success', 'Campo agregado correctamente.')
            ->with('new_field_key', $key);
    }

    public function destroy(PriceLabelTemplate $priceLabelTemplate, string $key): RedirectResponse
    {
        $this->authorize('update', $priceLabelTemplate);

        $this->templateService->removeField($priceLabelTemplate, $key);

        return redirect()
            ->route('pricelabels.edit', $priceLabelTemplate)
            ->with('success', 'Campo eliminado correctamente.');
    }
}
