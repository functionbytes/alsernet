<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
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

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];
        $count = 0;

        $banners = Banner::whereIn('id', $ids)->get();

        foreach ($banners as $banner) {
            if ($action === 'delete') {
                $banner->delete();
            } else {
                $banner->update(['is_active' => $action === 'activate']);
            }
            $count++;
        }

        $labels = [
            'activate' => 'activado(s)',
            'deactivate' => 'desactivado(s)',
            'delete' => 'eliminado(s)',
        ];

        return response()->json([
            'message' => "{$count} banner(s) {$labels[$action]}.",
            'count' => $count,
        ]);
    }
}
