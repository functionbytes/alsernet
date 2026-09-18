@extends('layouts.theme')

@section('title', 'Vistas guardadas de tickets')

@section('page_header')
    @include('core::components.card', ['title' => 'Vistas guardadas de tickets'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Vistas guardadas</h5>
                        <p class="small mb-0 text-muted">Filtros predefinidos para el listado de tickets, propios o compartidos con el equipo</p>
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
                                <small class="text-muted">Vistas configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Del sistema</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['system']) }}</h4>
                                <small class="text-muted">No editables</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Personalizadas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['custom']) }}</h4>
                                <small class="text-muted">Creadas por agentes</small>
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
                                <input type="search" name="search" class="form-control ps-0"
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
                        <table class="table table-hover align-middle text-nowrap" id="views-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Descripcion</th>
                                    <th class="text-center">Propietario</th>
                                    <th class="text-center">Visibilidad</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="views-sortable">
                                @foreach($views as $view)
                                    <tr data-id="{{ $view->id }}">
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $view->id }}"></td>
                                        <td>
                                            <span class="fw-semibold">{{ $view->name }}</span>
                                            @if($view->is_default)
                                                <span class="badge bg-primary-subtle text-primary ms-1">Por defecto</span>
                                            @endif
                                            @if($view->is_system)
                                                <span class="badge bg-secondary-subtle text-secondary ms-1">Sistema</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $view->description ? Str::limit($view->description, 60) : '—' }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <small class="text-muted">
                                                {{ $view->is_system ? 'Sistema' : ($view->user ? trim($view->user->firstname.' '.$view->user->lastname) : '—') }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($view->is_shared)
                                                <span class="badge bg-success-subtle text-success">Compartida</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Privada</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.settings.ticket-views.edit', $view->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    @if(!$view->is_system)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn" href="#"
                                                               data-bs-toggle="modal"
                                                               data-bs-target="#delete-modal"
                                                               data-url="{{ route('manager.helpdesk.settings.ticket-views.destroy', $view->id) }}"
                                                               data-title="Eliminar vista: {{ $view->name }}">
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
                        <i class="fas fa-table-list fa-3x mb-3 text-muted opacity-50"></i>
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
                                Aun no hay vistas creadas
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

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> vista(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select select2">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Compartir</option>
                            <option value="deactivate">Dejar de compartir</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
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

    // Bulk actions
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });
    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids = bulk.getIds();
        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una vista.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' vista(s) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');
        $.ajax({
            url: '{{ route("manager.helpdesk.settings.ticket-views.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // Drag-drop reorder (jQuery UI sortable)
    if (document.getElementById('views-sortable')) {
        $('#views-sortable').sortable({
            // Ver ticket-statuses: la fila entera es el asidero de arrastre.
            handle: 'tr',
            cancel: 'input,textarea,button,select,option,a',
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

                $.post('{{ route('manager.helpdesk.settings.ticket-views.reorder') }}', {
                    _token: '{{ csrf_token() }}',
                    ids: ids,
                }).done(function (res) {
                    toastr.success(res.message || 'Orden actualizado', 'Exito');
                }).fail(function () {
                    toastr.error('Error al actualizar el orden', 'Error');
                    $('#views-sortable').sortable('cancel');
                });
            },
        });
    }
});
</script>
@endpush
