<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\Mailing\MailingLayout;
use Modules\Mailing\Models\Mailing\MailingTemplate;
use Modules\Mailing\Models\Mailing\MailingTemplateLang;

class TemplateController extends Controller
{
    /**
     * Display a listing of email templates.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.templates.view');

        $templates = MailingTemplate::with('layout', 'translations')
            ->orderBy('name')
            ->get();

        return view('mailing::settings.templates.index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for creating a new email template.
     */
    public function create(): View
    {
        Gate::authorize('mailing.settings.templates.create');

        $layouts = MailingLayout::where('is_enabled', true)
            ->orderBy('name')
            ->get();

        return view('mailing::settings.templates.create', [
            'layouts' => $layouts,
        ]);
    }

    /**
     * Store a newly created email template.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.templates.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'key' => 'required|string|max:255|unique:mailing_templates,key',
                'layout_id' => 'nullable|integer|exists:mailing_layouts,id',
                'module' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'is_enabled' => 'boolean',
                'is_protected' => 'boolean',
                'variables' => 'nullable|json',
                'subject' => 'required|string',
                'content' => 'required|string',
            ]);

            $template = MailingTemplate::create($validated);

            // Store translation
            if ($validated['subject'] && $validated['content']) {
                MailingTemplateLang::create([
                    'mailing_template_id' => $template->id,
                    'lang_id' => 1,
                    'subject' => $validated['subject'],
                    'content' => $validated['content'],
                ]);
            }

            return redirect()
                ->route('settings.mailing.templates.email.index')
                ->with('success', 'Plantilla de correo creada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear la plantilla: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified email template.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailing.settings.templates.edit');

        $template = MailingTemplate::with('layout', 'translations')
            ->findOrFail($id);

        $layouts = MailingLayout::where('is_enabled', true)
            ->orderBy('name')
            ->get();

        return view('mailing::settings.templates.edit', [
            'template' => $template,
            'layouts' => $layouts,
        ]);
    }

    /**
     * Update the specified email template.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.templates.edit');

        try {
            $template = MailingTemplate::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'key' => 'required|string|max:255|unique:mailing_templates,key,'.$id,
                'layout_id' => 'nullable|integer|exists:mailing_layouts,id',
                'module' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'is_enabled' => 'boolean',
                'is_protected' => 'boolean',
                'variables' => 'nullable|json',
                'subject' => 'required|string',
                'content' => 'required|string',
            ]);

            $template->update($validated);

            // Update or create translation
            $translation = $template->translations()->where('lang_id', 1)->first();

            if ($translation) {
                $translation->update([
                    'subject' => $validated['subject'],
                    'content' => $validated['content'],
                ]);
            } else {
                MailingTemplateLang::create([
                    'mailing_template_id' => $template->id,
                    'lang_id' => 1,
                    'subject' => $validated['subject'],
                    'content' => $validated['content'],
                ]);
            }

            return redirect()
                ->route('settings.mailing.templates.email.index')
                ->with('success', 'Plantilla de correo actualizada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar la plantilla: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified email template.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.templates.delete');

        try {
            $template = MailingTemplate::findOrFail($id);

            // Check if template is protected
            if ($template->is_protected) {
                return redirect()
                    ->back()
                    ->with('error', 'No se puede eliminar una plantilla protegida.');
            }

            // Delete associated translations
            $template->translations()->delete();

            // Delete template
            $template->delete();

            return redirect()
                ->route('settings.mailing.templates.email.index')
                ->with('success', 'Plantilla de correo eliminada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la plantilla: '.$e->getMessage());
        }
    }

    /**
     * Preview the specified email template.
     */
    public function preview(int $id): View
    {
        Gate::authorize('mailing.settings.templates.view');

        $template = MailingTemplate::with('layout')
            ->findOrFail($id);

        return view('mailing::settings.templates.preview', [
            'template' => $template,
        ]);
    }

    /**
     * Duplicate the specified email template.
     */
    public function duplicate(int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.templates.create');

        try {
            $originalTemplate = MailingTemplate::with('translations')
                ->findOrFail($id);

            // Create a copy
            $newTemplate = $originalTemplate->replicate();
            $newTemplate->key = $originalTemplate->key.'_copy_'.time();
            $newTemplate->name = $originalTemplate->name.' (Copia)';
            $newTemplate->is_protected = false;
            $newTemplate->save();

            // Duplicate translations
            foreach ($originalTemplate->translations as $translation) {
                $newTranslation = $translation->replicate();
                $newTranslation->mailing_template_id = $newTemplate->id;
                $newTranslation->save();
            }

            return redirect()
                ->route('settings.mailing.templates.email.edit', $newTemplate->id)
                ->with('success', 'Plantilla duplicada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al duplicar la plantilla: '.$e->getMessage());
        }
    }
}
