<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Conversation;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Conversations\ConversationView;

class ConversationsChatController extends Controller
{
    /**
     * Display a listing of conversation
     */
    public function index(Request $request)
    {
        $this->authorize('manager.helpdesk.conversation.index');

        $userId = auth()->id();

        // Get all views available for the current user
        $views = ConversationView::forUser($userId)
            ->ordered()
            ->get();

        // Get current view - use viewId from request or default view
        $currentView = null;
        if ($request->has('viewId')) {
            $currentView = $views->firstWhere('id', $request->viewId);
        }

        // If no view selected or view not found, use default or first view
        if (! $currentView) {
            $currentView = $views->firstWhere('is_default', true) ?? $views->first();
        }

        $query = Conversation::query()
            ->with(['customer', 'status', 'assignee'])
            ->latest();

        // Apply view filters if a view is selected
        if ($currentView && ! empty($currentView->filters)) {
            $this->applyViewFilters($query, $currentView->filters);
        }

        // Apply additional URL filters (these override view filters)
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status_id', $request->status);
        }

        if ($request->has('assignee') && $request->assignee !== 'all') {
            if ($request->assignee === 'mine') {
                $query->where('assignee_id', $userId);
            } elseif ($request->assignee === 'unassigned') {
                $query->whereNull('assignee_id');
            } else {
                $query->where('assignee_id', $request->assignee);
            }
        }

        // Filter by group (shows conversation assigned to any user in the group)
        if ($request->has('group') && $request->group !== 'all') {
            $group = Group::find($request->group);
            if ($group) {
                $userIds = $group->users()->pluck('users.id');
                $query->whereIn('assignee_id', $userIds);
            }
        }

        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->has('archived')) {
            $query->where('is_archived', (bool) $request->archived);
        } else {
            $query->where('is_archived', false);
        }

        $conversations = $query->paginate(50)->appends($request->query());
        $statuses = ConversationStatus::active()->ordered()->get();
        $groups = Group::orderBy('name')->get();

        return view('theme.views.helpdesk.conversation.index', [
            'conversation' => $conversations,
            'statuses' => $statuses,
            'groups' => $groups,
            'views' => $views,
            'currentView' => $currentView,
            'filters' => $request->only(['status', 'assignee', 'group', 'priority', 'search', 'archived']),
        ]);
    }

    /**
     * Apply filters from a saved view
     */
    protected function applyViewFilters($query, array $filters)
    {
        foreach ($filters as $key => $value) {
            if (empty($value) || $value === 'all') {
                continue;
            }

            match ($key) {
                'status_id' => $query->where('status_id', $value),
                'assignee' => $value === 'mine'
                    ? $query->where('assignee_id', auth()->id())
                    : ($value === 'unassigned'
                        ? $query->whereNull('assignee_id')
                        : $query->where('assignee_id', $value)),
                'group' => (function () use ($query, $value) {
                    $group = Group::find($value);
                    if ($group) {
                        $userIds = $group->users()->pluck('users.id');
                        $query->whereIn('assignee_id', $userIds);
                    }
                })(),
                'priority' => $query->where('priority', $value),
                'is_open' => $query->whereHas('status', fn ($q) => $q->where('is_open', (bool) $value)),
                'is_archived' => $query->where('is_archived', (bool) $value),
                default => null,
            };
        }
    }

    /**
     * Show the form for creating a new conversation
     */
    public function create(Request $request)
    {
        $this->authorize('manager.helpdesk.conversation.create');

        $customer = null;
        if ($request->has('customer')) {
            $customer = Customer::findOrFail($request->customer);
        }

        $statuses = ConversationStatus::open()->ordered()->get();

        return view('theme.views.helpdesk.conversation.create', [
            'customer' => $customer,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Store a newly created conversation
     */
    public function store(Request $request)
    {
        $this->authorize('manager.helpdesk.conversation.create');

        $validated = $request->validate([
            'customer_id' => 'required|exists:helpdesk_customers,id',
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:low,normal,high,urgent',
            'status_id' => 'required|exists:helpdesk_conversation_statuses,id',
        ]);

        $conversation = Conversation::create($validated);

        return redirect()->route('manager.helpdesk.conversation.show', $conversation)
            ->with('success', 'Conversación creada exitosamente');
    }

    /**
     * Display the specified conversation
     */
    public function show(Conversation $conversation, Request $request)
    {
        $this->authorize('manager.helpdesk.conversation.show');

        $conversation->load(['customer', 'status', 'assignee', 'items', 'conversationTags']);

        $statuses = ConversationStatus::orderBy('order')->get();

        // Get available tags for the modal
        $availableTags = ConversationTag::active()->orderBy('name')->get();

        // Get views for sidebar (same as index)
        $views = ConversationView::query()
            ->forUser(Auth::id())
            ->ordered()
            ->get();

        // Get current view if specified
        $currentView = null;
        if ($request->filled('viewId')) {
            $currentView = ConversationView::find($request->viewId);
        }

        // Get groups for sidebar
        $groups = Group::with('users')->get();

        // Get conversation list (same as index)
        $conversationsQuery = Conversation::query()
            ->with(['customer', 'status', 'assignee']);

        // Apply view filters if specified
        if ($currentView) {
            $filters = $currentView->filters ?? [];
            // Apply filters logic here (simplified for now)
        }

        // Apply group filter if specified
        if ($request->filled('group')) {
            $conversationsQuery->whereHas('assignee.groups', function ($q) use ($request) {
                $q->where('id', $request->group);
            });
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $conversationsQuery->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        // Get paginated conversation
        $conversations = $conversationsQuery->latest('updated_at')->paginate(20);

        return view('theme.views.helpdesk.conversation.show', [
            'conversation' => $conversation,
            'statuses' => $statuses,
            'availableTags' => $availableTags,
            'views' => $views,
            'currentView' => $currentView,
            'groups' => $groups,
            'conversation' => $conversations,
        ]);
    }

    /**
     * Show the form for editing the conversation
     */
    public function edit(Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversation.update');

        $conversation->load(['customer', 'status', 'assignee']);
        $statuses = ConversationStatus::orderBy('order')->get();

        return view('theme.views.helpdesk.conversation.edit', [
            'conversation' => $conversation,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Update the specified conversation
     */
    public function update(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversation.update');

        // Handle AJAX requests (priority, assignee, tags)
        if ($request->ajax() || $request->wantsJson()) {
            // Handle tag actions
            if ($request->has('action')) {
                if ($request->action === 'add_tag') {
                    $request->validate([
                        'tag_id' => 'required|exists:helpdesk.helpdesk_conversation_tags,id',
                    ]);

                    // Attach tag if not already attached
                    if (! $conversation->conversationTags()->where('tag_id', $request->tag_id)->exists()) {
                        $conversation->conversationTags()->attach($request->tag_id);
                    }

                    // Get the tag to return its data
                    $tag = ConversationTag::find($request->tag_id);

                    return response()->json([
                        'success' => true,
                        'message' => 'Etiqueta agregada',
                        'tag' => $tag,
                    ]);
                }

                if ($request->action === 'remove_tag') {
                    $request->validate([
                        'tag_id' => 'required|exists:helpdesk.helpdesk_conversation_tags,id',
                    ]);

                    $conversation->conversationTags()->detach($request->tag_id);

                    return response()->json(['success' => true, 'message' => 'Etiqueta eliminada']);
                }
            }

            // Handle priority update
            if ($request->has('priority')) {
                $request->validate([
                    'priority' => 'required|in:low,normal,high,urgent',
                ]);

                $conversation->update(['priority' => $request->priority]);

                return response()->json(['success' => true, 'message' => 'Prioridad actualizada']);
            }

            // Handle assignee update
            if ($request->has('assignee_id')) {
                // Validate assignee exists in the default database (not helpdesk)
                if ($request->assignee_id) {
                    $user = \App\Models\User::find($request->assignee_id);
                    if (! $user) {
                        return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
                    }
                }

                $data = ['assignee_id' => $request->assignee_id];
                if ($request->assignee_id) {
                    $data['assigned_at'] = now();
                }
                $conversation->update($data);

                return response()->json(['success' => true, 'message' => 'Asignación actualizada']);
            }

            return response()->json(['success' => true]);
        }

        // Handle regular form submissions
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'status_id' => 'required|exists:helpdesk_conversation_statuses,id',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,normal,high,urgent',
            'is_archived' => 'boolean',
        ]);

        // If assigning to someone new, update assigned_at
        if (isset($validated['assignee_id']) && $validated['assignee_id'] && $validated['assignee_id'] !== $conversation->assignee_id) {
            $validated['assigned_at'] = now();
        }

        $conversation->update($validated);

        return redirect()->route('manager.helpdesk.conversation.show', $conversation)
            ->with('success', 'Conversación actualizada');
    }

    /**
     * Remove the specified conversation (soft delete)
     */
    public function destroy(Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversation.delete');

        $conversation->delete();

        return redirect()->route('manager.helpdesk.conversation.index')
            ->with('success', 'Conversación eliminada');
    }

    /**
     * Restore a soft-deleted conversation
     */
    public function restore($id)
    {
        $conversation = Conversation::onlyTrashed()->findOrFail($id);
        $this->authorize('manager.helpdesk.conversation.delete');

        $conversation->restore();

        return redirect()->route('manager.helpdesk.conversation.index')
            ->with('success', 'Conversación restaurada');
    }

    /**
     * Permanently delete a conversation
     */
    public function forceDelete($id)
    {
        $conversation = Conversation::withTrashed()->findOrFail($id);
        $this->authorize('manager.helpdesk.conversation.delete');

        $conversation->forceDelete();

        return redirect()->route('manager.helpdesk.conversation.index')
            ->with('success', 'Conversación eliminada permanentemente');
    }

    /**
     * Close a conversation
     */
    public function close(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversation.update');

        $conversation->close();

        return redirect()->back()
            ->with('success', 'Conversación cerrada');
    }

    /**
     * Reopen a conversation
     */
    public function reopen(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversation.update');

        $conversation->reopen();

        return redirect()->back()
            ->with('success', 'Conversación reabierta');
    }

    /**
     * Archive a conversation
     */
    public function archive(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversation.update');

        $conversation->archive();

        return redirect()->back()
            ->with('success', 'Conversación archivada');
    }

    /**
     * Unarchive a conversation
     */
    public function unarchive(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversation.update');

        $conversation->unarchive();

        return redirect()->back()
            ->with('success', 'Conversación desarchivada');
    }

    /**
     * Store a new message in a conversation
     */
    public function storeMessage(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversation.update');

        $validated = $request->validate([
            'body' => 'required|string',
            'is_internal' => 'nullable|boolean',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
            'action' => 'nullable|in:send,send_and_close',
        ]);

        // Handle attachments
        $attachmentUrls = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('helpdesk/attachments', 'public');
                $attachmentUrls[] = [
                    'name' => $file->getClientOriginalName(),
                    'url' => asset('storage/'.$path),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        // Create the message
        $item = $conversation->items()->create([
            'user_id' => auth()->id(),
            'type' => 'message',
            'body' => $validated['body'],
            'html_body' => nl2br(e($validated['body'])),
            'is_internal' => $request->boolean('is_internal'),
            'attachment_urls' => ! empty($attachmentUrls) ? $attachmentUrls : null,
        ]);

        // Update conversation timestamps
        $conversation->update([
            'last_message_at' => now(),
        ]);

        // Set first response time if this is the first agent response
        if (! $conversation->first_response_at) {
            $conversation->update([
                'first_response_at' => now(),
            ]);
        }

        // Close conversation if requested
        if ($request->input('action') === 'send_and_close') {
            $conversation->close();
            $successMessage = 'Mensaje enviado y conversación cerrada';
        } else {
            $successMessage = 'Mensaje enviado correctamente';
        }

        return redirect()->route('manager.helpdesk.conversation.show', $conversation)
            ->with('success', $successMessage);
    }
}
