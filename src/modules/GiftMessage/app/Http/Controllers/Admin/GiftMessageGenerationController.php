<?php

namespace Modules\GiftMessage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Modules\GiftMessage\Http\Requests\GenerateGiftMessagePdfRequest;
use Modules\GiftMessage\Models\GiftMessageGeneration;
use Modules\GiftMessage\Services\GiftMessageGenerationService;
use Modules\GiftMessage\Services\GiftMessagePdfService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GiftMessageGenerationController extends Controller
{
    public function __construct(
        private readonly GiftMessagePdfService $pdfService,
        private readonly GiftMessageGenerationService $generationService
    ) {}

    public function generate(GenerateGiftMessagePdfRequest $request): Response
    {
        $data = $request->validated();

        $pdfContent = $this->pdfService->generate($data['type'], $data['rows'])->output();

        $generation = $this->generationService->store($data['type'], count($data['rows']), $pdfContent);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$generation->file_name.'"',
        ]);
    }

    public function historyIndex(Request $request): View
    {
        $this->authorize('viewAny', GiftMessageGeneration::class);

        return view('giftmessage::admin.history.index', [
            'pageTitle' => 'Historial de mensajes regalo',
            'generations' => $this->generationService->list($request->only(['type', 'date_from', 'date_to'])),
        ]);
    }

    public function download(GiftMessageGeneration $generation): StreamedResponse
    {
        $this->authorize('view', $generation);

        return Storage::disk('public')->download($generation->file_path, $generation->file_name);
    }

    public function destroy(GiftMessageGeneration $generation): RedirectResponse
    {
        $this->authorize('delete', $generation);

        $this->generationService->delete($generation);

        return redirect()
            ->route('giftmessage.history.index')
            ->with('success', 'Generacion eliminada correctamente.');
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('delete', GiftMessageGeneration::class);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:gift_message_generations,id'],
        ]);

        $this->generationService->bulkAction($validated['ids'], $validated['action']);

        return response()->json(['success' => true]);
    }
}
