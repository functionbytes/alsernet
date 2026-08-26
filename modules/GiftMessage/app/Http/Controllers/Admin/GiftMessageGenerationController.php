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
use Modules\GiftMessage\Services\GiftMessageOrderService;
use Modules\GiftMessage\Services\GiftMessagePdfService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GiftMessageGenerationController extends Controller
{
    public function __construct(
        private readonly GiftMessagePdfService $pdfService,
        private readonly GiftMessageGenerationService $generationService,
        private readonly GiftMessageOrderService $orderService
    ) {}

    public function generate(GenerateGiftMessagePdfRequest $request): Response|JsonResponse
    {
        $data = $request->validated();

        $pdfContent = $this->pdfService->generate($data['type'], $data['rows'])->output();

        $generation = $this->generationService->store($data['type'], $data['rows'], $pdfContent);

        // El panel dispara la generacion via AJAX (modal de accion masiva) y
        // abre el PDF ya guardado en una pestana nueva a traves de la ruta de
        // visualizacion del historico; el POST directo (usado por los tests)
        // sigue devolviendo el PDF inline.
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'view_url' => route('giftmessage.history.view', $generation),
            ]);
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$generation->file_name.'"',
        ]);
    }

    /**
     * Reimprime UN pedido de un PDF que salio con varios: genera un PDF nuevo
     * con esa sola pagina y lo guarda como una generacion mas, sin tocar la
     * original. Sirve para cuando una unica tarjeta sale mal de la impresora y
     * no tiene sentido rehacer el lote entero.
     */
    public function regenerateOrder(Request $request, GiftMessageGeneration $generation): JsonResponse
    {
        $this->authorize('view', $generation);

        // Reimprimir es generar: se pide el permiso de creacion, no el de lectura.
        if (! $request->user()->can('giftmessage.create')) {
            abort(403, 'No tienes permiso para generar PDF de mensaje regalo.');
        }

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
        ], [
            'order_number.required' => 'Indica que pedido quieres reimprimir.',
        ]);

        $orderNumber = $validated['order_number'];

        if (! in_array($orderNumber, $generation->order_numbers ?? [], true)) {
            return response()->json([
                'message' => 'Ese pedido no forma parte de este PDF.',
            ], 422);
        }

        $row = $this->resolveRow($generation, $orderNumber);

        if ($row === null) {
            return response()->json([
                'message' => 'No se pudo recuperar el mensaje regalo del pedido '.$orderNumber.'. Buscalo en el panel y generalo desde ahi.',
            ], 422);
        }

        $pdfContent = $this->pdfService->generate($generation->type, [$row])->output();
        $new = $this->generationService->store($generation->type, [$row], $pdfContent);

        return response()->json([
            'success' => true,
            'view_url' => route('giftmessage.history.view', $new),
            'order_number' => $orderNumber,
        ]);
    }

    /**
     * La fila guardada en la generacion es la fuente preferente: reimprime
     * exactamente lo que se imprimio. Las generaciones anteriores a la columna
     * `rows` no la tienen, asi que para esas se vuelve a preguntar al bridge.
     *
     * @return array<string, mixed>|null
     */
    private function resolveRow(GiftMessageGeneration $generation, string $orderNumber): ?array
    {
        $row = $this->generationService->rowFor($generation, $orderNumber);

        if ($row !== null && trim((string) ($row['gift_message'] ?? '')) !== '') {
            return $row;
        }

        $found = $this->orderService->searchByIds([$orderNumber]);

        return $found[0] ?? null;
    }

    public function historyIndex(Request $request): View
    {
        $this->authorize('viewAny', GiftMessageGeneration::class);

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200], true) ? $perPage : 10;

        return view('giftmessage::admin.history.index', [
            'pageTitle' => 'Historial de mensajes regalo',
            'generations' => $this->generationService->list(
                $request->only(['type', 'date_from', 'date_to', 'search']),
                $perPage
            ),
            'stats' => $this->generationService->stats(),
        ]);
    }

    public function view(GiftMessageGeneration $generation): StreamedResponse
    {
        $this->authorize('view', $generation);

        return Storage::disk('public')->response($generation->file_path, $generation->file_name, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function download(GiftMessageGeneration $generation): StreamedResponse
    {
        $this->authorize('view', $generation);

        return Storage::disk('public')->download($generation->file_path, $generation->file_name);
    }

    public function destroy(Request $request, GiftMessageGeneration $generation): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $generation);

        $this->generationService->delete($generation);

        // El historial borra por AJAX para poder explicar el fallo en un toast
        // (sin permiso, sesion caducada...) en vez de tumbar la pagina entera
        // con la vista de error; el POST clasico sigue redirigiendo.
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Generacion eliminada correctamente.',
            ]);
        }

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
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'Selecciona al menos una generacion.',
            'ids.min' => 'Selecciona al menos una generacion.',
        ]);

        // Sin regla 'exists': si alguien borro una de las filas mientras el
        // listado estaba abierto, la seleccion entera fallaba con un 422 en vez
        // de borrar el resto. bulkAction() ya ignora los ids que no encuentra.
        $deleted = $this->generationService->bulkAction($validated['ids'], $validated['action']);

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
        ]);
    }
}
