@extends('layouts.theme')

@section('title', $campaign->name . ' — Campañas')

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskcampaigns/css/campaigns.css') }}?v={{ @filemtime(public_path('modules/helpdeskcampaigns/css/campaigns.css')) }}">
@endpush

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
                            <div class="progress hcm-progress-thin">
                                <div class="progress-bar bg-success hcm-ctr-bar" role="progressbar"
                                     style="--hcm-ctr: {{ min($stats['ctr'], 100) }}%"
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
                    <div class="hcm-chart-box">
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
    window.HcmCampaignShow = {
        timelineUrl: '{{ route('helpdesk.campaigns.statistics.timeline', $campaign) }}',
        activityUrl: '{{ route('helpdesk.campaigns.activity', $campaign) }}',
        flashSuccess: @json(session('success')),
        flashError: @json(session('error')),
    };
</script>
<script src="{{ asset('modules/helpdeskcampaigns/js/campaign-show.js') }}?v={{ @filemtime(public_path('modules/helpdeskcampaigns/js/campaign-show.js')) }}" defer></script>
@endpush
