<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Managers\Settings\StoreBannerRequest;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateBannerRequest;
use Modules\Helpdesk\Models\Banner;

class BannersController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.banners.manage');
    }

    public function index(Request $request): View
    {
        $banners = Banner::query()
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => Banner::count(),
            'active' => Banner::active()->count(),
            'scheduled' => Banner::where('is_active', true)->where('starts_at', '>', now())->count(),
        ];

        return view('helpdesk::settings.banners.index', compact('banners', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.banners.form');
    }

    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['dismissible'] = $request->boolean('dismissible', true);

        Banner::create($data);

        return redirect()->route('settings.helpdesk.banners.index')
            ->with('success', 'Banner creado exitosamente.');
    }

    public function edit(Banner $banner): View
    {
        return view('helpdesk::settings.banners.form', compact('banner'));
    }

    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['dismissible'] = $request->boolean('dismissible', true);

        $banner->update($data);

        return redirect()->route('settings.helpdesk.banners.index')
            ->with('success', 'Banner actualizado exitosamente.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()->route('settings.helpdesk.banners.index')
            ->with('success', 'Banner eliminado exitosamente.');
    }
}
