@extends('layouts.theme')

@section('title', $campaign->name . ' - Campañas')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <h4 class="fw-semibold mb-3">Detalles de Campaña</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('manager.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manager.helpdesk.campaigns.index') }}">Campañas</a></li>
                    <li class="breadcrumb-item active">{{ $campaign->name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('core::components.alerts')

    {{-- Campaign Header Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h4 class="fw-bold mb-2">{{ $campaign->name }}</h4>
                    @if($campaign->description)
                        <p class="text-muted mb-3">{{ $campaign->description }}</p>
                    @endif
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-{{ $campaign->status_color }} fs-6">
                            {{ $campaign->status_label }}
                        </span>
                        <span class="badge bg-light-primary fs-6">
                            {{ $campaign->type_label }}
                        </span>
                        @if($campaign->published_at)
                            <span class="badge bg-light text-dark fs-6">
                                <i class="fa fa-calendar me-1"></i>
                                Publicado: {{ $campaign->published_at->format('d/m/Y H:i') }}
                            </span>
                        @endif
                        @if($campaign->ends_at)
                            <span class="badge bg-light text-dark fs-6">
                                <i class="fa fa-flag-checkered me-1"></i>
                                Finaliza: {{ $campaign->ends_at->format('d/m/Y H:i') }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @can('update', $campaign)
                        <a href="{{ route('manager.helpdesk.campaigns.edit', $campaign) }}" class="btn btn-primary">
                            <i class="fa fa-pen me-1"></i> Editar
                        </a>
                    @endcan
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fa fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @can('update', $campaign)
                                @if($campaign->status === 'draft')
                                    <li>
                                        <form method="POST" action="{{ route('manager.helpdesk.campaigns.publish', $campaign) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-success">
                                                <i class="fa fa-rocket me-2"></i> Publicar
                                            </button>
                                        </form>
                                    </li>
                                @endif

                                @if($campaign->status === 'active')
                                    <li>
                                        <form method="POST" action="{{ route('manager.helpdesk.campaigns.pause', $campaign) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-warning">
                                                <i class="fa fa-pause me-2"></i> Pausar
                                            </button>
                                        </form>
                                    </li>
                                @endif

                                @if($campaign->status === 'paused')
                                    <li>
                                        <form method="POST" action="{{ route('manager.helpdesk.campaigns.resume', $campaign) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-success">
                                                <i class="fa fa-play me-2"></i> Reanudar
                                            </button>
                                        </form>
                                    </li>
                                @endif

                                @if(in_array($campaign->status, ['active', 'paused']))
                                    <li>
                                        <form method="POST" action="{{ route('manager.helpdesk.campaigns.end', $campaign) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger"
                                                    onclick="return confirm('¿Finalizar esta campaña?');">
                                                <i class="fa fa-stop me-2"></i> Finalizar
                                            </button>
                                        </form>
                                    </li>
                                @endif

                                <li><hr class="dropdown-divider"></li>
                            @endcan

                            @can('create', App\Models\Helpdesk\Campaign::class)
                                <li>
                                    <form method="POST" action="{{ route('manager.helpdesk.campaigns.duplicate', $campaign) }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fa fa-copy me-2"></i> Duplicar
                                        </button>
                                    </form>
                                </li>
                            @endcan

                            @can('delete', $campaign)
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('manager.helpdesk.campaigns.destroy', $campaign) }}"
                                          onsubmit="return confirm('¿Eliminar esta campaña?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fa fa-trash me-2"></i> Eliminar
                                        </button>
                                    </form>
                                </li>
                            @endcan
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-start border-primary border-3">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 48px; height: 48px; background-color: rgba(13, 110, 253, 0.1);">
                                <i class="fa fa-eye fs-5 text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Impresiones</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($stats['total_impressions']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-start border-success border-3">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 48px; height: 48px; background-color: rgba(25, 135, 84, 0.1);">
                                <i class="fa fa-mouse-pointer fs-5 text-success"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Clics</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($stats['total_clicks']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-start border-warning border-3">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 48px; height: 48px; background-color: rgba(255, 193, 7, 0.1);">
                                <i class="fa fa-percentage fs-5 text-warning"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">CTR</h6>
                            <h3 class="mb-0 fw-bold">{{ $stats['ctr'] }}%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-start border-info border-3">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 48px; height: 48px; background-color: rgba(13, 202, 240, 0.1);">
                                <i class="fa fa-chart-line fs-5 text-info"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Promedio Diario</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($stats['daily_avg']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Campaign Details --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Información de la Campaña</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td class="fw-semibold" style="width: 40%;">Estado:</td>
                                <td>
                                    <span class="badge bg-{{ $campaign->status_color }}">
                                        {{ $campaign->status_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Tipo:</td>
                                <td>{{ $campaign->type_label }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Creado:</td>
                                <td>{{ $campaign->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @if($campaign->published_at)
                                <tr>
                                    <td class="fw-semibold">Publicado:</td>
                                    <td>{{ $campaign->published_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Días Activos:</td>
                                    <td>{{ $stats['days_active'] }} días</td>
                                </tr>
                            @endif
                            @if($campaign->ends_at)
                                <tr>
                                    <td class="fw-semibold">Finaliza:</td>
                                    <td>{{ $campaign->ends_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="fw-semibold">Bloques de Contenido:</td>
                                <td>{{ $campaign->content_blocks_count }}</td>
                            </tr>
                            @if($campaign->conditions)
                                <tr>
                                    <td class="fw-semibold">Condiciones:</td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ count($campaign->conditions) }} reglas
                                        </span>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Campaign Performance --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Rendimiento</h5>
                </div>
                <div class="card-body">
                    @if($stats['total_impressions'] > 0)
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tasa de Conversión</span>
                                <span class="fw-bold">{{ $stats['ctr'] }}%</span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: {{ min($stats['ctr'], 100) }}%"
                                     aria-valuenow="{{ $stats['ctr'] }}"
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>

                        <div class="row text-center g-3">
                            <div class="col-6">
                                <div class="p-3 rounded" style="background-color: #f8f9fa;">
                                    <h6 class="text-muted mb-1">Impresiones Totales</h6>
                                    <h4 class="mb-0 fw-bold text-primary">{{ number_format($stats['total_impressions']) }}</h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded" style="background-color: #f8f9fa;">
                                    <h6 class="text-muted mb-1">Clics Totales</h6>
                                    <h4 class="mb-0 fw-bold text-success">{{ number_format($stats['total_clicks']) }}</h4>
                                </div>
                            </div>
                        </div>

                        @if($campaign->published_at)
                            <div class="mt-4">
                                <p class="mb-0 small text-muted">
                                    <i class="fa fa-info-circle me-1"></i>
                                    Promedio de {{ number_format($stats['daily_avg']) }} impresiones por día
                                    durante {{ $stats['days_active'] }} días activos.
                                </p>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="fa fa-circle-info me-2"></i>
                            Esta campaña aún no tiene impresiones registradas.
                            @if($campaign->status === 'draft')
                                <strong>Publícala</strong> para comenzar a recopilar datos.
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Appearance & Content Preview --}}
    @if($campaign->content || $campaign->appearance)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Contenido y Apariencia</h5>
                    </div>
                    <div class="card-body">
                        @if($campaign->appearance)
                            <div class="mb-3">
                                <h6 class="fw-semibold mb-2">Apariencia:</h6>
                                <div class="d-flex gap-2">
                                    @if(isset($campaign->appearance['background_color']))
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded" style="width: 30px; height: 30px; background-color: {{ $campaign->appearance['background_color'] }}; border: 1px solid #ddd;"></div>
                                            <small class="text-muted">Fondo: {{ $campaign->appearance['background_color'] }}</small>
                                        </div>
                                    @endif
                                    @if(isset($campaign->appearance['text_color']))
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded" style="width: 30px; height: 30px; background-color: {{ $campaign->appearance['text_color'] }}; border: 1px solid #ddd;"></div>
                                            <small class="text-muted">Texto: {{ $campaign->appearance['text_color'] }}</small>
                                        </div>
                                    @endif
                                    @if(isset($campaign->appearance['primary_color']))
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded" style="width: 30px; height: 30px; background-color: {{ $campaign->appearance['primary_color'] }}; border: 1px solid #ddd;"></div>
                                            <small class="text-muted">Primario: {{ $campaign->appearance['primary_color'] }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($campaign->content)
                            <div>
                                <h6 class="fw-semibold mb-2">Contenido:</h6>
                                <pre class="bg-light p-3 rounded"><code>{{ json_encode($campaign->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            </div>
                        @endif

                        @if($campaign->conditions)
                            <div class="mt-3">
                                <h6 class="fw-semibold mb-2">Condiciones de Segmentación:</h6>
                                <pre class="bg-light p-3 rounded"><code>{{ json_encode($campaign->conditions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
