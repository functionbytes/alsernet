<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// tickets.user_reopen_issue/user_reopen_time viven en helpdesk_settings
// (Modules\Helpdesk\Models\Setting) — distinta de Modules\Core\Models\Setting
// (tabla `settings`, usada por ejemplo para incoming_email). Alias explícito
// para no confundir las dos clases "Setting".
use Modules\Helpdesk\Models\Setting as HelpdeskGeneralSetting;
use Modules\Helpdesk\Services\HelpdeskSettings;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Events\TicketReopened;
use Modules\HelpdeskTickets\Events\TicketUpdated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Models\TicketMessage;
use Modules\HelpdeskTickets\Models\TicketStatus;

class TicketService
{
    // Antes recibía SlaService por inyección y no lo usaba en ningún método.
    // El SLA se calcula en TicketObserver::created() vía
    // Ticket::calculateSlaDueDates(), no desde aquí.

    /**
     * Create a new ticket
     */
    public function createTicket(array $data): Ticket
    {
        try {
            return DB::transaction(function () use ($data) {
                $data['ticket_number'] = $this->generateTicketNumber();

                if (! isset($data['priority_id'])) {
                    $data['priority_id'] = 2;
                }

                if (! isset($data['status_id'])) {
                    $defaultStatus = TicketStatus::where('slug', 'new')->first();
                    $data['status_id'] = $defaultStatus?->id ?? 1;
                }

                $ticket = Ticket::create($data);

                // El SLA lo calcula TicketObserver::created vía
                // Ticket::calculateSlaDueDates() con TicketSlaPolicy (la que
                // configura la UI). El antiguo calculateDueDate() escribía a
                // `due_at` —columna inexistente— vía el modelo SlaPolicy
                // @deprecated: era un no-op. Se elimina para no consultar la
                // tabla obsoleta ni divergir del motor real.

                event(new TicketCreated($ticket));

                Log::info('Ticket created', [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'customer_email' => $ticket->customer_email,
                ]);

                return $ticket->fresh();
            });
        } catch (\Exception $e) {
            Log::error('Error creating ticket', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing ticket
     */
    public function updateTicket(Ticket $ticket, array $data): Ticket
    {
        try {
            return DB::transaction(function () use ($ticket, $data) {
                $originalPriority = $ticket->priority;
                $originalStatus = $ticket->status_id;

                $ticket->update($data);

                // Al cambiar la prioridad, recalcular los vencimientos SLA con el
                // motor real (TicketSlaPolicy + priority_multipliers). Antes la
                // condición miraba `priority_id` —columna inexistente— y escribía
                // a `due_at` —tampoco existe— vía el SlaPolicy @deprecated: no-op.
                if (array_key_exists('priority', $data) && $data['priority'] !== $originalPriority) {
                    $ticket->calculateSlaDueDates();
                }

                if (isset($data['status_id']) && $data['status_id'] !== $originalStatus) {
                    TicketHistory::logFieldChange(
                        $ticket,
                        'status_id',
                        $originalStatus,
                        $data['status_id'],
                        auth()->user()
                    );
                }

                event(new TicketUpdated($ticket));

                Log::info('Ticket updated', [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                ]);

                return $ticket->fresh();
            });
        } catch (\Exception $e) {
            Log::error('Error updating ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add a message to a ticket.
     *
     * @deprecated Escribe en `helpdesk_ticket_messages` (TicketMessage), que NO
     * es la tabla del hilo del inbox de manager — ese hilo se renderiza desde
     * `$ticket->items` (TicketItem). Un mensaje creado por aquí NO aparecería en
     * el hilo. Sin callers actuales. Para responder a un ticket usar la vía
     * única TicketMessagingController::createMessageItem (TicketItem + eventos).
     * TicketMessage solo debe usarse en el subsistema widget/portal/público
     * (storeAttachments), no para el hilo del agente.
     */
    public function addMessage(Ticket $ticket, array $data): TicketMessage
    {
        try {
            return DB::transaction(function () use ($ticket, $data) {
                $data['ticket_id'] = $ticket->id;
                $data['user_id'] = auth()->id();

                $message = TicketMessage::create($data);

                if (isset($data['adjuntos']) && is_array($data['adjuntos'])) {
                    foreach ($data['adjuntos'] as $adjunto) {
                        if (isset($adjunto['path'])) {
                            $message->attachments()->create([
                                'file_path' => $adjunto['path'],
                                'file_name' => $adjunto['name'] ?? basename($adjunto['path']),
                                'file_size' => $adjunto['size'] ?? null,
                                'mime_type' => $adjunto['mime_type'] ?? null,
                            ]);
                        }
                    }
                }

                $user = auth()->user();
                if ($user && $user->hasRole('helpdesk-agent') && ! $ticket->first_response_at) {
                    $ticket->update(['first_response_at' => now()]);
                }

                $ticket->update(['last_activity_at' => now()]);

                event(new MessageAdded($message));
                // El email al cliente lo envía el listener SendCustomerReplyNotification
                // suscrito a MessageAdded — no duplicar aquí.

                Log::info('Message added to ticket', [
                    'ticket_id' => $ticket->id,
                    'message_id' => $message->id,
                    'is_internal' => $data['is_internal'] ?? false,
                ]);

                return $message->fresh();
            });
        } catch (\Exception $e) {
            Log::error('Error adding message to ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Close a ticket
     */
    public function closeTicket(Ticket $ticket, ?string $reason = null): Ticket
    {
        try {
            if ($ticket->closed_at) {
                throw new \Exception('Ticket is already closed');
            }

            return DB::transaction(function () use ($ticket, $reason) {
                $closedStatus = TicketStatus::where('slug', 'closed')->first();

                $ticket->update([
                    'status_id' => $closedStatus?->id ?? $ticket->status_id,
                    'closed_at' => now(),
                    'closed_by' => auth()->id(),
                    'close_reason' => $reason ?: $ticket->close_reason,
                ]);

                TicketHistory::logFieldChange(
                    $ticket,
                    'status_id',
                    $ticket->getOriginal('status_id'),
                    $closedStatus?->id,
                    auth()->user()
                );

                event(new TicketClosed($ticket));
                // La notificación de cierre la maneja el listener UpdateTicketOnClose
                // suscrito a TicketClosed — no duplicar aquí.

                Log::info('Ticket closed', [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'reason' => $reason,
                ]);

                return $ticket->fresh();
            });
        } catch (\Exception $e) {
            Log::error('Error closing ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reabre el ticket si está cerrado y el ajuste "Permitir que el cliente
     * reabra tickets cerrados" (tickets.user_reopen_issue, default ON) lo
     * permite, dentro de la ventana en días configurada
     * (tickets.user_reopen_time, default 7) desde el cierre.
     *
     * Estos dos ajustes existían en Settings → General desde antes de esta
     * sesión, pero sin ningún efecto real en el código (bug real encontrado
     * 4-sep-2026, TCK-2026-00093): tanto FetchTicketEmailsJob como
     * CustomerPortalController::replyToTicket() ya enganchaban la respuesta
     * del cliente al ticket cerrado (por Message-ID/asunto o por
     * ticket_number), pero el ticket se quedaba cerrado y el mensaje entraba
     * en un hilo que nadie iba a revisar. Fuera de la ventana permitida el
     * mensaje se añade al hilo igual, solo no se reabre el ticket.
     *
     * Un fallo al reabrir se registra pero no se relanza: no debe perder el
     * mensaje del cliente, que ya está (o va a quedar) enganchado al hilo.
     */
    public function reopenIfCustomerCanReopen(Ticket $ticket): void
    {
        if ($ticket->closed_at === null) {
            return;
        }

        if (! filter_var(HelpdeskGeneralSetting::get('tickets.user_reopen_issue', true), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $days = (int) HelpdeskGeneralSetting::get('tickets.user_reopen_time', 7);

        if ($ticket->closed_at->copy()->addDays($days)->isPast()) {
            return;
        }

        try {
            $this->reopenTicket($ticket, 'El cliente respondió a un ticket cerrado.');
        } catch (\Throwable $e) {
            Log::warning('TicketService: no se pudo reabrir el ticket tras la respuesta del cliente', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reopen a closed ticket
     */
    public function reopenTicket(Ticket $ticket, ?string $reason = null): Ticket
    {
        try {
            if (! $ticket->closed_at) {
                throw new \Exception('Ticket is not closed');
            }

            return DB::transaction(function () use ($ticket, $reason) {
                $newStatus = TicketStatus::where('slug', 'new')->first();

                $ticket->update([
                    'status_id' => $newStatus?->id ?? 1,
                    'closed_at' => null,
                    'closed_by' => null,
                ]);

                TicketHistory::logFieldChange(
                    $ticket,
                    'status_id',
                    $ticket->getOriginal('status_id'),
                    $newStatus?->id,
                    auth()->user()
                );

                event(new TicketReopened($ticket));

                Log::info('Ticket reopened', [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'reason' => $reason,
                ]);

                return $ticket->fresh();
            });
        } catch (\Exception $e) {
            Log::error('Error reopening ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate a unique ticket number
     *
     * Esto era una TERCERA implementación (además de Ticket::
     * generateTicketNumber() y del TicketObserver) y, peor, con otro prefijo:
     * emitía "TKT-2026-00001" mientras el resto del módulo emitía
     * "TCK-2026-00001". Como cada una busca el último número por su propio
     * prefijo, eran dos secuencias independientes; y el hilado del correo
     * entrante solo reconoce TCK (`/#(TCK-\d{4}-\d{5})/`), así que un ticket
     * nacido del widget o del formulario público jamás se enlazaba al
     * responder citando su número: se abría uno nuevo cada vez.
     *
     * Ahora delega en la única implementación buena, que además lleva el
     * lockForUpdate() dentro de su propia transacción.
     */
    public function generateTicketNumber(): string
    {
        return Ticket::generateTicketNumber();
    }

    /**
     * Store uploaded files as TicketAttachments linked to a new public
     * TicketMessage. Shared by the widget, public form and portal controllers.
     *
     * @param  array<int, UploadedFile>  $files
     * @param  array<string, mixed>  $messageAttributes  Extra attributes for the TicketMessage.
     * @param  int|null  $maxFileSize  Optional per-file size cap in bytes (oversized files are skipped).
     */
    public function storeAttachments(
        array $files,
        int $ticketId,
        array $messageAttributes = [],
        ?int $maxFileSize = null
    ): TicketMessage {
        $safeFiles = [];
        $attachmentSecurity = app(TicketAttachmentSecurityService::class);

        // Keep the service safe even when a caller bypasses a FormRequest
        // (widget, public form, portal or a queued integration). The request
        // rules and this final boundary now use the same Settings value.
        $maxFileSize ??= app(HelpdeskSettings::class)->attachmentMaxKilobytes() * 1024;

        // Validar y escanear todos los archivos antes de crear el mensaje:
        // una amenaza o un fallo del antivirus no debe dejar un mensaje
        // huérfano sin adjuntos en el portal/widget.
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            if ($file->getSize() > $maxFileSize) {
                continue;
            }

            $attachmentSecurity->assertSafe($file);
            $safeFiles[] = $file;
        }

        $message = TicketMessage::create(array_merge([
            'ticket_id' => $ticketId,
            'is_internal' => false,
        ], $messageAttributes));

        $disk = config('helpdesk.attachments.disk', 'local');
        $path = config('helpdesk.attachments.path', 'helpdesk/attachments');

        foreach ($safeFiles as $file) {
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

        return $message;
    }
}
