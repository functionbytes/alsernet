<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Mailrelay\Entities\CustomField;
use Modules\Mailrelay\Http\Controllers\Controller;

class CustomFieldController extends Controller
{
    /**
     * Display a listing of custom fields.
     */
    public function index(): View
    {
        Gate::authorize('mailrelay.settings.custom-fields.view');

        $customFields = CustomField::orderBy('name')->get();

        return view('mailrelay::settings.custom-fields.index', [
            'customFields' => $customFields,
        ]);
    }

    /**
     * Show the form for creating a new custom field.
     */
    public function create(): View
    {
        Gate::authorize('mailrelay.settings.custom-fields.create');

        return view('mailrelay::settings.custom-fields.create');
    }

    /**
     * Store a newly created custom field.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.custom-fields.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'mailrelay_field_id' => 'nullable|integer',
                'field_type' => 'required|string|in:text,number,date,boolean,select',
                'mapping' => 'nullable|string|max:255',
                'default_value' => 'nullable|string',
                'required' => 'boolean',
            ]);

            CustomField::create($validated);

            return redirect()
                ->route('managers.settings.mailrelay.custom-fields.index')
                ->with('success', 'Campo personalizado creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Mailrelay custom field create failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el campo. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Show the form for editing the specified custom field.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailrelay.settings.custom-fields.edit');

        $customField = CustomField::findOrFail($id);

        return view('mailrelay::settings.custom-fields.edit', [
            'customField' => $customField,
        ]);
    }

    /**
     * Update the specified custom field.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.custom-fields.edit');

        try {
            $customField = CustomField::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'mailrelay_field_id' => 'nullable|integer',
                'field_type' => 'required|string|in:text,number,date,boolean,select',
                'mapping' => 'nullable|string|max:255',
                'default_value' => 'nullable|string',
                'required' => 'boolean',
            ]);

            $customField->update($validated);

            return redirect()
                ->route('managers.settings.mailrelay.custom-fields.index')
                ->with('success', 'Campo personalizado actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Mailrelay custom field update failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el campo. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Remove the specified custom field.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.custom-fields.delete');

        try {
            $customField = CustomField::findOrFail($id);
            $customField->delete();

            return redirect()
                ->route('managers.settings.mailrelay.custom-fields.index')
                ->with('success', 'Campo personalizado eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Mailrelay custom field delete failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el campo. Por favor, inténtalo de nuevo.');
        }
    }
}
