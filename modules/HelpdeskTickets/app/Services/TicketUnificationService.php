<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Mail\TicketsUnifiedMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Models\TicketLink;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketWatcher;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;

/**
 * Unificación de varios tickets del mismo cliente en uno solo.
 *
 * El caso real: el cliente escribe tres correos el mismo día sobre lo mismo y
 * el buzón abre tres tickets. La detección de duplicados ya avisaba, pero solo
 * dejaba fusionar de uno en uno y de forma destructiva (TicketMergeService
 * borra el ticket origen), así que en la práctica nadie lo usaba con tres.
 *
 * Aquí se unifican N de una vez con tres diferencias respecto a esa fusión:
 *
 *  - el contenido se mueve al ticket que se conserva, pero los tickets origen
 *    NO se borran: se CIERRAN con el motivo, enlazados al superviviente, así
 *    que su número sigue existiendo si el cliente lo menciona;
 *  - el superviviente recibe una nota interna con el resumen de lo unificado;
 *  - al cliente se le avisa por correo con ese mismo resumen y el número que
 *    debe usar a partir de ahora.
 *
 * La ruta antigua (banner → "Revisar duplicado" → fusionar) se deja intacta.
 */
class TicketUnificationService
{
    public function __construct(
        private readonly TicketChannelMailerService $channelMailer,
    ) {}

    /**
     * @param  Collection<int, Ticket>  $duplicates  Tickets a cerrar dentro del superviviente.
     * @return array{survivor: Ticket, closed: array<int, string>, notified: bool}
     */
    public function unify(Ticket $survivor, Collection $duplicates, bool $notifyCustomer = true): array
    {
        // Nunca contra sí mismo ni contra tickets ya cerrados: unificar dos
        // veces el mismo duplicado dejaría dos notas y dos avisos al cliente.
        $duplicates = $duplicates
            ->reject(fn (Ticket $t) => $t->id === $survivor->id || $t->closed_at !== null)
            ->values();

        if ($duplicates->isEmpty()) {
            return ['survivor' => $survivor, 'closed' => [], 'notified' => false];
        }

        $resumen = $this->buildSummary($duplicates);

        DB::transaction(function () use ($survivor, $duplicates, $resumen) {
            foreach ($duplicates as $duplicate) {
                $this->moveContent($duplicate, $survivor);
                $this->linkAndClose($duplicate, $survivor);
            }

            // Nota interna con el resumen: es lo que ve el agente que abra el
            // ticket mañana y no sepa por qué tiene mensajes de tres hilos.
            $survivor->items()->create([
                'type' => 'message',
                'user_id' => auth()->id(),
                'body' => 'Unificados '.$duplicates->count()." ticket(s) en este:\n".$resumen,
                'is_internal' => true,
            ]);
        });

        $notified = $notifyCustomer && $this->notifyCustomer($survivor, $duplicates, $resumen);

        return [
            'survivor' => $survivor->fresh(),
            'closed' => $duplicates->pluck('ticket_number')->all(),
            'notified' => $notified,
        ];
    }

    /**
     * Mueve el contenido del duplicado al superviviente.
     *
     * Mismo alcance que TicketMergeService::merge() (hilo, correos, historial,
     * tiempos, notas, comentarios, seguidores), pero SIN borrar el origen.
     */
    private function moveContent(Ticket $duplicate, Ticket $survivor): void
    {
        $duplicate->items()->update(['ticket_id' => $survivor->id]);
        $duplicate->mails()->update(['ticket_id' => $survivor->id]);
        $duplicate->history()->update(['ticket_id' => $survivor->id]);
        $duplicate->timeEntries()->update(['ticket_id' => $survivor->id]);

        TicketNote::withTrashed()->where('ticket_id', $duplicate->id)->update(['ticket_id' => $survivor->id]);
        TicketComment::withTrashed()->where('ticket_id', $duplicate->id)->update(['ticket_id' => $survivor->id]);

        $duplicate->watchers()->each(function (TicketWatcher $watcher) use ($survivor) {
            TicketWatcher::firstOrCreate([
                'ticket_id' => $survivor->id,
                'user_id' => $watcher->user_id,
            ]);
        });
    }

    /**
     * Enlaza el duplicado con el superviviente y lo cierra dejando dicho por qué.
     */
    private function linkAndClose(Ticket $duplicate, Ticket $survivor): void
    {
        TicketLink::firstOrCreate([
            'ticket_id' => $survivor->id,
            'linked_ticket_id' => $duplicate->id,
        ], ['link_type' => 'duplicate']);

        // El hilo del duplicado ya está vacío (se ha movido), así que este
        // mensaje es lo único que queda en él: tiene que decir dónde mirar.
        $duplicate->items()->create([
            'type' => 'message',
            'user_id' => auth()->id(),
            'body' => "Este ticket se unificó en {$survivor->ticket_number}. La conversación continúa allí.",
            'is_internal' => true,
        ]);

        $duplicate->close("Unificado en {$survivor->ticket_number}");
    }

    /**
     * Resumen legible de los tickets unificados, en texto plano.
     */
    private function buildSummary(Collection $duplicates): string
    {
        return $duplicates->map(function (Ticket $t) {
            $mensajes = $t->items()->where('is_internal', false)->count();

            return sprintf(
                '· %s — %s (%s, %d mensaje%s)',
                $t->ticket_number,
                Str::limit((string) $t->subject, 70),
                $t->created_at?->format('d/m/Y H:i') ?? '—',
                $mensajes,
                $mensajes === 1 ? '' : 's'
            );
        })->implode("\n");
    }

    /**
     * Avisa al cliente de que sus tickets pasan a ser uno.
     *
     * Mismo canal y mismo renderizado de plantilla que el resto de correos al
     * cliente del módulo (SendCustomerConfirmation): así el correo sale del
     * buzón real de soporte y el cliente puede responderlo.
     */
    private function notifyCustomer(Ticket $survivor, Collection $duplicates, string $resumen): bool
    {
        $email = $survivor->customer?->email;

        if (! $email) {
            Log::info('[HelpdeskTickets] Unificación sin aviso: el ticket no tiene cliente con email', [
                'ticket_id' => $survivor->id,
            ]);

            return false;
        }

        $listaHtml = $duplicates->map(fn (Ticket $t) => '<li>#'.e($t->ticket_number).' — '.e(Str::limit((string) $t->subject, 70)).
            ' <span style="color:#888;">('.($t->created_at?->format('d/m/Y') ?? '—').')</span></li>'
        )->implode('');

        [$subject, $content] = TicketMailRenderer::render(
            'helpdesk_tickets.tickets_unified',
            [
                'TICKET_NUMBER' => $survivor->ticket_number,
                'TICKET_SUBJECT' => (string) $survivor->subject,
                'MERGED_COUNT' => (string) $duplicates->count(),
                'MERGED_LIST' => '<ul style="margin:0 0 16px;padding-left:18px;">'.$listaHtml.'</ul>',
            ],
            'Hemos unificado tus solicitudes — #'.$survivor->ticket_number,
        );

        $channel = $this->channelMailer->resolveChannelForTicket($survivor);
        $mailerName = $channel ? $this->channelMailer->mailerNameFor($channel) : null;
        $fromAddress = $channel['username'] ?? null;
        // Sin '<' '>' al guardar — ver TicketMail::createOutbound().
        $ownMessageId = Str::uuid().'@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost');

        ($mailerName ? Mail::mailer($mailerName) : Mail::mailer())
            ->to($email, $survivor->customer?->name)
            ->queue(new TicketsUnifiedMail($survivor, $subject, $content, $fromAddress, $ownMessageId));

        TicketMail::create([
            'ticket_id' => $survivor->id,
            'user_id' => auth()->id(),
            'direction' => 'outbound',
            'message_id' => $ownMessageId,
            'from' => $fromAddress ?: config('mail.from.address'),
            'to' => $email,
            'subject' => $subject,
            'body_html' => $content,
            'body_text' => strip_tags($content),
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return true;
    }
}
