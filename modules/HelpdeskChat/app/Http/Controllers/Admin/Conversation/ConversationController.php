<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Conversation;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\HelpdeskChat\Events\Conversations\ConversationAssigned;
use Modules\HelpdeskChat\Events\Conversations\ConversationStatusChanged;
use Modules\HelpdeskChat\Events\ConversationUpdated;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Http\Requests\Conversations\StoreConversationRequest;
use Modules\HelpdeskChat\Http\Requests\Conversations\UpdateConversationRequest;
use Modules\HelpdeskChat\Jobs\SendCsatSurvey;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Contacts\ContactInbox;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Label;
use Modules\HelpdeskChat\Models\Teams\Team;

class ConversationController extends Controller
{
    /**
     * Display a listing of conversation.
     */
    public function index(Request $request)
    {
        $accountId = $request->user()->account_id;

        $query = Conversation::where('account_id', $accountId)
            ->with(['contact', 'inbox.channel', 'assignee', 'team', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }]);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by inbox
        if ($request->has('inbox_id')) {
            $query->where('inbox_id', $request->inbox_id);
        }

        // Filter by assignee
        if ($request->has('assignee_id')) {
            $query->where('assignee_id', $request->assignee_id);
        }

        // Filter by team
        if ($request->has('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // Filter by labels
        if ($request->has('labels') && ! empty($request->labels)) {
            $labels = is_array($request->labels) ? $request->labels : [$request->labels];
            $query->where(function ($q) use ($labels) {
                foreach ($labels as $label) {
                    $q->orWhere('cached_label_list', 'like', "%{$label}%");
                }
            });
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by last activity date
        if ($request->has('last_activity_from')) {
            $query->whereDate('last_activity_at', '>=', $request->last_activity_from);
        }

        if ($request->has('last_activity_to')) {
            $query->whereDate('last_activity_at', '<=', $request->last_activity_to);
        }

        // Text search across contact name, email, and message content
        if ($request->has('search') && ! empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('contact', function ($contactQuery) use ($searchTerm) {
                    $contactQuery->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('phone_number', 'like', "%{$searchTerm}%");
                })
                    ->orWhereHas('messages', function ($msgQuery) use ($searchTerm) {
                        $msgQuery->where('content', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Sort by
        $sortField = $request->get('sort_by', 'last_activity_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $conversations = $query->paginate(25)->withQueryString();

        // Get filter counts for UI
        $filterCounts = [
            'all' => Conversation::where('account_id', $accountId)->count(),
            'open' => Conversation::where('account_id', $accountId)->where('status', 'open')->count(),
            'resolved' => Conversation::where('account_id', $accountId)->where('status', 'resolved')->count(),
            'pending' => Conversation::where('account_id', $accountId)->where('status', 'pending')->count(),
            'mine' => Conversation::where('account_id', $accountId)->where('assignee_id', auth()->id())->count(),
            'unassigned' => Conversation::where('account_id', $accountId)->whereNull('assignee_id')->count(),
            'mentions' => Conversation::where('account_id', $accountId)
                ->whereHas('messages', function ($query) {
                    $query->where('content', 'like', '%@'.auth()->id().'%')
                        ->orWhere('content', 'like', '%@'.auth()->user()->name.'%');
                })
                ->count(),
            'unattended' => Conversation::where('account_id', $accountId)
                ->where('status', '!=', 'resolved')
                ->whereDoesntHave('messages', function ($query) {
                    $query->where('message_type', 'outgoing')
                        ->where('sender_type', 'App\Models\User');
                })
                ->count(),
        ];

        // Priority counts
        $priorityCounts = [
            'urgent' => Conversation::where('account_id', $accountId)->where('priority', 'urgent')->count(),
            'high' => Conversation::where('account_id', $accountId)->where('priority', 'high')->count(),
            'medium' => Conversation::where('account_id', $accountId)->where('priority', 'medium')->count(),
            'low' => Conversation::where('account_id', $accountId)->where('priority', 'low')->count(),
        ];

        // Get inboxes with channel and conversation counts for sidebar
        $inboxes = Inbox::where('account_id', $accountId)
            ->with('channel')
            ->get()
            ->map(function ($inbox) use ($accountId) {
                $inbox->conversations_count = Conversation::where('account_id', $accountId)
                    ->where('inbox_id', $inbox->id)
                    ->count();

                return $inbox;
            });

        // Get teams with conversation counts for sidebar
        $teams = Team::where('account_id', $accountId)
            ->get()
            ->map(function ($team) use ($accountId) {
                $team->conversations_count = Conversation::where('account_id', $accountId)
                    ->where('team_id', $team->id)
                    ->count();

                return $team;
            });

        // Get labels with conversation counts for sidebar
        $labels = Label::where('account_id', $accountId)
            ->get()
            ->map(function ($label) use ($accountId) {
                $label->conversations_count = Conversation::where('account_id', $accountId)
                    ->where('cached_label_list', 'like', '%'.$label->title.'%')
                    ->count();

                return $label;
            });

        // Create labels keyed by title for efficient lookup in the view
        $labelsByTitle = $labels->keyBy('title');

        return view('helpdeskchat::admin.conversation.index', compact('conversations', 'filterCounts', 'priorityCounts', 'inboxes', 'teams', 'labels', 'labelsByTitle'));
    }

    /**
     * Display the specified conversation.
     */
    public function show(Conversation $conversation)
    {
        $conversation->load([
            'contact',
            'inbox.channel',
            'assignee',
            'messages.sender',
            'slaTracking.slaPolicy',
            'slaPolicy',
        ]);

        // Previous conversations del mismo contacto
        $previousConversations = $conversation->contact->conversations()
            ->where('id', '!=', $conversation->id)
            ->with('inbox')
            ->latest('last_activity_at')
            ->limit(5)
            ->get();

        // Macros disponibles para el usuario
        $macros = auth()->user()->account->macros()
            ->where(function ($q) {
                $q->where('visibility', \Modules\HelpdeskChat\Models\Macro::VISIBILITY_GLOBAL)
                    ->orWhere('created_by_id', auth()->id());
            })
            ->orderBy('name')
            ->get();

        // Usuarios para asignar
        $users = \App\Models\User::where('account_id', auth()->user()->account_id)
            ->orderBy('name')
            ->get();

        // Labels mapeados por título para evitar N+1
        $labelsByTitle = auth()->user()->account->labels()->get()->keyBy('title');

        return view('helpdeskchat::admin.conversation.show', compact(
            'conversation',
            'previousConversations',
            'macros',
            'users',
            'labelsByTitle'
        ));
    }

    /**
     * Update the conversation status.
     */
    public function updateStatus(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,resolved,pending',
        ]);

        $oldStatus = $conversation->status;
        $conversation->update(['status' => $validated['status']]);

        // Dispatch CSAT survey when conversation is resolved
        if ($validated['status'] === 'resolved' && $oldStatus !== 'resolved') {
            SendCsatSurvey::dispatch($conversation);
        }

        // Broadcast conversation status changed
        broadcast(new ConversationStatusChanged(
            $conversation,
            $oldStatus,
            $validated['status']
        ));

        return back()->with('success', 'Conversation status updated!');
    }

    /**
     * Assign conversation to a user.
     */
    public function assign(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $conversation->update(['assignee_id' => $validated['assignee_id']]);

        // Broadcast conversation assigned
        $assignedAgent = $validated['assignee_id'] ? User::find($validated['assignee_id']) : null;
        broadcast(new ConversationAssigned(
            $conversation,
            $assignedAgent
        ));

        return back()->with('success', 'Conversation assigned successfully!');
    }

    /**
     * Update conversation team.
     */
    public function updateTeam(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $conversation->update(['team_id' => $validated['team_id']]);

        return back()->with('success', 'Team updated successfully!');
    }

    /**
     * Update conversation priority.
     */
    public function updatePriority(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $conversation->update(['priority' => $validated['priority']]);

        // Broadcast conversation update
        broadcast(new ConversationUpdated($conversation, 'priority_changed'));

        return back()->with('success', 'Priority updated successfully!');
    }

    /**
     * Add labels to conversation.
     */
    public function addLabels(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'labels' => 'required|array',
            'labels.*' => 'string|exists:labels,title',
        ]);

        $conversation->addLabels($validated['labels']);

        return back()->with('success', 'Labels added successfully!');
    }

    /**
     * Remove label from conversation.
     */
    public function removeLabel(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'label' => 'required|string',
        ]);

        $conversation->removeLabels([$validated['label']]);

        return back()->with('success', 'Label removed successfully!');
    }

    /**
     * Snooze conversation.
     */
    public function snooze(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'snoozed_until' => 'required|date|after:now',
        ]);

        $conversation->snooze(new \DateTime($validated['snoozed_until']));

        return back()->with('success', 'Conversation snoozed successfully!');
    }

    /**
     * Unsnooze conversation.
     */
    public function unsnooze(Conversation $conversation)
    {
        $conversation->unsnooze();

        return back()->with('success', 'Conversation unsnoozed!');
    }

    /**
     * Show conversation assigned to me.
     */
    public function mine(Request $request)
    {
        $accountId = $request->user()->account_id;

        $conversations = Conversation::where('account_id', $accountId)
            ->where('assignee_id', $request->user()->id)
            ->with(['contact', 'inbox.channel', 'assignee', 'team', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->latest('last_activity_at')
            ->paginate(25);

        $commonData = $this->getCommonIndexData($accountId);

        return view('helpdeskchat::admin.conversation.index', array_merge(
            compact('conversations'),
            $commonData,
            ['filter' => 'mine']
        ));
    }

    /**
     * Show unassigned conversation.
     */
    public function unassigned(Request $request)
    {
        $accountId = $request->user()->account_id;

        $conversations = Conversation::where('account_id', $accountId)
            ->whereNull('assignee_id')
            ->with(['contact', 'inbox.channel', 'assignee', 'team', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->latest('last_activity_at')
            ->paginate(25);

        $commonData = $this->getCommonIndexData($accountId);

        return view('helpdeskchat::admin.conversation.index', array_merge(
            compact('conversations'),
            $commonData,
            ['filter' => 'unassigned']
        ));
    }

    /**
     * Show conversation where I'm mentioned.
     */
    public function mentions(Request $request)
    {
        $accountId = $request->user()->account_id;
        $userId = $request->user()->id;

        // Buscar conversaciones donde el usuario fue mencionado en mensajes
        $conversations = Conversation::where('account_id', $accountId)
            ->whereHas('messages', function ($query) use ($userId) {
                $query->where('content', 'like', '%@'.$userId.'%')
                    ->orWhere('content', 'like', '%@'.auth()->user()->name.'%');
            })
            ->with(['contact', 'inbox.channel', 'assignee', 'team', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->latest('last_activity_at')
            ->paginate(25);

        $commonData = $this->getCommonIndexData($accountId);

        return view('helpdeskchat::admin.conversation.index', array_merge(
            compact('conversations'),
            $commonData,
            ['filter' => 'mentions']
        ));
    }

    /**
     * Show unattended conversation (no agent has replied yet).
     */
    public function unattended(Request $request)
    {
        $accountId = $request->user()->account_id;

        // Conversaciones donde no hay mensajes salientes de agentes
        $conversations = Conversation::where('account_id', $accountId)
            ->where('status', '!=', 'resolved')
            ->whereDoesntHave('messages', function ($query) {
                $query->where('message_type', 'outgoing')
                    ->where('sender_type', 'App\Models\User');
            })
            ->with(['contact', 'inbox.channel', 'assignee', 'team', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->latest('last_activity_at')
            ->paginate(25);

        $commonData = $this->getCommonIndexData($accountId);

        return view('helpdeskchat::admin.conversation.index', array_merge(
            compact('conversations'),
            $commonData,
            ['filter' => 'unattended']
        ));
    }

    /**
     * Show conversation by inbox/channel.
     */
    public function byInbox(Request $request, $inboxId)
    {
        $accountId = $request->user()->account_id;

        $inbox = Inbox::where('account_id', $accountId)
            ->findOrFail($inboxId);

        $conversations = Conversation::where('account_id', $accountId)
            ->where('inbox_id', $inboxId)
            ->with(['contact', 'inbox.channel', 'assignee', 'team', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->latest('last_activity_at')
            ->paginate(25);

        $commonData = $this->getCommonIndexData($accountId);

        return view('helpdeskchat::admin.conversation.index', array_merge(
            compact('conversations', 'inbox'),
            $commonData,
            ['filter' => 'inbox']
        ));
    }

    /**
     * Show conversation by team.
     */
    public function byTeam(Request $request, $teamId)
    {
        $accountId = $request->user()->account_id;

        $team = Team::where('account_id', $accountId)
            ->findOrFail($teamId);

        $conversations = Conversation::where('account_id', $accountId)
            ->where('team_id', $teamId)
            ->with(['contact', 'inbox.channel', 'assignee', 'team', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->latest('last_activity_at')
            ->paginate(25);

        $commonData = $this->getCommonIndexData($accountId);

        return view('helpdeskchat::admin.conversation.index', array_merge(
            compact('conversations', 'team'),
            $commonData,
            ['filter' => 'team']
        ));
    }

    /**
     * Show conversation by label.
     */
    public function byLabel(Request $request, $labelTitle)
    {
        $accountId = $request->user()->account_id;

        // Verificar que la etiqueta exista
        $label = Label::where('account_id', $accountId)
            ->where('title', $labelTitle)
            ->firstOrFail();

        $conversations = Conversation::where('account_id', $accountId)
            ->where('cached_label_list', 'like', '%'.$labelTitle.'%')
            ->with(['contact', 'inbox.channel', 'assignee', 'team', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->latest('last_activity_at')
            ->paginate(25);

        $commonData = $this->getCommonIndexData($accountId);

        return view('helpdeskchat::admin.conversation.index', array_merge(
            compact('conversations', 'label'),
            $commonData,
            ['filter' => 'label']
        ));
    }

    /**
     * Store a new conversation.
     */
    public function store(StoreConversationRequest $request)
    {
        $validated = $request->validated();

        $contact = Contact::findOrFail($validated['contact_id']);
        $inbox = Inbox::findOrFail($validated['inbox_id']);

        // Check if contact inbox exists
        $contactInbox = ContactInbox::firstOrCreate([
            'contact_id' => $contact->id,
            'inbox_id' => $inbox->id,
        ], [
            'source_id' => 'manual',
        ]);

        // Create conversation
        $conversation = Conversation::create([
            'account_id' => auth()->user()->account_id,
            'inbox_id' => $inbox->id,
            'contact_id' => $contact->id,
            'contact_inbox_id' => $contactInbox->id,
            'status' => 'open',
            'assignee_id' => auth()->id(), // Auto-assign to creator
            'priority' => 'medium',
            'last_activity_at' => now(),
        ]);

        // Create initial message if provided
        if (! empty($validated['initial_message'])) {
            $conversation->messages()->create([
                'account_id' => auth()->user()->account_id,
                'inbox_id' => $inbox->id,
                'sender_id' => auth()->id(),
                'sender_type' => User::class,
                'message_type' => 'outgoing',
                'content_type' => 'text',
                'content' => $validated['initial_message'],
                'private' => false,
            ]);
        }

        return redirect()->route('admin.helpdesk.conversation.show', $conversation)
            ->with('success', '¡Conversación creada exitosamente!');
    }

    /**
     * Search contacts (API endpoint).
     */
    public function searchContacts(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $contacts = Contact::where('account_id', auth()->user()->account_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%')
                    ->orWhere('phone_number', 'like', '%'.$query.'%');
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone_number']);

        return response()->json($contacts);
    }

    /**
     * Broadcast typing indicator.
     */
    public function typing(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        // Broadcast typing event
        broadcast(new \App\Events\Conversations\UserTyping(
            $conversation,
            auth()->user(),
            $validated['is_typing']
        ));

        return response()->json(['success' => true]);
    }

    /**
     * Search conversation and messages.
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $accountId = auth()->user()->account_id;

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Search in conversation by:
        // 1. Contact name, email, phone
        // 2. Message content
        // 3. Conversation ID
        $conversations = Conversation::where('account_id', $accountId)
            ->where(function ($q) use ($query) {
                // Search by conversation ID
                if (is_numeric($query)) {
                    $q->where('id', $query);
                }

                // Search by contact details
                $q->orWhereHas('contact', function ($contactQuery) use ($query) {
                    $contactQuery->where('name', 'like', '%'.$query.'%')
                        ->orWhere('email', 'like', '%'.$query.'%')
                        ->orWhere('phone_number', 'like', '%'.$query.'%');
                });

                // Search by message content
                $q->orWhereHas('messages', function ($messageQuery) use ($query) {
                    $messageQuery->where('content', 'like', '%'.$query.'%');
                });
            })
            ->with(['contact', 'inbox.channel', 'assignee', 'messages' => function ($query) use ($q) {
                // Get matching messages or latest message
                $query->where('content', 'like', '%'.$q.'%')
                    ->orWhereRaw('id = (SELECT id FROM messages WHERE conversation_id = conversation.id ORDER BY created_at DESC LIMIT 1)')
                    ->latest()
                    ->limit(1);
            }])
            ->latest('last_activity_at')
            ->limit(20)
            ->get();

        // Format results
        $results = $conversations->map(function ($conversation) use ($query) {
            $lastMessage = $conversation->messages->first();

            // Check if the match was in a message
            $matchInMessage = $conversation->messages()
                ->where('content', 'like', '%'.$query.'%')
                ->exists();

            return [
                'id' => $conversation->id,
                'contact' => [
                    'name' => $conversation->contact->name,
                    'email' => $conversation->contact->email,
                    'phone' => $conversation->contact->phone_number,
                ],
                'inbox' => [
                    'name' => $conversation->inbox->name,
                    'type' => $conversation->inbox->channel_type,
                ],
                'status' => $conversation->status,
                'assignee' => $conversation->assignee ? $conversation->assignee->name : null,
                'last_message' => $lastMessage ? [
                    'content' => \Illuminate\Support\Str::limit($lastMessage->content, 100),
                    'created_at' => $lastMessage->created_at->diffForHumans(),
                ] : null,
                'last_activity_at' => $conversation->last_activity_at->diffForHumans(),
                'match_type' => $matchInMessage ? 'message' : 'contact',
                'url' => route('admin.helpdesk.conversation.show', $conversation),
            ];
        });

        return response()->json($results);
    }

    /**
     * Export conversation to PDF.
     */
    public function exportToPdf(Conversation $conversation)
    {
        // Load relationships needed for the PDF
        $conversation->load([
            'contact',
            'inbox.channel',
            'assignee',
            'team',
            'messages.sender',
        ]);

        // Generate PDF
        $pdf = Pdf::loadView('helpdeskchat::admin.conversation.pdf', [
            'conversation' => $conversation,
        ]);

        // Set PDF options for better formatting
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        // Generate filename
        $filename = sprintf(
            'conversacion_%d_%s.pdf',
            $conversation->id,
            now()->format('Ymd_His')
        );

        // Download the PDF
        return $pdf->download($filename);
    }

    /**
     * Show print-optimized view for conversation.
     */
    public function printView(Conversation $conversation)
    {
        // Load relationships
        $conversation->load([
            'contact',
            'inbox.channel',
            'assignee',
            'team',
            'messages.sender',
        ]);

        return view('helpdeskchat::admin.conversation.print', compact('conversation'));
    }

    /**
     * Export conversation to Excel/CSV.
     */
    public function exportToExcel(Request $request)
    {
        $accountId = $request->user()->account_id;

        // Get filters from request
        $filters = $request->only(['status', 'inbox_id', 'assignee_id', 'team_id', 'created_after', 'created_before']);

        // Determine format
        $format = $request->get('format', 'xlsx'); // xlsx or csv

        // Generate filename
        $filename = sprintf(
            'conversaciones_%s.%s',
            now()->format('Ymd_His'),
            $format
        );

        // Export
        return Excel::download(
            new ConversationsExport($accountId, $filters),
            $filename
        );
    }

    /**
     * Email conversation transcript.
     */
    public function emailTranscript(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string|max:500',
        ]);

        // Load relationships
        $conversation->load([
            'contact',
            'inbox.channel',
            'assignee',
            'team',
            'messages.sender',
        ]);

        // Generate PDF
        $pdf = Pdf::loadView('helpdeskchat::admin.conversation.pdf', [
            'conversation' => $conversation,
        ]);

        $pdf->setPaper('A4', 'portrait');

        // Send email with PDF attachment
        \Mail::to($validated['email'])->send(
            new \App\Mail\ConversationTranscript(
                $conversation,
                $pdf->output(),
                $validated['message'] ?? null
            )
        );

        return back()->with('success', '¡Transcripción enviada por email exitosamente!');
    }

    /**
     * Update the conversation.
     */
    public function update(UpdateConversationRequest $request, Conversation $conversation)
    {
        $validated = $request->validated();

        $conversation->update($validated);

        return back()->with('success', 'Conversation updated successfully!');
    }

    /**
     * Delete the conversation.
     */
    public function destroy(Conversation $conversation)
    {
        $conversation->delete();

        return redirect()->route('admin.helpdesk.conversation.index')
            ->with('success', 'Conversation deleted successfully!');
    }

    /**
     * Close the conversation.
     */
    public function close(Conversation $conversation)
    {
        $conversation->close();

        // Broadcast conversation closed
        broadcast(new \App\Events\Conversations\ConversationStatusChanged(
            $conversation,
            $conversation->getOriginal('status'),
            'closed'
        ));

        return back()->with('success', 'Conversation closed successfully!');
    }

    /**
     * Reopen the conversation.
     */
    public function reopen(Conversation $conversation)
    {
        $conversation->reopen();

        // Broadcast conversation reopened
        broadcast(new \App\Events\Conversations\ConversationStatusChanged(
            $conversation,
            $conversation->getOriginal('status'),
            'open'
        ));

        return back()->with('success', 'Conversation reopened successfully!');
    }

    /**
     * Get common data needed by all index views.
     */
    private function getCommonIndexData(int $accountId): array
    {
        $labels = Label::where('account_id', $accountId)->get();

        return [
            'filterCounts' => [
                'all' => Conversation::where('account_id', $accountId)->count(),
                'open' => Conversation::where('account_id', $accountId)->where('status', 'open')->count(),
                'resolved' => Conversation::where('account_id', $accountId)->where('status', 'resolved')->count(),
                'pending' => Conversation::where('account_id', $accountId)->where('status', 'pending')->count(),
                'mine' => Conversation::where('account_id', $accountId)->where('assignee_id', auth()->id())->count(),
                'unassigned' => Conversation::where('account_id', $accountId)->whereNull('assignee_id')->count(),
                'mentions' => Conversation::where('account_id', $accountId)
                    ->whereHas('messages', function ($query) {
                        $query->where('content', 'like', '%@'.auth()->id().'%')
                            ->orWhere('content', 'like', '%@'.auth()->user()->name.'%');
                    })
                    ->count(),
                'unattended' => Conversation::where('account_id', $accountId)
                    ->where('status', '!=', 'resolved')
                    ->whereDoesntHave('messages', function ($query) {
                        $query->where('message_type', 'outgoing')
                            ->where('sender_type', 'App\Models\User');
                    })
                    ->count(),
            ],
            'priorityCounts' => [
                'urgent' => Conversation::where('account_id', $accountId)->where('priority', 'urgent')->count(),
                'high' => Conversation::where('account_id', $accountId)->where('priority', 'high')->count(),
                'medium' => Conversation::where('account_id', $accountId)->where('priority', 'medium')->count(),
                'low' => Conversation::where('account_id', $accountId)->where('priority', 'low')->count(),
            ],
            'inboxes' => Inbox::where('account_id', $accountId)
                ->with('channel')
                ->get()
                ->map(function ($inbox) use ($accountId) {
                    $inbox->conversations_count = Conversation::where('account_id', $accountId)
                        ->where('inbox_id', $inbox->id)
                        ->count();

                    return $inbox;
                }),
            'teams' => Team::where('account_id', $accountId)
                ->get()
                ->map(function ($team) use ($accountId) {
                    $team->conversations_count = Conversation::where('account_id', $accountId)
                        ->where('team_id', $team->id)
                        ->count();

                    return $team;
                }),
            'labels' => $labels->map(function ($label) use ($accountId) {
                $label->conversations_count = Conversation::where('account_id', $accountId)
                    ->where('cached_label_list', 'like', '%'.$label->title.'%')
                    ->count();

                return $label;
            }),
            'labelsByTitle' => $labels->keyBy('title'),
        ];
    }
}
