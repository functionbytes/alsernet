<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBanner($request);

        Banner::create($data);

        return redirect()->route('settings.helpdesk.banners.index')
            ->with('success', 'Banner creado exitosamente.');
    }

    public function edit(Banner $banner): View
    {
        return view('helpdesk::settings.banners.form', compact('banner'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $this->validateBanner($request);

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

    private function validateBanner(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['required', 'in:info,success,warning,danger'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'url', 'max:500'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ], [
            'title.required' => 'El titulo es obligatorio.',
            'body.required' => 'El contenido es obligatorio.',
            'type.required' => 'El tipo es obligatorio.',
            'cta_url.url' => 'La URL del boton debe ser una URL valida.',
            'ends_at.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['dismissible'] = $request->boolean('dismissible', true);

        return $validated;
    }
}
