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
            'pageTitle' => 'Nuevo anuncio',
            'breadcrumb' => 'Ads',
        ]);
    }

    public function store(StoreAdsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab', false);

        Ads::query()->create($data);

        return redirect()->route('ads.index')
            ->with('success', 'Anuncio creado correctamente.');
    }

    public function edit(Ads $ad): View
    {
        $this->authorize('ads.update');

        return view('ads::admin.ads.edit', [
            'ad' => $ad,
            'statuses' => AdsStatus::cases(),
            'types' => AdsType::cases(),
            'pageTitle' => 'Editar anuncio',
            'breadcrumb' => 'Ads',
        ]);
    }

    public function update(UpdateAdsRequest $request, Ads $ad): RedirectResponse
    {
        $data = $request->validated();
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab', false);

        $ad->update($data);

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
}
