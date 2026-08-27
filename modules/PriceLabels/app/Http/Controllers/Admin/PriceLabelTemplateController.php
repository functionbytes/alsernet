<?php

namespace Modules\PriceLabels\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\PriceLabels\Http\Requests\StorePriceLabelTemplateRequest;
use Modules\PriceLabels\Http\Requests\UpdatePriceLabelTemplateRequest;
use Modules\PriceLabels\Models\PriceLabelTemplate;
use Modules\PriceLabels\Services\PriceLabelBarcodeService;
use Modules\PriceLabels\Services\PriceLabelFontService;
use Modules\PriceLabels\Services\PriceLabelGenerationService;
use Modules\PriceLabels\Services\PriceLabelTemplateService;

class PriceLabelTemplateController extends Controller
{
    public function __construct(
        private readonly PriceLabelTemplateService $templateService,
        private readonly PriceLabelGenerationService $generationService,
        private readonly PriceLabelFontService $fontService,
        private readonly PriceLabelBarcodeService $barcodeService
    ) {}

    /**
     * Previsualizaciones de codigo/QR para el lienzo del editor, generadas a
     * partir de la ultima fila de ejemplo importada.
     *
     * @return array<string, string>
     */
    private function barcodePreviews(array $fieldDefinitions, ?array $sampleRow): array
    {
        $previews = [];

        foreach ($fieldDefinitions as $definition) {
            $type = $definition['type'] ?? 'text';

            if (! $this->barcodeService->isImageType($type)) {
                continue;
            }

            $value = $sampleRow[$definition['key']] ?? $definition['label'];
            $uri = $this->barcodeService->pngDataUri($type, (string) $value, $definition['barcode_type'] ?? 'C128');

            if ($uri) {
                $previews[$definition['key']] = $uri;
            }
        }

        return $previews;
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PriceLabelTemplate::class);

        $templates = $this->templateService->list($request->string('search')->toString() ?: null);

        return view('pricelabels::admin.templates.index', [
            'pageTitle' => 'Etiquetas de precio',
            'templates' => $templates,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PriceLabelTemplate::class);

        $fieldDefinitions = $this->templateService->defaultFieldDefinitions();

        return view('pricelabels::admin.templates.create', [
            'pageTitle' => 'Nueva plantilla',
            'defaultFields' => $this->templateService->defaultFields($fieldDefinitions),
            'fieldDefinitions' => $fieldDefinitions,
            'fieldKeys' => $this->templateService->fieldKeys($fieldDefinitions),
        ]);
    }

    public function store(StorePriceLabelTemplateRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image_vertical', 'image_horizontal']);

        $template = $this->templateService->create(
            $data,
            $request->file('image_vertical'),
            $request->file('image_horizontal')
        );

        return redirect()
            ->route('pricelabels.edit', $template)
            ->with('success', 'Plantilla creada correctamente.');
    }

    public function edit(PriceLabelTemplate $priceLabelTemplate): View
    {
        $this->authorize('update', $priceLabelTemplate);

        $fieldDefinitions = $priceLabelTemplate->field_definitions ?: $this->templateService->defaultFieldDefinitions();
        $sampleRow = $this->generationService->lastSampleRow($priceLabelTemplate);

        return view('pricelabels::admin.templates.edit', [
            'pageTitle' => 'Editar plantilla',
            'symbologies' => PriceLabelBarcodeService::SYMBOLOGIES,
            'barcodePreviews' => $this->barcodePreviews($fieldDefinitions, $sampleRow),
            'template' => $priceLabelTemplate,
            'fields' => $this->templateService->fieldsWithDefaults($priceLabelTemplate),
            'positionsVertical' => $this->templateService->positionsWithDefaults($priceLabelTemplate, 'vertical'),
            'positionsHorizontal' => $this->templateService->positionsWithDefaults($priceLabelTemplate, 'horizontal'),
            'imageVerticalUrl' => $priceLabelTemplate->image_vertical ? Storage::disk('public')->url($priceLabelTemplate->image_vertical) : null,
            'imageHorizontalUrl' => $priceLabelTemplate->image_horizontal ? Storage::disk('public')->url($priceLabelTemplate->image_horizontal) : null,
            'fieldDefinitions' => $fieldDefinitions,
            'fieldKeys' => $this->templateService->fieldKeys($fieldDefinitions),
            'sampleRow' => $this->generationService->lastSampleRow($priceLabelTemplate),
            'fontOptions' => $this->fontService->familyOptions(),
            'fontStacks' => $this->fontService->cssStacks(),
            'fontFaceCss' => $this->fontService->fontFaceCss(forPdf: false),
        ]);
    }

    public function editPositions(PriceLabelTemplate $priceLabelTemplate): View
    {
        $this->authorize('update', $priceLabelTemplate);

        $fieldDefinitions = $priceLabelTemplate->field_definitions ?: $this->templateService->defaultFieldDefinitions();
        $fieldLabels = collect($fieldDefinitions)->pluck('label', 'key')->all();
        $fieldLabels['label'] = 'Texto fijo (label)';
        $sampleRow = $this->generationService->lastSampleRow($priceLabelTemplate);

        return view('pricelabels::admin.templates.positions', [
            'pageTitle' => 'Editar posiciones',
            'barcodePreviews' => $this->barcodePreviews($fieldDefinitions, $sampleRow),
            'template' => $priceLabelTemplate,
            'fields' => $this->templateService->fieldsWithDefaults($priceLabelTemplate),
            'positionsVertical' => $this->templateService->positionsWithDefaults($priceLabelTemplate, 'vertical'),
            'positionsHorizontal' => $this->templateService->positionsWithDefaults($priceLabelTemplate, 'horizontal'),
            'imageVerticalUrl' => $priceLabelTemplate->image_vertical ? Storage::disk('public')->url($priceLabelTemplate->image_vertical) : null,
            'imageHorizontalUrl' => $priceLabelTemplate->image_horizontal ? Storage::disk('public')->url($priceLabelTemplate->image_horizontal) : null,
            'fieldKeys' => $this->templateService->fieldKeys($fieldDefinitions),
            'fieldLabels' => $fieldLabels,
            'sampleRow' => $this->generationService->lastSampleRow($priceLabelTemplate),
            'fontOptions' => $this->fontService->familyOptions(),
            'fontStacks' => $this->fontService->cssStacks(),
            'fontFaceCss' => $this->fontService->fontFaceCss(forPdf: false),
        ]);
    }

    public function update(UpdatePriceLabelTemplateRequest $request, PriceLabelTemplate $priceLabelTemplate): RedirectResponse
    {
        $this->authorize('update', $priceLabelTemplate);

        $data = $request->safe()->except(['image_vertical', 'image_horizontal']);

        $this->templateService->update(
            $priceLabelTemplate,
            $data,
            $request->file('image_vertical'),
            $request->file('image_horizontal')
        );

        return redirect()
            ->route('pricelabels.edit', $priceLabelTemplate)
            ->with('success', 'Plantilla actualizada correctamente.');
    }

    public function duplicate(PriceLabelTemplate $priceLabelTemplate): RedirectResponse
    {
        $this->authorize('create', PriceLabelTemplate::class);

        $copy = $this->templateService->duplicate($priceLabelTemplate);

        return redirect()
            ->route('pricelabels.edit', $copy)
            ->with('success', 'Plantilla duplicada correctamente.');
    }

    public function destroy(PriceLabelTemplate $priceLabelTemplate): RedirectResponse
    {
        $this->authorize('delete', $priceLabelTemplate);

        $this->templateService->delete($priceLabelTemplate);

        return redirect()
            ->route('pricelabels.index')
            ->with('success', 'Plantilla eliminada correctamente.');
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('delete', PriceLabelTemplate::class);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:delete,activate,deactivate'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:price_label_templates,id'],
        ]);

        $this->templateService->bulkAction($validated['ids'], $validated['action']);

        return response()->json(['success' => true]);
    }
}
