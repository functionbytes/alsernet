<?php

namespace Modules\Locales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Modules\Locales\Services\ThemeTranslationService;
use Symfony\Component\HttpFoundation\Response;

class ThemeTranslationController extends Controller
{
    public function __construct(
        private readonly ThemeTranslationService $service
    ) {}

    public function index(Request $request): View
    {
        $locales = $this->service->getAvailableLocales();
        $groupsByModule = $this->service->getGroupsByModule();
        $moduleFilter = $request->input('module', '');

        if ($moduleFilter !== '') {
            $groupsByModule = $groupsByModule->only([$moduleFilter]);
        }

        return view('locales::translations.index', compact('locales', 'groupsByModule', 'moduleFilter'));
    }

    public function edit(Request $request, string $locale, string $group): View
    {
        $allTranslations = $this->service->getTranslations($locale, $group);
        $locales = $this->service->getAvailableLocales();
        $groups = $this->service->getGroups();
        $currentGroup = collect($groups)->firstWhere('name', $group);

        $sourceLocale = $locale === 'es' ? 'en' : 'es';
        $sourceTranslations = $this->service->getTranslations($sourceLocale, $group);
        $sourceLocaleName = $locales->firstWhere('code', $sourceLocale)?->name ?? strtoupper($sourceLocale);
        $targetLocaleName = $locales->firstWhere('code', $locale)?->name ?? strtoupper($locale);

        // Stats (always over full set)
        $totalKeys = count($allTranslations);
        $translatedCount = collect($allTranslations)
            ->filter(fn ($v, $k) => $v !== '' && $v !== ($sourceTranslations[$k] ?? $k))
            ->count();
        $untranslatedCount = $totalKeys - $translatedCount;
        $percent = $totalKeys ? round($translatedCount / $totalKeys * 100) : 0;

        // Filter
        $search = $request->input('search', '');
        $tab = $request->input('tab', 'all');

        $filtered = collect($allTranslations);

        if ($search !== '') {
            $q = mb_strtolower($search);
            $filtered = $filtered->filter(function ($value, $key) use ($q, $sourceTranslations) {
                $source = $sourceTranslations[$key] ?? $key;

                return str_contains(mb_strtolower($key), $q)
                    || str_contains(mb_strtolower($value), $q)
                    || str_contains(mb_strtolower($source), $q);
            });
        }

        if ($tab === 'translated') {
            $filtered = $filtered->filter(fn ($v, $k) => $v !== '' && $v !== ($sourceTranslations[$k] ?? $k));
        } elseif ($tab === 'untranslated') {
            $filtered = $filtered->filter(fn ($v, $k) => $v === '' || $v === ($sourceTranslations[$k] ?? $k));
        }

        // Paginate
        $perPage = 25;
        $page = $request->integer('page', 1);
        $items = $filtered->slice(($page - 1) * $perPage, $perPage)->all();

        $translations = new LengthAwarePaginator(
            $items,
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->except('page')]
        );

        return view('locales::translations.edit', compact(
            'locale', 'group', 'currentGroup', 'translations', 'allTranslations', 'locales', 'groups',
            'sourceLocale', 'sourceTranslations', 'sourceLocaleName', 'targetLocaleName',
            'totalKeys', 'translatedCount', 'untranslatedCount', 'percent',
            'search', 'tab'
        ));
    }

    public function update(Request $request, string $locale, string $group): RedirectResponse
    {
        $translations = $request->input('translations', []);

        $this->service->saveTranslations($locale, $group, $translations);

        return redirect()
            ->route('locales.translations.edit', [$locale, $group])
            ->with('success', 'Traducciones guardadas correctamente.');
    }

    public function updateKey(Request $request, string $locale, string $group): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable|string',
        ]);

        $translations = $this->service->getTranslations($locale, $group);
        $key = $request->input('key');

        if (! array_key_exists($key, $translations)) {
            return response()->json(['success' => false, 'message' => 'Clave no encontrada.'], 404);
        }

        $translations[$key] = $request->input('value', '');
        $this->service->saveTranslations($locale, $group, $translations);

        return response()->json(['success' => true, 'message' => 'Traducción guardada.']);
    }

    public function bulkAction(Request $request, string $locale, string $group): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:delete',
            'keys' => 'required|array|min:1',
            'keys.*' => 'required|string',
        ]);

        $translations = $this->service->getTranslations($locale, $group);
        $keys = $request->input('keys');

        foreach ($keys as $key) {
            unset($translations[$key]);
        }

        $this->service->saveTranslations($locale, $group, $translations);

        return response()->json(['success' => true, 'message' => count($keys).' clave(s) eliminada(s).']);
    }

    public function renameKey(Request $request, string $locale, string $group): JsonResponse
    {
        $request->validate([
            'old_key' => 'required|string',
            'key' => 'required|string|max:255',
            'value' => 'nullable|string',
        ]);

        $translations = $this->service->getTranslations($locale, $group);
        $oldKey = $request->input('old_key');
        $newKey = trim($request->input('key'));

        if (! array_key_exists($oldKey, $translations)) {
            return response()->json(['success' => false, 'message' => 'Clave original no encontrada.'], 404);
        }

        if ($oldKey !== $newKey && array_key_exists($newKey, $translations)) {
            return response()->json(['success' => false, 'message' => 'La nueva clave ya existe.'], 422);
        }

        // Preserve key order: replace key in-place
        $result = [];
        foreach ($translations as $k => $v) {
            if ($k === $oldKey) {
                $result[$newKey] = $request->input('value', $v);
            } else {
                $result[$k] = $v;
            }
        }

        $this->service->saveTranslations($locale, $group, $result);

        return response()->json(['success' => true, 'message' => 'Clave actualizada correctamente.']);
    }

    public function storeKey(Request $request, string $locale, string $group): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'nullable|string',
        ]);

        $translations = $this->service->getTranslations($locale, $group);
        $key = trim($request->input('key'));

        if (array_key_exists($key, $translations)) {
            return response()->json(['success' => false, 'message' => 'La clave ya existe.'], 422);
        }

        $translations[$key] = $request->input('value', '');
        $this->service->saveTranslations($locale, $group, $translations);

        return response()->json(['success' => true, 'message' => 'Clave creada correctamente.', 'key' => $key, 'value' => $translations[$key]]);
    }

    public function export(string $locale, string $group): Response
    {
        $translations = $this->service->getTranslations($locale, $group);
        $filename = "{$locale}-{$group}.json";

        return response()->json($translations, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function import(Request $request, string $locale, string $group): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:json']);

        $contents = file_get_contents($request->file('file')->getRealPath());
        $imported = json_decode($contents, true);

        if (! is_array($imported)) {
            return response()->json(['success' => false, 'message' => 'Archivo JSON inválido.'], 422);
        }

        $existing = $this->service->getTranslations($locale, $group);
        $merged = array_merge($existing, $imported);
        $this->service->saveTranslations($locale, $group, $merged);

        return response()->json(['success' => true, 'message' => count($imported).' traducción(es) importada(s).']);
    }
}
