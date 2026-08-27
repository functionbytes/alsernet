<?php

namespace Modules\PriceLabels\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\PriceLabels\Http\Requests\StorePriceLabelFontRequest;
use Modules\PriceLabels\Models\PriceLabelFont;
use Modules\PriceLabels\Services\PriceLabelFontService;

class PriceLabelSettingsController extends Controller
{
    public function __construct(
        private readonly PriceLabelFontService $fontService
    ) {}

    public function index(): View
    {
        return view('pricelabels::admin.settings.index', [
            'pageTitle' => 'Configuracion de etiquetas de precio',
            'fonts' => $this->fontService->all(),
        ]);
    }

    public function storeFont(StorePriceLabelFontRequest $request): RedirectResponse
    {
        $this->fontService->store($request->safe()->except('font_file'), $request->file('font_file'));

        return redirect()
            ->route('settings.pricelabels.index')
            ->with('success', 'Fuente subida correctamente.');
    }

    public function destroyFont(PriceLabelFont $font): RedirectResponse
    {
        $this->fontService->delete($font);

        return redirect()
            ->route('settings.pricelabels.index')
            ->with('success', 'Fuente eliminada correctamente.');
    }
}
