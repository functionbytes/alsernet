<?php

namespace Modules\Helpdesk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Models\StatusComponent;
use Modules\Helpdesk\Models\StatusIncident;

class StatusPageController extends Controller
{
    public function index(): View
    {
        $components = StatusComponent::query()->visible()->get();
        $activeIncidents = StatusIncident::query()->active()->with([])->latest('started_at')->get();
        $recentIncidents = StatusIncident::query()->resolved()->recent(90)->latest('started_at')->take(20)->get();

        $overallStatus = $this->computeOverallStatus($components);

        return view('helpdesk::public.status', compact(
            'components',
            'activeIncidents',
            'recentIncidents',
            'overallStatus'
        ));
    }

    public function json(): JsonResponse
    {
        $components = StatusComponent::query()->visible()->get();
        $activeIncidents = StatusIncident::query()->active()->latest('started_at')->get();

        return response()->json([
            'page' => [
                'name' => config('app.name'),
                'updated_at' => now()->toIso8601String(),
            ],
            'overall_status' => $this->computeOverallStatus($components),
            'components' => $components->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
                'description' => $c->description,
            ]),
            'incidents' => $activeIncidents->map(fn ($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'severity' => $i->severity,
                'status' => $i->status,
                'started_at' => $i->started_at->toIso8601String(),
                'affected_components' => $i->affected_components ?? [],
            ]),
        ]);
    }

    private function computeOverallStatus($components): string
    {
        if ($components->contains('status', 'major_outage')) {
            return 'major_outage';
        }

        if ($components->contains('status', 'partial_outage')) {
            return 'partial_outage';
        }

        if ($components->contains('status', 'degraded')) {
            return 'degraded';
        }

        if ($components->contains('status', 'maintenance')) {
            return 'maintenance';
        }

        return 'operational';
    }
}
