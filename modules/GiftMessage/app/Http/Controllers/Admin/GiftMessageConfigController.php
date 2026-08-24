<?php

namespace Modules\GiftMessage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\GiftMessage\Http\Requests\SaveGiftMessageFontsRequest;
use Modules\GiftMessage\Http\Requests\SaveGiftMessagePositionsRequest;
use Modules\GiftMessage\Http\Requests\UploadGiftMessageImagesRequest;
use Modules\GiftMessage\Models\GiftMessageConfig;
use Modules\GiftMessage\Services\GiftMessageConfigService;
use Modules\GiftMessage\Services\GiftMessageFontService;

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

    public function uploadImages(UploadGiftMessageImagesRequest $request): RedirectResponse
    {
        $this->configService->uploadImages([
            'envelope_image' => $request->file('envelope_image'),
            'card_image' => $request->file('card_image'),
        ]);

        return redirect()
            ->route('settings.giftmessage.index')
            ->with('success', 'Imagenes actualizadas correctamente.');
    }

    public function saveFonts(SaveGiftMessageFontsRequest $request): RedirectResponse
    {
        $this->configService->saveFonts($request->validated());

        return redirect()
            ->route('settings.giftmessage.index')
            ->with('success', 'Fuentes actualizadas correctamente.');
    }

    public function savePositions(SaveGiftMessagePositionsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->configService->savePositions($data['scope'], collect($data)->except('scope')->all());

        return response()->json(['success' => true]);
    }
}
