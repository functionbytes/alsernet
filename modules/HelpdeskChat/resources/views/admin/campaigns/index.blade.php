@extends('layouts.admin')

@section('title', 'Campañas')

@push('styles')
<style>
    :root {
        --primary: #5D87FF;
        --primary-dark: #3E5BDB;
        --success: #13C672;
        --danger: #FA896B;
        --warning: #FEC90F;
        --info: #5DADE2;
        --light-bg: #f8f9fa;
        --card-border: #e0e0e0;
    }

    .campaigns-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(93, 135, 255, 0.2);
    }

    .campaigns-header h2 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .campaigns-header p {
        opacity: 0.95;
        margin: 0;
        font-size: 0.95rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border: 1px solid var(--card-border);
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.2s ease;
    }

    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .stat-card h6 {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #999;
        margin-bottom: 0.5rem;
    }

    .stat-card h4 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 0.25rem;
    }

    .stat-card p {
        font-size: 0.85rem;
        color: #999;
        margin: 0;
    }

    .card-header-custom {
        padding: 1.5rem;
        border-bottom: 1px solid var(--card-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
    }

    .card-header-custom h5 {
        margin: 0;
        font-weight: 700;
        color: #333;
    }

    .btn-primary-custom {
        background: var(--primary);
        border: none;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s ease;
        text-decoration: none;
        color: white;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary-custom:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(93, 135, 255, 0.3);
        color: white;
    }

    .campaign-card {
        background: white;
        border: 1px solid var(--card-border);
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .campaign-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-color: var(--primary);
    }
</style>
@endpush

@section('content')

<div class="container-fluid">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('manager.dashboard') }}">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Campañas</li>
        </ol>
    </nav>

    {{-- Header --}}
    <div class="campaigns-header">
        <h2>
            <i class="fas fa-bullhorn me-2"></i>
            Campañas
        </h2>
        <p>Gestiona tus campañas de marketing para el chat en vivo</p>
    </div>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <h6 class="text-primary">Total</h6>
            <h4>{{ $campaigns->total() }}</h4>
            <p>Campañas creadas</p>
        </div>
        <div class="stat-card">
            <h6 class="text-success">Activas</h6>
            <h4 class="text-success">{{ $campaigns->where('status', 'active')->count() }}</h4>
            <p>En ejecución</p>
        </div>
        <div class="stat-card">
            <h6 class="text-warning">Borradores</h6>
            <h4 class="text-warning">{{ $campaigns->where('status', 'draft')->count() }}</h4>
            <p>Sin publicar</p>
        </div>
        <div class="stat-card">
            <h6 class="text-info">Impresiones</h6>
            <h4 class="text-info">{{ number_format($campaigns->sum('impressions_count')) }}</h4>
            <p>Total vistas</p>
        </div>
    </div>

    <div class="widget-content searchable-container list">

        @include('admin.components.alerts')

        <!-- Main Card -->
        <div class="card" style="border-radius: 12px; border: 1px solid #e0e0e0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
            <!-- Header Section -->
            <div class="card-header-custom">
                <div>
                    <h5>Campañas disponibles</h5>
                </div>
                <div class="d-flex gap-2">
                    @if(request()->hasAny(['search', 'status', 'type']))
                        <a href="{{ route('manager.helpdesk.campaigns.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i> Limpiar filtros
                        </a>
                    @endif
                    @can('create', App\Models\Helpdesk\Campaign::class)
                        <a href="{{ route('manager.helpdesk.campaigns.create') }}" class="btn-primary-custom">
                            <i class="fas fa-plus"></i> Nueva campaña
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card-body" style="background: #f8f9fa;">
                <form method="GET" action="{{ route('manager.helpdesk.campaigns.index') }}">
                    <div class="row align-items-end g-2">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-color: #e0e0e0;">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0" placeholder="Buscar campañas..." value="{{ $filters['search'] ?? '' }}" style="border-color: #e0e0e0;">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select" style="border-color: #e0e0e0;">
                                <option value="">Todos los estados</option>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="type" class="form-select">
                                <option value="">Todos los tipos</option>
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['type'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                Buscar
                            </button>
                        </div>
                        <div class="col-md-2">
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="view" id="view-grid" value="grid" checked>
                                <label class="btn btn-outline-secondary" for="view-grid">
                                    <i class="fas fa-th"></i>
                                </label>

                                <input type="radio" class="btn-check" name="view" id="view-list" value="list">
                                <label class="btn btn-outline-secondary" for="view-list">
                                    <i class="fas fa-list"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Campaigns List/Grid -->
            <div class="card-body">
                @if($campaigns->count() > 0)
                    <div class="row" id="campaigns-grid">
                        @foreach($campaigns as $campaign)
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 campaign-card">
                                    {{-- Preview Header --}}
                                    <div class="campaign-preview" style="height: 120px; background: {{ $campaign->appearance['background_color'] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }}; border-radius: 8px 8px 0 0; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                        @if($campaign->type === 'popup')
                                            <i class="far fa-window-maximize"></i>
                                        @elseif($campaign->type === 'banner')
                                            <i class="fas fa-rectangle-ad"></i>
                                        @elseif($campaign->type === 'slide-in')
                                            <i class="fas fa-sidebar"></i>
                                        @else
                                            <i class="fas fa-layer-group"></i>
                                        @endif
                                    </div>

                                    <div class="card-body">
                                        {{-- Title and Status --}}
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0">
                                                <a href="{{ route('manager.helpdesk.campaigns.edit', $campaign) }}" class="text-dark text-decoration-none">
                                                    {{ Str::limit($campaign->name, 30) }}
                                                </a>
                                            </h5>
                                            <span class="badge bg-{{ $campaign->status_color }}-subtle text-{{ $campaign->status_color }}">
                                                {{ $campaign->status_label }}
                                            </span>
                                        </div>

                                        @if($campaign->description)
                                            <p class="text-muted small mb-3">{{ Str::limit($campaign->description, 80) }}</p>
                                        @endif

                                        {{-- Type Badge --}}
                                        <div class="mb-3">
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="fas fa-layer-group"></i> {{ $campaign->type_label }}
                                            </span>
                                        </div>

                                        {{-- Stats --}}
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <div class="text-center p-2 bg-light rounded">
                                                    <div class="small text-muted">Impresiones</div>
                                                    <div class="fw-bold">{{ number_format($campaign->impressions_count) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-center p-2 bg-light rounded">
                                                    <div class="small text-muted">CTR</div>
                                                    <div class="fw-bold">
                                                        @if($campaign->impressions_count > 0)
                                                            {{ $campaign->ctr }}%
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Metadata --}}
                                        <div class="small text-muted mb-3">
                                            <div><i class="far fa-calendar"></i> Creada: {{ $campaign->created_at->format('d/m/Y') }}</div>
                                            @if($campaign->published_at)
                                                <div><i class="fas fa-rocket"></i> Publicada: {{ $campaign->published_at->format('d/m/Y') }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="card-footer bg-white border-top">
                                        <div class="d-flex gap-2">
                                            @can('update', $campaign)
                                                <a href="{{ route('manager.helpdesk.campaigns.edit', $campaign) }}" class="btn btn-sm btn-primary flex-fill">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                            @endcan

                                            <a href="{{ route('manager.helpdesk.campaigns.show', $campaign) }}" class="btn btn-sm btn-light flex-fill">
                                                <i class="fas fa-chart-bar"></i> Stats
                                            </a>

                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if($campaign->status === 'draft')
                                                        <li>
                                                            <form method="POST" action="{{ route('manager.helpdesk.campaigns.publish', $campaign) }}" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="fas fa-rocket"></i> Publicar
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @elseif($campaign->status === 'active')
                                                        <li>
                                                            <form method="POST" action="{{ route('manager.helpdesk.campaigns.pause', $campaign) }}" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="fas fa-pause"></i> Pausar
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @elseif($campaign->status === 'paused')
                                                        <li>
                                                            <form method="POST" action="{{ route('manager.helpdesk.campaigns.resume', $campaign) }}" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="fas fa-play"></i> Reanudar
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endif

                                                    <li>
                                                        <form method="POST" action="{{ route('manager.helpdesk.campaigns.duplicate', $campaign) }}" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="far fa-copy"></i> Duplicar
                                                            </button>
                                                        </form>
                                                    </li>

                                                    <li><hr class="dropdown-divider"></li>

                                                    @can('delete', $campaign)
                                                        <li>
                                                            <form method="POST" action="{{ route('manager.helpdesk.campaigns.destroy', $campaign) }}" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger" onclick="return confirm('¿Eliminar esta campaña?')">
                                                                    <i class="fas fa-trash"></i> Eliminar
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
                        @endforeach
                    </div>

                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-bullhorn fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay campañas para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request()->hasAny(['search', 'status', 'type']))
                                    No se encontraron campañas con los filtros aplicados
                                @else
                                    Crea tu primera campaña para comenzar a interactuar con tus visitantes
                                @endif
                            </p>
                            @if(!request()->hasAny(['search', 'status', 'type']))
                                @can('create', App\Models\Helpdesk\Campaign::class)
                                    <a href="{{ route('manager.helpdesk.campaigns.create') }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus"></i> Crear Primera Campaña
                                    </a>
                                @endcan
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($campaigns->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $campaigns->firstItem() }}</strong> a <strong>{{ $campaigns->lastItem() }}</strong>
                            de <strong>{{ $campaigns->total() }}</strong> campañas
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $campaigns->withQueryString()->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<style>
    .campaign-card {
        transition: all 0.3s ease;
        border: 1px solid var(--bs-border-color);
    }

    .campaign-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .stat-card {
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
</style>

<script>
$(document).ready(function() {
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // View toggle (optional enhancement)
    $('input[name="view"]').on('change', function() {
        const view = $(this).val();
        if (view === 'list') {
            $('#campaigns-grid').removeClass('row').addClass('list-view');
            $('#campaigns-grid > div').removeClass('col-md-4').addClass('col-12');
        } else {
            $('#campaigns-grid').removeClass('list-view').addClass('row');
            $('#campaigns-grid > div').removeClass('col-12').addClass('col-md-4');
        }
    });
});
</script>
@endpush
