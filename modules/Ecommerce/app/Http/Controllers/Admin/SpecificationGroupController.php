<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreSpecificationGroupRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateSpecificationGroupRequest;
use Modules\Ecommerce\Models\SpecificationGroup;

class SpecificationGroupController extends Controller
{
    public function index(): View
    {
        $specificationGroups = SpecificationGroup::query()
            ->withCount('attributes')
            ->latest()
            ->paginate(20);

        return view('ecommerce::specification-groups.index', compact('specificationGroups'));
    }

    public function create(): View
    {
        return view('ecommerce::specification-groups.create');
    }

    public function store(StoreSpecificationGroupRequest $request): RedirectResponse
    {
        SpecificationGroup::query()->create($request->validated());

        return redirect()->route('ecommerce.specification-groups.index')
            ->with('success', 'Grupo de especificaciones creado exitosamente.');
    }

    public function edit(SpecificationGroup $specificationGroup): View
    {
        return view('ecommerce::specification-groups.edit', compact('specificationGroup'));
    }

    public function update(UpdateSpecificationGroupRequest $request, SpecificationGroup $specificationGroup): RedirectResponse
    {
        $specificationGroup->update($request->validated());

        return redirect()->route('ecommerce.specification-groups.index')
            ->with('success', 'Grupo de especificaciones actualizado exitosamente.');
    }

    public function destroy(SpecificationGroup $specificationGroup): RedirectResponse
    {
        $specificationGroup->delete();

        return redirect()->route('ecommerce.specification-groups.index')
            ->with('success', 'Grupo de especificaciones eliminado exitosamente.');
    }
}
