<?php

namespace Modules\HelpdeskTickets\Services\TicketDetail;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\EmailLogLookupService;
use Modules\HelpdeskTickets\Services\TicketDetail\Support\FormatsDisplayTimezone;

/**
 * Pestaña Correo del panel de detalle: último correo, "reenviar último
 * correo", traza de entrega, identificadores técnicos y el modal "Correos
 * del ticket". Extraído de TicketDetailDataService (30-sep-2026) al trocear
 * ese servicio por sección de panel — ver el docblock de la clase façade.
 */
class MailBuilder
{
    use FormatsDisplayTimezone;

    public function __construct(
        private readonly EmailLogLookupService $emailLogLookup,
    ) {}

    /**
     * @return array{mail: ?array<string, mixed>, last_outbound_mail: ?array<string, mixed>, trace: array<int, array<string, mixed>>, trace_meta: ?array<string, mixed>, mails: array<int, array<string, mixed>>}
     */
    public function build(Ticket $ticket): array
    {
        // reorder() antes de latest(): Ticket::mails() ya define su propio
        // orderBy('created_at','asc') por defecto (para el hilo interno) —
        // encadenar latest() SIN limpiarlo antes añade un SEGUNDO
        // "ORDER BY created_at desc" sobre la MISMA columna, que MySQL
        // ignora (el primer criterio ya determina el orden por completo).
        // Sin este reorder(), $allMails/$lastMail salían en realidad en
        // ASC — el correo "más reciente" mostrado era el MÁS ANTIGUO del
        // ticket (bug real confirmado en vivo con TCK-2026-00014/173: el
        // widget "Último correo del ticket" mostraba un entrante de hace 3
        // días en vez del saliente de hoy).
        // with('user'): el bloque 'mails' de abajo lee $m->user->fullName()
        // para las iniciales de cada correo SALIENTE (modal 14) — sin esto
        // era un N+1 real, una query por cada correo saliente distinto de
        // los hasta 50 que trae este límite (detectado perfilando este
        // endpoint con un ticket de historial denso: 3 queries extra a
        // `users` con solo 3 salientes; un ticket longevo con más agentes
        // implicados escala linealmente).
        $allMails = $ticket->mails()->reorder()->latest()->limit(50)->with('user:id,firstname,lastname,email')->get();
        $lastMail = $allMails->first();
        // Una sola consulta para mailOpensSummary()/mailClicksSummary() y
        // traceFor(): los tres cruzaban EmailLog por el mismo message_id por
        // separado. Ver EmailLogLookupService para por qué vive en un
        // servicio compartido en vez de repetirse aquí.
        $lastMailEmailLog = $lastMail ? $this->emailLogLookup->forMessageId($lastMail->message_id) : null;

        // "Reenviar último correo" SOLO puede operar sobre un correo de
        // SALIDA real (direction=outbound): si el más reciente del ticket es
        // uno de ENTRADA (recibido del cliente), su campo 'to' es la propia
        // bandeja de soporte (quien lo recibió, ver TicketMail::parseInbound*
        // en la ingesta), no la dirección del cliente — "reenviarlo" tal cual
        // generaría un correo real dirigido a soporte, el efecto opuesto al
        // que sugiere el botón (bug real de QA). Se busca aparte de $lastMail
        // (que sigue alimentando el widget puramente informativo "Último
        // correo del ticket": ese sí muestra el más reciente sea cual sea la
        // dirección). Consulta directa en vez de filtrar $allMails: el cap de
        // 50 de arriba podría no incluir el último saliente real si hay más
        // de 50 correos entrantes intercalados más recientes.
        $lastOutboundMail = $ticket->mails()->where('direction', 'outbound')->reorder()->latest()->first();

        // Mismo motivo que $lastOutboundMail de arriba, pero para la pestaña
        // Traza: si el cliente responde, $lastMail pasa a ser ese correo
        // ENTRANTE, que nunca tiene sent_at/delivered_at/EmailLog propios
        // (nosotros no lo enviamos) -- traceFor() devolvía [] y la pestaña
        // se veía vacía justo después de que el cliente contestara (bug real,
        // confirmado en vivo, TCK-2026-00093: la traza desaparecía tras la
        // segunda respuesta del cliente). La traza es sobre lo que NOSOTROS
        // mandamos, así que tiene que seguir al último saliente, no al
        // último mensaje sea cual sea su dirección.
        $lastOutboundMailEmailLog = $lastOutboundMail === null
            ? null
            : ($lastMail && $lastOutboundMail->is($lastMail)
                ? $lastMailEmailLog
                : $this->emailLogLookup->forMessageId($lastOutboundMail->message_id));

        return [
            'mail' => $lastMail ? [
                'subject' => $lastMail->subject,
                'to' => $lastMail->to,
                'status' => $lastMail->status,
                'body_html' => $lastMail->safeBodyHtml(),
                'created_at_human' => $lastMail->created_at?->diffForHumans(),
                // Card "Destinatarios" del mockup (De / Para / CC / Responder
                // a) y su desplegable "Detalles técnicos". Todas estas
                // columnas ya existían en helpdesk_ticket_mails; simplemente
                // no se enviaban al front, que solo pintaba Para/Asunto.
                'from' => $lastMail->from,
                'cc' => $lastMail->cc,
                'bcc' => $lastMail->bcc,
                'message_id' => $lastMail->message_id,
                'in_reply_to' => $lastMail->in_reply_to,
                // body_text real del correo, no el body_html despojado de
                // etiquetas: la vista "Texto" del mockup enseña la parte
                // text/plain que se envió de verdad, que puede diferir.
                'body_text' => $lastMail->body_text,
                // "Fuente" (MIME crudo) solo si se archivó el correo entero.
                'raw_email' => $lastMail->raw_email,
                'attachments' => $lastMail->attachments ?? [],
                // Chip "Spam N/10": la puntuación que puso el filtro del
                // servidor entrante, leída de las cabeceras archivadas. null
                // cuando el correo no pasó por ningún filtro (ver
                // TicketMail::spamVerdict()).
                'spam' => $lastMail->spamVerdict(),
                // Modales 05 (detalle de entrega) y 06 (rebote).
                'id' => $lastMail->id,
                'direction' => $lastMail->direction,
                'sent_at_human' => $this->inDisplayTz($lastMail->sent_at)?->translatedFormat('d M Y · H:i:s'),
                'delivered_at_human' => $this->inDisplayTz($lastMail->delivered_at)?->translatedFormat('d M Y · H:i:s'),
                'delivery_error' => $lastMail->delivery_error,
                'url_resend' => route('manager.helpdesk.tickets.emails.resend', $lastMail),
                'url_fix_bounce' => route('manager.helpdesk.tickets.emails.fix-bounce', $lastMail),
                // Aperturas reales (EmailLogOpen, mismo cruce por message_id
                // que traceFor()) — "reintentos" del mockup se omite: no hay
                // columna de intentos en EmailLog, no se inventa.
                ...$this->mailOpensSummary($lastMail, $lastMailEmailLog),
                // Clics reales (EmailLogClick, vía EmailLogLink) — mismo
                // criterio que las aperturas.
                ...$this->mailClicksSummary($lastMail, $lastMailEmailLog),
            ] : null,
            // Datos de "Reenviar último correo" — deliberadamente
            // independientes del bloque 'mail' de arriba (ver comentario en
            // $lastOutboundMail). null cuando el ticket no tiene ningún
            // correo de SALIDA todavía (aunque sí tenga entrantes): el JS
            // debe deshabilitar el botón en ese caso con un motivo explícito
            // en vez de ofrecer "reenviar" un correo que en realidad llegó
            // del cliente.
            'last_outbound_mail' => $lastOutboundMail ? [
                'to' => $lastOutboundMail->to,
                'subject' => $lastOutboundMail->subject,
                // Reusa el mismo endpoint que ya existe en la bandeja de
                // emails (TicketMailsController::resend()) — sin duplicar
                // lógica de reenvío.
                'url_resend' => route('manager.helpdesk.tickets.emails.resend', $lastOutboundMail),
            ] : null,
            'trace' => $lastOutboundMail ? $this->traceFor($lastOutboundMail, $lastOutboundMailEmailLog) : [],
            // Bloques "Traza SMTP" e "Identificadores" del pie de la pestaña
            // Traza. El mockup enseña ahí relay/IP/TLS/reintentos, que esta
            // instalación NO guarda en ninguna parte (raw_headers está vacío
            // en las 1057 filas del log y ticket_mails no tiene columnas de
            // transporte): se publican solo los campos con dato real y el JS
            // omite las filas vacías, en vez de rellenarlas de ejemplo.
            // Mismo motivo que 'trace' de arriba: los identificadores tienen
            // que ser del MISMO correo que la línea de tiempo, si no
            // contradice lo que se acaba de mostrar (la traza decía "enviado
            // desde info@..." y el panel de IDs de al lado decía
            // "dirección: entrante" del correo del cliente).
            'trace_meta' => $lastOutboundMail ? $this->traceMetaFor($ticket, $lastOutboundMail, $lastOutboundMailEmailLog) : null,
            // Lista completa (modal "Correos del ticket") — antes solo se
            // veía el último; el resto obligaba a salir a la bandeja global.
            'mails' => $allMails->map(fn ($m) => [
                'id' => $m->id,
                'subject' => $m->subject,
                'from' => $m->from,
                'to' => $m->to,
                'direction' => $m->direction,
                'status' => $m->status,
                'created_at_human' => $m->created_at?->diffForHumans(),
                // Modal 14: iniciales para el avatar de cada correo del hilo.
                'initials' => TicketMail::initialsFor($m->direction === 'inbound' ? $m->from : ($m->user?->fullName() ?: $m->from)),
                'attachment_count' => count($m->attachments ?? []),
                'scheduled_at_human' => $this->inDisplayTz($m->scheduled_at)?->translatedFormat('d M · H:i'),
                // Modales 07/09/10, que actúan sobre un correo concreto.
                'url_resend' => route('manager.helpdesk.tickets.emails.resend', $m),
                'url_cancel_scheduled' => route('manager.helpdesk.tickets.emails.cancel-scheduled', $m),
                'url_link' => route('manager.helpdesk.tickets.emails.link', $m),
            ])->values()->all(),
        ];
    }

    /**
     * Trazabilidad de entrega del último correo del ticket — mismo cruce
     * EmailLog/EmailLogOpen por message_id (trim de '<>') ya construido para
     * la bandeja de emails, ver TicketMailsController::traceFor().
     *
     * @return array{opens_count: int, last_opened_human: ?string}
     */
    private function mailOpensSummary(TicketMail $mail, ?EmailLog $log): array
    {
        return $this->engagementSummary($mail, $log?->opens, 'opened_at', 'opens_count', 'last_opened_human');
    }

    /**
     * Mismo criterio que mailOpensSummary(), pero sobre los clics de los
     * enlaces reescritos del correo (ver HelpdeskEmailActivity\Models\EmailLog::
     * clicks(), hasManyThrough vía EmailLogLink).
     *
     * @return array{clicks_count: int, last_clicked_human: ?string}
     */
    private function mailClicksSummary(TicketMail $mail, ?EmailLog $log): array
    {
        return $this->engagementSummary($mail, $log?->clicks, 'clicked_at', 'clicks_count', 'last_clicked_human');
    }

    /**
     * Forma común de mailOpensSummary()/mailClicksSummary(): contar una
     * colección de eventos (EmailLogOpen o EmailLogClick) y devolver el más
     * reciente en humano — la única diferencia real entre aperturas y clics
     * es QUÉ colección y QUÉ columna de fecha, nunca la forma del resultado.
     * Los nombres de las claves de salida se pasan explícitos (en vez de
     * derivarlos de $dateField) porque el JS (renderCorreoSidePane en
     * tickets-app.js) ya los lee tal cual.
     *
     * @param  ?Collection<int, mixed>  $events
     */
    private function engagementSummary(TicketMail $mail, ?Collection $events, string $dateField, string $countKey, string $lastHumanKey): array
    {
        if (! $mail->message_id) {
            return [$countKey => 0, $lastHumanKey => null];
        }

        $last = $events?->sortByDesc($dateField)->first();

        return [
            $countKey => $events?->count() ?? 0,
            $lastHumanKey => $last?->{$dateField}?->diffForHumans(),
        ];
    }

    /**
     * Datos de transporte e identificadores del último correo del ticket.
     *
     * @return array{smtp: array<int, array{k: string, v: string}>, ids: array<int, array{k: string, v: string}>}
     */
    private function traceMetaFor(Ticket $ticket, TicketMail $mail, ?EmailLog $log): array
    {
        $smtp = [];

        // Latencia real de entrega: lo que tardó el destino en confirmar
        // desde que el servidor aceptó el mensaje.
        $sentAt = $log?->sent_at ?? $mail->sent_at;
        if ($sentAt && $mail->delivered_at) {
            $seconds = $sentAt->diffInSeconds($mail->delivered_at, false);
            if ($seconds >= 0) {
                $smtp[] = ['k' => 'latencia', 'v' => $seconds < 60
                    ? number_format($seconds, 1, ',', '.').' s'
                    : $sentAt->diffForHumans($mail->delivered_at, ['syntax' => CarbonInterface::DIFF_ABSOLUTE, 'parts' => 1])];
            }
        }

        if ($mail->to && str_contains($mail->to, '@')) {
            $smtp[] = ['k' => 'dominio destino', 'v' => Str::afterLast($mail->to, '@')];
        }

        // Transporte configurado en la app: es de dónde salió el correo.
        if ($mailer = config('mail.default')) {
            $transport = config('mail.mailers.'.$mailer.'.transport', $mailer);
            $smtp[] = ['k' => 'transporte', 'v' => $transport === $mailer ? (string) $mailer : $mailer.' · '.$transport];
        }

        if ($host = config('mail.mailers.'.config('mail.default').'.host')) {
            $smtp[] = ['k' => 'host', 'v' => (string) $host];
        }

        $ids = [
            ['k' => 'mail_id', 'v' => (string) $mail->id],
            ['k' => 'ticket_id', 'v' => (string) $ticket->id],
            ['k' => 'dirección', 'v' => $mail->direction === 'inbound' ? 'entrante' : 'saliente'],
            ['k' => 'estado', 'v' => (string) ($mail->status ?: '—')],
        ];

        if ($mail->message_id) {
            $ids[] = ['k' => 'message_id', 'v' => trim($mail->message_id, '<>')];
        }

        // El log solo existe si el mailable usa TracksEmailLog; cuando está,
        // su uid es la forma de saltar al registro completo del envío.
        if ($log) {
            $ids[] = ['k' => 'email_log', 'v' => (string) $log->uid];
            if ($log->external_id) {
                $ids[] = ['k' => 'id del proveedor', 'v' => (string) $log->external_id];
            }
        }

        return ['smtp' => $smtp, 'ids' => $ids];
    }

    /**
     * Cada evento lleva 'at' (ISO-8601 crudo, se conserva por si algún
     * consumidor futuro necesita ordenar/comparar fechas) Y 'at_human'
     * (diffForHumans, mismo patrón created_at/created_at_human que ya usan
     * Hilo/Actividad/Correo/Archivos en este mismo controlador) — antes solo
     * existía 'at' crudo y el JS lo imprimía tal cual ('2026-08-28T14:06:19
     * +00:00'), inconsistente con el resto de la pantalla.
     */
    private function traceFor(TicketMail $mail, ?EmailLog $log): array
    {
        // Antes esto exigía un EmailLog correlacionado y devolvía [] si no
        // lo había: la pestaña Traza se quedaba en blanco aunque el propio
        // TicketMail tuviera sent_at/delivered_at. No todos los correos de
        // ticket pasan por el módulo de log (solo los mailables que usan
        // TracksEmailLog), así que se construye la traza con lo que hay: el
        // log cuando existe, y el TicketMail como fuente en cualquier caso.
        if (! $log && ! $mail->sent_at && ! $mail->delivered_at && ! $mail->delivery_error) {
            return [];
        }

        // 'detail' es la segunda línea de cada hito en la pestaña Traza: el
        // dato concreto que explica el paso (el mailable que lo encoló, el
        // error que devolvió el servidor…). Vacío cuando no hay nada real que
        // contar — no se rellena con texto de ejemplo.
        $events = [
            [
                'type' => 'queued',
                'label' => 'Encolado para envío',
                'detail' => $mail->headers['Mailable'] ?? ($log?->mailable_class),
                'at' => ($log?->created_at ?? $mail->created_at)?->toIso8601String(),
                'at_human' => ($log?->created_at ?? $mail->created_at)?->diffForHumans(),
            ],
        ];

        if ($sentAt = ($log?->sent_at ?? $mail->sent_at)) {
            $events[] = ['type' => 'sent', 'label' => 'Aceptado por el servidor de correo', 'detail' => $mail->from ? 'desde '.$mail->from : null, 'at' => $sentAt->toIso8601String(), 'at_human' => $sentAt->diffForHumans()];
        }
        if ($mail->delivered_at) {
            $events[] = ['type' => 'delivered', 'label' => 'Entregado al destinatario', 'detail' => $mail->to, 'at' => $mail->delivered_at->toIso8601String(), 'at_human' => $mail->delivered_at->diffForHumans()];
        }
        if ($log?->bounced_at) {
            $events[] = ['type' => 'bounced', 'label' => 'Rebotado', 'detail' => $mail->delivery_error, 'at' => $log->bounced_at->toIso8601String(), 'at_human' => $log->bounced_at->diffForHumans()];
        }
        if ($failedAt = ($log?->failed_at ?? ($mail->status === 'failed' ? $mail->updated_at : null))) {
            $events[] = ['type' => 'failed', 'label' => 'Fallido', 'detail' => $mail->delivery_error, 'at' => $failedAt->toIso8601String(), 'at_human' => $failedAt->diffForHumans()];
        }
        // Misma forma para aperturas y clics (un evento por hit, sin
        // agregar) — solo cambia qué colección y qué columna de fecha leer.
        foreach ([
            ['items' => $log?->opens ?? [], 'field' => 'opened_at', 'type' => 'opened', 'label' => 'Abierto por el destinatario'],
            ['items' => $log?->clicks ?? [], 'field' => 'clicked_at', 'type' => 'clicked', 'label' => 'Enlace clicado por el destinatario'],
        ] as $group) {
            foreach ($group['items'] as $item) {
                $at = $item->{$group['field']};
                $events[] = [
                    'type' => $group['type'],
                    'label' => $group['label'],
                    // El user agent es lo único que el log guarda de cada
                    // apertura/clic; se recorta porque son cadenas larguísimas.
                    'detail' => isset($item->user_agent) ? Str::limit((string) $item->user_agent, 60) : null,
                    'at' => $at?->toIso8601String(),
                    'at_human' => $at?->diffForHumans(),
                ];
            }
        }

        return $events;
    }
}
