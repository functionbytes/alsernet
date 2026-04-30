@extends('layouts.theme')

@section('title', 'Reglas de automatización')

@section('content')

@include('core::components.alerts')

<div class="card">
    <div class="card-header bg-info-subtle">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Reglas de automatización</h5>
                <p class="small mb-0 text-muted">Automatiza acciones cuando ocurren eventos específicos en las conversaciones</p>
            </div>
            <div>
                <a href="{{ route('settings.chat.automation-rules.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva regla
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="card-body border-bottom">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card bg-light-subtle h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">Total de reglas</h6>
                                <h3 class="mb-0 fw-bold">{{ $automationRules->total() }}</h3>
                            </div>
                            <div class="rounded-circle bg-primary-subtle p-3">
                                <i class="fas fa-robot text-primary fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light-subtle h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">Activas</h6>
                                <h3 class="mb-0 fw-bold text-success">{{ $automationRules->where('active', true)->count() }}</h3>
                            </div>
                            <div class="rounded-circle bg-success-subtle p-3">
                                <i class="fas fa-check-circle text-success fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light-subtle h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">Inactivas</h6>
                                <h3 class="mb-0 fw-bold text-secondary">{{ $automationRules->where('active', false)->count() }}</h3>
                            </div>
                            <div class="rounded-circle bg-secondary-subtle p-3">
                                <i class="fas fa-pause-circle text-secondary fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('settings.chat.automation-rules.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label class="control-label col-form-label mb-2">Buscar reglas</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="search" name="search" class="form-control"
                               placeholder="Buscar por nombre o descripción..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
            @if(request('search'))
                <div class="mt-2">
                    <a href="{{ route('settings.chat.automation-rules.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times"></i> Limpiar búsqueda
                    </a>
                </div>
            @endif
        </form>
    </div>

    <!-- Rules List -->
    <div class="card-body">
        @if($automationRules->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="25%">Nombre</th>
                            <th width="20%">Evento</th>
                            <th width="15%" class="text-center">Condiciones</th>
                            <th width="15%" class="text-center">Acciones</th>
                            <th width="10%" class="text-center">Estado</th>
                            <th width="15%" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($automationRules as $rule)
                            <tr>
                                <td>
                                    <div>
                                        <strong class="d-block">{{ $rule->name }}</strong>
                                        @if($rule->description)
                                            <small class="text-muted">{{ Str::limit($rule->description, 60) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <code class="small bg-light px-2 py-1 rounded">{{ $rule->event_name }}</code>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info">
                                        {{ count($rule->conditions) }} condiciones
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info">
                                        {{ count($rule->actions) }} acciones
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($rule->active)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check-circle"></i> Activa
                                        </span>
                                    @else
                                        <span class="badge bg-info-subtle text-info">
                                            <i class="fas fa-pause-circle"></i> Inactiva
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <form action="{{ route('settings.chat.automation-rules.toggle', $rule->id) }}" method="POST" class="d-inline-block">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-{{ $rule->active ? 'secondary' : 'success' }}"
                                                    title="{{ $rule->active ? 'Desactivar' : 'Activar' }}">
                                                <i class="fas fa-{{ $rule->active ? 'pause' : 'play' }}"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('settings.chat.automation-rules.edit', $rule->id) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger delete-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#delete-modal"
                                                data-url="{{ route('settings.chat.automation-rules.destroy', $rule->id) }}"
                                                data-title="Eliminar regla: {{ $rule->name }}"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
                    <div class="rounded-circle bg-light-subtle p-4 mb-3">
                        <i class="fas fa-robot fs-1 text-muted"></i>
                    </div>
                    <h5 class="mb-2">No hay reglas de automatización</h5>
                    <p class="text-muted mb-3">
                        @if(request('search'))
                            No se encontraron resultados para tu búsqueda
                        @else
                            Crea tu primera regla para automatizar acciones en las conversaciones
                        @endif
                    </p>
                    @if(!request('search'))
                        <a href="{{ route('settings.chat.automation-rules.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Crear primera regla
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if($automationRules->hasPages())
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Mostrando <strong>{{ $automationRules->firstItem() }}</strong> a <strong>{{ $automationRules->lastItem() }}</strong>
                    de <strong>{{ $automationRules->total() }}</strong> reglas
                </div>
                <nav>
                    {{ $automationRules->links() }}
                </nav>
            </div>
        </div>
    @endif
</div>

@include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').attr('action', deleteUrl);
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
