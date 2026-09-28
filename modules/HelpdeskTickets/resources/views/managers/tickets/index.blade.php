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
    @php
        // Mismo criterio "opcional, cae si está desactualizado" que el JS
        // (ver el bloque @push('scripts') más abajo y scripts/build-tickets-app.mjs):
        // tickets-app.min.css SOLO se sirve si existe Y es más reciente que
        // el .css fuente que minifica.
        $cssPath = public_path('modules/helpdesktickets/css/tickets-app.css');
        $cssMinPath = public_path('modules/helpdesktickets/css/tickets-app.min.css');
        $cssMtime = @filemtime($cssPath);
        $cssMinMtime = @filemtime($cssMinPath);
        $useCssMin = $cssMinMtime !== false && $cssMtime !== false && $cssMinMtime >= $cssMtime;
    @endphp
    @if ($useCssMin)
        <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/tickets-app.min.css') }}?v={{ $cssMinMtime }}">
    @else
        <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/tickets-app.css') }}?v={{ $cssMtime }}">
    @endif
@endpush

@php
    // Default 'unassigned' (antes 'all'): mismo criterio que
    // TicketsCrudController::index() — sin filtro explícito en la URL, la
    // bandeja arranca en "Sin asignar". Una sola variable para las tabs/
    // pills de abajo Y data-initial-filter: con dos defaults sueltos habría
    // sido cuestión de tiempo que uno cambiara sin el otro y la tab marcada
    // como activa dejara de coincidir con los tickets realmente listados.
    $activeFilter = request('quick_filter', 'unassigned');

    // Ticket::toListRow() es la única fuente del contrato de fila (mismo
    // patrón que TicketMail::toListRow() para la bandeja de emails) — la usa
    // tanto esta hidratación SSR como el futuro JSON de refetch.
    $ticketsPayload = $tickets->getCollection()->map(fn ($t) => $t->toListRow())->values();

    // El ticket preseleccionado (?ticket=) puede no estar en la página/filtro
    // actual del listado: se antepone al payload para que el JS lo encuentre.
    // outside_filter: la fila se pinta marcada como "fuera del filtro" para
    // que no parezca que cumple la pestaña activa (QA 28-sep-2026: el ticket
    // recién asignado seguía en "Sin asignar" con 25 filas y "24 tickets").
    if ($selectedTicket && ! $ticketsPayload->contains('id', $selectedTicket->id)) {
        $ticketsPayload->prepend($selectedTicket->toListRow() + ['outside_filter' => true]);
    }

    // Plantillas de las URLs de acción del listado (PERF-09): una sola
    // generación de las ~27 rutas por respuesta en vez de una por fila —
    // toListRow() ya no las incluye, tickets-app.js las expande sustituyendo
    // '__TICKET__' por el id de cada ticket. Ver el docblock del método.
    $ticketUrlTemplates = \Modules\HelpdeskTickets\Models\Ticket::listRowUrlTemplates();

    // Origen y prioridad: una sola fuente (el lang file) para las dos veces
    // que la pantalla necesita esta traducción — el chip de filtro de la
    // barra (línea ~317) y la etiqueta legible del chip "filtro activo" con
    // ✕ (línea ~452). Antes eran dos arrays sueltos que había que mantener
    // sincronizados a mano.
    $sourceLabels = collect(['email', 'widget', 'wa', 'fb', 'ig', 'formulario'])
        ->mapWithKeys(fn ($key) => [$key => __('helpdesktickets::helpdesktickets.source.'.$key)]);
    $priorityLabels = collect(__('helpdesktickets::helpdesktickets.priority'));
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
         {{-- Textos de los toasts de public/js/tickets-app/core.js (TKA.t()) —
              una sola pasada de traducción, no un __() por toast. --}}
         data-i18n="{{ json_encode(__('helpdesktickets::helpdesktickets.tickets_index_js'), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-tickets="{{ json_encode($ticketsPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-tab-counts="{{ json_encode($tabCounts, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         {{-- Settings → Helpdesk · Tickets → Funcionalidades: qué botones/
              secciones de la vista de detalle debe pintar el JS. --}}
         data-features="{{ json_encode($ticketFeatures ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-attachment-max-bytes="{{ (int) data_get($ticketAttachmentSettings ?? [], 'max_bytes', 10 * 1024 * 1024) }}"
         data-attachment-extensions="{{ json_encode(data_get($ticketAttachmentSettings ?? [], 'extensions', []), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-user-id="{{ auth()->id() }}"
         data-initial-filter="{{ $activeFilter }}"
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
         {{-- Presencia en vivo del listado: qué agentes están viendo cada fila ahora mismo. --}}
         data-presence-overview-url="{{ route('manager.helpdesk.tickets.presence.overview') }}"
         {{-- Disponibilidad general del agente (módulo Helpdesk hermano) — late
              mientras el panel de tickets está abierto para que "N agentes en
              línea" del pie de pantalla deje de dar siempre 0 (QA 14-sep-2026). --}}
         data-agent-presence-heartbeat-url="{{ route('manager.helpdesk.presence.heartbeat') }}"
         data-agent-presence-agents-url="{{ route('manager.helpdesk.presence.agents') }}"
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
         {{-- Modal 03: "Duplicar como mía" — a diferencia de la de arriba,
              esta SÍ funciona sin helpdesk.tickets.settings (ver
              TicketCannedRepliesController::duplicate()). --}}
         data-canned-duplicate-url-template="{{ route('manager.helpdesk.tickets.canned-replies.duplicate', ['reply' => '__REPLY__']) }}"
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
                <i class="fa-solid fa-headset"></i><span>{{ __('helpdesktickets::helpdesktickets.tickets_index.breadcrumb_helpdesk') }}</span><i class="fa-solid fa-chevron-right"></i><span>{{ __('helpdesktickets::helpdesktickets.tickets_index.breadcrumb_tickets') }}</span><i class="fa-solid fa-chevron-right"></i><span class="on" id="tkt-crumb-tab">{{ __('helpdesktickets::helpdesktickets.tickets_index.tab_all') }}</span>
            </div>
            <div class="tkt-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="tkt-search" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.search_aria_label') }}" placeholder="{{ __('helpdesktickets::helpdesktickets.tickets_index.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="tkt-toolbar-right">
                <div class="tkt-seg" id="tkt-mode-switch" role="group" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.mobile_nav_aria') }}">
                    <button type="button" class="on" data-mode="list" aria-pressed="true"><i class="fa-solid fa-list"></i> {{ __('helpdesktickets::helpdesktickets.tickets_index.mode_list') }}</button>
                    <button type="button" data-mode="kanban" aria-pressed="false"><i class="fa-solid fa-table-columns"></i> {{ __('helpdesktickets::helpdesktickets.tickets_index.mode_kanban') }}</button>
                </div>
                <button type="button" class="tkt-btn" id="tkt-sync" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.sync_title') }}"><i class="fa-solid fa-rotate"></i> {{ __('helpdesktickets::helpdesktickets.tickets_index.sync') }}</button>
                <button type="button" class="tkt-btn" id="tkt-export-open" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.export_title') }}"><i class="fa-solid fa-download"></i> {{ __('helpdesktickets::helpdesktickets.tickets_index.export') }}</button>
                <a href="{{ route('manager.helpdesk.ticket-templates.index') }}" class="tkt-btn" id="tkt-templates-open"><i class="fa-solid fa-clone"></i> {{ __('helpdesktickets::helpdesktickets.tickets_index.templates') }}</a>
                <a href="{{ route('manager.helpdesk.tickets.create') }}" class="tkt-btn tkt-btn-primary" id="tkt-new-ticket"><i class="fa-solid fa-plus"></i> {{ __('helpdesktickets::helpdesktickets.tickets_index.new_ticket') }}</a>
            </div>
        </div>

        {{-- Tabs de estado (mismo orden/conjunto que el mockup: Todos/Sin
             asignar/Abiertos/Pendientes/Resueltos/Cerrados — Urgentes/Míos
             viven como chips de "Vistas" más abajo, no como tabs de estado) --}}
        <div class="tkt-state-tabs">
            <button type="button" class="tkt-state-tab{{ $activeFilter === 'all' ? ' on' : '' }}" data-filter="all">{{ __('helpdesktickets::helpdesktickets.tickets_index.tab_all') }} <span class="c">{{ $tabCounts['all'] }}</span></button>
            <button type="button" class="tkt-state-tab{{ $activeFilter === 'unassigned' ? ' on' : '' }}" data-filter="unassigned">{{ __('helpdesktickets::helpdesktickets.tickets_index.tab_unassigned') }} <span class="c">{{ $tabCounts['unassigned'] }}</span></button>
            <button type="button" class="tkt-state-tab{{ $activeFilter === 'open' ? ' on' : '' }}" data-filter="open">{{ __('helpdesktickets::helpdesktickets.tickets_index.tab_open') }} <span class="c">{{ $tabCounts['open'] }}</span></button>
            <button type="button" class="tkt-state-tab{{ $activeFilter === 'pending' ? ' on' : '' }}" data-filter="pending">{{ __('helpdesktickets::helpdesktickets.tickets_index.tab_pending') }} <span class="c">{{ $tabCounts['pending'] }}</span></button>
            <button type="button" class="tkt-state-tab{{ $activeFilter === 'resolved' ? ' on' : '' }}" data-filter="resolved">{{ __('helpdesktickets::helpdesktickets.tickets_index.tab_resolved') }} <span class="c">{{ $tabCounts['resolved'] }}</span></button>
            <button type="button" class="tkt-state-tab{{ $activeFilter === 'closed' ? ' on' : '' }}" data-filter="closed">{{ __('helpdesktickets::helpdesktickets.tickets_index.tab_closed') }} <span class="c">{{ $tabCounts['closed'] }}</span></button>
            {{-- "Plantillas" cierra la fila de tabs en el mockup. Una auditoría previa
                 lo había quitado por ser un <a> que navegaba fuera de la pantalla en
                 vez de filtrar como sus vecinos, y por duplicar el botón de la barra
                 superior. Se restaura porque la pantalla clona el mockup al pie de la
                 letra, pero con la clase .tkt-state-link en lugar de .tkt-state-tab:
                 mismo sitio y misma tipografía, sin fingir que es un filtro (no lleva
                 data-filter, así que renderTabs() y el listener de tabs lo ignoran). --}}
            <a href="{{ route('manager.helpdesk.ticket-templates.index') }}" class="tkt-state-link">{{ __('helpdesktickets::helpdesktickets.tickets_index.templates') }}</a>
            {{-- "· cola de correo: N" lo rellena tickets-app.js al cargar (fetchOpsQueueHint(),
                 reusa TKA.urls.ops — misma fuente que ya alimenta el modal "Cola" — sin
                 duplicar ninguna sonda ni inventar un endpoint nuevo). Vacío hasta entonces. --}}
            {{-- Modo compacto (24-sep-2026): con poca altura de ventana las
                 barras de filtros y vistas se esconden tras este botón para
                 dejar sitio a la conversación (a 900 px de alto quedaban unos
                 60 px de hilo visibles). En pantallas altas el botón no se ve
                 y todo queda como en el mockup. Ver .tkt-compact-toggle. --}}
            @php
                $activeFilterCount = collect(request()->only(['source', 'tag', 'category', 'assignee', 'priority', 'created_from', 'created_to', 'status', 'group', 'sla_status', 'mail_status', 'mail_type', 'mailbox', 'has_attachments']))
                    ->filter(fn ($v) => is_scalar($v) && filled($v) && $v !== 'all')
                    ->count();
            @endphp
            <button type="button" class="tkt-state-link tkt-compact-toggle" id="tkt-toggle-filters" aria-expanded="false" aria-controls="tkt-filter-form">{{ __('helpdesktickets::helpdesktickets.tickets_index.filters_and_views') }} @if($activeFilterCount > 0)<span class="c">{{ $activeFilterCount }}</span>@endif</button>
            <span class="tkt-queue-hint">{{ __('helpdesktickets::helpdesktickets.tickets_index.sla_risk_hint', ['count' => $tabCounts['sla_risk']]) }}<span id="tkt-mail-queue-hint"></span></span>
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
            {{-- Cada chip es un <select> real con select2 (initSelect2 en
                 tickets-app.js, rama [data-fchip-label]). Antes era un <select>
                 nativo transparente superpuesto sobre un <label> pintado: clonaba
                 el mockup al pixel, pero heredaba el desplegable del sistema —sin
                 buscador (el chip "Agente" tiene decenas de opciones) y con un
                 aspecto distinto en cada SO/navegador—. templateSelection
                 recompone dentro del widget el mismo contenido del chip
                 (etiqueta + valor en gris), así que el diseño no cambia. --}}
            <select class="tkt-fsel" name="source" data-fchip-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_source_label') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_source_aria') }}">
                <option value="">{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_all_masc') }}</option>
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
            <select class="tkt-fsel" name="tag" data-fchip-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_tag_label') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_tag_aria') }}">
                <option value="">{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_all_fem') }}</option>
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
            <select class="tkt-fsel" name="category" data-fchip-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_category_label') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_category_aria') }}">
                <option value="">{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_all_fem') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select class="tkt-fsel" name="assignee" data-fchip-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_assignee_label') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_assignee_aria') }}">
                <option value="">{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_all_masc') }}</option>
                <option value="me" @selected(request('assignee') === 'me')>{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_assignee_me') }}</option>
                <option value="unassigned" @selected(request('assignee') === 'unassigned')>{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_assignee_unassigned') }}</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" @selected(request('assignee') == $agent->id)>{{ trim($agent->firstname.' '.$agent->lastname) }}</option>
                @endforeach
            </select>
            <select class="tkt-fsel" name="priority" data-fchip-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_priority_label') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_priority_aria') }}">
                <option value="">{{ __('helpdesktickets::helpdesktickets.tickets_index.filter_all_fem') }}</option>
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
                    (bool) $rangeFrom => __('helpdesktickets::helpdesktickets.tickets_index.date_range_from', ['date' => $rangeFrom->translatedFormat('d M Y')]),
                    (bool) $rangeTo => __('helpdesktickets::helpdesktickets.tickets_index.date_range_to', ['date' => $rangeTo->translatedFormat('d M Y')]),
                    default => __('helpdesktickets::helpdesktickets.tickets_index.date_range_any'),
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
            <button type="button" class="tkt-fchip" id="tkt-filters-modal-open">{{ __('helpdesktickets::helpdesktickets.tickets_index.more_filters') }}</button>
            <span id="tkt-count" class="mono tkt-count">{{ __('helpdesktickets::helpdesktickets.tickets_index.ticket_count', ['count' => $tickets->total()]) }}</span>
            {{-- "limpiar" quita los FILTROS, no el contexto: la pestaña abierta,
                 el orden elegido, la vista guardada y el ticket que estés
                 mirando sobreviven. Antes apuntaba a la ruta pelada y un clic
                 te devolvía a "Todos", al orden por defecto y con el panel
                 derecho cerrado — casi nunca era lo que se buscaba. --}}
            <a href="{{ route('manager.helpdesk.tickets.index', request()->only(['quick_filter', 'view', 'sort', 'ticket', 'viewId'])) }}" class="tkt-clear-link">{{ __('helpdesktickets::helpdesktickets.tickets_index.clear_filters') }}</a>
        </form>

        @php
            // Chips de filtro activo con ✕ para quitar uno a uno, en vez de
            // solo el link "limpiar" que borra todos de golpe.
            $activeFilterLabels = [
                'source' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_source'),
                'category' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_category'),
                'assignee' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_assignee'),
                'priority' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_priority'),
                'tag' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_tag'),
                'search' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_search'),
                'created_from' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_created_from'),
                'created_to' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_created_to'),
                'sla_status' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_sla_status'),
                'mail_status' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_mail_status'),
                'mail_type' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_mail_type'),
                'mailbox' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_mailbox'),
                'has_attachments' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_has_attachments'),
                // Estado y Grupo faltaban en esta lista aunque TicketFilter los
                // aplica de verdad (applyStatus/applyGroup): se filtraba por ellos
                // desde el modal y no aparecía ningún chip con ✕ — el filtro estaba
                // activo y era invisible, sin más salida que "limpiar" del todo.
                'status' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_status'),
                'group' => __('helpdesktickets::helpdesktickets.tickets_index.active_filter_group'),
            ];
            // Bug real de QA: el chip mostraba el valor CRUDO del query
            // param ("Prioridad: urgent", "Agente: 16") en vez de la
            // etiqueta legible — mismas fuentes que ya usan los <select> de
            // esta misma barra ($sourceLabels/$priorityLabels arriba), para no
            // duplicar ni desalinear el mapeo.
            $filterValueLabel = function (string $key, string $value) use ($categories, $agents, $statuses, $groups, $sourceLabels, $priorityLabels) {
                return match ($key) {
                    // Fecha legible: el chip mostraba el valor crudo del query
                    // param ("Desde: 2026-08-19") mientras el chip de rango de la
                    // barra, dos líneas más arriba, ya dice "19 ago – 01 sep 2026".
                    'created_from', 'created_to' => \Illuminate\Support\Carbon::parse($value)->translatedFormat('d M Y'),
                    'status' => $statuses->firstWhere('id', (int) $value)?->name ?? $value,
                    'group' => $groups->firstWhere('id', (int) $value)?->name ?? $value,
                    'priority' => $priorityLabels->get($value, $value),
                    'source' => $sourceLabels->get($value, $value),
                    'sla_status' => match ($value) {
                        'breach' => __('helpdesktickets::helpdesktickets.tickets_index.sla_status_breach'),
                        'warn' => __('helpdesktickets::helpdesktickets.tickets_index.sla_status_warn'),
                        'ok' => __('helpdesktickets::helpdesktickets.tickets_index.sla_status_ok'),
                        default => $value,
                    },
                    'mail_status' => match ($value) {
                        'delivered' => __('helpdesktickets::helpdesktickets.tickets_index.mail_status_delivered'),
                        'sent' => __('helpdesktickets::helpdesktickets.tickets_index.mail_status_sent'),
                        'pending' => __('helpdesktickets::helpdesktickets.tickets_index.mail_status_pending'),
                        'bounced' => __('helpdesktickets::helpdesktickets.tickets_index.mail_status_bounced'),
                        'failed' => __('helpdesktickets::helpdesktickets.tickets_index.mail_status_failed'),
                        default => $value,
                    },
                    'mail_type' => match ($value) {
                        'reply' => __('helpdesktickets::helpdesktickets.tickets_index.mail_type_reply'),
                        'internal' => __('helpdesktickets::helpdesktickets.tickets_index.mail_type_internal'),
                        'inbound' => __('helpdesktickets::helpdesktickets.tickets_index.mail_type_inbound'),
                        default => $value,
                    },
                    'has_attachments' => __('helpdesktickets::helpdesktickets.tickets_index.has_attachments_only'),
                    'category' => $categories->firstWhere('id', (int) $value)?->name ?? $value,
                    'assignee' => match (true) {
                        $value === 'me' => __('helpdesktickets::helpdesktickets.tickets_index.filter_assignee_me'),
                        $value === 'unassigned' => __('helpdesktickets::helpdesktickets.tickets_index.filter_assignee_unassigned'),
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
                        {{ __('helpdesktickets::helpdesktickets.tickets_index.filter_chip_remove', ['label' => $f['label'], 'value' => Str::limit($f['value'], 24)]) }} <i class="fa-solid fa-xmark"></i>
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
                <span class="tkt-cap">{{ __('helpdesktickets::helpdesktickets.tickets_index.views_caption') }}</span>
                <button type="button" class="tkt-view-pill{{ $activeFilter === 'all' ? ' on' : '' }}" data-filter="all">{{ __('helpdesktickets::helpdesktickets.tickets_index.view_all') }}</button>
                <button type="button" class="tkt-view-pill{{ $activeFilter === 'mine' ? ' on' : '' }}" data-filter="mine">{{ __('helpdesktickets::helpdesktickets.tickets_index.view_mine') }}</button>
                <button type="button" class="tkt-view-pill{{ $activeFilter === 'unassigned' ? ' on' : '' }}" data-filter="unassigned">{{ __('helpdesktickets::helpdesktickets.tickets_index.view_unassigned') }}</button>
                <button type="button" class="tkt-view-pill{{ $activeFilter === 'sla_risk' ? ' on' : '' }}" data-filter="sla_risk">{{ __('helpdesktickets::helpdesktickets.tickets_index.view_sla_risk') }}</button>
                <button type="button" class="tkt-view-pill{{ $activeFilter === 'from_presta' ? ' on' : '' }}" data-filter="from_presta">{{ __('helpdesktickets::helpdesktickets.tickets_index.view_from_presta') }}</button>
                <button type="button" class="tkt-view-pill{{ $activeFilter === 'from_email' ? ' on' : '' }}" data-filter="from_email">{{ __('helpdesktickets::helpdesktickets.tickets_index.view_from_email') }}</button>
                {{-- Las vistas de otros agentes (is_shared) llevan un icono de
                     equipo: la lista mezcla las propias con las compartidas y sin
                     esa marca no habría forma de saber cuáles puedes borrar ni de
                     dónde ha salido una que no recuerdas haber creado. --}}
                @foreach($views as $view)
                    @php
                        $isTeamView = $view->is_shared && $view->user_id !== auth()->id();
                        $viewOwnerName = trim(($view->user->firstname ?? '').' '.($view->user->lastname ?? ''))
                            ?: __('helpdesktickets::helpdesktickets.tickets_index.view_shared_by_fallback');
                    @endphp
                    <a href="{{ route('manager.helpdesk.tickets.index', ['viewId' => $view->id]) }}"
                       class="tkt-view-pill @if($currentView?->id === $view->id) on @endif"
                       @if($isTeamView) title="{{ __('helpdesktickets::helpdesktickets.tickets_index.view_shared_by', ['name' => $viewOwnerName]) }}" @endif>
                        @if($isTeamView)<i class="fa-solid fa-users tkt-view-pill-icon" aria-hidden="true"></i>@endif{{ $view->name }}
                    </a>
                @endforeach
                <button type="button" class="tkt-view-pill add" id="tkt-save-view">{{ __('helpdesktickets::helpdesktickets.tickets_index.view_save') }}</button>
            </div>
            <div class="tkt-ops-seg">
                <button type="button" id="tkt-pill-queue" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_queue_title') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_queue_title') }}"><i class="fa-solid fa-list-check" aria-hidden="true"></i>{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_queue') }}<span class="tkt-ops-badge mono" id="tkt-ops-queue-badge" aria-live="polite" hidden></span></button>
                {{-- Entregabilidad: reusa las mismas KPI (bounce_rate/opened_rate/avg_latency)
                     que ya calcula TicketMailsController::stats() para la bandeja de emails —
                     mismo endpoint (data-emails-index-url) pedido con Accept JSON, sin
                     duplicar la sonda ni inventar un número nuevo. --}}
                <button type="button" id="tkt-pill-deliverability" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_deliverability_title') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_deliverability_title') }}"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_deliverability') }}</button>
                <button type="button" id="tkt-pill-workload" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_workload_title') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_workload_title') }}"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_workload') }}</button>
                {{-- Avisos: abre el MISMO panel global de notificaciones de la cabecera del
                     tema (#notifications-dropdown) — no es un sistema de avisos propio de
                     Helpdesk (no existe ninguno en el código), así que en vez de inventar uno
                     nuevo se da un atajo real al que ya existe y ya funciona. --}}
                <button type="button" id="tkt-pill-notices" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_notices_title') }}"><i class="fa-regular fa-bell" aria-hidden="true"></i>{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_notices') }}</button>
                {{-- Modales 16/29/30: buzones, reglas de escalado y recurrencias.
                     Van como iconos sin texto para no romper el ancho del grupo
                     de cuatro que define el mockup. --}}
                <button type="button" id="tkt-pill-mailboxes" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_mailboxes_title') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_mailboxes_title') }}"><i class="fa-regular fa-envelope" aria-hidden="true"></i></button>
                <button type="button" id="tkt-pill-escalation" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_escalation_title') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_escalation_title') }}"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i></button>
                <button type="button" id="tkt-pill-recurring" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_recurring_title') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.ops_recurring_title') }}"><i class="fa-solid fa-repeat" aria-hidden="true"></i></button>
            </div>
        </div>

        <div class="tkt-mobile-nav" id="tkt-mobile-nav" role="tablist" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.mobile_nav_aria') }}">
            <button type="button" class="on" data-mobile-pane="list" role="tab" aria-selected="true"><i class="fa-solid fa-list"></i> {{ __('helpdesktickets::helpdesktickets.tickets_index.mobile_nav_list') }}</button>
            <button type="button" data-mobile-pane="detail" role="tab" aria-selected="false"><i class="fa-regular fa-message"></i> {{ __('helpdesktickets::helpdesktickets.tickets_index.mobile_nav_detail') }}</button>
            <button type="button" data-mobile-pane="side" role="tab" aria-selected="false"><i class="fa-solid fa-sliders"></i> {{ __('helpdesktickets::helpdesktickets.tickets_index.mobile_nav_management') }}</button>
        </div>

        <div class="tkt-split-wrap" id="tkt-split-wrap">
        <div class="tkt-split">

            {{-- Columna: lista --}}
            <div class="tkt-split-list" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.list_aria') }}">
                {{-- Cabecera de la lista, como el mockup: checkbox + "Seleccionar
                     todo" + un <select> de orden (no un enlace que alterna un solo
                     criterio) y, a la derecha, los tres iconos de actualizar,
                     exportar y enviar a papelera. --}}
                <div class="tkt-list-head">
                    <input type="checkbox" id="tkt-select-all" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.select_all') }}">
                    <span class="tkt-meta">{{ __('helpdesktickets::helpdesktickets.tickets_index.select_all_label') }}</span>
                    {{-- Era el último desplegable nativo de la pantalla (llevaba
                         data-no-select2): con los chips de filtro ya en select2,
                         desentonaba él solo con el chrome del sistema operativo.
                         data-fchip-size="sm" mantiene los 10,5px que tenía como
                         <select> nativo, para no engordar la fila. --}}
                    <select id="tkt-sort" class="tkt-sort-select" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.sort_aria') }}"
                            data-fchip-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.sort_label') }}" data-fchip-size="sm">
                        {{-- El marcado por defecto es "Fecha ↓" y no "SLA más urgente"
                             porque es lo que hace el servidor sin ?sort (el ->latest()
                             de la query base). Antes el control decía SLA de entrada y
                             la lista venía por fecha: el orden solo se cumplía a partir
                             del momento en que el agente tocaba el desplegable. --}}
                        <option value="sla" @selected(request('sort') === 'sla')>{{ __('helpdesktickets::helpdesktickets.tickets_index.sort_sla') }}</option>
                        <option value="date_desc" @selected(request('sort', 'date_desc') === 'date_desc')>{{ __('helpdesktickets::helpdesktickets.tickets_index.sort_date_desc') }}</option>
                        <option value="date_asc" @selected(request('sort') === 'date_asc')>{{ __('helpdesktickets::helpdesktickets.tickets_index.sort_date_asc') }}</option>
                        <option value="priority" @selected(request('sort') === 'priority')>{{ __('helpdesktickets::helpdesktickets.tickets_index.sort_priority') }}</option>
                        <option value="activity" @selected(request('sort') === 'activity')>{{ __('helpdesktickets::helpdesktickets.tickets_index.sort_activity') }}</option>
                        <option value="waiting" @selected(request('sort') === 'waiting')>{{ __('helpdesktickets::helpdesktickets.tickets_index.sort_waiting') }}</option>
                    </select>
                    <span class="tkt-list-head-icons">
                        <button type="button" id="tkt-list-density" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.density_title') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.density_title') }}" aria-pressed="false"><i class="fa-solid fa-compress"></i></button>
                        <button type="button" id="tkt-list-refresh" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.refresh') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.refresh_aria') }}"><i class="fa-solid fa-rotate"></i></button>
                        <button type="button" id="tkt-list-export" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.export') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.export_aria') }}"><i class="fa-solid fa-download"></i></button>
                        <button type="button" id="tkt-list-trash" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.trash_title') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.trash_aria') }}"><i class="fa-regular fa-trash-can"></i></button>
                    </span>
                </div>
                <div class="tkt-bulk-bar" id="tkt-bulk-bar">
                    <span id="tkt-bulk-count" class="tkt-title-sm">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_selected_count', ['count' => 0]) }}</span>
                    {{-- 24-sep-2026: 14 acciones en línea ocupaban 3-4 filas en
                         la columna de 380 px. Quedan a la vista las cuatro de
                         uso diario y el resto va en "Más acciones". --}}
                    <span class="tkt-actions">
                        @can('helpdesk.tickets.update')
                            <button type="button" class="tkt-btn" data-bulk-action="assign">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_assign') }}</button>
                            <button type="button" class="tkt-btn" data-bulk-action="change_status">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_change_status') }}</button>
                        @endcan
                        @can('helpdesk.tickets.resolve')
                            <button type="button" class="tkt-btn" data-bulk-action="resolve">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_resolve') }}</button>
                        @endcan
                        @can('helpdesk.tickets.close')
                            <button type="button" class="tkt-btn" data-bulk-action="close">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_close') }}</button>
                        @endcan
                        <span class="tkt-relative">
                            <button type="button" class="tkt-btn" id="tkt-bulk-more" aria-haspopup="menu" aria-expanded="false" aria-controls="tkt-bulk-more-menu">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_more_actions') }}</button>
                            <div class="tkt-drop tkt-bulk-more-menu" id="tkt-bulk-more-menu" role="menu" hidden>
                                @can('helpdesk.tickets.update')
                                    <button type="button" class="tkt-drop-item" role="menuitem" data-bulk-action="change_priority">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_change_priority') }}</button>
                                    <button type="button" class="tkt-drop-item" role="menuitem" data-bulk-action="add_tag">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_add_tag') }}</button>
                                    <button type="button" class="tkt-drop-item" role="menuitem" data-bulk-action="remove_tag">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_remove_tag') }}</button>
                                    <button type="button" class="tkt-drop-item" role="menuitem" data-bulk-action="snooze">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_snooze') }}</button>
                                    <button type="button" class="tkt-drop-item" role="menuitem" id="tkt-bulk-move-team">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_move_team') }}</button>
                                    <button type="button" class="tkt-drop-item" role="menuitem" data-bulk-action="retry_failed_mail">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_retry_mail') }}</button>
                                    <button type="button" class="tkt-drop-item" role="menuitem" id="tkt-bulk-link-ticket">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_link_ticket') }}</button>
                                @endcan
                                <button type="button" class="tkt-drop-item" role="menuitem" id="tkt-bulk-export">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_export_selection') }}</button>
                                @can('helpdesk.tickets.delete')
                                    <button type="button" class="tkt-drop-item" role="menuitem" data-bulk-action="delete">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_delete') }}</button>
                                @endcan
                            </div>
                        </span>
                        <button type="button" class="tkt-btn" id="tkt-bulk-clear">{{ __('helpdesktickets::helpdesktickets.tickets_index.bulk_clear_selection') }}</button>
                    </span>
                </div>
                <div class="tkt-skeleton-list" id="tkt-skeleton">
                    <div class="tkt-skeleton"></div><div class="tkt-skeleton"></div><div class="tkt-skeleton"></div>
                </div>
                <div class="tkt-list" id="tkt-list" role="list" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.list_items_aria') }}"></div>
                {{-- Pie: "1–6 de 218 tickets" y dos chevrones, como el mockup —
                     no el paginador numerado del tema, que no cabe en 380px de
                     columna y desentona con el resto de la pantalla. --}}
                {{-- El pie lo repinta también el JS tras cada refetch (renderFoot
                     en tickets-app.js), así que los ids/clases de aquí son
                     contrato: el SSR pinta la primera carga y el JS las
                     siguientes, con el mismo markup. --}}
                <div class="tkt-list-foot">
                    <span id="tkt-foot-range">{{ __('helpdesktickets::helpdesktickets.tickets_index.list_foot_range', ['first' => $tickets->firstItem() ?? 0, 'last' => $tickets->lastItem() ?? 0, 'total' => $tickets->total()]) }}</span>
                    <span class="tkt-list-foot-nav" id="tkt-foot-nav">
                        @if($tickets->onFirstPage())
                            <span class="off" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></span>
                        @else
                            <a href="{{ $tickets->previousPageUrl() }}" rel="prev" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.list_foot_prev') }}"><i class="fa-solid fa-chevron-left"></i></a>
                        @endif
                        @if($tickets->hasMorePages())
                            <a href="{{ $tickets->nextPageUrl() }}" rel="next" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.list_foot_next') }}"><i class="fa-solid fa-chevron-right"></i></a>
                        @else
                            <span class="off" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                        @endif
                    </span>
                </div>
            </div>

            <button type="button" class="tkt-split-resizer" id="tkt-resizer-list" data-resize-target="list"
                    role="separator" aria-orientation="vertical" aria-valuemin="320" aria-valuemax="520" aria-valuenow="380" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.resize_list_aria') }}"
                    title="{{ __('helpdesktickets::helpdesktickets.tickets_index.resize_list_title') }}"></button>

            {{-- Columna: detalle --}}
            <div class="tkt-split-detail" id="tkt-detail-col" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.detail_aria') }}">
                <div class="tkt-empty-state" id="tkt-detail-empty">
                    <div class="tkt-empty-icon"><i class="fa-regular fa-rectangle-list"></i></div>
                    <div class="tkt-empty-title">{{ __('helpdesktickets::helpdesktickets.tickets_index.empty_title') }}</div>
                    <div class="tkt-empty-text">{{ __('helpdesktickets::helpdesktickets.tickets_index.empty_text') }}</div>
                    <div class="tkt-badge-row">
                        <span class="tkt-hint-key">{{ __('helpdesktickets::helpdesktickets.tickets_index.hint_navigate') }}</span>
                        <span class="tkt-hint-key">{{ __('helpdesktickets::helpdesktickets.tickets_index.hint_open') }}</span>
                        <span class="tkt-hint-key">{{ __('helpdesktickets::helpdesktickets.tickets_index.hint_new') }}</span>
                        <span class="tkt-hint-key">{{ __('helpdesktickets::helpdesktickets.tickets_index.hint_shortcuts') }}</span>
                    </div>
                    @can('helpdesk.tickets.create')
                        <button type="button" class="tkt-btn tkt-btn-primary" id="tkt-empty-create">{{ __('helpdesktickets::helpdesktickets.tickets_index.empty_create') }}</button>
                    @endcan
                </div>
                <div id="tkt-detail" class="tkt-initially-hidden"></div>
            </div>

            <button type="button" class="tkt-split-resizer" id="tkt-resizer-side" data-resize-target="side"
                    role="separator" aria-orientation="vertical" aria-valuemin="290" aria-valuemax="460" aria-valuenow="316" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.resize_side_aria') }}"
                    title="{{ __('helpdesktickets::helpdesktickets.tickets_index.resize_side_title') }}"></button>

            {{-- Columna: panel lateral — Fase C: las 8 pestañas ya tienen
                 contenido real (reusan el mismo JSON de data()). --}}
            <div class="tkt-side" id="tkt-side" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_aria') }}">
                <div class="tkt-icon-rail top" id="tkt-side-rail">
                    <button type="button" class="tkt-icon-tab on" data-side="gestion" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_gestion') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_gestion') }}"><i class="fa-solid fa-sliders"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="cliente" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_cliente') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_cliente') }}"><i class="fa-regular fa-address-card"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="form" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_form') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_form') }}"><i class="fa-regular fa-rectangle-list"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="correo" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_correo') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_correo') }}"><i class="fa-regular fa-envelope"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="notas" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_notas') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_notas') }}"><i class="fa-regular fa-note-sticky"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="tags" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_tags') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_tags') }}"><i class="fa-solid fa-tag" aria-hidden="true"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="files" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_files') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_files') }}"><i class="fa-solid fa-paperclip"></i></button>
                    {{-- Novena pestaña del mockup: el histórico de tickets del
                         mismo cliente, para ver si lo que pregunta ya se le
                         respondió antes sin salir de la pantalla. --}}
                    <button type="button" class="tkt-icon-tab" data-side="tickets" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_tickets') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_tickets') }}"><i class="fa-solid fa-ticket"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="hist" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_hist') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_hist') }}"><i class="fa-solid fa-clock-rotate-left"></i></button>
                    <button type="button" class="tkt-side-toggle" id="tkt-side-toggle" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_toggle_title') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.side_toggle_title') }}" aria-pressed="false"><i class="fa-solid fa-angles-right"></i></button>
                </div>
                <div id="tkt-side-content" class="tkt-side-pane">
                    <div class="tkt-empty-box">{{ __('helpdesktickets::helpdesktickets.tickets_index.side_empty') }}</div>
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
        <div class="tkt-status-bar" id="tkt-status-bar" role="status" aria-live="polite">
            <span class="tkt-status-item"><span class="tkt-status-dot" id="tkt-status-conn-dot"></span><span id="tkt-status-conn-text">{{ __('helpdesktickets::helpdesktickets.tickets_index.status_connecting') }}</span></span>
            <span class="tkt-status-sep">·</span>
            <span class="tkt-status-item" id="tkt-status-agents"><i class="fa-solid fa-users"></i> —</span>
            <span class="tkt-status-sep">·</span>
            <span class="tkt-status-item" id="tkt-status-sla"><i class="fa-regular fa-clock"></i> {{ __('helpdesktickets::helpdesktickets.tickets_index.status_sla_risk') }}</span>
            <span class="tkt-status-sep">·</span>
            <span class="tkt-status-item" id="tkt-status-resolved"><i class="fa-solid fa-circle-check"></i> {{ __('helpdesktickets::helpdesktickets.tickets_index.status_resolved') }}</span>
            <span class="tkt-status-sep">·</span>
            <button type="button" class="tkt-status-item tkt-status-queue" id="tkt-status-queue" hidden aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.status_queue_aria') }}"><i class="fa-regular fa-envelope"></i></button>
            <span class="tkt-undo" id="tkt-undo" hidden aria-live="polite"><span id="tkt-undo-text"></span><button type="button" class="tkt-btn tkt-btn-mini" id="tkt-undo-btn">{{ __('helpdesktickets::helpdesktickets.tickets_index.status_undo') }}</button></span>
            <span class="tkt-spacer"></span>
            <button type="button" class="tkt-status-icon-btn" id="tkt-status-sound" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.status_sound_title') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.status_sound_aria') }}" aria-pressed="false"><i class="fa-solid fa-volume-high"></i></button>
            <button type="button" class="tkt-status-icon-btn" id="tkt-status-shortcuts" title="{{ __('helpdesktickets::helpdesktickets.tickets_index.status_shortcuts_title') }}" aria-label="{{ __('helpdesktickets::helpdesktickets.tickets_index.status_shortcuts_aria') }}"><i class="fa-solid fa-keyboard"></i></button>
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
        $manifest = json_decode(@file_get_contents($manifestPath) ?: '{}', true) ?: [];
        $ticketsAppFiles = $manifest['files'] ?? [];

        // Modales que el bundle NO trae y descarga la primera vez que se abren
        // (manifest → "lazy"; ver scripts/build-tickets-app.mjs). Existen solo
        // como js/tickets-app-lazy/<fichero>.min.js.
        $lazyFiles = array_keys($manifest['lazy'] ?? []);
        $lazyDir = 'modules/helpdesktickets/js/tickets-app-lazy';

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

        // Con modales 'lazy' el bundle solo es válido si TODOS sus ficheros
        // bajo demanda existen y son más recientes que su fuente: un bundle
        // nuevo con un modal que no se puede descargar dejaría botones muertos.
        if ($useMinified) {
            foreach ($lazyFiles as $file) {
                $lazyMtime = @filemtime(public_path($lazyDir.'/'.$file.'.min.js'));
                $srcMtime = @filemtime(public_path('modules/helpdesktickets/js/tickets-app/'.$file.'.js'));
                if ($lazyMtime === false || $srcMtime === false || $srcMtime > $lazyMtime) {
                    $useMinified = false;
                    break;
                }
            }
        }
    @endphp
    @if ($useMinified)
        @if ($lazyFiles !== [])
            <script>window.TKT_LAZY = { base: @json(asset($lazyDir)), v: @json((string) $minMtime) };</script>
        @endif
        <script src="{{ asset('modules/helpdesktickets/js/tickets-app.min.js') }}?v={{ $minMtime }}"></script>
    @else
        @foreach ($ticketsAppFiles as $file)
            <script src="{{ asset('modules/helpdesktickets/js/tickets-app/'.$file.'.js') }}?v={{ @filemtime(public_path('modules/helpdesktickets/js/tickets-app/'.$file.'.js')) }}"></script>
        @endforeach
    @endif
@endpush
