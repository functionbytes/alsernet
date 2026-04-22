@extends('layouts.theme')

@section('title', 'Vistas guardadas de tickets')

@section('content')

    @include('core::components.card', ['title' => 'Vistas guardadas de tickets'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Vistas guardadas de tickets</h5>
                        <p class="small mb-0 text-muted">Configura las vistas del listado de tickets disponibles para los agentes</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk.settings.ticket-views.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva vista
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Vistas registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Compartidas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['shared']) }}</h4>
                                <small class="text-muted">Visibles para el equipo</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Privadas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['custom']) }}</h4>
                                <small class="text-muted">Solo para su creador</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Del sistema</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['system']) }}</h4>
                                <small class="text-muted">Predefinidas e inmutables</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.settings.ticket-views.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre o descripcion..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.settings.ticket-views.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($views->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="views-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="40"></th>
                                    <th>Nombre</th>
                                    <th>Descripcion</th>
                                    <th>Ordenacion</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Sistema</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="views-sortable">
                                @foreach($views as $ticketView)
                                    <tr data-id="{{ $ticketView->id }}">
                                        <td class="text-center drag-handle" style="cursor:grab;">
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="fw-semibold">{{ $ticketView->name }}</span>
                                                @if($ticketView->is_default)
                                                    <span class="badge bg-primary-subtle text-primary ms-1">Por defecto</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $ticketView->description ? Str::limit($ticketView->description, 60) : '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($ticketView->sort_by)
                                                <small class="text-muted">
                                                    {{ $ticketView->sort_by }}
                                                    <span class="ms-1">
                                                        @if($ticketView->sort_direction === 'asc')
                                                            <i class="fas fa-arrow-up"></i>
                                                        @else
                                                            <i class="fas fa-arrow-down"></i>
                                                        @endif
                                                    </span>
                                                </small>
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($ticketView->is_shared)
                                                <span class="badge bg-success-subtle text-success">Compartida</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Privada</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($ticketView->is_system)
                                                <span class="badge bg-warning-subtle text-warning">Sistema</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(!$ticketView->is_system)
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('manager.helpdesk.settings.ticket-views.edit', $ticketView->id) }}">
                                                                Editar
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn" href="#"
                                                               data-bs-toggle="modal"
                                                               data-bs-target="#delete-modal"
                                                               data-url="{{ route('manager.helpdesk.settings.ticket-views.destroy', $ticketView->id) }}"
                                                               data-title="Eliminar vista: {{ $ticketView->name }}">
                                                                Eliminar
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-filter fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search'))
                                No se encontraron resultados
                            @else
                                No hay vistas configuradas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Aun no hay vistas guardadas creadas
                            @endif
                        </p>
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.settings.ticket-views.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('manager.helpdesk.settings.ticket-views.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva vista
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($views->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $views->firstItem() }} - {{ $views->lastItem() }} de {{ $views->total() }}
                        </div>
                        <div>
                            {{ $views->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Drag-drop reorder (jQuery UI Sortable)
    if ($('#views-sortable').length) {
        $('#views-sortable').sortable({
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
                const ids = $('#views-sortable tr').map(function () {
                    return $(this).data('id');
                }).get();

                $.ajax({
                    url: '{{ route('manager.helpdesk.settings.ticket-views.reorder') }}',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ ids }),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        toastr.success(res.message || 'Orden actualizado.');
                    },
                    error: function () {
                        toastr.error('Error al actualizar el orden.');
                        $('#views-sortable').sortable('cancel');
                    },
                });
            },
        });
    }
});
</script>
@endpush
