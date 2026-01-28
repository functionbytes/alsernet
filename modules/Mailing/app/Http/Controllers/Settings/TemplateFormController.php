<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\Mailing\MailingTemplate;

class TemplateFormController extends Controller
{
    /**
     * Display a listing of template form fields.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.template-forms.view');

        // Get all templates with their form fields
        $templates = MailingTemplate::with('layout')
            ->orderBy('name')
            ->get();

        return view('mailing::settings.template-forms.index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for creating a new template form field.
     */
    public function create(): View
    {
        Gate::authorize('mailing.settings.template-forms.create');

        $templates = MailingTemplate::where('is_enabled', true)
            ->orderBy('name')
            ->get();

        return view('mailing::settings.template-forms.create', [
            'templates' => $templates,
        ]);
    }

    /**
     * Store a newly created template form field.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.template-forms.create');

        try {
            $validated = $request->validate([
                'mailing_template_id' => 'required|integer|exists:mailing_templates,id',
                'field_name' => 'required|string|max:255',
                'field_label' => 'required|string|max:255',
                'field_type' => 'required|string|in:text,email,number,textarea,select,checkbox,radio,date,file',
                'field_placeholder' => 'nullable|string|max:255',
                'is_required' => 'boolean',
                'is_enabled' => 'boolean',
                'sort_order' => 'nullable|integer|min:0',
                'validation_rules' => 'nullable|string',
                'help_text' => 'nullable|string|max:500',
            ]);

            // Get template to store form field data
            $template = MailingTemplate::findOrFail($validated['mailing_template_id']);

            // Initialize form fields if not exists
            $formFields = is_array($template->form_fields) ? $template->form_fields : [];

            // Generate unique field ID
            $fieldId = 'field_'.time().'_'.uniqid();

            $formFields[$fieldId] = [
                'id' => $fieldId,
                'name' => $validated['field_name'],
                'label' => $validated['field_label'],
                'type' => $validated['field_type'],
                'placeholder' => $validated['field_placeholder'] ?? null,
                'required' => $validated['is_required'] ?? false,
                'enabled' => $validated['is_enabled'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
                'validation_rules' => $validated['validation_rules'] ?? null,
                'help_text' => $validated['help_text'] ?? null,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];

            // Sort by sort_order
            usort($formFields, fn ($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

            // Update template with form fields
            $template->update(['form_fields' => $formFields]);

            return redirect()
                ->route('settings.mailing.templates.forms.index')
                ->with('success', 'Campo de formulario creado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el campo de formulario: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified template form field.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailing.settings.template-forms.edit');

        $template = MailingTemplate::findOrFail($id);

        $templates = MailingTemplate::where('is_enabled', true)
            ->orderBy('name')
            ->get();

        return view('mailing::settings.template-forms.edit', [
            'template' => $template,
            'templates' => $templates,
        ]);
    }

    /**
     * Update the specified template form field.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.template-forms.edit');

        try {
            $template = MailingTemplate::findOrFail($id);

            $validated = $request->validate([
                'mailing_template_id' => 'required|integer|exists:mailing_templates,id',
                'form_fields' => 'nullable|json',
            ]);

            // If form_fields are provided as JSON, parse and update them
            $formFields = $validated['form_fields'];
            if (is_string($formFields)) {
                $formFields = json_decode($formFields, true);
            }

            if (is_array($formFields)) {
                // Update each field with current timestamp
                foreach ($formFields as &$field) {
                    $field['updated_at'] = now()->toIso8601String();
                }

                // Sort by sort_order
                usort($formFields, fn ($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

                $template->update(['form_fields' => $formFields]);
            }

            return redirect()
                ->route('settings.mailing.templates.forms.index')
                ->with('success', 'Campos de formulario actualizados correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar los campos de formulario: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified template form field.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.template-forms.delete');

        try {
            // $id is the template ID, but we need field_id from the request
            $fieldId = request('field_id');

            if (! $fieldId) {
                return redirect()
                    ->back()
                    ->with('error', 'Campo de formulario no especificado.');
            }

            $template = MailingTemplate::findOrFail($id);

            $formFields = is_array($template->form_fields) ? $template->form_fields : [];

            // Remove the field by ID
            unset($formFields[$fieldId]);

            $template->update(['form_fields' => array_values($formFields)]);

            return redirect()
                ->route('settings.mailing.templates.forms.index')
                ->with('success', 'Campo de formulario eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el campo de formulario: '.$e->getMessage());
        }
    }
}
