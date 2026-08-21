@extends('layouts.theme')

@section('title', 'Logs del Sistema de Automatización')

@section('page_header')
    @include('core::components.card', ['title' => 'Logs del Sistema de Automatización'])
@endsection

@push('styles')
    <style>
        .automation-log-search { width: 220px; }
        .automation-log-output {
            max-height: 600px;
            overflow-y: auto;
            background-color: #f5f6f8;
        }
        .automation-log-pre {
            color: #000;
            margin: 0;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            line-height: 1.6;
        }
        .automation-log-footer {
            background: #f5f6f8;
            border-top: 1px solid #f5f6f8;
        }
        .automation-log-meta { color: #6b737b; }
        .log-status-ok { color: #000; }
        .log-status-pending { color: #ffc107; }
        .log-ts { color: #a9dc76; }
        .log-ctx { color: #636e7b; }
        .log-level { font-weight: bold; }
        .log-level-error { color: #ff6b6b; }
        .log-level-warning { color: #ffd43b; }
        .log-level-info { color: #74c0fc; }
        .log-level-debug { color: #a9dc76; }
        .log-level-default { color: #adb5bd; }
        .log-highlight { background: #ffd43b44; color: #ffd43b; }
    </style>
@endpush

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Logs del sistema de automatización</h5>
                        <p class="small mb-0 text-muted">Visualiza y descarga los registros de eventos del sistema de automatización de proveedores</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.suppliers.automation.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <div class="dropdown">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a href="#" class="dropdown-item" id="refreshLogsBtn">Refrescar</a></li>
                                <li><a href="#" class="dropdown-item" id="downloadLogsBtn">Descargar logs</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a href="#" class="dropdown-item" id="clearLogsBtn">Limpiar vista</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Errores</h6>
                                <h2 id="errorCount" class="fw-bold mb-1">—</h2>
                                <small class="text-muted">Logs de tipo ERROR</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Advertencias</h6>
                                <h2 id="warningCount" class="fw-bold mb-1">—</h2>
                                <small class="text-muted">Logs de tipo WARNING</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Información</h6>
                                <h2 id="infoCount" class="fw-bold mb-1">—</h2>
                                <small class="text-muted">Logs de tipo INFO</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total de logs</h6>
                                <h2 id="totalCount" class="fw-bold mb-1">—</h2>
                                <small class="text-muted">Todos los registros</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Configuración --}}
            <div class="card-body border-bottom">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="logTypeFilter" class="form-label fw-semibold small mb-1">Tipo de log</label>
                        <select id="logTypeFilter" class="form-control select2">
                            <option value="error" selected>Errores</option>
                            <option value="warning">Advertencias</option>
                            <option value="info">Información</option>
                            <option value="all">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="logLimit" class="form-label fw-semibold small mb-1">Límite</label>
                        <select id="logLimit" class="form-control select2">
                            <option value="50">50 registros</option>
                            <option value="100" selected>100 registros</option>
                            <option value="200">200 registros</option>
                            <option value="500">500 registros</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="autoScroll" class="form-label fw-semibold small mb-1">Auto-scroll</label>
                        <select id="autoScroll" class="form-control select2">
                            <option value="1" selected>Activado</option>
                            <option value="0">Desactivado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="showLineNumbers" class="form-label fw-semibold small mb-1">Números de línea</label>
                        <select id="showLineNumbers" class="form-control select2">
                            <option value="0" selected>Ocultar</option>
                            <option value="1">Mostrar</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Salida de logs --}}
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-semibold small">
                        Salida de logs
                        <span class="text-muted fw-normal ms-1" id="logLineCount">— registros</span>
                    </span>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" class="form-control automation-log-search"
                               id="logSearch" placeholder="Buscar en logs..." autocomplete="off">
                        <small class="text-muted text-nowrap" id="logLastUpdate"></small>
                    </div>
                </div>

                <div class="rounded overflow-hidden border">
                    <div id="logsContentWrapper" class="automation-log-output">
                        <pre id="logsContent"
                             class="p-3 mb-0 automation-log-pre">Cargando logs del sistema...</pre>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 automation-log-footer">
                        <small class="automation-log-meta">
                            <i class="fas fa-circle me-1 log-status-pending" id="logStatusIcon"></i>
                            <span id="logStatusText">Cargando...</span>
                        </small>
                        <small class="automation-log-meta">Auto-refresh cada 30s</small>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    let currentLogType = 'error';
    let currentLimit   = 100;
    let currentSearch  = '';
    let rawLogs        = [];
    let autoRefreshInterval;

    const s2opts = { minimumResultsForSearch: Infinity, width: '100%' };
    $('#logTypeFilter, #logLimit, #autoScroll, #showLineNumbers').select2(s2opts);

    loadLogs();

    // ── Cambios de filtro ──────────────────────────────────────────────

    $('#logTypeFilter').on('change', function () {
        currentLogType = $(this).val();
        loadLogs();
    });

    $('#logLimit').on('change', function () {
        currentLimit = $(this).val();
        loadLogs();
    });

    $('#autoScroll').on('change', function () {
        if ($(this).val() === '1') { scrollToBottom(); }
    });

    $('#showLineNumbers').on('change', function () {
        renderLogs(rawLogs);
    });

    // ── Acciones del dropdown ──────────────────────────────────────────

    $('#refreshLogsBtn').on('click', function (e) {
        e.preventDefault();
        loadLogs();
        toastr.success('Logs actualizados', 'Sistema');
    });

    $('#clearLogsBtn').on('click', function (e) {
        e.preventDefault();
        rawLogs = [];
        renderContent('Logs limpiados. Presiona "Refrescar" para recargar.');
        setStatus('Limpiado', false);
        $('#logLineCount').text('0 registros');
    });

    $('#downloadLogsBtn').on('click', function (e) {
        e.preventDefault();
        const form = $('<form>', {
            method: 'POST',
            action: '{{ route("settings.suppliers.automation.logs.download") }}',
            html  : `<input type="hidden" name="_token" value="{{ csrf_token() }}">
                     <input type="hidden" name="type" value="${currentLogType}">`,
        });
        $('body').append(form);
        form.submit();
        form.remove();
    });

    // ── Búsqueda inline ────────────────────────────────────────────────

    $('#logSearch').on('keyup', function () {
        currentSearch = $(this).val().toLowerCase();
        renderLogs(rawLogs);
    });

    // ── Carga de logs ──────────────────────────────────────────────────

    function loadLogs() {
        renderContent('Cargando logs...');
        setStatus('Cargando...', false);

        $.ajax({
            url    : '{{ route("settings.suppliers.automation.logs.data") }}',
            method : 'GET',
            data   : { type: currentLogType, limit: currentLimit },
            success: function (res) {
                if (res.success) {
                    rawLogs = res.logs || [];
                    updateCounters(res.counts || {});
                    renderLogs(rawLogs);
                    setStatus('Actualizado', true);
                    $('#logLastUpdate').text('Última actualización: ' + new Date().toLocaleTimeString());
                } else {
                    renderContent('Sin datos disponibles.');
                    setStatus('Sin datos', false);
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message ?? 'Error de conexión';
                renderContent('Error: ' + escapeHtml(msg));
                setStatus('Error', false);
            },
        });
    }

    // ── Render ─────────────────────────────────────────────────────────

    function renderLogs(logs) {
        if (!logs.length) {
            renderContent('No se encontraron logs con los filtros aplicados.');
            $('#logLineCount').text('0 registros');
            return;
        }

        const showNumbers = $('#showLineNumbers').val() === '1';
        const search      = currentSearch;
        const filtered    = search
            ? logs.filter(l => (l.message + ' ' + l.timestamp).toLowerCase().includes(search))
            : logs;

        if (!filtered.length) {
            renderContent('Sin resultados para "' + escapeHtml(currentSearch) + '".');
            $('#logLineCount').text('0 registros');
            return;
        }

        const lines = [];
        filtered.forEach(function (log, idx) {
            const num  = showNumbers ? String(idx + 1).padStart(4, ' ') + '  ' : '';
            const ts   = log.timestamp ? `<span class="log-ts">${escapeHtml(log.timestamp)}</span>  ` : '';
            const lvl  = levelBadge(log.level);
            const msg  = highlight(escapeHtml(log.message), search);
            lines.push(num + ts + lvl + '  ' + msg);

            if (log.context) {
                const ctx = highlight(escapeHtml(log.context), search);
                lines.push((showNumbers ? '         ' : '') + `<span class="log-ctx">${ctx}</span>`);
            }
            lines.push('');
        });

        renderContent(lines.join('\n'));
        $('#logLineCount').text(filtered.length + ' registros');

        if ($('#autoScroll').val() === '1') { scrollToBottom(); }
    }

    function renderContent(html) {
        $('#logsContent').html(html);
    }

    function levelBadge(level) {
        const classes = { error: 'log-level-error', warning: 'log-level-warning', info: 'log-level-info', debug: 'log-level-debug' };
        const cls   = classes[level] || 'log-level-default';
        const label = (level || 'log').toUpperCase().padEnd(7, ' ');
        return `<span class="log-level ${cls}">${label}</span>`;
    }

    function highlight(text, search) {
        if (!search) { return text; }
        const rx = new RegExp('(' + escapeRegex(search) + ')', 'gi');
        return text.replace(rx, '<mark class="log-highlight">$1</mark>');
    }

    // ── Helpers ────────────────────────────────────────────────────────

    function updateCounters(counts) {
        $('#errorCount').text(counts.error ?? 0);
        $('#warningCount').text(counts.warning ?? 0);
        $('#infoCount').text(counts.info ?? 0);
        $('#totalCount').text(counts.total ?? 0);
    }

    function setStatus(text, ok) {
        $('#logStatusText').text(text);
        $('#logStatusIcon').toggleClass('log-status-ok', ok).toggleClass('log-status-pending', !ok);
    }

    function scrollToBottom() {
        const w = document.getElementById('logsContentWrapper');
        if (w) { w.scrollTop = w.scrollHeight; }
    }

    function escapeHtml(text) {
        if (!text) { return ''; }
        return String(text).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
    }

    function escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // ── Auto-refresh cada 30 s ─────────────────────────────────────────
    autoRefreshInterval = setInterval(loadLogs, 30000);
    $(window).on('beforeunload', function () { clearInterval(autoRefreshInterval); });

});
</script>
@endpush
