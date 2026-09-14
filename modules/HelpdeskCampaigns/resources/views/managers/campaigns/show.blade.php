@extends('layouts.theme')

@section('title', $campaign->name . ' — Campañas')

@section('page_header')
    @include('core::components.card', ['title' => 'Detalle de campana'])
@endsection

@section('content')

    @include('core::components.alerts')

    {{-- Campaign header --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="fw-bold mb-1">{{ $campaign->name }}</h5>
                    @if($campaign->description)
                        <p class="text-muted mb-2 small">{{ $campaign->description }}</p>
                    @endif
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-{{ $campaign->status_color }}-subtle text-{{ $campaign->status_color }}">
                            {{ $campaign->status_label }}
                        </span>
                        <span class="badge bg-light text-dark border">{{ $campaign->type_label }}</span>
                        @if($campaign->published_at)
                            <span class="badge bg-light text-dark border">
                                Publicado: {{ $campaign->published_at->format('d/m/Y H:i') }}
                            </span>
                        @endif
                        @if($campaign->ends_at)
                            <span class="badge bg-light text-dark border">
                                Finaliza: {{ $campaign->ends_at->format('d/m/Y H:i') }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @can('update', $campaign)
                        <a href="{{ route('helpdesk.campaigns.edit', $campaign) }}" class="btn btn-primary btn-sm">
                            Editar
                        </a>
                    @endcan
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Mas acciones
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @can('update', $campaign)
                                @if($campaign->status === 'draft' && $campaign->approval_required)
                                    <li>
                                        <form method="POST" action="{{ route('helpdesk.campaigns.submit-for-approval', $campaign) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Enviar a aprobación</button>
                                        </form>
                                    </li>
                                @elseif($campaign->status === 'draft')
                                    <li>
                                        <form method="POST" action="{{ route('helpdesk.campaigns.publish', $campaign) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Publicar</button>
                                        </form>
                                    </li>
                                @endif
                                @if($campaign->status === 'pending_approval')
                                    @can('helpdesk.campaigns.manage')
                                    <li>
                                        <form method="POST" action="{{ route('helpdesk.campaigns.approve', $campaign) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-success">✓ Aprobar</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('helpdesk.campaigns.reject', $campaign) }}" class="js-campaign-reject-form">
                                            @csrf
                                            <input type="hidden" name="reason" value="">
                                            <button type="submit" class="dropdown-item text-warning">✗ Rechazar</button>
                                        </form>
                                    </li>
                                    @endcan
                                @endif
                                @if($campaign->status === 'active')
                                    <li>
                                        <form method="POST" action="{{ route('helpdesk.campaigns.pause', $campaign) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Pausar</button>
                                        </form>
                                    </li>
                                @endif
                                @if($campaign->status === 'paused')
                                    <li>
                                        <form method="POST" action="{{ route('helpdesk.campaigns.resume', $campaign) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Reanudar</button>
                                        </form>
                                    </li>
                                @endif
                                @if(in_array($campaign->status, ['active', 'paused']))
                                    <li>
                                        <form method="POST" action="{{ route('helpdesk.campaigns.end', $campaign) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Finalizar</button>
                                        </form>
                                    </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                            @endcan
                            @can('create', Modules\HelpdeskCampaigns\Models\Campaign::class)
                                <li>
                                    <form method="POST" action="{{ route('helpdesk.campaigns.duplicate', $campaign) }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Duplicar</button>
                                    </form>
                                </li>
                            @endcan
                            <li>
                                <a class="dropdown-item" href="{{ route('helpdesk.campaigns.statistics.export', $campaign) }}">
                                    <i class="fas fa-file-csv me-1"></i> Exportar CSV
                                </a>
                            </li>
                            @can('delete', $campaign)
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item delete-btn" href="#"
                                       data-bs-toggle="modal"
                                       data-bs-target="#delete-modal"
                                       data-url="{{ route('helpdesk.campaigns.destroy', $campaign) }}"
                                       data-title="Eliminar campaña: {{ $campaign->name }}">
                                        Eliminar
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">Impresiones</h6>
                    <h4 class="mb-1 fw-bold">{{ number_format($stats['total_impressions']) }}</h4>
                    <small class="text-muted">Total vistas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">Clics</h6>
                    <h4 class="mb-1 fw-bold">{{ number_format($stats['total_clicks']) }}</h4>
                    <small class="text-muted">Total interacciones</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">CTR</h6>
                    <h4 class="mb-1 fw-bold">{{ $stats['ctr'] }}%</h4>
                    <small class="text-muted">Tasa de conversion</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">Promedio diario</h6>
                    <h4 class="mb-1 fw-bold">{{ number_format($stats['daily_avg']) }}</h4>
                    <small class="text-muted">Impresiones por dia</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- Campaign details --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header border-bottom p-3">
                    <h6 class="mb-0 fw-bold">Informacion de la campana</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted fw-normal">Estado</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $campaign->status_color }}-subtle text-{{ $campaign->status_color }}">
                                {{ $campaign->status_label }}
                            </span>
                        </dd>
                        <dt class="col-5 text-muted fw-normal">Tipo</dt>
                        <dd class="col-7">{{ $campaign->type_label }}</dd>
                        <dt class="col-5 text-muted fw-normal">Creada</dt>
                        <dd class="col-7">{{ $campaign->created_at->format('d/m/Y H:i') }}</dd>
                        @if($campaign->published_at)
                            <dt class="col-5 text-muted fw-normal">Publicada</dt>
                            <dd class="col-7">{{ $campaign->published_at->format('d/m/Y H:i') }}</dd>
                            <dt class="col-5 text-muted fw-normal">Dias activos</dt>
                            <dd class="col-7">{{ $stats['days_active'] }} dias</dd>
                        @endif
                        @if($campaign->ends_at)
                            <dt class="col-5 text-muted fw-normal">Finaliza</dt>
                            <dd class="col-7">{{ $campaign->ends_at->format('d/m/Y H:i') }}</dd>
                        @endif
                        <dt class="col-5 text-muted fw-normal">Bloques</dt>
                        <dd class="col-7">{{ $campaign->content_blocks_count }}</dd>
                        @if($campaign->conditions)
                            <dt class="col-5 text-muted fw-normal">Condiciones</dt>
                            <dd class="col-7">{{ count($campaign->conditions) }} reglas</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Performance --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header border-bottom p-3">
                    <h6 class="mb-0 fw-bold">Rendimiento</h6>
                </div>
                <div class="card-body">
                    @if($stats['total_impressions'] > 0)
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Tasa de conversion</span>
                                <span class="fw-bold">{{ $stats['ctr'] }}%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: {{ min($stats['ctr'], 100) }}%"
                                     aria-valuenow="{{ $stats['ctr'] }}"
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="small text-muted mb-1">Impresiones totales</div>
                                    <h5 class="mb-0 fw-bold">{{ number_format($stats['total_impressions']) }}</h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="small text-muted mb-1">Clics totales</div>
                                    <h5 class="mb-0 fw-bold">{{ number_format($stats['total_clicks']) }}</h5>
                                </div>
                            </div>
                        </div>
                        @if($campaign->published_at)
                            <p class="mb-0 small text-muted mt-3">
                                Promedio de {{ number_format($stats['daily_avg']) }} impresiones por dia durante {{ $stats['days_active'] }} dias activos.
                            </p>
                        @endif
                    @else
                        <div class="alert alert-info mb-0">
                            Esta campana aun no tiene impresiones registradas.
                            @if($campaign->status === 'draft')
                                Publicala para comenzar a recopilar datos.
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Tabs: Chart + Activity --}}
    <div class="card mt-3">
        <div class="card-header border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs px-3 pt-2" id="campaign-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-chart" type="button">
                        <i class="fas fa-chart-line me-1"></i> Tendencia
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-activity" type="button">
                        <i class="fas fa-history me-1"></i> Actividad
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-chart">
                    <div style="position: relative; height: 320px;">
                        <canvas id="campaign-chart"></canvas>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-activity">
                    <div id="campaign-activity-list" class="list-group list-group-flush">
                        <div class="text-center text-muted py-4">Cargando actividad…</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success(@json(session('success')), 'Exito');
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')), 'Error');
    @endif

    $(document).on('submit', '.js-campaign-reject-form', function (e) {
        var reason = window.prompt('Indica el motivo del rechazo:');
        if (reason === null || reason.trim() === '') {
            e.preventDefault();
            return;
        }
        $(this).find('input[name="reason"]').val(reason.trim());
    });

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Load timeline chart
    $.getJSON('{{ route('helpdesk.campaigns.statistics.timeline', $campaign) }}?days=30', function(data) {
        const ctx = document.getElementById('campaign-chart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Impresiones',
                        data: data.impressions,
                        borderColor: '#90bb13',
                        backgroundColor: 'rgba(177, 1, 0, 0.1)',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Clics',
                        data: data.clicks,
                        borderColor: '#13C672',
                        backgroundColor: 'rgba(19, 198, 114, 0.1)',
                        tension: 0.3,
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    });

    // Lazy-load activity tab
    let activityLoaded = false;
    $('button[data-bs-target="#tab-activity"]').on('shown.bs.tab', function() {
        if (activityLoaded) return;
        activityLoaded = true;
        $.getJSON('{{ route('helpdesk.campaigns.activity', $campaign) }}', function(res) {
            const list = $('#campaign-activity-list').empty();
            if (!res.data || res.data.length === 0) {
                list.html('<div class="text-center text-muted py-4">Sin actividad registrada.</div>');
                return;
            }
            res.data.forEach(item => {
                const html = `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong>${item.description}</strong>
                            <small class="text-muted">${item.time_ago}</small>
                        </div>
                        <small class="text-muted">${item.causer}</small>
                    </div>`;
                list.append(html);
            });
        });
    });
});
</script>
@endpush
