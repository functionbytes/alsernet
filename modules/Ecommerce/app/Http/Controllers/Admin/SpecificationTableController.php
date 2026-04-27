<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreSpecificationTableRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateSpecificationTableRequest;
use Modules\Ecommerce\Models\SpecificationGroup;
use Modules\Ecommerce\Models\SpecificationTable;

class SpecificationTableController extends Controller
{
    public function index(): View
    {
        $specificationTables = SpecificationTable::query()
            ->withCount('groups')
            ->latest()
            ->paginate(20);

        return view('ecommerce::specification-tables.index', compact('specificationTables'));
    }

    public function create(): View
    {
        $allGroups = SpecificationGroup::query()->orderBy('name')->get();

        return view('ecommerce::specification-tables.create', compact('allGroups'));
    }

    public function store(StoreSpecificationTableRequest $request): RedirectResponse
    {
        $table = SpecificationTable::query()->create($request->safe()->only(['name', 'description']));

        if ($request->groups) {
            $table->groups()->sync($request->groups);
        }

        return redirect()->route('ecommerce.specification-tables.index')
            ->with('success', 'Tabla de especificaciones creada exitosamente.');
    }

    public function edit(SpecificationTable $specificationTable): View
    {
        $specificationTable->load('groups');
        $allGroups = SpecificationGroup::query()->orderBy('name')->get();

        return view('ecommerce::specification-tables.edit', compact('specificationTable', 'allGroups'));
    }

    public function update(UpdateSpecificationTableRequest $request, SpecificationTable $specificationTable): RedirectResponse
    {
        $specificationTable->update($request->safe()->only(['name', 'description']));
        $specificationTable->groups()->sync($request->groups ?? []);

        return redirect()->route('ecommerce.specification-tables.index')
            ->with('success', 'Tabla de especificaciones actualizada exitosamente.');
    }

    public function destroy(SpecificationTable $specificationTable): RedirectResponse
    {
        $specificationTable->delete();

        return redirect()->route('ecommerce.specification-tables.index')
            ->with('success', 'Tabla de especificaciones eliminada exitosamente.');
    }
}
