@extends('layouts.theme')

@section('title', 'Estados de Tickets')

@section('content')

    @include('core::components.card', ['title' => 'Estados de Tickets'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- System Settings Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Estados disponibles</h5>
                        <p class="small mb-0 text-muted">Define y organiza los estados del flujo de tickets</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.backups.tickets.statuses.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('manager.helpdesk.backups.tickets.statuses.create') }}" class="btn btn-primary">
                            Nuevo estado
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">
                                            Total
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Estados configurados</small>
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
                                        <h6 class="card-title text-success mb-2">
                                            Abiertos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['open'] }}</h4>
                                        <small class="text-muted">Estados abiertos</small>
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
                                        <h6 class="card-title text-warning mb-2">
                                            Cerrados
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['closed'] }}</h4>
                                        <small class="text-muted">Estados cerrados</small>
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
                                        <h6 class="card-title text-info mb-2">
                                            Por defecto
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['default'] }}</h4>
                                        <small class="text-muted">Estado Por defecto</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.backups.tickets.statuses.index') }}">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar por nombre o slug..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Statuses List -->
            <div class="card-body ">
                @if($statuses->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-hand-pointer mr-2"></i> Arrastra y suelta para reordenar los estados
                    </div>


                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="statusesTable">
                            <thead class="table-light">
                            <tr>
                                <th width="5%"></th>
                                <th width="25%">Nombre</th>
                                <th width="20%">Slug</th>
                                <th width="25%">Descripción</th>
                                <th width="10%" class="text-center">SLA Timer</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody id="statusesList">
                            @foreach($statuses as $status)
                                <tr data-id="{{ $status->id }}" class="sortable-row">
                                    <td class="drag-handle text-center align-middle">
                                        <i class="fas fa-grip-vertical" style="cursor: grab;"></i>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle" style="width: 16px; height: 16px; background-color: {{ $status->color }};"></div>
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
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded">{{ $status->slug }}</code>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $status->description ? Str::limit($status->description, 60) : '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if($status->stops_sla_timer)
                                            <span class="badge bg-warning-subtle text-warning">Pausado</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">Activo</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.helpdesk.backups.tickets.statuses.edit', $status->id) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                @if(!$status->is_default)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.statuses.destroy', $status->id) }}"
                                                              onsubmit="return confirm('¿Estás seguro de eliminar este estado?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item">
                                                                Eliminar
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-list-check fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay estados para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primer estado para organizar el flujo de tickets
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('manager.helpdesk.backups.tickets.statuses.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear Primer Estado
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($statuses->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $statuses->firstItem() }}</strong> a <strong>{{ $statuses->lastItem() }}</strong>
                            de <strong>{{ $statuses->total() }}</strong> estados
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $statuses->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
    .sortable-row {
        cursor: move;
    }
    .sortable-row:hover {
        background-color: #f8f9fa;
    }
    .ui-sortable-helper {
        display: table;
        background-color: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .ui-sortable-placeholder {
        background-color: #e9ecef;
        visibility: visible !important;
        border: 2px dashed #dee2e6;
    }
    .drag-handle {
        cursor: grab;
    }
    .drag-handle:active {
        cursor: grabbing;
    }
</style>

<script>
$(document).ready(function() {
    // Initialize sortable
    $('#statusesList').sortable({
        handle: '.drag-handle',
        axis: 'y',
        cursor: 'grabbing',
        placeholder: 'ui-sortable-placeholder',
        helper: function(e, tr) {
            var $originals = tr.children();
            var $helper = tr.clone();
            $helper.children().each(function(index) {
                $(this).width($originals.eq(index).width());
            });
            return $helper;
        },
        start: function(e, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function(event, ui) {
            const ids = [];
            $('#statusesList tr').each(function() {
                ids.push($(this).data('id'));
            });

            // Save new order
            $.ajax({
                url: '{{ route('manager.helpdesk.backups.tickets.statuses.reorder') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                },
                success: function(response) {
                    toastr.success(response.message || 'Orden actualizado exitosamente', 'Éxito');
                },
                error: function(xhr) {
                    toastr.error('Error al actualizar el orden', 'Error');
                    $('#statusesList').sortable('cancel');
                }
            });
        }
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
