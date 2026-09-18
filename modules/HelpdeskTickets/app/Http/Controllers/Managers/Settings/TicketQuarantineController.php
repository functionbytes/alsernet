<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketQuarantine;
use Modules\HelpdeskTickets\Models\TicketStatus;

/**
 * Bandeja de revisión de los correos retenidos por el clasificador de spam.
 *
 * Es la mitad que hace defendible al clasificador: sin un sitio donde ver y
 * liberar lo retenido, «retener» y «descartar» son lo mismo para el cliente
 * cuyo correo se quedó fuera.
 */
class TicketQuarantineController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: TicketQuarantine::STATUS_PENDING;

        $items = TicketQuarantine::query()
            ->when(
                $status !== 'all',
                fn ($q) => $q->where('status', $status)
            )
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('helpdesktickets::managers.settings.quarantine.index', [
            'items' => $items,
            'status' => $status,
            'pendingCount' => TicketQuarantine::query()->pending()->count(),
        ]);
    }

    /**
     * Falso positivo: era un cliente real. Se crea el ticket que debió crearse.
     */
    public function release(Request $request, TicketQuarantine $quarantine): RedirectResponse
    {
        if ($quarantine->status !== TicketQuarantine::STATUS_PENDING) {
            return back()->with('error', 'Este correo ya se ha revisado.');
        }

        $customer = Customer::query()->firstOrCreate(
            ['email' => $quarantine->from_email],
            ['name' => $quarantine->from_name ?: $quarantine->from_email],
        );

        // Misma transacción que la ingesta real, y por el mismo motivo: el
        // lockForUpdate de generateTicketNumber() solo surte efecto dentro de
        // una transacción.
        $ticket = DB::connection('helpdesk')->transaction(fn () => Ticket::create([
            'customer_id' => $customer->id,
            'subject' => $quarantine->subject ?: '(sin asunto)',
            'description' => $quarantine->body_text ?: $quarantine->body_html,
            'source' => 'email',
            'status_id' => TicketStatus::where('is_default', true)->first()?->id ?? 1,
            'priority' => 'normal',
            'ticket_number' => Ticket::generateTicketNumber(),
        ]));

        // Igual que en la ingesta: sin esto el cliente no recibe la
        // confirmación de que su solicitud ha llegado.
        TicketCreated::dispatch($ticket);

        $quarantine->update([
            'status' => TicketQuarantine::STATUS_RELEASED,
            'released_ticket_id' => $ticket->id,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Correo liberado como ticket #{$ticket->ticket_number}.");
    }

    /**
     * Confirmado como spam. Se añade a la lista negra para que el siguiente
     * correo del mismo remitente ni siquiera llegue al clasificador.
     */
    public function confirm(Request $request, TicketQuarantine $quarantine): RedirectResponse
    {
        if ($quarantine->status !== TicketQuarantine::STATUS_PENDING) {
            return back()->with('error', 'Este correo ya se ha revisado.');
        }

        TicketEmailBlacklist::query()->firstOrCreate(
            ['type' => 'email', 'value' => $quarantine->from_email],
            [
                'reason' => 'Confirmado como spam desde la cuarentena',
                'is_active' => true,
                'added_by' => $request->user()?->id,
            ]
        );

        $quarantine->update([
            'status' => TicketQuarantine::STATUS_CONFIRMED,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Remitente añadido a la lista negra.');
    }
}
