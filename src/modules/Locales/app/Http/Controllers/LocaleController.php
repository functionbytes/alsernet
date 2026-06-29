<?php

namespace Modules\Locales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Locales\Http\Requests\StoreLocaleRequest;
use Modules\Locales\Http\Requests\UpdateLocaleRequest;
use Modules\Locales\Models\Locale;
use Modules\Locales\Services\LocaleService;

class LocaleController extends Controller
{
    public function index(): View
    {
        $locales = Locale::query()->ordered()->get();

        $settings = [
            'hide_default_from_url' => setting('locales.hide_default_from_url', '0'),
            'language_display' => setting('locales.language_display', 'flags_names'),
            'selector_type' => setting('locales.selector_type', 'dropdown'),
            'auto_detect' => setting('locales.auto_detect', '0'),
        ];

        $presetLanguages = $this->presetLanguages();

        return view('locales::locales.index', compact('locales', 'settings', 'presetLanguages'));
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids) || ! is_array($ids)) {
            return response()->json(['message' => 'No hay registros seleccionados.'], 422);
        }

        $count = 0;

        if ($action === 'delete') {
            $locales = Locale::query()->whereIn('id', $ids)->get();

            foreach ($locales as $locale) {
                if ($locale->is_default) {
                    continue;
                }

                $locale->delete();
                $count++;
            }

            LocaleService::clearCache();

            return response()->json(['message' => "{$count} idioma(s) eliminado(s).", 'count' => $count]);
        }

        if ($action === 'activate' || $action === 'deactivate') {
            $active = $action === 'activate';

            $count = Locale::query()
                ->whereIn('id', $ids)
                ->where('is_default', false)
                ->update(['is_active' => $active]);

            LocaleService::clearCache();

            $label = $active ? 'activado(s)' : 'desactivado(s)';

            return response()->json(['message' => "{$count} idioma(s) {$label}.", 'count' => $count]);
        }

        return response()->json(['message' => 'Acción no válida.'], 422);
    }

    public function create(): View
    {
        return view('locales::locales.create');
    }

    public function store(StoreLocaleRequest $request): RedirectResponse
    {
        Locale::query()->create($request->validated());
        LocaleService::clearCache();

        return redirect()->route('locales.index')->with('success', 'Idioma creado correctamente.');
    }

    public function edit(Locale $locale): View
    {
        return view('locales::locales.edit', compact('locale'));
    }

    public function update(UpdateLocaleRequest $request, Locale $locale): RedirectResponse
    {
        $locale->update($request->validated());
        LocaleService::clearCache();

        return redirect()->route('locales.index')->with('success', 'Idioma actualizado correctamente.');
    }

    public function destroy(Locale $locale): RedirectResponse
    {
        if ($locale->is_default) {
            return redirect()->route('locales.index')->with('error', 'No se puede eliminar el idioma predeterminado.');
        }

        $activeCount = Locale::query()->where('is_active', true)->count();

        if ($locale->is_active && $activeCount <= 1) {
            return redirect()->route('locales.index')->with('error', 'No se puede eliminar el ultimo idioma activo.');
        }

        $locale->delete();
        LocaleService::clearCache();

        return redirect()->route('locales.index')->with('success', 'Idioma eliminado correctamente.');
    }

    public function storeSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'hide_default_from_url' => ['boolean'],
            'language_display' => ['required', 'in:flags_names,flag,name'],
            'selector_type' => ['required', 'in:dropdown,list'],
            'auto_detect' => ['boolean'],
        ]);

        updateSettings([
            'locales.hide_default_from_url' => $request->has('hide_default_from_url') ? '1' : '0',
            'locales.language_display' => $request->input('language_display', 'flags_names'),
            'locales.selector_type' => $request->input('selector_type', 'dropdown'),
            'locales.auto_detect' => $request->has('auto_detect') ? '1' : '0',
        ]);

        LocaleService::clearCache();

        return redirect()->route('locales.index', ['tab' => 'config'])->with('success', 'Ajustes guardados.');
    }

    public function setDefault(Locale $locale): JsonResponse
    {
        Locale::query()->where('is_default', true)->update(['is_default' => false]);
        $locale->update(['is_default' => true, 'is_active' => true]);
        LocaleService::clearCache();

        return response()->json(['success' => true]);
    }

    public function toggle(Locale $locale): JsonResponse
    {
        if ($locale->is_default && $locale->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede desactivar el idioma predeterminado.',
            ], 422);
        }

        $locale->update(['is_active' => ! $locale->is_active]);
        LocaleService::clearCache();

        return response()->json(['success' => true, 'is_active' => $locale->is_active]);
    }

    /**
     * @return array<string, array{name: string, native_name: string, code: string, language_code: string, flag: string, rtl: bool}>
     */
    private function presetLanguages(): array
    {
        return [
            'ar' => ['name' => 'Arabic',     'native_name' => 'العربية',    'code' => 'ar', 'language_code' => 'ar',    'flag' => '🇸🇦', 'rtl' => true],
            'zh' => ['name' => 'Chinese',    'native_name' => '中文',       'code' => 'zh', 'language_code' => 'zh_CN', 'flag' => '🇨🇳', 'rtl' => false],
            'nl' => ['name' => 'Dutch',      'native_name' => 'Nederlands', 'code' => 'nl', 'language_code' => 'nl',    'flag' => '🇳🇱', 'rtl' => false],
            'en' => ['name' => 'English',    'native_name' => 'English',    'code' => 'en', 'language_code' => 'en',    'flag' => '🇺🇸', 'rtl' => false],
            'fr' => ['name' => 'French',     'native_name' => 'Français',   'code' => 'fr', 'language_code' => 'fr',    'flag' => '🇫🇷', 'rtl' => false],
            'de' => ['name' => 'German',     'native_name' => 'Deutsch',    'code' => 'de', 'language_code' => 'de',    'flag' => '🇩🇪', 'rtl' => false],
            'it' => ['name' => 'Italian',    'native_name' => 'Italiano',   'code' => 'it', 'language_code' => 'it',    'flag' => '🇮🇹', 'rtl' => false],
            'ja' => ['name' => 'Japanese',   'native_name' => '日本語',     'code' => 'ja', 'language_code' => 'ja',    'flag' => '🇯🇵', 'rtl' => false],
            'ko' => ['name' => 'Korean',     'native_name' => '한국어',     'code' => 'ko', 'language_code' => 'ko',    'flag' => '🇰🇷', 'rtl' => false],
            'pl' => ['name' => 'Polish',     'native_name' => 'Polski',     'code' => 'pl', 'language_code' => 'pl',    'flag' => '🇵🇱', 'rtl' => false],
            'pt' => ['name' => 'Portuguese', 'native_name' => 'Português',  'code' => 'pt', 'language_code' => 'pt_BR', 'flag' => '🇧🇷', 'rtl' => false],
            'ru' => ['name' => 'Russian',    'native_name' => 'Русский',    'code' => 'ru', 'language_code' => 'ru',    'flag' => '🇷🇺', 'rtl' => false],
            'es' => ['name' => 'Spanish',    'native_name' => 'Español',    'code' => 'es', 'language_code' => 'es',    'flag' => '🇪🇸', 'rtl' => false],
            'tr' => ['name' => 'Turkish',    'native_name' => 'Türkçe',     'code' => 'tr', 'language_code' => 'tr',    'flag' => '🇹🇷', 'rtl' => false],
        ];
    }
}
