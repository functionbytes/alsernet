<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Integrations\Integration;

class IntegrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $baseQuery = auth()->user()->account->integrations();

        $activeCount = (clone $baseQuery)->where('status', Integration::STATUS_ENABLED)->count();
        $inactiveCount = (clone $baseQuery)->where('status', '!=', Integration::STATUS_ENABLED)->count();

        $integrations = $baseQuery
            ->with(['inbox'])
            ->when($request->get('search'), function ($query, $search) {
                return $query->where('app_id', 'like', "%{$search}%");
            })
            ->orderBy('status', 'desc')
            ->orderBy('app_id')
            ->paginate(20);

        return view('Chat::settings.integrations.index', compact('integrations', 'activeCount', 'inactiveCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $inboxes = auth()->user()->account->inboxes;
        $statusOptions = [
            'disabled' => Integration::STATUS_DISABLED,
            'enabled' => Integration::STATUS_ENABLED,
        ];

        return view('Chat::settings.integrations.create', compact('inboxes', 'statusOptions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'app_id' => 'required|string|max:255',
            'status' => 'required|integer|min:0|max:1',
            'hook_type' => 'required|integer',
            'inbox_id' => 'nullable|exists:chat_inboxes,id',
            'reference_id' => 'nullable|string|max:255',
            'access_token' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
        ]);

        auth()->user()->account->integrations()->create($validated);

        return redirect()->route('settings.chat.integrations.index')
            ->with('success', 'Integration hook created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Integration $integration)
    {
        $this->authorize('view', $integration);

        $integration->load('inbox');

        return view('Chat::settings.integrations.show', compact('integration'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Integration $integration)
    {
        $this->authorize('update', $integration);

        $inboxes = auth()->user()->account->inboxes;
        $statusOptions = [
            'disabled' => Integration::STATUS_DISABLED,
            'enabled' => Integration::STATUS_ENABLED,
        ];

        return view('Chat::settings.integrations.edit', compact('integration', 'inboxes', 'statusOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Integration $integration)
    {
        $this->authorize('update', $integration);

        $validated = $request->validate([
            'app_id' => 'required|string|max:255',
            'status' => 'required|integer|min:0|max:1',
            'hook_type' => 'required|integer',
            'inbox_id' => 'nullable|exists:chat_inboxes,id',
            'reference_id' => 'nullable|string|max:255',
            'access_token' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
        ]);

        $integration->update($validated);

        return redirect()->route('settings.chat.integrations.index')
            ->with('success', 'Integrations hook updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Integration $integration)
    {
        $this->authorize('delete', $integration);

        $integration->delete();

        return redirect()->route('settings.chat.integrations.index')
            ->with('success', 'Integrations hook deleted successfully.');
    }

    /**
     * Toggle status
     */
    public function toggleStatus(Integration $integration)
    {
        $this->authorize('update', $integration);

        $newStatus = $integration->status === Integration::STATUS_ENABLED
            ? Integration::STATUS_DISABLED
            : Integration::STATUS_ENABLED;

        $integration->update(['status' => $newStatus]);

        return redirect()->back()
            ->with('success', 'Integrations hook status updated.');
    }
}
