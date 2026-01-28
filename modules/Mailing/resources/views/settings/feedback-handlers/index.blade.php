@extends('layouts.theme')

@section('page_title', 'Manejadores de retroalimentación')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Manejadores de retroalimentación',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Manejadores de retroalimentación', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Manejadores de retroalimentación</h5>
                        <p class="small mb-0 text-muted">Gestiona los servicios que procesan quejas de usuarios</p>
                    </div>
                    <a href="{{ route('settings.mailing.feedback-handlers.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus me-2"></i>Nuevo manejador
                    </a>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-2">Manejadores de retroalimentación</h6>
                                <p class="mb-0">
                                    Configura servicios para procesar automáticamente quejas y reportes de spam (IMAP, POP3, Webhook o API).
                                    Las quejas se procesan y se marcan los suscriptores como inactivos automáticamente.
                                </p>
                            </div>
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
                            <i class="fa fa-check fs-4 me-2"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.mailing.feedback-handlers.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-7">
                            <label for="search" class="form-label fw-semibold">Búsqueda</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por nombre, email o proveedor..."
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="status" class="form-label fw-semibold">Estado</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                                <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Error</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fa fa-magnifying-glass me-2"></i>Buscar
                            </button>
                            @if (request('search') || request('status'))
                                <a href="{{ route('settings.mailing.feedback-handlers.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Handlers Table --}}
            @if ($handlers->count() > 0)
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="25%">Nombre</th>
                                <th width="15%">Tipo</th>
                                <th width="15%">Estado</th>
                                <th width="20%">Información</th>
                                <th width="15%">Quejas procesadas</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($handlers as $handler)
                                <tr>
                                    <td>
                                        <strong class="d-block">{{ $handler->name }}</strong>
                                        <small class="text-muted font-monospace d-block">{{ $handler->uid }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning-subtle text-warning">
                                            {{ strtoupper($handler->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($handler->status === 'active')
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="fa fa-check-circle me-1"></i>Activo
                                            </span>
                                        @elseif($handler->status === 'inactive')
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="fa fa-times-circle me-1"></i>Inactivo
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">
                                                <i class="fa fa-exclamation-circle me-1"></i>Error
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $handler->provider ?? $handler->type }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $handler->complaints_processed ?? 0 }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.mailing.feedback-handlers.edit', $handler->uid) }}">
                                                        <i class="fa fa-edit me-2"></i>Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger delete-handler"
                                                            data-id="{{ $handler->id }}"
                                                            data-name="{{ $handler->name }}">
                                                        <i class="fa fa-trash me-2"></i>Eliminar
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
                    <h5 class="fw-bold mb-2">No hay manejadores</h5>
                    <p class="text-muted mb-4">
                        @if (request('search') || request('status'))
                            No se encontraron resultados con los filtros aplicados.
                        @else
                            Comienza creando tu primer manejador de retroalimentación.
                        @endif
                    </p>
                    @if (request('search') || request('status'))
                        <a href="{{ route('settings.mailing.feedback-handlers.index') }}" class="btn btn-secondary">
                            Ver todos
                        </a>
                    @else
                        <a href="{{ route('settings.mailing.feedback-handlers.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus me-2"></i>Crear ahora
                        </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Pagination --}}
            @if($handlers->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $handlers->firstItem() }} - {{ $handlers->lastItem() }} de {{ $handlers->total() }} manejadores
                        </div>
                        <div>
                            {{ $handlers->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-handler').forEach(btn => {
        btn.addEventListener('click', function() {
            const handlerId = this.dataset.id;
            const handlerName = this.dataset.name;

            Swal.fire({
                title: '¿Eliminar manejador?',
                text: `Se eliminará el manejador "${handlerName}".`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('settings.mailing.feedback-handlers.destroy', '') }}/${handlerId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('¡Eliminado!', data.message, 'success')
                                .then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'Error al eliminar el manejador', 'error');
                    });
                }
            });
        });
    });
});
</script>
@endpush
