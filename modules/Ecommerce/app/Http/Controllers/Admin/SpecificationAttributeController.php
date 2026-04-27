<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreSpecificationAttributeRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateSpecificationAttributeRequest;
use Modules\Ecommerce\Models\SpecificationAttribute;
use Modules\Ecommerce\Models\SpecificationGroup;

class SpecificationAttributeController extends Controller
{
    public function index(): View
    {
        $specificationAttributes = SpecificationAttribute::query()
            ->with('group')
            ->latest()
            ->paginate(20);

        return view('ecommerce::specification-attributes.index', compact('specificationAttributes'));
    }

    public function create(): View
    {
        $groups = SpecificationGroup::query()->pluck('name', 'id');

        return view('ecommerce::specification-attributes.create', compact('groups'));
    }

    public function store(StoreSpecificationAttributeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['options'] = filled($data['options'] ?? null) ? $data['options'] : null;

        SpecificationAttribute::query()->create($data);

        return redirect()->route('ecommerce.specification-attributes.index')
            ->with('success', 'Atributo de especificacion creado exitosamente.');
    }

    public function edit(SpecificationAttribute $specificationAttribute): View
    {
        $specificationAttribute->load('group');
        $groups = SpecificationGroup::query()->pluck('name', 'id');

        return view('ecommerce::specification-attributes.edit', compact('specificationAttribute', 'groups'));
    }

    public function update(UpdateSpecificationAttributeRequest $request, SpecificationAttribute $specificationAttribute): RedirectResponse
    {
        $data = $request->validated();
        $data['options'] = filled($data['options'] ?? null) ? $data['options'] : null;

        $specificationAttribute->update($data);

        return redirect()->route('ecommerce.specification-attributes.index')
            ->with('success', 'Atributo de especificacion actualizado exitosamente.');
    }

    public function destroy(SpecificationAttribute $specificationAttribute): RedirectResponse
    {
        $specificationAttribute->delete();

        return redirect()->route('ecommerce.specification-attributes.index')
            ->with('success', 'Atributo de especificacion eliminado exitosamente.');
    }
}
