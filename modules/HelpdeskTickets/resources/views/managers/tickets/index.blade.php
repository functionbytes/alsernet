@extends('layouts.theme')

@section('title', 'Tickets')

@push('css')
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
         data-notes-store-url-template="{{ route('manager.helpdesk.tickets.notes.store', ['ticket' => '__TICKET__']) }}"
         data-views-store-url="{{ route('manager.helpdesk.tickets.views.store') }}"
         data-contacts-sync-url-template="{{ Nwidart\Modules\Facades\Module::isEnabled('HelpdeskContacts') ? route('contacts.sync', ['customer' => '__CUSTOMER__']) : '' }}"
         data-macros-list-url="{{ route('manager.helpdesk.macros.list') }}"
         data-emails-store-url="{{ route('manager.helpdesk.tickets.emails.store') }}"
         data-typing-url-template="{{ route('manager.helpdesk.tickets.typing', ['ticket' => '__TICKET__']) }}"
         data-export-url-template="{{ route('manager.helpdesk.tickets.export', ['format' => '__FORMAT__']) }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}"
         data-automations-index-url="{{ route('manager.helpdesk.settings.automations.index') }}"
         @if(Nwidart\Modules\Facades\Module::isEnabled('HelpdeskContacts'))
         data-contacts-merge-search-url-template="{{ route('contacts.merge.search', ['customer' => '__CUSTOMER__']) }}"
         data-contacts-merge-preview-url-template="{{ route('contacts.merge.preview', ['customer' => '__CUSTOMER__']) }}"
         data-contacts-merge-execute-url-template="{{ route('contacts.merge.execute', ['customer' => '__CUSTOMER__']) }}"
         @endif
         data-ops-url="{{ route('manager.helpdesk.tickets.ops') }}"
         data-workload-url="{{ route('manager.helpdesk.tickets.workload') }}"
         data-workload-distribute-url="{{ route('manager.helpdesk.tickets.workload.distribute') }}"
         data-statuses="{{ json_encode($statuses->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-categories="{{ json_encode($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-groups="{{ json_encode($groups->map(fn ($g) => ['id' => $g->id, 'name' => $g->name]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-agents-full="{{ json_encode($agents->map(fn ($a) => ['id' => $a->id, 'name' => trim($a->firstname.' '.$a->lastname)]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-canned-replies="{{ json_encode($cannedReplies->map(fn ($r) => ['id' => $r->id, 'title' => $r->title, 'content' => $r->content, 'short_code' => $r->short_code]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-close-reasons="{{ json_encode(collect(config('helpdesktickets.close_reasons', []))->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         hidden>
    </div>

    <div class="tkt-app-scroll">
    <div class="tkt-app-card">

        {{-- Barra superior --}}
        <div class="tkt-app-bar">
            <div class="tkt-crumbs">
                <i class="fa-solid fa-headset"></i><span>Helpdesk</span><i class="fa-solid fa-chevron-right"></i><span class="on">Tickets</span>
            </div>
            <div class="tkt-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="tkt-search" placeholder="Buscar por nº de ticket, cliente o asunto…" value="{{ request('search') }}">
            </div>
            <div style="margin-left:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap;row-gap:8px">
                <div class="tkt-seg" id="tkt-mode-switch">
                    <button type="button" class="on" data-mode="list"><i class="fa-solid fa-list"></i> Lista</button>
                    <button type="button" data-mode="kanban"><i class="fa-solid fa-table-columns"></i> Kanban</button>
                </div>
                <button type="button" class="tkt-btn" id="tkt-sync" title="Resincronizar con PrestaShop/ERP"><i class="fa-solid fa-rotate"></i> Sincronizar</button>
                <button type="button" class="tkt-btn" id="tkt-export-open" title="Exportar los tickets del filtro actual"><i class="fa-solid fa-download"></i> Exportar</button>
                <a href="{{ route('manager.helpdesk.ticket-templates.index') }}" class="tkt-btn"><i class="fa-solid fa-clone"></i> Plantillas</a>
                <a href="{{ route('manager.helpdesk.tickets.create') }}" class="tkt-btn tkt-btn-primary"><i class="fa-solid fa-plus"></i> Nuevo ticket</a>
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
            <a href="{{ route('manager.helpdesk.ticket-templates.index') }}" class="tkt-state-tab" style="text-decoration:none">Plantillas</a>
            <span class="tkt-queue-hint">SLA en riesgo: {{ $tabCounts['sla_risk'] }}</span>
        </div>

        {{-- Filtros --}}
        <form method="get" id="tkt-filter-form" class="tkt-filter-bar">
            <select name="source" class="tkt-fselect" onchange="this.form.submit()">
                <option value="">Origen: todos</option>
                <option value="email" @selected(request('source') === 'email')>Email</option>
                <option value="widget" @selected(request('source') === 'widget')>Widget</option>
                <option value="wa" @selected(request('source') === 'wa')>WhatsApp</option>
                <option value="fb" @selected(request('source') === 'fb')>Facebook</option>
                <option value="ig" @selected(request('source') === 'ig')>Instagram</option>
                <option value="formulario" @selected(request('source') === 'formulario')>Formulario</option>
            </select>
            <select name="category" class="tkt-fselect" onchange="this.form.submit()">
                <option value="">Categoría: todas</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="assignee" class="tkt-fselect" onchange="this.form.submit()">
                <option value="">Agente: todos</option>
                <option value="me" @selected(request('assignee') === 'me')>Asignados a mí</option>
                <option value="unassigned" @selected(request('assignee') === 'unassigned')>Sin asignar</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" @selected(request('assignee') == $agent->id)>{{ trim($agent->firstname.' '.$agent->lastname) }}</option>
                @endforeach
            </select>
            <select name="priority" class="tkt-fselect" onchange="this.form.submit()">
                <option value="">Prioridad: todas</option>
                <option value="urgent" @selected(request('priority') === 'urgent')>Urgente</option>
                <option value="high" @selected(request('priority') === 'high')>Alta</option>
                <option value="normal" @selected(request('priority') === 'normal')>Normal</option>
                <option value="low" @selected(request('priority') === 'low')>Baja</option>
            </select>
            <select name="tag" class="tkt-fselect" onchange="this.form.submit()">
                <option value="">Etiquetas: todas</option>
                @foreach($availableTags as $tagOption)
                    <option value="{{ $tagOption }}" @selected(request('tag') === $tagOption)>{{ $tagOption }}</option>
                @endforeach
            </select>
            <button type="button" class="tkt-btn" id="tkt-filters-modal-open"><i class="fa-solid fa-sliders"></i> Más filtros</button>
            <a href="{{ route('manager.helpdesk.tickets.index') }}" class="tkt-clear-link">limpiar</a>
            <span id="tkt-count" class="mono" style="margin-left:auto;color:var(--tkt-text-faint);font-size:10.5px">{{ $tickets->total() }} tickets</span>
        </form>

        @php
            // Chips de filtro activo con ✕ para quitar uno a uno, en vez de
            // solo el link "limpiar" que borra todos de golpe.
            $activeFilterLabels = [
                'source' => 'Origen', 'category' => 'Categoría', 'assignee' => 'Agente',
                'priority' => 'Prioridad', 'tag' => 'Etiqueta', 'search' => 'Búsqueda',
                'created_from' => 'Desde', 'created_to' => 'Hasta', 'sla_status' => 'SLA',
            ];
            // Bug real de QA: el chip mostraba el valor CRUDO del query
            // param ("Prioridad: urgent", "Agente: 16") en vez de la
            // etiqueta legible — mismas fuentes que ya usan los <select> de
            // esta misma barra, para no duplicar ni desalinear el mapeo.
            $filterValueLabel = function (string $key, string $value) use ($categories, $agents) {
                return match ($key) {
                    'priority' => ['urgent' => 'Urgente', 'high' => 'Alta', 'normal' => 'Normal', 'low' => 'Baja'][$value] ?? $value,
                    'source' => ['email' => 'Email', 'widget' => 'Widget', 'wa' => 'WhatsApp', 'fb' => 'Facebook', 'ig' => 'Instagram', 'formulario' => 'Formulario'][$value] ?? $value,
                    'sla_status' => ['breach' => 'Vencido', 'warn' => 'En riesgo', 'ok' => 'En plazo'][$value] ?? $value,
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
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;padding:0 14px 9px">
                @foreach($activeFilters as $f)
                    <a href="{{ route('manager.helpdesk.tickets.index', request()->except([$f['key'], 'page'])) }}" class="tkt-filter-chip" style="text-decoration:none">
                        {{ $f['label'] }}: {{ Str::limit($f['value'], 24) }} <i class="fa-solid fa-xmark"></i>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Vistas guardadas: los recortes rápidos que no son "estado" del
             ticket (Urgentes/Míos) viven aquí como chips client-side, igual
             que en el mockup, además de las vistas guardadas reales
             (TicketView) y el acceso a gestionarlas. --}}
        <div class="tkt-views-bar">
            <span class="tkt-cap">Vistas</span>
            <button type="button" class="tkt-view-pill" data-filter="urgent">Urgentes <span class="mono">{{ $tabCounts['urgent'] }}</span></button>
            <button type="button" class="tkt-view-pill" data-filter="mine">Míos <span class="mono">{{ $tabCounts['mine'] }}</span></button>
            <button type="button" class="tkt-view-pill" data-filter="sla_risk">SLA en riesgo <span class="mono">{{ $tabCounts['sla_risk'] }}</span></button>
            @foreach($views as $view)
                <a href="{{ route('manager.helpdesk.tickets.index', ['viewId' => $view->id]) }}"
                   class="tkt-view-pill @if($currentView?->id === $view->id) on @endif">{{ $view->name }}</a>
            @endforeach
            <button type="button" class="tkt-view-pill add" id="tkt-save-view">+ guardar vista</button>
            <button type="button" class="tkt-view-pill" id="tkt-pill-queue" title="Estado de las colas de trabajo"><i class="fa-solid fa-layer-group"></i> Cola</button>
            <button type="button" class="tkt-view-pill" id="tkt-pill-workload" title="Carga de trabajo por agente"><i class="fa-solid fa-scale-balanced"></i> Carga</button>
            <a href="{{ route('settings.helpdesk.views.index') }}" class="tkt-view-pill" title="Gestionar todas las vistas" style="margin-left:auto">Gestionar vistas</a>
        </div>

        <div class="tkt-split-wrap" id="tkt-split-wrap">
        <div class="tkt-split">

            {{-- Columna: lista --}}
            <div class="tkt-split-list">
                <div class="tkt-list-head">
                    <input type="checkbox" id="tkt-select-all">
                    <span style="font-size:11px;color:var(--tkt-text-mute)">Seleccionar todo</span>
                    <a href="{{ route('manager.helpdesk.tickets.index', array_merge(request()->except('page'), ['sort' => request('sort') === 'sla' ? null : 'sla'])) }}"
                       class="tkt-clear-link" style="margin-left:auto;text-decoration:none">
                        <i class="fa-solid fa-arrow-down-{{ request('sort') === 'sla' ? 'short-wide' : 'wide-short' }}"></i>
                        {{ request('sort') === 'sla' ? 'Ordenado por SLA' : 'Ordenar por SLA' }}
                    </a>
                </div>
                <div class="tkt-bulk-bar" id="tkt-bulk-bar">
                    <span id="tkt-bulk-count" style="font-size:11.5px;font-weight:700">0 seleccionados</span>
                    <span style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
                        @can('helpdesk.tickets.update')
                            <button type="button" class="tkt-btn" data-bulk-action="assign">Asignar</button>
                            <button type="button" class="tkt-btn" data-bulk-action="add_tag">Etiquetar</button>
                            <button type="button" class="tkt-btn" data-bulk-action="change_status">Cambiar estado</button>
                            <button type="button" class="tkt-btn" id="tkt-bulk-move-team">Mover a equipo</button>
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
                        <button type="button" class="tkt-btn" id="tkt-bulk-clear">Quitar</button>
                    </span>
                </div>
                <div class="tkt-skeleton-list" id="tkt-skeleton">
                    <div class="tkt-skeleton"></div><div class="tkt-skeleton"></div><div class="tkt-skeleton"></div>
                </div>
                <div class="tkt-list" id="tkt-list"></div>
                <div class="tkt-list-foot">
                    <span>{{ $tickets->firstItem() ?? 0 }}–{{ $tickets->lastItem() ?? 0 }} de {{ $tickets->total() }}</span>
                    @if($tickets->hasPages())
                        <span style="margin-left:auto">{{ $tickets->onEachSide(1)->links() }}</span>
                    @endif
                </div>
            </div>

            {{-- Columna: detalle --}}
            <div class="tkt-split-detail" id="tkt-detail-col">
                <div class="tkt-empty-state" id="tkt-detail-empty">
                    <div class="tkt-empty-icon"><i class="fa-regular fa-rectangle-list"></i></div>
                    <div class="tkt-empty-title">Ningún ticket seleccionado</div>
                    <div class="tkt-empty-text">Elige un ticket de la lista para ver el hilo, la trazabilidad, la actividad y gestionarlo desde el panel de la derecha.</div>
                    <div style="display:flex;gap:6px;margin-top:4px;flex-wrap:wrap;justify-content:center">
                        <span class="tkt-hint-key">J / K navegar</span>
                        <span class="tkt-hint-key">C nuevo ticket</span>
                    </div>
                </div>
                <div id="tkt-detail" style="display:none"></div>
            </div>

            {{-- Columna: panel lateral — Fase C: las 8 pestañas ya tienen
                 contenido real (reusan el mismo JSON de data()). --}}
            <div class="tkt-side" id="tkt-side">
                <div class="tkt-icon-rail top" id="tkt-side-rail">
                    <button type="button" class="tkt-icon-tab on" data-side="gestion" title="Gestión"><i class="fa-solid fa-sliders"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="cliente" title="Cliente"><i class="fa-regular fa-address-card"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="form" title="Formulario"><i class="fa-regular fa-rectangle-list"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="correo" title="Correo"><i class="fa-regular fa-envelope"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="notas" title="Notas internas"><i class="fa-regular fa-note-sticky"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="tags" title="Etiquetas"><i class="fa-solid fa-tag"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="files" title="Archivos"><i class="fa-solid fa-paperclip"></i></button>
                    <button type="button" class="tkt-icon-tab" data-side="hist" title="Historial"><i class="fa-solid fa-clock-rotate-left"></i></button>
                </div>
                <div id="tkt-side-content" class="tkt-side-pane">
                    <div class="tkt-empty-box">Sin ticket seleccionado. Aquí verás la gestión (estado, prioridad, categoría, equipo, acciones) del ticket que elijas en la lista.</div>
                </div>
            </div>

        </div>
        </div>

        {{-- Modo Kanban: llega en la Fase D --}}
        <div class="tkt-kanban" id="tkt-kanban"></div>

    </div>
    </div>

    {{-- Dentro de .tkt a propósito: las variables --tkt-* solo se definen
         bajo ese selector — fuera de él el modal se renderiza transparente
         (mismo bug ya corregido una vez en openModal(), ver tickets-app.js). --}}
    @include('helpdesktickets::managers.tickets.partials._filters-modal')
</div>
@endsection

@push('scripts')
    <script src="{{ asset('modules/helpdesktickets/js/tickets-app.js') }}?v={{ @filemtime(public_path('modules/helpdesktickets/js/tickets-app.js')) }}"></script>
@endpush
