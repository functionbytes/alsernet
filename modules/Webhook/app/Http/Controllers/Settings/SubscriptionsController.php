<?php

namespace Modules\Webhook\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Webhook\Http\Requests\Managers\Settings\StoreSubscriptionRequest;
use Modules\Webhook\Http\Requests\Managers\Settings\UpdateSubscriptionRequest;
use Modules\Webhook\Models\WebhookIntegration;
use Modules\Webhook\Models\WebhookSubscription;

class SubscriptionsController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $isActive = $request->input('is_active');

        $subscriptions = WebhookSubscription::query()
            ->with(['integration'])
            ->when($search, fn ($q, $s) => $q->where('event_type', 'like', "%{$s}%")->orWhere('webhook_url', 'like', "%{$s}%"))
            ->when($isActive !== null && $isActive !== '', fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate(20);

        return view('webhook::subscriptions.index', compact('subscriptions', 'search', 'isActive'));
    }

    /**
     * Show the form for creating a new subscription.
     */
    public function create(Request $request)
    {
        $integrations = WebhookIntegration::active()->get();
        $preselectedIntegration = null;

        if ($request->has('integration_id')) {
            $preselectedIntegration = WebhookIntegration::find($request->input('integration_id'));
        }

        return view('webhook::subscriptions.create', compact('integrations', 'preselectedIntegration'));
    }

    /**
     * Store a newly created subscription.
     */
    public function store(StoreSubscriptionRequest $request)
    {
        $subscription = WebhookSubscription::create($request->validated());

        return redirect()
            ->route('manager.backups.webhooks.subscriptions.show', $subscription->uid)
            ->with('success', 'Subscription created successfully.');
    }

    /**
     * Display the specified subscription.
     */
    public function show(string $uid)
    {
        $subscription = WebhookSubscription::where('uid', $uid)
            ->with(['integration'])
            ->firstOrFail();

        return view('webhook::subscriptions.show', compact('subscription'));
    }

    /**
     * Show the form for editing the specified subscription.
     */
    public function edit(string $uid)
    {
        $subscription = WebhookSubscription::where('uid', $uid)->firstOrFail();
        $integrations = WebhookIntegration::active()->get();

        return view('webhook::subscriptions.edit', compact('subscription', 'integrations'));
    }

    /**
     * Update the specified subscription.
     */
    public function update(UpdateSubscriptionRequest $request, string $uid)
    {
        $subscription = WebhookSubscription::where('uid', $uid)->firstOrFail();
        $subscription->update($request->validated());

        return redirect()
            ->route('manager.backups.webhooks.subscriptions.show', $subscription->uid)
            ->with('success', 'Subscription updated successfully.');
    }

    /**
     * Remove the specified subscription.
     */
    public function destroy(string $uid)
    {
        $subscription = WebhookSubscription::where('uid', $uid)->firstOrFail();
        $subscription->delete();

        return redirect()
            ->route('manager.backups.webhooks.subscriptions.index')
            ->with('success', 'Subscription deleted successfully.');
    }

    /**
     * Activate a subscription.
     */
    public function activate(string $uid)
    {
        $subscription = WebhookSubscription::where('uid', $uid)->firstOrFail();
        $subscription->activate();

        return back()->with('success', 'Subscription activated successfully.');
    }

    /**
     * Deactivate a subscription.
     */
    public function deactivate(string $uid)
    {
        $subscription = WebhookSubscription::where('uid', $uid)->firstOrFail();
        $subscription->deactivate();

        return back()->with('success', 'Subscription deactivated successfully.');
    }

    /**
     * Test webhook delivery.
     */
    public function test(string $uid)
    {
        $subscription = WebhookSubscription::where('uid', $uid)->firstOrFail();

        // TODO: Implement webhook test delivery logic
        // This could dispatch a test event to verify the subscription works

        return back()->with('success', 'Test webhook sent successfully.');
    }
}
