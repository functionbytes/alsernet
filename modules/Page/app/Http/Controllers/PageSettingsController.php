<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Locales\Models\Locale;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;

class PageSettingsController extends Controller
{
    private const SETTING_KEY = 'permalink-modules-page-models-page';

    private const ERROR_CODES = ['400', '401', '403', '404', '405', '408', '410', '419', '422', '429', '500', '502', '503', '504'];

    public function edit(): View
    {
        $publishedPages = Page::query()
            ->where('status', PageStatus::Published)
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);

        $activeLocales = collect();
        if (class_exists(Locale::class)) {
            try {
                $activeLocales = Locale::active()->ordered()->get();
            } catch (\Exception) {
            }
        }

        return view('page::pages.settings', compact('publishedPages', 'activeLocales'));
    }

    public function update(Request $request): RedirectResponse
    {
        $errorPageRules = [];
        foreach (self::ERROR_CODES as $code) {
            $errorPageRules["error_page_{$code}"] = ['nullable', 'integer', 'exists:pages,id'];
        }

        $request->validate(array_merge([
            'permalink_prefix' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-\/]*$/'],
            'supported_locales' => ['nullable', 'array'],
            'supported_locales.*' => ['string', 'max:10'],
        ], $errorPageRules));

        $prefix = ltrim((string) $request->input('permalink_prefix', ''), '/');
        $locales = array_filter(array_map('trim', $request->input('supported_locales', [])));

        Setting::set(self::SETTING_KEY, $prefix);
        Setting::set('page-supported-locales', implode(',', $locales));

        foreach (self::ERROR_CODES as $code) {
            $pageId = $request->input("error_page_{$code}");
            Setting::set("error-page-{$code}", $pageId ?: '');
        }

        return redirect()
            ->route('settings.pages')
            ->with('success', 'Configuracion guardada exitosamente.');
    }
}
