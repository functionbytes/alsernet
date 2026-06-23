<?php

namespace Modules\HelpdeskSla\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\HelpdeskSla\Http\Requests\Managers\BreachIndexRequest;
use Modules\HelpdeskSla\Models\ConversationSlaBreach;

/**
 * Manager view + JSON feed for conversation SLA breaches (the historic record
 * produced by ConversationSlaService). Read-only listing with filters plus a
 * "mark resolved" action.
 */
class SlaBreachesController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ConversationSlaBreach::class);

        return view('helpdesksla::breaches.index');
    }

    public function data(BreachIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ConversationSlaBreach::class);

        $breaches = ConversationSlaBreach::query()
            ->with(['conversation' => fn ($query) => $query->with('customer')])
            ->when($request->filled('sla_type'), fn ($query) => $query->where('sla_type', $request->string('sla_type')))
            ->when($request->filled('resolved'), fn ($query) => $query->where('resolved', $request->boolean('resolved')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('breached_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('breached_at', '<=', $request->date('to')))
            ->latest('breached_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $breaches->getCollection()->map(fn (ConversationSlaBreach $breach): array => [
                'id' => $breach->id,
                'conversationId' => $breach->conversation_id,
                'subject' => $breach->conversation?->subject ?? 'Sin asunto',
                'customer' => $breach->conversation?->customer?->name ?? 'Sin cliente',
                'channel' => $breach->conversation?->channel,
                'slaType' => $breach->sla_type,
                'slaTypeLabel' => $breach->type_label,
                'dueAt' => $breach->due_at?->toIso8601String(),
                'breachedAt' => $breach->breached_at?->toIso8601String(),
                'minutesOver' => $breach->minutes_over,
                'resolved' => $breach->resolved,
                'resolvedAt' => $breach->resolved_at?->toIso8601String(),
            ])->all(),
            'meta' => [
                'currentPage' => $breaches->currentPage(),
                'lastPage' => $breaches->lastPage(),
                'total' => $breaches->total(),
                'unresolved' => ConversationSlaBreach::query()->unresolved()->count(),
            ],
        ]);
    }

    public function resolve(ConversationSlaBreach $breach): JsonResponse
    {
        $this->authorize('update', $breach);

        $breach->markAsResolved();

        return response()->json([
            'success' => true,
            'message' => 'Incumplimiento marcado como resuelto.',
        ]);
    }
}
