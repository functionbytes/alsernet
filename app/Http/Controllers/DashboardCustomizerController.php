<?php

namespace App\Http\Controllers;

use App\Models\UserDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Models\Ticket;

class DashboardCustomizerController extends Controller
{
    public function index(Request $request): View
    {
        $dashboard = UserDashboard::firstOrCreate(
            ['user_id' => $request->user()->id, 'is_default' => true],
            ['name' => 'My Dashboard', 'widgets' => $this->defaultWidgets()]
        );

        return view('dashboard-customizer', compact('dashboard'));
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widgets' => ['required', 'array'],
        ]);

        UserDashboard::updateOrCreate(
            ['user_id' => $request->user()->id, 'is_default' => true],
            ['widgets' => $validated['widgets']]
        );

        return response()->json(['success' => true]);
    }

    public function reset(Request $request): JsonResponse
    {
        UserDashboard::where('user_id', $request->user()->id)
            ->where('is_default', true)
            ->update(['widgets' => json_encode($this->defaultWidgets())]);

        return response()->json(['success' => true]);
    }

    public function widgetData(string $source): JsonResponse
    {
        $value = $this->resolveWidgetValue($source);

        return response()->json(['value' => $value]);
    }

    private function resolveWidgetValue(string $source): int
    {
        if (! class_exists(Ticket::class)) {
            return 0;
        }

        return match ($source) {
            'tickets_open' => Ticket::query()
                ->whereNull('resolved_at')
                ->count(),
            'tickets_unassigned' => Ticket::query()
                ->whereNull('assignee_id')
                ->count(),
            'sla_breach' => Ticket::query()
                ->where(function ($q) {
                    $q->where('sla_first_response_breached', 1)
                        ->orWhere('sla_resolution_breached', 1);
                })
                ->count(),
            'resolved_today' => Ticket::query()
                ->whereDate('resolved_at', today())
                ->count(),
            default => 0,
        };
    }

    private function defaultWidgets(): array
    {
        return [
            ['id' => 'w1', 'type' => 'stat', 'title' => 'Tickets abiertos', 'source' => 'tickets_open', 'x' => 0, 'y' => 0, 'w' => 3, 'h' => 2],
            ['id' => 'w2', 'type' => 'stat', 'title' => 'Sin asignar', 'source' => 'tickets_unassigned', 'x' => 3, 'y' => 0, 'w' => 3, 'h' => 2],
            ['id' => 'w3', 'type' => 'stat', 'title' => 'SLA breach', 'source' => 'sla_breach', 'x' => 6, 'y' => 0, 'w' => 3, 'h' => 2],
            ['id' => 'w4', 'type' => 'stat', 'title' => 'Resueltos hoy', 'source' => 'resolved_today', 'x' => 9, 'y' => 0, 'w' => 3, 'h' => 2],
            ['id' => 'w5', 'type' => 'recent_tickets', 'title' => 'Tickets recientes', 'source' => null, 'x' => 0, 'y' => 2, 'w' => 12, 'h' => 4],
        ];
    }
}
