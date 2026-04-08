<?php

namespace Modules\Locales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Locales\Services\ThemeTranslationService;

class ThemeTranslationController extends Controller
{
    public function __construct(
        private readonly ThemeTranslationService $service
    ) {}

    public function index(): View
    {
        $locales = $this->service->getAvailableLocales();
        $groups = $this->service->getGroups();

        return view('locales::translations.index', compact('locales', 'groups'));
    }

    public function edit(string $locale, string $group): View
    {
        $translations = $this->service->getTranslations($locale, $group);
        $locales = $this->service->getAvailableLocales();
        $groups = $this->service->getGroups();

        return view('locales::translations.edit', compact('locale', 'group', 'translations', 'locales', 'groups'));
    }

    public function update(Request $request, string $locale, string $group): RedirectResponse
    {
        $translations = $request->input('translations', []);

        $this->service->saveTranslations($locale, $group, $translations);

        return redirect()
            ->route('locales.translations.edit', [$locale, $group])
            ->with('success', 'Traducciones guardadas correctamente.');
    }
}
