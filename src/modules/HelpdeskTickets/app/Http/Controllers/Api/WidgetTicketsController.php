<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskTickets\Http\Requests\Api\StoreWidgetTicketRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketMessage;
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
     * List tickets for a customer identified by email. Returns an empty list
     * when the customer does not exist to avoid revealing email enumeration.
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

        $customer = Customer::where('email', $email)->first();

        if (! $customer) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $tickets = Ticket::where('customer_id', $customer->id)
            ->with(['status:id,name,color,is_open', 'category:id,name,icon,color'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'ticket_number', 'subject', 'priority', 'source', 'status_id', 'category_id', 'created_at', 'closed_at']);

        $data = $tickets->map(fn ($t) => [
            'id' => $t->id,
            'ticket_number' => $t->ticket_number,
            'subject' => $t->subject,
            'priority' => $t->priority,
            'created_at' => $t->created_at->toIso8601String(),
            'closed_at' => $t->closed_at?->toIso8601String(),
            'status' => $t->status ? ['name' => $t->status->name, 'color' => $t->status->color, 'is_open' => $t->status->is_open] : null,
            'category' => $t->category ? ['name' => $t->category->name, 'icon' => $t->category->icon] : null,
        ]);

        return response()->json(['success' => true, 'data' => $data]);
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
            $this->storeAttachments($request->file('attachments'), $ticket->id);
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

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeAttachments(array $files, int $ticketId): void
    {
        $message = TicketMessage::create([
            'ticket_id' => $ticketId,
            'is_internal' => false,
        ]);

        $disk = config('helpdesk.attachments.disk', 'local');
        $path = config('helpdesk.attachments.path', 'helpdesk/attachments');

        foreach ($files as $file) {
            if (! $file->isValid()) {
                continue;
            }

            $stored = $file->store($path, $disk);

            TicketAttachment::create([
                'ticket_message_id' => $message->id,
                'filename' => $file->getClientOriginalName(),
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $stored,
            ]);
        }
    }
}
