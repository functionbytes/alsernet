@extends('layouts.theme')

@section('page_title', 'Endpoints ERP')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Endpoints ERP',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'ERP', 'url' => route('settings.erp.index')],
            ['label' => 'Endpoints', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Endpoints ERP</h5>
                        <p class="small mb-0 text-muted">Gestiona las conexiones API para el sistema ERP</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.erp.index') }}" class="btn btn-secondary">
                            Volver a configuración
                        </a>
                        <a href="{{ route('settings.erp.endpoints.create') }}" class="btn btn-primary">
                            Nuevo endpoint
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">¿Qué son los endpoints ERP?</h6>
                            <p class="mb-0">
                                Los endpoints permiten conectar con APIs externas para sincronizar datos del ERP.
                                Cada endpoint puede tener credenciales específicas y configuración de timeout, reintentos y headers personalizados.
                            </p>
                        </div>
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
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="card-body border-bottom">
                    <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-exclamation-triangle fs-4 me-2"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            {{-- Tabs Section --}}
            <div class="card-body border-bottom p-0">
                <ul class="nav nav-tabs nav-fill" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="{{ route('settings.erp.endpoints.index', ['tab' => 'configured']) }}"
                           class="nav-link {{ ($tab ?? 'configured') === 'configured' ? 'active' : '' }} fw-semibold"
                           id="configured-tab"
                           role="tab"
                           aria-controls="configured-panel"
                           aria-selected="{{ ($tab ?? 'configured') === 'configured' ? 'true' : 'false' }}">
                           Configurados
                            <span class="badge bg-info ms-2">{{ $configuredCount ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="{{ route('settings.erp.endpoints.index', ['tab' => 'available']) }}"
                           class="nav-link {{ ($tab ?? 'configured') === 'available' ? 'active' : '' }} fw-semibold"
                           id="available-tab"
                           role="tab"
                           aria-controls="available-panel"
                           aria-selected="{{ ($tab ?? 'configured') === 'available' ? 'true' : 'false' }}">
                            Disponibles
                            <span class="badge bg-info ms-2">{{ $availableCount ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="{{ route('settings.erp.endpoints.index', ['tab' => 'deprecated']) }}"
                           class="nav-link {{ ($tab ?? 'configured') === 'deprecated' ? 'active' : '' }} fw-semibold"
                           id="deprecated-tab"
                           role="tab"
                           aria-controls="deprecated-panel"
                           aria-selected="{{ ($tab ?? 'configured') === 'deprecated' ? 'true' : 'false' }}">
                            Obsoleto
                            <span class="badge bg-warning ms-2">{{ $deprecatedCount ?? 0 }}</span>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.erp.endpoints.index') }}">
                    <input type="hidden" name="tab" value="{{ $tab ?? 'configured' }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6">
                            <label for="search" class="form-label fw-semibold">Búsqueda</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por nombre, slug o URL..."
                                   value="{{ $search ?? '' }}">
                        </div>

                        @if(($tab ?? 'configured') === 'configured')
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="status" class="form-label fw-semibold">Estado</label>
                            <select id="status" name="status" class="form-control select2">
                                <option value="">Todos los estados</option>
                                <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Activos</option>
                                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                        </div>
                        @endif

                        <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fa fa-magnifying-glass me-2"></i>Buscar
                            </button>
                            @if (($search ?? null) || ($status ?? null))
                                <a href="{{ route('settings.erp.endpoints.index', ['tab' => $tab ?? 'configured']) }}" class="btn btn-secondary">
                                    <i class="fa fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <div class="tab-content">
                {{-- Configured Endpoints Tab --}}
                <div class="tab-pane fade {{ ($tab ?? 'configured') === 'configured' ? 'show active' : '' }}" id="configured-panel" role="tabpanel" aria-labelledby="configured-tab">
                    @if ($endpoints->count() > 0)
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Endpoint</th>
                                        <th width="25%">URL</th>
                                        <th width="8%">Método</th>
                                        <th width="10%">Estado</th>
                                        <th width="12%">Credenciales</th>
                                        <th width="10%" class="text-center">Estadísticas</th>
                                        <th width="15%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($endpoints as $endpoint)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong class="d-block">{{ $endpoint->name }}</strong>
                                                    <small class="text-muted font-monospace d-block">{{ $endpoint->slug }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <code class="small">{{ Str::limit($endpoint->url, 40) }}</code>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info">{{ $endpoint->method }}</span>
                                            </td>
                                            <td>
                                                @if($endpoint->is_active)
                                                    <span class="badge bg-success-subtle text-success">
                                                        Activo
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">
                                                        Inactivo
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($endpoint->credential)
                                                    <span class="badge bg-success-subtle text-success">
                                                        <i class="fa fa-key"></i> {{ ucfirst($endpoint->credential->auth_type) }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        Sin credenciales
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="small">
                                                    <div>Llamadas: <strong>{{ $endpoint->logs->count() }}</strong></div>
                                                    <div>Éxito: <strong>{{ number_format($endpoint->success_rate, 1) }}%</strong></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa fa-ellipsis"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('settings.erp.endpoints.show', $endpoint) }}">
                                                                Ver detalles
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('settings.erp.endpoints.edit', $endpoint) }}">
                                                                Editar endpoint
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('settings.erp.endpoints.credentials.index', $endpoint) }}">
                                                                Gestionar credenciales
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form action="{{ route('settings.erp.endpoints.test', $endpoint) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item text-primary">
                                                                    Probar conexión
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('settings.erp.endpoints.toggle', $endpoint) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">
                                                                    {{ $endpoint->is_active ? 'Desactivar' : 'Activar' }}
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item text-danger delete-endpoint"
                                                                    data-id="{{ $endpoint->id }}"
                                                                    data-name="{{ $endpoint->name }}">
                                                                Eliminar endpoint
                                                            </button>
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
                            <h5 class="fw-bold mb-2">No hay endpoints configurados</h5>
                            <p class="text-muted mb-4">
                                @if (($search ?? null) || ($status ?? null))
                                    No se encontraron resultados con los filtros aplicados.
                                @else
                                    Todos los endpoints auto-descubiertos aparecen en la pestaña "Disponibles".
                                @endif
                            </p>
                            @if (($search ?? null) || ($status ?? null))
                                <a href="{{ route('settings.erp.endpoints.index', ['tab' => 'configured']) }}" class="btn btn-secondary">
                                    Ver todos
                                </a>
                            @else
                                <a href="{{ route('settings.erp.endpoints.index', ['tab' => 'available']) }}" class="btn btn-primary">
                                    <i class="fa fa-arrow-right me-2"></i>Ver endpoints disponibles
                                </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Pagination --}}
                    @if($endpoints->hasPages())
                        <div class="card-footer bg-white border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Mostrando {{ $endpoints->firstItem() }} - {{ $endpoints->lastItem() }} de {{ $endpoints->total() }} endpoints
                                </div>
                                <div>
                                    {{ $endpoints->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Available Endpoints Tab --}}
                <div class="tab-pane fade {{ ($tab ?? 'configured') === 'available' ? 'show active' : '' }}" id="available-panel" role="tabpanel" aria-labelledby="available-tab">
                    @if ($availableEndpoints && $availableEndpoints->count() > 0)
                    <div class="card-body">
                        <div class="alert alert-info border-0 mb-4" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="fa fa-lightbulb fs-5 me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-2">Endpoints auto-descubiertos</h6>
                                    <p class="mb-0 small">
                                        Estos endpoints fueron detectados automáticamente desde las rutas de la API.
                                        Puedes configurarlos asignando credenciales y configuración de timeout.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Endpoint</th>
                                        <th width="20%">Controlador</th>
                                        <th width="15%">Método</th>
                                        <th width="15%">URL</th>
                                        <th width="15%">Estado</th>
                                        <th width="15%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($availableEndpoints as $endpoint)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong class="d-block">{{ $endpoint->name }}</strong>
                                                    <small class="text-muted font-monospace d-block">{{ $endpoint->slug }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <code class="small">{{ Str::limit($endpoint->controller ?? '-', 30) }}</code>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info">{{ $endpoint->method }}</span>
                                            </td>
                                            <td>
                                                <code class="small">{{ Str::limit($endpoint->url, 30) }}</code>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info">
                                                    No configurado
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa fa-ellipsis"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('settings.erp.endpoints.show', $endpoint) }}">
                                                                Ver detalles
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('settings.erp.endpoints.edit', $endpoint) }}">
                                                                Configurar
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
                            <i class="fa fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                            <h5 class="fw-bold mb-2">Todos los endpoints están configurados</h5>
                            <p class="text-muted mb-4">Excelente, has configurado todos los endpoints disponibles.</p>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Deprecated Endpoints Tab --}}
                <div class="tab-pane fade {{ ($tab ?? 'configured') === 'deprecated' ? 'show active' : '' }}" id="deprecated-panel" role="tabpanel" aria-labelledby="deprecated-tab">
                    @if ($deprecatedEndpoints && $deprecatedEndpoints->count() > 0)
                    <div class="card-body">
                        <div class="alert alert-warning border-0 mb-4" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="fa fa-exclamation-triangle fs-5 me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-2">Endpoints deprecated</h6>
                                    <p class="mb-0 small">
                                        Estos endpoints ya no existen en las rutas de la API, pero se mantienen en el registro para auditoría.
                                        Puedes eliminarlos si ya no los necesitas.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Endpoint</th>
                                        <th width="20%">URL</th>
                                        <th width="15%">Método</th>
                                        <th width="18%">Deprecated desde</th>
                                        <th width="15%">Uso</th>
                                        <th width="12%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($deprecatedEndpoints as $endpoint)
                                        <tr class="opacity-75">
                                            <td>
                                                <div>
                                                    <strong class="d-block">{{ $endpoint->name }}</strong>
                                                    <small class="text-muted font-monospace d-block">{{ $endpoint->slug }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <code class="small">{{ Str::limit($endpoint->url, 30) }}</code>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info">{{ $endpoint->method }}</span>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $endpoint->deprecated_at->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $endpoint->logs->count() }} llamadas</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa fa-ellipsis"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('settings.erp.endpoints.show', $endpoint) }}">
                                                                Ver historial
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item text-danger delete-endpoint"
                                                                    data-id="{{ $endpoint->id }}"
                                                                    data-name="{{ $endpoint->name }}">
                                                                Eliminar definitivamente
                                                            </button>
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
                            <i class="fa fa-history fa-3x mb-3 text-muted opacity-50"></i>
                            <h5 class="fw-bold mb-2">No hay endpoints deprecated</h5>
                            <p class="text-muted mb-4">Todos tus endpoints están activos en el sistema.</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-endpoint').forEach(btn => {
        btn.addEventListener('click', function() {
            const endpointId = this.dataset.id;
            const endpointName = this.dataset.name;

            Swal.fire({
                title: '¿Eliminar endpoint?',
                text: `Se eliminará "${endpointName}" y todos sus datos asociados.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/manager/settings/erp/endpoints/${endpointId}`;

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;

                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'DELETE';

                    form.appendChild(csrfToken);
                    form.appendChild(methodField);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
});
</script>
@endpush
