<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageSettingsController extends Controller
{
    private const SETTING_KEY = 'permalink-modules-page-models-page';

    public function edit(): View
    {
        return view('page::settings.settings');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'permalink_prefix' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-\/]*$/'],
        ]);

        $prefix = ltrim((string) $request->input('permalink_prefix', ''), '/');

        setting([self::SETTING_KEY => $prefix]);
        setting()->save();

        return redirect()
            ->route('settings.pages')
            ->with('success', 'Configuracion guardada exitosamente.');
    }
}
