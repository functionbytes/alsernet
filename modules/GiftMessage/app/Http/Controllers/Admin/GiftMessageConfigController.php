<?php

namespace Modules\GiftMessage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\GiftMessage\Http\Requests\SaveGiftMessageFontsRequest;
use Modules\GiftMessage\Http\Requests\SaveGiftMessagePositionsRequest;
use Modules\GiftMessage\Http\Requests\UploadGiftMessageImagesRequest;
use Modules\GiftMessage\Models\GiftMessageConfig;
use Modules\GiftMessage\Services\GiftMessageConfigService;
use Modules\GiftMessage\Services\GiftMessageFontService;
use Modules\GiftMessage\Services\GiftMessagePdfService;

class GiftMessageConfigController extends Controller
{
    public function __construct(
        private readonly GiftMessageConfigService $configService,
        private readonly GiftMessageFontService $fontService
    ) {}

    public function index(): View
    {
        $this->authorize('update', GiftMessageConfig::class);

        return view('giftmessage::admin.settings.index', [
            'pageTitle' => 'Configuracion de mensaje regalo',
            'config' => $this->configService->current(),
            'fonts' => $this->fontService->familyOptions(),
            'uploadedFonts' => $this->fontService->all(),
            'fontFaceCss' => $this->fontService->fontFaceCss(forPdf: false),
            'fontStacks' => $this->fontService->cssStacks(),
        ]);
    }

    public function uploadImages(UploadGiftMessageImagesRequest $request): RedirectResponse|JsonResponse
    {
        $config = $this->configService->uploadImages([
            'envelope_image' => $request->file('envelope_image'),
            'card_image' => $request->file('card_image'),
        ]);

        // El formulario clasico sigue redirigiendo; la zona de arrastrar y soltar
        // sube por AJAX y necesita de vuelta la ruta publica de la imagen para
        // repintar la vista previa y el lienzo sin recargar la pagina.
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'images' => [
                    'envelope' => $config->envelope_image ? Storage::disk('public')->url($config->envelope_image) : null,
                    'card' => $config->card_image ? Storage::disk('public')->url($config->card_image) : null,
                ],
                'names' => [
                    'envelope' => $config->envelope_image ? basename($config->envelope_image) : null,
                    'card' => $config->card_image ? basename($config->card_image) : null,
                ],
            ]);
        }

        return redirect()
            ->route('settings.giftmessage.index')
            ->with('success', 'Imagenes actualizadas correctamente.');
    }

    public function saveContent(Request $request): RedirectResponse
    {
        $this->authorize('update', GiftMessageConfig::class);

        $validated = $request->validate([
            'scope' => ['required', 'string', 'in:envelope,card'],
            'env_t1_content' => ['nullable', 'string', 'in:message,recipient'],
            'card_t1_content' => ['nullable', 'string', 'in:message,recipient'],
        ]);

        $column = ($validated['scope'] === 'card' ? 'card' : 'env').'_t1_content';

        $this->configService->current()->update([
            $column => $validated[$column] ?? GiftMessagePdfService::CONTENT_MESSAGE,
        ]);

        return redirect()
            ->route('settings.giftmessage.index')
            ->with('success', 'Contenido del texto actualizado correctamente.');
    }

    public function saveLimits(Request $request): RedirectResponse
    {
        $this->authorize('update', GiftMessageConfig::class);

        $validated = $request->validate([
            'min_font_size' => ['required', 'integer', 'min:5', 'max:72'],
            'max_message_length' => ['required', 'integer', 'min:50', 'max:5000'],
        ], [
            'min_font_size.min' => 'Por debajo de 5 pt no se lee nada impreso.',
            'max_message_length.max' => 'El tope son 5.000 caracteres.',
        ]);

        $this->configService->current()->update($validated);

        return redirect()
            ->route('settings.giftmessage.index')
            ->with('success', 'Limites del texto actualizados correctamente.');
    }

    public function saveFonts(SaveGiftMessageFontsRequest $request): RedirectResponse
    {
        $this->configService->saveFonts($request->validated());

        return redirect()
            ->route('settings.giftmessage.index')
            ->with('success', 'Fuentes actualizadas correctamente.');
    }

    /**
     * Fuente y tamano que usaria el PDF para el texto de muestra, para que la
     * vista previa del editor no ensene una letra distinta a la que se imprime.
     */
    public function previewMetrics(Request $request, GiftMessagePdfService $pdfService): JsonResponse
    {
        $this->authorize('update', GiftMessageConfig::class);

        $validated = $request->validate([
            // Mismo tope que al generar (GenerateGiftMessagePdfRequest), para que
            // la vista previa no rechace un mensaje que si se puede imprimir.
            'message' => ['nullable', 'string', 'max:5000'],
            'order' => ['nullable', 'string', 'max:50'],
            'recipient' => ['nullable', 'string', 'max:120'],
            'boxes' => ['nullable', 'array'],
            'boxes.*.*.w' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'boxes.*.*.h' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $message = (string) ($validated['message'] ?? '');
        $order = (string) ($validated['order'] ?? '');
        $boxes = $validated['boxes'] ?? [];

        $recipient = (string) ($validated['recipient'] ?? '');

        return response()->json([
            'envelope' => $pdfService->previewMetrics('envelope', $message, $order, $boxes['envelope'] ?? [], $recipient),
            'card' => $pdfService->previewMetrics('card', $message, $order, $boxes['card'] ?? [], $recipient),
        ]);
    }

    public function savePositions(SaveGiftMessagePositionsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->configService->savePositions($data['scope'], collect($data)->except('scope')->all());

        return response()->json(['success' => true]);
    }
}
