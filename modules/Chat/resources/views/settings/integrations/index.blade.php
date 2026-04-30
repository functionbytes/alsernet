@extends('layouts.theme')

@section('title', 'Integraciones')

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Integraciones</h5>
                        <p class="small mb-0 text-muted">Conecta con aplicaciones externas como Slack, Zapier o webhooks personalizados</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.chat.integrations.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.chat.integrations.create') }}" class="btn btn-primary">
                            Nueva integración
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $integrations->total() }}</h4>
                                        <small class="text-muted">Integraciones configuradas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Activas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $activeCount }}</h4>
                                        <small class="text-muted">Integraciones habilitadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Inactivas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $inactiveCount }}</h4>
                                        <small class="text-muted">Integraciones deshabilitadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.chat.integrations.index') }}">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar integraciones..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Integrations List -->
            <div class="card-body">
                @if($integrations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="25%">App ID</th>
                                <th width="20%">Bandeja</th>
                                <th width="15%" class="text-center">Estado</th>
                                <th width="15%" class="text-center">Tipo</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($integrations as $hook)
                                <tr>
                                    <td>
                                        <strong>{{ $hook->app_id }}</strong>
                                    </td>
                                    <td>
                                        {{ $hook->inbox->name ?? 'Nivel de cuenta' }}
                                    </td>
                                    <td class="text-center">
                                        @if($hook->status === 1)
                                            <span class="badge bg-success-subtle text-success">Activa</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($hook->hook_type == 0)
                                            <span class="badge bg-info-subtle text-info">Entrante</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Saliente</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.chat.integrations.show', $hook) }}">
                                                        Ver detalles
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.chat.integrations.edit', $hook) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button"
                                                        class="dropdown-item text-danger delete-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#delete-modal"
                                                        data-url="{{ route('settings.chat.integrations.destroy', $hook) }}"
                                                        data-title="Eliminar integración: {{ $hook->app_id }}">
                                                        Eliminar
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
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-plug fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay integraciones registradas</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados
                                @else
                                    Crea tu primera integración para conectar con aplicaciones externas
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.chat.integrations.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primera integración
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($integrations->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $integrations->firstItem() }}</strong> a <strong>{{ $integrations->lastItem() }}</strong>
                            de <strong>{{ $integrations->total() }}</strong> integraciones
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $integrations->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Delete modal functionality
            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const deleteUrl = $(this).data('url');
                const deleteTitle = $(this).data('title');

                $('#delete-modal .modal-title').text(deleteTitle);
                $('#delete-form').attr('action', deleteUrl);

                const deleteModal = new bootstrap.Modal(document.getElementById('delete-modal'));
                deleteModal.show();
            });

            @if (session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
            @endif

            @if (session('error'))
            toastr.error('{{ session('error') }}', 'Error');
            @endif
        });
    </script>
@endpush
