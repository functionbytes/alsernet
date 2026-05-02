<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Remarketing\Http\Requests\Web\StoreTemplateRequest;
use Modules\Remarketing\Http\Requests\Web\UpdateTemplateRequest;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Template;

class TemplateController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Template::class);

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $templates = Template::query()
            ->with('store')
            ->where(fn ($q) => $q->whereIn('store_id', $storeIds)->orWhere('is_global', true))
            ->latest()
            ->paginate(20);

        return view('remarketing::templates.index', compact('templates'));
    }

    public function create(): View
    {
        $this->authorize('create', Template::class);

        $stores = $this->getUserStores();

        return view('remarketing::templates.create', compact('stores'));
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        Template::query()->create($request->validated());

        return redirect()->route('remarketing.templates.index')
            ->with('success', 'Plantilla creada correctamente.');
    }

    public function edit(Template $template): View
    {
        $this->authorize('update', $template);

        $stores = $this->getUserStores();

        return view('remarketing::templates.edit', compact('template', 'stores'));
    }

    public function update(UpdateTemplateRequest $request, Template $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $template->update($request->validated());

        return redirect()->route('remarketing.templates.index')
            ->with('success', 'Plantilla actualizada correctamente.');
    }

    public function destroy(Template $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()->route('remarketing.templates.index')
            ->with('success', 'Plantilla eliminada correctamente.');
    }

    public function preview(Template $template): View
    {
        $this->authorize('view', $template);

        return view('remarketing::templates.preview', compact('template'));
    }

    private function getUserStores(): Collection
    {
        $user = auth()->user();

        return Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->get(['id', 'name']);
    }
}
