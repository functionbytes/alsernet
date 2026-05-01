@extends('layouts.theme')

@section('title', 'Visitantes en vivo · Helpdesk')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <div>
                <h4 class="mb-1 fw-bold">
                    Visitantes en vivo
                    <span class="badge bg-primary ms-2 fs-6" id="visitor-count">--</span>
                </h4>
                <p class="text-muted mb-0">Sesiones activas en los últimos 5 minutos</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="auto-refresh-toggle" checked>
                <label class="form-check-label small text-muted" for="auto-refresh-toggle">
                    Auto-actualizar
                </label>
            </div>
            <span class="bv-live-pulse"></span>
            <small class="text-muted" id="last-updated">--</small>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small text-muted fw-semibold">Visitantes activos</span>
                        <i class="fas fa-circle-dot text-primary"></i>
                    </div>
                    <div class="h2 mb-0" id="stat-total">--</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small text-muted fw-semibold">Usuarios conocidos</span>
                        <i class="fas fa-user-check text-success"></i>
                    </div>
                    <div class="h2 mb-0" id="stat-known">--</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small text-muted fw-semibold">Anónimos</span>
                        <i class="fas fa-user-secret text-muted"></i>
                    </div>
                    <div class="h2 mb-0" id="stat-anonymous">--</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">URL visitada</th>
                            <th>Cliente</th>
                            <th>País</th>
                            <th>Dispositivo</th>
                            <th>Activo hace</th>
                        </tr>
                    </thead>
                    <tbody id="visitors-tbody">
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fas fa-spinner fa-spin me-2"></i> Cargando visitantes…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top py-2 px-3">
            <small class="text-muted">Actualización automática cada 10 segundos</small>
        </div>
    </div>

</div>
@endsection

@push('css')
<style>
    .bv-live-pulse {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #13C672;
        box-shadow: 0 0 0 0 rgba(19, 198, 114, .6);
        animation: bv-pulse 1.5s infinite;
    }
    @keyframes bv-pulse {
        0%   { box-shadow: 0 0 0 0 rgba(19, 198, 114, .6); }
        70%  { box-shadow: 0 0 0 10px rgba(19, 198, 114, 0); }
        100% { box-shadow: 0 0 0 0 rgba(19, 198, 114, 0); }
    }
    .bv-live-pulse.paused { background: #adb5bd; animation: none; }
    .bv-url-cell { max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    var refreshInterval = null;
    var dataUrl = '{{ route("manager.helpdesk.live-visitors.data") }}';

    function flagEmoji(code) {
        if (!code || code.length !== 2) { return ''; }
        var offset = 127397;
        return String.fromCodePoint(code.charCodeAt(0) + offset) +
               String.fromCodePoint(code.charCodeAt(1) + offset);
    }

    function deviceIcon(device) {
        if (!device) { return '<i class="fas fa-question text-muted"></i>'; }
        var type = (device.type || '').toLowerCase();
        if (type === 'mobile' || type === 'phone') {
            return '<i class="fas fa-mobile-screen text-muted" title="Móvil"></i>';
        }
        if (type === 'tablet') {
            return '<i class="fas fa-tablet-screen-button text-muted" title="Tablet"></i>';
        }
        return '<i class="fas fa-display text-muted" title="Escritorio"></i>';
    }

    function buildRow(s) {
        var url = s.current_url || '—';
        var urlDisplay = url.replace(/^https?:\/\/[^/]+/, '');
        if (!urlDisplay) { urlDisplay = '/'; }

        var customerCell = s.customer
            ? '<a href="{{ url("/panel/helpdesk/customers") }}/' + s.customer.id + '" class="text-decoration-none fw-semibold">' + $('<span>').text(s.customer.name).html() + '</a>'
            : '<span class="text-muted">Anónimo</span>';

        var countryCell = s.country_code
            ? '<span title="' + s.country_code.toUpperCase() + '">' + flagEmoji(s.country_code.toUpperCase()) + ' ' + s.country_code.toUpperCase() + '</span>'
            : '<span class="text-muted">—</span>';

        return '<tr>'
            + '<td class="ps-3"><div class="bv-url-cell" title="' + $('<span>').text(url).html() + '">'
            + '<a href="' + $('<span>').text(url).html() + '" target="_blank" class="text-decoration-none text-muted small">'
            + '<i class="fas fa-arrow-up-right-from-square me-1 text-muted" style="font-size:.75rem"></i>'
            + $('<span>').text(urlDisplay).html()
            + '</a></div></td>'
            + '<td>' + customerCell + '</td>'
            + '<td>' + countryCell + '</td>'
            + '<td class="text-center">' + deviceIcon(s.device) + '</td>'
            + '<td><small class="text-muted">' + (s.last_activity_at || '—') + '</small></td>'
            + '</tr>';
    }

    function loadVisitors() {
        $.get(dataUrl, function (data) {
            $('#visitor-count').text(data.total);
            $('#stat-total').text(data.total);
            $('#stat-known').text(data.known);
            $('#stat-anonymous').text(data.anonymous);
            $('#last-updated').text('Actualizado: ' + new Date().toLocaleTimeString());

            var html = '';
            if (data.sessions.length === 0) {
                html = '<tr><td colspan="5" class="text-center text-muted py-5">'
                     + '<i class="fas fa-satellite-dish me-2"></i>Sin visitantes activos en este momento'
                     + '</td></tr>';
            } else {
                data.sessions.forEach(function (s) { html += buildRow(s); });
            }
            $('#visitors-tbody').html(html);
        }).fail(function () {
            $('#last-updated').text('Error al cargar');
        });
    }

    function startAutoRefresh() {
        if (!refreshInterval) {
            refreshInterval = setInterval(loadVisitors, 10000);
        }
    }

    function stopAutoRefresh() {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }

    $('#auto-refresh-toggle').on('change', function () {
        if ($(this).is(':checked')) {
            $('.bv-live-pulse').removeClass('paused');
            startAutoRefresh();
        } else {
            $('.bv-live-pulse').addClass('paused');
            stopAutoRefresh();
        }
    });

    loadVisitors();
    startAutoRefresh();
});
</script>
@endpush
