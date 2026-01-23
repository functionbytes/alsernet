@extends('layouts.theme')

@section('title', 'Grupos de Validación')

@section('content')

    @include('core::components.card', ['title' => 'Grupos de Validación'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Grupos de validación</h5>
                        <p class="small mb-0 text-muted">Organiza a los usuarios en grupos para validación multi-etapa de documentos, tickets y otros procesos</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.documents.groups.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.documents.groups.create') }}" class="btn btn-primary">
                            Nuevo grupo
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
                                        <small class="text-muted">Grupos configurados</small>
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
                                        <h6 class="card-title text-success mb-2">Activos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                        <small class="text-muted">Grupos habilitados</small>
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
                                        <h6 class="card-title text-warning mb-2">Inactivos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                        <small class="text-muted">Grupos deshabilitados</small>
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
                                        <h6 class="card-title text-info mb-2">Total usuarios</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total_members'] }}</h4>
                                        <small class="text-muted">Miembros en todos los grupos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.documents.groups.index') }}">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar grupos..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Groups List -->
            <div class="card-body">
                @if($groups->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="25%">Nombre</th>
                                <th width="12%">Clave</th>
                                <th width="12%">Modo</th>
                                <th width="8%">Miembros</th>
                                <th width="23%">Descripción</th>
                                <th width="10%" class="text-center">Estado</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($groups as $group)
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ $group->name }}</strong>
                                            @if($group->is_default)
                                                <span class="badge bg-primary-subtle text-primary ms-1">Por defecto</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <code class="text-primary">{{ $group->key }}</code>
                                    </td>
                                    <td>
                                        @php
                                            $modeLabels = [
                                                'manual' => ['label' => 'Manual', 'color' => 'light'],
                                                'round_robin' => ['label' => 'R. Robin', 'color' => 'light'],
                                                'load_balanced' => ['label' => 'Balance', 'color' => 'light']
                                            ];
                                            $mode = $modeLabels[$group->assignment_mode] ?? ['label' => 'Manual', 'color' => 'black'];
                                        @endphp
                                        <span class="badge bg-{{ $mode['color'] }}-subtle text-{{ $mode['color'] }}">{{ $mode['label'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $group->users->count() }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $group->description ? Str::limit($group->description, 40) : '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('settings.documents.groups.toggle', $group->uid) }}" class="toggle-form">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" class="form-check-input toggle-checkbox" role="switch"
                                                       {{ $group->is_active ? 'checked' : '' }}
                                                       onchange="this.form.submit()">
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.documents.groups.edit', $group->uid) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.documents.groups.configuration', $group->uid) }}">
                                                        Configuración
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.documents.groups.permissions.edit', $group->uid) }}">
                                                        Permisos
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button"
                                                            class="dropdown-item  delete-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#delete-modal"
                                                            data-url="{{ route('settings.documents.groups.destroy', $group->uid) }}"
                                                            data-title="Eliminar grupo: {{ $group->name }}">
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
                                <i class="fas fa-users fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay grupos para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primer grupo para organizar usuarios
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.documents.groups.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($groups->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $groups->firstItem() }}</strong> a <strong>{{ $groups->lastItem() }}</strong>
                            de <strong>{{ $groups->total() }}</strong> grupos
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $groups->links() }}
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
    $('.delete-btn').on('click', function() {
        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').attr('action', deleteUrl);
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
