<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Mailrelay\Entities\Automation;
use Modules\Mailrelay\Http\Controllers\Controller;
use Modules\Mailrelay\Http\Requests\Settings\StoreAutomationRequest;
use Modules\Mailrelay\Http\Requests\Settings\UpdateAutomationRequest;

class AutomationController extends Controller
{
    /**
     * Display a listing of automations.
     */
    public function index(): View
    {
        Gate::authorize('mailrelay.settings.automations.view');

        $automations = Automation::orderBy('created_at', 'desc')->get();

        return view('mailrelay::settings.automations.index', [
            'automations' => $automations,
        ]);
    }

    /**
     * Show the form for creating a new automation.
     */
    public function create(): View
    {
        Gate::authorize('mailrelay.settings.automations.create');

        return view('mailrelay::settings.automations.create');
    }

    /**
     * Store a newly created automation.
     */
    public function store(StoreAutomationRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            Automation::create($validated);

            return redirect()
                ->route('managers.settings.mailrelay.automations.index')
                ->with('success', 'Automatización creada correctamente.');
        } catch (\Exception $e) {
            Log::error('Mailrelay automation create failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear la automatización. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Show the form for editing the specified automation.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailrelay.settings.automations.edit');

        $automation = Automation::findOrFail($id);

        return view('mailrelay::settings.automations.edit', [
            'automation' => $automation,
        ]);
    }

    /**
     * Update the specified automation.
     */
    public function update(UpdateAutomationRequest $request, int $id): RedirectResponse
    {
        try {
            $automation = Automation::findOrFail($id);

            $validated = $request->validated();

            $automation->update($validated);

            return redirect()
                ->route('managers.settings.mailrelay.automations.index')
                ->with('success', 'Automatización actualizada correctamente.');
        } catch (\Exception $e) {
            Log::error('Mailrelay automation update failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar la automatización. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Remove the specified automation.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.automations.delete');

        try {
            $automation = Automation::findOrFail($id);
            $automation->delete();

            return redirect()
                ->route('managers.settings.mailrelay.automations.index')
                ->with('success', 'Automatización eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Mailrelay automation delete failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la automatización. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Toggle automation active status.
     */
    public function toggle(int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.automations.edit');

        try {
            $automation = Automation::findOrFail($id);
            $automation->update([
                'active' => ! $automation->active,
            ]);

            return response()->json([
                'success' => true,
                'message' => $automation->active
                    ? 'Automatización activada correctamente.'
                    : 'Automatización desactivada correctamente.',
                'active' => $automation->active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }
}
