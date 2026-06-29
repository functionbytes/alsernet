<?php

namespace Modules\Locations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Locations\Http\Requests\StoreCountryRequest;
use Modules\Locations\Http\Requests\UpdateCountryRequest;
use Modules\Locations\Models\Country;

class CountryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('locations.countries.view');

        $query = Country::query()->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        $countries = $query->paginate(20)->withQueryString();

        return view('locations::countries.index', compact('countries'));
    }

    public function create(): View
    {
        $this->authorize('locations.countries.create');

        return view('locations::countries.create');
    }

    public function store(StoreCountryRequest $request): RedirectResponse
    {
        Country::query()->create($request->validated());

        return redirect()->route('locations.countries.index')
            ->with('success', 'País creado correctamente.');
    }

    public function edit(Country $country): View
    {
        $this->authorize('locations.countries.update');

        return view('locations::countries.edit', compact('country'));
    }

    public function update(UpdateCountryRequest $request, Country $country): RedirectResponse
    {
        $country->update($request->validated());

        return redirect()->route('locations.countries.index')
            ->with('success', 'País actualizado correctamente.');
    }

    public function destroy(Country $country): RedirectResponse
    {
        $this->authorize('locations.countries.delete');

        $country->delete();

        return redirect()->route('locations.countries.index')
            ->with('success', 'País eliminado correctamente.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $this->authorize('locations.countries.delete');

        $request->validate([
            'action' => ['required', 'string', 'in:delete,activate,deactivate'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');

        match ($action) {
            'delete' => Country::query()->whereIn('id', $ids)->delete(),
            'activate' => Country::query()->whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => Country::query()->whereIn('id', $ids)->update(['is_active' => false]),
        };

        return redirect()->route('locations.countries.index')
            ->with('success', 'Acción aplicada correctamente.');
    }
}
