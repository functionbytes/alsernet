@extends('layouts.theme')

@section('title', 'Macros')

@section('page_header')
    @include('core::components.card', ['title' => 'Macros'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Macros configurados</h5>
                        <p class="small mb-0 text-muted">Conjuntos de acciones predefinidas que los agentes pueden ejecutar con un clic</p>
                    </div>
                    <a href="{{ route('settings.helpdesk.macros.create') }}" class="btn btn-primary">
                        Nuevo macro
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Macros configurados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Globales</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['shared'] }}</h4>
                                <small class="text-muted">Disponibles para todos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Personales</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['personal'] }}</h4>
                                <small class="text-muted">Solo del agente creador</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Ejecutados</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total_runs']) }}</h4>
                                <small class="text-muted">Total de ejecuciones</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk.macros.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md-7">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                    placeholder="Buscar por nombre..."
                                    value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="visibility" class="form-select">
                                <option value="">Todas las visibilidades</option>
                                <option value="global" @selected(request('visibility') === 'global')>Global</option>
                                <option value="personal" @selected(request('visibility') === 'personal')>Personal</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($macros->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Descripcion</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                    <th scope="col" class="text-center">Visibilidad</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col" class="text-center">Ejecutados</th>
                                    <th scope="col" class="text-center">Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($macros as $macro)
                                    <tr>
                                        <td>
                                            <strong>{{ $macro->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="text-muted small">
                                                {{ $macro->description ? Str::limit($macro->description, 60) : '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ count($macro->actions ?? []) }}
                                                {{ Str::plural('accion', count($macro->actions ?? [])) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($macro->is_shared)
                                                <span class="badge bg-primary-subtle text-primary">Global</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">Personal</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($macro->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ number_format($macro->usage_count ?? 0) }}
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.macros.edit', $macro) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $macro->id }}"
                                                            data-url="{{ route('settings.helpdesk.macros.destroy', $macro) }}"
                                                            data-name="{{ $macro->name }}">
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
                                <i class="fas fa-bolt fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay macros configurados</h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('visibility'))
                                    No se encontraron resultados para los filtros aplicados
                                @else
                                    Crea tu primer macro para agilizar las acciones de tus agentes
                                @endif
                            </p>
                            @unless(request('search') || request('visibility'))
                                <a href="{{ route('settings.helpdesk.macros.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primer macro
                                </a>
                            @endunless
                        </div>
                    </div>
                @endif
            </div>

            @if($macros->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $macros->firstItem() }}</strong> a <strong>{{ $macros->lastItem() }}</strong>
                            de <strong>{{ $macros->total() }}</strong> macros
                        </div>
                        {{ $macros->appends(request()->input())->links() }}
                    </div>
                </div>
            @endif

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');
        $('#deleteForm').attr('action', url);
        $('#deleteItemName').text(name);
        $('#deleteModal').modal('show');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
