@extends('layouts.theme')

@section('title', 'Dashboard en vivo · Helpdesk')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-bolt text-primary me-2"></i>Dashboard en vivo</h1>
            <p class="text-muted mb-0">Métricas actualizadas cada 10 segundos.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="bv-live-pulse"></span>
            <span class="text-muted small" id="bv-live-updated">--</span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small text-muted fw-semibold">Conversaciones activas</span>
                        <i class="far fa-comments text-primary"></i>
                    </div>
                    <div class="h2 mb-0" data-bv-metric="active_conversations">--</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small text-muted fw-semibold">Agentes en línea</span>
                        <i class="far fa-user text-success"></i>
                    </div>
                    <div class="h2 mb-0" data-bv-metric="agents_online">--</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small text-muted fw-semibold">Mensajes hoy</span>
                        <i class="far fa-envelope text-info"></i>
                    </div>
                    <div class="h2 mb-0" data-bv-metric="messages_today">--</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small text-muted fw-semibold">1ª respuesta avg</span>
                        <i class="far fa-clock text-warning"></i>
                    </div>
                    <div class="h2 mb-0"><span data-bv-metric="avg_first_response_seconds">--</span><small class="text-muted ms-1">s</small></div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small text-muted fw-semibold">CSAT hoy</span>
                        <i class="fas fa-star text-warning"></i>
                    </div>
                    <div class="h2 mb-0"><span data-bv-metric="csat_avg_today">--</span><small class="text-muted ms-1">/5</small></div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase small text-muted fw-semibold">Cola pendiente</span>
                        <i class="fas fa-list-check text-secondary"></i>
                    </div>
                    <div class="h2 mb-0" data-bv-metric="queue_pending_jobs">--</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-uppercase small text-muted fw-semibold mb-3">Conversaciones abiertas por canal</div>
                    <div id="bv-channel-list" class="d-flex flex-wrap gap-2">
                        <span class="text-muted">Cargando…</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('css')
<style>
    .bv-live-pulse { width:10px; height:10px; border-radius:50%; background:#13C672; box-shadow:0 0 0 0 rgba(19,198,114,.6); animation:bv-pulse 1.5s infinite; }
    @keyframes bv-pulse {
        0% { box-shadow:0 0 0 0 rgba(19,198,114,.6); }
        70% { box-shadow:0 0 0 12px rgba(19,198,114,0); }
        100% { box-shadow:0 0 0 0 rgba(19,198,114,0); }
    }
    .bv-channel-pill { display:inline-flex; align-items:center; gap:8px; padding:6px 12px; border-radius:20px; background:#f5f5fa; font-size:.875rem; }
    .bv-channel-pill .count { background:#fff; padding:1px 8px; border-radius:10px; font-weight:600; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const channelLabels = {
        whatsapp: { label: 'WhatsApp', icon: 'fab fa-whatsapp', color: '#25D366' },
        facebook: { label: 'Facebook', icon: 'fab fa-facebook-messenger', color: '#0084FF' },
        instagram: { label: 'Instagram', icon: 'fab fa-instagram', color: '#E4405F' },
        email: { label: 'Email', icon: 'far fa-envelope', color: '#6c757d' },
        widget: { label: 'Widget', icon: 'far fa-comment-dots', color: '#90bb13' },
        web: { label: 'Web', icon: 'far fa-comment-dots', color: '#90bb13' },
    };

    function renderChannels(byChannel) {
        const $container = $('#bv-channel-list').empty();
        const entries = Object.entries(byChannel || {});
        if (!entries.length) {
            $container.append('<span class="text-muted">Sin conversaciones abiertas</span>');
            return;
        }
        entries.forEach(([channel, count]) => {
            const meta = channelLabels[channel] || { label: channel, icon: 'fas fa-circle', color: '#6c757d' };
            $container.append(
                $('<span class="bv-channel-pill"></span>')
                    .append('<i class="' + meta.icon + '" style="color:' + meta.color + '"></i>')
                    .append(' ' + meta.label + ' ')
                    .append('<span class="count">' + count + '</span>')
            );
        });
    }

    function loadMetrics() {
        $.get("{{ route('manager.helpdesk.dashboard.live.metrics') }}").done(function (data) {
            $('[data-bv-metric]').each(function () {
                const key = $(this).data('bv-metric');
                if (data[key] !== undefined) {
                    $(this).text(data[key]);
                }
            });
            renderChannels(data.open_by_channel);
            $('#bv-live-updated').text('Actualizado: ' + new Date().toLocaleTimeString());
        }).fail(function () {
            $('#bv-live-updated').text('Error al cargar');
        });
    }

    loadMetrics();
    setInterval(loadMetrics, 10000);
});
</script>
@endpush
@endsection
