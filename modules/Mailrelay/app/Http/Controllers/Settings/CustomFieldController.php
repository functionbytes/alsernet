<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailrelay\Http\Controllers\Controller;
use Modules\Mailrelay\Models\MailrelayCustomField;

class CustomFieldController extends Controller
{
    /**
     * Display a listing of custom fields.
     */
    public function index(): View
    {
        Gate::authorize('mailrelay.settings.custom-fields.view');

        $customFields = MailrelayCustomField::orderBy('name')->get();

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

            MailrelayCustomField::create($validated);

            return redirect()
                ->route('managers.settings.mailrelay.custom-fields.index')
                ->with('success', 'Campo personalizado creado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el campo: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified custom field.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailrelay.settings.custom-fields.edit');

        $customField = MailrelayCustomField::findOrFail($id);

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
            $customField = MailrelayCustomField::findOrFail($id);

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
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el campo: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified custom field.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.custom-fields.delete');

        try {
            $customField = MailrelayCustomField::findOrFail($id);
            $customField->delete();

            return redirect()
                ->route('managers.settings.mailrelay.custom-fields.index')
                ->with('success', 'Campo personalizado eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el campo: '.$e->getMessage());
        }
    }
}
