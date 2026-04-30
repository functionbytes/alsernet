<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Macro;
use Modules\Chat\Services\Macros\MacroExecutor;

class MacroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $macros = auth()->user()->account->macros()
            ->with(['createdBy', 'updatedBy'])
            ->orderBy('visibility')
            ->orderBy('name')
            ->paginate(20);

        return view('Chat::settings.macros.index', compact('macros'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $account = auth()->user()->account;
        $visibilityOptions = [
            'personal' => Macro::VISIBILITY_PERSONAL,
            'global' => Macro::VISIBILITY_GLOBAL,
            'team' => Macro::VISIBILITY_TEAM,
        ];
        $users = $account->users()->orderBy('firstname')->get();
        $teams = $account->teams;
        $labels = $account->labels;

        return view('Chat::settings.macros.create', compact('visibilityOptions', 'users', 'teams', 'labels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'required|integer|min:0|max:2',
            'actions' => 'required|json',
            'conditions' => 'nullable|json',
            'enabled' => 'nullable|boolean',
        ]);

        // Parse JSON fields
        $validated['actions'] = json_decode($validated['actions'], true);
        $validated['conditions'] = $validated['conditions'] ? json_decode($validated['conditions'], true) : null;
        $validated['enabled'] = $request->has('enabled');

        auth()->user()->account->macros()->create([
            ...$validated,
            'created_by_id' => auth()->id(),
            'updated_by_id' => auth()->id(),
        ]);

        return redirect()->route('settings.chat.macros.index')
            ->with('success', 'Macro created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Macro $macro)
    {
        $this->authorize('view', $macro);

        $macro->load(['createdBy', 'updatedBy']);

        return view('Chat::settings.macros.show', compact('macro'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Macro $macro)
    {
        $this->authorize('update', $macro);

        $account = auth()->user()->account;
        $visibilityOptions = [
            'personal' => Macro::VISIBILITY_PERSONAL,
            'global' => Macro::VISIBILITY_GLOBAL,
            'team' => Macro::VISIBILITY_TEAM,
        ];
        $users = $account->users()->orderBy('firstname')->get();
        $teams = $account->teams;
        $labels = $account->labels;

        return view('Chat::settings.macros.edit', compact('macro', 'visibilityOptions', 'users', 'teams', 'labels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Macro $macro)
    {
        $this->authorize('update', $macro);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'required|integer|min:0|max:2',
            'actions' => 'required|json',
            'conditions' => 'nullable|json',
            'enabled' => 'nullable|boolean',
        ]);

        // Parse JSON fields
        $validated['actions'] = json_decode($validated['actions'], true);
        $validated['conditions'] = $validated['conditions'] ? json_decode($validated['conditions'], true) : null;
        $validated['enabled'] = $request->has('enabled');

        $macro->update([
            ...$validated,
            'updated_by_id' => auth()->id(),
        ]);

        return redirect()->route('settings.chat.macros.index')
            ->with('success', 'Macro updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Macro $macro)
    {
        $this->authorize('delete', $macro);

        $macro->delete();

        return redirect()->route('settings.chat.macros.index')
            ->with('success', 'Macro deleted successfully.');
    }

    /**
     * Execute a macro on a conversation
     */
    public function execute(Request $request, Macro $macro, MacroExecutor $executor)
    {
        $this->authorize('view', $macro);

        $validated = $request->validate([
            'conversation_id' => 'required|exists:chat_conversations,id',
        ]);

        $conversation = Conversation::findOrFail($validated['conversation_id']);

        // Execute the macro
        $results = $executor->execute($macro, $conversation);

        // Check if all actions succeeded
        $allSuccessful = collect($results)->every(fn ($r) => $r['success'] ?? false);

        if ($allSuccessful) {
            return redirect()->back()
                ->with('success', "Macro '{$macro->name}' executed successfully.");
        } else {
            $errors = collect($results)->filter(fn ($r) => ! ($r['success'] ?? false))->pluck('error');

            return redirect()->back()
                ->with('error', 'Some macro actions failed: '.$errors->implode(', '));
        }
    }
}
