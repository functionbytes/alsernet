@php
    $isEdit = !is_null($shortcode);
    $action = $isEdit
        ? route('settings.shortcodes.update', $shortcode->id)
        : route('settings.shortcodes.store');
    $existingFields = $isEdit ? ($shortcode->config_fields ?? []) : [];
@endphp

<div class="card">

    <form action="{{ $action }}" method="POST" id="shortcodeForm">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="card-header border-bottom p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">
                        {{ $isEdit ? 'Editar shortcode: ' . $shortcode->name : 'Nuevo shortcode' }}
                    </h5>
                    <p class="mb-0 text-muted small">
                        {{ $isEdit ? 'Modifica los datos del shortcode' : 'Define un nuevo componente dinámico para el editor de páginas' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="card-body">

            @include('core::components.alerts')

            <div class="row">

                {{-- ── Información básica ──────────────────────────────────── --}}
                <div class="col-12">
                    <h6 class="fw-bold mb-0">Información básica</h6>
                    <p class="text-muted mb-4">Define el nombre, clave técnica e icono del shortcode.</p>
                </div>

                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="name" class="control-label col-form-label">
                            Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name"
                               value="{{ old('name', $shortcode?->name) }}"
                               required maxlength="150" autofocus>
                        @error('name')
                            <span class="field-validation-error">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="key" class="control-label col-form-label">
                            Clave técnica <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('key') is-invalid @enderror"
                               id="key" name="key"
                               value="{{ old('key', $shortcode?->key) }}"
                               required maxlength="100"
                               placeholder="mi-shortcode" pattern="[a-z0-9\-]+">
                        <small class="form-text text-muted">
                            Solo letras minúsculas, números y guiones. Se genera automáticamente desde el nombre.
                        </small>
                        @error('key')
                            <span class="field-validation-error">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="description" class="control-label col-form-label">Descripción</label>
                        <input type="text" class="form-control @error('description') is-invalid @enderror"
                               id="description" name="description"
                               value="{{ old('description', $shortcode?->description) }}"
                               maxlength="255" placeholder="Breve descripción del shortcode">
                        @error('description')
                            <span class="field-validation-error">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="icon" class="control-label col-form-label">
                            Icono <span class="text-muted small">(clase Font Awesome)</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" id="iconPreview">
                                <i class="{{ old('icon', $shortcode?->icon ?? 'fas fa-code') }}"></i>
                            </span>
                            <input type="text" class="form-control @error('icon') is-invalid @enderror"
                                   id="icon" name="icon"
                                   value="{{ old('icon', $shortcode?->icon ?? 'fas fa-code') }}"
                                   maxlength="100" placeholder="fas fa-code">
                        </div>
                        @error('icon')
                            <span class="field-validation-error">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="col-12 col-md-3">
                    <div class="mb-4">
                        <label for="sort_order" class="control-label col-form-label">Orden</label>
                        <input type="number" class="form-control"
                               id="sort_order" name="sort_order"
                               value="{{ old('sort_order', $shortcode?->sort_order ?? 0) }}" min="0">
                    </div>
                </div>

                <div class="col-12 col-md-3">
                    <div class="mb-4">
                        <label class="control-label col-form-label d-block">Estado</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                   value="1" {{ old('is_active', $shortcode?->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Activo</label>
                        </div>
                        <small class="text-muted">
                            Los shortcodes inactivos no aparecen en el editor de páginas.
                        </small>
                    </div>
                </div>

                {{-- ── Campos de configuración ──────────────────────────────── --}}
                <div class="col-12">
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-start mt-4">
                        <div>
                            <h6 class="fw-bold mb-0">Campos de configuración</h6>
                            <p class="text-muted mb-4">
                                Define los campos que verá el editor al insertar este shortcode.
                                El ID de cada campo es el placeholder que puedes usar en las plantillas
                                (<code>{campo_id}</code>).
                            </p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="addFieldBtn">
                            <i class="fas fa-plus me-1"></i> Agregar campo
                        </button>
                    </div>

                    <div id="fieldsContainer">
                        @forelse($existingFields as $i => $field)
                            @include('template::shortcodes.partials.field-row', ['field' => $field, 'index' => $i])
                        @empty
                            <p class="text-muted text-center py-3 border rounded bg-light mb-0" id="emptyFieldsMsg">
                                Sin campos — este shortcode no mostrará formulario de configuración.
                            </p>
                        @endforelse
                    </div>

                    <input type="hidden" id="config_fields" name="config_fields"
                           value="{{ old('config_fields', json_encode($existingFields)) }}">
                </div>

                {{-- ── Plantilla de inserción ───────────────────────────────── --}}
                <div class="col-12">
                    <hr class="my-2">
                    <h6 class="fw-bold mb-0 mt-4">Plantilla de inserción</h6>
                    <p class="text-muted mb-3">
                        Texto que se inserta en el editor al usar este shortcode.
                        Usa <code>{campo_id}</code> como placeholder. Ejemplo:
                        <code>[boton url="{bc_url}" estilo="{bc_style}"]{bc_text}[/boton]</code>
                    </p>
                    <input type="text" class="form-control font-monospace @error('shortcode_template') is-invalid @enderror"
                           id="shortcode_template" name="shortcode_template"
                           value="{{ old('shortcode_template', $shortcode?->shortcode_template) }}"
                           maxlength="500"
                           placeholder='[mi-shortcode param="{bc_param}"][/mi-shortcode]'>
                    @error('shortcode_template')
                        <span class="field-validation-error">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </span>
                    @enderror
                    <div id="templatePreview"
                         class="mt-2 p-2 rounded bg-light small font-monospace text-secondary"
                         style="display:none; word-break:break-all;"></div>
                </div>

                {{-- ── Plantilla de renderizado ─────────────────────────────── --}}
                <div class="col-12">
                    <hr class="my-2">
                    <h6 class="fw-bold mb-0 mt-4">
                        Plantilla de renderizado
                        <span class="badge bg-info-subtle text-info ms-1" style="font-size:.7rem;">Opcional</span>
                    </h6>
                    <p class="text-muted mb-3">
                        HTML que el motor de shortcodes usará para renderizar este bloque en el frontend.
                        Usa <code>{campo_id}</code> como placeholder y <code>{content}</code> para el contenido interior.
                        Ejemplo: <code>&lt;div class="alert alert-{bc_type}"&gt;{bc_message}&lt;/div&gt;</code><br>
                        <strong class="text-muted">Nota:</strong>
                        Si está vacío, el shortcode debe tener un handler PHP registrado en el módulo Shortcode.
                    </p>
                    <textarea class="form-control font-monospace @error('render_template') is-invalid @enderror"
                              id="render_template" name="render_template"
                              rows="6"
                              placeholder="<div class=&quot;mi-bloque&quot;>{content}</div>">{{ old('render_template', $shortcode?->render_template) }}</textarea>
                    @error('render_template')
                        <span class="field-validation-error">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </span>
                    @enderror
                </div>

                {{-- ── JavaScript ───────────────────────────────────────────── --}}
                <div class="col-12">
                    <hr class="my-2">
                    <h6 class="fw-bold mb-0 mt-4">
                        JavaScript
                        <span class="badge bg-info-subtle text-info ms-1" style="font-size:.7rem;">Opcional</span>
                    </h6>
                    <p class="text-muted mb-3">
                        Código JavaScript que se ejecutará cuando este shortcode se renderice en el frontend.
                        Se inyecta en un <code>&lt;script&gt;</code> inline al final del HTML generado.<br>
                        Puedes usar <code>document.currentScript</code> para referenciar el propio script
                        e inicializar el componente sin IDs globales.
                    </p>
                    <textarea class="form-control font-monospace @error('js_code') is-invalid @enderror"
                              id="js_code" name="js_code"
                              rows="8"
                              placeholder="// Ejemplo: inicializar un slider&#10;(function() {&#10;  var el = document.currentScript.previousElementSibling;&#10;  // ... tu lógica aquí&#10;})();">{{ old('js_code', $shortcode?->js_code) }}</textarea>
                    @error('js_code')
                        <span class="field-validation-error">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </span>
                    @enderror
                </div>

            </div>

        </div>

        <div class="card-footer border-top">
            <button type="submit" class="btn btn-primary w-100 mb-1">
                {{ $isEdit ? 'Guardar cambios' : 'Crear shortcode' }}
            </button>
            <a href="{{ route('settings.shortcodes.index') }}" class="btn btn-secondary w-100 mb-1">
                Cancelar
            </a>
            @if($isEdit)
                <button type="button" class="btn btn-outline-danger w-100" id="deleteBtnSidebar"
                        data-url="{{ route('settings.shortcodes.destroy', $shortcode->id) }}"
                        data-name="{{ $shortcode->name }}">
                    Eliminar shortcode
                </button>
            @endif
        </div>

    </form>

</div>

{{-- Template oculta para nuevos campos --}}
<template id="fieldRowTemplate">
    @include('template::shortcodes.partials.field-row', ['field' => [], 'index' => '__INDEX__'])
</template>

@push('scripts')
<script>
$(function () {
    var CSRF       = $('meta[name="csrf-token"]').attr('content');
    var fieldIndex = {{ count($existingFields) }};

    // ── Auto-generar key desde nombre ───────────────────────────────────────
    $('#name').on('input', function () {
        if (!$('#key').data('manual')) {
            $('#key').val($(this).val()
                .toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
            );
        }
    });
    $('#key').on('input', function () { $(this).data('manual', true); });

    // ── Preview icono ────────────────────────────────────────────────────────
    $('#icon').on('input', function () {
        $('#iconPreview i').attr('class', $(this).val() || 'fas fa-code');
    });

    // ── Drag & drop en campos de configuración (Sortable) ────────────────────
    if (typeof Sortable !== 'undefined') {
        Sortable.create(document.getElementById('fieldsContainer'), {
            handle: '.field-drag-handle',
            animation: 150,
            onEnd: syncFieldsJson,
        });
    }

    // ── Agregar campo ────────────────────────────────────────────────────────
    $('#addFieldBtn').on('click', function () {
        $('#emptyFieldsMsg').remove();
        var tpl = $('#fieldRowTemplate').html().replace(/__INDEX__/g, fieldIndex);
        $('#fieldsContainer').append(tpl);
        fieldIndex++;
        syncFieldsJson();
    });

    // ── Eliminar campo ───────────────────────────────────────────────────────
    $(document).on('click', '.remove-field-btn', function () {
        $(this).closest('.field-row').remove();
        if ($('.field-row').length === 0) {
            $('#fieldsContainer').html(
                '<p class="text-muted text-center py-3 border rounded bg-light mb-0" id="emptyFieldsMsg">Sin campos — este shortcode no mostrará formulario de configuración.</p>'
            );
        }
        syncFieldsJson();
    });

    // ── Copiar placeholder ───────────────────────────────────────────────────
    $(document).on('click', '.copy-placeholder-btn', function () {
        var id = $(this).closest('.field-row').find('.field-id').val();
        if (!id) { return; }
        var placeholder = '{' + id + '}';
        navigator.clipboard.writeText(placeholder).then(function () {
            toastr.info(placeholder + ' copiado al portapapeles.');
        });
    });

    // ── Mostrar/ocultar grupos al cambiar tipo ───────────────────────────────
    $(document).on('change', '.field-type-select', function () {
        var $row = $(this).closest('.field-row');
        $row.find('.field-options-group').toggle($(this).val() === 'select');
        $row.find('.field-rows-group').toggle($(this).val() === 'textarea');
        syncFieldsJson();
    });

    // ── Sync en cualquier cambio de campo ────────────────────────────────────
    $(document).on('input change', '.field-row input, .field-row select, .field-row textarea', function () {
        syncFieldsJson();
    });

    // ── Preview en vivo de shortcode_template ────────────────────────────────
    $('#shortcode_template').on('input', updateTemplatePreview);

    function updateTemplatePreview() {
        var tpl = $('#shortcode_template').val().trim();
        var $preview = $('#templatePreview');
        if (!tpl) { $preview.hide(); return; }
        var html = $('<div>').text(tpl).html()
            .replace(/\{([^}]+)\}/g, '<span class="text-primary fw-semibold">{$1}</span>');
        $preview.html(html).show();
    }

    // ── Sincronizar JSON de config_fields ────────────────────────────────────
    function syncFieldsJson() {
        var fields = [];
        $('.field-row').each(function () {
            var type  = $(this).find('.field-type-select').val();
            var field = {
                id:          $(this).find('.field-id').val(),
                label:       $(this).find('.field-label').val(),
                type:        type,
                placeholder: $(this).find('.field-placeholder').val(),
            };
            if (type === 'select') {
                var opts = {};
                $(this).find('.field-options').val().split('\n').forEach(function (line) {
                    var parts = line.split(':');
                    var k     = parts[0].trim();
                    var v     = (parts[1] || parts[0]).trim();
                    if (k) { opts[k] = v; }
                });
                field.options = opts;
            }
            if (type === 'textarea') {
                field.rows = parseInt($(this).find('.field-rows').val()) || 4;
            }
            fields.push(field);
        });
        $('#config_fields').val(JSON.stringify(fields));
    }

    // ── Eliminar shortcode (AJAX) ────────────────────────────────────────────
    $('#deleteBtnSidebar').on('click', function () {
        var $btn = $(this);
        if (!confirm('¿Eliminar el shortcode «' + $btn.data('name') + '»?')) { return; }

        $.ajax({
            url:     $btn.data('url'),
            method:  'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            success: function () {
                toastr.success('Shortcode eliminado.');
                setTimeout(function () {
                    window.location.href = '{{ route('settings.shortcodes.index') }}';
                }, 800);
            },
            error: function () {
                toastr.error('No se pudo eliminar el shortcode.');
            },
        });
    });

    // ── Init ─────────────────────────────────────────────────────────────────
    $('.field-type-select').each(function () {
        var $row = $(this).closest('.field-row');
        $row.find('.field-options-group').toggle($(this).val() === 'select');
        $row.find('.field-rows-group').toggle($(this).val() === 'textarea');
    });

    updateTemplatePreview();

    @if(session('success'))
    toastr.success(@json(session('success')), 'Éxito');
    @endif
    @if(session('error'))
    toastr.error(@json(session('error')), 'Error');
    @endif
});
</script>
@endpush
