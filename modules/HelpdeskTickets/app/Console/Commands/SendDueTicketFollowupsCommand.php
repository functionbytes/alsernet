<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Models\TicketFollowup;
use Modules\HelpdeskTickets\Notifications\TicketFollowupDueNotification;
use Modules\HelpdeskTickets\Services\TicketVariableInterpolator;
use Throwable;

/**
 * Envía los recordatorios de seguimiento cuya fecha ya venció: notifica al
 * usuario que lo programó y marca el followup como enviado. Barrido idempotente
 * (filtra is_sent=false), consistente con el resto de comandos de tickets.
 */
class SendDueTicketFollowupsCommand extends Command
{
    protected $signature = 'ticket:send-followups';

    protected $description = 'Notify scheduled ticket follow-ups whose due time has passed';

    public function handle(): int
    {
        $sent = 0;

        $cancelados = 0;

        TicketFollowup::query()
            ->pending()
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->with(['ticket', 'user', 'cannedReply'])
            ->chunkById(200, function ($followups) use (&$sent, &$cancelados) {
                foreach ($followups as $followup) {
                    // "Detener la secuencia si el cliente responde": si hubo
                    // respuesta del cliente después de programar el paso, el
                    // recordatorio ya no tiene sentido — avisar de algo que
                    // el cliente ya contestó es justo el ruido que hace que
                    // se dejen de mirar los avisos.
                    if ($followup->cancel_if_customer_replies && $this->clienteRespondio($followup)) {
                        $followup->update(['cancelled_at' => now()]);
                        $cancelados++;

                        continue;
                    }

                    if ($followup->user) {
                        $followup->user->notify(new TicketFollowupDueNotification($followup));
                    }

                    // Selector "Plantilla" del mockup: antes este comando
                    // SOLO avisaba al agente que había programado el paso,
                    // nunca al cliente -- el mockup muestra el seguimiento
                    // como un email real que sale del ticket. Se manda como
                    // un mensaje del agente que programó el paso (o del
                    // asignado, si ya cambió), reusando el mismo mecanismo
                    // que un mensaje escrito a mano (MessageAdded ->
                    // SendCustomerReplyNotification ya envía el correo real
                    // vía la plantilla helpdesk.ticket_reply).
                    if ($followup->canned_reply_id && $followup->cannedReply && $followup->ticket) {
                        $this->sendCustomerFollowup($followup);
                    }

                    $followup->update(['is_sent' => true, 'sent_at' => now()]);
                    $sent++;
                }
            });

        if ($sent > 0 || $cancelados > 0) {
            Log::info('SendDueTicketFollowups: recordatorios procesados', [
                'sent' => $sent,
                'cancelled' => $cancelados,
            ]);
        }

        $this->info("Enviados {$sent} recordatorio(s) de seguimiento.");

        if ($cancelados > 0) {
            $this->info("Cancelados {$cancelados} porque el cliente ya había respondido.");
        }

        return self::SUCCESS;
    }

    /**
     * Manda al cliente, por correo real, el contenido de la plantilla
     * elegida al programar este paso -- interpolando las mismas variables
     * ({{ticket_number}}, {{customer_name}}...) que ya usan macros y
     * plantillas de creación de ticket. Un fallo (plantilla borrada,
     * dispatcher caído) no debe tumbar el resto del barrido: se registra y
     * el paso igual se marca como enviado, para no reintentarlo cada minuto.
     */
    private function sendCustomerFollowup(TicketFollowup $followup): void
    {
        try {
            $body = (new TicketVariableInterpolator)->interpolate(
                $followup->cannedReply->content,
                $followup->ticket,
            );

            $item = $followup->ticket->items()->create([
                'type' => 'message',
                'user_id' => $followup->user_id,
                'body' => $body,
                'html_body' => $followup->cannedReply->html_body,
                'is_internal' => false,
            ]);

            MessageAdded::dispatch($item);
        } catch (Throwable $e) {
            Log::error('SendDueTicketFollowups: no se pudo enviar el correo de seguimiento al cliente', [
                'followup_id' => $followup->id,
                'ticket_id' => $followup->ticket_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ¿Escribió el cliente después de programarse este paso?
     *
     * Un item sin user_id y no interno es un mensaje entrante del cliente
     * (mismo criterio que usa el hilo para decidir la dirección).
     */
    private function clienteRespondio(TicketFollowup $followup): bool
    {
        if (! $followup->ticket) {
            return false;
        }

        // '>=' y no '>': los timestamps tienen precisión de segundo, así que
        // una respuesta del mismo segundo en que se programó el paso cuenta
        // como posterior. Ante la duda es mejor no avisar de algo que el
        // cliente ya contestó que avisar de más.
        return $followup->ticket->items()
            ->whereNull('user_id')
            ->where('is_internal', false)
            ->where('created_at', '>=', $followup->created_at)
            ->exists();
    }
}
