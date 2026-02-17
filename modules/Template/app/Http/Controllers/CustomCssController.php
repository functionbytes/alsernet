<?php

namespace Modules\Template\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Modules\Template\Http\Requests\UpdateCustomCssRequest;
use Modules\Template\Services\TemplateService;

class CustomCssController
{
    public function __construct(
        protected TemplateService $service,
    ) {}

    /**
     * Mostrar formulario para editar CSS personalizado
     */
    public function edit(): View
    {
        $customCss = setting('theme.custom_css', '');
        $activeTemplateName = $this->service->getActiveName();

        return view('template::settings.custom-css', compact('customCss', 'activeTemplateName'));
    }

    /**
     * Guardar CSS personalizado
     */
    public function update(UpdateCustomCssRequest $request): RedirectResponse
    {
        try {
            $customCss = $request->input('custom_css', '');

            DB::table('settings')
                ->updateOrInsert(
                    ['key' => 'theme.custom_css'],
                    ['value' => $customCss]
                );

            // Limpiar caché
            \Illuminate\Support\Facades\Cache::forget('settings.theme.custom_css');

            return redirect()
                ->back()
                ->with('success', __('template::template.custom_css_saved'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('template::template.error_saving_custom_css').': '.$e->getMessage());
        }
    }
}
