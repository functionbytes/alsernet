<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreGlobalOptionRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateGlobalOptionRequest;
use Modules\Ecommerce\Models\GlobalOption;

class GlobalOptionController extends Controller
{
    public function index(): View
    {
        $globalOptions = GlobalOption::query()
            ->withCount('values')
            ->latest()
            ->paginate(20);

        return view('ecommerce::global-options.index', compact('globalOptions'));
    }

    public function create(): View
    {
        return view('ecommerce::global-options.create');
    }

    public function store(StoreGlobalOptionRequest $request): RedirectResponse
    {
        $globalOption = null;

        DB::transaction(function () use ($request, &$globalOption) {
            $data = $request->safe()->except('values');
            $data['required'] = $request->boolean('required');

            $globalOption = GlobalOption::query()->create($data);

            foreach ($request->input('values', []) as $value) {
                $globalOption->values()->create($value);
            }
        });

        if ($request->input('save_action') === 'save') {
            return redirect()->route('ecommerce.global-options.edit', $globalOption)
                ->with('success', 'Opcion global creada exitosamente.');
        }

        return redirect()->route('ecommerce.global-options.index')
            ->with('success', 'Opcion global creada exitosamente.');
    }

    public function edit(GlobalOption $globalOption): View
    {
        $globalOption->load('values');

        return view('ecommerce::global-options.edit', compact('globalOption'));
    }

    public function update(UpdateGlobalOptionRequest $request, GlobalOption $globalOption): RedirectResponse
    {
        DB::transaction(function () use ($request, $globalOption) {
            $data = $request->safe()->except('values');
            $data['required'] = $request->boolean('required');

            $globalOption->update($data);
            $globalOption->values()->delete();

            foreach ($request->input('values', []) as $value) {
                $globalOption->values()->create($value);
            }
        });

        if ($request->input('save_action') === 'save') {
            return redirect()->route('ecommerce.global-options.edit', $globalOption)
                ->with('success', 'Opcion global actualizada exitosamente.');
        }

        return redirect()->route('ecommerce.global-options.index')
            ->with('success', 'Opcion global actualizada exitosamente.');
    }

    public function destroy(GlobalOption $globalOption): RedirectResponse
    {
        $globalOption->delete();

        return redirect()->route('ecommerce.global-options.index')
            ->with('success', 'Opcion global eliminada exitosamente.');
    }
}
