@extends('layouts.theme')

@section('title', 'Reglas de auto-respuesta')

@section('page_header')
    @include('core::components.card', ['title' => 'Reglas de auto-respuesta'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Reglas de auto-respuesta</h5>
                        <p class="small mb-0 text-muted">Responde automáticamente a las reseñas según condiciones configuradas</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('reviews.autoreply.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear regla
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Reglas configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                <small class="text-muted">En ejecución</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                <small class="text-muted">Deshabilitadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                    <div class="flex-fill">
                        <div class="input-group h-100">
                            <span class="input-group-text bg-white border-end-1">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" id="searchRule" class="form-control border-start-0 ps-0"
                                   placeholder="Buscar por nombre...">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                <div id="rules-loading" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <i class="fas fa-spinner fa-spin fs-4 text-muted mb-2"></i>
                        <p class="text-muted mb-0">Cargando reglas...</p>
                    </div>
                </div>

                <div id="rules-empty" class="text-center py-5 d-none">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-robot fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay reglas configuradas</h6>
                        <p class="text-muted mb-0">Crea una regla para comenzar a responder automáticamente</p>
                    </div>
                </div>

                <div id="rules-table-wrapper" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="rules-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Condición</th>
                                    <th>Plantilla</th>
                                    <th>Retardo</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="rules-tbody"></tbody>
                        </table>
                    </div>

                    <div id="noResults" class="text-center py-5 d-none">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-search fs-7"></i>
                            </div>
                            <h6 class="mb-1">No se encontraron reglas</h6>
                            <p class="text-muted mb-0">No hay resultados para los criterios de búsqueda</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Bulk toolbar --}}
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> regla(s)</strong>.</p>
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
<script>
(function () {
    'use strict';

    var rulesUrl  = '{{ route("reviews.autoreply.index") }}';
    var baseUrl   = '{{ rtrim(route("reviews.autoreply.index"), "/") }}';
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Render condition summary
    function conditionSummary(conditions) {
        if (! conditions) { return '<span class="text-muted">—</span>'; }
        var parts = [];
        if (conditions.min_rating != null || conditions.max_rating != null) {
            var min = conditions.min_rating != null ? conditions.min_rating + '★' : '1★';
            var max = conditions.max_rating != null ? conditions.max_rating + '★' : '5★';
            parts.push('Rating: ' + min + '–' + max);
        }
        if (conditions.has_comment === true)  { parts.push('Con comentario'); }
        if (conditions.has_comment === false) { parts.push('Sin comentario'); }
        return parts.length ? parts.join(', ') : '<span class="text-muted">Sin condiciones</span>';
    }

    // Client-side search
    $('#searchRule').on('input', function () {
        var search  = $(this).val().toLowerCase();
        var visible = 0;

        $('#rules-tbody tr').each(function () {
            var name  = $(this).find('td:eq(1)').text().toLowerCase();
            var match = !search || name.includes(search);
            $(this).toggleClass('d-none', !match);
            if (match) { visible++; }
        });

        $('#noResults').toggleClass('d-none', visible > 0);
    });

    // Load rules
    function loadRules() {
        $('#rules-loading').removeClass('d-none');
        $('#rules-table-wrapper, #rules-empty').addClass('d-none');

        $.ajax({
            url: rulesUrl,
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                $('#rules-loading').addClass('d-none');

                if (! res.data || res.data.length === 0) {
                    $('#rules-empty').removeClass('d-none');
                    return;
                }

                var rows = '';
                res.data.forEach(function (rule) {
                    var toggleChecked = rule.is_active ? 'checked' : '';
                    var templateName  = rule.template ? $('<div>').text(rule.template.name).html() : '<span class="text-muted">—</span>';
                    var delay         = rule.delay_minutes > 0 ? rule.delay_minutes + ' min' : 'Inmediato';

                    rows += '<tr data-id="' + rule.id + '">';
                    rows += '<td><input type="checkbox" class="form-check-input bulk-checkbox" value="' + rule.id + '"></td>';
                    rows += '<td class="fw-semibold">' + $('<div>').text(rule.name).html() + '</td>';
                    rows += '<td><p>' + conditionSummary(rule.conditions) + '</p></td>';
                    rows += '<td><p>' + templateName + '</p></td>';
                    rows += '<td><p>' + delay + '</p></td>';
                    rows += '<td class="text-center">';
                    rows += '<div class="dropdown">';
                    rows += '<a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-vertical"></i></a>';
                    rows += '<ul class="dropdown-menu dropdown-menu-end">';
                    rows += '<li><a class="dropdown-item" href="' + baseUrl + '/' + rule.id + '/edit">Editar</a></li>';
                    rows += '<li><a class="dropdown-item toggle-rule-link" href="javascript:void(0)" data-id="' + rule.id + '">' + (rule.is_active ? 'Desactivar' : 'Activar') + '</a></li>';
                    rows += '<li><hr class="dropdown-divider"></li>';
                    rows += '<li><a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#delete-modal" data-url="' + baseUrl + '/' + rule.id + '" data-title="Eliminar: ' + $('<div>').text(rule.name).html() + '">Eliminar</a></li>';
                    rows += '</ul></div>';
                    rows += '</td></tr>';
                });

                $('#rules-tbody').html(rows);
                $('#select-all').prop('checked', false).prop('indeterminate', false);
                $('#bulk-toolbar').addClass('d-none').find('[data-bulk-count]').text(0);
                $('#rules-table-wrapper').removeClass('d-none');
                $('#noResults').addClass('d-none');
                $('#searchRule').trigger('input');
            },
            error: function () {
                $('#rules-loading').addClass('d-none');
                toastr.error('Error al cargar las reglas.');
            },
        });
    }

    // Toggle via switch
    $(document).on('change', '.toggle-rule', function () {
        var id      = $(this).data('id');
        var $toggle = $(this).prop('disabled', true);

        $.ajax({
            url:     baseUrl + '/' + id + '/toggle',
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                $toggle.prop('disabled', false).prop('checked', res.data.is_active);
                toastr.success(res.message);
                loadRules();
            },
            error: function () {
                $toggle.prop('disabled', false).prop('checked', ! $toggle.is(':checked'));
                toastr.error('Error al cambiar el estado de la regla.');
            },
        });
    });

    // Toggle via dropdown link
    $(document).on('click', '.toggle-rule-link', function () {
        var id = $(this).data('id');

        $.ajax({
            url:     baseUrl + '/' + id + '/toggle',
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                toastr.success(res.message);
                loadRules();
            },
            error: function () {
                toastr.error('Error al cambiar el estado de la regla.');
            },
        });
    });

    // Delete modal handler
    $('#delete-modal').on('show.bs.modal', function (e) {
        var $trigger = $(e.relatedTarget);
        $(this).find('.modal-title').text($trigger.data('title'));
        $('#delete-form').attr('action', $trigger.data('url'));
    });

    // Init
    loadRules();
}());

$(document).ready(function () {
    var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

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
        if (!ids.length) { toastr.warning('Selecciona al menos una regla.'); return; }
        

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('reviews.autoreply.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' regla(s) actualizadas.');
                setTimeout(function () { location.reload(); }, 800);
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

@include('core::components.delete')
