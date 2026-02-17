<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\Mailrelay\Entities\EmailTemplate;
use Modules\Mailrelay\Entities\MailrelayLayout;
use Modules\Mailrelay\Http\Controllers\Controller;
use Modules\Mailrelay\Services\TemplateRendererService;
use Modules\Mailrelay\Services\VariableReplacementService;

class TemplateController extends Controller
{
    protected TemplateRendererService $rendererService;

    protected VariableReplacementService $variableService;

    public function __construct(
        TemplateRendererService $rendererService,
        VariableReplacementService $variableService
    ) {
        $this->rendererService = $rendererService;
        $this->variableService = $variableService;
    }

    /**
     * Display a listing of templates.
     */
    public function index(Request $request): View
    {
        Gate::authorize('mailrelay.settings.templates.view');

        $query = EmailTemplate::query();

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

        $layouts = MailrelayLayout::active()->layouts()->orderBy('name')->get();

        return view('mailrelay::settings.templates.create', [
            'layouts' => $layouts,
        ]);
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
                'layout_id' => 'nullable|exists:mailrelay_layouts,id',
                'use_layout' => 'boolean',
            ]);

            EmailTemplate::create($validated);

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

        $template = EmailTemplate::findOrFail($id);
        $layouts = MailrelayLayout::active()->layouts()->orderBy('name')->get();

        return view('mailrelay::settings.templates.edit', [
            'template' => $template,
            'layouts' => $layouts,
        ]);
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.templates.edit');

        try {
            $template = EmailTemplate::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'event_type' => 'required|string|max:100',
                'subject' => 'required|string|max:255',
                'body' => 'required|string',
                'active' => 'boolean',
                'use_html' => 'boolean',
                'mailrelay_template_id' => 'nullable|integer',
                'layout_id' => 'nullable|exists:mailrelay_layouts,id',
                'use_layout' => 'boolean',
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
            $template = EmailTemplate::findOrFail($id);
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
            $template = EmailTemplate::findOrFail($id);

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

    /**
     * Preview template with AJAX (live preview without saving).
     */
    public function previewAjax(Request $request, int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.templates.view');

        try {
            $template = EmailTemplate::findOrFail($id);

            // Get temporary content from request (for live preview while editing)
            $tempContent = $request->input('html_content', $template->html_content);
            $tempSubject = $request->input('subject', $template->subject);

            // Create a temporary template object for preview
            $previewTemplate = clone $template;
            $previewTemplate->html_content = $tempContent;
            $previewTemplate->subject = $tempSubject;

            // Render with example data
            $renderedHtml = $this->rendererService->renderPreview($previewTemplate);
            $renderedSubject = $this->rendererService->renderSubject($previewTemplate, [], true);

            return response()->json([
                'success' => true,
                'html' => $renderedHtml,
                'subject' => $renderedSubject,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available variables for a template.
     */
    public function variables(int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.templates.view');

        try {
            $template = EmailTemplate::findOrFail($id);

            // Get all variables grouped by category
            $variablesByCategory = $this->variableService->getVariablesGroupedByCategory();

            // Extract variables from current template content
            $usedVariables = $this->variableService->extractVariablesFromContent(
                $template->html_content ?? ''
            );

            return response()->json([
                'success' => true,
                'variables' => $variablesByCategory,
                'used_variables' => $usedVariables,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format HTML content (beautify).
     */
    public function formatHtml(Request $request): JsonResponse
    {
        try {
            $html = $request->input('html', '');

            if (empty($html)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No HTML content provided',
                ], 400);
            }

            $formatted = $this->rendererService->beautifyHtml($html);

            return response()->json([
                'success' => true,
                'html' => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a test email with this template.
     */
    public function sendTest(Request $request, int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.templates.edit');

        try {
            $validated = $request->validate([
                'email' => 'required|email',
            ]);

            $template = EmailTemplate::findOrFail($id);

            // Render template with example data for preview
            $renderedHtml = $this->rendererService->renderPreview($template);
            $renderedSubject = $this->rendererService->renderSubject($template, [], true);

            // Send email
            Mail::html($renderedHtml, function ($message) use ($validated, $renderedSubject) {
                $message->to($validated['email'])
                    ->subject('[TEST] '.$renderedSubject);
            });

            return response()->json([
                'success' => true,
                'message' => 'Email de prueba enviado a '.$validated['email'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle template status (active/inactive).
     */
    public function toggleStatus(int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.templates.edit');

        try {
            $template = EmailTemplate::findOrFail($id);

            // Toggle active status (assuming 'active' field exists)
            $newStatus = ! ($template->active ?? false);
            $template->active = $newStatus;
            $template->save();

            return response()->json([
                'success' => true,
                'active' => $newStatus,
                'message' => $newStatus
                    ? 'Plantilla activada correctamente'
                    : 'Plantilla desactivada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
