@extends('layouts.theme')

@section('title', 'Vistas guardadas de tickets')

@push('styles')
<style>
.hd-drag-handle { cursor: grab; }
.hd-bulk-toolbar { z-index: 1050; }
.hd-filter-badge { font-size: .6rem; }
</style>
@endpush

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
                        <h5 class="mb-1 fw-bold">Vistas guardadas de tickets</h5>
                        <p class="small mb-0 text-muted">Configura las vistas del listado de tickets disponibles para los agentes</p>
                    </div>
                    <div class="ms-auto d-flex gap-2">
                        <a href="{{ route('settings.helpdesk.views.create') }}" class="btn btn-primary">
                            Nueva vista
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

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['scope'])->filter(fn ($k) => request($k))->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request('search');
                @endphp
                <form id="views-filter-form" method="GET" action="{{ route('settings.helpdesk.views.index') }}">
                    <input type="hidden" name="scope" id="filter-scope" value="{{ request('scope') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre o descripción..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#views-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary hd-filter-badge">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('settings.helpdesk.views.index') }}"
                                   class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-4">
                            <div>
                                <h6 class="mb-1">Filtrados:</h6>
                            </div>
                            @if(request('scope'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    {{ request('scope') === 'personal' ? 'Personales' : 'Compartidas' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($views->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="views-table">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" width="36"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col">Filtros</th>
                                    <th scope="col">Ordenación</th>
                                    <th scope="col" class="text-center">Tipo</th>
                                    <th scope="col" class="text-center">Sistema</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="views-sortable">
                                @foreach($views as $ticketView)
                                    <tr data-id="{{ $ticketView->id }}">
                                        <td>
                                            @if(!$ticketView->is_system)
                                                <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $ticketView->id }}">
                                            @endif
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
                                                {{ $ticketView->description ? Str::limit($ticketView->description, 45) : '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            @forelse($ticketView->filterLabels() as $filterLabel)
                                                <span class="badge bg-light text-secondary border fw-normal me-1 mb-1">{{ $filterLabel }}</span>
                                            @empty
                                                <small class="text-muted">Sin filtros</small>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if($ticketView->sort_by)
                                                <small class="text-muted text-nowrap">
                                                    {{ $sortLabels[$ticketView->sort_by] ?? $ticketView->sort_by }}
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
                                            @if($ticketView->is_public)
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
                                                            <a class="dropdown-item" href="{{ route('settings.helpdesk.views.edit', $ticketView->id) }}">
                                                                Editar
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn" href="#"
                                                               data-bs-toggle="modal"
                                                               data-bs-target="#delete-modal"
                                                               data-url="{{ route('settings.helpdesk.views.destroy', $ticketView->id) }}"
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
                            @if($hasAnyFilter)
                                No se encontraron resultados
                            @else
                                No hay vistas configuradas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @elseif(request('scope'))
                                No hay resultados para los filtros aplicados
                            @else
                                Aun no hay vistas guardadas creadas
                            @endif
                        </p>
                        @if($hasAnyFilter)
                            <a href="{{ route('settings.helpdesk.views.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('settings.helpdesk.views.create') }}" class="btn btn-primary">
                                Nueva vista
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

    {{-- Filter modal --}}
    <div class="modal fade" id="views-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Alcance</label>
                        <select id="modal-scope" class="form-control select2-filter-modal">
                            <option value="">Todas</option>
                            <option value="personal" {{ request('scope') === 'personal' ? 'selected' : '' }}>Personales</option>
                            <option value="public" {{ request('scope') === 'public' ? 'selected' : '' }}>Compartidas</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="views-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="views-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar --}}
    <div id="bulk-toolbar" class="hd-bulk-toolbar position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionada(s) &mdash; Aplicar acción
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> vista(s)</strong>. Las vistas del sistema serán omitidas.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-2">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

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

    // ── Filter modal ─────────────────────────────────────────────────
    $('.select2-filter-modal').select2({ dropdownParent: $('#views-filter-modal'), width: '100%' });

    // ── Bulk actions ──────────────────────────────────────────────────
    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#views-filter-apply-btn').on('click', function () {
        $('#filter-scope').val($('#modal-scope').val());
        $('#views-filter-modal').modal('hide');
        $('#views-filter-form').submit();
    });

    $('#views-filter-clear-btn').on('click', function () {
        $('#modal-scope').val(null).trigger('change');
    });

    // ── Bulk actions ──────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una vista.'); return; }

        var applyBulkAction = function () {
            var $btn = $('#bulk-apply-btn');
            $btn.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: '{{ route('settings.helpdesk.views.bulk-action') }}',
                method: 'POST',
                data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#bulk-modal').modal('hide');
                    toastr.success(res.message);
                    setTimeout(function () { location.reload(); }, 800);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message ?? 'Error al procesar la acción.');
                    $btn.prop('disabled', false).text('Aplicar');
                },
            });
        };

        if (action === 'delete') {
            window.__confirm('¿Eliminar ' + ids.length + ' vista(s)? Esta acción no se puede deshacer.', applyBulkAction);
        } else {
            applyBulkAction();
        }
    });

    // ── Drag-drop reorder (jQuery UI Sortable) ────────────────────────
    if ($('#views-sortable').length) {
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

                $.ajax({
                    url: '{{ route('settings.helpdesk.views.reorder') }}',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ ids }),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
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
