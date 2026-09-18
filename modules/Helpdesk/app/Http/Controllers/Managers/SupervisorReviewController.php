<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\StoreSupervisorReviewRequest;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\SupervisorReview;

/**
 * Solicitudes de revisión de supervisor sobre una conversación (modal
 * "supervisor-review" del inbox). Fase 1: solo registra la solicitud
 * (status = pending). La bandeja para que un supervisor la resuelva es
 * una fase futura, fuera de este alcance.
 */
class SupervisorReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.conversations.update')->only(['store']);
    }

    public function store(StoreSupervisorReviewRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        // 'pending' explícito: es el default de la columna, pero sin pasarlo
        // aquí el modelo en memoria devuelto por create() no lo refleja (la
        // respuesta JSON mandaría status: null aunque la fila en BD sea correcta).
        $review = SupervisorReview::create([
            'conversation_id' => $conversation->id,
            'requested_by' => $request->user()->id,
            'review_type' => $request->validated('review_type'),
            'comment' => $request->validated('comment'),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud enviada al supervisor.',
            'review' => [
                'id' => $review->id,
                'review_type' => $review->review_type,
                'status' => $review->status,
            ],
        ], 201);
    }
}
