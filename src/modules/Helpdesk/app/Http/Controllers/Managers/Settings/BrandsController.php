<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreBrandRequest;
use Modules\Helpdesk\Http\Requests\Settings\UpdateBrandRequest;
use Modules\Helpdesk\Models\Brand;

class BrandsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.brands.view')->only(['index']);
        $this->middleware('can:helpdesk.brands.manage')->only(['create', 'store', 'edit', 'update', 'destroy', 'toggle']);
    }

    public function index(Request $request): View
    {
        $query = Brand::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%")
                    ->orWhere('email_from_address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $brands = $query->withCount('inboxes')->latest()->paginate(20);

        $stats = [
            'total' => Brand::query()->count(),
            'active' => Brand::query()->where('is_active', true)->count(),
            'with_inboxes' => Brand::query()->has('inboxes')->count(),
        ];

        return view('helpdesk::settings.brands.index', compact('brands', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.brands.create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        Brand::create($request->validated());

        return redirect()
            ->route('settings.helpdesk.brands.index')
            ->with('success', 'Marca creada exitosamente.');
    }

    public function edit(Brand $brand): View
    {
        return view('helpdesk::settings.brands.edit', compact('brand'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $brand->update($data);

        return redirect()
            ->route('settings.helpdesk.brands.index')
            ->with('success', 'Marca actualizada exitosamente.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return redirect()
            ->route('settings.helpdesk.brands.index')
            ->with('success', 'Marca eliminada exitosamente.');
    }

    public function toggle(Brand $brand): RedirectResponse
    {
        $brand->update(['is_active' => ! $brand->is_active]);

        $message = $brand->is_active ? 'Marca activada.' : 'Marca desactivada.';

        return redirect()
            ->route('settings.helpdesk.brands.index')
            ->with('success', $message);
    }
}
