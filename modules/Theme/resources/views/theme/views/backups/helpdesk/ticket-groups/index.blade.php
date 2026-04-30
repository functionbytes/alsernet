@extends('layouts.theme')

@section('title', 'Grupos de tickets')

@section('page_header')
    @include('core::components.card', ['title' => 'Grupos de tickets'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Grupos de tickets</h5>
                        <p class="small mb-0 text-muted">Organiza a los agentes en grupos para la asignacion automatica de tickets</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk.settings.ticket-groups.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo grupo
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
                                <small class="text-muted">Grupos configurados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Grupos habilitados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['inactive']) }}</h4>
                                <small class="text-muted">Grupos deshabilitados</small>
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
                <form method="GET" action="{{ route('manager.helpdesk.settings.ticket-groups.index') }}" id="filterForm">
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
                            <a href="{{ route('manager.helpdesk.settings.ticket-groups.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($groups->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap" id="groups-table">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>Nombre</th>
                                    <th>Descripcion</th>
                                    <th>Modo asignacion</th>
                                    <th class="text-center">Usuarios</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="groups-sortable">
                                @foreach($groups as $group)
                                    @php
                                        $modeLabels = [
                                            'manual' => ['label' => 'Manual', 'color' => 'secondary'],
                                            'round_robin' => ['label' => 'Round robin', 'color' => 'info'],
                                            'load_balanced' => ['label' => 'Balanceo', 'color' => 'success'],
                                        ];
                                        $mode = $modeLabels[$group->assignment_mode] ?? ['label' => 'Manual', 'color' => 'secondary'];
                                    @endphp
                                    <tr data-id="{{ $group->id }}">
                                        <td class="text-center" style="cursor:grab;">
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $group->name }}</span>
                                            @if($group->is_default)
                                                <span class="badge bg-primary-subtle text-primary ms-1">Por defecto</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $group->description ? Str::limit($group->description, 60) : '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $mode['color'] }}-subtle text-{{ $mode['color'] }}">
                                                {{ $mode['label'] }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ $group->users->count() }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($group->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.settings.ticket-groups.edit', $group->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    @if(!$group->is_default)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn" href="#"
                                                               data-bs-toggle="modal"
                                                               data-bs-target="#delete-modal"
                                                               data-url="{{ route('manager.helpdesk.settings.ticket-groups.destroy', $group->id) }}"
                                                               data-title="Eliminar grupo: {{ $group->name }}">
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
                        <i class="fas fa-users fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search'))
                                No se encontraron resultados
                            @else
                                No hay grupos configurados
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Aun no hay grupos creados
                            @endif
                        </p>
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.settings.ticket-groups.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('manager.helpdesk.settings.ticket-groups.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nuevo grupo
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($groups->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $groups->firstItem() }} - {{ $groups->lastItem() }} de {{ $groups->total() }}
                        </div>
                        <div>
                            {{ $groups->appends(request()->input())->links() }}
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
    if (document.getElementById('groups-sortable')) {
        $('#groups-sortable').sortable({
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
                const ids = $('#groups-sortable tr').map(function () {
                    return $(this).data('id');
                }).get();

                $.post('{{ route('manager.helpdesk.settings.ticket-groups.reorder') }}', {
                    _token: '{{ csrf_token() }}',
                    ids: ids,
                }).done(function (res) {
                    toastr.success(res.message || 'Orden actualizado', 'Exito');
                }).fail(function () {
                    toastr.error('Error al actualizar el orden', 'Error');
                    $('#groups-sortable').sortable('cancel');
                });
            },
        });
    }
});
</script>
@endpush
