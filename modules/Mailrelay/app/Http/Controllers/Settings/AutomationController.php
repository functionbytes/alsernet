<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailrelay\Http\Controllers\Controller;
use Modules\Mailrelay\Models\MailrelayAutomation;

class AutomationController extends Controller
{
    /**
     * Display a listing of automations.
     */
    public function index(): View
    {
        Gate::authorize('mailrelay.settings.automations.view');

        $automations = MailrelayAutomation::orderBy('created_at', 'desc')->get();

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
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.automations.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'trigger_event' => 'required|string|max:100',
                'conditions' => 'nullable|json',
                'actions' => 'required|json',
                'active' => 'boolean',
                'priority' => 'integer|min:0|max:100',
            ]);

            MailrelayAutomation::create($validated);

            return redirect()
                ->route('managers.settings.mailrelay.automations.index')
                ->with('success', 'Automatización creada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear la automatización: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified automation.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailrelay.settings.automations.edit');

        $automation = MailrelayAutomation::findOrFail($id);

        return view('mailrelay::settings.automations.edit', [
            'automation' => $automation,
        ]);
    }

    /**
     * Update the specified automation.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.automations.edit');

        try {
            $automation = MailrelayAutomation::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'trigger_event' => 'required|string|max:100',
                'conditions' => 'nullable|json',
                'actions' => 'required|json',
                'active' => 'boolean',
                'priority' => 'integer|min:0|max:100',
            ]);

            $automation->update($validated);

            return redirect()
                ->route('managers.settings.mailrelay.automations.index')
                ->with('success', 'Automatización actualizada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar la automatización: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified automation.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.automations.delete');

        try {
            $automation = MailrelayAutomation::findOrFail($id);
            $automation->delete();

            return redirect()
                ->route('managers.settings.mailrelay.automations.index')
                ->with('success', 'Automatización eliminada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la automatización: '.$e->getMessage());
        }
    }

    /**
     * Toggle automation active status.
     */
    public function toggle(int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.automations.edit');

        try {
            $automation = MailrelayAutomation::findOrFail($id);
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
                'message' => 'Error al cambiar el estado: '.$e->getMessage(),
            ], 500);
        }
    }
}
