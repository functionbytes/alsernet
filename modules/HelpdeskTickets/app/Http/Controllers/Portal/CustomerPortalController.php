<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Portal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Http\Controllers\FeedbackController;
use Modules\HelpdeskTickets\Http\Requests\Portal\PortalLoginRequest;
use Modules\HelpdeskTickets\Http\Requests\Portal\RateTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Portal\ReplyTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Portal\StoreTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Portal\UpdateAccountRequest;
use Modules\HelpdeskTickets\Mail\PortalMagicLinkMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketMessage;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketDeflectionService;
use Modules\HelpdeskTickets\Services\TicketService;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerPortalController extends Controller
{
    /**
     * El interruptor del módulo se comprobaba solo en index(), así que con
     * HelpdeskTickets desactivado seguían operativos /portal/login,
     * /portal/tickets y el alta de tickets — solo dejaba de funcionar la
     * redirección de la raíz. En el constructor cubre las catorce acciones,
     * mismo patrón que Dev\EmailTestController.
     */
    public function __construct()
    {
        abort_if(! helpdesk_tickets_enabled(), 404);
    }

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
        return view('helpdesktickets::portal.login');
    }

    /** POST /portal/login — send magic link */
    public function login(PortalLoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $throttleKey = 'portal-login:'.md5($validated['email']).':'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['email' => __('helpdesktickets::helpdesktickets.portal.login_throttled', ['seconds' => $seconds])]);
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

        return back()->with('status', __('helpdesktickets::helpdesktickets.portal.login_link_sent'));
    }

    /** GET /portal/auth/{token} — authenticate via magic link */
    public function authenticate(string $token): RedirectResponse
    {
        $throttleKey = 'portal-auth:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            return redirect()->route('portal.login')->withErrors(['email' => __('helpdesktickets::helpdesktickets.portal.auth_throttled')]);
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

            return redirect()->route('portal.login')->with('error', __('helpdesktickets::helpdesktickets.portal.link_expired'));
        }

        RateLimiter::clear($throttleKey);

        $customer->update([
            'portal_token' => null,
            'portal_token_expires_at' => null,
        ]);

        // regenerate() antes de elevar la sesión a autenticada: sin cambiar el
        // identificador de sesión, quien hubiera fijado previamente el de la
        // víctima se queda dentro con ella (fijación de sesión). Es lo que hace
        // el guard de Laravel en un login normal; aquí la sesión se eleva a
        // mano y faltaba.
        request()->session()->regenerate();

        session(['portal_customer_id' => $customer->id]);

        return redirect()->route('portal.tickets');
    }

    /** POST /portal/logout */
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('portal_customer_id');

        // invalidate() + regenerateToken(): forget() solo quita la clave y deja
        // viva la sesión y su token CSRF.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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

    /**
     * GET /portal/tickets/{ticketNumber}/attachments/{attachment}
     *
     * Descarga de un adjunto del propio ticket. El portal listaba los ficheros
     * por nombre pero no había forma de recuperarlos: el disco es privado y no
     * existía ninguna ruta que sirviera TicketAttachment.
     */
    public function downloadAttachment(string $ticketNumber, TicketAttachment $attachment): StreamedResponse|RedirectResponse
    {
        $customerOrRedirect = $this->getAuthenticatedCustomerOrFail();

        if ($customerOrRedirect instanceof RedirectResponse) {
            return $customerOrRedirect;
        }

        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->where('customer_id', $customerOrRedirect->id)
            ->firstOrFail();

        // Doble comprobación: el ticket es del cliente en sesión (arriba) y el
        // adjunto cuelga de ese ticket (aquí). Sin la segunda, cambiar el id
        // del adjunto en la URL serviría ficheros de otros clientes.
        abort_if($attachment->message?->ticket_id !== $ticket->id, 404);

        $disk = config('helpdesk.attachments.disk', 'local');

        abort_unless(Storage::disk($disk)->exists((string) $attachment->path), 404);

        return Storage::disk($disk)->download(
            $attachment->path,
            $attachment->original_filename ?: $attachment->filename ?: basename((string) $attachment->path),
        );
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

        app(TicketService::class)->reopenIfCustomerCanReopen($ticket);

        $item = $ticket->items()->create([
            'type' => 'message',
            'author_id' => $customer->id,
            'body' => $validated['message'],
            'is_internal' => false,
        ]);

        if ($request->hasFile('attachments')) {
            $this->storeAttachments($request->file('attachments'), $ticket->id);
        }

        // Sin esto, una respuesta del cliente por el portal era invisible para
        // el equipo: no corría ningún listener de MessageAdded
        // (UpdateTicketLastActivity, RunAiSentimentAnalysis, aviso al agente
        // asignado) y el saveQuietly() de antes se saltaba hasta el
        // TicketObserver. La vía de correo (FetchTicketEmailsJob) sí lo hacía
        // desde siempre; esta era la única entrada de cliente que no.
        MessageAdded::dispatch($item);

        // El reloj de SLA se pausa al pasar el ticket a un estado con
        // stops_sla_timer ("en espera del cliente"). Si el cliente ya ha
        // respondido, la pelota vuelve al agente y el reloj tiene que correr —
        // antes se quedaba pausado indefinidamente porque nadie lo reanudaba
        // por esta vía.
        if ($ticket->isSlaPaused()) {
            $ticket->resumeSla();
        }

        $ticket->update(['last_message_at' => now()]);

        return back()->with('status', __('helpdesktickets::helpdesktickets.portal.reply_sent'));
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
    /**
     * Artículos de ayuda que podrían resolver la duda antes de abrir el ticket.
     *
     * Sugiere y se aparta: nunca impide crear el ticket, y una lista vacía —el
     * caso normal cuando ninguno encaja— simplemente no muestra nada. Ver
     * TicketDeflectionService.
     */
    public function suggestArticles(Request $request, TicketDeflectionService $deflection): JsonResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        if (! $customer) {
            return response()->json(['success' => false, 'articles' => []], 401);
        }

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        return response()->json([
            'success' => true,
            'articles' => $deflection->suggest(
                (string) ($validated['subject'] ?? ''),
                (string) ($validated['description'] ?? ''),
            ),
        ]);
    }

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
            ->with('status', __('helpdesktickets::helpdesktickets.portal.ticket_created'));
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

        return back()->with('success', __('helpdesktickets::helpdesktickets.portal.account_updated'));
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

        return back()->with('success', __('helpdesktickets::helpdesktickets.portal.feedback_thanks'));
    }

    /** GET /portal/tickets/{ticketNumber}/rate/{rating} — rate from email link (no session required) */
    public function rateTicketFromEmail(Request $request, string $ticketNumber, int $rating): RedirectResponse
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->whereNotNull('closed_at')
            ->firstOrFail();

        // Un doble clic en el botón del correo (o el mismo enlace abierto dos
        // veces) caía antes en whereNull('rated_at')->firstOrFail() -> 404
        // crudo de Laravel. Ahora simplemente no reescribe una valoración ya
        // guardada y sigue igual hacia la página de agradecimiento.
        if (! $ticket->rated_at && $rating >= 1 && $rating <= 5) {
            $ticket->update([
                'rating' => $rating,
                'rated_at' => now(),
            ]);
        }

        // Antes redirigía a portal.login con un mensaje flash "gracias" --
        // confuso: el cliente hacía clic en una puntuación y aterrizaba en un
        // formulario de inicio de sesión (reportado por el usuario, 3-sep-2026).
        // FeedbackController::show() ya tiene la pantalla de agradecimiento
        // correcta (sin login) para cuando rated_at está seteado, así que se
        // reusa en vez de duplicarla.
        return redirect()->to(FeedbackController::signedShowUrl($ticket));
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
                ->withErrors(['email' => __('helpdesktickets::helpdesktickets.portal.account_suspended')]);
        }

        return $customer;
    }
}
