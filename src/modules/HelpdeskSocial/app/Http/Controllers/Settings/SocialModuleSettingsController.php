<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialModuleSettingsRequest;

class SocialModuleSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesksocial.view')->only('index');
        $this->middleware('can:helpdesksocial.rules.manage')->only('update');
    }

    public function index(): View
    {
        return view('helpdesksocial::settings.index', [
            'config' => config('helpdesksocial'),
        ]);
    }

    public function update(UpdateSocialModuleSettingsRequest $request): RedirectResponse
    {
        // Config values are read-only at runtime; flash to session for display purposes.
        return redirect()->route('settings.helpdesk.social.index')
            ->with('success', 'Configuración actualizada. Reinicia el servicio para aplicar cambios.');
    }
}
