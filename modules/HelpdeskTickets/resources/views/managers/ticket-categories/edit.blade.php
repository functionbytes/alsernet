@extends('layouts.theme')

@section('content')

    <div class="card w-100">

        <form id="formCategory" method="POST" action="{{ route('manager.helpdesk.settings.ticket-categories.update', $category->id) }}">

            {{ csrf_field() }}
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Editar categoría</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Modifica la configuración de esta categoría. Los cambios afectarán cómo se clasifican y asignan los tickets.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted mb-3">Define el nombre, identificador y características visuales de la categoría.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required placeholder="Ej: Soporte Técnico">
                            <small class="form-text text-muted">Nombre visible de la categoría</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Slug (Identificador)
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="slug" class="form-control bg-light" value="{{ old('slug', $category->slug) }}" readonly>
                            <small class="form-text text-muted">El slug no se puede modificar una vez creado</small>
                            @error('slug')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Icono (Font Awesome)</label>
                            <input type="text" name="icon" class="form-control" value="{{ old('icon', $category->icon) }}" placeholder="Ej: fas fa-headset">
                            <small class="form-text text-muted">Usa el nombre del icono de <a href="https://fontawesome.com/icons" target="_blank">Font Awesome 6</a></small>
                            @error('icon')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color" class="form-control form-control-color" value="{{ old('color', $category->color) }}" id="colorPicker">
                                <input type="text" id="colorHex" class="form-control" value="{{ old('color', $category->color) }}" readonly style="max-width: 120px;">
                                <div id="colorPreview" class="border rounded" style="width: 50px; height: 50px; background-color: {{ old('color', $category->color) }};"></div>
                            </div>
                            <small class="form-text text-muted">Color de identificación de la categoría</small>
                            @error('color')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Colores sugeridos</label>
                            <small class="d-block text-muted mb-2">Haz clic en cualquier color para aplicarlo rápidamente</small>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm color-preset" data-color="#90bb13" style="background-color: #90bb13; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#13C672" style="background-color: #13C672; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#FA896B" style="background-color: #FA896B; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#FEC90F" style="background-color: #FEC90F; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#539BFF" style="background-color: #539BFF; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#8E44AD" style="background-color: #8E44AD; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#E74C3C" style="background-color: #E74C3C; width: 40px; height: 40px; border-radius: 8px;"></button>
                                <button type="button" class="btn btn-sm color-preset" data-color="#95A5A6" style="background-color: #95A5A6; width: 40px; height: 40px; border-radius: 8px;"></button>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Descripción de la categoría">{{ old('description', $category->description) }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre esta categoría</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- SLA and Assignment -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración de SLA y asignación</h6>
                        <p class="text-muted mb-3">Define la política de tiempo de respuesta y los grupos asignados a esta categoría.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Política SLA Por defecto</label>
                            <select name="default_sla_policy" class="form-select">
                                <option value="">Sin política SLA</option>
                                @foreach($slaPolicies ?? [] as $policy)
                                    <option value="{{ $policy->id }}" {{ old('default_sla_policy', $category->default_sla_policy) == $policy->id ? 'selected' : '' }}>
                                        {{ $policy->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Política de tiempo de respuesta para esta categoría</small>
                            @error('default_sla_policy')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Grupos asignados</label>
                            <select name="groups[]" class="form-select" multiple size="5">
                                @foreach($groups ?? [] as $group)
                                    <option value="{{ $group->id }}" {{ in_array($group->id, old('groups', $category->groups ?? [])) ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Grupos que pueden gestionar esta categoría (mantén Ctrl/Cmd para seleccionar múltiples)</small>
                            @error('groups')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Custom Form Fields Editor -->
                    <div class="col-12">
                        <hr class="my-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h6 class="mb-0 fw-semibold">Campos personalizados del formulario</h6>
                            <button type="button" class="btn btn-sm btn-primary" id="btnAddField">
                                <i class="fas fa-plus me-1"></i> Agregar campo
                            </button>
                        </div>
                        <p class="text-muted mb-3">Define campos adicionales que se muestran en el widget al crear un ticket de esta categoría.</p>
                    </div>

                    <div class="col-12 mb-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle" id="categoryFieldsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:30px"></th>
                                        <th>Etiqueta</th>
                                        <th>Clave</th>
                                        <th>Tipo</th>
                                        <th>Ancho</th>
                                        <th>¿Requerido?</th>
                                        <th style="width:80px">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="fieldsList">
                                    <tr id="noFieldsRow">
                                        <td colspan="7" class="text-center text-muted py-3">
                                            <i class="fas fa-inbox me-1"></i> No hay campos personalizados. Haz clic en "Agregar campo".
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Campos requeridos</label>
                            <select name="required_fields[]" class="form-select" multiple size="6">
                                <option value="subject" {{ in_array('subject', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Asunto</option>
                                <option value="description" {{ in_array('description', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Descripción</option>
                                <option value="priority" {{ in_array('priority', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Prioridad</option>
                                <option value="customer_email" {{ in_array('customer_email', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Email del cliente</option>
                                <option value="customer_phone" {{ in_array('customer_phone', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Teléfono del cliente</option>
                                <option value="attachments" {{ in_array('attachments', old('required_fields', $category->required_fields ?? [])) ? 'selected' : '' }}>Archivos adjuntos</option>
                            </select>
                            <small class="form-text text-muted">Campos obligatorios al crear un ticket de esta categoría</small>
                            @error('required_fields')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Respuestas predefinidas</label>
                            <select name="canned_replies[]" class="form-select" multiple size="6">
                                @foreach($cannedReplies ?? [] as $reply)
                                    <option value="{{ $reply->id }}" {{ in_array($reply->id, old('canned_replies', $category->canned_replies ?? [])) ? 'selected' : '' }}>
                                        {{ $reply->title }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Respuestas predefinidas disponibles para esta categoría</small>
                            @error('canned_replies')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Opciones</h6>
                        <p class="text-muted mb-3">Configura el comportamiento de la categoría en el sistema.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activeCheck">
                                    <strong>Categoría activa</strong>
                                    <small class="d-block text-muted">Permite que esta categoría esté disponible para crear tickets.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_default" value="0">
                                <input type="checkbox" name="is_default" class="form-check-input" id="defaultCheck" value="1" {{ old('is_default', $category->is_default) ? 'checked' : '' }}>
                                <label class="form-check-label" for="defaultCheck">
                                    <strong>Categoría Por defecto</strong>
                                    <small class="d-block text-muted">Se selecciona automáticamente al crear nuevos tickets.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                    Guardar
                </button>
                <a href="{{ route('manager.helpdesk.settings.ticket-categories.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

    <!-- Modal de campo personalizado -->
    <div class="modal fade" id="fieldModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fieldModalTitle">Agregar campo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="fieldId">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Tipo de campo <span class="text-danger">*</span></label>
                            <select id="fieldType" class="form-select">
                                <option value="text">Texto corto</option>
                                <option value="textarea">Texto largo</option>
                                <option value="email">Email</option>
                                <option value="phone">Teléfono</option>
                                <option value="number">Número</option>
                                <option value="select">Lista desplegable</option>
                                <option value="radio">Opción única (radio)</option>
                                <option value="checkbox">Opciones múltiples (checkbox)</option>
                                <option value="date">Fecha</option>
                                <option value="file">Archivo</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Ancho</label>
                            <select id="fieldWidth" class="form-select">
                                <option value="full">Completo (100%)</option>
                                <option value="half">Mitad (50%)</option>
                                <option value="third">Tercio (33%)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label">Etiqueta <span class="text-danger">*</span></label>
                            <input type="text" id="fieldLabel" class="form-control" placeholder="Ej: Número de orden">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Clave (key)</label>
                            <input type="text" id="fieldKey" class="form-control" placeholder="Auto desde etiqueta">
                            <small class="text-muted">Solo letras, números y guion bajo</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Placeholder</label>
                            <input type="text" id="fieldPlaceholder" class="form-control" placeholder="Texto de ayuda dentro del campo">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Texto de ayuda</label>
                            <input type="text" id="fieldHelpText" class="form-control" placeholder="Descripción que aparece bajo el campo">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="checkbox" id="fieldRequired" class="form-check-input" value="1">
                                <label class="form-check-label" for="fieldRequired">Campo obligatorio</label>
                            </div>
                        </div>

                        <!-- Opciones (solo para select/radio/checkbox) -->
                        <div class="col-12" id="optionsSection" style="display:none">
                            <label class="form-label fw-semibold">Opciones <span class="text-danger">*</span></label>
                            <div id="optionsList"></div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btnAddOption">
                                <i class="fas fa-plus me-1"></i> Agregar opción
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="btnSaveField">Guardar campo</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    // ─── Constants ───────────────────────────────────────────────────────────
    const ROUTES = {
        fieldsIndex:   @json(route('manager.helpdesk.settings.ticket-categories.fields.index', $category)),
        fieldsStore:   @json(route('manager.helpdesk.settings.ticket-categories.fields.store', $category)),
        fieldsReorder: @json(route('manager.helpdesk.settings.ticket-categories.fields.reorder', $category)),
    };

    const TYPE_LABELS = {
        text: 'Texto corto', textarea: 'Texto largo', email: 'Email',
        phone: 'Teléfono', number: 'Número', select: 'Lista desplegable',
        radio: 'Opción única', checkbox: 'Múltiple', date: 'Fecha', file: 'Archivo',
    };

    const WIDTH_LABELS = { full: '100%', half: '50%', third: '33%' };

    const TYPES_WITH_OPTIONS = ['select', 'radio', 'checkbox'];

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // ─── Color picker ────────────────────────────────────────────────────────
    $('#colorPicker').on('input', function () {
        const color = $(this).val();
        $('#colorHex').val(color);
        $('#colorPreview').css('background-color', color);
    });

    $('.color-preset').on('click', function () {
        const color = $(this).data('color');
        $('#colorPicker').val(color);
        $('#colorHex').val(color);
        $('#colorPreview').css('background-color', color);
    });

    // ─── Flash messages ───────────────────────────────────────────────────────
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Categoría actualizada');
    @endif
    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // ─── Sortable ─────────────────────────────────────────────────────────────
    $('#fieldsList').sortable({
        handle: '.drag-handle',
        items: 'tr:not(#noFieldsRow)',
        update: function () {
            const items = [];
            $('#fieldsList tr[data-field-id]').each(function (i) {
                items.push({ id: $(this).data('field-id'), sort_order: i + 1 });
            });
            $.post(ROUTES.fieldsReorder, { items });
        },
    });

    // ─── Load fields ──────────────────────────────────────────────────────────
    function loadFields() {
        $.getJSON(ROUTES.fieldsIndex, function (res) {
            const $list = $('#fieldsList');
            $list.empty();

            if (!res.data || res.data.length === 0) {
                $list.html(
                    '<tr id="noFieldsRow">' +
                    '<td colspan="7" class="text-center text-muted py-3">' +
                    '<i class="fas fa-inbox me-1"></i> No hay campos personalizados. Haz clic en "Agregar campo".' +
                    '</td></tr>'
                );
                return;
            }

            res.data.forEach(function (field) {
                $list.append(buildFieldRow(field));
            });
        });
    }

    function buildFieldRow(field) {
        const required = field.is_required
            ? '<span class="badge bg-danger-subtle text-danger">Sí</span>'
            : '<span class="badge bg-secondary-subtle text-secondary">No</span>';

        const $editLink   = $('<a class="dropdown-item btn-edit-field" href="#">').attr('data-id', field.id).text('Editar');
        const $deleteLink = $('<a class="dropdown-item btn-delete-field" href="#">').attr('data-id', field.id).text('Eliminar');
        const $dropdown   = $('<div class="dropdown">').append(
            $('<button class="btn btn-sm btn-light" data-bs-toggle="dropdown">').append($('<i class="fas fa-ellipsis-vertical">')),
            $('<ul class="dropdown-menu dropdown-menu-end">').append(
                $('<li>').append($editLink),
                $('<li>').append($('<hr class="dropdown-divider">')),
                $('<li>').append($deleteLink)
            )
        );

        return $('<tr>').attr('data-field-id', field.id).append(
            $('<td class="drag-handle" style="cursor:grab">').append($('<i class="fas fa-grip-vertical text-muted">')),
            $('<td>').text(field.label),
            $('<td>').append($('<code>').text(field.key)),
            $('<td>').append($('<span class="badge bg-light text-dark border">').text(TYPE_LABELS[field.type] || field.type)),
            $('<td>').text(WIDTH_LABELS[field.width] || field.width),
            $('<td>').html(required),
            $('<td>').append($dropdown)
        );
    }

    loadFields();

    // ─── Modal instance ───────────────────────────────────────────────────────
    const fieldModal = new bootstrap.Modal(document.getElementById('fieldModal'));

    // ─── Open modal (add) ─────────────────────────────────────────────────────
    $('#btnAddField').on('click', function () {
        resetModal();
        $('#fieldModalTitle').text('Agregar campo');
        fieldModal.show();
    });

    // ─── Auto-generate key from label ─────────────────────────────────────────
    $('#fieldLabel').on('input', function () {
        if ($('#fieldId').val()) return; // don't overwrite on edit
        const key = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
        $('#fieldKey').val(key);
    });

    // ─── Show/hide options section ────────────────────────────────────────────
    $('#fieldType').on('change', function () {
        const needsOptions = TYPES_WITH_OPTIONS.includes($(this).val());
        $('#optionsSection').toggle(needsOptions);
    });

    // ─── Add option row ───────────────────────────────────────────────────────
    $('#btnAddOption').on('click', function () {
        appendOptionRow('', '');
    });

    function appendOptionRow(label, value) {
        const $row = $('<div class="d-flex gap-2 mb-2">').append(
            $('<input class="form-control form-control-sm option-label" placeholder="Etiqueta">').val(label),
            $('<input class="form-control form-control-sm option-value" style="max-width:140px" placeholder="Valor (key)">').val(value),
            $('<button type="button" class="btn btn-sm btn-outline-danger btn-remove-option">').append($('<i class="fas fa-times">'))
        );
        $('#optionsList').append($row);
    }

    $(document).on('click', '.btn-remove-option', function () {
        $(this).closest('.d-flex').remove();
    });

    // ─── Save field ───────────────────────────────────────────────────────────
    $('#btnSaveField').on('click', function () {
        const fieldId = $('#fieldId').val();
        const type    = $('#fieldType').val();

        const options = TYPES_WITH_OPTIONS.includes(type) ? collectOptions() : [];

        const payload = {
            type:        type,
            label:       $('#fieldLabel').val().trim(),
            key:         $('#fieldKey').val().trim(),
            placeholder: $('#fieldPlaceholder').val().trim(),
            help_text:   $('#fieldHelpText').val().trim(),
            is_required: $('#fieldRequired').is(':checked') ? 1 : 0,
            width:       $('#fieldWidth').val(),
            options:     options,
        };

        const url    = fieldId ? ROUTES.fieldsIndex + '/' + fieldId : ROUTES.fieldsStore;
        const method = fieldId ? 'PATCH' : 'POST';

        $.ajax({
            url,
            method,
            data: JSON.stringify(payload),
            contentType: 'application/json',
            success: function () {
                toastr.success(fieldId ? 'Campo actualizado.' : 'Campo agregado.');
                fieldModal.hide();
                loadFields();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    const msg = Object.values(errors).flat().join('<br>');
                    toastr.error(msg || 'Verifica los datos del campo.', 'Error de validación');
                } else {
                    toastr.error('Ocurrió un error al guardar el campo.', 'Error');
                }
            },
        });
    });

    function collectOptions() {
        const opts = [];
        $('#optionsList .d-flex').each(function () {
            const label = $(this).find('.option-label').val().trim();
            const value = $(this).find('.option-value').val().trim();
            if (label || value) {
                opts.push({ label, value });
            }
        });
        return opts;
    }

    // ─── Edit field ───────────────────────────────────────────────────────────
    $(document).on('click', '.btn-edit-field', function (e) {
        e.preventDefault();
        const fieldId = $(this).data('id');

        $.getJSON(ROUTES.fieldsIndex, function (res) {
            const field = (res.data || []).find(function (f) { return f.id === fieldId; });
            if (!field) { return; }

            resetModal();
            $('#fieldModalTitle').text('Editar campo');
            $('#fieldId').val(field.id);
            $('#fieldType').val(field.type).trigger('change');
            $('#fieldWidth').val(field.width);
            $('#fieldLabel').val(field.label);
            $('#fieldKey').val(field.key);
            $('#fieldPlaceholder').val(field.placeholder || '');
            $('#fieldHelpText').val(field.help_text || '');
            $('#fieldRequired').prop('checked', !!field.is_required);

            if (TYPES_WITH_OPTIONS.includes(field.type) && Array.isArray(field.options)) {
                field.options.forEach(function (opt) {
                    appendOptionRow(opt.label, opt.value);
                });
            }

            fieldModal.show();
        });
    });

    // ─── Delete field ─────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete-field', function (e) {
        e.preventDefault();
        const fieldId = $(this).data('id');

        if (!confirm('¿Estás seguro de que deseas eliminar este campo?')) { return; }

        $.ajax({
            url: ROUTES.fieldsIndex + '/' + fieldId,
            method: 'DELETE',
            success: function () {
                toastr.success('Campo eliminado.');
                loadFields();
            },
            error: function () {
                toastr.error('No se pudo eliminar el campo.', 'Error');
            },
        });
    });

    // ─── Reset modal ──────────────────────────────────────────────────────────
    function resetModal() {
        $('#fieldId').val('');
        $('#fieldType').val('text').trigger('change');
        $('#fieldWidth').val('full');
        $('#fieldLabel').val('');
        $('#fieldKey').val('');
        $('#fieldPlaceholder').val('');
        $('#fieldHelpText').val('');
        $('#fieldRequired').prop('checked', false);
        $('#optionsList').empty();
        $('#optionsSection').hide();
    }
});
</script>
@endsection
