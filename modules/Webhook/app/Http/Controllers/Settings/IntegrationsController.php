<?php

namespace Modules\Webhook\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Webhook\Http\Requests\Managers\Settings\StoreIntegrationRequest;
use Modules\Webhook\Http\Requests\Managers\Settings\UpdateIntegrationRequest;
use Modules\Webhook\Models\WebhookIntegration;

class IntegrationsController extends Controller
{
    /**
     * Display a listing of integrations.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $plan = $request->input('plan');

        $integrations = WebhookIntegration::query()
            ->withCount('subscriptions')
            ->when($search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('uid', 'like', "%{$s}%"))
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($plan, fn ($q, $p) => $q->where('plan', $p))
            ->latest()
            ->paginate(20);

        return view('webhook::integrations.index', compact('integrations', 'search', 'status', 'plan'));
    }

    /**
     * Show the form for creating a new integration.
     */
    public function create()
    {
        return view('webhook::integrations.create');
    }

    /**
     * Store a newly created integration.
     */
    public function store(StoreIntegrationRequest $request)
    {
        $integration = WebhookIntegration::create($request->validated());

        return redirect()
            ->route('manager.backups.webhooks.integrations.show', $integration->uid)
            ->with('success', 'Integration created successfully.');
    }

    /**
     * Display the specified integration.
     */
    public function show(string $uid)
    {
        $integration = WebhookIntegration::where('uid', $uid)
            ->with(['subscriptions'])
            ->firstOrFail();

        return view('webhook::integrations.show', compact('integration'));
    }

    /**
     * Show the form for editing the specified integration.
     */
    public function edit(string $uid)
    {
        $integration = WebhookIntegration::where('uid', $uid)->firstOrFail();

        return view('webhook::integrations.edit', compact('integration'));
    }

    /**
     * Update the specified integration.
     */
    public function update(UpdateIntegrationRequest $request, string $uid)
    {
        $integration = WebhookIntegration::where('uid', $uid)->firstOrFail();
        $integration->update($request->validated());

        return redirect()
            ->route('manager.backups.webhooks.integrations.show', $integration->uid)
            ->with('success', 'Integration updated successfully.');
    }

    /**
     * Remove the specified integration.
     */
    public function destroy(string $uid)
    {
        $integration = WebhookIntegration::where('uid', $uid)->firstOrFail();
        $integration->delete();

        return redirect()
            ->route('manager.backups.webhooks.integrations.index')
            ->with('success', 'Integration deleted successfully.');
    }

    /**
     * Activate an integration.
     */
    public function activate(string $uid)
    {
        $integration = WebhookIntegration::where('uid', $uid)->firstOrFail();
        $integration->update(['status' => 'active']);

        return back()->with('success', 'Integration activated successfully.');
    }

    /**
     * Suspend an integration.
     */
    public function suspend(string $uid)
    {
        $integration = WebhookIntegration::where('uid', $uid)->firstOrFail();
        $integration->update(['status' => 'suspended']);

        return back()->with('success', 'Integration suspended successfully.');
    }

    /**
     * Disable an integration.
     */
    public function disable(string $uid)
    {
        $integration = WebhookIntegration::where('uid', $uid)->firstOrFail();
        $integration->update(['status' => 'disabled']);

        return back()->with('success', 'Integration disabled successfully.');
    }
}
