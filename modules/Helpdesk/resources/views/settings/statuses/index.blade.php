@extends('layouts.theme')

@section('title', 'Estados de tickets')

@section('page_header')
    @include('core::components.card', ['title' => 'Estados de tickets'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Estados de tickets</h5>
                        <p class="small mb-0 text-muted">Define y organiza los estados del flujo de tickets</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk.statuses.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo estado
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
                                <small class="text-muted">Estados registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Abiertos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['open']) }}</h4>
                                <small class="text-muted">Tickets activos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Cerrados</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['closed']) }}</h4>
                                <small class="text-muted">Tickets finalizados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Por defecto</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['default']) }}</h4>
                                <small class="text-muted">Configurados</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk.statuses.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre o slug..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('settings.helpdesk.statuses.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($statuses->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap" id="statuses-table">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>Nombre</th>
                                    <th>Slug</th>
                                    <th>Descripcion</th>
                                    <th class="text-center">SLA</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="statuses-sortable">
                                @foreach($statuses as $status)
                                    <tr data-id="{{ $status->id }}">
                                        <td class="text-center" style="cursor:grab;">
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="rounded-circle d-inline-block flex-shrink-0"
                                                      style="width:12px;height:12px;background-color:{{ $status->color }};"></span>
                                                <div>
                                                    <span class="fw-semibold">{{ $status->name }}</span>
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
                                            <code class="bg-light px-2 py-1 rounded small">{{ $status->slug }}</code>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $status->description ? Str::limit($status->description, 60) : '—' }}
                                            </small>
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
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.statuses.edit', $status->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    @if(!$status->is_default)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn" href="#"
                                                               data-bs-toggle="modal"
                                                               data-bs-target="#delete-modal"
                                                               data-url="{{ route('settings.helpdesk.statuses.destroy', $status->id) }}"
                                                               data-title="Eliminar estado: {{ $status->name }}">
                                                                Eliminar
                                                            </a>
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
                        <i class="fas fa-circle-check fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search'))
                                No se encontraron resultados
                            @else
                                No hay estados configurados
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Aun no hay estados creados
                            @endif
                        </p>
                        @if(request('search'))
                            <a href="{{ route('settings.helpdesk.statuses.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('settings.helpdesk.statuses.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nuevo estado
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($statuses->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $statuses->firstItem() }} - {{ $statuses->lastItem() }} de {{ $statuses->total() }}
                        </div>
                        <div>
                            {{ $statuses->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Delete modal
    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Drag-drop reorder (jQuery UI sortable)
    if (document.getElementById('statuses-sortable')) {
        $('#statuses-sortable').sortable({
            handle: 'td:first-child',
            axis: 'y',
            cursor: 'grabbing',
            start: function (e, ui) {
                ui.item.addClass('table-active');
            },
            stop: function (e, ui) {
                ui.item.removeClass('table-active');
            },
            update: function () {
                const ids = $('#statuses-sortable tr').map(function () {
                    return $(this).data('id');
                }).get();

                $.post('{{ route('settings.helpdesk.statuses.reorder') }}', {
                    _token: '{{ csrf_token() }}',
                    ids: ids,
                }).done(function (res) {
                    toastr.success(res.message || 'Orden actualizado', 'Exito');
                }).fail(function () {
                    toastr.error('Error al actualizar el orden', 'Error');
                    $('#statuses-sortable').sortable('cancel');
                });
            },
        });
    }
});
</script>
@endpush
