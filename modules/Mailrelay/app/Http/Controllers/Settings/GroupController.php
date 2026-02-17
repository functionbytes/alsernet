<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Mailrelay\Http\Controllers\Controller;
use Modules\Mailrelay\Models\MailrelayGroup;
use Modules\Mailrelay\Models\MailrelaySettings;

class GroupController extends Controller
{
    /**
     * Display a listing of groups.
     */
    public function index(): View
    {
        Gate::authorize('mailrelay.settings.groups.view');

        $groups = MailrelayGroup::orderBy('name')->get();

        return view('mailrelay::settings.groups.index', [
            'groups' => $groups,
        ]);
    }

    /**
     * Show the form for creating a new group.
     */
    public function create(): View
    {
        Gate::authorize('mailrelay.settings.groups.create');

        return view('mailrelay::settings.groups.create');
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.groups.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'mailrelay_group_id' => 'nullable|integer',
                'description' => 'nullable|string',
                'sync_enabled' => 'boolean',
            ]);

            MailrelayGroup::create($validated);

            return redirect()
                ->route('managers.settings.mailrelay.groups.index')
                ->with('success', 'Grupo creado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el grupo: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified group.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailrelay.settings.groups.edit');

        $group = MailrelayGroup::findOrFail($id);

        return view('mailrelay::settings.groups.edit', [
            'group' => $group,
        ]);
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.groups.edit');

        try {
            $group = MailrelayGroup::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'mailrelay_group_id' => 'nullable|integer',
                'description' => 'nullable|string',
                'sync_enabled' => 'boolean',
            ]);

            $group->update($validated);

            return redirect()
                ->route('managers.settings.mailrelay.groups.index')
                ->with('success', 'Grupo actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el grupo: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified group.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.groups.delete');

        try {
            $group = MailrelayGroup::findOrFail($id);
            $group->delete();

            return redirect()
                ->route('managers.settings.mailrelay.groups.index')
                ->with('success', 'Grupo eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el grupo: '.$e->getMessage());
        }
    }

    /**
     * Synchronize group with Mailrelay API.
     */
    public function sync(int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.groups.edit');

        try {
            $group = MailrelayGroup::findOrFail($id);
            $settings = MailrelaySettings::first();

            if ($settings === null || empty($settings->api_url) || empty($settings->api_key)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración API no disponible.',
                ], 422);
            }

            if ($group->mailrelay_group_id === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'El grupo no tiene un ID de Mailrelay asignado.',
                ], 422);
            }

            $response = Http::timeout($settings->timeout ?? 30)
                ->withHeaders([
                    'X-AUTH-TOKEN' => $settings->api_key,
                ])
                ->get($settings->api_url.'/api/v1/groups/'.$group->mailrelay_group_id);

            if ($response->successful()) {
                $data = $response->json();

                $group->update([
                    'name' => $data['name'] ?? $group->name,
                    'description' => $data['description'] ?? $group->description,
                    'last_synced_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Grupo sincronizado correctamente.',
                    'data' => $group,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar: '.$response->status(),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar el grupo: '.$e->getMessage(),
            ], 500);
        }
    }
}
