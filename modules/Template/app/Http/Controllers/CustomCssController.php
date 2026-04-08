<?php

namespace Modules\Template\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Modules\Template\Http\Requests\UpdateCustomCssRequest;
use Modules\Template\Services\TemplateService;

class CustomCssController extends Controller
{
    public function __construct(
        protected TemplateService $service,
    ) {}

    /**
     * Mostrar formulario para editar CSS personalizado
     */
    public function edit(): View
    {
        $this->authorize('template.custom-code');

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
            updateSettings(['theme.custom_css' => $request->input('custom_css', '')]);

            return redirect()
                ->back()
                ->with('success', __('template::template.custom_css_saved'));
        } catch (\Exception $e) {
            Log::error('Custom CSS save failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', __('template::template.error_saving_custom_css').'. Por favor, inténtalo de nuevo.');
        }
    }
}
