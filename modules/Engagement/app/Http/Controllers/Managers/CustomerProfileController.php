<?php

namespace Modules\Engagement\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\VisitorContext;
use Modules\Engagement\Models\VisitorScore;
use Modules\Engagement\Models\VisitorSession;
use Modules\Helpdesk\Models\Customer;

class CustomerProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.events.view');
    }

    public function show(Customer $customer): View
    {
        return view('engagement::managers.customer-profile.show', compact('customer'));
    }

    public function data(Customer $customer): JsonResponse
    {
        $sessions = VisitorSession::query()
            ->where('customer_id', $customer->id)
            ->latest('last_activity_at')
            ->limit(20)
            ->get();

        $sessionTokens = $sessions->pluck('session_token')->all();

        $scores = VisitorScore::query()
            ->whereIn('session_token', $sessionTokens)
            ->latest('updated_at')
            ->get();

        $contexts = VisitorContext::query()
            ->whereIn('session_token', $sessionTokens)
            ->get();

        $events = Event::query()
            ->whereIn('session_token', $sessionTokens)
            ->latest('occurred_at')
            ->limit(100)
            ->get();

        $latestScore = $scores->sortByDesc('updated_at')->first();
        $latestContext = $contexts->sortByDesc('updated_at')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                ],
                'kpis' => [
                    'sessions_total' => $sessions->count(),
                    'events_total' => $events->count(),
                    'current_score' => $latestScore?->score ?? 0,
                    'current_segment' => $latestScore?->segment ?? 'cold',
                ],
                'attribution' => $latestContext ? [
                    'utm_source' => $latestContext->utm_source,
                    'utm_medium' => $latestContext->utm_medium,
                    'utm_campaign' => $latestContext->utm_campaign,
                    'referrer_host' => $latestContext->referrer_host,
                ] : null,
                'sessions' => $sessions->map(fn ($s) => [
                    'session_token' => substr((string) $s->session_token, 0, 8).'…',
                    'current_url' => $s->current_url,
                    'started_at' => $s->started_at?->toIso8601String(),
                    'last_activity_at' => $s->last_activity_at?->toIso8601String(),
                ]),
                'events' => $events->map(fn ($e) => [
                    'name' => $e->event_name,
                    'platform' => $e->platform,
                    'occurred_at' => $e->occurred_at?->toIso8601String(),
                    'properties' => $e->properties,
                ]),
            ],
        ]);
    }
}
