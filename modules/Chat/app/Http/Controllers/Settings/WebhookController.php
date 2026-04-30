<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Webhook;

class WebhookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $baseQuery = auth()->user()->account->webhooks();

        $accountCount = (clone $baseQuery)->where('webhook_type', Webhook::TYPE_ACCOUNT)->count();
        $inboxCount = (clone $baseQuery)->where('webhook_type', Webhook::TYPE_INBOX)->count();

        $webhooks = $baseQuery
            ->with('inbox')
            ->when($request->get('search'), function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(20);

        return view('Chat::settings.webhooks.index', compact('webhooks', 'accountCount', 'inboxCount'));
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

        return view('Chat::settings.webhooks.create', compact('inboxes', 'webhookTypes'));
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
            'inbox_id' => 'nullable|exists:chat_inboxes,id',
            'subscriptions' => 'required|array',
        ]);

        auth()->user()->account->webhooks()->create($validated);

        return redirect()->route('settings.chat.webhooks.index')
            ->with('success', 'Webhook creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Webhook $webhook)
    {
        $this->authorize('view', $webhook);

        $webhook->load('inbox');

        return view('Chat::settings.webhooks.show', compact('webhook'));
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

        return view('Chat::settings.webhooks.edit', compact('webhook', 'inboxes', 'webhookTypes'));
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
            'inbox_id' => 'nullable|exists:chat_inboxes,id',
            'subscriptions' => 'required|array',
        ]);

        $webhook->update($validated);

        return redirect()->route('settings.chat.webhooks.index')
            ->with('success', 'Webhook actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Webhook $webhook)
    {
        $this->authorize('delete', $webhook);

        $webhook->delete();

        return redirect()->route('settings.chat.webhooks.index')
            ->with('success', 'Webhook deleted successfully.');
    }
}
