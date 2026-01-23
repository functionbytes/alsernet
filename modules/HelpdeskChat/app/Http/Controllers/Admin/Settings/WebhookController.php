<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Webhook;

class WebhookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $webhooks = auth()->user()->account->webhooks()
            ->with('inbox')
            ->orderBy('name')
            ->paginate(20);

        return view('helpdeskchat::admin.settings.webhooks.index', compact('webhooks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $inboxes = auth()->user()->account->inboxes;
        $webhookTypes = [
            'account' => Webhook::TYPE_ACCOUNT,
            'inbox' => Webhook::TYPE_INBOX,
        ];

        return view('helpdeskchat::admin.settings.webhooks.create', compact('inboxes', 'webhookTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'webhook_type' => 'required|integer|min:0|max:1',
            'inbox_id' => 'nullable|exists:inboxes,id',
            'subscriptions' => 'required|array',
        ]);

        auth()->user()->account->webhooks()->create($validated);

        return redirect()->route('admin.helpdesk.webhooks.index')
            ->with('success', 'Webhook created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Webhook $webhook)
    {
        $this->authorize('view', $webhook);

        $webhook->load('inbox');

        return view('helpdeskchat::admin.settings.webhooks.show', compact('webhook'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Webhook $webhook)
    {
        $this->authorize('update', $webhook);

        $inboxes = auth()->user()->account->inboxes;
        $webhookTypes = [
            'account' => Webhook::TYPE_ACCOUNT,
            'inbox' => Webhook::TYPE_INBOX,
        ];

        return view('helpdeskchat::admin.settings.webhooks.edit', compact('webhook', 'inboxes', 'webhookTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Webhook $webhook)
    {
        $this->authorize('update', $webhook);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'webhook_type' => 'required|integer|min:0|max:1',
            'inbox_id' => 'nullable|exists:inboxes,id',
            'subscriptions' => 'required|array',
        ]);

        $webhook->update($validated);

        return redirect()->route('admin.helpdesk.webhooks.index')
            ->with('success', 'Webhook updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Webhook $webhook)
    {
        $this->authorize('delete', $webhook);

        $webhook->delete();

        return redirect()->route('admin.helpdesk.webhooks.index')
            ->with('success', 'Webhook deleted successfully.');
    }
}
