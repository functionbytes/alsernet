@extends('layouts.theme')

@section('title', 'Políticas SLA de Documentos')

@section('content')

    @include('core::components.card', ['title' => 'Políticas SLA de Documentos'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Políticas SLA disponibles</h5>
                        <p class="small mb-0 text-muted">Define tiempos de respuesta y revisión para la gestión de documentos</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.documents.sla-policies.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.documents.sla-policies.create') }}" class="btn btn-primary">
                           Nueva política
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Políticas configuradas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">Activas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                        <small class="text-muted">Políticas habilitadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Inactivas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                        <small class="text-muted">Políticas deshabilitadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">Con escalamiento</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['with_escalation'] }}</h4>
                                        <small class="text-muted">Políticas con escalamiento</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.documents.sla-policies.index') }}">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar políticas SLA..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Policies List -->
            <div class="card-body">
                @if($policies->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="20%">Nombre</th>
                                <th width="15%">Tiempo Revisión</th>
                                <th width="15%">Tiempo Aprobación</th>
                                <th width="15%">Horario</th>
                                <th width="15%">Escalamiento</th>
                                <th width="10%" class="text-center">Estado</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($policies as $policy)
                                <tr>
                                    <td>
                                        <div>
                                            <a href="{{ route('settings.documents.sla-policies.edit', $policy->id) }}" class="text-decoration-none">
                                                <strong>{{ $policy->name }}</strong>
                                            </a>
                                            @if($policy->is_default)
                                                <br><small class="badge bg-success-subtle text-success">Por defecto</small>
                                            @endif
                                            <small class="d-block text-muted">{{ Str::limit($policy->description, 40) }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($policy->review_time)
                                            <span class="badge bg-info-subtle text-info">{{ $policy->review_time }} min</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-warning-subtle text-warning">{{ $policy->approval_time }} min</span>
                                    </td>
                                    <td>
                                        @if($policy->business_hours_only)
                                            <span class="badge bg-info-subtle text-info">Horario Comercial</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">24/7</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($policy->enable_escalation)
                                            <span class="badge bg-info-subtle text-info">Activado</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('settings.documents.sla-policies.toggle', $policy->id) }}" class="toggle-form">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" class="form-check-input toggle-checkbox" role="switch"
                                                       {{ $policy->active ? 'checked' : '' }}
                                                       onchange="this.form.submit()">
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.documents.sla-policies.edit', $policy->id) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button"
                                                        class="dropdown-item text-success delete-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#delete-modal"
                                                        data-url="{{ route('settings.documents.sla-policies.destroy', $policy->id) }}"
                                                        data-title="Eliminar política SLA: {{ $policy->name }}">
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
                                <i class="fas fa-clipboard-list fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay políticas SLA para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados
                                @else
                                    Crea tu primera política SLA para gestionar tiempos de documentos
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.documents.sla-policies.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear Primera Política SLA
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($policies->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $policies->firstItem() }}</strong> a <strong>{{ $policies->lastItem() }}</strong>
                            de <strong>{{ $policies->total() }}</strong> políticas
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $policies->links() }}
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
            // Toggle form handling
            $('.toggle-form').on('submit', function() {
                $(this).find('.toggle-checkbox').prop('disabled', true);
            });

            // Delete modal functionality
            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const deleteUrl = $(this).data('url');
                const deleteTitle = $(this).data('title');

                $('#delete-modal .modal-title').text(deleteTitle);
                $('#delete-form').attr('action', deleteUrl);

                // Show the modal explicitly
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
