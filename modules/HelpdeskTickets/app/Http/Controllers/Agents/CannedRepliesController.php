<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\CannedReply;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\TicketVariableInterpolator;

class CannedRepliesController extends Controller
{
    public function __construct(
        private readonly TicketVariableInterpolator $interpolator,
    ) {}

    /**
     * Search canned replies for the autocomplete widget.
     * Returns global replies + replies owned by the current agent.
     *
     * Con ?ticket_id, el body/html_body vienen ya interpolados con las variables
     * del ticket ({{customer_name}}, {{ticket_number}}, …) listos para insertar.
     */
    public function search(Request $request): JsonResponse
    {
        $query = CannedReply::forUser(auth()->id());

        if ($request->filled('q')) {
            $query->search($request->q);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        $replies = $query
            ->orderByDesc('usage_count')
            ->limit(20)
            ->get(['id', 'title', 'body', 'html_body', 'category', 'shortcut', 'usage_count']);

        $ticket = $request->filled('ticket_id')
            ? Ticket::find((int) $request->input('ticket_id'))
            : null;

        if ($ticket) {
            $replies->transform(function ($reply) use ($ticket) {
                $reply->body = $this->interpolator->interpolate($reply->body, $ticket);
                $reply->html_body = $this->interpolator->interpolate($reply->html_body, $ticket);

                return $reply;
            });
        }

        return response()->json($replies);
    }
}
