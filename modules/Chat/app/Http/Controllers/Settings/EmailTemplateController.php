<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Templates\EmailTemplate;
use Modules\Chat\Services\EmailTemplateVariableService;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of email templates.
     */
    public function index(Request $request)
    {
        $accountId = $request->user()->account_id;

        $templates = EmailTemplate::where('account_id', $accountId)
            ->when($request->event_type, function ($query, $eventType) {
                $query->where('event_type', $eventType);
            })
            ->when($request->has('active'), function ($query) use ($request) {
                $query->where('active', $request->boolean('active'));
            })
            ->latest()
            ->paginate(20);

        $eventTypes = EmailTemplateVariableService::getEventTypes();

        return view('Chat::settings.email-templates.index', compact('templates', 'eventTypes'));
    }

    /**
     * Show the form for creating a new email template.
     */
    public function create(Request $request)
    {
        $eventTypes = EmailTemplateVariableService::getEventTypes();
        $selectedEventType = $request->get('event_type', 'conversation_created');
        $variables = EmailTemplateVariableService::getAvailableVariables($selectedEventType);

        return view('Chat::settings.email-templates.create', compact('eventTypes', 'selectedEventType', 'variables'));
    }

    /**
     * Store a newly created email template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'event_type' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'active' => 'boolean',
        ]);

        $validated['account_id'] = $request->user()->account_id;
        $validated['active'] = $request->boolean('active', true);

        $template = EmailTemplate::create($validated);

        return redirect()
            ->route('settings.chat.email.index')
            ->with('success', '¡Plantilla de email creada exitosamente!');
    }

    /**
     * Display the specified email template.
     */
    public function show(EmailTemplate $emailTemplate)
    {
        $this->authorize('view', $emailTemplate);

        $variables = EmailTemplateVariableService::getAvailableVariables($emailTemplate->event_type);

        return view('Chat::settings.email-templates.show', compact('emailTemplate', 'variables'));
    }

    /**
     * Show the form for editing an email template.
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        $this->authorize('update', $emailTemplate);

        $eventTypes = EmailTemplateVariableService::getEventTypes();
        $variables = EmailTemplateVariableService::getAvailableVariables($emailTemplate->event_type);

        return view('Chat::settings.email-templates.edit', compact('emailTemplate', 'eventTypes', 'variables'));
    }

    /**
     * Update the specified email template.
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $this->authorize('update', $emailTemplate);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'event_type' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'active' => 'boolean',
        ]);

        $validated['active'] = $request->boolean('active', true);

        $emailTemplate->update($validated);

        return redirect()
            ->route('settings.chat.email.index')
            ->with('success', '¡Plantilla de email actualizada exitosamente!');
    }

    /**
     * Remove the specified email template.
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        $this->authorize('delete', $emailTemplate);

        $emailTemplate->delete();

        return redirect()
            ->route('settings.chat.email.index')
            ->with('success', '¡Plantilla de email eliminada exitosamente!');
    }

    /**
     * Preview email template with sample data.
     */
    public function preview(Request $request, EmailTemplate $emailTemplate)
    {
        $this->authorize('view', $emailTemplate);

        // Generate sample data for preview
        $sampleData = $this->getSampleData($emailTemplate->event_type);

        $renderedHtml = EmailTemplateVariableService::render($emailTemplate->body_html, $sampleData);
        $renderedSubject = EmailTemplateVariableService::render($emailTemplate->subject, $sampleData);

        return view('Chat::settings.email-templates.preview', compact('emailTemplate', 'renderedHtml', 'renderedSubject'));
    }

    /**
     * Get available variables for an event type (AJAX).
     */
    public function getVariables(Request $request)
    {
        $eventType = $request->get('event_type', 'conversation_created');
        $variables = EmailTemplateVariableService::getAvailableVariables($eventType);

        return response()->json($variables);
    }

    /**
     * Toggle template active status.
     */
    public function toggleActive(EmailTemplate $emailTemplate)
    {
        $this->authorize('update', $emailTemplate);

        $emailTemplate->update(['active' => ! $emailTemplate->active]);

        return back()->with('success', 'Estado de plantilla actualizado');
    }

    /**
     * Get sample data for preview.
     */
    protected function getSampleData(string $eventType): array
    {
        return [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'current_date' => now()->format('d/m/Y'),
            'current_time' => now()->format('H:i'),
            'conversation_id' => '12345',
            'conversation_url' => '#',
            'conversation_status' => 'Abierta',
            'conversation_priority' => 'Alta',
            'contact_name' => 'Juan Pérez',
            'contact_email' => 'juan.perez@ejemplo.com',
            'contact_phone' => '+1 234 567 8900',
            'contact_city' => 'Ciudad de México',
            'contact_country' => 'México',
            'inbox_name' => 'Soporte Web',
            'assigned_agent_name' => 'María García',
            'assigned_agent_email' => 'maria@ejemplo.com',
            'team_name' => 'Equipo de Soporte',
            'message_content' => 'Hola, necesito ayuda con mi cuenta.',
            'message_sender_name' => 'Juan Pérez',
            'message_time' => now()->format('H:i'),
        ];
    }
}
