@extends('layouts.theme')

@section('title', 'Encuestas')

@push('styles')
<style>
.hd-bulk-toolbar { z-index: 1050; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Encuestas'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Encuestas de satisfaccion</h5>
                        <p class="small mb-0 text-muted">Recopila feedback de tus clientes tras las conversaciones</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk.surveys.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva encuesta
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
                                <small class="text-muted">Encuestas configuradas</small>
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
                                <h6 class="card-title mb-2">Respuestas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['responses']) }}</h4>
                                <small class="text-muted">Respuestas completadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['status'])->filter(fn ($k) => request()->filled($k))->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request()->filled('search');
                @endphp
                <form id="surveys-filter-form" method="GET" action="{{ route('settings.helpdesk.surveys.index') }}">
                    <input type="hidden" name="status" id="filter-status" value="{{ request('status') }}">

                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por nombre..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#surveys-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                    {{ $activeFilterCount }}
                                </span>
                            @endif
                        </button>

                        <button type="submit" class="btn btn-primary flex-shrink-0" aria-label="Buscar">
                            <i class="fas fa-search"></i>
                        </button>
                        @if($hasAnyFilter)
                            <a href="{{ route('settings.helpdesk.surveys.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar filtros">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap align-items-center mt-3">
                            <h6 class="mb-0">Filtrados:</h6>
                            @if(request()->filled('status'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Estado: {{ request('status') === '1' ? 'Activas' : 'Inactivas' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($surveys->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" width="36"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Disparo</th>
                                    <th scope="col" class="text-center">Preguntas</th>
                                    <th scope="col" class="text-center">Respuestas</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surveys as $survey)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $survey->id }}"></td>
                                        <td>
                                            <span class="fw-semibold">{{ $survey->name }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ \Modules\Helpdesk\Models\Survey::TRIGGER_TYPES[$survey->trigger_type] ?? $survey->trigger_type }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ is_array($survey->questions) ? count($survey->questions) : 0 }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('settings.helpdesk.surveys.responses', $survey->id) }}"
                                               class="badge bg-primary-subtle text-primary text-decoration-none">
                                                {{ number_format($survey->responses_count) }}
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            @if($survey->is_active)
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
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.surveys.edit', $survey->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.surveys.responses', $survey->id) }}">
                                                            Ver respuestas
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.helpdesk.surveys.destroy', $survey->id) }}"
                                                           data-title="Eliminar encuesta: {{ $survey->name }}">
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
                        <i class="fas fa-poll fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request()->hasAny(['search', 'status']))
                                No se encontraron resultados
                            @else
                                No hay encuestas configuradas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request()->hasAny(['search', 'status']))
                                No hay resultados para los filtros aplicados
                            @else
                                Crea tu primera encuesta para recopilar feedback de tus clientes
                            @endif
                        </p>
                        @if(request()->hasAny(['search', 'status']))
                            <a href="{{ route('settings.helpdesk.surveys.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('settings.helpdesk.surveys.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva encuesta
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($surveys->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $surveys->firstItem() }} - {{ $surveys->lastItem() }} de {{ $surveys->total() }}
                        </div>
                        <div>
                            {{ $surveys->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

    {{-- Filter modal --}}
    <div class="modal fade" id="surveys-filter-modal" tabindex="-1" aria-hidden="true">
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
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Activas</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactivas</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="surveys-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="surveys-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> encuesta(s)</strong>.</p>
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

    // ── Filtros avanzados ────────────────────────────────────────────
    $('.select2-filter-modal').select2({ dropdownParent: $('#surveys-filter-modal'), width: '100%' });

    $('#surveys-filter-apply-btn').on('click', function () {
        $('#filter-status').val($('#modal-status').val());
        $('#surveys-filter-modal').modal('hide');
        $('#surveys-filter-form').submit();
    });

    $('#surveys-filter-clear-btn').on('click', function () {
        $('#modal-status').val(null).trigger('change');
    });

    // ── Bulk actions ──────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una encuesta.'); return; }

        var applyBulkAction = function () {
            var $btn = $('#bulk-apply-btn');
            $btn.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: '{{ route('settings.helpdesk.surveys.bulk-action') }}',
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
            window.__confirm('¿Eliminar ' + ids.length + ' encuesta(s)? Esta acción no se puede deshacer.', applyBulkAction);
        } else {
            applyBulkAction();
        }
    });
});
</script>
@endpush
