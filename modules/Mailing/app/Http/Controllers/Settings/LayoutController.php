<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\Mailing\MailingLayout;
use Modules\Mailing\Models\Mailing\MailingLayoutLang;
use Modules\Mailing\Models\Mailing\MailingTemplate;

class LayoutController extends Controller
{
    /**
     * Display a listing of email layouts.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.layouts.view');

        $layouts = MailingLayout::with('translations')
            ->orderBy('alias')
            ->get();

        return view('mailing::settings.layouts.index', [
            'layouts' => $layouts,
        ]);
    }

    /**
     * Show the form for creating a new email layout.
     */
    public function create(): View
    {
        Gate::authorize('mailing.settings.layouts.create');

        return view('mailing::settings.layouts.create');
    }

    /**
     * Store a newly created email layout.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.layouts.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'alias' => 'required|string|max:255|unique:mailing_layouts,alias',
                'code' => 'nullable|string|max:255|unique:mailing_layouts,code',
                'type' => 'required|string|in:email,form,page',
                'group_name' => 'nullable|string|max:255',
                'is_enabled' => 'boolean',
                'is_protected' => 'boolean',
                'subject' => 'nullable|string',
                'content' => 'required|string',
            ]);

            $layout = MailingLayout::create($validated);

            // Store translation
            if ($validated['content']) {
                MailingLayoutLang::create([
                    'layout_id' => $layout->id,
                    'lang_id' => 1,
                    'subject' => $validated['subject'] ?? null,
                    'content' => $validated['content'],
                ]);
            }

            return redirect()
                ->route('settings.mailing.templates.layouts.index')
                ->with('success', 'Diseño de correo creado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el diseño: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified email layout.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailing.settings.layouts.edit');

        $layout = MailingLayout::with('translations')
            ->findOrFail($id);

        return view('mailing::settings.layouts.edit', [
            'layout' => $layout,
        ]);
    }

    /**
     * Update the specified email layout.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.layouts.edit');

        try {
            $layout = MailingLayout::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'alias' => 'required|string|max:255|unique:mailing_layouts,alias,'.$id,
                'code' => 'nullable|string|max:255|unique:mailing_layouts,code,'.$id,
                'type' => 'required|string|in:email,form,page',
                'group_name' => 'nullable|string|max:255',
                'is_enabled' => 'boolean',
                'is_protected' => 'boolean',
                'subject' => 'nullable|string',
                'content' => 'required|string',
            ]);

            $layout->update($validated);

            // Update or create translation
            $translation = $layout->translations()->where('lang_id', 1)->first();

            if ($translation) {
                $translation->update([
                    'subject' => $validated['subject'] ?? null,
                    'content' => $validated['content'],
                ]);
            } else {
                MailingLayoutLang::create([
                    'layout_id' => $layout->id,
                    'lang_id' => 1,
                    'subject' => $validated['subject'] ?? null,
                    'content' => $validated['content'],
                ]);
            }

            return redirect()
                ->route('settings.mailing.templates.layouts.index')
                ->with('success', 'Diseño de correo actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el diseño: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified email layout.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.layouts.delete');

        try {
            $layout = MailingLayout::findOrFail($id);

            // Check if layout is protected
            if ($layout->is_protected) {
                return redirect()
                    ->back()
                    ->with('error', 'No se puede eliminar un diseño protegido.');
            }

            // Check if layout is being used by templates
            $templateCount = MailingTemplate::where('layout_id', $id)->count();

            if ($templateCount > 0) {
                return redirect()
                    ->back()
                    ->with('error', "No se puede eliminar este diseño. Está siendo utilizado por {$templateCount} plantilla(s).");
            }

            // Delete associated translations
            $layout->translations()->delete();

            // Delete layout
            $layout->delete();

            return redirect()
                ->route('settings.mailing.templates.layouts.index')
                ->with('success', 'Diseño de correo eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el diseño: '.$e->getMessage());
        }
    }

    /**
     * Preview the specified email layout.
     */
    public function preview(int $id): View
    {
        Gate::authorize('mailing.settings.layouts.view');

        $layout = MailingLayout::with('translations')
            ->findOrFail($id);

        return view('mailing::settings.layouts.preview', [
            'layout' => $layout,
        ]);
    }
}
