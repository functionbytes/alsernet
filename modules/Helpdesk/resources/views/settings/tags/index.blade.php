@extends('layouts.theme')

@section('title', 'Etiquetas')

@section('page_header')
    @include('core::components.card', ['title' => 'Etiquetas'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Etiquetas de tickets</h5>
                        <p class="small mb-0 text-muted">Organiza y categoriza los tickets con etiquetas personalizadas</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk.tags.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva etiqueta
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Etiquetas registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Habilitadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['inactive']) }}</h4>
                                <small class="text-muted">Deshabilitadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['status'])->filter(fn($k) => request($k))->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request('search');
                @endphp
                <form id="tags-filter-form" method="GET" action="{{ route('settings.helpdesk.tags.index') }}">
                    <input type="hidden" name="status" id="filter-status" value="{{ request('status') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre, slug o descripcion..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#tags-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('settings.helpdesk.tags.index') }}"
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
                            @if(request('status'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Estado: {{ request('status') === 'active' ? 'Activas' : 'Inactivas' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($tags->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Slug</th>
                                    <th scope="col">Descripcion</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tags as $tag)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $tag->id }}"></td>
                                        <td>
                                            <span class="fw-semibold">{{ $tag->name }}</span>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded small">{{ $tag->slug }}</code>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $tag->description ? Str::limit($tag->description, 60) : '—' }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($tag->is_active)
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.tags.edit', $tag->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.helpdesk.tags.destroy', $tag->id) }}"
                                                           data-title="Eliminar etiqueta: {{ $tag->name }}">
                                                            Eliminar
                                                        </a>
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
                        <i class="fas fa-tags fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if($hasAnyFilter)
                                No se encontraron resultados
                            @else
                                No hay etiquetas configuradas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if($hasAnyFilter)
                                No hay resultados para los filtros aplicados
                            @else
                                Aun no hay etiquetas creadas
                            @endif
                        </p>
                        @if($hasAnyFilter)
                            <a href="{{ route('settings.helpdesk.tags.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('settings.helpdesk.tags.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva etiqueta
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($tags->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $tags->firstItem() }} - {{ $tags->lastItem() }} de {{ $tags->total() }}
                        </div>
                        <div>
                            {{ $tags->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Filter modal --}}
    <div class="modal fade" id="tags-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="modal-status" class="form-control select2-filter-modal">
                            <option value="">Todos</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivas</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="tags-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="tags-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none">
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> etiqueta(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
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

    @include('core::components.delete')

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
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

    // Filter modal
    $('.select2-filter-modal').select2({ dropdownParent: $('#tags-filter-modal'), width: '100%' });

    $('#tags-filter-apply-btn').on('click', function () {
        $('#filter-status').val($('#modal-status').val());
        $('#tags-filter-modal').modal('hide');
        $('#tags-filter-form').submit();
    });

    $('#tags-filter-clear-btn').on('click', function () {
        $('#modal-status').val(null).trigger('change');
    });

    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una etiqueta.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' etiqueta(s) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("settings.helpdesk.tags.bulk-action") }}',
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
});
</script>
@endpush
