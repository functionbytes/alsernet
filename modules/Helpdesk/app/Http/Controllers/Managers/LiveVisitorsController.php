<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\WidgetSession;

class LiveVisitorsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Customer::class);

        return view('helpdesk::managers.live-visitors.index');
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $sessions = WidgetSession::query()
            ->with('customer')
            ->where('last_activity_at', '>=', now()->subMinutes(5))
            ->latest('last_activity_at')
            ->limit(100)
            ->get();

        return response()->json([
            'total' => $sessions->count(),
            'known' => $sessions->whereNotNull('customer')->count(),
            'anonymous' => $sessions->whereNull('customer')->count(),
            'sessions' => $sessions->map(fn ($s) => [
                'id' => $s->id,
                'current_url' => $s->current_url,
                'country_code' => $s->country_code,
                'device' => $s->device,
                'last_activity_at' => $s->last_activity_at?->diffForHumans(),
                'customer' => $s->customer ? [
                    'id' => $s->customer->id,
                    'name' => $s->customer->name ?? $s->customer->email ?? 'Anónimo',
                ] : null,
            ]),
        ]);
    }
}
