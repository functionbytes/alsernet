<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Helpdesk\Filters\ConversationFilter;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\ConversationView;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Services\ConversationTagService;
use Modules\Helpdesk\Services\OutboundMessageService;

class ConversationsController extends Controller
{
    public function __construct(private ConversationTagService $tagService) {}

    /**
     * Display a listing of conversations
     */
    public function index(Request $request)
    {
        $this->authorize('manager.helpdesk.conversations.index');

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
    public function create(Request $request)
    {
        $this->authorize('manager.helpdesk.conversations.create');

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
    public function store(Request $request)
    {
        $this->authorize('manager.helpdesk.conversations.create');

        $validated = $request->validate([
            'customer_id' => 'required|exists:helpdesk_customers,id',
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:low,normal,high,urgent',
            'status_id' => 'required|exists:helpdesk_conversation_statuses,id',
        ]);

        $conversation = Conversation::create($validated);

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', __('helpdesk::helpdesk.messages.conversation_created'));
    }

    /**
     * Display the specified conversation
     */
    public function show(Conversation $conversation, Request $request)
    {
        $this->authorize('manager.helpdesk.conversations.show');

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
    public function edit(Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversations.update');

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
    public function update(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversations.update');

        // Handle AJAX requests (priority, assignee, tags)
        if ($request->ajax() || $request->wantsJson()) {
            // Handle tag actions
            if ($request->has('action')) {
                if ($request->action === 'add_tag') {
                    $request->validate([
                        'tag_id' => 'required|exists:helpdesk.helpdesk_conversation_tags,id',
                    ]);

                    $tag = $this->tagService->addTag($conversation, (int) $request->tag_id);

                    return response()->json([
                        'success' => true,
                        'message' => __('helpdesk::helpdesk.messages.tag_added'),
                        'tag' => $tag,
                    ]);
                }

                if ($request->action === 'remove_tag') {
                    $request->validate([
                        'tag_id' => 'required|exists:helpdesk.helpdesk_conversation_tags,id',
                    ]);

                    $this->tagService->removeTag($conversation, (int) $request->tag_id);

                    return response()->json(['success' => true, 'message' => __('helpdesk::helpdesk.messages.tag_removed')]);
                }
            }

            // Handle priority update
            if ($request->has('priority')) {
                $request->validate([
                    'priority' => 'required|in:low,normal,high,urgent',
                ]);

                $conversation->update(['priority' => $request->priority]);

                return response()->json(['success' => true, 'message' => __('helpdesk::helpdesk.messages.priority_updated')]);
            }

            // Handle assignee update
            if ($request->has('assignee_id')) {
                // Validate assignee exists in the default database (not helpdesk)
                if ($request->assignee_id) {
                    $user = User::find($request->assignee_id);
                    if (! $user) {
                        return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
                    }
                }

                $data = ['assignee_id' => $request->assignee_id];
                if ($request->assignee_id) {
                    $data['assigned_at'] = now();
                }
                $conversation->update($data);

                return response()->json(['success' => true, 'message' => __('helpdesk::helpdesk.messages.assignment_updated')]);
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

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', __('helpdesk::helpdesk.messages.conversation_updated'));
    }

    /**
     * Remove the specified conversation (soft delete)
     */
    public function destroy(Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversations.delete');

        $conversation->delete();

        return redirect()->route('manager.helpdesk.conversations.index')
            ->with('success', __('helpdesk::helpdesk.messages.conversation_deleted'));
    }

    /**
     * Restore a soft-deleted conversation
     */
    public function restore($id)
    {
        $conversation = Conversation::onlyTrashed()->findOrFail($id);
        $this->authorize('manager.helpdesk.conversations.delete');

        $conversation->restore();

        return redirect()->route('manager.helpdesk.conversations.index')
            ->with('success', __('helpdesk::helpdesk.messages.conversation_restored'));
    }

    /**
     * Permanently delete a conversation
     */
    public function forceDelete($id)
    {
        $conversation = Conversation::withTrashed()->findOrFail($id);
        $this->authorize('manager.helpdesk.conversations.delete');

        $conversation->forceDelete();

        return redirect()->route('manager.helpdesk.conversations.index')
            ->with('success', __('helpdesk::helpdesk.messages.conversation_force_deleted'));
    }

    /**
     * Close a conversation
     */
    public function close(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversations.update');

        $conversation->close();

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_closed'));
    }

    /**
     * Reopen a conversation
     */
    public function reopen(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversations.update');

        $conversation->reopen();

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_reopened'));
    }

    /**
     * Archive a conversation
     */
    public function archive(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversations.update');

        $conversation->archive();

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_archived'));
    }

    /**
     * Unarchive a conversation
     */
    public function unarchive(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversations.update');

        $conversation->unarchive();

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_unarchived'));
    }

    /**
     * Store a new message in a conversation
     */
    public function storeMessage(Request $request, Conversation $conversation)
    {
        $this->authorize('manager.helpdesk.conversations.update');

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

        // Send outbound reply via social channel if applicable
        if (! $request->boolean('is_internal')) {
            /** @var OutboundMessageService $outbound */
            $outbound = app(OutboundMessageService::class);
            $externalMessageId = $outbound->sendReply($conversation, strip_tags($validated['body']));

            if ($externalMessageId) {
                $item->update(['metadata' => array_merge($item->metadata ?? [], [
                    'outbound_message_id' => $externalMessageId,
                    'sent_via' => $conversation->channel,
                ])]);
            }
        }

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
            $successMessage = __('helpdesk::helpdesk.messages.conversation_message_sent_and_closed');
        } else {
            $successMessage = __('helpdesk::helpdesk.messages.conversation_message_sent');
        }

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', $successMessage);
    }
}
