<?php

namespace Modules\HelpdeskTickets\Models\Concerns;

use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Http\Controllers\SharedTicketController;
use Modules\HelpdeskTickets\Models\TicketStatus;

trait HasTicketPresentation
{
    /**
     * Bootstrap contextual color for the ticket priority badge.
     */
    public function getPriorityColorAttribute(): string
    {
        return self::PRIORITY_COLORS[$this->priority] ?? 'secondary';
    }

    /**
     * Get total logged time in minutes.
     */
    public function getTotalTimeMinutesAttribute(): int
    {
        return (int) $this->timeEntries()->sum('minutes');
    }

    /**
     * Get total logged time as a human-readable string.
     */
    public function getFormattedTotalTimeAttribute(): string
    {
        $total = $this->total_time_minutes;
        $hours = intdiv($total, 60);
        $mins = $total % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$mins}m";
    }

    /**
     * Slug "lógico" de estado para el frontend, a partir del TicketStatus
     * real. Mapea a los slugs canónicos que usa el JS (open, progress,
     * pending, resolved, closed) — antes vivía como closure inline en
     * index.blade.php; se extrae aquí para que SSR y el futuro JSON de
     * refetch (TicketsCrudController::index() con wantsJson()) no diverjan,
     * mismo patrón que TicketMail::toListRow().
     */
    public function statusSlug(): string
    {
        return static::canonicalStatusSlug($this->status);
    }

    /**
     * Misma normalización que statusSlug() pero sobre un TicketStatus suelto,
     * para poder agrupar el CATÁLOGO (una tabla de decenas de filas) en vez de
     * recorrer los tickets uno a uno. Es lo que permite a
     * TicketsCrudController::tabCounts() contar con una sola agregación SQL en
     * lugar de hidratar la tabla entera.
     */
    public static function canonicalStatusSlug(?TicketStatus $status): string
    {
        if (! $status) {
            return 'open';
        }
        $raw = $status->slug ?? str($status->name ?? '')->slug()->toString();
        $known = ['open', 'progress', 'pending', 'resolved', 'closed'];
        if (in_array($raw, $known, true)) {
            return $raw;
        }

        // El catálogo real (HelpdeskTicketStatusSeeder / gestión de estados
        // en Settings) admite más estados que los 5 "canónicos" que usan las
        // tabs/kanban del listado — se agrupan aquí para no perder ninguno
        // en un bucket erróneo (antes cualquier slug no reconocido caía
        // ciegamente en open/closed según is_open, metiendo "en espera del
        // cliente"/"en pausa" dentro de "Abiertos" en vez de "Pendientes").
        $aliases = [
            'waiting-customer' => 'pending',
            'waiting_customer' => 'pending',
            'on-hold' => 'pending',
            'on_hold' => 'pending',
            'escalated' => 'open',
            'reopened' => 'open',
            'new' => 'open',
        ];
        if (isset($aliases[$raw])) {
            return $aliases[$raw];
        }

        return ($status->is_open ?? true) ? 'open' : 'closed';
    }

    /**
     * Slug de origen normalizado para el badge de canal. Cualquier source no
     * reconocido se deja tal cual (nunca cae silenciosamente en 'email' —
     * bug real que hubo antes de extraer esto).
     */
    public function sourceSlug(): string
    {
        $known = ['email', 'widget', 'wa', 'fb', 'ig', 'whatsapp', 'facebook', 'instagram', 'agent', 'formulario', 'web_form'];
        $aliases = ['whatsapp' => 'wa', 'facebook' => 'fb', 'instagram' => 'ig'];
        $s = strtolower((string) $this->source);

        return $aliases[$s] ?? $s;
    }

    /**
     * Plantillas de las URLs de acción del listado — se generan UNA sola vez
     * por respuesta (no por fila) y viajan en el contenedor
     * (data-ticket-url-templates en index.blade.php); tickets-app.js las
     * expande sustituyendo '__TICKET__' por el id real de cada ticket al
     * hidratar TKA.state.tickets. Antes cada fila de toListRow() llamaba a
     * route() ~27 veces con el mismo patrón salvo el id — con paginación de
     * 50, un refetch completo son ~1.350 generaciones de ruta redundantes.
     * Las que ya tenían un segundo placeholder para su sub-recurso
     * (__MACRO__/__NOTE__/__SIDE__/__FOLLOWUP__) lo conservan sin cambios.
     *
     * @return array<string, string>
     */
    public static function listRowUrlTemplates(): array
    {
        return [
            'url' => route('manager.helpdesk.tickets.show', ['ticket' => '__TICKET__']),
            'url_data' => route('manager.helpdesk.tickets.data', ['ticket' => '__TICKET__']),
            // Modal 03 "Plantillas de email": el mismo listado de
            // TicketCannedReply que ya viaja en data-canned-replies, pero con
            // {{...}} resuelto contra este ticket concreto.
            'url_canned_replies' => route('manager.helpdesk.tickets.canned-replies', ['ticket' => '__TICKET__']),
            // Sonda del refresco automático; ver TicketDetailDataController::pulse().
            'url_pulse' => route('manager.helpdesk.tickets.pulse', ['ticket' => '__TICKET__']),
            'url_draft' => route('manager.helpdesk.tickets.draft.update', ['ticket' => '__TICKET__']),
            'url_tasks_store' => route('manager.helpdesk.tickets.tasks.store', ['ticket' => '__TICKET__']),
            'url_task_template' => route('manager.helpdesk.tickets.tasks.update', ['ticket' => '__TICKET__', 'task' => '__TASK__']),
            'url_subtickets_store' => route('manager.helpdesk.tickets.subtickets.store', ['ticket' => '__TICKET__']),
            'url_custom_fields' => route('manager.helpdesk.tickets.custom-fields.update', ['ticket' => '__TICKET__']),
            'url_dispute_review' => route('manager.helpdesk.tickets.ai.dispute-review', ['ticket' => '__TICKET__']),
            'url_message_store' => route('manager.helpdesk.tickets.messages.store', ['ticket' => '__TICKET__']),
            'url_update' => route('manager.helpdesk.tickets.update', ['ticket' => '__TICKET__']),
            'url_close' => route('manager.helpdesk.tickets.close', ['ticket' => '__TICKET__']),
            'url_resolve' => route('manager.helpdesk.tickets.resolve', ['ticket' => '__TICKET__']),
            'url_reopen' => route('manager.helpdesk.tickets.reopen', ['ticket' => '__TICKET__']),
            'url_tags' => route('manager.helpdesk.tickets.tags', ['ticket' => '__TICKET__']),
            'url_snooze' => route('manager.helpdesk.tickets.snooze', ['ticket' => '__TICKET__']),
            'url_link' => route('manager.helpdesk.tickets.link', ['ticket' => '__TICKET__']),
            'url_merge' => route('manager.helpdesk.tickets.merge', ['ticket' => '__TICKET__']),
            'url_side_conversations_store' => route('manager.helpdesk.tickets.side-conversations.store', ['ticket' => '__TICKET__']),
            'url_side_conversations_message_template' => route('manager.helpdesk.tickets.side-conversations.messages.store', ['ticket' => '__TICKET__', 'sideConversation' => '__SIDE__']),
            'url_macro_apply_template' => route('manager.helpdesk.tickets.macros.apply', ['ticket' => '__TICKET__', 'macro' => '__MACRO__']),
            'url_followups_store' => route('manager.helpdesk.tickets.followups.store', ['ticket' => '__TICKET__']),
            'url_followup_destroy_template' => route('manager.helpdesk.tickets.followups.destroy', ['ticket' => '__TICKET__', 'followup' => '__FOLLOWUP__']),
            'url_followups_destroy_all' => route('manager.helpdesk.tickets.followups.destroy-all', ['ticket' => '__TICKET__']),
            'url_ai_apply' => route('manager.helpdesk.tickets.apply-ai-suggestion', ['ticket' => '__TICKET__']),
            // Borrador sugerido de la franja de IA del composer.
            'url_ai_suggest_reply' => route('manager.helpdesk.tickets.ai.suggest-reply', ['ticket' => '__TICKET__']),
            // Modal 46: candidatos a duplicado del mismo cliente.
            'url_duplicates' => route('manager.helpdesk.tickets.ai.duplicates', ['ticket' => '__TICKET__']),
            // Unificar duplicados (v2): resumen de cada ticket y unificación en
            // bloque. La v1 (url_duplicates + url_merge) se mantiene intacta.
            'url_unify_summary' => route('manager.helpdesk.tickets.unify.summary', ['ticket' => '__TICKET__']),
            'url_unify' => route('manager.helpdesk.tickets.unify', ['ticket' => '__TICKET__']),
            // Lista negra desde el propio ticket (correo y/o dominio) + borrado.
            'url_blacklist' => route('manager.helpdesk.tickets.blacklist', ['ticket' => '__TICKET__']),
            'url_note_destroy_template' => route('manager.helpdesk.tickets.notes.destroy', ['ticket' => '__TICKET__', 'note' => '__NOTE__']),
            'url_note_pin_template' => route('manager.helpdesk.tickets.notes.pin', ['ticket' => '__TICKET__', 'note' => '__NOTE__']),
            'url_note_color_template' => route('manager.helpdesk.tickets.notes.color', ['ticket' => '__TICKET__', 'note' => '__NOTE__']),
            'url_summary' => route('manager.helpdesk.tickets.summary', ['ticket' => '__TICKET__']),
            // Tarjeta "Tiempo invertido" del panel de gestión.
            'url_time_entries' => route('manager.helpdesk.tickets.time-entries.index', ['ticket' => '__TICKET__']),
            'url_time_entry_destroy_template' => route('manager.helpdesk.tickets.time-entries.destroy', ['ticket' => '__TICKET__', 'timeEntry' => '__ENTRY__']),
            'url_watch' => route('manager.helpdesk.tickets.watch', ['ticket' => '__TICKET__']),
            'url_unwatch' => route('manager.helpdesk.tickets.unwatch', ['ticket' => '__TICKET__']),
            'url_destroy' => route('manager.helpdesk.tickets.destroy', ['ticket' => '__TICKET__']),
            'url_archive' => route('manager.helpdesk.tickets.archive', ['ticket' => '__TICKET__']),
            'url_send_csat' => route('manager.helpdesk.tickets.csat.send', ['ticket' => '__TICKET__']),
            // Modal 40: la valoración recibida y su contexto.
            'url_csat' => route('manager.helpdesk.tickets.csat.show', ['ticket' => '__TICKET__']),
            // Modal 50 "Traducir respuesta": traduce un texto suelto antes de
            // enviarlo (distinto de emails.translate, que traduce un correo ya
            // registrado).
            'url_translate_text' => route('manager.helpdesk.tickets.translate', ['ticket' => '__TICKET__']),
            // Modal 39: separar mensajes en un ticket nuevo.
            'url_split' => route('manager.helpdesk.tickets.split', ['ticket' => '__TICKET__']),
            // Modal 23: avisar a un agente presente en el ticket.
            'url_presence_nudge' => route('manager.helpdesk.tickets.presence.nudge', ['ticket' => '__TICKET__']),
            // Late mientras el detalle está abierto ("estoy viendo/
            // respondiendo este ticket") y avisa al cerrarlo — el mismo
            // heartbeat/leave de TicketPresenceController que ya existía
            // pero ningún JS llamaba (QA 14-sep-2026): el listado ahora
            // pinta un punto de presencia por fila con este dato.
            'url_presence_heartbeat' => route('manager.helpdesk.tickets.presence.heartbeat', ['ticket' => '__TICKET__']),
            'url_presence_leave' => route('manager.helpdesk.tickets.presence.leave', ['ticket' => '__TICKET__']),
            // Modal 25: pedidos PrestaShop del cliente, bajo demanda.
            'url_customer_orders' => route('manager.helpdesk.tickets.customer-360.orders', ['ticket' => '__TICKET__']),
            // Modal 32: enviar el enlace mágico de acceso al portal.
            'url_portal_send_access' => route('manager.helpdesk.tickets.portal.send-access', ['ticket' => '__TICKET__']),
        ];
    }

    /**
     * Contrato único de fila para el listado — lo usan tanto la hidratación
     * SSR de index.blade.php como el futuro JSON de refetch
     * (TicketsCrudController::index() con wantsJson()), igual que
     * TicketMail::toListRow() para la bandeja de emails. Tenerlo en un solo
     * sitio evita que SSR y AJAX diverjan en los nombres de campo.
     *
     * Las URLs de acción NO viajan aquí: son derivables del id y se generan
     * una sola vez como plantilla en listRowUrlTemplates(); tickets-app.js
     * las expande por fila. Solo queda url_shared_ticket, que es una URL
     * firmada (temporarySignedRoute) y por tanto no se puede plantillar.
     *
     * @return array<string, mixed>
     */
    /**
     * Texto de la tercera línea de la fila del listado: el último mensaje del
     * hilo o, a falta de él, la descripción original del ticket.
     */
    private function listSnippet(): ?string
    {
        $source = null;

        if ($this->relationLoaded('lastMessage') && $this->lastMessage) {
            $source = $this->lastMessage->body ?: $this->lastMessage->html_body;
        }

        // Sin mensajes en el hilo se cae a la descripción, pero en los
        // tickets de formulario ésa es el VOLCADO de campos
        // ("Firstname: …\nLastname: …\nPhone: …"): la fila del listado
        // acababa enseñando datos de contacto cortados a mitad en vez de
        // qué pide el cliente. Se busca primero el campo de texto libre del
        // propio formulario, que es el equivalente real al último mensaje.
        $source ??= $this->formMessageField();
        $source ??= $this->description;

        $clean = trim(strip_tags((string) $source));

        // Los saltos de línea del volcado se compactan con separador: si
        // aun así toca mostrarlo, que se lea de corrido y no se corte en
        // mitad de una etiqueta de campo.
        $clean = trim((string) preg_replace('/\s*\n+\s*/u', ' · ', $clean));

        return $clean === '' ? null : Str::limit($clean, 90);
    }

    /**
     * Texto libre que escribió el cliente en un formulario, si lo hay.
     *
     * Los formularios guardan cada campo por separado en custom_fields; solo
     * uno de ellos es el mensaje. Se prueban los nombres habituales y se
     * exige una longitud mínima para no confundir un "Motivo: Envío" (una
     * opción de un desplegable) con lo que el cliente escribió.
     */
    private function formMessageField(): ?string
    {
        $fields = $this->custom_fields;

        if (! is_array($fields) || $fields === []) {
            return null;
        }

        foreach (['message', 'mensaje', 'comment', 'comments', 'comentario', 'comentarios',
            'observations', 'observaciones', 'consulta', 'descripcion', 'description',
            'body', 'texto', 'pregunta'] as $key) {
            $value = $fields[$key] ?? null;

            if (is_string($value) && mb_strlen(trim($value)) >= 20) {
                return trim($value);
            }
        }

        return null;
    }

    public function toListRow(): array
    {
        $assignee = null;
        if ($this->assignee) {
            $name = $this->assignee->fullName() ?: 'Agente';
            $assignee = ['id' => $this->assignee->id, 'name' => $name];
        }

        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject ?? $this->title,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority ?? 'normal',
            'source' => $this->sourceSlug(),
            'tags' => is_array($this->tags) ? array_values($this->tags) : [],
            'status_id' => $this->status_id,
            'status_slug' => $this->statusSlug(),
            'status_name' => $this->status?->name,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'group_id' => $this->group_id,
            'group_name' => $this->group?->name,
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                // Segunda línea de la fila en el mockup: "Gabriel Morales ·
                // Construcinsa S.A. de C.V." — el nombre solo cuando el
                // contacto no está asociado a ninguna empresa.
                'company' => $this->customer->relationLoaded('company') ? $this->customer->company?->name : null,
            ] : null,
            // Tercera línea de la fila: resumen del último mensaje del hilo,
            // o la descripción con la que se abrió el ticket si aún no hay
            // ninguno. En el mockup TODAS las filas tienen esta línea; sin el
            // respaldo, la lista alternaba filas de 96 y 79 px y perdía la
            // sensación de rejilla.
            'last_message_snippet' => $this->listSnippet(),
            // Clip de adjuntos junto al asunto. attachment_urls del último
            // mensaje es lo que ya usa el hilo para pintar los ficheros.
            'has_attachments' => $this->relationLoaded('lastMessage') && $this->lastMessage
                ? ! empty($this->lastMessage->attachment_urls)
                : false,
            // Chip de entrega de la cabecera del detalle. null cuando el
            // ticket no ha generado ningún correo saliente (widget, WhatsApp,
            // ticket creado a mano): entonces no hay entrega que reportar y
            // el chip no se pinta.
            'last_mail_status' => $this->relationLoaded('lastOutboundMail')
                ? $this->lastOutboundMail?->status
                : null,
            'assignee' => $assignee,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            // Si el agente ya respondió al cliente — dato distinto del SLA
            // de resolución (sla_text/sla_kind, que sigue el plazo de
            // CERRAR el ticket, no el de responder). Sin esto la fila del
            // listado no podía distinguir "nadie le ha contestado todavía"
            // de "ya le contestamos, solo falta cerrarlo" — confusión real
            // de un agente que veía "vencido" en negro tras haber respondido
            // (QA visual 14-sep-2026).
            'first_response_at' => $this->first_response_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'close_reason' => $this->close_reason,
            'close_reason_label' => $this->close_reason
                ? ((array) config('helpdesktickets.close_reasons', []))[$this->close_reason] ?? $this->close_reason
                : null,
            'unread_count' => (int) ($this->unread_count ?? $this->getUnreadCountForUser(auth()->id())),
            // Contador total de mensajes (mockup: 💬 N) — distinto del
            // punto rojo de "no leído", que se conserva porque transmite
            // algo que el conteo no dice (hay algo nuevo que mirar).
            'message_count' => $this->message_count ?? null,
            'sla_kind' => $this->slaRowKind(),
            // Nivel de escalado vigente (0 = sin escalar). Con el escalado en
            // modo 'flag' es la única señal visible de que se escaló.
            'escalation_level' => $this->escalated_at && ! $this->closed_at && ! $this->resolved_at
                ? (int) $this->escalation_count
                : 0,
            'sla_text' => $this->slaRowText(),
            'sla_status' => $this->sla_status,
            'sla_due_at' => $this->slaEffectiveDueDate()?->toIso8601String(),
            // "Ver como el cliente" del mockup, versión segura: enlace
            // firmado de solo lectura (SharedTicketController), no
            // suplantación de sesión — ver su docblock para el porqué.
            'url_shared_ticket' => SharedTicketController::signedShowUrl($this),
        ];
    }
}
