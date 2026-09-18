<?php

namespace Modules\GiftMessage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\GiftMessage\Http\Requests\StoreGiftMessageFontRequest;
use Modules\GiftMessage\Models\GiftMessageConfig;
use Modules\GiftMessage\Models\GiftMessageFont;
use Modules\GiftMessage\Services\GiftMessageFontService;

class GiftMessageFontController extends Controller
{
    public function __construct(
        private readonly GiftMessageFontService $fontService
    ) {}

    public function store(StoreGiftMessageFontRequest $request): RedirectResponse
    {
        $this->fontService->store($request->safe()->except('font_file'), $request->file('font_file'));

        return redirect()
            ->route('settings.giftmessage.index')
            ->with('success', 'Fuente subida correctamente.');
    }

    public function destroy(GiftMessageFont $font): RedirectResponse
    {
        $this->authorize('update', GiftMessageConfig::class);

        $this->fontService->delete($font);

        return redirect()
            ->route('settings.giftmessage.index')
            ->with('success', 'Fuente eliminada correctamente.');
    }
}
