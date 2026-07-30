<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Http\Requests\ApplyAiSuggestionRequest;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Aplica al ticket una sugerencia de IA ya calculada (categoría o prioridad).
 * Hasta ahora ai_suggested_category_id / ai_suggested_priority se escribían pero
 * no había forma de aplicarlas desde la UI. Al aplicar, se limpia la sugerencia.
 */
class ApplyAiSuggestionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.update');
    }

    public function apply(ApplyAiSuggestionRequest $request, Ticket $ticket): JsonResponse
    {
        $field = $request->validated()['field'];

        if ($field === 'category' && $ticket->ai_suggested_category_id) {
            $ticket->update([
                'category_id' => $ticket->ai_suggested_category_id,
                'ai_suggested_category_id' => null,
            ]);
        }

        if ($field === 'priority' && $ticket->ai_suggested_priority) {
            $ticket->update([
                'priority' => $ticket->ai_suggested_priority,
                'ai_suggested_priority' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sugerencia aplicada.',
            'data' => [
                'category_id' => $ticket->category_id,
                'priority' => $ticket->priority,
            ],
        ]);
    }
}
