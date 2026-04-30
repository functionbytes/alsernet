@extends('layouts.theme')

@section('title', 'Tipos de campo')

@push('css')
<style>
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Tipos de campo'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div>
                    <h5 class="mb-1 fw-bold">Tipos de campo</h5>
                    <p class="small mb-0 text-muted">Configura los tipos de campo disponibles en el constructor de formularios</p>
                </div>
            </div>

            {{-- Stats --}}
            @php
                $allTypes      = $groups->flatten();
                $totalCount    = $allTypes->count();
                $enabledCount  = $allTypes->where('is_enabled', true)->count();
                $disabledCount = $totalCount - $enabledCount;
                $groupCount    = $groups->count();
            @endphp
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total tipos</h6>
                                <h4 class="mb-1 fw-bold">{{ $totalCount }}</h4>
                                <small class="text-muted">Tipos configurados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Habilitados</h6>
                                <h4 class="mb-1 fw-bold">{{ $enabledCount }}</h4>
                                <small class="text-muted">Disponibles en el constructor</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Deshabilitados</h6>
                                <h4 class="mb-1 fw-bold ">{{ $disabledCount }}</h4>
                                <small class="text-muted">Ocultos del constructor</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Grupos</h6>
                                <h4 class="mb-1 fw-bold">{{ $groupCount }}</h4>
                                <small class="text-muted">Grupos de tipos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.forms.field-types.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por etiqueta o tipo..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" >
                            <select name="status" class="form-select h-100">
                                <option value="">Todos los estados</option>
                                <option value="enabled"  {{ request('status') === 'enabled'  ? 'selected' : '' }}>Habilitado</option>
                                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Deshabilitado</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('settings.forms.field-types.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($groups->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-sliders fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search') || request('status'))
                                    No se encontraron tipos de campo
                                @else
                                    No hay tipos de campo configurados
                                @endif
                            </h6>
                            <p class="text-muted mb-0">
                                @if(request('search') || request('status'))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Los tipos de campo se generan automáticamente al instalar el módulo
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="field-types-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Tipo</th>
                                    <th>Grupo</th>
                                    <th>Etiqueta</th>
                                    <th>Clase CSS</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center" width="60">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groups as $groupName => $types)
                                    @foreach ($types as $type)
                                        <tr data-id="{{ $type->id }}"
                                            data-label="{{ $type->label }}"
                                            data-icon="{{ $type->icon }}"
                                            data-css-class="{{ $type->default_css_class }}"
                                            data-placeholder="{{ $type->default_placeholder }}"
                                            data-group-name="{{ $type->group_name }}"
                                            data-is-enabled="{{ $type->is_enabled ? '1' : '0' }}">
                                            <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $type->id }}"></td>
                                            <td><code class="small">{{ $type->type }}</code></td>
                                            <td><span class="badge bg-light text-dark border">{{ $type->group_name }}</span></td>
                                            <td>{{ $type->label }}</td>
                                            <td><code class="small text-muted">{{ $type->default_css_class ?: '—' }}</code></td>
                                            <td class="text-center">
                                                @if ($type->is_enabled)
                                                    <span class="badge bg-success-subtle text-success status-badge">Habilitado</span>
                                                @else
                                                    <span class="badge bg-danger-subtle  status-badge">Deshabilitado</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('settings.forms.field-types.edit', $type) }}">Editar</a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item btn-toggle" href="javascript:void(0)">
                                                                {{ $type->is_enabled ? 'Deshabilitar' : 'Habilitar' }}
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Floating bulk toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" >
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-labelledby="bulk-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulk-modal-label">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold" for="bulk-action-select">Selecciona una acción para los tipos seleccionados</label>
                    <select id="bulk-action-select" class="form-select">
                        <option value="">Seleccionar acción...</option>
                        <option value="enable">Habilitar</option>
                        <option value="disable">Deshabilitar</option>
                    </select>
                </div>
                <div class="modal-footer d-flex flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="bulk-apply-btn">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit modal --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar tipo de campo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="editLabel">Etiqueta</label>
                        <input type="text" class="form-control" id="editLabel" name="label" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="editIcon">Icono</label>
                        <div class="input-group">
                            <span class="input-group-text" id="iconPreviewAddon"><i id="iconPreview"></i></span>
                            <input type="text" class="form-control" id="editIcon" name="icon" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="editCssClass">Clase CSS por defecto</label>
                        <input type="text" class="form-control" id="editCssClass" name="default_css_class">
                        <div class="form-text">Clases CSS que se aplicarán al campo al crearlo</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="editPlaceholder">Placeholder por defecto</label>
                        <input type="text" class="form-control" id="editPlaceholder" name="default_placeholder">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="editGroupName">Grupo</label>
                        <input type="text" class="form-control" id="editGroupName" name="group_name">
                        <div class="form-text">Nombre del grupo en el constructor</div>
                    </div>
                    <div class="mb-0">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="editIsEnabled" name="is_enabled">
                            <label class="form-check-label" for="editIsEnabled">Habilitado</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="btnSaveEdit">Guardar cambios</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    const baseUrl    = '/panel/settings/forms/field-types/';
    const csrfToken  = $('meta[name="csrf-token"]').attr('content');

    // ── Bulk actions ──────────────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action)    { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un tipo.'); return; }

        $(this).prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('settings.forms.field-types.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: csrfToken }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' tipo(s) actualizados.');
                setTimeout(() => location.reload(), 800);
            },
            error: function () {
                toastr.error('Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    // ── Drag-and-drop reorder ─────────────────────────────────────────────────
    $('#field-types-table tbody').sortable({
        handle: '.drag-handle',
        stop: function () {
            const items = [];
            $('#field-types-table tbody tr').each(function (index) {
                items.push({ id: $(this).data('id'), sort_order: index + 1 });
            });
            $.ajax({
                url: '{{ route('settings.forms.field-types.reorder') }}',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                contentType: 'application/json',
                data: JSON.stringify({ items }),
                success: function () { toastr.success('Orden actualizado'); },
                error: function ()  { toastr.error('Error al guardar el orden'); },
            });
        },
    });

    // ── Edit modal ────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-edit', function () {
        const row   = $(this).closest('tr');
        const modal = $('#editModal');

        modal.data('editing-id', row.data('id'));
        $('#editLabel').val(row.data('label'));
        $('#editIcon').val(row.data('icon'));
        $('#iconPreview').attr('class', row.data('icon'));
        $('#editCssClass').val(row.data('css-class'));
        $('#editPlaceholder').val(row.data('placeholder'));
        $('#editGroupName').val(row.data('group-name'));
        $('#editIsEnabled').prop('checked', row.data('is-enabled') == '1');

        modal.modal('show');
    });

    $('#editIcon').on('input', function () {
        $('#iconPreview').attr('class', $(this).val());
    });

    $('#btnSaveEdit').on('click', function () {
        const modal = $('#editModal');
        const id    = modal.data('editing-id');
        const btn   = $(this);

        btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: baseUrl + id,
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: {
                label:               $('#editLabel').val(),
                icon:                $('#editIcon').val(),
                default_css_class:   $('#editCssClass').val(),
                default_placeholder: $('#editPlaceholder').val(),
                group_name:          $('#editGroupName').val(),
                is_enabled:          $('#editIsEnabled').is(':checked') ? 1 : 0,
            },
            success: function (res) {
                toastr.success(res.message ?? 'Tipo de campo actualizado');

                const row     = $('#field-types-table tbody tr[data-id="' + id + '"]');
                const enabled = $('#editIsEnabled').is(':checked');

                row.data('label',       $('#editLabel').val());
                row.data('icon',        $('#editIcon').val());
                row.data('css-class',   $('#editCssClass').val());
                row.data('placeholder', $('#editPlaceholder').val());
                row.data('group-name',  $('#editGroupName').val());
                row.data('is-enabled',  enabled ? '1' : '0');

                row.find('.icon-preview i').attr('class', $('#editIcon').val());
                row.find('td:eq(5)').text($('#editLabel').val());
                row.find('td:eq(6) code').text($('#editCssClass').val() || '—');
                row.find('td:eq(4) span').text($('#editGroupName').val());

                const badge = row.find('.status-badge');
                badge.attr('class', 'badge status-badge ' + (enabled ? 'bg-success-subtle text-success' : 'bg-danger-subtle '));
                badge.text(enabled ? 'Habilitado' : 'Deshabilitado');
                row.find('.btn-toggle').text(enabled ? 'Deshabilitar' : 'Habilitar');

                modal.modal('hide');
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        toastr.error(messages[0]);
                    });
                } else {
                    toastr.error('Error al guardar los cambios');
                }
            },
            complete: function () {
                btn.prop('disabled', false).text('Guardar cambios');
            },
        });
    });

    // ── Toggle enable/disable ─────────────────────────────────────────────────
    $(document).on('click', '.btn-toggle', function () {
        const row = $(this).closest('tr');
        const id  = row.data('id');

        $.ajax({
            url: baseUrl + id + '/toggle',
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                toastr.success(res.message ?? 'Estado actualizado');

                const enabled = res.is_enabled;
                row.data('is-enabled', enabled ? '1' : '0');

                const badge = row.find('.status-badge');
                badge.attr('class', 'badge status-badge ' + (enabled ? 'bg-success-subtle text-success' : 'bg-danger-subtle '));
                badge.text(enabled ? 'Habilitado' : 'Deshabilitado');
                row.find('.btn-toggle').text(enabled ? 'Deshabilitar' : 'Habilitar');
            },
            error: function () {
                toastr.error('Error al cambiar el estado');
            },
        });
    });
});
</script>
@endpush
