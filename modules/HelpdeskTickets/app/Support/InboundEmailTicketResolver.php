<?php

namespace Modules\HelpdeskTickets\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\SpamClassifierService;

/**
 * Hilado (threading) por Message-ID/asunto y creación de tickets/clientes a
 * partir de un correo entrante ya parseado. Extraído de FetchTicketEmailsJob
 * (30-sep-2026, job de 1103 líneas) — FetchTicketEmailsJob::findOrCreateTicket()/
 * etc. son ahora delegados finos a esta clase; ver su docblock para el
 * porqué del reparto.
 */
class InboundEmailTicketResolver
{
    public function __construct(
        private readonly InboundEmailMessageParser $parser,
    ) {}

    /**
     * Find existing ticket or create new one for email.
     */
    public function findOrCreateTicket(array $parsed, array $connection = []): ?Ticket
    {
        // Resolve the sender address up front: it is required both for ticket
        // threading verification and for customer lookup/creation.
        $rawFrom = $parsed['from'];
        if (empty($rawFrom)) {
            Log::warning('FetchTicketEmailsJob: email without From header, skipping', ['subject' => $parsed['subject']]);

            return null;
        }
        $fromEmail = $this->parser->extractEmailAddress($rawFrom);

        // Sender blacklist: block by exact email or by domain (including
        // subdomains) ANTES de cualquier intento de hilado. Estaba después del
        // hilado por Message-ID, así que un remitente bloqueado que respondiera
        // a un hilo existente se colaba entero: no se puede decidir si un
        // correo entra sin haber mirado antes de quién viene.
        if ($fromEmail && ($blocked = TicketEmailBlacklist::matches($fromEmail))) {
            $blocked->registerMatch(
                $fromEmail,
                $parsed['subject'] ?? null,
                $parsed['body_html'] ?? null,
                $parsed['body_text'] ?? null,
            );
            Log::info("FetchTicketEmailsJob: email from {$fromEmail} discarded, sender is blacklisted (rule #{$blocked->id}).");

            return null;
        }

        // Clasificador de spam: complementa a la lista negra, que solo bloquea
        // remitentes YA conocidos. RETIENE en cuarentena, no descarta — un
        // falso positivo aquí es un cliente real cuyo correo desaparece sin
        // que nadie se entere. Ver SpamClassifierService.
        if ($fromEmail && app(SpamClassifierService::class)->quarantineIfSpam($fromEmail, $parsed)) {
            return null;
        }

        // Boletines y envíos automáticos de remitentes nuevos: a cuarentena,
        // no a ticket. Antes Hostinger, JetBrains, Oracle o Mailrelay abrían
        // tickets que además el escalado acababa subiendo a "Urgente".
        if ($fromEmail && app(SpamClassifierService::class)->quarantineIfBulk($fromEmail, $parsed)) {
            return null;
        }

        // Try to find by Message-ID threading first — In-Reply-To es el padre
        // inmediato; References es la cadena completa del hilo (RFC 5322) y
        // cubre el caso en que el cliente responde a un mensaje intermedio
        // que ya no es el último, o un cliente de correo que solo rellena
        // References y no In-Reply-To.
        //
        // La verificación de remitente aplica aquí igual que en el hilado por
        // asunto de abajo, y por el mismo motivo. Los Message-ID salientes no
        // son adivinables, pero sí circulan: basta con que el cliente reenvíe
        // el correo del helpdesk a un tercero para que ese tercero tenga la
        // cabecera y, respondiendo, escriba dentro de un ticket ajeno.
        $threadIds = array_filter(array_merge(
            [$parsed['in_reply_to']],
            $this->parser->splitReferences($parsed['references'] ?? null),
        ));

        if ($threadIds !== []) {
            $existingMail = TicketMail::with('ticket.customer:id,email')
                ->whereIn('message_id', $threadIds)
                ->first();

            if ($existingMail?->ticket) {
                if ($this->senderMatchesTicket($existingMail->ticket, $fromEmail)) {
                    return $this->threadedTicket($existingMail->ticket);
                }

                Log::warning('FetchTicketEmailsJob: Message-ID thread sender does not match ticket customer, not threading', [
                    'ticket_number' => $existingMail->ticket->ticket_number,
                    'from' => $fromEmail,
                ]);
            }
        }

        // Try to find by ticket number in subject (e.g., "Re: Ticket #TCK-2025-00123").
        // Only thread into the ticket when the sender matches the ticket customer,
        // otherwise a third party could inject messages into someone else's ticket.
        if (preg_match('/#(TCK-\d{4}-\d{5})/', $parsed['subject'], $matches)) {
            $ticket = Ticket::with('customer:id,email')->where('ticket_number', $matches[1])->first();
            if ($ticket && $this->senderMatchesTicket($ticket, $fromEmail)) {
                return $this->threadedTicket($ticket);
            }

            if ($ticket) {
                Log::warning('FetchTicketEmailsJob: sender does not match ticket customer, creating new ticket', [
                    'ticket_number' => $matches[1],
                    'from' => $fromEmail,
                ]);
            }
        }

        // Llegados aquí no se pudo enlazar con un ticket existente, así que
        // habría que CREAR uno nuevo. Si esta conexión no permite crear tickets
        // (solo respuestas), no se crea: se devuelve null y el email se ignora.
        // Sin conexión (fallback de buzón único) se mantiene el comportamiento previo.
        if ($connection !== [] && ! ($connection['create_tickets'] ?? false)) {
            return null;
        }

        $customer = Customer::where('email', $fromEmail)->first();

        if (! $customer) {
            // Create new customer
            $fromName = $this->parser->extractEmailName($parsed['from']);
            $customer = Customer::create([
                'email' => $fromEmail,
                'name' => $fromName ?: $fromEmail,
            ]);
            Log::info("Created new customer: {$fromEmail}");
        }

        // Create new ticket inside a transaction so the lockForUpdate in
        // generateTicketNumber() is effective and numbers never collide.
        $ticket = DB::transaction(fn () => Ticket::create([
            'customer_id' => $customer->id,
            'subject' => $parsed['subject'],
            'description' => $parsed['body_text'] ?? $parsed['body_html'],
            'source' => 'email',
            'status_id' => TicketStatus::where('is_default', true)->first()?->id ?? 1,
            'priority' => $this->parser->detectPriority($parsed['subject']),
            // Explícito (no depender del TicketObserver::creating): el número se
            // genera aquí, dentro de la transacción que hace efectivo el lockForUpdate.
            'ticket_number' => Ticket::generateTicketNumber(),
        ]));

        Log::info("Created new ticket #{$ticket->ticket_number} from email");

        // Sin esto, TicketCreated nunca se disparaba para un ticket nacido de
        // un correo real: SendCustomerConfirmation (correo "hemos recibido tu
        // solicitud"), NotifyAgentsOnNewTicket, RunAiAutoClassify, etc. están
        // suscritos a este evento pero solo lo reciben cuando el ticket se
        // crea vía TicketService::createTicket() (widget/formulario público),
        // nunca desde este job — el único canal real de entrada de tickets no
        // avisaba al cliente que su solicitud había llegado. Mismo patrón que
        // el MessageAdded::dispatch() de más arriba.
        TicketCreated::dispatch($ticket);

        // Después del Ticket::create(), no antes: el trabajo lleva el ticket de
        // origen para que CustomerErpResolved pueda enrutar ESTE ticket y no
        // todo lo que el cliente tenga abierto.
        $this->dispatchErpLookup($customer->id, $ticket->id);

        return $ticket;
    }

    /**
     * Un correo que se engancha a un ticket ya abierto también pide la búsqueda.
     *
     * Antes solo se pedía en la rama que crea ticket: si el ERP estaba caído
     * ese día, o el cliente aún no existía en gestión, nada volvía a intentarlo
     * nunca. El enfriamiento de LinkCustomerToErpJob es lo que evita que esto
     * consulte el ERP en cada respuesta.
     */
    public function threadedTicket(Ticket $ticket): Ticket
    {
        if ($ticket->customer_id) {
            $this->dispatchErpLookup($ticket->customer_id, $ticket->id);
        }

        return $ticket;
    }

    /**
     * Best-effort y asíncrono: si el email no está en el ERP, o el ERP no
     * responde, no se vincula nada — nunca bloquea ni descarta el correo.
     * class_exists() porque HelpdeskErp es un módulo aparte que puede no estar
     * instalado, y helpdesk_erp_enabled() respeta el toggle de
     * Ajustes → Integraciones.
     */
    public function dispatchErpLookup(int $customerId, int $ticketId): void
    {
        if (! helpdesk_erp_enabled() || ! class_exists(LinkCustomerToErpJob::class)) {
            return;
        }

        LinkCustomerToErpJob::dispatch($customerId, 'ticket', $ticketId);
    }

    /**
     * Determine whether the sender address belongs to the ticket customer.
     */
    public function senderMatchesTicket(Ticket $ticket, string $fromEmail): bool
    {
        $customerEmail = $ticket->customer?->email;

        if (! $customerEmail) {
            return false;
        }

        return strcasecmp(trim($customerEmail), trim($fromEmail)) === 0;
    }
}
