@extends('layouts.theme')

@section('title', 'Políticas SLA')

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Políticas SLA de conversaciones</h5>
                        <p class="small mb-0 text-muted">Define tiempos de respuesta y resolución para gestión de conversaciones</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.chat.sla-policies.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.chat.sla-policies.create') }}" class="btn btn-primary">
                            Nueva política SLA
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
                                        <h4 class="mb-1 fw-bold">{{ $policies->total() }}</h4>
                                        <small class="text-muted">Políticas configuradas</small>
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
                                        <h4 class="mb-1 fw-bold">{{ $policies->where('is_active', true)->count() }}</h4>
                                        <small class="text-muted">Políticas habilitadas</small>
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
                                        <h4 class="mb-1 fw-bold">{{ $policies->where('is_active', false)->count() }}</h4>
                                        <small class="text-muted">Políticas deshabilitadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.chat.sla-policies.index') }}">
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
                                <th width="25%">Nombre</th>
                                <th width="15%">Primera respuesta</th>
                                <th width="15%">Siguiente respuesta</th>
                                <th width="15%">Resolución</th>
                                <th width="10%">Horario</th>
                                <th width="10%" class="text-center">Estado</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($policies as $policy)
                                <tr>
                                    <td>
                                        <div>
                                            <a href="{{ route('settings.chat.sla-policies.show', $policy) }}" class="text-decoration-none">
                                                <strong>{{ $policy->name }}</strong>
                                            </a>
                                            @if($policy->description)
                                                <small class="d-block text-muted">{{ Str::limit($policy->description, 50) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($policy->first_response_time_minutes)
                                            <span class="badge bg-info-subtle text-info">
                                                {{ floor($policy->first_response_time_minutes / 60) }}h {{ $policy->first_response_time_minutes % 60 }}m
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($policy->next_response_time_minutes)
                                            <span class="badge bg-info-subtle text-info">
                                                {{ floor($policy->next_response_time_minutes / 60) }}h {{ $policy->next_response_time_minutes % 60 }}m
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($policy->resolution_time_minutes)
                                            <span class="badge bg-warning-subtle text-warning">
                                                {{ floor($policy->resolution_time_minutes / 60) }}h {{ $policy->resolution_time_minutes % 60 }}m
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($policy->business_hours_only)
                                            <span class="badge bg-info-subtle text-info">Comercial</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">24/7</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($policy->is_active)
                                            <span class="badge bg-success-subtle text-success">Activa</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.chat.sla-policies.show', $policy) }}">
                                                        Ver detalles
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.chat.sla-policies.edit', $policy) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button"
                                                        class="dropdown-item text-success delete-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#delete-modal"
                                                        data-url="{{ route('settings.chat.sla-policies.destroy', $policy) }}"
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
                                <i class="fas fa-clock fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay políticas SLA para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados
                                @else
                                    Crea tu primera política SLA para gestionar tiempos de respuesta
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.chat.sla-policies.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primera política SLA
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
