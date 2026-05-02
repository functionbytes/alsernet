<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class SocialModuleSettingsController extends Controller
{
    public function index(): View
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        return view('helpdesksocial::settings.index', [
            'config' => config('helpdesksocial'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);

        $validated = $request->validate([
            'auto_reply_enabled' => 'boolean',
            'intent_classification_enabled' => 'boolean',
            'intent_provider' => 'in:rules,openai,hybrid',
            'comments_enabled' => 'boolean',
            'meta_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
        ]);

        // Update .env values would require a different approach
        // For now, we flash to session
        return redirect()->route('settings.helpdesk.social.index')
            ->with('success', 'Configuración actualizada. Reinicia el servicio para aplicar cambios.');
    }
}
