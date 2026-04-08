<?php

namespace Modules\Helpdesk\Http\Controllers\Portal;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Modules\Helpdesk\Mail\PortalMagicLinkMail;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketAttachment;
use Modules\Helpdesk\Models\TicketCategory;
use Modules\Helpdesk\Models\TicketMessage;
use Modules\Helpdesk\Models\TicketStatus;

class CustomerPortalController extends Controller
{
    /** GET /portal — redirect to login or tickets */
    public function index(): RedirectResponse
    {
        if ($this->getAuthenticatedCustomer()) {
            return redirect()->route('portal.tickets');
        }

        return redirect()->route('portal.login');
    }

    /** GET /portal/login */
    public function showLogin(): View
    {
        return view('helpdesk::portal.login');
    }

    /** POST /portal/login — send magic link */
    public function login(Request $request): RedirectResponse
    {
        $throttleKey = 'portal-login:'.md5($request->input('email', '')).':'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['email' => "Too many attempts. Please try again in {$seconds} seconds."]);
        }

        RateLimiter::hit($throttleKey, 300);

        $validated = $request->validate(['email' => ['required', 'email']]);

        $customer = Customer::where('email', $validated['email'])->first();

        if ($customer) {
            $token = $customer->generatePortalToken();
            Mail::to($customer->email)->queue(new PortalMagicLinkMail($customer, $token));
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

        $customer = Customer::where('portal_token', $token)
            ->where('portal_token_expires_at', '>', now())
            ->first();

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

        return view('helpdesk::portal.tickets.index', compact('customer', 'tickets'));
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

        $messages = $ticket->messages()->with('user:id,name')->orderBy('created_at')->get();

        $attachments = TicketMessage::where('ticket_id', $ticket->id)
            ->with('attachments')
            ->get()
            ->flatMap(fn (TicketMessage $msg) => $msg->attachments);

        return view('helpdesk::portal.tickets.show', compact('customer', 'ticket', 'messages', 'attachments'));
    }

    /** POST /portal/tickets/{ticketNumber}/reply */
    public function replyToTicket(Request $request, string $ticketNumber): RedirectResponse
    {
        $customerOrRedirect = $this->getAuthenticatedCustomerOrFail();

        if ($customerOrRedirect instanceof RedirectResponse) {
            return $customerOrRedirect;
        }

        $customer = $customerOrRedirect;

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'attachments.*' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip'],
        ]);

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
            $this->storeAttachments($request->file('attachments'), $ticket->id, $customer, $item->id);
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

        return view('helpdesk::portal.tickets.create', compact('customer', 'categories', 'statuses'));
    }

    /** POST /portal/tickets */
    public function storeTicket(Request $request): RedirectResponse
    {
        $customerOrRedirect = $this->getAuthenticatedCustomerOrFail();

        if ($customerOrRedirect instanceof RedirectResponse) {
            return $customerOrRedirect;
        }

        $customer = $customerOrRedirect;

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip'],
        ]);

        $defaultStatus = TicketStatus::where('is_default', true)->first();

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
            $this->storeAttachments($request->file('attachments'), $ticket->id, $customer);
        }

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

        return view('helpdesk::portal.account', compact('customer'));
    }

    /** PUT /portal/account — update name and phone */
    public function updateAccount(Request $request): RedirectResponse
    {
        $customer = $this->getAuthenticatedCustomerOrFail();

        if ($customer instanceof RedirectResponse) {
            return $customer;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $customer->update($validated);

        return back()->with('success', 'Account updated successfully.');
    }

    /** POST /portal/tickets/{ticketNumber}/rate */
    public function rateTicket(Request $request, string $ticketNumber): RedirectResponse
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

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'rating_comment' => ['nullable', 'string', 'max:500'],
        ]);

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
     *
     * @param  UploadedFile[]  $files
     */
    private function storeAttachments(array $files, int $ticketId, Customer $customer, ?int $ticketItemId = null): void
    {
        $message = TicketMessage::create([
            'ticket_id' => $ticketId,
            'is_internal' => false,
        ]);

        foreach ($files as $file) {
            if (! $file->isValid() || $file->getSize() > 5 * 1024 * 1024) {
                continue;
            }

            $path = $file->store(
                config('helpdesk.attachments.path', 'helpdesk/attachments'),
                config('helpdesk.attachments.disk', 'local')
            );

            TicketAttachment::create([
                'ticket_message_id' => $message->id,
                'filename' => $file->getClientOriginalName(),
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
            ]);
        }
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
