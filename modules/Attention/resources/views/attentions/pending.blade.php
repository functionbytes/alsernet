@extends('layouts.theme')

@section('title', 'peticiones Pendientes')

@section('page_header')
    @include('core::components.card', [
    'title' => 'peticiones Pendientes',
    'breadcrumbs' => [
    ['label' => 'Dashboard', 'url' => url('/home')],
    ['label' => 'peticiones', 'url' => ''],
    ['label' => 'Pendientes', 'active' => true],
    ]
    ])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Pendientes</h5>
                        <p class="small mb-0 text-muted">Gestiona, filtra y da seguimiento a las peticiones</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('attention.create') }}">Nueva peticiones</a>
                                <a class="dropdown-item" href="{{ route('attention.dashboard') }}">Dashboard</a>
                                <a class="dropdown-item" href="{{ route('import.show', 'attention') }}">Importar</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('attention.export', array_merge(request()->query(), ['format' => 'excel'])) }}">Exportar Excel</a>
                                <a class="dropdown-item" href="{{ route('attention.export', array_merge(request()->query(), ['format' => 'csv'])) }}">Exportar CSV</a>
                                <a class="dropdown-item" href="{{ route('attention.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">Exportar PDF</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total pendientes</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] ?? 0 }}</h4>
                                <small class="text-muted">peticiones sin finalizar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Hoy</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['today'] ?? 0 }}</h4>
                                <small class="text-muted">Registrados hoy</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Vencidos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['overdue'] ?? 0 }}</h4>
                                <small class="text-muted">Fuera de tiempo</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Asignados a mí</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['assigned_to_me'] ?? 0 }}</h4>
                                <small class="text-muted">En mi bandeja</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                @php
                    $extraFiltersCount = collect([$typeId, $categoryId, $departmentId, $sedeId, $dateFrom])->filter()->count();
                @endphp
                <form class="form-search" action="{{ route('attention.pending') }}" method="GET" id="pending-filter-form">
                    {{-- Hidden extra filters (submitted with form) --}}
                    <input type="hidden" name="type_id"       id="h_type_id"       value="{{ $typeId ?? '' }}">
                    <input type="hidden" name="category_id"   id="h_category_id"   value="{{ $categoryId ?? '' }}">
                    <input type="hidden" name="department_id" id="h_department_id" value="{{ $departmentId ?? '' }}">
                    <input type="hidden" name="sede_id"       id="h_sede_id"       value="{{ $sedeId ?? '' }}">
                    <input type="hidden" id="date_from" name="date_from" value="{{ $dateFrom ?? '' }}">
                    <input type="hidden" id="date_to"   name="date_to"   value="{{ $dateTo ?? '' }}">

                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        {{-- Search --}}
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input class="form-control border-start-0 ps-0" type="text" name="search"
                                       placeholder="Buscar por radicado, nombre, DNI o asunto..."
                                       value="{{ $searchKey ?? '' }}">
                            </div>
                        </div>

                        {{-- Más filtros (icon only) --}}
                        <div class="flex-shrink-0">
                            <button type="button" class="btn btn-info position-relative"
                                    data-bs-toggle="modal" data-bs-target="#more-filters-modal"
                                    title="Más filtros">
                                <i class="fas fa-sliders"></i>
                                @if($extraFiltersCount > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                        {{ $extraFiltersCount }}
                                    </span>
                                @endif
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if($searchKey || $extraFiltersCount)
                                <a href="{{ route('attention.pending') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif

                        </div>

                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body p-0">
                @if(($attentions ?? collect())->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="attentions-table">
                        <thead class="table-light">
                            <tr>
                                <th width="3%">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </th>
                                <th>Radicado</th>
                                <th>Tipo</th>
                                <th>Ciudadano</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attentions as $attention)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input attention-checkbox"
                                               value="{{ $attention->id }}">
                                    </td>
                                    <td>
                                        <strong class="text-primary">{{ $attention->radicado }}</strong>
                                    </td>
                                    <td>
                                        @if($attention->type)
                                            <span class="badge bg-info-subtle text-info">{{ $attention->type->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $attention->full_name }}</strong>
                                        @if($attention->customer_email)
                                            <br><small class="text-muted">{{ $attention->customer_email }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success">
                                            {{ $attention->status->label() }}
                                        </span>
                                    </td>

                                    <td>
                                        <small class="text-muted">{{ $attention->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('attention.show', $attention->uid) }}">
                                                        Ver detalles
                                                    </a>
                                                </li>
                                                @if($attention->canBeEdited())
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('attention.edit', $attention->uid) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('attention.tracking', $attention->radicado) }}">
                                                        Seguimiento
                                                    </a>
                                                </li>
                                                @if($attention->mails()->count() > 0)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('attention.emails.index', $attention->uid) }}">
                                                            Ver emails
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
                    <i class="fa fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay peticiones pendientes</h5>
                    <p class="text-muted mb-4">
                        @if($searchKey || $typeId || $categoryId || $departmentId || $sedeId || $dateFrom)
                            No se encontraron resultados con los filtros aplicados.
                        @else
                            No tienes peticiones pendientes en este momento.
                        @endif
                    </p>
                    @if($searchKey || $typeId || $categoryId || $departmentId || $sedeId || $dateFrom)
                        <a href="{{ route('attention.pending') }}" class="btn btn-secondary">
                            Ver todos
                        </a>
                    @endif
                </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($attentions->hasPages())
                <div class="card-footer">{{ $attentions->links() }}</div>
            @endif

        </div>

    </div>

    {{-- More Filters Modal --}}
    <div class="modal fade" id="more-filters-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Más filtros</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select class="form-select select2-modal" id="m_type_id">
                            <option value="">Todos los tipos</option>
                            @foreach($types ?? [] as $type)
                                <option value="{{ $type->id }}" {{ ($typeId ?? '') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Categoría</label>
                        <select class="form-select select2-modal" id="m_category_id">
                            <option value="">Todas las categorías</option>
                            @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}" {{ ($categoryId ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Departamento</label>
                        <select class="form-select select2-modal" id="m_department_id">
                            <option value="">Todos los departamentos</option>
                            @foreach($departments ?? [] as $department)
                                <option value="{{ $department->id }}" {{ ($departmentId ?? '') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sede</label>
                        <select class="form-select select2-modal" id="m_sede_id">
                            <option value="">Todas las sedes</option>
                            @foreach($sedes ?? [] as $sede)
                                <option value="{{ $sede->id }}" {{ ($sedeId ?? '') == $sede->id ? 'selected' : '' }}>
                                    {{ $sede->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rango de fechas</label>
                        <div class="input-group">
                            <input type="text" class="form-control daterange-modal" id="m_daterange"
                                   placeholder="Seleccionar rango..."
                                   value="{{ ($dateFrom && $dateTo) ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y').' - '.\Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '' }}"
                                   readonly style="cursor:pointer;">
                            <span class="input-group-text bg-white" style="cursor:pointer;" id="m_daterange_icon">
                                <i class="fas fa-calendar-alt text-muted" style="font-size:0.8rem;"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="modal-filters-apply" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="modal-filters-clear" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Limpiar estos filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk trigger (floating) --}}
    <div id="bulk-toolbar"
         class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none"
         style="z-index: 1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk Modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Se aplicará la acción sobre <strong><span class="bulk-count-label">0</span> peticiones</strong> seleccionados.
                    </p>
                    <div class="mb-3">
                        <label for="bulk-action-select" class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-control select2">
                            <option value="">Seleccionar acción...</option>
                            <option value="close">Cerrar peticiones</option>
                            <option value="change_status">Cambiar estado</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                    <div id="bulk-status-wrapper" class="d-none">
                        <label for="bulk-status-select" class="form-label fw-semibold">Estado</label>
                        <select id="bulk-status-select" class="form-control select2">
                            <option value="">Elegir estado...</option>
                            <option value="received">Recibido</option>
                            <option value="in_process">En proceso</option>
                            <option value="resolved">Resuelto</option>
                            <option value="closed">Cerrado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal" id="bulk-cancel-btn">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // ── Select2 ───────────────────────────────────────────────────────────
    $('.select2').select2({ width: '100%' });
    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });
    $('#bulk-status-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    // ── More-filters modal: init select2 + daterangepicker (once) ────────
    $('#more-filters-modal').on('shown.bs.modal', function () {
        if (!$('#m_type_id').data('select2')) {
            $('.select2-modal').select2({ dropdownParent: $('#more-filters-modal'), width: '100%' });
        }
        if ($('#m_daterange').data('daterangepicker')) { return; }

        var drpOpts = {
            autoUpdateInput: !!$('#date_from').val(),
            locale: {
                cancelLabel: 'Limpiar', applyLabel: 'Aplicar', format: 'DD/MM/YYYY', separator: ' - ',
                daysOfWeek: ['Do','Lu','Ma','Mi','Ju','Vi','Sa'],
                monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
                firstDay: 1,
            },
            ranges: {
                'Hoy':            [moment(), moment()],
                'Ayer':           [moment().subtract(1,'days'), moment().subtract(1,'days')],
                'Últimos 7 días': [moment().subtract(6,'days'), moment()],
                'Últimos 30 días':[moment().subtract(29,'days'), moment()],
                'Este mes':       [moment().startOf('month'), moment().endOf('month')],
                'Mes pasado':     [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')],
            },
        };
        if ($('#date_from').val()) {
            drpOpts.startDate = moment($('#date_from').val(), 'YYYY-MM-DD');
            drpOpts.endDate   = moment($('#date_to').val(),   'YYYY-MM-DD');
        }

        $('#m_daterange').daterangepicker(drpOpts);

        $('#m_daterange').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            $('#date_from').val(picker.startDate.format('YYYY-MM-DD'));
            $('#date_to').val(picker.endDate.format('YYYY-MM-DD'));
        });

        $('#m_daterange').on('cancel.daterangepicker', function () {
            $(this).val('');
            $('#date_from').val('');
            $('#date_to').val('');
        });

        $('#m_daterange_icon').on('click', function () { $('#m_daterange').trigger('click'); });
    });

    // ── Apply extra filters: copy modal selects → hidden inputs → submit ──
    $('#modal-filters-apply').on('click', function () {
        $('#h_type_id').val($('#m_type_id').val());
        $('#h_category_id').val($('#m_category_id').val());
        $('#h_department_id').val($('#m_department_id').val());
        $('#h_sede_id').val($('#m_sede_id').val());
        $('#more-filters-modal').modal('hide');
        $('#pending-filter-form').submit();
    });

    // ── Clear extra filters ───────────────────────────────────────────────
    $('#modal-filters-clear').on('click', function () {
        $('#m_type_id').val('').trigger('change');
        $('#m_category_id').val('').trigger('change');
        $('#m_department_id').val('').trigger('change');
        $('#m_sede_id').val('').trigger('change');
        $('#m_daterange').val('');
        $('#h_type_id, #h_category_id, #h_department_id, #h_sede_id, #date_from, #date_to').val('');
        $('#pending-filter-form').submit();
    });

    // ── Bulk actions ──────────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.attention-checkbox' });

    $('#bulk-action-select').on('change', function () {
        $('#bulk-status-wrapper').toggleClass('d-none', this.value !== 'change_status');
    });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-status-select').val('').trigger('change');
        $('#bulk-status-wrapper').addClass('d-none');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();
        const value  = $('#bulk-status-select').val();

        if (!action) { toastr.warning('Selecciona una acción antes de continuar.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una peticiones.'); return; }
        if (action === 'change_status' && !value) { toastr.warning('Selecciona un estado.'); return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('attention.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, value, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' peticiones actualizado(s) correctamente.');
                setTimeout(() => location.reload(), 1000);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar la acción.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });
});
</script>
@endpush
