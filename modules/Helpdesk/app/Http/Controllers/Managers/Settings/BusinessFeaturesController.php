<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateBusinessFeaturesRequest;
use Modules\Helpdesk\Services\BusinessFeatureSettingsService;
use Modules\HelpdeskTranslate\Services\CachedTranslator;

/**
 * Página propia "Panel de administración" (Settings → Business → Panel):
 * activa/desactiva Horarios de atención, Fuera de horario, Bienvenida y
 * Despedida. El index de cada función en sí vive en su propio controller
 * (BusinessHoursController, OffHoursResponsesController,
 * ConversationGreetingsController, ConversationFarewellsController).
 */
class BusinessFeaturesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.update')->only('update');
    }

    public function index(): View
    {
        $businessFeatures = app(BusinessFeatureSettingsService::class)->all();

        // null = módulo HelpdeskTranslate no instalado (no hay nada que
        // reportar); true/false = estado real del circuit breaker de traducción.
        $translationHealthy = class_exists(CachedTranslator::class)
            ? app(CachedTranslator::class)->isHealthy()
            : null;

        return view('helpdesk::settings.business.features', compact('businessFeatures', 'translationHealthy'));
    }

    public function update(UpdateBusinessFeaturesRequest $request): RedirectResponse
    {
        // Checkboxes: un toggle desmarcado no viaja en el POST, por eso se lee
        // con $request->boolean() (ausencia = false) en vez de validated().
        $values = collect(BusinessFeatureSettingsService::KEYS)
            ->mapWithKeys(fn (string $key) => [$key => $request->boolean($key)])
            ->all();

        app(BusinessFeatureSettingsService::class)->update($values);

        return back()->with('success', 'Panel de funciones actualizado correctamente.');
    }
}
