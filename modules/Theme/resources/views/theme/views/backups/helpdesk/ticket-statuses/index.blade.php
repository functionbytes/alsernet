@extends('layouts.theme')

@section('title', 'Estados de tickets')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-circle-check me-2 text-primary"></i>
                Estados de tickets
            </h4>
            <p class="text-muted mb-0">Define y organiza los estados del flujo de tickets</p>
        </div>
        <div class="d-flex gap-2">
            @if(request('search'))
                <a href="{{ route('manager.helpdesk.backups.tickets.statuses.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Limpiar búsqueda
                </a>
            @endif
            <a href="{{ route('manager.helpdesk.backups.tickets.statuses.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Nuevo estado
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total</p>
                    <h4 class="fw-bold mb-0">{{ $stats['total'] }}</h4>
                    <small class="text-muted">estados</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-success small mb-1">Abiertos</p>
                    <h4 class="fw-bold mb-0">{{ $stats['open'] }}</h4>
                    <small class="text-muted">estados abiertos</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-secondary small mb-1">Cerrados</p>
                    <h4 class="fw-bold mb-0">{{ $stats['closed'] }}</h4>
                    <small class="text-muted">estados cerrados</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-primary small mb-1">Por defecto</p>
                    <h4 class="fw-bold mb-0">{{ $stats['default'] }}</h4>
                    <small class="text-muted">configurados</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        {{-- Search --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('manager.helpdesk.backups.tickets.statuses.index') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" name="search" class="form-control border-start-0"
                                   placeholder="Buscar por nombre o slug..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Buscar</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body p-0">
            @if($statuses->count() > 0)
                <div class="alert alert-info m-3 mb-0">
                    <i class="fas fa-hand-pointer me-2"></i> Arrastra y suelta las filas para reordenar los estados
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="statusesTable">
                        <thead class="table-light">
                            <tr>
                                <th width="40"></th>
                                <th>Nombre</th>
                                <th>Slug</th>
                                <th>Descripcion</th>
                                <th class="text-center">SLA</th>
                                <th class="text-center" width="80">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="statusesList">
                            @foreach($statuses as $status)
                                <tr data-id="{{ $status->id }}">
                                    <td class="text-center align-middle drag-handle" style="cursor: grab;">
                                        <i class="fas fa-grip-vertical text-muted"></i>
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="rounded-circle d-inline-block"
                                                  style="width:14px;height:14px;background-color:{{ $status->color }};"></span>
                                            <div>
                                                <strong>{{ $status->name }}</strong>
                                                <div class="d-flex gap-1 mt-1">
                                                    @if($status->is_default)
                                                        <span class="badge bg-primary-subtle text-primary">Por defecto</span>
                                                    @endif
                                                    @if($status->is_open)
                                                        <span class="badge bg-success-subtle text-success">Abierto</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary">Cerrado</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <code class="bg-light px-2 py-1 rounded small">{{ $status->slug }}</code>
                                    </td>
                                    <td class="align-middle">
                                        <small class="text-muted">
                                            {{ $status->description ? Str::limit($status->description, 60) : '—' }}
                                        </small>
                                    </td>
                                    <td class="text-center align-middle">
                                        @if($status->stops_sla_timer)
                                            <span class="badge bg-warning-subtle text-warning">Pausado</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">Activo</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.helpdesk.backups.tickets.statuses.edit', $status->id) }}">
                                                        <i class="fas fa-pen me-2"></i> Editar
                                                    </a>
                                                </li>
                                                @if(!$status->is_default)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.statuses.destroy', $status->id) }}"
                                                              onsubmit="return confirm('¿Eliminar este estado?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fas fa-trash me-2"></i> Eliminar
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
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
                    <i class="fas fa-circle-check fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">
                        @if(request('search'))
                            Sin resultados para "{{ request('search') }}"
                        @else
                            No hay estados configurados
                        @endif
                    </h6>
                    @if(!request('search'))
                        <a href="{{ route('manager.helpdesk.backups.tickets.statuses.create') }}" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-plus me-1"></i> Crear primer estado
                        </a>
                    @endif
                </div>
            @endif
        </div>

        @if($statuses->hasPages())
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Mostrando {{ $statuses->firstItem() }}–{{ $statuses->lastItem() }} de {{ $statuses->total() }}
                </small>
                {{ $statuses->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(function () {
    $('#statusesList').sortable({
        handle: '.drag-handle',
        axis: 'y',
        cursor: 'grabbing',
        start: function (e, ui) {
            ui.item.addClass('table-active');
        },
        stop: function (e, ui) {
            ui.item.removeClass('table-active');
        },
        update: function () {
            const ids = $('#statusesList tr').map(function () {
                return $(this).data('id');
            }).get();

            $.post('{{ route('manager.helpdesk.backups.tickets.statuses.reorder') }}', {
                _token: '{{ csrf_token() }}',
                ids: ids,
            }).done(function (res) {
                toastr.success(res.message || 'Orden actualizado', 'Exito');
            }).fail(function () {
                toastr.error('Error al actualizar el orden', 'Error');
                $('#statusesList').sortable('cancel');
            });
        },
    });
});
</script>
@endpush
