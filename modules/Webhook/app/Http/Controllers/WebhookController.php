<?php

namespace Modules\Webhook\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Webhook\Models\WebhookIntegration;

class WebhookController extends Controller
{
    /**
     * Display a listing of webhook integrations.
     */
    public function index(Request $request)
    {
        $integrations = WebhookIntegration::query()
            ->with(['subscriptions'])
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('webhook::webhooks.index', compact('integrations'));
    }

    /**
     * Show details of a specific integration.
     */
    public function show(string $uid)
    {
        $integration = WebhookIntegration::where('uid', $uid)
            ->with(['subscriptions'])
            ->firstOrFail();

        return view('webhook::webhooks.show', compact('integration'));
    }

    /**
     * Display webhook deliveries for monitoring.
     */
    public function deliveries(Request $request)
    {
        // To be implemented when webhook_deliveries table is ready
        return view('webhook::webhooks.deliveries');
    }

    /**
     * Display webhook events.
     */
    public function events(Request $request)
    {
        // To be implemented when webhook_events table is ready
        return view('webhook::webhooks.events');
    }
}
