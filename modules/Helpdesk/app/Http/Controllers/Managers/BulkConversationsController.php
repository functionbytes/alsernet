<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Http\Requests\BulkConversationsRequest;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\CsatService;

class BulkConversationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.conversations.update');
    }

    /**
     * Handle bulk conversation operations.
     *
     * Actions: archive | unarchive | close | reopen | assign | tag | mark_read | mark_unread
     */
    public function handle(BulkConversationsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $ids = $validated['ids'];
        $payload = $validated['payload'] ?? [];

        $conversations = Conversation::query()
            ->whereIn('id', $ids)
            ->get();

        foreach ($conversations as $conversation) {
            if (! $request->user()->can('update', $conversation)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para modificar una o más conversaciones seleccionadas.',
                ], 403);
            }
        }

        try {
            $affected = DB::transaction(function () use ($action, $ids, $payload, $request, $conversations): int {
                return match ($action) {
                    'archive' => Conversation::whereIn('id', $ids)
                        ->update(['is_archived' => true, 'updated_at' => now()]),

                    'unarchive' => Conversation::whereIn('id', $ids)
                        ->update(['is_archived' => false, 'updated_at' => now()]),

                    'close' => $this->bulkClose($conversations, $request),

                    'reopen' => $this->bulkReopen($conversations),

                    'assign' => $this->bulkAssign($conversations, $payload['assignee_id'] ?? null),

                    'tag' => $this->bulkTag($ids, $payload['tag_ids'] ?? []),

                    'mark_read' => $this->bulkMarkRead($ids, $request->user()->id),

                    'mark_unread' => $this->bulkMarkUnread($ids, $request->user()->id),
                };
            });

            return response()->json([
                'success' => true,
                'message' => "{$affected} conversaciones actualizadas correctamente.",
                'affected' => $affected,
            ]);
        } catch (\Throwable $e) {
            Log::error('Bulk conversation operation failed', [
                'action' => $action,
                'ids' => $ids,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar la operación. Por favor intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Close each open conversation via the unit close() logic so status_id is set,
     * then fire the close event + CSAT survey for parity with the individual action.
     *
     * @param  Collection<int, Conversation>  $conversations
     */
    private function bulkClose(Collection $conversations, BulkConversationsRequest $request): int
    {
        $count = 0;
        $skipCsat = $request->boolean('skip_csat');

        foreach ($conversations as $conversation) {
            if ($conversation->closed_at !== null) {
                continue;
            }

            $conversation->close();
            ConversationClosed::dispatch($conversation);

            if (! $skipCsat) {
                try {
                    app(CsatService::class)->dispatchForConversation($conversation);
                } catch (\Throwable $e) {
                    Log::warning('CSAT dispatch failed on bulk close', [
                        'conversation_id' => $conversation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $count++;
        }

        return $count;
    }

    /**
     * Reopen each closed conversation via the unit reopen() logic so status_id is
     * restored to an open status (not just closed_at cleared).
     *
     * @param  Collection<int, Conversation>  $conversations
     */
    private function bulkReopen(Collection $conversations): int
    {
        $count = 0;

        foreach ($conversations as $conversation) {
            if ($conversation->closed_at === null) {
                continue;
            }

            $conversation->reopen();
            $count++;
        }

        return $count;
    }

    /**
     * Assign each conversation via the unit assignTo() logic so the
     * ConversationAssigned event/notification fires for parity with the individual action.
     *
     * @param  Collection<int, Conversation>  $conversations
     */
    private function bulkAssign(Collection $conversations, ?int $assigneeId): int
    {
        foreach ($conversations as $conversation) {
            $conversation->assignTo($assigneeId);
        }

        return $conversations->count();
    }

    private function bulkTag(array $ids, array $tagIds): int
    {
        $conversations = Conversation::whereIn('id', $ids)->get();

        foreach ($conversations as $conversation) {
            $conversation->conversationTags()->sync($tagIds);
        }

        return $conversations->count();
    }

    private function bulkMarkRead(array $ids, int $userId): int
    {
        $now = now();

        foreach ($ids as $conversationId) {
            DB::connection('helpdesk')->table('helpdesk_conversation_reads')
                ->updateOrInsert(
                    ['conversation_id' => $conversationId, 'user_id' => $userId],
                    ['read_at' => $now, 'updated_at' => $now, 'created_at' => $now]
                );
        }

        return count($ids);
    }

    private function bulkMarkUnread(array $ids, int $userId): int
    {
        return DB::connection('helpdesk')->table('helpdesk_conversation_reads')
            ->whereIn('conversation_id', $ids)
            ->where('user_id', $userId)
            ->delete();
    }
}
