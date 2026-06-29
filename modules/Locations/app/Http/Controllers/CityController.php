<?php

namespace Modules\Locations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Locations\Http\Requests\StoreCityRequest;
use Modules\Locations\Http\Requests\UpdateCityRequest;
use Modules\Locations\Models\City;
use Modules\Locations\Models\Country;
use Modules\Locations\Models\State;

class CityController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('locations.cities.view');

        $query = City::query()->with(['country', 'state'])->latest();

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->input('country_id'));
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->input('state_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        $cities = $query->paginate(20)->withQueryString();
        $countries = Country::query()->active()->ordered()->get(['id', 'name']);

        return view('locations::cities.index', compact('cities', 'countries'));
    }

    public function create(Request $request): View
    {
        $this->authorize('locations.cities.create');

        $countries = Country::query()->active()->ordered()->get(['id', 'name']);
        $states = collect();

        if ($request->filled('state_id')) {
            $state = State::query()->find($request->input('state_id'));
            if ($state) {
                $states = State::query()->active()->ordered()
                    ->where('country_id', $state->country_id)
                    ->get(['id', 'name']);
            }
        }

        return view('locations::cities.create', compact('countries', 'states'));
    }

    public function store(StoreCityRequest $request): RedirectResponse
    {
        City::query()->create($request->validated());

        return redirect()->route('locations.cities.index')
            ->with('success', 'Ciudad creada correctamente.');
    }

    public function edit(City $city): View
    {
        $this->authorize('locations.cities.update');

        $city->load(['country', 'state']);
        $countries = Country::query()->active()->ordered()->get(['id', 'name']);
        $states = State::query()->active()->ordered()
            ->where('country_id', $city->country_id)
            ->get(['id', 'name']);

        return view('locations::cities.edit', compact('city', 'countries', 'states'));
    }

    public function update(UpdateCityRequest $request, City $city): RedirectResponse
    {
        $city->update($request->validated());

        return redirect()->route('locations.cities.index')
            ->with('success', 'Ciudad actualizada correctamente.');
    }

    public function destroy(City $city): RedirectResponse
    {
        $this->authorize('locations.cities.delete');

        $city->delete();

        return redirect()->route('locations.cities.index')
            ->with('success', 'Ciudad eliminada correctamente.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $this->authorize('locations.cities.delete');

        $request->validate([
            'action' => ['required', 'string', 'in:delete,activate,deactivate'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');

        match ($action) {
            'delete' => City::query()->whereIn('id', $ids)->delete(),
            'activate' => City::query()->whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => City::query()->whereIn('id', $ids)->update(['is_active' => false]),
        };

        return redirect()->route('locations.cities.index')
            ->with('success', 'Acción aplicada correctamente.');
    }
}
