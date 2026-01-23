<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailrelay\Http\Controllers\Controller;
use Modules\Mailrelay\Models\MailrelayTemplate;

class TemplateController extends Controller
{
    /**
     * Display a listing of templates.
     */
    public function index(Request $request): View
    {
        Gate::authorize('mailrelay.settings.templates.view');

        $query = MailrelayTemplate::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhere('event_type', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $templates = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('mailrelay::settings.templates.index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(): View
    {
        Gate::authorize('mailrelay.settings.templates.create');

        return view('mailrelay::settings.templates.create');
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.templates.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'event_type' => 'required|string|max:100',
                'subject' => 'required|string|max:255',
                'body' => 'required|string',
                'active' => 'boolean',
                'use_html' => 'boolean',
                'mailrelay_template_id' => 'nullable|integer',
            ]);

            MailrelayTemplate::create($validated);

            return redirect()
                ->route('managers.settings.mailrelay.templates.index')
                ->with('success', 'Plantilla creada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear la plantilla: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailrelay.settings.templates.edit');

        $template = MailrelayTemplate::findOrFail($id);

        return view('mailrelay::settings.templates.edit', [
            'template' => $template,
        ]);
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.templates.edit');

        try {
            $template = MailrelayTemplate::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'event_type' => 'required|string|max:100',
                'subject' => 'required|string|max:255',
                'body' => 'required|string',
                'active' => 'boolean',
                'use_html' => 'boolean',
                'mailrelay_template_id' => 'nullable|integer',
            ]);

            $template->update($validated);

            return redirect()
                ->route('managers.settings.mailrelay.templates.index')
                ->with('success', 'Plantilla actualizada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar la plantilla: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified template.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.templates.delete');

        try {
            $template = MailrelayTemplate::findOrFail($id);
            $template->delete();

            return redirect()
                ->route('managers.settings.mailrelay.templates.index')
                ->with('success', 'Plantilla eliminada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la plantilla: '.$e->getMessage());
        }
    }

    /**
     * Duplicate the specified template.
     */
    public function duplicate(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.templates.create');

        try {
            $template = MailrelayTemplate::findOrFail($id);

            $newTemplate = $template->replicate();
            $newTemplate->name = $template->name.' (Copia)';
            $newTemplate->active = false;
            $newTemplate->save();

            return redirect()
                ->route('managers.settings.mailrelay.templates.edit', $newTemplate->id)
                ->with('success', 'Plantilla duplicada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al duplicar la plantilla: '.$e->getMessage());
        }
    }
}
