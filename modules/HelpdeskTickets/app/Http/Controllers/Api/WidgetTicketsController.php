<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskTickets\Http\Requests\Api\StoreWidgetTicketRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Services\TicketService;

class WidgetTicketsController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {}

    /**
     * GET /hd/api/tickets/categories
     *
     * Public list of active ticket categories for the widget form.
     */
    public function categories(): JsonResponse
    {
        $categories = TicketCategory::query()
            ->where('active', true)
            ->with(['fields' => fn ($q) => $q->where('is_visible', true)->ordered()])
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'color', 'required_fields']);

        $data = $categories->map(fn ($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'icon' => $cat->icon,
            'color' => $cat->color,
            'required_fields' => $cat->required_fields ?? [],
            'fields' => $cat->fields->map(fn ($f) => [
                'id' => $f->id,
                'key' => $f->key,
                'type' => $f->type,
                'label' => $f->label,
                'placeholder' => $f->placeholder,
                'help_text' => $f->help_text,
                'is_required' => $f->is_required,
                'width' => $f->width,
                'options' => $f->options ?? [],
            ]),
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /hd/api/tickets?email=...&website_token=...
     *
     * Sin prueba de propiedad del email (este endpoint es publico, sin auth),
     * NO se puede devolver el contenido de los tickets — solo un resumen +
     * un enlace al portal seguro (magic-link) donde el cliente sí demuestra
     * ser el dueño del email antes de ver ningun detalle.
     */
    public function index(Request $request): JsonResponse
    {
        $web = Web::where('website_token', $request->query('website_token'))->first();

        if (! $web) {
            return response()->json([
                'success' => false,
                'message' => 'Token de widget no válido.',
            ], 422);
        }

        $email = $request->query('email');

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'El campo email es obligatorio y debe ser un correo válido.',
            ], 422);
        }

        // Respuesta neutra e idéntica exista o no el cliente/tickets: devolver
        // has_tickets/open_count convertía este endpoint público en un oráculo
        // de enumeración de clientes (email registrado vs no). El visitante
        // demuestra la propiedad del email en el portal (magic-link) para ver
        // sus tickets reales.
        return response()->json([
            'success' => true,
            'message' => 'Si tienes solicitudes asociadas a este correo, accede al portal para consultarlas.',
            'login_url' => route('portal.login'),
        ]);
    }

    /**
     * POST /hd/api/tickets
     *
     * Public ticket creation from the widget visitors. Validates the website
     * token, finds-or-creates the Customer by email and persists the ticket
     * with source=widget so agents can recognise the origin.
     */
    public function store(StoreWidgetTicketRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customer = Customer::firstOrCreate(
            ['email' => $validated['customer_email']],
            ['name' => $validated['customer_name'] ?? $validated['customer_email']]
        );

        $ticket = $this->ticketService->createTicket([
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'] ?? null,
            'priority' => $validated['priority'] ?? 'normal',
            'customer_id' => $customer->id,
            'source' => 'widget',
        ]);

        if ($request->hasFile('attachments')) {
            $this->ticketService->storeAttachments($request->file('attachments'), $ticket->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket creado correctamente.',
            'data' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
            ],
        ], 201);
    }
}
