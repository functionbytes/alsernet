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
        $locales = Locale::query()->ordered()->paginate(20);

        $stats = [
            'total' => Locale::query()->count(),
            'active' => Locale::query()->where('is_active', true)->count(),
            'inactive' => Locale::query()->where('is_active', false)->count(),
            'default' => Locale::query()->where('is_default', true)->value('name') ?? '—',
        ];

        return view('locales::locales.index', compact('locales', 'stats'));
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

            Locale::query()
                ->whereIn('id', $ids)
                ->where('is_default', false)
                ->update(['is_active' => $active]);

            $count = count($ids);
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
}
