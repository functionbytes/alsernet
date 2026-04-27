<?php

namespace Modules\Locations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Locations\Http\Requests\StoreStateRequest;
use Modules\Locations\Http\Requests\UpdateStateRequest;
use Modules\Locations\Models\Country;
use Modules\Locations\Models\State;

class StateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('locations.states.view');

        $query = State::query()->with('country')->latest();

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->input('country_id'));
        }

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

        $states = $query->paginate(20)->withQueryString();
        $countries = Country::query()->active()->ordered()->get(['id', 'name']);

        return view('locations::states.index', compact('states', 'countries'));
    }

    public function create(Request $request): View
    {
        $this->authorize('locations.states.create');

        $countries = Country::query()->active()->ordered()->get(['id', 'name']);
        $selectedCountryId = $request->input('country_id');

        return view('locations::states.create', compact('countries', 'selectedCountryId'));
    }

    public function store(StoreStateRequest $request): RedirectResponse
    {
        State::query()->create($request->validated());

        return redirect()->route('locations.states.index')
            ->with('success', 'Estado/provincia creado correctamente.');
    }

    public function edit(State $state): View
    {
        $this->authorize('locations.states.update');

        $state->load('country');
        $countries = Country::query()->active()->ordered()->get(['id', 'name']);

        return view('locations::states.edit', compact('state', 'countries'));
    }

    public function update(UpdateStateRequest $request, State $state): RedirectResponse
    {
        $state->update($request->validated());

        return redirect()->route('locations.states.index')
            ->with('success', 'Estado/provincia actualizado correctamente.');
    }

    public function destroy(State $state): RedirectResponse
    {
        $this->authorize('locations.states.delete');

        $state->delete();

        return redirect()->route('locations.states.index')
            ->with('success', 'Estado/provincia eliminado correctamente.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $this->authorize('locations.states.delete');

        $request->validate([
            'action' => ['required', 'string', 'in:delete,activate,deactivate'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');

        match ($action) {
            'delete' => State::query()->whereIn('id', $ids)->delete(),
            'activate' => State::query()->whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => State::query()->whereIn('id', $ids)->update(['is_active' => false]),
        };

        return redirect()->route('locations.states.index')
            ->with('success', 'Acción aplicada correctamente.');
    }
}
