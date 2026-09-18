<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Http\Requests\BulkApplyMacroRequest;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Macro;
use Modules\Helpdesk\Services\Macros\MacroExecutorService;

/**
 * Macro application for conversations: single, bulk, and the inbox picker
 * list (extracted from ConversationsController — QUAL-03).
 */
class ConversationMacrosController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.macros.use')->only(['applyMacro', 'macrosForPicker']);
        $this->middleware('can:helpdesk.conversations.update')->only(['bulkApplyMacro']);
    }

    public function applyMacro(Conversation $conversation, Macro $macro): JsonResponse
    {
        $this->authorize('update', $conversation);

        $result = app(MacroExecutorService::class)->apply($macro, $conversation, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Macro aplicado',
            'executed' => $result['executed'] ?? [],
            'failed' => $result['failed'] ?? [],
        ]);
    }

    /**
     * Apply a single macro to many conversations at once. Each conversation is
     * guarded in its own try/catch so one failure does not abort the batch.
     */
    public function bulkApplyMacro(BulkApplyMacroRequest $request, MacroExecutorService $executor): JsonResponse
    {
        $validated = $request->validated();

        $macro = Macro::query()->active()->findOrFail($validated['macro_id']);

        $conversations = Conversation::query()
            ->with(['customer', 'inbox', 'assignee'])
            ->whereIn('id', $validated['conversation_ids'])
            ->get();

        $user = $request->user();
        $userId = $user->id;
        $canManage = $user->hasPermissionTo('helpdesk.manage');
        $canUpdateAll = $user->hasPermissionTo('helpdesk.conversations.update');
        $accessibleInboxIds = $canManage
            ? null
            : AgentInboxCapacity::query()->where('user_id', $userId)->pluck('inbox_id')->all();

        $applied = 0;
        $failed = 0;

        foreach ($conversations as $conversation) {
            if (! $this->canBulkUpdate($conversation, $userId, $canManage, $canUpdateAll, $accessibleInboxIds)) {
                $failed++;

                continue;
            }

            try {
                $executor->apply($macro, $conversation, $userId);
                $applied++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Bulk macro apply failed for conversation', [
                    'macro_id' => $macro->id,
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => $applied > 0,
            'applied' => $applied,
            'failed' => $failed,
            'message' => $failed === 0
                ? "Macro aplicado a {$applied} conversaciones."
                : "Macro aplicado a {$applied} conversaciones, {$failed} fallaron.",
        ]);
    }

    /**
     * List active macros for the inbox picker. When ?sort=used, macros are
     * ordered by usage (most used first) with a 'usados' flag on each entry.
     */
    public function macrosForPicker(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $sortByUsage = $request->query('sort') === 'used';

        $query = Macro::query()
            ->active()
            ->where(function (Builder $q): void {
                $q->where('is_shared', true)
                    ->orWhere('user_id', auth()->id());
            });

        if ($sortByUsage) {
            $query->orderByDesc('usage_count')->orderByDesc('last_used_at');
        } else {
            $query->orderBy('name');
        }

        $macros = $query->get()->map(function (Macro $macro): array {
            $actions = collect($macro->actions ?? [])
                ->map(fn (array $action): string => Macro::ACTION_TYPES[$action['type'] ?? ''] ?? ($action['type'] ?? 'Acción'))
                ->values();

            return [
                'id' => $macro->id,
                'name' => $macro->name,
                'description' => $macro->description,
                // null = generico (aplica a cualquier idioma); el picker del
                // composer lo usa para ordenar/marcar coincidencia con el
                // idioma del contacto (helpdesk_customers.language).
                'language' => $macro->language,
                'usageCount' => (int) $macro->usage_count,
                'usados' => (int) $macro->usage_count > 0,
                'lastUsedAt' => $macro->last_used_at?->toIso8601String(),
                'actions_count' => $actions->count(),
                'actions_summary' => $actions->implode(' · '),
                'actions' => $actions->map(fn (string $label): array => ['label' => $label])->all(),
            ];
        });

        return response()->json(['success' => true, 'macros' => $macros]);
    }

    /**
     * In-memory mirror of ConversationPolicy::update for bulk batches: avoids a
     * per-conversation AgentInboxCapacity query by reusing preloaded inbox ids.
     *
     * @param  array<int, int>|null  $accessibleInboxIds  null when the user has helpdesk.manage
     */
    private function canBulkUpdate(
        Conversation $conversation,
        int $userId,
        bool $canManage,
        bool $canUpdateAll,
        ?array $accessibleInboxIds,
    ): bool {
        if (! $canUpdateAll && $conversation->assignee_id !== $userId) {
            return false;
        }

        if ($canManage) {
            return true;
        }

        return in_array($conversation->inbox_id, $accessibleInboxIds ?? [], true);
    }
}
