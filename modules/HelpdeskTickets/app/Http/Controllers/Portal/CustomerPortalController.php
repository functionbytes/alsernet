<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Portal;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Http\Requests\Portal\PortalLoginRequest;
use Modules\HelpdeskTickets\Http\Requests\Portal\RateTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Portal\ReplyTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Portal\StoreTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Portal\UpdateAccountRequest;
use Modules\HelpdeskTickets\Mail\PortalMagicLinkMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketMessage;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketService;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;

class CustomerPortalController extends Controller
{
    /** GET /portal — redirect to login or tickets */
    public function index(): RedirectResponse
    {
        abort_if(! helpdesk_tickets_enabled(), 404);

        if ($this->getAuthenticatedCustomer()) {
            return redirect()->route('portal.tickets');
        }

        return redirect()->route('portal.login');
    }

    /** GET /portal/login */
    public function showLogin(): View
    {
        return view('helpdesktickets::portal.login');
    }

    /** POST /portal/login — send magic link */
    public function login(PortalLoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $throttleKey = 'portal-login:'.md5($validated['email']).':'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['email' => "Too many attempts. Please try again in {$seconds} seconds."]);
        }

        RateLimiter::hit($throttleKey, 300);

        $customer = Customer::where('email', $validated['email'])->first();

        if ($customer) {
            $token = $customer->generatePortalToken();
            [$subject, $content] = TicketMailRenderer::render(
                'helpdesk_tickets.portal_magic_link',
                [
                    'CUSTOMER_NAME' => e($customer->name),
                    'PORTAL_URL' => url('/portal/auth/'.$token),
                ],
                'Your portal login link',
            );
            Mail::to($customer->email)->queue(new PortalMagicLinkMail($customer, $subject, $content));
        }

        return back()->with('status', 'If this email is registered, a login link has been sent.');
    }

    /** GET /portal/auth/{token} — authenticate via magic link */
    public function authenticate(string $token): RedirectResponse
    {
        $throttleKey = 'portal-auth:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            return redirect()->route('portal.login')->withErrors(['email' => 'Too many authentication attempts.']);
        }

        RateLimiter::hit($throttleKey, 600);

        // generatePortalToken() guarda el HASH sha256 del token (nunca el
        // cleartext); findByPortalToken() hace el hash antes de comparar.
        // Comparar el cleartext contra la columna nunca autenticaría a nadie.
        $customer = Customer::findByPortalToken($token);

        if (! $customer) {
            Log::warning('Portal: invalid or expired token attempt', [
                'ip' => request()->ip(),
                'token_prefix' => substr($token, 0, 8).'...',
            ]);

            return redirect()->route('portal.login')->with('error', 'The login link has expired or is invalid.');
        }

        RateLimiter::clear($throttleKey);

        $customer->update([
            'portal_token' => null,
            'portal_token_expires_at' => null,
        ]);

        session(['portal_customer_id' => $customer->id]);

        return redirect()->route('portal.tickets');
    }

    /** POST /portal/logout */
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('portal_customer_id');

        return redirect()->route('portal.login');
    }

    /** GET /portal/tickets */
    public function tickets(): View|RedirectResponse
    {
        $customerOrRedirect = $this->getAuthenticatedCustomerOrFail();

        if ($customerOrRedirect instanceof RedirectResponse) {
            return $customerOrRedirect;
        }

        $customer = $customerOrRedirect;

        $tickets = Ticket::where('customer_id', $customer->id)
            ->with(['status:id,name,color', 'category:id,name'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('helpdesktickets::portal.tickets.index', compact('customer', 'tickets'));
    }

    /** GET /portal/tickets/{ticketNumber} */
    public function showTicket(string $ticketNumber): View|RedirectResponse
    {
        $customerOrRedirect = $this->getAuthenticatedCustomerOrFail();

        if ($customerOrRedirect instanceof RedirectResponse) {
            return $customerOrRedirect;
        }

        $customer = $customerOrRedirect;

        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        // El stream del portal son TicketItems tipo "message" (sin notas
        // internas). Los adjuntos viven aparte: TicketService::storeAttachments
        // crea TicketMessage + TicketAttachment (FK ticket_message_id), así que
        // se cargan desde ahí y no como relación del item.
        $messages = $ticket->messages()
            ->where('is_internal', false)
            ->with(['user:id,firstname,lastname'])
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        $attachments = TicketMessage::where('ticket_id', $ticket->id)
            ->with('attachments')
            ->get()
            ->flatMap(fn (TicketMessage $msg) => $msg->attachments);

        return view('helpdesktickets::portal.tickets.show', compact('customer', 'ticket', 'messages', 'attachments'));
    }

    /** POST /portal/tickets/{ticketNumber}/reply */
    public function replyToTicket(ReplyTicketRequest $request, string $ticketNumber): RedirectResponse
    {
        $customerOrRedirect = $this->getAuthenticatedCustomerOrFail();

        if ($customerOrRedirect instanceof RedirectResponse) {
            return $customerOrRedirect;
        }

        $customer = $customerOrRedirect;

        $validated = $request->validated();

        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $item = $ticket->items()->create([
            'type' => 'message',
            'author_id' => $customer->id,
            'body' => $validated['message'],
            'is_internal' => false,
        ]);

        if ($request->hasFile('attachments')) {
            $this->storeAttachments($request->file('attachments'), $ticket->id);
        }

        $ticket->updated_at = now();
        $ticket->saveQuietly();

        return back()->with('status', 'Your reply has been sent.');
    }

    /** GET /portal/tickets/create */
    public function createTicket(): View|RedirectResponse
    {
        $customerOrRedirect = $this->getAuthenticatedCustomerOrFail();

        if ($customerOrRedirect instanceof RedirectResponse) {
            return $customerOrRedirect;
        }

        $customer = $customerOrRedirect;

        $categories = TicketCategory::active()->ordered()->get(['id', 'name']);
        $statuses = TicketStatus::active()->ordered()->get(['id', 'name', 'color']);

        return view('helpdesktickets::portal.tickets.create', compact('customer', 'categories', 'statuses'));
    }

    /** POST /portal/tickets */
    public function storeTicket(StoreTicketRequest $request): RedirectResponse
    {
        $customerOrRedirect = $this->getAuthenticatedCustomerOrFail();

        if ($customerOrRedirect instanceof RedirectResponse) {
            return $customerOrRedirect;
        }

        $customer = $customerOrRedirect;

        $validated = $request->validated();

        $defaultStatus = TicketStatus::where('is_default', true)->first();

        // Wrap creation in a transaction so the lockForUpdate in
        // generateTicketNumber() is effective and numbers never collide.
        $ticket = DB::transaction(function () use ($validated, $customer, $defaultStatus, $request) {
            $ticket = Ticket::create([
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'category_id' => $validated['category_id'] ?? null,
                'priority' => $validated['priority'] ?? 'normal',
                'status_id' => $defaultStatus?->id ?? 1,
                'source' => 'portal',
            ]);

            if ($request->hasFile('attachments')) {
                $this->storeAttachments($request->file('attachments'), $ticket->id);
            }

            return $ticket;
        });

        return redirect()->route('portal.tickets.show', $ticket->ticket_number)
            ->with('status', 'Your ticket has been created.');
    }

    /** GET /portal/account — show account settings form */
    public function account(): View|RedirectResponse
    {
        $customer = $this->getAuthenticatedCustomerOrFail();

        if ($customer instanceof RedirectResponse) {
            return $customer;
        }

        return view('helpdesktickets::portal.account', compact('customer'));
    }

    /** PUT /portal/account — update name and phone */
    public function updateAccount(UpdateAccountRequest $request): RedirectResponse
    {
        $customer = $this->getAuthenticatedCustomerOrFail();

        if ($customer instanceof RedirectResponse) {
            return $customer;
        }

        $customer->update($request->validated());

        return back()->with('success', 'Account updated successfully.');
    }

    /** POST /portal/tickets/{ticketNumber}/rate */
    public function rateTicket(RateTicketRequest $request, string $ticketNumber): RedirectResponse
    {
        $customerOrRedirect = $this->getAuthenticatedCustomerOrFail();

        if ($customerOrRedirect instanceof RedirectResponse) {
            return $customerOrRedirect;
        }

        $customer = $customerOrRedirect;

        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->where('customer_id', $customer->id)
            ->whereNotNull('closed_at')
            ->whereNull('rated_at')
            ->firstOrFail();

        $validated = $request->validated();

        $ticket->update([
            'rating' => $validated['rating'],
            'rating_comment' => $validated['rating_comment'] ?? null,
            'rated_at' => now(),
        ]);

        return back()->with('success', 'Thank you for your feedback!');
    }

    /** GET /portal/tickets/{ticketNumber}/rate/{rating} — rate from email link (no session required) */
    public function rateTicketFromEmail(Request $request, string $ticketNumber, int $rating): RedirectResponse
    {
        if ($rating < 1 || $rating > 5) {
            return redirect()->route('portal.login')->withErrors(['error' => 'Puntuacion invalida.']);
        }

        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->whereNotNull('closed_at')
            ->whereNull('rated_at')
            ->firstOrFail();

        $ticket->update([
            'rating' => $rating,
            'rated_at' => now(),
        ]);

        return redirect()->route('portal.login')
            ->with('status', 'Gracias por tu valoracion! Tu opinion nos ayuda a mejorar.');
    }

    /**
     * Store uploaded files as TicketAttachments linked via a TicketMessage.
     * Delegates to the shared TicketService implementation keeping the 5 MB
     * per-file cap the portal always applied.
     *
     * @param  UploadedFile[]  $files
     */
    private function storeAttachments(array $files, int $ticketId): void
    {
        app(TicketService::class)->storeAttachments($files, $ticketId, [], 5 * 1024 * 1024);
    }

    private function getAuthenticatedCustomer(): ?Customer
    {
        $id = session('portal_customer_id');

        if (! $id) {
            return null;
        }

        return Customer::find($id);
    }

    /**
     * Get authenticated customer or return a redirect response.
     * Also checks for banned status.
     */
    private function getAuthenticatedCustomerOrFail(): Customer|RedirectResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (! $customer) {
            return redirect()->route('portal.login');
        }

        if ($customer->banned_at !== null) {
            session()->forget('portal_customer_id');

            return redirect()->route('portal.login')
                ->withErrors(['email' => 'Your account has been suspended.']);
        }

        return $customer;
    }
}
