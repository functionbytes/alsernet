<?php

namespace Modules\HelpdeskChatFlow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskChatFlow\Models\ChatFlow;

/**
 * Procedures an editor can pick in a `call_flow` node: active flows whose
 * trigger is `procedure`.
 */
class ChatFlowProceduresController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ChatFlow::class);

        $procedures = ChatFlow::query()
            ->active()
            ->where('trigger_type', ChatFlow::TRIGGER_PROCEDURE)
            ->when($request->integer('exclude'), fn ($query, int $id) => $query->whereKeyNot($id))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ChatFlow $flow): array => ['id' => $flow->id, 'name' => $flow->name])
            ->all();

        return response()->json(['data' => $procedures]);
    }
}
