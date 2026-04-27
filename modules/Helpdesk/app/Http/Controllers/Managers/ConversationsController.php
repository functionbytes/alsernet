<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Helpdesk\Filters\ConversationFilter;
use Modules\Helpdesk\Http\Requests\ConversationAjaxActionRequest;
use Modules\Helpdesk\Http\Requests\StoreConversationMessageRequest;
use Modules\Helpdesk\Http\Requests\StoreConversationRequest;
use Modules\Helpdesk\Http\Requests\UpdateConversationRequest;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\ConversationView;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Services\ConversationMessageService;
use Modules\Helpdesk\Services\ConversationTagService;

class ConversationsController extends Controller
{
    public function __construct(private ConversationTagService $tagService)
    {
        $this->middleware('can:helpdesk.conversations.view')->only(['index', 'show']);
        $this->middleware('can:helpdesk.conversations.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.conversations.update')->only(['edit', 'update', 'close', 'reopen', 'archive', 'unarchive', 'storeMessage']);
        $this->middleware('can:helpdesk.conversations.delete')->only(['destroy', 'restore', 'forceDelete']);
    }

    /**
     * Display a listing of conversations
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Conversation::class);

        $userId = auth()->id();

        $views = ConversationView::forUser($userId)->ordered()->get();

        $currentView = null;
        if ($request->has('viewId')) {
            $currentView = $views->firstWhere('id', $request->viewId);
        }

        if (! $currentView) {
            $currentView = $views->firstWhere('is_default', true) ?? $views->first();
        }

        $filter = new ConversationFilter($request);

        $query = Conversation::query()
            ->with(['customer', 'status', 'assignee'])
            ->latest();

        if ($currentView && ! empty($currentView->filters)) {
            $filter->applyViewFilters($query, $currentView->filters);
        }

        $filter->apply($query);

        $conversations = $query->paginate(50)->appends($request->query());
        $statuses = ConversationStatus::active()->ordered()->get();
        $groups = Group::orderBy('name')->get();

        return view('helpdesk::managers.helpdesk.conversations.index', [
            'conversations' => $conversations,
            'statuses' => $statuses,
            'groups' => $groups,
            'views' => $views,
            'currentView' => $currentView,
            'filters' => $request->only(['status', 'assignee', 'group', 'priority', 'search', 'archived', 'channel']),
        ]);
    }

    /**
     * Show the form for creating a new conversation
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Conversation::class);

        $customer = null;
        if ($request->has('customer')) {
            $customer = Customer::findOrFail($request->customer);
        }

        $statuses = ConversationStatus::open()->ordered()->get();

        return view('helpdesk::managers.helpdesk.conversations.create', [
            'customer' => $customer,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Store a newly created conversation
     */
    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $conversation = Conversation::create([
            'customer_id' => $validated['customer_id'],
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
        ]);
        $conversation->status_id = $validated['status_id'];
        $conversation->save();

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', __('helpdesk::helpdesk.messages.conversation_created'));
    }

    /**
     * Display the specified conversation
     */
    public function show(Conversation $conversation, Request $request): View
    {
        $this->authorize('view', $conversation);

        $conversation->load(['customer', 'status', 'assignee', 'items.user', 'items.author', 'conversationTags']);

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

        // Get conversations list (same as index)
        $conversationsQuery = Conversation::query()
            ->with(['customer', 'status', 'assignee'])
            ->withCount('messages');

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

        // Get paginated conversations
        $conversations = $conversationsQuery->latest('updated_at')->paginate(20);

        return view('helpdesk::managers.helpdesk.conversations.show', [
            'conversation' => $conversation,
            'statuses' => $statuses,
            'availableTags' => $availableTags,
            'views' => $views,
            'currentView' => $currentView,
            'groups' => $groups,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Show the form for editing the conversation
     */
    public function edit(Conversation $conversation): View
    {
        $this->authorize('update', $conversation);

        $conversation->load(['customer', 'status', 'assignee']);
        $statuses = ConversationStatus::orderBy('order')->get();

        return view('helpdesk::managers.helpdesk.conversations.edit', [
            'conversation' => $conversation,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Update the specified conversation
     */
    public function update(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $conversation);

        // AJAX path: lightweight partial updates (tag/priority/assignee) — handled by ConversationAjaxActionRequest
        if ($request->ajax() || $request->wantsJson()) {
            return $this->handleAjaxUpdate($conversation);
        }

        // Regular form submission — validate via UpdateConversationRequest
        $validated = app(UpdateConversationRequest::class)->validated();

        $conversation->update([
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
        ]);

        $conversation->status_id = $validated['status_id'];

        if (isset($validated['assignee_id'])) {
            if ($validated['assignee_id'] && $validated['assignee_id'] !== $conversation->assignee_id) {
                $conversation->assignTo($validated['assignee_id']);
            } elseif (! $validated['assignee_id']) {
                $conversation->assignee_id = null;
                $conversation->assigned_at = null;
            }
        }

        if (isset($validated['is_archived'])) {
            $conversation->is_archived = $validated['is_archived'];
        }

        $conversation->save();

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', __('helpdesk::helpdesk.messages.conversation_updated'));
    }

    /**
     * Handle AJAX partial updates (tag toggle, priority, assignee).
     */
    private function handleAjaxUpdate(Conversation $conversation): JsonResponse
    {
        $request = app(ConversationAjaxActionRequest::class);
        $action = $request->input('action');

        if ($action === 'add_tag') {
            $tag = $this->tagService->addTag($conversation, (int) $request->validated()['tag_id']);

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.tag_added'),
                'tag' => $tag,
            ]);
        }

        if ($action === 'remove_tag') {
            $this->tagService->removeTag($conversation, (int) $request->validated()['tag_id']);

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.tag_removed'),
            ]);
        }

        if ($request->has('priority')) {
            $conversation->update(['priority' => $request->validated()['priority']]);

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.priority_updated'),
            ]);
        }

        if ($request->has('assignee_id')) {
            $assigneeId = $request->validated()['assignee_id'] ?? null;

            if ($assigneeId) {
                $conversation->assignTo($assigneeId);
            } else {
                $conversation->assignee_id = null;
                $conversation->assigned_at = null;
                $conversation->save();
            }

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.assignment_updated'),
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified conversation (soft delete)
     */
    public function destroy(Conversation $conversation): RedirectResponse
    {
        $this->authorize('delete', $conversation);

        $conversation->delete();

        return redirect()->route('manager.helpdesk.conversations.index')
            ->with('success', __('helpdesk::helpdesk.messages.conversation_deleted'));
    }

    /**
     * Restore a soft-deleted conversation
     */
    public function restore($id): RedirectResponse
    {
        $conversation = Conversation::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $conversation);

        $conversation->restore();

        return redirect()->route('manager.helpdesk.conversations.index')
            ->with('success', __('helpdesk::helpdesk.messages.conversation_restored'));
    }

    /**
     * Permanently delete a conversation
     */
    public function forceDelete($id): RedirectResponse
    {
        $conversation = Conversation::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $conversation);

        $conversation->forceDelete();

        return redirect()->route('manager.helpdesk.conversations.index')
            ->with('success', __('helpdesk::helpdesk.messages.conversation_force_deleted'));
    }

    /**
     * Close a conversation
     */
    public function close(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('update', $conversation);

        $conversation->close();

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_closed'));
    }

    /**
     * Reopen a conversation
     */
    public function reopen(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('update', $conversation);

        $conversation->reopen();

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_reopened'));
    }

    /**
     * Archive a conversation
     */
    public function archive(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('update', $conversation);

        $conversation->archive();

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_archived'));
    }

    /**
     * Unarchive a conversation
     */
    public function unarchive(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('update', $conversation);

        $conversation->unarchive();

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_unarchived'));
    }

    /**
     * Store a new message in a conversation
     */
    public function storeMessage(StoreConversationMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validated();

        [, $successMessage] = app(ConversationMessageService::class)->store($conversation, [
            'body' => $validated['body'],
            'is_internal' => $request->boolean('is_internal'),
            'attachments' => $request->file('attachments', []),
            'action' => $request->input('action'),
        ]);

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', $successMessage);
    }
}
