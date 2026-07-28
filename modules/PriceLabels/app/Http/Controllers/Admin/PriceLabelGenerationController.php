<?php

namespace Modules\PriceLabels\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\PriceLabels\Models\PriceLabelGeneration;
use Modules\PriceLabels\Models\PriceLabelTemplate;
use Modules\PriceLabels\Services\PriceLabelGenerationService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PriceLabelGenerationController extends Controller
{
    public function __construct(
        private readonly PriceLabelGenerationService $generationService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PriceLabelGeneration::class);

        $generations = $this->generationService->list($request->only(['template_id', 'type', 'date_from', 'date_to']));

        return view('pricelabels::admin.generations.index', [
            'pageTitle' => 'Historial de generaciones',
            'generations' => $generations,
            'templates' => PriceLabelTemplate::query()->orderBy('name')->pluck('name', 'id'),
            'stats' => $this->generationService->stats(),
        ]);
    }

    public function download(PriceLabelGeneration $generation): StreamedResponse
    {
        $this->authorize('view', $generation);

        return Storage::disk('public')->download($generation->file_path, $generation->file_name);
    }

    public function destroy(PriceLabelGeneration $generation): RedirectResponse
    {
        $this->authorize('delete', $generation);

        $this->generationService->delete($generation);

        return redirect()
            ->route('pricelabels.history.index')
            ->with('success', 'Generacion eliminada correctamente.');
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('delete', PriceLabelGeneration::class);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:price_label_generations,id'],
        ]);

        $this->generationService->bulkAction($validated['ids'], $validated['action']);

        return response()->json(['success' => true]);
    }
}
