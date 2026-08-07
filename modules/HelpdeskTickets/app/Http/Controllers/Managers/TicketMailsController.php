<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\HelpdeskTickets\Http\Requests\Managers\BulkTicketMailRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\ComposeTicketMailRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Modules\HelpdeskTickets\Services\TicketMailDispatcher;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bandeja "Emails enviados" — vista global de helpdesk_ticket_mails (todos
 * los tickets), a diferencia del widget de hasta 30 filas que ya existía
 * dentro de la ficha de un ticket concreto (TicketsCrudController::showFull).
 */
class TicketMailsController extends Controller
{
    public function __construct(
        private readonly TicketMailDispatcher $dispatcher,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', TicketMail::class);

        abort_if(! helpdesk_tickets_enabled(), 404);

        $mails = $this->filteredQuery($request)->paginate(50)->appends($request->query());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $mails->getCollection()->map(fn (TicketMail $m) => $m->toListRow())->values(),
                'meta' => [
                    'current_page' => $mails->currentPage(),
                    'last_page' => $mails->lastPage(),
                    'total' => $mails->total(),
                ],
                // Los KPIs de arriba (enviados/rebotados/tasa/programados) se
                // hidratan en el primer render SSR pero refetch() vuelve a
                // pedir esta misma ruta por AJAX en cada envío/reenvío/acción
                // masiva — sin esto se quedaban congelados con los valores
                // del primer GET (bug real encontrado en QA manual).
                'stats' => $this->stats(),
            ]);
        }

        return view('helpdesktickets::managers.emails.index', [
            'mails' => $mails,
            'categories' => CatalogCacheService::categories(),
            'agents' => CatalogCacheService::agents(),
            'stats' => $this->stats(),
            'filters' => $request->only(['view', 'search', 'origin', 'category', 'agent', 'from', 'to']),
        ]);
    }

    public function data(TicketMail $mail): JsonResponse
    {
        $this->authorize('view', $mail);

        $mail->load(['ticket.customer', 'ticket.status', 'ticket.category', 'ticket.assignee', 'user', 'category']);

        $thread = TicketMail::where('ticket_id', $mail->ticket_id)->oldest()->get();

        return response()->json([
            'success' => true,
            'data' => array_merge($mail->toListRow(), [
                'from' => $mail->from,
                'cc' => $mail->cc,
                'bcc' => $mail->bcc,
                'message_id' => $mail->message_id,
                'in_reply_to' => $mail->in_reply_to,
                'body_html' => $mail->body_html,
                'body_text' => $mail->body_text,
                'attachments' => $mail->attachments ?? [],
                'delivery_error' => $mail->delivery_error,
                'thread' => $thread->map(fn (TicketMail $m) => $m->toListRow())->values(),
                'ticket' => $this->mapTicket($mail->ticket),
            ]),
        ]);
    }

    public function store(ComposeTicketMailRequest $request): JsonResponse
    {
        $ticket = Ticket::findOrFail($request->integer('ticket_id'));

        $this->authorize('view', $ticket);
        $this->authorize('create', TicketMail::class);

        $validated = $request->validated();

        [$attachmentFiles, $attachmentMeta] = $this->storeAttachments($request, $ticket);

        $scheduledAt = $validated['scheduled_at'] ?? null;

        $mail = TicketMail::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'category_id' => $validated['category_id'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'direction' => 'outbound',
            'from' => config('mail.from.address'),
            'to' => $validated['to'],
            'cc' => isset($validated['cc']) ? implode(',', $validated['cc']) : null,
            'bcc' => isset($validated['bcc']) ? implode(',', $validated['bcc']) : null,
            'subject' => $validated['subject'],
            'body_html' => $validated['body'],
            'body_text' => strip_tags($validated['body']),
            'attachments' => $attachmentMeta ?: null,
            'status' => $scheduledAt ? 'scheduled' : 'pending',
            'scheduled_at' => $scheduledAt,
        ]);

        if ($scheduledAt) {
            return response()->json([
                'success' => true,
                'message' => 'Email programado correctamente.',
                'data' => $mail->fresh()->toListRow(),
            ], 201);
        }

        $this->dispatcher->send($mail, $ticket, $validated['cc'] ?? [], $validated['bcc'] ?? [], $attachmentFiles);

        return response()->json([
            'success' => true,
            'message' => 'Email enviado correctamente.',
            'data' => $mail->fresh()->toListRow(),
        ], 201);
    }

    public function resend(Request $request, TicketMail $mail): JsonResponse
    {
        $this->authorize('resend', $mail);

        $ticket = $mail->ticket;
        abort_if(! $ticket, 404);

        $to = $request->filled('to') ? $request->string('to')->toString() : $mail->to;

        $newMail = $this->createResendCopy($mail, $ticket, $to);

        $this->dispatcher->send($newMail, $ticket, [], [], $this->dispatcher->resendableAttachments($mail));

        return response()->json([
            'success' => true,
            'message' => "Email reenviado a {$to}.",
            'data' => $newMail->fresh()->toListRow(),
        ]);
    }

    /**
     * Acciones masivas: reenviar o cancelar programados. Autorización por
     * email (igual que BulkTicketsController): los que el usuario no puede
     * actuar se omiten en vez de abortar toda la operación.
     */
    public function bulk(BulkTicketMailRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];

        $mails = TicketMail::whereIn('id', $validated['mail_ids'])->get();

        $ability = $action === 'resend' ? 'resend' : 'delete';
        [$authorized, $skipped] = $mails->partition(
            fn (TicketMail $m) => $request->user()->can($ability, $m)
        );

        if ($authorized->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para actuar sobre los emails seleccionados.',
                'skipped_ids' => $skipped->pluck('id')->values(),
            ], 403);
        }

        $count = match ($action) {
            'resend' => $this->bulkResend($authorized),
            'cancel_scheduled' => $authorized->filter(fn (TicketMail $m) => $m->status === 'scheduled')
                ->each(fn (TicketMail $m) => $m->delete())
                ->count(),
        };

        $message = "{$count} emails procesados.";
        if ($skipped->isNotEmpty()) {
            $message .= " {$skipped->count()} omitidos por falta de permiso.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'skipped_ids' => $skipped->pluck('id')->values(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', TicketMail::class);

        $query = $this->filteredQuery($request);

        if ($request->filled('ids')) {
            $query->whereIn('id', (array) $request->input('ids'));
        }

        $rows = $query->limit(5000)->cursor();
        $filename = 'emails-enviados-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Ticket', 'Asunto', 'Para', 'Estado', 'Agente', 'Categoría', 'Enviado', 'Programado']);

            foreach ($rows as $mail) {
                fputcsv($out, [
                    $mail->ticket?->ticket_number,
                    $mail->subject,
                    $mail->to,
                    $mail->status_label,
                    $mail->user ? trim("{$mail->user->firstname} {$mail->user->lastname}") : '',
                    $mail->category?->name,
                    $mail->sent_at?->format('Y-m-d H:i:s'),
                    $mail->scheduled_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(TicketMail $mail): JsonResponse
    {
        $this->authorize('delete', $mail);

        $mail->delete();

        return response()->json(['success' => true, 'message' => 'Email eliminado.']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function filteredQuery(Request $request): Builder
    {
        $query = TicketMail::query()
            ->with([
                'ticket:id,ticket_number,subject,customer_id,source,category_id,assignee_id',
                'ticket.customer:id,name,email',
                'user:id,firstname,lastname',
                'category:id,name',
            ])
            ->latest();

        match ($request->string('view', 'outbound')->toString()) {
            'scheduled' => $query->scheduled(),
            'bounced' => $query->bounced(),
            'failed' => $query->failed(),
            'inbound' => $query->inbound(),
            default => $query->outbound()->where('status', '!=', 'scheduled'),
        };

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('subject', 'like', "%{$search}%")
                ->orWhere('to', 'like', "%{$search}%")
                ->orWhere('message_id', 'like', "%{$search}%")
                ->orWhereHas('ticket', fn ($t) => $t->where('ticket_number', 'like', "%{$search}%"))
            );
        }

        if ($request->filled('origin')) {
            $origin = $request->string('origin')->toString();
            $query->whereHas('ticket', fn ($t) => $t->where('source', $origin));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        if ($request->filled('agent')) {
            $query->where('user_id', $request->integer('agent'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from')->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        return $query;
    }

    /**
     * @return array{total: int, bounced: int, bounce_rate: float, scheduled: int}
     */
    private function stats(): array
    {
        $total = TicketMail::outbound()->count();
        $bounced = TicketMail::outbound()->bounced()->count();
        $scheduled = TicketMail::scheduled()->count();

        return [
            'total' => $total,
            'bounced' => $bounced,
            'bounce_rate' => $total > 0 ? round($bounced / $total * 100, 1) : 0.0,
            'scheduled' => $scheduled,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapTicket(?Ticket $ticket): ?array
    {
        if (! $ticket) {
            return null;
        }

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'status' => $ticket->status?->name,
            'customer' => $ticket->customer ? [
                'id' => $ticket->customer->id,
                'name' => $ticket->customer->name,
                'email' => $ticket->customer->email,
                'phone' => $ticket->customer->phone,
            ] : null,
            'assignee' => $ticket->assignee ? trim("{$ticket->assignee->firstname} {$ticket->assignee->lastname}") : null,
            'url_full' => route('manager.helpdesk.tickets.show-full', $ticket),
        ];
    }

    /**
     * @return array{0: array<int, array{disk: string, path: string, name: string}>, 1: array<int, array{name: string, path: string, disk: string, size: int}>}
     */
    private function storeAttachments(ComposeTicketMailRequest $request, Ticket $ticket): array
    {
        if (! $request->hasFile('attachments')) {
            return [[], []];
        }

        $disk = config('helpdesk.attachments.disk', 'local');
        $files = [];
        $meta = [];

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('helpdesk/tickets/'.$ticket->id.'/emails', $disk);
            $name = $file->getClientOriginalName();

            $files[] = ['disk' => $disk, 'path' => $path, 'name' => $name];
            $meta[] = ['name' => $name, 'path' => $path, 'disk' => $disk, 'size' => $file->getSize()];
        }

        return [$files, $meta];
    }

    /**
     * @param  Collection<int, TicketMail>  $mails
     */
    private function bulkResend($mails): int
    {
        $count = 0;

        foreach ($mails as $mail) {
            $ticket = $mail->ticket;
            if (! $ticket) {
                continue;
            }

            // Igual que resend(): crear una fila NUEVA en vez de reutilizar
            // $mail. dispatcher->send() llama markAsSent() sobre lo que le
            // pasemos — si le pasábamos el original, un reenvío masivo de un
            // email "rebotado"/"fallido" lo mutaba a "enviado" en el sitio,
            // borrando el historial del fallo (bug real encontrado en QA
            // manual de esta pantalla).
            $newMail = $this->createResendCopy($mail, $ticket, $mail->to);
            $this->dispatcher->send($newMail, $ticket, [], [], $this->dispatcher->resendableAttachments($mail));
            $count++;
        }

        return $count;
    }

    private function createResendCopy(TicketMail $mail, Ticket $ticket, string $to): TicketMail
    {
        return TicketMail::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'category_id' => $mail->category_id,
            'tags' => $mail->tags,
            'direction' => 'outbound',
            'from' => config('mail.from.address'),
            'to' => $to,
            'subject' => $mail->subject,
            'body_html' => $mail->body_html,
            'body_text' => $mail->body_text,
            'attachments' => $mail->attachments,
            'in_reply_to' => $mail->message_id,
            'status' => 'pending',
        ]);
    }
}
