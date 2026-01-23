<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Search;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class SearchController extends Controller
{
    /**
     * Global search across conversation, messages, and contacts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all'); // all, conversation, messages, contacts
        $limit = min((int) $request->get('limit', 10), 50);

        if (strlen($query) < 2) {
            return response()->json([
                'conversation' => [],
                'messages' => [],
                'contacts' => [],
                'total' => 0,
            ]);
        }

        $accountId = auth()->user()->account_id;
        $results = [];

        // Search conversation
        if (in_array($type, ['all', 'conversation'])) {
            $results['conversation'] = Conversation::search($query)
                ->where('account_id', $accountId)
                ->take($limit)
                ->get()
                ->map(function ($conversation) {
                    return [
                        'id' => $conversation->id,
                        'type' => 'conversation',
                        'title' => "Conversation #{$conversation->id} - {$conversation->contact->name}",
                        'subtitle' => $conversation->inbox->name,
                        'url' => route('admin.helpdesk.conversation.show', $conversation),
                        'status' => $conversation->status,
                        'priority' => $conversation->priority,
                        'last_activity' => $conversation->last_activity_at->diffForHumans(),
                    ];
                });
        }

        // Search messages
        if (in_array($type, ['all', 'messages'])) {
            $results['messages'] = Message::search($query)
                ->where('account_id', $accountId)
                ->take($limit)
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'type' => 'message',
                        'title' => "Message in Conversation #{$message->conversation_id}",
                        'subtitle' => \Illuminate\Support\Str::limit($message->content, 100),
                        'url' => route('admin.helpdesk.conversation.show', $message->conversation_id),
                        'sender' => $message->sender->name,
                        'created_at' => $message->created_at->diffForHumans(),
                    ];
                });
        }

        // Search contacts
        if (in_array($type, ['all', 'contacts'])) {
            $results['contacts'] = Contact::search($query)
                ->where('account_id', $accountId)
                ->take($limit)
                ->get()
                ->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'type' => 'contact',
                        'title' => $contact->name,
                        'subtitle' => $contact->email ?? $contact->phone_number ?? $contact->company_name,
                        'url' => '#', // Could link to contact detail page if exists
                        'company' => $contact->company_name,
                        'conversations_count' => $contact->conversations()->count(),
                    ];
                });
        }

        $total = collect($results)->flatten()->count();

        return response()->json(array_merge($results, ['total' => $total]));
    }

    /**
     * Autocomplete suggestions for search.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $accountId = auth()->user()->account_id;
        $suggestions = [];

        // Get top 5 conversation
        $conversations = Conversation::search($query)
            ->where('account_id', $accountId)
            ->take(5)
            ->get()
            ->map(fn ($c) => [
                'value' => "Conversation #{$c->id} - {$c->contact->name}",
                'type' => 'conversation',
                'id' => $c->id,
                'url' => route('admin.helpdesk.conversation.show', $c),
            ]);

        // Get top 5 contacts
        $contacts = Contact::search($query)
            ->where('account_id', $accountId)
            ->take(5)
            ->get()
            ->map(fn ($c) => [
                'value' => $c->name.($c->email ? " ({$c->email})" : ''),
                'type' => 'contact',
                'id' => $c->id,
                'url' => '#',
            ]);

        return response()->json($conversations->merge($contacts)->take(10));
    }
}
