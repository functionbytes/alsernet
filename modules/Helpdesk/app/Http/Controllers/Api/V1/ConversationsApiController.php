<?php

namespace Modules\Helpdesk\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\ConversationStatusChanged;
use Modules\Helpdesk\Http\Requests\Api\IndexConversationApiRequest;
use Modules\Helpdesk\Http\Requests\Api\StoreConversationApiRequest;
use Modules\Helpdesk\Http\Requests\Api\StoreMessageApiRequest;
use Modules\Helpdesk\Http\Requests\Api\UpdateConversationApiRequest;
use Modules\Helpdesk\Http\Resources\ConversationResource;
use Modules\Helpdesk\Http\Resources\MessageResource;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;

/**
 * @group Conversations
 *
 * Manage helpdesk conversations.
 */
class ConversationsApiController extends Controller
{
    /**
     * List conversations
     *
     * Returns a paginated list of conversations.
     *
     * @queryParam status string Filter by status slug. Example: open
     * @queryParam priority string Priority filter. Enum: low,normal,high,urgent. Example: high
     * @queryParam assignee_id integer Filter by assignee user ID. Example: 5
     * @queryParam q string Search in subject. Example: pedido
     * @queryParam per_page integer Results per page (default 15, max 100). Example: 20
     */
    public function index(IndexConversationApiRequest $request): JsonResponse
    {
        $conversations = Conversation::query()
            ->with(['customer', 'status', 'assignee', 'conversationTags'])
            ->when($request->filled('q'), fn ($q, $search) => $q->where('subject', 'like', "%{$search}%"))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->when($request->filled('assignee_id'), fn ($q) => $q->where('assignee_id', $request->assignee_id))
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->whereHas('status', fn ($s) => $s->where('slug', $request->status));
            })
            ->latest('last_message_at')
            ->paginate($request->input('per_page', 15));

        return ApiResponse::success(ConversationResource::collection($conversations));
    }

    /**
     * Create conversation
     *
     * Opens a new conversation with an opening message.
     *
     * @bodyParam customer_id integer required Customer ID. Example: 12
     * @bodyParam subject string required Conversation subject. Example: Problema con mi pedido
     * @bodyParam priority string Priority. Enum: low,normal,high,urgent. Example: normal
     * @bodyParam message string required Opening message. Example: Necesito ayuda.
     * @bodyParam assignee_id integer Assign to agent user ID. Example: 3
     */
    public function store(StoreConversationApiRequest $request): JsonResponse
    {
        $conversation = DB::transaction(function () use ($request) {
            $status = ConversationStatus::where('is_open', true)->orderBy('sort_order')->first();

            $conv = Conversation::create([
                'customer_id' => $request->customer_id,
                'subject' => $request->subject,
                'priority' => $request->priority ?? 'normal',
                'channel' => 'api',
                'status_id' => $status?->id,
                'assignee_id' => $request->assignee_id,
                'assigned_at' => $request->assignee_id ? now() : null,
            ]);

            ConversationItem::create([
                'conversation_id' => $conv->id,
                'user_id' => $request->user()->id,
                'type' => 'outgoing',
                'body' => $request->message,
            ]);

            $conv->update(['last_message_at' => now()]);

            return $conv;
        });

        $conversation->load(['customer', 'status', 'assignee']);
        ConversationCreated::dispatch($conversation);

        return ApiResponse::created(new ConversationResource($conversation), 'Conversación creada correctamente.');
    }

    /**
     * Get conversation
     *
     * Returns a single conversation with its relations.
     */
    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $conversation->load(['customer', 'status', 'assignee', 'conversationTags']);

        return ApiResponse::success(new ConversationResource($conversation));
    }

    /**
     * Update conversation
     *
     * Update subject, priority, assignee or status.
     */
    public function update(UpdateConversationApiRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->update($request->validated());

        if ($request->filled('status_id') && $conversation->wasChanged('status_id')) {
            $newStatus = ConversationStatus::find($request->status_id);
            if ($newStatus) {
                ConversationStatusChanged::dispatch($conversation, $newStatus);
            }
        }

        $conversation->load(['customer', 'status', 'assignee', 'conversationTags']);

        return ApiResponse::success(new ConversationResource($conversation), 'Conversación actualizada.');
    }

    /**
     * Close conversation
     *
     * Marks the conversation as closed.
     */
    public function close(Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->close();
        $conversation->load(['customer', 'status', 'assignee']);

        return ApiResponse::success(new ConversationResource($conversation), 'Conversación cerrada.');
    }

    /**
     * Post message
     *
     * Add a new message or internal note to the conversation.
     *
     * @bodyParam body string required Message content. Example: Gracias por tu paciencia.
     * @bodyParam is_internal boolean Whether this is an internal note. Example: false
     */
    public function storeMessage(StoreMessageApiRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $item = DB::transaction(function () use ($request, $conversation) {
            $message = ConversationItem::create([
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'type' => $request->boolean('is_internal') ? 'note' : 'outgoing',
                'body' => $request->body,
                'is_internal' => $request->boolean('is_internal'),
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $message;
        });

        ConversationMessageCreated::dispatch($item);

        $item->load(['user']);

        return ApiResponse::created(new MessageResource($item), 'Mensaje enviado correctamente.');
    }
}
