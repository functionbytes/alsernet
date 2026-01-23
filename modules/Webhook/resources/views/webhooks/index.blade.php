@extends('layouts.theme')

@section('page_title', 'Webhooks')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Webhooks',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Webhooks', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Webhooks</h5>
                        <p class="small mb-0 text-muted">Gestiona integraciones y entregas de webhooks en tiempo real</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route("webhooks.backups.integrations.index") }}" class="btn btn-secondary">
                            Ver integraciones
                        </a>
                        <a href="{{ route('webhooks.deliveries') }}" class="btn btn-outline-primary">
                            Ver entregas
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-2">¿Necesitas configurar una nueva integración?</h6>
                                <p class="mb-0">
                                    Las integraciones de webhooks te permiten recibir notificaciones en tiempo real de eventos del sistema.
                                    Configura suscripciones a eventos específicos y gestiona las entregas desde el panel de configuración.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route("webhooks.backups.integrations.index") }}" class="btn btn-info flex-shrink-0">
                            <i class="fa fa-arrow-right me-2"></i>Ver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
            @if ($errors->any())
                <div class="card-body border-bottom">
                    <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-exclamation-circle fs-4 me-2 mt-1"></i>
                            <div>
                                <h6 class="alert-heading fw-bold mb-2">Errores de validación</h6>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="card-body border-bottom">
                    <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-check fs-4 me-2"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('webhooks.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-7">
                            <label for="search" class="form-label fw-semibold">Búsqueda</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por nombre de integración..."
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="status" class="form-label fw-semibold">Estado</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
                                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendido</option>
                                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Deshabilitado</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fa fa-magnifying-glass me-2"></i>Buscar
                            </button>
                            @if (request('search') || request('status'))
                                <a href="{{ route('webhooks.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Integrations Table --}}
            @if ($integrations->count() > 0)
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="25%">Nombre</th>
                                <th width="13%">Estado</th>
                                <th width="12%">Plan</th>
                                <th width="15%">Suscripciones</th>
                                <th width="15%">Límite diario</th>
                                <th width="20%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($integrations as $integration)
                                <tr>
                                    <td>
                                        <div>
                                            <strong class="d-block">{{ $integration->name }}</strong>
                                            @if ($integration->description)
                                                <small class="text-muted d-block">{{ Str::limit($integration->description, 50) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($integration->isActive())
                                            <span class="badge bg-success-subtle text-success">
                                                Activo
                                            </span>
                                        @elseif($integration->isSuspended())
                                            <span class="badge bg-warning-subtle text-warning">
                                                Suspendido
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">
                                                Deshabilitado
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">{{ ucfirst($integration->plan) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $integration->subscriptions_count ?? $integration->subscriptions->count() }} suscripciones
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ number_format($integration->daily_limit) }} req/día</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('webhooks.show', $integration->uid) }}">
                                                        Ver detalles
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('webhooks.backups.integrations.edit', $integration->uid) }}">
                                                        Editar integración
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('webhooks.backups.subscriptions.index', ['integration_uid' => $integration->uid]) }}">
                                                        Ver suscripciones
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('webhooks.deliveries') }}?integration={{ $integration->uid }}">
                                                        Ver entregas
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fa fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay integraciones</h5>
                    <p class="text-muted mb-4">
                        @if (request('search') || request('status'))
                            No se encontraron resultados con los filtros aplicados.
                        @else
                            Comienza creando tu primera integración de webhook.
                        @endif
                    </p>
                    @if (request('search') || request('status'))
                        <a href="{{ route('webhooks.index') }}" class="btn btn-secondary">
                            Ver todas
                        </a>
                    @else
                        <a href="{{ route('webhooks.backups.integrations.create') }}" class="btn btn-primary">
                            + Crear ahora
                        </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Pagination --}}
            @if($integrations->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $integrations->firstItem() }} - {{ $integrations->lastItem() }} de {{ $integrations->total() }} integraciones
                        </div>
                        <div>
                            {{ $integrations->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>

@endsection
