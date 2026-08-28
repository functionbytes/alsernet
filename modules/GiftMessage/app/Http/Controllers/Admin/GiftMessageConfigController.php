<?php

namespace Modules\GiftMessage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        $aligns = implode(',', array_keys(GiftMessagePdfService::ALIGNMENTS));
        $valigns = implode(',', array_keys(GiftMessagePdfService::VERTICAL_ALIGNMENTS));

        $rules = [
            'scope' => ['required', 'string', 'in:envelope,card'],
            'env_t1_content' => ['nullable', 'string', 'in:message,recipient'],
            'card_t1_content' => ['nullable', 'string', 'in:message,recipient'],
        ];

        foreach (['env', 'card'] as $piece) {
            foreach (['t1', 't2'] as $slot) {
                $rules["{$piece}_{$slot}_align"] = ['nullable', 'string', 'in:'.$aligns];
                $rules["{$piece}_{$slot}_valign"] = ['nullable', 'string', 'in:'.$valigns];
            }
        }

        $validated = $request->validate($rules);

        $piece = $validated['scope'] === 'card' ? 'card' : 'env';
        $update = [
            $piece.'_t1_content' => $validated[$piece.'_t1_content'] ?? GiftMessagePdfService::CONTENT_MESSAGE,
        ];

        // Solo se tocan las columnas de la pieza que envia el formulario: cada
        // una tiene el suyo y no debe pisar a la otra.
        foreach (['t1', 't2'] as $slot) {
            $update["{$piece}_{$slot}_align"] = $validated["{$piece}_{$slot}_align"] ?? 'center';
            $update["{$piece}_{$slot}_valign"] = $validated["{$piece}_{$slot}_valign"] ?? 'middle';
        }

        $this->configService->current()->update($update);

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
            'paragraph_spacing' => ['required', 'numeric', 'min:0', 'max:2'],
        ], [
            'min_font_size.min' => 'Por debajo de 5 pt no se lee nada impreso.',
            'max_message_length.max' => 'El tope son 5.000 caracteres.',
            'paragraph_spacing.max' => 'Como mucho el doble del tamano de letra.',
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
            'aligns' => ['nullable', 'array'],
            'boxes.*.*.w' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'boxes.*.*.h' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $message = (string) ($validated['message'] ?? '');
        $order = (string) ($validated['order'] ?? '');
        $boxes = $validated['boxes'] ?? [];

        $recipient = (string) ($validated['recipient'] ?? '');

        $aligns = $validated['aligns'] ?? [];

        return response()->json([
            'envelope' => $pdfService->previewMetrics('envelope', $message, $order, $boxes['envelope'] ?? [], $recipient, $aligns['envelope'] ?? []),
            'card' => $pdfService->previewMetrics('card', $message, $order, $boxes['card'] ?? [], $recipient, $aligns['card'] ?? []),
        ]);
    }

    /**
     * PDF de prueba de una pieza con lo que el usuario tiene en pantalla, aunque
     * no lo haya guardado. No pasa por el historial: es solo para mirar como
     * queda antes de mandar nada a imprimir.
     */
    public function previewPdf(Request $request, GiftMessagePdfService $pdfService): Response
    {
        $this->authorize('update', GiftMessageConfig::class);

        $aligns = implode(',', array_keys(GiftMessagePdfService::ALIGNMENTS));
        $valigns = implode(',', array_keys(GiftMessagePdfService::VERTICAL_ALIGNMENTS));

        $validated = $request->validate([
            'scope' => ['required', 'string', 'in:envelope,card'],
            'message' => ['nullable', 'string', 'max:5000'],
            'recipient' => ['nullable', 'string', 'max:120'],
            'order' => ['nullable', 'string', 'max:50'],
            'content' => ['nullable', 'string', 'in:message,recipient'],
            'boxes' => ['nullable', 'array'],
            'boxes.*.x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'boxes.*.y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'boxes.*.w' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
            'boxes.*.h' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
            'styles' => ['nullable', 'array'],
            'styles.*.font' => ['nullable', 'string'],
            'styles.*.size' => ['nullable', 'integer', 'min:5', 'max:72'],
            'styles.*.color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'styles.*.opacity' => ['nullable', 'integer', 'min:0', 'max:100'],
            'styles.*.align' => ['nullable', 'string', 'in:'.$aligns],
            'styles.*.valign' => ['nullable', 'string', 'in:'.$valigns],
        ]);

        $type = $validated['scope'];
        $prefix = $type === 'card' ? 'card' : 'env';

        // Copia en memoria de la configuracion: se le aplican los cambios de la
        // pantalla y se genera con ella, sin tocar nada de lo guardado.
        $config = $this->configService->current()->replicate();
        $config->{$prefix.'_t1_content'} = $validated['content'] ?? $config->{$prefix.'_t1_content'};

        foreach (['t1', 't2'] as $slot) {
            foreach (['x', 'y', 'w', 'h'] as $axis) {
                $value = $validated['boxes'][$slot][$axis] ?? null;

                if ($value !== null) {
                    $config->{$prefix.'_'.$slot.'_'.$axis} = $value;
                }
            }

            foreach (['font', 'size', 'color', 'opacity', 'align', 'valign'] as $property) {
                $value = $validated['styles'][$slot][$property] ?? null;

                if ($value !== null && $value !== '') {
                    $config->{$prefix.'_'.$slot.'_'.$property} = $value;
                }
            }
        }

        $row = [
            'gift_message' => (string) ($validated['message'] ?? ''),
            'npedidocli' => (string) ($validated['order'] ?? ''),
            'firstname' => (string) ($validated['recipient'] ?? ''),
            'lastname' => '',
        ];

        $pdf = $pdfService->generateWith($config, $type, [$row]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="previsualizacion.pdf"',
        ]);
    }

    public function savePositions(SaveGiftMessagePositionsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->configService->savePositions($data['scope'], collect($data)->except('scope')->all());

        return response()->json(['success' => true]);
    }
}
