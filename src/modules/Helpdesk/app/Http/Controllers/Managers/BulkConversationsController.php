<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Http\Requests\BulkConversationsRequest;
use Modules\Helpdesk\Models\Conversation;

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
            $affected = DB::transaction(function () use ($action, $ids, $payload, $request): int {
                return match ($action) {
                    'archive' => Conversation::whereIn('id', $ids)
                        ->update(['is_archived' => true, 'updated_at' => now()]),

                    'unarchive' => Conversation::whereIn('id', $ids)
                        ->update(['is_archived' => false, 'updated_at' => now()]),

                    'close' => Conversation::whereIn('id', $ids)
                        ->whereNull('closed_at')
                        ->update(['closed_at' => now(), 'updated_at' => now()]),

                    'reopen' => Conversation::whereIn('id', $ids)
                        ->whereNotNull('closed_at')
                        ->update(['closed_at' => null, 'updated_at' => now()]),

                    'assign' => Conversation::whereIn('id', $ids)
                        ->update([
                            'assignee_id' => $payload['assignee_id'] ?? null,
                            'assigned_at' => isset($payload['assignee_id']) ? now() : null,
                            'updated_at' => now(),
                        ]),

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
