@php
    $isEdit = !is_null($shortcode);
    $action = $isEdit
        ? route('settings.shortcodes.update', $shortcode->id)
        : route('settings.shortcodes.store');
    $existingFields = $isEdit ? ($shortcode->config_fields ?? []) : [];
@endphp

<form action="{{ $action }}" method="POST" id="shortcodeForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row">

        {{-- COLUMNA PRINCIPAL --}}
        <div class="col-lg-8">

            {{-- Datos básicos --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Información básica</h5>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name"
                               value="{{ old('name', $shortcode?->name) }}" required maxlength="150" autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="key" class="form-label fw-semibold">Clave técnica <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('key') is-invalid @enderror"
                               id="key" name="key"
                               value="{{ old('key', $shortcode?->key) }}" required maxlength="100"
                               placeholder="mi-shortcode" pattern="[a-z0-9\-]+">
                        <small class="form-text text-muted">Solo letras minúsculas, números y guiones. Se genera automáticamente desde el nombre.</small>
                        @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Descripción</label>
                        <input type="text" class="form-control @error('description') is-invalid @enderror"
                               id="description" name="description"
                               value="{{ old('description', $shortcode?->description) }}" maxlength="255"
                               placeholder="Breve descripción del shortcode">
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-0">
                        <label for="icon" class="form-label fw-semibold">Icono <span class="text-muted small fw-normal">(clase Font Awesome)</span></label>
                        <div class="input-group">
                            <span class="input-group-text" id="iconPreview">
                                <i class="{{ old('icon', $shortcode?->icon ?? 'fas fa-code') }}"></i>
                            </span>
                            <input type="text" class="form-control @error('icon') is-invalid @enderror"
                                   id="icon" name="icon"
                                   value="{{ old('icon', $shortcode?->icon ?? 'fas fa-code') }}"
                                   maxlength="100" placeholder="fas fa-code">
                        </div>
                        @error('icon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                </div>
            </div>

            {{-- Campos de configuración --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Campos de configuración</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addFieldBtn">
                        <i class="fas fa-plus me-1"></i> Agregar campo
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Define los campos que verá el editor al insertar este shortcode. El ID de cada campo es el placeholder que puedes usar en las plantillas (<code>{campo_id}</code>).
                    </p>

                    <div id="fieldsContainer">
                        @forelse($existingFields as $i => $field)
                            @include('template::shortcodes.partials.field-row', ['field' => $field, 'index' => $i])
                        @empty
                            <p class="text-muted text-center py-3" id="emptyFieldsMsg">
                                Sin campos — este shortcode no mostrará formulario de configuración.
                            </p>
                        @endforelse
                    </div>

                    <input type="hidden" id="config_fields" name="config_fields" value="{{ old('config_fields', json_encode($existingFields)) }}">
                </div>
            </div>

            {{-- Plantilla de inserción (editor) --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Plantilla de inserción</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        Texto que se inserta en el editor al usar este shortcode. Usa <code>{campo_id}</code> como placeholder. Ejemplo:<br>
                        <code>[boton url="{bc_url}" estilo="{bc_style}"]{bc_text}[/boton]</code>
                    </p>
                    <input type="text" class="form-control font-monospace @error('shortcode_template') is-invalid @enderror"
                           id="shortcode_template" name="shortcode_template"
                           value="{{ old('shortcode_template', $shortcode?->shortcode_template) }}"
                           maxlength="500"
                           placeholder='[mi-shortcode param="{bc_param}"][/mi-shortcode]'>
                    @error('shortcode_template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Plantilla de renderizado --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Plantilla de renderizado <span class="badge bg-info-subtle text-info ms-1" style="font-size:.7rem;">Opcional</span></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        HTML que el motor de shortcodes usará para renderizar este bloque en el frontend. Usa <code>{campo_id}</code> como placeholder y <code>{content}</code> para el contenido interior. Ejemplo:<br>
                        <code>&lt;div class="alert alert-{bc_type}"&gt;{bc_message}&lt;/div&gt;</code><br>
                        <strong class="text-muted">Nota:</strong> Si este campo está vacío, el shortcode debe tener un handler PHP registrado en el módulo Shortcode.
                    </p>
                    <textarea class="form-control font-monospace @error('render_template') is-invalid @enderror"
                              id="render_template" name="render_template"
                              rows="6"
                              placeholder="<div class=&quot;mi-bloque&quot;>{content}</div>">{{ old('render_template', $shortcode?->render_template) }}</textarea>
                    @error('render_template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

        </div>

        {{-- SIDEBAR --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        {{ $isEdit ? 'Guardar cambios' : 'Crear shortcode' }}
                    </button>
                    <a href="{{ route('settings.shortcodes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    @if($isEdit)
                        <hr class="my-1">
                        <form action="{{ route('settings.shortcodes.destroy', $shortcode->id) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar este shortcode?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">Eliminar shortcode</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Opciones</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="sort_order" class="form-label fw-semibold">Orden</label>
                        <input type="number" class="form-control form-control-sm"
                               id="sort_order" name="sort_order"
                               value="{{ old('sort_order', $shortcode?->sort_order ?? 0) }}" min="0">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                               value="1" {{ old('is_active', $shortcode?->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_active">Activo</label>
                        <small class="d-block text-muted">Los shortcodes inactivos no aparecen en el editor de páginas.</small>
                    </div>
                </div>
            </div>

        </div>
    </div>
</form>

{{-- Template oculta para nuevos campos --}}
<template id="fieldRowTemplate">
    @include('template::shortcodes.partials.field-row', ['field' => [], 'index' => '__INDEX__'])
</template>

@push('scripts')
<script>
$(function () {
    var fieldIndex = {{ count($existingFields) }};

    // Auto-generar key desde nombre
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

    // Preview icono
    $('#icon').on('input', function () {
        $('#iconPreview i').attr('class', $(this).val() || 'fas fa-code');
    });

    // Agregar campo
    $('#addFieldBtn').on('click', function () {
        $('#emptyFieldsMsg').remove();
        var tpl = $('#fieldRowTemplate').html().replace(/__INDEX__/g, fieldIndex);
        $('#fieldsContainer').append(tpl);
        fieldIndex++;
        syncFieldsJson();
    });

    // Eliminar campo (delegado)
    $(document).on('click', '.remove-field-btn', function () {
        $(this).closest('.field-row').remove();
        if ($('.field-row').length === 0) {
            $('#fieldsContainer').html('<p class="text-muted text-center py-3" id="emptyFieldsMsg">Sin campos — este shortcode no mostrará formulario de configuración.</p>');
        }
        syncFieldsJson();
    });

    // Mostrar/ocultar grupos al cambiar tipo
    $(document).on('change', '.field-type-select', function () {
        var $row = $(this).closest('.field-row');
        $row.find('.field-options-group').toggle($(this).val() === 'select');
        $row.find('.field-rows-group').toggle($(this).val() === 'textarea');
        syncFieldsJson();
    });

    // Sync al cambiar cualquier campo
    $(document).on('input change', '.field-row input, .field-row select, .field-row textarea', function () {
        syncFieldsJson();
    });

    function syncFieldsJson() {
        var fields = [];
        $('.field-row').each(function () {
            var type = $(this).find('.field-type-select').val();
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
                    var k = parts[0].trim();
                    var v = (parts[1] || parts[0]).trim();
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

    // Init: toggle opciones al cargar
    $('.field-type-select').each(function () {
        var $row = $(this).closest('.field-row');
        $row.find('.field-options-group').toggle($(this).val() === 'select');
        $row.find('.field-rows-group').toggle($(this).val() === 'textarea');
    });

    @if(session('success'))
    toastr.success(@json(session('success')), 'Éxito');
    @endif
    @if(session('error'))
    toastr.error(@json(session('error')), 'Error');
    @endif
});
</script>
@endpush
