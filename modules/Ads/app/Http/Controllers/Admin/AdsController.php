<?php

namespace Modules\Ads\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ads\Enums\AdsStatus;
use Modules\Ads\Enums\AdsType;
use Modules\Ads\Http\Requests\StoreAdsRequest;
use Modules\Ads\Http\Requests\UpdateAdsRequest;
use Modules\Ads\Models\Ads;
use Modules\Locales\Models\Locale;

class AdsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ads.view');

        $query = Ads::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $ads = $query->paginate(20);
        $statuses = AdsStatus::cases();

        return view('ads::admin.ads.index', [
            'ads' => $ads,
            'statuses' => $statuses,
            'pageTitle' => 'Anuncios',
            'breadcrumb' => 'Ads',
        ]);
    }

    public function create(): View
    {
        $this->authorize('ads.create');

        return view('ads::admin.ads.create', [
            'statuses' => AdsStatus::cases(),
            'types' => AdsType::cases(),
            'locales' => $this->getActiveLocales(),
            'pageTitle' => 'Nuevo anuncio',
            'breadcrumb' => 'Ads',
        ]);
    }

    public function store(StoreAdsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab', false);

        $translations = $data['translations'];
        unset($data['translations']);

        $defaultLocale = $this->getDefaultLocale();
        $defaultTranslation = collect($translations)->firstWhere('locale', $defaultLocale)
            ?? $translations[array_key_first($translations)];
        $data['name'] = $defaultTranslation['name'];

        $ad = Ads::query()->create($data);
        $this->syncTranslations($ad, $translations);

        return redirect()->route('ads.index')
            ->with('success', 'Anuncio creado correctamente.');
    }

    public function edit(Ads $ad): View
    {
        $this->authorize('ads.update');

        return view('ads::admin.ads.edit', [
            'ad' => $ad->load('translations'),
            'statuses' => AdsStatus::cases(),
            'types' => AdsType::cases(),
            'locales' => $this->getActiveLocales(),
            'pageTitle' => 'Editar anuncio',
            'breadcrumb' => 'Ads',
        ]);
    }

    public function update(UpdateAdsRequest $request, Ads $ad): RedirectResponse
    {
        $data = $request->validated();
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab', false);

        $translations = $data['translations'];
        unset($data['translations']);

        $defaultLocale = $this->getDefaultLocale();
        $defaultTranslation = collect($translations)->firstWhere('locale', $defaultLocale)
            ?? $translations[array_key_first($translations)];
        $data['name'] = $defaultTranslation['name'];

        $ad->update($data);
        $this->syncTranslations($ad, $translations);

        return redirect()->route('ads.index')
            ->with('success', 'Anuncio actualizado correctamente.');
    }

    public function destroy(Ads $ad): RedirectResponse
    {
        $this->authorize('ads.delete');

        $ad->delete();

        return redirect()->route('ads.index')
            ->with('success', 'Anuncio eliminado correctamente.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getActiveLocales(): array
    {
        try {
            return Locale::active()->get(['code', 'name', 'native_name', 'is_default'])->toArray();
        } catch (\Throwable) {
            return [['code' => config('app.locale', 'es'), 'name' => 'Default', 'native_name' => 'Default', 'is_default' => true]];
        }
    }

    private function getDefaultLocale(): string
    {
        try {
            return Locale::active()->firstWhere('is_default', true)?->code ?? config('app.locale', 'es');
        } catch (\Throwable) {
            return config('app.locale', 'es');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $translations
     */
    private function syncTranslations(Ads $ad, array $translations): void
    {
        foreach ($translations as $translation) {
            $ad->translations()->updateOrCreate(
                ['locale' => $translation['locale']],
                ['name' => $translation['name']]
            );
        }
    }
}
