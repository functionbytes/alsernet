<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\SlaService;

class TicketSlaController extends Controller
{
    public function pauseSla(Ticket $ticket): RedirectResponse
    {
        $this->authorize('pauseSla', $ticket);

        app(SlaService::class)->pauseSla($ticket);

        return back()->with('success', __('helpdesktickets::helpdesktickets.messages.sla_paused'));
    }

    public function resumeSla(Ticket $ticket): RedirectResponse
    {
        $this->authorize('pauseSla', $ticket);

        app(SlaService::class)->resumeSla($ticket);

        return back()->with('success', __('helpdesktickets::helpdesktickets.messages.sla_resumed'));
    }
}
