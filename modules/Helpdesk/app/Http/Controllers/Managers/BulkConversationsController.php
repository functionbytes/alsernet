<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Http\Requests\BulkConversationsRequest;
use Modules\Helpdesk\Jobs\UnsnoozeConversationJob;
use Modules\Helpdesk\Models\AgentInboxCapacity;
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
     * Actions: archive | unarchive | close | reopen | assign | tag | mark_read | mark_unread | priority | team | snooze | mute
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

        $closedConversations = [];

        try {
            $affected = DB::transaction(function () use ($action, $ids, $payload, $request, $conversations, &$closedConversations): int {
                return match ($action) {
                    'archive' => $this->bulkArchive($conversations),

                    'unarchive' => $this->bulkUnarchive($conversations),

                    'close' => $this->bulkClose($conversations, $closedConversations),

                    'reopen' => $this->bulkReopen($conversations),

                    'assign' => $this->bulkAssign($conversations, $payload['assignee_id'] ?? null),

                    'tag' => $this->bulkTag($conversations, $payload['tag_ids'] ?? []),

                    'mark_read' => $this->bulkMarkRead($ids, $request->user()->id),

                    'mark_unread' => $this->bulkMarkUnread($ids, $request->user()->id),

                    'priority' => Conversation::whereIn('id', $ids)
                        ->update(['priority' => $payload['priority'] ?? 'normal', 'updated_at' => now()]),

                    'team' => $this->bulkMoveToTeam($conversations, $payload['group_id'] ?? null),

                    'snooze' => $this->bulkSnooze($conversations, $payload['until'] ?? null),

                    'mute' => $this->bulkMute($ids, $request->user()->id, $payload['until'] ?? null),
                };
            });

            // Evento de cierre + CSAT van despues de confirmar la transaccion:
            // si se dispararan dentro del closure y otra fila del mismo lote
            // provocara un rollback, quedarian disparados para cierres que
            // nunca se llegaron a persistir.
            $this->dispatchCloseSideEffects($closedConversations, $request->boolean('skip_csat'));

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
     * Close each open conversation via the unit close() logic so status_id is set.
     * Solo hace trabajo de BD: acumula las cerradas con exito en $closedConversations
     * para que handle() dispare el evento de cierre + CSAT una vez confirmada la
     * transaccion (ver dispatchCloseSideEffects()).
     *
     * @param  Collection<int, Conversation>  $conversations
     * @param  array<int, Conversation>  $closedConversations
     */
    private function bulkClose(Collection $conversations, array &$closedConversations): int
    {
        $count = 0;

        foreach ($conversations as $conversation) {
            if ($conversation->closed_at !== null) {
                continue;
            }

            $conversation->close();
            $closedConversations[] = $conversation;
            $count++;
        }

        return $count;
    }

    /**
     * Dispara el evento de cierre + encuesta CSAT (llamada HTTP sincrona) para
     * cada conversacion cerrada en el bulk close, una vez que la transaccion ya
     * se confirmo, para parity con la accion individual sin mantener la
     * transaccion de BD abierta ni disparar side-effects de cierres que un
     * rollback posterior del mismo lote pudiera revertir.
     *
     * @param  array<int, Conversation>  $closedConversations
     */
    private function dispatchCloseSideEffects(array $closedConversations, bool $skipCsat): void
    {
        foreach ($closedConversations as $conversation) {
            ConversationClosed::dispatch($conversation);

            if ($skipCsat) {
                continue;
            }

            try {
                app(CsatService::class)->dispatchForConversation($conversation);
            } catch (\Throwable $e) {
                Log::warning('CSAT dispatch failed on bulk close', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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
     * Archive each conversation via the unit archive() logic and broadcast the
     * inbox change (a mass ->update() bypasses model methods and events).
     *
     * @param  Collection<int, Conversation>  $conversations
     */
    private function bulkArchive(Collection $conversations): int
    {
        foreach ($conversations as $conversation) {
            $conversation->archive();
            $conversation->broadcastInboxChanged('archived');
        }

        return $conversations->count();
    }

    /**
     * @param  Collection<int, Conversation>  $conversations
     */
    private function bulkUnarchive(Collection $conversations): int
    {
        foreach ($conversations as $conversation) {
            $conversation->unarchive();
            $conversation->broadcastInboxChanged('unarchived');
        }

        return $conversations->count();
    }

    /**
     * Assign each conversation via the unit assignTo() logic so the
     * ConversationAssigned event/notification fires for parity with the individual action.
     * Skips (and logs) conversations whose inbox the assignee has no capacity
     * for, unless the assignee holds the broader manage permission.
     *
     * @param  Collection<int, Conversation>  $conversations
     */
    private function bulkAssign(Collection $conversations, ?int $assigneeId): int
    {
        $assignee = $assigneeId ? User::find($assigneeId) : null;
        $accessibleInboxIds = $this->assigneeAccessibleInboxIds($assignee);
        $count = 0;

        foreach ($conversations as $conversation) {
            if ($assignee && ! $this->assigneeCanAccessInbox($accessibleInboxIds, $conversation->inbox_id)) {
                Log::warning('Bulk assign skipped: assignee lacks inbox capacity', [
                    'conversation_id' => $conversation->id,
                    'assignee_id' => $assignee->id,
                    'inbox_id' => $conversation->inbox_id,
                ]);

                continue;
            }

            $conversation->assignTo($assigneeId);
            $count++;
        }

        return $count;
    }

    /**
     * Preloaded inbox ids the assignee has capacity for, fetched once instead
     * of a per-conversation exists() query inside bulkAssign()'s foreach.
     *
     * @return array<int, int>|null null = unrestricted (assignee has helpdesk.manage)
     */
    private function assigneeAccessibleInboxIds(?User $assignee): ?array
    {
        if (! $assignee || $assignee->hasPermissionTo('helpdesk.manage')) {
            return null;
        }

        return AgentInboxCapacity::where('user_id', $assignee->id)->pluck('inbox_id')->all();
    }

    /**
     * @param  array<int, int>|null  $accessibleInboxIds  null when unrestricted
     */
    private function assigneeCanAccessInbox(?array $accessibleInboxIds, ?int $inboxId): bool
    {
        return $accessibleInboxIds === null || in_array($inboxId, $accessibleInboxIds, true);
    }

    /**
     * @param  Collection<int, Conversation>  $conversations
     */
    private function bulkTag(Collection $conversations, array $tagIds): int
    {
        foreach ($conversations as $conversation) {
            $conversation->conversationTags()->sync($tagIds);
        }

        return $conversations->count();
    }

    private function bulkMarkRead(array $ids, int $userId): int
    {
        $now = now();

        // Upsert único (índice conversation_id+user_id) en vez de un
        // updateOrInsert (SELECT + write) por conversación.
        $rows = array_map(fn (int $conversationId): array => [
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ], $ids);

        DB::connection('helpdesk')->table('helpdesk_conversation_reads')
            ->upsert($rows, ['conversation_id', 'user_id'], ['read_at', 'updated_at']);

        return count($ids);
    }

    private function bulkMarkUnread(array $ids, int $userId): int
    {
        return DB::connection('helpdesk')->table('helpdesk_conversation_reads')
            ->whereIn('conversation_id', $ids)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * @param  Collection<int, Conversation>  $conversations
     */
    private function bulkMoveToTeam(Collection $conversations, ?int $groupId): int
    {
        if (! $groupId) {
            return 0;
        }

        foreach ($conversations as $conversation) {
            $conversation->update(['group_id' => $groupId]);
        }

        return $conversations->count();
    }

    /**
     * Mirrors ConversationsController::snooze() per conversation: sets
     * snoozed_until/snoozed_by, broadcasts, and schedules the auto-unsnooze job.
     *
     * @param  Collection<int, Conversation>  $conversations
     */
    private function bulkSnooze(Collection $conversations, ?string $until): int
    {
        if (! $until) {
            return 0;
        }

        $untilDate = Carbon::parse($until);
        $count = 0;

        foreach ($conversations as $conversation) {
            $conversation->update([
                'snoozed_until' => $untilDate,
                'snoozed_by' => auth()->id(),
            ]);

            $conversation->broadcastInboxChanged('snoozed');

            UnsnoozeConversationJob::dispatch($conversation)->delay($untilDate);

            $count++;
        }

        return $count;
    }

    /**
     * Mutes each conversation for the acting user (per-agent, mirrors
     * ConversationsController::toggleMute()) — never global to the conversation.
     */
    private function bulkMute(array $ids, int $userId, ?string $until): int
    {
        $untilDate = $until ? Carbon::parse($until) : now()->addDays(7);
        $now = now();

        // Un único upsert (índice único user_id+conversation_id) en vez de
        // SELECT + update/insert por conversación; en filas existentes solo se
        // tocan muted_until/updated_at, preservando pinned_at y blocked.
        $rows = array_map(fn (int $conversationId): array => [
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'pinned_at' => null,
            'muted_until' => $untilDate,
            'blocked' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $ids);

        DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->upsert($rows, ['user_id', 'conversation_id'], ['muted_until', 'updated_at']);

        return count($ids);
    }
}
