<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Search;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Services\Conversations\ConversationIndexService;

class SearchController extends Controller
{
    private const PER_PAGE = 15;

    private const MIN_QUERY_LENGTH = 2;

    private const MESSAGES_LOOKBACK_MONTHS = 3;

    public function __construct(private readonly ConversationIndexService $indexService) {}

    public function index(): View
    {
        $user = auth()->user();
        $indexData = $this->indexService->buildIndexData($user->account_id, $user->id, $user->name ?? '');

        return view('Chat::chats.search.index', array_merge($indexData, [
            'filter' => 'search',
            'activeFilters' => [],
        ]));
    }

    public function contacts(Request $request): JsonResponse
    {
        $q = trim($request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        if (strlen($q) < self::MIN_QUERY_LENGTH) {
            return $this->emptyPayload('contacts');
        }

        $accountId = auth()->user()->account_id;

        $results = Customer::forAccount($accountId)
            ->search($q)
            ->paginate(self::PER_PAGE, ['id', 'name', 'last_name', 'email', 'phone_number', 'identifier'], 'page', $page);

        $contacts = $results->map(fn ($c) => [
            'id' => $c->id,
            'name' => trim($c->name.' '.($c->last_name ?? '')),
            'email' => $c->email,
            'phone_number' => $c->phone_number,
            'identifier' => $c->identifier,
        ]);

        return response()->json([
            'payload' => [
                'contacts' => $contacts,
                'meta' => [
                    'total' => $results->total(),
                    'page' => $page,
                    'per_page' => self::PER_PAGE,
                ],
            ],
        ]);
    }

    public function conversations(Request $request): JsonResponse
    {
        $q = trim($request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        if (strlen($q) < self::MIN_QUERY_LENGTH) {
            return $this->emptyPayload('conversations');
        }

        $accountId = auth()->user()->account_id;

        $query = Conversation::forAccount($accountId);

        if (is_numeric($q)) {
            $query->where('id', (int) $q);
        } else {
            $query->textSearch($q);
        }

        $results = $query
            ->with(['customer:id,name,last_name,email,phone_number,identifier', 'inbox:id,name,channel_type', 'assignee:id,name,email'])
            ->latest('last_activity_at')
            ->paginate(self::PER_PAGE, ['id', 'account_id', 'customer_id', 'inbox_id', 'assignee_id', 'subject', 'last_activity_at', 'created_at'], 'page', $page);

        $conversations = $results->map(fn ($conv) => [
            'id' => $conv->id,
            'created_at' => $conv->created_at?->timestamp,
            'last_activity_at' => $conv->last_activity_at?->diffForHumans(),
            'contact' => $conv->customer ? [
                'id' => $conv->customer->id,
                'name' => trim($conv->customer->name.' '.($conv->customer->last_name ?? '')),
                'email' => $conv->customer->email,
                'phone_number' => $conv->customer->phone_number,
                'identifier' => $conv->customer->identifier,
            ] : null,
            'inbox' => $conv->inbox ? [
                'id' => $conv->inbox->id,
                'name' => $conv->inbox->name,
                'channel_type' => $conv->inbox->channel_type,
            ] : null,
            'agent' => $conv->assignee ? [
                'id' => $conv->assignee->id,
                'name' => $conv->assignee->name,
            ] : null,
            'additional_attributes' => [
                'mail_subject' => $conv->subject,
            ],
        ]);

        return response()->json([
            'payload' => [
                'conversations' => $conversations,
                'meta' => [
                    'total' => $results->total(),
                    'page' => $page,
                    'per_page' => self::PER_PAGE,
                ],
            ],
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $q = trim($request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        if (strlen($q) < self::MIN_QUERY_LENGTH) {
            return $this->emptyPayload('messages');
        }

        $accountId = auth()->user()->account_id;
        $lookback = Carbon::now()->subMonths(self::MESSAGES_LOOKBACK_MONTHS);

        $results = ConversationMessage::query()
            ->where('account_id', $accountId)
            ->where('content', 'like', '%'.$q.'%')
            ->where('created_at', '>=', $lookback)
            ->where('private', false)
            ->with('sender:id,name')
            ->latest()
            ->paginate(self::PER_PAGE, ['id', 'content', 'conversation_id', 'inbox_id', 'message_type', 'private', 'created_at', 'sender_type', 'sender_id'], 'page', $page);

        $messages = $results->map(fn ($msg) => [
            'id' => $msg->id,
            'content' => $msg->content,
            'conversation_id' => $msg->conversation_id,
            'inbox_id' => $msg->inbox_id,
            'message_type' => $msg->message_type,
            'private' => (bool) $msg->private,
            'created_at' => $msg->created_at?->diffForHumans(),
            'sender' => $msg->sender ? [
                'id' => $msg->sender->id,
                'name' => $msg->sender->name,
                'type' => $msg->sender_type === 'App\\Models\\User' ? 'user' : 'contact',
            ] : null,
        ]);

        return response()->json([
            'payload' => [
                'messages' => $messages,
                'meta' => [
                    'total' => $results->total(),
                    'page' => $page,
                    'per_page' => self::PER_PAGE,
                ],
            ],
        ]);
    }

    private function emptyPayload(string $type): JsonResponse
    {
        return response()->json([
            'payload' => [
                $type => [],
                'meta' => ['total' => 0, 'page' => 1, 'per_page' => self::PER_PAGE],
            ],
        ]);
    }
}
