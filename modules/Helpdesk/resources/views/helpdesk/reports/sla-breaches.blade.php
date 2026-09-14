@extends('layouts.theme')
@section('title', 'Incumplimientos SLA · Helpdesk')

@section('page_header')
    @include('core::components.card', ['title' => 'Incumplimientos SLA · Helpdesk'])
@endsection

@section('content')

{{-- Estado no disponible (módulo de tickets desactivado) --}}
<div id="state-unavailable" class="text-center py-5 d-none">
    <i class="fas fa-plug-circle-xmark fa-3x mb-3 text-muted opacity-50"></i>
    <h5 class="fw-bold mb-2">El módulo de tickets no está disponible</h5>
    <p class="text-muted mb-0">Sin él no hay SLA que medir. Actívalo en los ajustes de integraciones del helpdesk.</p>
</div>

<div id="state-content" class="widget-content searchable-container list d-none">

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Incumplimientos SLA</h5>
                    <p class="small mb-0 text-muted">Tickets abiertos que han superado su plazo de resolución, y los que lo superarán en las próximas 24 horas</p>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" id="btn-export" class="btn btn-secondary">
                        <i class="fas fa-file-export me-1"></i> Exportar
                    </button>
                    <button type="button" id="btn-refresh" class="btn btn-primary">
                        <i class="fas fa-rotate me-1"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Incumplidos ahora</h6>
                            <h4 class="mb-1 fw-bold" id="kpi-breached">—</h4>
                            <small class="text-muted" id="kpi-breached-hint">&nbsp;</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Agentes afectados</h6>
                            <h4 class="mb-1 fw-bold" id="kpi-agents">—</h4>
                            <small class="text-muted" id="kpi-agents-hint">&nbsp;</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Retraso medio</h6>
                            <h4 class="mb-1 fw-bold" id="kpi-avg">—</h4>
                            <small class="text-muted" id="kpi-avg-hint">&nbsp;</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Vencen en 24 h</h6>
                            <h4 class="mb-1 fw-bold" id="kpi-upcoming">—</h4>
                            <small class="text-muted" id="kpi-upcoming-hint">&nbsp;</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card-body border-bottom">
            <div class="d-flex align-items-center gap-2">
                <input type="search" id="slb-search" class="form-control flex-grow-1"
                       placeholder="Buscar por asunto o número..." autocomplete="off">
                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                        data-bs-toggle="modal" data-bs-target="#slb-filter-modal" title="Filtros avanzados">
                    <i class="fas fa-filter"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary slb-filter-badge d-none" id="slb-filter-badge">1</span>
                </button>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="card-body">
            <ul class="nav nav-pills user-profile-tab mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            data-bs-toggle="pill" data-bs-target="#slb-tab-breached"
                            type="button" role="tab" aria-controls="slb-tab-breached" aria-selected="true">
                        Vencidos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            data-bs-toggle="pill" data-bs-target="#slb-tab-upcoming"
                            type="button" role="tab" aria-controls="slb-tab-upcoming" aria-selected="false">
                        Próximos a vencer
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                {{-- Vencidos --}}
                <div class="tab-pane fade show active" id="slb-tab-breached">
                    <p class="small text-muted mb-3">Ordenados por retraso, de mayor a menor.</p>

                    <div id="slb-breached-wrap">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        @if($bulkUrl)
                                            <th width="3%"><input type="checkbox" id="slb-select-all-breached" class="form-check-input" aria-label="Seleccionar todos los tickets"></th>
                                        @endif
                                        <th>Ticket</th>
                                        <th>Asunto</th>
                                        <th>Agente</th>
                                        <th>Vencía</th>
                                        <th>Retraso</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="slb-breached-body"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="text-muted small" id="slb-count">&nbsp;</div>
                            <div class="text-muted small" id="slb-updated">&nbsp;</div>
                        </div>
                    </div>

                    <div id="slb-breached-empty" class="text-center py-5 d-none">
                        <i class="fas fa-circle-check fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2" id="slb-breached-empty-title">Ningún SLA incumplido</h5>
                        <p class="text-muted mb-0" id="slb-breached-empty-text">Todos los tickets abiertos están dentro de plazo.</p>
                    </div>
                </div>

                {{-- Próximos a vencer --}}
                <div class="tab-pane fade" id="slb-tab-upcoming">
                    <p class="small text-muted mb-3">Todavía a tiempo. La barra marca cuánto queda de las 24 horas.</p>

                    <div id="slb-upcoming-wrap">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        @if($bulkUrl)
                                            <th width="3%"><input type="checkbox" id="slb-select-all-upcoming" class="form-check-input" aria-label="Seleccionar todos los tickets"></th>
                                        @endif
                                        <th>Ticket</th>
                                        <th>Asunto</th>
                                        <th>Agente</th>
                                        <th>Vence</th>
                                        <th>Tiempo restante</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="slb-upcoming-body"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="text-muted small" id="slb-upcoming-count">&nbsp;</div>
                        </div>
                    </div>

                    <div id="slb-upcoming-empty" class="text-center py-5 d-none">
                        <i class="fas fa-circle-check fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">Sin vencimientos a la vista</h5>
                        <p class="text-muted mb-0">Ningún ticket abierto vence en las próximas 24 horas.</p>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

{{-- Modal de filtros avanzados --}}
<div class="modal fade" id="slb-filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filtros avanzados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Agente</label>
                    <select id="slb-modal-agent" class="form-control select2-filter-modal">
                        <option value="">Todos los agentes</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Retraso</label>
                    <select id="slb-modal-band" class="form-control select2-filter-modal">
                        <option value="all">Todos (0)</option>
                        <option value="high">Más de 48 h (0)</option>
                        <option value="mid">24 – 48 h (0)</option>
                        <option value="low">Menos de 24 h (0)</option>
                    </select>
                    <p class="small text-muted mt-2 mb-0">Solo aplica a la pestaña "Vencidos".</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="slb-filter-apply-btn" class="btn btn-primary w-100 mb-1">Aplicar filtros</button>
                <button type="button" id="slb-filter-clear-btn" class="btn btn-secondary w-100">Limpiar</button>
            </div>
        </div>
    </div>
</div>

@if($bulkUrl)
    {{-- Bulk: reasignar en masa — una barra/modal por pestaña (Vencidos / Próximos
         a vencer), igual patrón que ticket-templates, para no mezclar la selección
         de una tabla con la otra. --}}
    {{-- Los ids de toolbar/modal siguen la convención que espera core/js/bulk.js
         (bulk-toolbar-X → bulk-X-modal, ver getCountEls() ahí): con otro prefijo
         el contador del modal se queda pegado en 0 porque no encuentra el modal. --}}
    @foreach(['breached' => 'Vencidos', 'upcoming' => 'Próximos a vencer'] as $slbGroup => $slbGroupLabel)
        <div id="bulk-toolbar-{{ $slbGroup }}" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none slb-bulk-toolbar">
            <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-{{ $slbGroup }}-modal">
                <span data-bulk-count>0</span> ticket(s) seleccionado(s) — Reasignar
            </button>
        </div>

        <div class="modal fade" id="bulk-{{ $slbGroup }}-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reasignar tickets — {{ $slbGroupLabel }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Se reasignarán <strong><span data-bulk-count>0</span> ticket(s)</strong> al agente seleccionado.</p>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Agente</label>
                            <select id="slb-bulk-{{ $slbGroup }}-agent" class="form-select select2">
                                <option value="">Seleccionar agente...</option>
                                @foreach($bulkAgents as $bulkAgent)
                                    <option value="{{ $bulkAgent['id'] }}">{{ $bulkAgent['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="slb-bulk-{{ $slbGroup }}-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Reasignar</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

@endsection

@push('styles')
<style>
    .slb-filter-badge { font-size: .6rem; }
    .slb-progress { height: 6px; }
    .slb-bulk-toolbar { z-index: 1050; }
</style>
@endpush

@if($bulkUrl)
    @push('scripts')
    <script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
    @endpush
@endif

@push('scripts')
<script>
(function () {
    const dataUrl = "{{ route('manager.helpdesk.reports.sla-breaches.data') }}";
    const bulkUrl = @json($bulkUrl);

    // Umbrales de las bandas de gravedad, en minutos. Están aquí y no
    // repartidos por el código porque los chips, el badge y el color de la
    // barrita tienen que coincidir; si se tocan, se tocan en un solo sitio.
    const BAND_HIGH = 48 * 60;
    const BAND_MID = 24 * 60;

    // Etiquetas base del select de banda — renderBandCounts() les añade el
    // conteo entre paréntesis cada vez que llegan datos nuevos.
    const BAND_LABELS = {
        all: 'Todos',
        high: 'Más de 48 h',
        mid: '24 – 48 h',
        low: 'Menos de 24 h',
    };

    // Ventana del bloque "próximos vencimientos": la misma que pide el
    // controlador a getUpcomingBreaches(24), y la que divide la barra.
    const UPCOMING_WINDOW_MIN = 24 * 60;

    let breached = [];
    let upcoming = [];
    let band = 'all';
    let agentFilter = '';
    let bulkBreached = null;
    let bulkUpcoming = null;

    function esc(value) {
        return $('<span>').text(value == null ? '' : value).html();
    }

    function formatDateTime(iso) {
        if (!iso) {
            return '—';
        }
        return new Date(iso).toLocaleString('es-ES', {
            day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
        });
    }

    // "3 d 4 h" / "62 h 43 m" / "18 m" — sin decimales y sin unidades vacías.
    function formatDuration(minutes) {
        const total = Math.max(0, Math.round(minutes || 0));
        const hours = Math.floor(total / 60);
        const mins = total % 60;

        if (hours >= 48) {
            const days = Math.floor(hours / 24);
            const rest = hours % 24;
            return rest > 0 ? (days + ' d ' + rest + ' h') : (days + ' d');
        }

        if (hours > 0) {
            return mins > 0 ? (hours + ' h ' + mins + ' m') : (hours + ' h');
        }

        return mins + ' m';
    }

    function bandOf(minutes) {
        if (minutes >= BAND_HIGH) { return 'high'; }
        if (minutes >= BAND_MID) { return 'mid'; }
        return 'low';
    }

    function delayBadgeClass(severity) {
        if (severity === 'high') { return 'badge bg-warning text-dark'; }
        if (severity === 'mid') { return 'badge bg-warning-subtle text-warning-emphasis'; }
        return 'badge bg-light text-dark border';
    }

    function ticketLink(row) {
        const label = esc(row.number || ('#' + row.id));
        if (!row.url) {
            return '<span class="fw-semibold">' + label + '</span>';
        }
        return '<a href="' + row.url + '" class="fw-semibold text-decoration-none">' + label + '</a>';
    }

    function agentCell(row) {
        if (!row.agentId) {
            return '<span class="text-muted fst-italic">Sin asignar</span>';
        }
        return esc(row.agentName);
    }

    // Celda de selección para el bulk: vacía (ni siquiera la <td>) cuando no
    // hay endpoint de reasignación, para no descuadrar las columnas del thead.
    function checkboxCell(group, row) {
        if (!bulkUrl) {
            return '';
        }
        return '<td><input type="checkbox" class="form-check-input bulk-checkbox-' + group + '" value="' + row.id + '" aria-label="Seleccionar ticket ' + esc(row.number || ('#' + row.id)) + '"></td>';
    }

    function rowMenu(row) {
        if (!row.url) {
            return '<span class="text-muted">—</span>';
        }

        return '<div class="dropdown">' +
            '<a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">' +
            '<i class="fas fa-ellipsis-vertical"></i>' +
            '</a>' +
            '<ul class="dropdown-menu dropdown-menu-end">' +
            '<li><a class="dropdown-item" href="' + row.url + '">Abrir ticket</a></li>' +
            '</ul>' +
            '</div>';
    }

    // El endpoint devuelve los incumplidos agrupados por agente; el panel
    // necesita una sola tabla plana ordenada por retraso.
    function flatten(groups) {
        const rows = [];
        (groups || []).forEach(function (group) {
            (group.tickets || []).forEach(function (ticket) {
                rows.push(ticket);
            });
        });
        return rows.sort(function (a, b) {
            return (b.overdueMinutes || 0) - (a.overdueMinutes || 0);
        });
    }

    function matchesCommonFilters(row, term) {
        if (agentFilter !== '' && String(row.agentId || '') !== String(agentFilter)) {
            return false;
        }

        if (term === '') {
            return true;
        }

        const haystack = ((row.subject || '') + ' ' + (row.number || '')).toLowerCase();
        return haystack.indexOf(term) !== -1;
    }

    function visibleRows() {
        const term = ($('#slb-search').val() || '').toString().trim().toLowerCase();

        return breached.filter(function (row) {
            if (band !== 'all' && bandOf(row.overdueMinutes) !== band) {
                return false;
            }

            return matchesCommonFilters(row, term);
        });
    }

    function visibleUpcoming() {
        const term = ($('#slb-search').val() || '').toString().trim().toLowerCase();

        return upcoming.filter(function (row) {
            return matchesCommonFilters(row, term);
        });
    }

    function minutesUntil(iso) {
        if (!iso) { return 0; }
        return Math.max(0, (new Date(iso).getTime() - Date.now()) / 60000);
    }

    function renderKpis(payload) {
        const total = breached.length;
        const open = payload.openTotal || 0;

        $('#kpi-breached').text(total);
        $('#kpi-breached-hint').text(
            open > 0 ? ('de ' + open + ' ' + (open === 1 ? 'ticket abierto' : 'tickets abiertos')) : ' '
        );

        const withAgent = new Set();
        let unassigned = 0;
        breached.forEach(function (row) {
            if (row.agentId) { withAgent.add(row.agentId); } else { unassigned++; }
        });

        $('#kpi-agents').text(withAgent.size);
        $('#kpi-agents-hint').text(unassigned > 0 ? ('+ ' + unassigned + ' sin asignar') : 'ninguno sin asignar');

        if (total > 0) {
            const sum = breached.reduce(function (acc, row) { return acc + (row.overdueMinutes || 0); }, 0);
            $('#kpi-avg').text(formatDuration(sum / total));
            $('#kpi-avg-hint').text('el peor, ' + formatDuration(breached[0].overdueMinutes));
        } else {
            $('#kpi-avg').text('—');
            $('#kpi-avg-hint').text(' ');
        }

        $('#kpi-upcoming').text(upcoming.length);
        if (upcoming.length > 0) {
            $('#kpi-upcoming-hint').text('el primero, en ' + formatDuration(minutesUntil(upcoming[0].dueAt)));
        } else {
            $('#kpi-upcoming-hint').text('nada en la ventana');
        }
    }

    function renderBandCounts() {
        const counts = { all: breached.length, high: 0, mid: 0, low: 0 };
        breached.forEach(function (row) { counts[bandOf(row.overdueMinutes)]++; });

        const select = $('#slb-modal-band');
        const current = select.val();

        Object.keys(counts).forEach(function (key) {
            select.find('option[value="' + key + '"]').text(BAND_LABELS[key] + ' (' + counts[key] + ')');
        });

        // select2 cachea el texto de la opción ya seleccionada: un
        // trigger('change') no le hace releer el DOM si el value no cambió,
        // así que el conteo se quedaba pegado en "(0)" tras la primera
        // carga. Destruir y reinicializar el widget sí lo fuerza a repintar.
        if (select.data('select2')) {
            select.select2('destroy');
        }
        select.select2({ dropdownParent: $('#slb-filter-modal'), width: '100%' }).val(current).trigger('change');
    }

    function renderAgentOptions() {
        const select = $('#slb-modal-agent');
        const current = select.val();
        const seen = new Map();
        let hasUnassigned = false;

        breached.concat(upcoming).forEach(function (row) {
            if (row.agentId) {
                seen.set(row.agentId, row.agentName);
            } else {
                hasUnassigned = true;
            }
        });

        select.find('option:not(:first)').remove();
        seen.forEach(function (name, id) {
            select.append($('<option>').attr('value', id).text(name));
        });

        if (hasUnassigned) {
            select.append($('<option>').attr('value', '0').text('Sin asignar'));
        }

        select.val(current).trigger('change');
    }

    function renderFilterBadge() {
        const count = (agentFilter !== '' ? 1 : 0) + (band !== 'all' ? 1 : 0);
        $('#slb-filter-badge').text(count).toggleClass('d-none', count === 0);
    }

    // Refleja band/agentFilter en los controles del modal cada vez que se
    // abre: si se aplicó un filtro y se reabre el modal, debe verse marcado.
    function syncFilterModal() {
        $('#slb-modal-band').val(band).trigger('change');
    }

    function renderBreached() {
        const rows = visibleRows();
        const body = $('#slb-breached-body');

        if (rows.length === 0) {
            $('#slb-breached-wrap').addClass('d-none');
            $('#slb-breached-empty').removeClass('d-none');

            if (breached.length === 0) {
                $('#slb-breached-empty-title').text('Ningún SLA incumplido');
                $('#slb-breached-empty-text').text('Todos los tickets abiertos están dentro de plazo.');
            } else {
                $('#slb-breached-empty-title').text('Sin resultados');
                $('#slb-breached-empty-text').text('Ningún ticket incumplido encaja con este filtro.');
            }
            if (bulkBreached) { bulkBreached.reset(); }
            return;
        }

        $('#slb-breached-wrap').removeClass('d-none');
        $('#slb-breached-empty').addClass('d-none');

        const html = rows.map(function (row) {
            const severity = bandOf(row.overdueMinutes);

            return '<tr>' +
                checkboxCell('breached', row) +
                '<td>' + ticketLink(row) + '</td>' +
                '<td>' + esc(row.subject) + '</td>' +
                '<td>' + agentCell(row) + '</td>' +
                '<td class="text-muted small">' + formatDateTime(row.dueAt) + '</td>' +
                '<td><span class="' + delayBadgeClass(severity) + '">' + formatDuration(row.overdueMinutes) + '</span></td>' +
                '<td class="text-center">' + rowMenu(row) + '</td>' +
                '</tr>';
        }).join('');

        body.html(html);
        if (bulkBreached) { bulkBreached.reset(); }
        $('#slb-count').text(
            rows.length === breached.length
                ? (breached.length + ' ' + (breached.length === 1 ? 'ticket' : 'tickets'))
                : ('Mostrando ' + rows.length + ' de ' + breached.length)
        );
    }

    function renderUpcoming() {
        const rows = visibleUpcoming();
        const body = $('#slb-upcoming-body');

        if (rows.length === 0) {
            $('#slb-upcoming-wrap').addClass('d-none');
            $('#slb-upcoming-empty').removeClass('d-none');
            if (bulkUpcoming) { bulkUpcoming.reset(); }
            return;
        }

        $('#slb-upcoming-wrap').removeClass('d-none');
        $('#slb-upcoming-empty').addClass('d-none');

        const html = rows.map(function (row) {
            const left = minutesUntil(row.dueAt);
            // La barra se llena según lo que QUEDA: cuanto menos queda, menos
            // barra. Menos de 6 h se considera inminente y pasa a ámbar.
            const pct = Math.max(2, Math.min(100, Math.round((left / UPCOMING_WINDOW_MIN) * 100)));
            const soon = left <= 6 * 60;
            const barClass = soon ? 'bg-warning' : 'bg-success';
            const leftClass = soon ? 'fw-semibold text-warning-emphasis' : 'text-muted';

            return '<tr>' +
                checkboxCell('upcoming', row) +
                '<td>' + ticketLink(row) + '</td>' +
                '<td>' + esc(row.subject) + '</td>' +
                '<td>' + agentCell(row) + '</td>' +
                '<td class="text-muted small">' + formatDateTime(row.dueAt) + '</td>' +
                '<td>' +
                    '<div class="progress slb-progress" style="min-width: 140px;">' +
                        '<div class="progress-bar ' + barClass + '" style="width: ' + pct + '%"></div>' +
                    '</div>' +
                    '<div class="small ' + leftClass + '">en ' + formatDuration(left) + '</div>' +
                '</td>' +
                '<td class="text-center">' + rowMenu(row) + '</td>' +
                '</tr>';
        }).join('');

        body.html(html);
        if (bulkUpcoming) { bulkUpcoming.reset(); }
        $('#slb-upcoming-count').text(
            rows.length === upcoming.length
                ? (upcoming.length + ' ' + (upcoming.length === 1 ? 'ticket' : 'tickets'))
                : ('Mostrando ' + rows.length + ' de ' + upcoming.length)
        );
    }

    function exportCsv() {
        const rows = visibleRows();

        if (rows.length === 0) {
            toastr.info('No hay nada que exportar con los filtros actuales.');
            return;
        }

        const header = ['Ticket', 'Asunto', 'Agente', 'Vencia', 'Retraso (minutos)'];
        const lines = [header].concat(rows.map(function (row) {
            return [
                row.number || ('#' + row.id),
                row.subject || '',
                row.agentId ? row.agentName : 'Sin asignar',
                row.dueAt || '',
                Math.round(row.overdueMinutes || 0)
            ];
        }));

        const csv = lines.map(function (cells) {
            return cells.map(function (cell) {
                return '"' + String(cell).replace(/"/g, '""') + '"';
            }).join(';');
        }).join('\n');

        // BOM para que Excel abra las tildes bien.
        const blob = new Blob(["﻿" + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'incumplimientos-sla.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    function renderAll(data) {
        renderKpis(data);
        renderBandCounts();
        renderAgentOptions();
        renderFilterBadge();
        renderBreached();
        renderUpcoming();
    }

    function load() {
        const colCount = bulkUrl ? 7 : 6;

        $('#state-unavailable').addClass('d-none');
        $('#state-content').removeClass('d-none');
        $('#slb-breached-body').html('<tr><td colspan="' + colCount + '" class="text-center py-5 text-muted">Cargando datos…</td></tr>');
        $('#slb-upcoming-body').html('<tr><td colspan="' + colCount + '" class="text-center py-5 text-muted">Cargando datos…</td></tr>');
        $('#slb-breached-wrap, #slb-upcoming-wrap').removeClass('d-none');
        $('#slb-breached-empty, #slb-upcoming-empty').addClass('d-none');

        $.getJSON(dataUrl).done(function (data) {
            if (!data.available) {
                $('#state-content').addClass('d-none');
                $('#state-unavailable').removeClass('d-none');
                return;
            }

            breached = flatten(data.breachedByAgent);
            upcoming = (data.upcoming || []).slice().sort(function (a, b) {
                return new Date(a.dueAt) - new Date(b.dueAt);
            });

            renderAll(data);

            $('#slb-updated').text(
                'Actualizado a las ' + new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
            );
        }).fail(function () {
            toastr.error('Error al cargar el reporte de incumplimientos SLA.');
        });
    }

    $('#btn-refresh').on('click', load);
    $('#btn-export').on('click', exportCsv);

    $('#slb-search').on('input', function () {
        renderBreached();
        renderUpcoming();
    });

    $('.select2-filter-modal').select2({ dropdownParent: $('#slb-filter-modal'), width: '100%' });

    $('#slb-filter-modal').on('show.bs.modal', syncFilterModal);

    $('#slb-filter-apply-btn').on('click', function () {
        agentFilter = $('#slb-modal-agent').val() || '';
        band = $('#slb-modal-band').val() || 'all';
        $('#slb-filter-modal').modal('hide');
        renderFilterBadge();
        renderBreached();
        renderUpcoming();
    });

    $('#slb-filter-clear-btn').on('click', function () {
        $('#slb-modal-agent').val(null).trigger('change');
        $('#slb-modal-band').val('all').trigger('change');
    });

    // ── Bulk: reasignar tickets seleccionados a un agente ──────────────────
    // Reusa el endpoint bulk de HelpdeskTickets (mismo que la bulk-bar del
    // listado de tickets), así que el payload es el suyo: ticket_ids[] +
    // action + agent_id, no {action, ids} como el resto de bulk-actions de
    // este proyecto.
    function submitBulkAssign(group, bulk) {
        const select = $('#slb-bulk-' + group + '-agent');
        const agentId = select.val();
        const ids = bulk.getIds();

        if (!ids.length) { toastr.warning('Selecciona al menos un ticket.'); return; }
        if (!agentId) { toastr.warning('Selecciona un agente.'); return; }

        const btn = $('#slb-bulk-' + group + '-apply-btn');
        btn.prop('disabled', true).text('Reasignando...');

        $.ajax({
            url: bulkUrl,
            method: 'POST',
            data: JSON.stringify({
                ticket_ids: ids.map(Number),
                action: 'assign',
                agent_id: Number(agentId),
            }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-' + group + '-modal').modal('hide');
                toastr.success(res.message);
                load();
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al reasignar los tickets.');
            },
            complete: function () {
                btn.prop('disabled', false).text('Reasignar');
            },
        });
    }

    if (bulkUrl) {
        bulkBreached = window.BulkActions.init({
            checkbox: '.bulk-checkbox-breached',
            toolbar: '#bulk-toolbar-breached',
            selectAll: '#slb-select-all-breached',
        });
        bulkUpcoming = window.BulkActions.init({
            checkbox: '.bulk-checkbox-upcoming',
            toolbar: '#bulk-toolbar-upcoming',
            selectAll: '#slb-select-all-upcoming',
        });

        $('#slb-bulk-breached-agent').select2({ dropdownParent: $('#bulk-breached-modal'), width: '100%' });
        $('#slb-bulk-upcoming-agent').select2({ dropdownParent: $('#bulk-upcoming-modal'), width: '100%' });

        $('#slb-bulk-breached-apply-btn').on('click', function () { submitBulkAssign('breached', bulkBreached); });
        $('#slb-bulk-upcoming-apply-btn').on('click', function () { submitBulkAssign('upcoming', bulkUpcoming); });

        $('#bulk-breached-modal, #bulk-upcoming-modal').on('hide.bs.modal', function () {
            $(this).find('select').val(null).trigger('change');
            $(this).find('button[id$="-apply-btn"]').prop('disabled', false).text('Reasignar');
        });
    }

    load();
})();
</script>
@endpush
