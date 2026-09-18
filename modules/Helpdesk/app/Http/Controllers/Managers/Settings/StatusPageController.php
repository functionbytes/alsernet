<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Managers\Settings\StoreStatusComponentRequest;
use Modules\Helpdesk\Http\Requests\Managers\Settings\StoreStatusIncidentRequest;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateStatusComponentRequest;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateStatusIncidentRequest;
use Modules\Helpdesk\Models\StatusComponent;
use Modules\Helpdesk\Models\StatusIncident;

class StatusPageController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.status.manage');
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('settings.helpdesk.status.components');
    }

    public function componentsIndex(): View
    {
        $components = StatusComponent::query()->orderBy('order')->paginate(20);
        $stats = [
            'total' => StatusComponent::count(),
            'operational' => StatusComponent::where('status', 'operational')->count(),
            'issues' => StatusComponent::whereIn('status', ['degraded', 'partial_outage', 'major_outage'])->count(),
        ];

        return view('helpdesk::settings.status-page.components', compact('components', 'stats'));
    }

    public function componentCreate(): View
    {
        return view('helpdesk::settings.status-page.component-form');
    }

    public function componentStore(StoreStatusComponentRequest $request): RedirectResponse
    {
        StatusComponent::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'order' => $request->integer('order', 0),
            'is_visible' => $request->boolean('is_visible', true),
        ]);

        return redirect()->route('settings.helpdesk.status.components')
            ->with('success', 'Componente creado exitosamente.');
    }

    public function componentEdit(StatusComponent $statusComponent): View
    {
        return view('helpdesk::settings.status-page.component-form', ['component' => $statusComponent]);
    }

    public function componentUpdate(UpdateStatusComponentRequest $request, StatusComponent $statusComponent): RedirectResponse
    {
        $statusComponent->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'order' => $request->integer('order', 0),
            'is_visible' => $request->boolean('is_visible'),
        ]);

        return redirect()->route('settings.helpdesk.status.components')
            ->with('success', 'Componente actualizado exitosamente.');
    }

    public function componentDestroy(StatusComponent $statusComponent): RedirectResponse
    {
        $statusComponent->delete();

        return redirect()->route('settings.helpdesk.status.components')
            ->with('success', 'Componente eliminado exitosamente.');
    }

    public function incidentsIndex(): View
    {
        $incidents = StatusIncident::query()->latest('started_at')->paginate(20);
        $stats = [
            'total' => StatusIncident::count(),
            'active' => StatusIncident::whereNull('resolved_at')->count(),
            'resolved' => StatusIncident::whereNotNull('resolved_at')->count(),
        ];

        return view('helpdesk::settings.status-page.incidents', compact('incidents', 'stats'));
    }

    public function incidentCreate(): View
    {
        $components = StatusComponent::query()->orderBy('order')->get();

        return view('helpdesk::settings.status-page.incident-form', compact('components'));
    }

    public function incidentStore(StoreStatusIncidentRequest $request): RedirectResponse
    {
        $incident = StatusIncident::create([
            'title' => $request->title,
            'body' => $request->body,
            'severity' => $request->severity,
            'status' => $request->status,
            'affected_components' => $request->input('affected_components', []),
            'started_at' => now(),
            'resolved_at' => $request->status === 'resolved' ? now() : null,
        ]);

        return redirect()->route('settings.helpdesk.status.incidents')
            ->with('success', 'Incidente creado exitosamente.');
    }

    public function incidentEdit(StatusIncident $statusIncident): View
    {
        $components = StatusComponent::query()->orderBy('order')->get();

        return view('helpdesk::settings.status-page.incident-form', [
            'incident' => $statusIncident,
            'components' => $components,
        ]);
    }

    public function incidentUpdate(UpdateStatusIncidentRequest $request, StatusIncident $statusIncident): RedirectResponse
    {
        $resolvedAt = $statusIncident->resolved_at;
        if ($request->status === 'resolved' && ! $resolvedAt) {
            $resolvedAt = now();
        } elseif ($request->status !== 'resolved') {
            $resolvedAt = null;
        }

        $statusIncident->update([
            'title' => $request->title,
            'body' => $request->body,
            'severity' => $request->severity,
            'status' => $request->status,
            'affected_components' => $request->input('affected_components', []),
            'resolved_at' => $resolvedAt,
        ]);

        return redirect()->route('settings.helpdesk.status.incidents')
            ->with('success', 'Incidente actualizado exitosamente.');
    }

    public function incidentDestroy(StatusIncident $statusIncident): RedirectResponse
    {
        $statusIncident->delete();

        return redirect()->route('settings.helpdesk.status.incidents')
            ->with('success', 'Incidente eliminado exitosamente.');
    }
}
