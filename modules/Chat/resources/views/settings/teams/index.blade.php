@extends('layouts.theme')

@section('title', 'Equipos')

@section('content')

    @include('core::components.card', ['title' => 'Equipos'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Equipos</h5>
                        <p class="small mb-0 text-muted">Gestiona los equipos de trabajo de tu chat para organizar y asignar conversaciones</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.chat.teams.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.chat.teams.create') }}" class="btn btn-primary">
                            Nuevo equipo
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
                                        <small class="text-muted">Equipos configurados</small>
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
                                        <h6 class="card-title mb-2">Con auto-asignación</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['with_auto_assign'] }}</h4>
                                        <small class="text-muted">Equipos con asignación automática</small>
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
                                        <h6 class="card-title text-warning mb-2">Sin auto-asignación</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['without_auto_assign'] }}</h4>
                                        <small class="text-muted">Asignación manual</small>
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
                                        <h6 class="card-title text-info mb-2">Total miembros</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total_members'] }}</h4>
                                        <small class="text-muted">Miembros en todos los equipos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.chat.teams.index') }}">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar equipos..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Teams List -->
            <div class="card-body">
                @if($teams->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="25%">Nombre</th>
                                <th width="35%">Descripción</th>
                                <th width="12%" class="text-center">Miembros</th>
                                <th width="18%" class="text-center">Auto-asignación</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($teams as $team)
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ $team->name }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $team->description ? Str::limit($team->description, 50) : '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $team->members_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($team->allow_auto_assign)
                                            <span class="badge bg-success-subtle text-white ">Habilitada</span>
                                        @else
                                            <span class="badge bg-light-subtle text-black">Deshabilitada</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.chat.teams.show', $team) }}">
                                                        Ver
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.chat.teams.edit', $team) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button"
                                                            class="dropdown-item delete-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#delete-modal"
                                                            data-url="{{ route('settings.chat.teams.destroy', $team) }}"
                                                            data-title="Eliminar equipo: {{ $team->name }}">
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
                            <h6 class="mb-1">No hay equipos para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primer equipo para organizar y asignar conversaciones
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.chat.teams.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($teams->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $teams->firstItem() }}</strong> a <strong>{{ $teams->lastItem() }}</strong>
                            de <strong>{{ $teams->total() }}</strong> equipos
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $teams->links() }}
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
