<?php

namespace Modules\GiftMessage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\GiftMessage\Http\Requests\SaveGiftMessageFontsRequest;
use Modules\GiftMessage\Http\Requests\SaveGiftMessagePositionsRequest;
use Modules\GiftMessage\Http\Requests\UploadGiftMessageImagesRequest;
use Modules\GiftMessage\Services\GiftMessageConfigService;

class GiftMessageConfigController extends Controller
{
    public function __construct(
        private readonly GiftMessageConfigService $configService
    ) {}

    public function uploadImages(UploadGiftMessageImagesRequest $request): RedirectResponse
    {
        $this->configService->uploadImages([
            'envelope_image' => $request->file('envelope_image'),
            'card_image' => $request->file('card_image'),
        ]);

        return redirect()
            ->route('giftmessage.index')
            ->with('success', 'Imagenes actualizadas correctamente.');
    }

    public function saveFonts(SaveGiftMessageFontsRequest $request): RedirectResponse
    {
        $this->configService->saveFonts($request->validated());

        return redirect()
            ->route('giftmessage.index')
            ->with('success', 'Fuentes actualizadas correctamente.');
    }

    public function savePositions(SaveGiftMessagePositionsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->configService->savePositions($data['scope'], [
            't1_x' => $data['t1_x'],
            't1_y' => $data['t1_y'],
            't2_x' => $data['t2_x'],
            't2_y' => $data['t2_y'],
        ]);

        return response()->json(['success' => true]);
    }
}
