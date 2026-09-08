@extends('layouts.theme')

@section('title', 'Tickets')

@push('css')
    {{-- Las dos familias del mockup. El tema ya carga Inter, pero solo hasta
         el peso 700 (el mockup usa 800 en los titulares) y NO carga JetBrains
         Mono en absoluto: sin esto, todo el texto monoespaciado de la pantalla
         (nº de ticket, fechas, Message-ID, contadores) caía al monospace del
         sistema, con métricas distintas a las del mockup — de ahí que la fila
         del listado midiera 2px más de la cuenta. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap">
    {{-- ?v=filemtime evita que el navegador sirva una versión en caché tras
         cada cambio (mismo patrón que modules/Helpdesk/.../inbox/index.blade.php) --}}
    <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/tickets-app.css') }}?v={{ @filemtime(public_path('modules/helpdesktickets/css/tickets-app.css')) }}">
@endpush

@php
    // Ticket::toListRow() es la única fuente del contrato de fila (mismo
    // patrón que TicketMail::toListRow() para la bandeja de emails) — la usa
    // tanto esta hidratación SSR como el futuro JSON de refetch.
    $ticketsPayload = $tickets->getCollection()->map(fn ($t) => $t->toListRow())->values();

    // El ticket preseleccionado (?ticket=) puede no estar en la página/filtro
    // actual del listado: se antepone al payload para que el JS lo encuentre.
    if ($selectedTicket && ! $ticketsPayload->contains('id', $selectedTicket->id)) {
        $ticketsPayload->prepend($selectedTicket->toListRow());
    }

    // Plantillas de las URLs de acción del listado (PERF-09): una sola
    // generación de las ~27 rutas por respuesta en vez de una por fila —
    // toListRow() ya no las incluye, tickets-app.js las expande sustituyendo
    // '__TICKET__' por el id de cada ticket. Ver el docblock del método.
    $ticketUrlTemplates = \Modules\HelpdeskTickets\Models\Ticket::listRowUrlTemplates();
@endphp

{{-- Sin page_header y con content_full_width: mismo criterio que el inbox
     de Conversaciones (modules/Helpdesk/.../inbox/index.blade.php) — el
     título ocupaba una franja fija arriba que le restaba alto útil a la
     pantalla; aquí, con 3 columnas de por sí apretadas de espacio, importa
     más aprovechar el viewport completo que repetir "Tickets" dos veces. --}}
@section('content_full_width', true)

@section('content')
<div class="tkt">

    {{-- Datos para el JS --}}
    <div id="tkt-data"
         data-tickets="{{ json_encode($ticketsPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-tab-counts="{{ json_encode($tabCounts, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-user-id="{{ auth()->id() }}"
         data-initial-filter="{{ request('quick_filter', 'all') }}"
         data-initial-view="{{ request('view', 'list') }}"
         data-selected-id="{{ $selectedTicket?->id }}"
         data-bulk-url="{{ route('manager.helpdesk.tickets.bulk') }}"
         data-index-url="{{ route('manager.helpdesk.tickets.index') }}"
         data-emails-index-url="{{ route('manager.helpdesk.tickets.emails.index') }}"
         {{-- Acciones por mensaje del hilo (reenviar / ver original): operan
              sobre el TicketMail asociado al item, de ahí el placeholder. --}}
         data-mail-resend-url-template="{{ route('manager.helpdesk.tickets.emails.resend', ['mail' => '__MAIL__']) }}"
         data-mail-data-url-template="{{ route('manager.helpdesk.tickets.emails.data', ['mail' => '__MAIL__']) }}"
         data-notes-store-url-template="{{ route('manager.helpdesk.tickets.notes.store', ['ticket' => '__TICKET__']) }}"
         data-ticket-url-templates="{{ json_encode($ticketUrlTemplates, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-views-store-url="{{ route('manager.helpdesk.tickets.views.store') }}"
         data-contacts-sync-url-template="{{ Nwidart\Modules\Facades\Module::isEnabled('HelpdeskContacts') ? route('contacts.sync', ['customer' => '__CUSTOMER__']) : '' }}"
         data-macros-list-url="{{ route('manager.helpdesk.macros.list') }}"
         data-emails-store-url="{{ route('manager.helpdesk.tickets.emails.store') }}"
         data-typing-url-template="{{ route('manager.helpdesk.tickets.typing', ['ticket' => '__TICKET__']) }}"
         data-export-url-template="{{ route('manager.helpdesk.tickets.export', ['format' => '__FORMAT__']) }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}"
         data-automations-index-url="{{ route('manager.helpdesk.settings.automations.index') }}"
         {{-- Modal de horario y SLA. --}}
         data-sla-calendar-url="{{ route('manager.helpdesk.tickets.sla-calendar') }}"
         data-sla-pause-status-url="{{ route('manager.helpdesk.tickets.sla-calendar.pause-status') }}"
         {{-- Modal de reglas de escalado. --}}
         data-automations-list-url="{{ route('manager.helpdesk.tickets.automations.index') }}"
         data-automations-store-url="{{ route('manager.helpdesk.tickets.automations.store') }}"
         data-automations-preview-url="{{ route('manager.helpdesk.tickets.automations.preview') }}"
         data-automations-toggle-url-template="{{ route('manager.helpdesk.tickets.automations.toggle', ['automation' => '__AUTOMATION__']) }}"
         {{-- Modal de carga de agentes: resumen y ajustes de reparto. --}}
         data-workload-overview-url="{{ route('manager.helpdesk.tickets.workload.overview') }}"
         data-workload-assignment-url="{{ route('manager.helpdesk.tickets.workload.assignment') }}"
         {{-- Modal de buzones de entrada: estado, comportamiento y prueba. --}}
         data-mailboxes-url="{{ route('manager.helpdesk.tickets.mailboxes.index') }}"
         data-mailbox-behavior-url-template="{{ route('manager.helpdesk.tickets.mailboxes.behavior', ['channel' => '__MBX__']) }}"
         data-mailbox-test-url-template="{{ route('manager.helpdesk.tickets.mailboxes.test', ['channel' => '__MBX__']) }}"
         {{-- Modal de tickets recurrentes: alta, edición y pausa. --}}
         data-recurring-ops-url="{{ route('manager.helpdesk.tickets.recurring.index') }}"
         data-recurring-store-url="{{ route('manager.helpdesk.tickets.recurring.store') }}"
         data-recurring-update-url-template="{{ route('manager.helpdesk.tickets.recurring.update', ['recurringTicket' => '__REC__']) }}"
         data-recurring-toggle-url-template="{{ route('manager.helpdesk.tickets.recurring.toggle', ['recurringTicket' => '__REC__']) }}"
         {{-- Preferencias de aviso del agente para los eventos de ticket. --}}
         data-notif-prefs-url="{{ route('manager.helpdesk.tickets.notification-preferences') }}"
         data-notif-prefs-update-url="{{ route('manager.helpdesk.tickets.notification-preferences.update') }}"
         {{-- Modal 31: webhooks de Slack/Teams (canal de equipo). --}}
         data-notif-team-channels-url="{{ route('manager.helpdesk.tickets.notification-preferences.team-channels') }}"
         data-notif-team-channels-test-url="{{ route('manager.helpdesk.tickets.notification-preferences.team-channels.test') }}"
         {{-- Cuántos tickets saldrían con el alcance elegido en el modal de exportar. --}}
         data-export-estimate-url="{{ route('manager.helpdesk.tickets.export-estimate') }}"
         {{-- Buscador de ticket destino para "Fusionar" y "Vincular ticket". --}}
         data-ticket-search-url="{{ route('manager.helpdesk.tickets.search') }}"
         {{-- Destino de "Abrir el panel de avisos" del modal de notificaciones. --}}
         @if (Route::has('notifications.index'))
         data-notifications-index-url="{{ route('notifications.index') }}"
         @endif
         {{-- Enlace "Auditoría" del panel de historial. El módulo Activity es
              opcional: sin él, el botón simplemente no se pinta. --}}
         @if (Route::has('activity.audit'))
         data-activity-audit-url="{{ route('activity.audit') }}"
         @endif
         {{-- "Calendario" de la card SLA: el horario laboral vive en la
              política (columna business_hours), así que el enlace lleva ahí. --}}
         data-sla-policies-url="{{ route('manager.helpdesk.settings.ticket-sla-policies.index') }}"
         data-ticket-templates-index-url="{{ route('manager.helpdesk.ticket-templates.index') }}"
         data-ticket-create-url="{{ route('manager.helpdesk.tickets.create') }}"
         data-settings-snapshot-url="{{ route('manager.helpdesk.tickets.settings-snapshot') }}"
         data-email-channels-url="{{ route('manager.helpdesk.settings.email-channels.index') }}"
         data-recurring-url="{{ route('manager.helpdesk.recurring-tickets.index') }}"
         {{-- Modal 21: guarda la plantilla editada. PUT real da 405 por AJAX en
              este entorno Docker, así que la plantilla de URL se usa con POST +
              _method=PUT (gotcha ya documentado del proyecto). --}}
         data-canned-update-url-template="{{ route('manager.helpdesk.settings.ticket-canned-replies.update', ['reply' => '__REPLY__']) }}"
         {{-- Remitentes elegibles del modal "Redactar email". Lista cerrada
              (ver TicketMailsController::availableSenders()); el backend
              vuelve a validar contra ella, no se fía de este campo. --}}
         data-senders="{{ json_encode(\Modules\HelpdeskTickets\Http\Controllers\Managers\TicketMailsController::availableSenders(), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         @if(Nwidart\Modules\Facades\Module::isEnabled('HelpdeskContacts'))
         data-contacts-merge-search-url-template="{{ route('contacts.merge.search', ['customer' => '__CUSTOMER__']) }}"
         data-contacts-merge-preview-url-template="{{ route('contacts.merge.preview', ['customer' => '__CUSTOMER__']) }}"
         data-contacts-merge-execute-url-template="{{ route('contacts.merge.execute', ['customer' => '__CUSTOMER__']) }}"
         @endif
         data-ops-url="{{ route('manager.helpdesk.tickets.ops') }}"
         data-macros-index-url="{{ route('manager.helpdesk.settings.macros.index') }}"
         {{-- Modal 35 "Nuevo ticket": crear sin salir del listado. Los
              clientes van en el payload (son 84, no hace falta un buscador
              contra el servidor); el aviso de duplicado se pide aparte. --}}
         data-ticket-store-url="{{ route('manager.helpdesk.tickets.store') }}"
         data-duplicates-preview-url="{{ route('manager.helpdesk.tickets.duplicates-preview') }}"
         data-customers="{{ json_encode($customers, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-queue-retry-url="{{ route('manager.helpdesk.tickets.queue.retry') }}"
         data-queue-flush-url="{{ route('manager.helpdesk.tickets.queue.flush') }}"
         data-workload-url="{{ route('manager.helpdesk.tickets.workload') }}"
         {{-- Modal 22: SPF/DKIM/DMARC reales del dominio de envío. --}}
         data-reputation-url="{{ route('manager.helpdesk.tickets.reputation') }}"
         {{-- Modal 27: "aplicar automáticamente si la confianza supera el 90%". --}}
         data-ai-auto-apply-url="{{ route('manager.helpdesk.tickets.ai-auto-apply.update') }}"
         data-workload-distribute-url="{{ route('manager.helpdesk.tickets.workload.distribute') }}"
         {{-- description/stops_sla_timer/is_closed alimentan el modal "Cambiar
              estado": describen la consecuencia real de cada estado en ESTE
              catálogo en vez de un texto fijo por slug, que mentiría en
              cuanto alguien añada o reconfigure un estado. --}}
         data-statuses="{{ json_encode($statuses->map(fn ($s) => [
             'id' => $s->id,
             'name' => $s->name,
             'slug' => $s->slug,
             'description' => $s->description,
             'stops_sla' => (bool) $s->stops_sla_timer,
             'is_closed' => (bool) $s->is_closed,
         ]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-categories="{{ json_encode($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-groups="{{ json_encode($groups->map(fn ($g) => ['id' => $g->id, 'name' => $g->name]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         {{-- El estado real de cada agente (conectado / no ha entrado nunca / de
              vacaciones / sin plaza) viaja con la lista: el modal de asignación
              pintaba "Agente" para todos, incluidos los que no han abierto el
              panel en su vida. --}}
         data-agents-full="{{ json_encode(app(\Modules\HelpdeskTickets\Services\AgentAvailabilityService::class)->describe($agents), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-canned-replies="{{ json_encode($cannedReplies->map(fn ($r) => ['id' => $r->id, 'title' => $r->title, 'content' => $r->content, 'short_code' => $r->short_code]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         {{-- Modal 44: crear un ticket ya relleno desde una plantilla. --}}
         data-ticket-templates="{{ json_encode($ticketTemplates->map(fn ($tpl) => ['id' => $tpl->id, 'name' => $tpl->name, 'description' => $tpl->description, 'subject' => $tpl->subject, 'body' => $tpl->body, 'category_id' => $tpl->category_id, 'category_name' => $tpl->category?->name, 'priority' => $tpl->priority]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-close-reasons="{{ json_encode(collect(config('helpdesktickets.close_reasons', []))->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         {{-- Causa raíz del cierre: por qué existió el ticket, para los informes. --}}
         data-close-root-causes="{{ json_encode(collect(config('helpdesktickets.close_root_causes', []))->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         hidden>
    </div>

    <div class="tkt-app-scroll">
    <div class="tkt-app-card">

        {{-- Barra superior --}}
        <div class="tkt-app-bar">
            <div class="tkt-crumbs">
                {{-- 3er nivel dinámico (comparado contra el mockup: "Helpdesk ›
                     Tickets › {tab activo}") — el texto lo mantiene renderTabs()
                     en tickets-app.js a partir de la misma etiqueta que ya usa
                     cada .tkt-state-tab, para no duplicar el mapeo de labels. --}}
                <i class="fa-solid fa-headset"></i><span>Helpdesk</span><i class="fa-solid fa-chevron-right"></i><span>Tickets</span><i class="fa-solid fa-chevron-right"></i><span class="on" id="tkt-crumb-tab">Todos</span>
            </div>
            <div class="tkt-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="tkt-search" aria-label="Buscar tickets" placeholder="Buscar por nº de ticket, cliente o asunto…" value="{{ request('search') }}">
            </div>
            <div class="tkt-toolbar-right">
                <div class="tkt-seg" id="tkt-mode-switch">
                    <button type="button" class="on" data-mode="list"><i class="fa-solid fa-list"></i> Lista</button>
                    <button type="button" data-mode="kanban"><i class="fa-solid fa-table-columns"></i> Kanban</button>
                </div>
                <button type="button" class="tkt-btn" id="tkt-sync" title="Resincronizar con PrestaShop/ERP"><i class="fa-solid fa-rotate"></i> Sincronizar</button>
                <button type="button" class="tkt-btn" id="tkt-export-open" title="Exportar los tickets del filtro actual"><i class="fa-solid fa-download"></i> Exportar</button>
                <a href="{{ route('manager.helpdesk.ticket-templates.index') }}" class="tkt-btn" id="tkt-templates-open"><i class="fa-solid fa-clone"></i> Plantillas</a>
                <a href="{{ route('manager.helpdesk.tickets.create') }}" class="tkt-btn tkt-btn-primary" id="tkt-new-ticket"><i class="fa-solid fa-plus"></i> Nuevo ticket</a>
            </div>
        </div>

        {{-- Tabs de estado (mismo orden/conjunto que el mockup: Todos/Sin
             asignar/Abiertos/Pendientes/Resueltos/Cerrados — Urgentes/Míos
             viven como chips de "Vistas" más abajo, no como tabs de estado) --}}
        <div class="tkt-state-tabs">
            <button type="button" class="tkt-state-tab on" data-filter="all">Todos <span class="c">{{ $tabCounts['all'] }}</span></button>
            <button type="button" class="tkt-state-tab" data-filter="unassigned">Sin asignar <span class="c">{{ $tabCounts['unassigned'] }}</span></button>
            <button type="button" class="tkt-state-tab" data-filter="open">Abiertos <span class="c">{{ $tabCounts['open'] }}</span></button>
            <button type="button" class="tkt-state-tab" data-filter="pending">Pendientes <span class="c">{{ $tabCounts['pending'] }}</span></button>
            <button type="button" class="tkt-state-tab" data-filter="resolved">Resueltos <span class="c">{{ $tabCounts['resolved'] }}</span></button>
            <button type="button" class="tkt-state-tab" data-filter="closed">Cerrados <span class="c">{{ $tabCounts['closed'] }}</span></button>
            {{-- "Plantillas" cierra la fila de tabs en el mockup. Una auditoría previa
                 lo había quitado por ser un <a> que navegaba fuera de la pantalla en
                 vez de filtrar como sus vecinos, y por duplicar el botón de la barra
                 superior. Se restaura porque la pantalla clona el mockup al pie de la
                 letra, pero con la clase .tkt-state-link en lugar de .tkt-state-tab:
                 mismo sitio y misma tipografía, sin fingir que es un filtro (no lleva
                 data-filter, así que renderTabs() y el listener de tabs lo ignoran). --}}
            <a href="{{ route('manager.helpdesk.ticket-templates.index') }}" class="tkt-state-link">Plantillas</a>
            {{-- "· cola de correo: N" lo rellena tickets-app.js al cargar (fetchOpsQueueHint(),
                 reusa TKA.urls.ops — misma fuente que ya alimenta el modal "Cola" — sin
                 duplicar ninguna sonda ni inventar un endpoint nuevo). Vacío hasta entonces. --}}
            <span class="tkt-queue-hint">SLA en riesgo: {{ $tabCounts['sla_risk'] }}<span id="tkt-mail-queue-hint"></span></span>
        </div>

        {{-- Filtros --}}
        <form method="get" id="tkt-filter-form" class="tkt-filter-bar">
            {{-- Los filtros que NO tienen chip propio aquí (Estado, Grupo, SLA,
                 Buzón, Tipo de email, Búsqueda…) viven en el modal "Más filtros",
                 que envía su PROPIO formulario. Como esta barra es un GET, todo
                 lo que no viaje en ella se pierde: bug real: filtrabas por Estado
                 en el modal, tocabas el chip "Prioridad" y el Estado desaparecía
                 sin aviso. Se arrastran como hidden — mismo criterio que el
                 <select> de orden de la lista, que ya construye su URL con
                 request()->except(['page','sort']).

                 Fuera de la lista: los siete campos que la barra sí controla (o
                 se duplicarían) y 'page' (cambiar de filtro tiene que devolver a
                 la página 1). Solo escalares: un array llegaría aquí como
                 "Array" y ensuciaría la URL. --}}
            @foreach(request()->except(['source', 'tag', 'category', 'assignee', 'priority', 'created_from', 'created_to', 'page']) as $carryKey => $carryValue)
                @if(is_scalar($carryValue) && filled($carryValue))
                    <input type="hidden" name="{{ $carryKey }}" value="{{ $carryValue }}">
                @endif
            @endforeach
            {{-- Chips de filtro: mismo orden y tipografía que el mockup
                 (Origen · Etiquetas · Categoría · Agente · Prioridad · rango de
                 fechas · Más filtros) — ver .tkt-fsel/.tkt-fchip en
                 tickets-app.css. El valor visible de cada chip ya no se calcula
                 aquí: es el texto de la <option> seleccionada, que select2 pinta
                 dentro del widget (ver el comentario del primer chip). --}}
            @php
                $sourceLabels = ['email' => 'Email', 'widget' => 'Widget', 'wa' => 'WhatsApp', 'fb' => 'Facebook', 'ig' => 'Instagram', 'formulario' => 'Formulario'];
                $priorityLabels = ['urgent' => 'Urgente', 'high' => 'Alta', 'normal' => 'Normal', 'low' => 'Baja'];
            @endphp
            {{-- Cada chip es un <select> real con select2 (initSelect2 en
                 tickets-app.js, rama [data-fchip-label]). Antes era un <select>
                 nativo transparente superpuesto sobre un <label> pintado: clonaba
                 el mockup al pixel, pero heredaba el desplegable del sistema —sin
                 buscador (el chip "Agente" tiene decenas de opciones) y con un
                 aspecto distinto en cada SO/navegador—. templateSelection
                 recompone dentro del widget el mismo contenido del chip
                 (etiqueta + valor en gris), así que el diseño no cambia. --}}
            <select class="tkt-fsel" name="source" data-fchip-label="Origen" aria-label="Filtrar por origen">
                <option value="">todos</option>
                @foreach($sourceLabels as $sourceValue => $sourceLabel)
                    <option value="{{ $sourceValue }}" @selected(request('source') === $sourceValue)>{{ $sourceLabel }}</option>
                @endforeach
            </select>
            {{-- "Etiqueta" en singular a propósito (hallazgo LOW #2): este <select>
                 solo admite UN valor exacto de una lista cerrada, a diferencia del
                 campo de texto libre "Etiquetas" (separadas por coma) del modal "Más
                 filtros" — mismo query param `tag`, pero affordance distinta. No se
                 fusionan los dos controles en esta pasada porque no está verificado si
                 el backend interpreta múltiples tags separados por coma; el copy queda
                 así como mínimo diferenciado para no prometer lo mismo en los dos sitios. --}}
            <select class="tkt-fsel" name="tag" data-fchip-label="Etiqueta" aria-label="Filtrar por etiqueta">
                <option value="">todas</option>
                {{-- El modal manda varias etiquetas separadas por coma ("vip,urgente",
                     que TicketsCrudController aplica como AND). Ese valor no existe
                     como <option> de este desplegable, así que el chip mostraba
                     "todas" —mintiendo sobre un filtro que sí estaba aplicado— y al
                     tocar cualquier otro chip lo enviaba vacío, borrándolo. La opción
                     se añade sobre la marcha para que el chip diga la verdad y el
                     filtro sobreviva al submit. --}}
                @if(request()->filled('tag') && ! $availableTags->contains(request('tag')))
                    <option value="{{ request('tag') }}" selected>{{ request('tag') }}</option>
                @endif
                @foreach($availableTags as $tagOption)
                    <option value="{{ $tagOption }}" @selected(request('tag') === $tagOption)>{{ $tagOption }}</option>
                @endforeach
            </select>
            <select class="tkt-fsel" name="category" data-fchip-label="Categoría" aria-label="Filtrar por categoría">
                <option value="">todas</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select class="tkt-fsel" name="assignee" data-fchip-label="Agente" aria-label="Filtrar por agente">
                <option value="">todos</option>
                <option value="me" @selected(request('assignee') === 'me')>Asignados a mí</option>
                <option value="unassigned" @selected(request('assignee') === 'unassigned')>Sin asignar</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" @selected(request('assignee') == $agent->id)>{{ trim($agent->firstname.' '.$agent->lastname) }}</option>
                @endforeach
            </select>
            <select class="tkt-fsel" name="priority" data-fchip-label="Prioridad" aria-label="Filtrar por prioridad">
                <option value="">todas</option>
                @foreach($priorityLabels as $priorityValue => $priorityLabel)
                    <option value="{{ $priorityValue }}" @selected(request('priority') === $priorityValue)>{{ $priorityLabel }}</option>
                @endforeach
            </select>
            {{-- Rango de fechas: en el mockup es UN solo chip que resume el periodo
                 ("19 ago – 01 sep 2026"), no dos <input type=date> sueltos. El chip
                 abre el daterangepicker que ya carga el layout del tema (con sus
                 presets y su locale en español, ver bindDateRangeChip) en vez del
                 calendario nativo del navegador, que venía con su propia tipografía
                 y su azul de sistema. Los dos hidden siguen mandando
                 created_from/created_to igual que el modal "Más filtros"
                 (Modules\Helpdesk\Filters\TicketFilter los valida y aplica). --}}
            @php
                $rangeFrom = request('created_from') ? \Illuminate\Support\Carbon::parse(request('created_from')) : null;
                $rangeTo = request('created_to') ? \Illuminate\Support\Carbon::parse(request('created_to')) : null;
                $rangeLabel = match (true) {
                    $rangeFrom && $rangeTo => $rangeFrom->translatedFormat('d M').' – '.$rangeTo->translatedFormat('d M Y'),
                    (bool) $rangeFrom => 'desde '.$rangeFrom->translatedFormat('d M Y'),
                    (bool) $rangeTo => 'hasta '.$rangeTo->translatedFormat('d M Y'),
                    default => 'Cualquier fecha',
                };
            @endphp
            <div class="tkt-fdate {{ ($rangeFrom || $rangeTo) ? 'on' : '' }}">
                <button type="button" class="tkt-fchip" id="tkt-daterange-open" aria-haspopup="dialog" aria-expanded="false"
                        data-from="{{ request('created_from') }}" data-to="{{ request('created_to') }}">
                    <span class="tkt-fchip-value">{{ $rangeLabel }}</span>
                    <i class="fa-solid fa-chevron-down tkt-fchip-caret" aria-hidden="true"></i>
                </button>
                <input type="hidden" name="created_from" id="tkt-created-from" value="{{ request('created_from') }}">
                <input type="hidden" name="created_to" id="tkt-created-to" value="{{ request('created_to') }}">
            </div>
            <button type="button" class="tkt-fchip" id="tkt-filters-modal-open">Más filtros</button>
            <span id="tkt-count" class="mono tkt-count">{{ $tickets->total() }} tickets</span>
            {{-- "limpiar" quita los FILTROS, no el contexto: la pestaña abierta,
                 el orden elegido, la vista guardada y el ticket que estés
                 mirando sobreviven. Antes apuntaba a la ruta pelada y un clic
                 te devolvía a "Todos", al orden por defecto y con el panel
                 derecho cerrado — casi nunca era lo que se buscaba. --}}
            <a href="{{ route('manager.helpdesk.tickets.index', request()->only(['quick_filter', 'view', 'sort', 'ticket', 'viewId'])) }}" class="tkt-clear-link">limpiar</a>
        </form>

        @php
            // Chips de filtro activo con ✕ para quitar uno a uno, en vez de
            // solo el link "limpiar" que borra todos de golpe.
            $activeFilterLabels = [
                'source' => 'Origen', 'category' => 'Categoría', 'assignee' => 'Agente',
                'priority' => 'Prioridad', 'tag' => 'Etiqueta', 'search' => 'Búsqueda',
                'created_from' => 'Desde', 'created_to' => 'Hasta', 'sla_status' => 'SLA',
                'mail_status' => 'Último correo', 'mail_type' => 'Tipo de email',
                'mailbox' => 'Buzón', 'has_attachments' => 'Adjuntos',
                // Estado y Grupo faltaban en esta lista aunque TicketFilter los
                // aplica de verdad (applyStatus/applyGroup): se filtraba por ellos
                // desde el modal y no aparecía ningún chip con ✕ — el filtro estaba
                // activo y era invisible, sin más salida que "limpiar" del todo.
                'status' => 'Estado', 'group' => 'Grupo',
            ];
            // Bug real de QA: el chip mostraba el valor CRUDO del query
            // param ("Prioridad: urgent", "Agente: 16") en vez de la
            // etiqueta legible — mismas fuentes que ya usan los <select> de
            // esta misma barra, para no duplicar ni desalinear el mapeo.
            $filterValueLabel = function (string $key, string $value) use ($categories, $agents, $statuses, $groups) {
                return match ($key) {
                    // Fecha legible: el chip mostraba el valor crudo del query
                    // param ("Desde: 2026-08-19") mientras el chip de rango de la
                    // barra, dos líneas más arriba, ya dice "19 ago – 01 sep 2026".
                    'created_from', 'created_to' => \Illuminate\Support\Carbon::parse($value)->translatedFormat('d M Y'),
                    'status' => $statuses->firstWhere('id', (int) $value)?->name ?? $value,
                    'group' => $groups->firstWhere('id', (int) $value)?->name ?? $value,
                    'priority' => ['urgent' => 'Urgente', 'high' => 'Alta', 'normal' => 'Normal', 'low' => 'Baja'][$value] ?? $value,
                    'source' => ['email' => 'Email', 'widget' => 'Widget', 'wa' => 'WhatsApp', 'fb' => 'Facebook', 'ig' => 'Instagram', 'formulario' => 'Formulario'][$value] ?? $value,
                    'sla_status' => ['breach' => 'Vencido', 'warn' => 'En riesgo', 'ok' => 'En plazo'][$value] ?? $value,
                    'mail_status' => ['delivered' => 'Entregado', 'sent' => 'Enviado', 'pending' => 'Pendiente', 'bounced' => 'Rebotado', 'failed' => 'Fallido'][$value] ?? $value,
                    'mail_type' => ['reply' => 'Respuesta al cliente', 'internal' => 'Aviso interno', 'inbound' => 'Entrante del cliente'][$value] ?? $value,
                    'has_attachments' => 'Solo con adjuntos',
                    'category' => $categories->firstWhere('id', (int) $value)?->name ?? $value,
                    'assignee' => match (true) {
                        $value === 'me' => 'Asignados a mí',
                        $value === 'unassigned' => 'Sin asignar',
                        default => trim((string) $agents->firstWhere('id', (int) $value)?->firstname.' '.(string) $agents->firstWhere('id', (int) $value)?->lastname) ?: $value,
                    },
                    default => $value,
                };
            };
            $activeFilters = collect($activeFilterLabels)
                ->filter(fn ($label, $key) => filled(request($key)))
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValueLabel($key, (string) request($key))]);
        @endphp
        @if($activeFilters->isNotEmpty())
            <div class="tkt-chip-row">
                @foreach($activeFilters as $f)
                    <a href="{{ route('manager.helpdesk.tickets.index', request()->except([$f['key'], 'page'])) }}" class="tkt-filter-chip tkt-link-plain">
                        {{ $f['label'] }}: {{ Str::limit($f['value'], 24) }} <i class="fa-solid fa-xmark"></i>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Vistas guardadas: los recortes rápidos que no son "estado" del
             ticket (Urgentes/Míos) viven aquí como chips client-side, igual
             que en el mockup, además de las vistas guardadas reales
             (TicketView) y el acceso a gestionarlas. --}}
        {{-- Barra de vistas. En el mockup son seis píldoras sin contador (Todos ·
             Míos · Sin asignar · SLA en riesgo · Desde PrestaShop · Desde email)
             más "+ guardar vista" en borde discontinuo, y a la derecha un grupo
             SEGMENTADO — no píldoras sueltas — con Cola (badge), Entregabilidad,
             Carga y Avisos, en ese orden. --}}
        <div class="tkt-views-bar">
            <div class="tkt-views-group">
                <span class="tkt-cap">Vistas</span>
                <button type="button" class="tkt-view-pill on" data-filter="all">Todos</button>
                <button type="button" class="tkt-view-pill" data-filter="mine">Míos</button>
                <button type="button" class="tkt-view-pill" data-filter="unassigned">Sin asignar</button>
                <button type="button" class="tkt-view-pill" data-filter="sla_risk">SLA en riesgo</button>
                <button type="button" class="tkt-view-pill" data-filter="from_presta">Desde PrestaShop</button>
                <button type="button" class="tkt-view-pill" data-filter="from_email">Desde email</button>
                {{-- Las vistas de otros agentes (is_shared) llevan un icono de
                     equipo: la lista mezcla las propias con las compartidas y sin
                     esa marca no habría forma de saber cuáles puedes borrar ni de
                     dónde ha salido una que no recuerdas haber creado. --}}
                @foreach($views as $view)
                    @php $isTeamView = $view->is_shared && $view->user_id !== auth()->id(); @endphp
                    <a href="{{ route('manager.helpdesk.tickets.index', ['viewId' => $view->id]) }}"
                       class="tkt-view-pill @if($currentView?->id === $view->id) on @endif"
                       @if($isTeamView) title="Vista compartida por {{ trim(($view->user->firstname ?? '').' '.($view->user->lastname ?? '')) ?: 'otro agente' }}" @endif>
                        @if($isTeamView)<i class="fa-solid fa-users tkt-view-pill-icon" aria-hidden="true"></i>@endif{{ $view->name }}
                    </a>
                @endforeach
                <button type="button" class="tkt-view-pill add" id="tkt-save-view">+ guardar vista</button>
            </div>
            <div class="tkt-ops-seg">
                <button type="button" id="tkt-pill-queue" title="Estado de las colas de trabajo"><i class="fa-solid fa-list-check"></i>Cola<span class="tkt-ops-badge mono" id="tkt-ops-queue-badge" hidden></span></button>
                {{-- Entregabilidad: reusa las mismas KPI (bounce_rate/opened_rate/avg_latency)
                     que ya calcula TicketMailsController::stats() para la bandeja de emails —
                     mismo endpoint (data-emails-index-url) pedido con Accept JSON, sin
                     duplicar la sonda ni inventar un número nuevo. --}}
                <button type="button" id="tkt-pill-deliverability" title="Entregabilidad de correo saliente"><i class="fa-solid fa-shield-halved"></i>Entregabilidad</button>
                <button type="button" id="tkt-pill-workload" title="Carga de trabajo por agente"><i class="fa-solid fa-scale-balanced"></i>Carga</button>
                {{-- Avisos: abre el MISMO panel global de notificaciones de la cabecera del
                     tema (#notifications-dropdown) — no es un sistema de avisos propio de
                     Helpdesk (no existe ninguno en el código), así que en vez de inventar uno
                     nuevo se da un atajo real al que ya existe y ya funciona. --}}
                <button type="button" id="tkt-pill-notices" title="Ver notificaciones"><i class="fa-regular fa-bell"></i>Avisos</button>
                {{-- Modales 16/29/30: buzones, reglas de escalado y recurrencias.
                     Van como iconos sin texto para no romper el ancho del grupo
                     de cuatro que define el mockup. --}}
                <button type="button" id="tkt-pill-mailboxes" title="Buzones de entrada" aria-label="Buzones de entrada"><i class="fa-regular fa-envelope"></i></button>
                <button type="button" id="tkt-pill-escalation" title="Reglas de escalado" aria-label="Reglas de escalado"><i class="fa-solid fa-arrow-trend-up"></i></button>
                <button type="button" id="tkt-pill-recurring" title="Tickets recurrentes" aria-label="Tickets recurrentes"><i class="fa-solid fa-repeat"></i></button>
            </div>
        </div>

        <div class="tkt-split-wrap" id="tkt-split-wrap">
        <div class="tkt-split">

            {{-- Columna: lista --}}
            <div class="tkt-split-list">
                {{-- Cabecera de la lista, como el mockup: checkbox + "Seleccionar
                     todo" + un <select> de orden (no un enlace que alterna un solo
                     criterio) y, a la derecha, los tres iconos de actualizar,
                     exportar y enviar a papelera. --}}
                <div class="tkt-list-head">
                    <input type="checkbox" id="tkt-select-all" aria-label="Seleccionar todos los tickets">
                    <span class="tkt-meta">Seleccionar todo</span>
                    {{-- Era el último desplegable nativo de la pantalla (llevaba
                         data-no-select2): con los chips de filtro ya en select2,
                         desentonaba él solo con el chrome del sistema operativo.
                         data-fchip-size="sm" mantiene los 10,5px que tenía como
                         <select> nativo, para no engordar la fila. --}}
                    <select id="tkt-sort" class="tkt-sort-select" aria-label="Ordenar la lista"
                            data-fchip-label="Orden" data-fchip-size="sm">
                        {{-- El marcado por defecto es "Fecha ↓" y no "SLA más urgente"
                             porque es lo que hace el servidor sin ?sort (el ->latest()
                             de la query base). Antes el control decía SLA de entrada y
                             la lista venía por fecha: el orden solo se cumplía a partir
                             del momento en que el agente tocaba el desplegable. --}}
                        <option value="sla" @selected(request('sort') === 'sla')>SLA más urgente</option>
                        <option value="date_desc" @selected(request('sort', 'date_desc') === 'date_desc')>Fecha ↓</option>
                        <option value="date_asc" @selected(request('sort') === 'date_asc')>Fecha ↑</option>
                        <option value="priority" @selected(request('sort') === 'priority')>Prioridad</option>
                    </select>
                    <span class="tkt-list-head-icons">
                        <button type="button" id="tkt-list-refresh" title="Actualizar" aria-label="Actualizar la lista"><i class="fa-solid fa-rotate"></i></button>
                        <button type="button" id="tkt-list-export" title="Exportar" aria-label="Exportar los tickets del filtro actual"><i class="fa-solid fa-download"></i></button>
                        <button type="button" id="tkt-list-trash" title="Enviar a papelera" aria-label="Enviar los tickets seleccionados a la papelera"><i class="fa-regular fa-trash-can"></i></button>
                    </span>
                </div>
                <div class="tkt-bulk-bar" id="tkt-bulk-bar">
                    <span id="tkt-bulk-count" class="tkt-title-sm">0 seleccionados</span>
                    <span class="tkt-actions">
                        @can('helpdesk.tickets.update')
                            <button type="button" class="tkt-btn" data-bulk-action="assign">Asignar</button>
                            <button type="button" class="tkt-btn" data-bulk-action="add_tag">Etiquetar</button>
                            <button type="button" class="tkt-btn" data-bulk-action="change_status">Cambiar estado</button>
                            <button type="button" class="tkt-btn" id="tkt-bulk-move-team">Mover a equipo</button>
                            {{-- "Reintentar envío"/"Vincular a un ticket" del mockup
                                 (modal 13, ve-mail-bulk) — mismo permiso que el resto
                                 de esta fila, ya que ambas operan sobre datos del
                                 propio ticket (no hay un ability "merge" separado
                                 expuesto aquí, a diferencia de show.blade.php). --}}
                            <button type="button" class="tkt-btn" data-bulk-action="retry_failed_mail">Reintentar envío</button>
                            <button type="button" class="tkt-btn" id="tkt-bulk-link-ticket">Vincular a un ticket</button>
                        @endcan
                        @can('helpdesk.tickets.resolve')
                            <button type="button" class="tkt-btn" data-bulk-action="resolve">Resolver</button>
                        @endcan
                        @can('helpdesk.tickets.close')
                            <button type="button" class="tkt-btn" data-bulk-action="close">Cerrar</button>
                        @endcan
                        @can('helpdesk.tickets.delete')
                            <button type="button" class="tkt-btn" data-bulk-action="delete">Eliminar</button>
                        @endcan
                        {{-- "Exportar selección": el modal de exportar ya sabe
                             acotarse a los ids marcados, aquí solo se ofrece
                             desde donde se hace la selección. --}}
                        <button type="button" class="tkt-btn" id="tkt-bulk-export">Exportar</button>
                        <button type="button" class="tkt-btn" id="tkt-bulk-clear">Quitar</button>
                    </span>
                </div>
                <div class="tkt-skeleton-list" id="tkt-skeleton">
                    <div class="tkt-skeleton"></div><div class="tkt-skeleton"></div><div class="tkt-skeleton"></div>
                </div>
                <div class="tkt-list" id="tkt-list"></div>
                {{-- Pie: "1–6 de 218 tickets" y dos chevrones, como el mockup —
                     no el paginador numerado del tema, que no cabe en 380px de
                     columna y desentona con el resto de la pantalla. --}}
                {{-- El pie lo repinta también el JS tras cada refetch (renderFoot
                     en tickets-app.js), así que los ids/clases de aquí son
                     contrato: el SSR pinta la primera carga y el JS las
                     siguientes, con el mismo markup. --}}
                <div class="tkt-list-foot">
                    <span id="tkt-foot-range">{{ $tickets->firstItem() ?? 0 }}–{{ $tickets->lastItem() ?? 0 }} de {{ $tickets->total() }} tickets</span>
                    <span class="tkt-list-foot-nav" id="tkt-foot-nav">
                        @if($tickets->onFirstPage())
                            <span class="off" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></span>
                        @else
                            <a href="{{ $tickets->previousPageUrl() }}" rel="prev" aria-label="Página anterior"><i class="fa-solid fa-chevron-left"></i></a>
                        @endif
                        @if($tickets->hasMorePages())
                            <a href="{{ $tickets->nextPageUrl() }}" rel="next" aria-label="Página siguiente"><i class="fa-solid fa-chevron-right"></i></a>
                        @else
                            <span class="off" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                        @endif
                    </span>
                </div>
            </div>

            {{-- Columna: detalle --}}
            <div class="tkt-split-detail" id="tkt-detail-col">
                <div class="tkt-empty-state" id="tkt-detail-empty">
                    <div class="tkt-empty-icon"><i class="fa-regular fa-rectangle-list"></i></div>
                    <div class="tkt-empty-title">Ningún ticket seleccionado</div>
                    <div class="tkt-empty-text">Elige un ticket de la lista para ver la conversación, los datos del cliente, el origen y la trazabilidad de los correos enviados.</div>
                    <div class="tkt-badge-row">
                        <span class="tkt-hint-key">J / K navegar</span>
                        <span class="tkt-hint-key">Enter abrir</span>
                        <span class="tkt-hint-key">C nuevo ticket</span>
                        <span class="tkt-hint-key">? más atajos</span>
                    </div>
                    @can('helpdesk.tickets.create')
                        <button type="button" class="tkt-btn tkt-btn-primary" id="tkt-empty-create">Crear un ticket</button>
                    @endcan
                </div>
                <div id="tkt-detail" style="display:none"></div>
            </div>

            {{-- Columna: panel lateral — Fase C: las 8 pestañas ya tienen
                 contenido real (reusan el mismo JSON de data()). --}}
            <div class="tkt-side" id="tkt-side">
                <div class="tkt-icon-rail top" id="tkt-side-rail">
                    <button type="button" class="tkt-icon-tab on" data-side="gestion" title="Gestión" aria-label="Gestión"><i class="fa-solid fa-sliders"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="cliente" title="Cliente" aria-label="Cliente"><i class="fa-regular fa-address-card"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="form" title="Formulario" aria-label="Formulario"><i class="fa-regular fa-rectangle-list"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="correo" title="Correo" aria-label="Correo"><i class="fa-regular fa-envelope"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="notas" title="Notas internas" aria-label="Notas internas"><i class="fa-regular fa-note-sticky"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="tags" title="Etiquetas" aria-label="Etiquetas"><i class="fa-solid fa-tag" aria-hidden="true"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="files" title="Archivos" aria-label="Archivos"><i class="fa-solid fa-paperclip"></i></button>
                    {{-- Novena pestaña del mockup: el histórico de tickets del
                         mismo cliente, para ver si lo que pregunta ya se le
                         respondió antes sin salir de la pantalla. --}}
                    <button type="button" class="tkt-icon-tab" data-side="tickets" title="Tickets del cliente" aria-label="Tickets del cliente"><i class="fa-solid fa-ticket"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="hist" title="Historial" aria-label="Historial"><i class="fa-solid fa-clock-rotate-left"></i></button>
                </div>
                <div id="tkt-side-content" class="tkt-side-pane">
                    <div class="tkt-empty-box">Sin ticket seleccionado. Aquí verás la gestión (estado, prioridad, categoría, equipo, acciones) del ticket que elijas en la lista.</div>
                </div>
            </div>

        </div>
        </div>

        {{-- Modo Kanban: llega en la Fase D --}}
        <div class="tkt-kanban" id="tkt-kanban"></div>

        {{-- Barra de estado: conexión en vivo, quién trabaja ahora mismo y
             accesos rápidos — misma idea que la barra equivalente de la
             bandeja de conversaciones de Helpdesk. La rellena
             renderStatusBar() en tickets-app.js; todos los valores salen de
             endpoints que la pantalla ya carga (nada aquí es decorativo). --}}
        <div class="tkt-status-bar" id="tkt-status-bar">
            <span class="tkt-status-item"><span class="tkt-status-dot" id="tkt-status-conn-dot"></span><span id="tkt-status-conn-text">Conectando…</span></span>
            <span class="tkt-status-sep">·</span>
            <span class="tkt-status-item" id="tkt-status-agents"><i class="fa-solid fa-users"></i> —</span>
            <span class="tkt-status-sep">·</span>
            <span class="tkt-status-item" id="tkt-status-sla"><i class="fa-regular fa-clock"></i> SLA en riesgo: —</span>
            <span class="tkt-status-sep">·</span>
            <span class="tkt-status-item" id="tkt-status-resolved"><i class="fa-solid fa-circle-check"></i> — resueltos</span>
            <span class="tkt-status-sep">·</span>
            <span class="tkt-status-item" id="tkt-status-queue" hidden><i class="fa-regular fa-envelope"></i></span>
            <span class="tkt-spacer"></span>
            <button type="button" class="tkt-status-icon-btn" id="tkt-status-sound" title="Sonido al llegar un mensaje nuevo" aria-label="Alternar sonido de mensaje nuevo" aria-pressed="false"><i class="fa-solid fa-volume-high"></i></button>
            <button type="button" class="tkt-status-icon-btn" id="tkt-status-shortcuts" title="Atajos de teclado (?)" aria-label="Mostrar atajos de teclado"><i class="fa-solid fa-keyboard"></i></button>
            <span class="tkt-status-version">v1.0 · Tickets</span>
        </div>

    </div>
    </div>

    {{-- Dentro de .tkt a propósito: las variables --tkt-* solo se definen
         bajo ese selector — fuera de él el modal se renderiza transparente
         (mismo bug ya corregido una vez en openModal(), ver tickets-app/core.js). --}}
    @include('helpdesktickets::managers.tickets.partials._filters-modal')
</div>
@endsection

@push('scripts')
    @php
        // tickets-app.js era un único fichero de 11.304 líneas / 1.001
        // funciones sin build (ver auditoría 7-sep-2026) — partido en un
        // núcleo + un fichero por modal el 8-sep-2026. Ya no vive dentro de
        // un IIFE de un solo archivo: cada <script> de aquí es su propio
        // scope de nivel superior, así que TKA, openModal(), escapeHtml()…
        // cuelgan de window y cualquier fichero puede llamarlos con solo
        // cargarse en la página — nada se ejecuta hasta que el usuario
        // interactúa (o initTicketsApp() al final, con todo ya cargado), así
        // que el orden exacto de los modales no importa; 'core' sí va primero
        // porque ahí vive TKA.
        //
        // El orden vive en un manifest.json (no aquí) porque scripts/
        // build-tickets-app.mjs necesita LEER exactamente la misma lista
        // para generar tickets-app.min.js — con dos copias del array
        // (una en PHP, otra en el script de build) habría sido cuestión de
        // tiempo que una cambiara sin la otra y el bundle minificado
        // sirviera un modal desincronizado del código fuente en silencio.
        $manifestPath = public_path('modules/helpdesktickets/js/tickets-app/manifest.json');
        $ticketsAppFiles = json_decode(@file_get_contents($manifestPath) ?: '{}', true)['files'] ?? [];

        // El bundle minificado (npm run build:tickets-app) es OPCIONAL y
        // NO es el camino por defecto en desarrollo: aquí se edita y se
        // prueba en vivo fichero a fichero constantemente (varias sesiones
        // a la vez), y un bundle desactualizado serviría un modal viejo sin
        // ningún aviso — el mismo tipo de bug de caché ya sufrido con el
        // CSS de este módulo. Por eso NO basta con que el bundle exista:
        // tiene que ser más reciente que TODOS los ficheros fuente que
        // agrupa, o se ignora y cae al camino de siempre (un <script> por
        // fichero). En producción, generar el bundle DESPUÉS del último cp
        // de turno hace que esta condición se cumpla sola.
        $minPath = public_path('modules/helpdesktickets/js/tickets-app.min.js');
        $minMtime = @filemtime($minPath);
        $useMinified = $minMtime !== false;
        if ($useMinified) {
            foreach ($ticketsAppFiles as $file) {
                $srcMtime = @filemtime(public_path('modules/helpdesktickets/js/tickets-app/'.$file.'.js'));
                if ($srcMtime === false || $srcMtime > $minMtime) {
                    $useMinified = false;
                    break;
                }
            }
        }
    @endphp
    @if ($useMinified)
        <script src="{{ asset('modules/helpdesktickets/js/tickets-app.min.js') }}?v={{ $minMtime }}"></script>
    @else
        @foreach ($ticketsAppFiles as $file)
            <script src="{{ asset('modules/helpdesktickets/js/tickets-app/'.$file.'.js') }}?v={{ @filemtime(public_path('modules/helpdesktickets/js/tickets-app/'.$file.'.js')) }}"></script>
        @endforeach
    @endif
@endpush
