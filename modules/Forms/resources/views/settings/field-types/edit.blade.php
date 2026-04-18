@extends('layouts.theme')

@section('title', 'Editar tipo de campo: ' . $typeSetting->label)

@section('content')
    @include('core::components.card', ['title' => 'Editar tipo de campo: ' . $typeSetting->label])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- ── Columna principal ── --}}
            <div class="col-lg-8">

                <form action="{{ route('settings.forms.field-types.update-full', $typeSetting) }}" method="POST" id="fieldTypeForm">
                    @csrf
                    @method('PUT')

                    <div class="card">

                        {{-- Header --}}
                        <div class="card-header border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h5 class="mb-0 fw-bold">Editor de tipo de campo</h5>
                                    <small class="text-muted">Configura la apariencia y comportamiento del campo</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge text-info"><i class="fas fa-keyboard me-1"></i>Ctrl+S para guardar</span>
                                    <span class="badge bg-black text-white" id="editorStatus">Listo</span>
                                </div>
                            </div>
                        </div>

                        {{-- Campos de información básica --}}
                        <div class="card-body border-bottom">
                            <div class="row g-3">

                                <div class="col-12">
                                    <label for="label" class="form-label fw-semibold">Etiqueta <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('label') is-invalid @enderror"
                                           id="label" name="label"
                                           value="{{ old('label', $typeSetting->label) }}"
                                           required maxlength="100">
                                    @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Tipo</label>
                                    <input class="form-control bg-light" readonly value="{{ $typeSetting->type }}">
                                    <small class="text-muted d-block mt-1">El tipo es único y no puede modificarse.</small>
                                </div>

                                <div class="col-12">
                                    <label for="icon" class="form-label fw-semibold">Icono <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i id="iconPreview" class="{{ old('icon', $typeSetting->icon) }}"></i>
                                        </span>
                                        <input type="text" class="form-control @error('icon') is-invalid @enderror"
                                               id="icon" name="icon"
                                               value="{{ old('icon', $typeSetting->icon) }}"
                                               required maxlength="100" placeholder="fas fa-font">
                                        @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="group_name" class="form-label fw-semibold">Grupo</label>
                                    <input type="text" class="form-control @error('group_name') is-invalid @enderror"
                                           id="group_name" name="group_name"
                                           value="{{ old('group_name', $typeSetting->group_name) }}"
                                           maxlength="100">
                                    @error('group_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label for="group_order" class="form-label fw-semibold">Orden de grupo</label>
                                    <input type="number" class="form-control @error('group_order') is-invalid @enderror"
                                           id="group_order" name="group_order"
                                           value="{{ old('group_order', $typeSetting->group_order) }}" min="0">
                                    @error('group_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label for="sort_order" class="form-label fw-semibold">Orden en grupo</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                           id="sort_order" name="sort_order"
                                           value="{{ old('sort_order', $typeSetting->sort_order) }}" min="0">
                                    @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label for="default_css_class" class="form-label fw-semibold">Clase CSS por defecto</label>
                                    <input type="text" class="form-control @error('default_css_class') is-invalid @enderror"
                                           id="default_css_class" name="default_css_class"
                                           value="{{ old('default_css_class', $typeSetting->default_css_class) }}"
                                           maxlength="255">
                                    <small class="text-muted d-block mt-1">Clases CSS aplicadas al crear el campo.</small>
                                    @error('default_css_class') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label for="default_placeholder" class="form-label fw-semibold">Placeholder por defecto</label>
                                    <input type="text" class="form-control @error('default_placeholder') is-invalid @enderror"
                                           id="default_placeholder" name="default_placeholder"
                                           value="{{ old('default_placeholder', $typeSetting->default_placeholder) }}"
                                           maxlength="255">
                                    @error('default_placeholder') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label for="is_enabled" class="form-label fw-semibold">Estado</label>
                                    <select class="form-select @error('is_enabled') is-invalid @enderror"
                                            id="is_enabled" name="is_enabled">
                                        <option value="1" {{ old('is_enabled', $typeSetting->is_enabled) ? 'selected' : '' }}>Habilitado</option>
                                        <option value="0" {{ !old('is_enabled', $typeSetting->is_enabled) ? 'selected' : '' }}>Deshabilitado</option>
                                    </select>
                                    @error('is_enabled') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                            </div>
                        </div>

                        {{-- Tabs nav --}}
                        <ul class="nav nav-tabs nav-fill border-bottom" id="editorTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-html-btn" data-bs-toggle="tab" data-bs-target="#tab-html" type="button" role="tab">
                                    Estructura
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-css-btn" data-bs-toggle="tab" data-bs-target="#tab-css" type="button" role="tab">
                                    Estilo
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-preview-btn" data-bs-toggle="tab" data-bs-target="#tab-preview" type="button" role="tab">
                                    Vista
                                </button>
                            </li>
                        </ul>

                        {{-- Toolbar (debajo de los tabs) --}}
                        <div class="editor-toolbar-row">
                            <button type="button" class="btn btn-sm btn-outline-light" id="btnFormat" title="Formatear código">
                                <i class="fas fa-wand-magic-sparkles"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light" id="btnFoldAll" title="Colapsar todo">
                                <i class="fas fa-compress-alt"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light" id="btnUnfoldAll" title="Expandir todo">
                                <i class="fas fa-expand-alt"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light" id="btnWrapLines" title="Ajuste de línea">
                                <i class="fas fa-align-left"></i>
                            </button>
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <small class="text-secondary">Ctrl+F buscar · F11 pantalla completa</small>
                                <button type="button" class="btn btn-sm btn-outline-light" id="btnTheme" title="Tema claro / oscuro">
                                    <i class="fas fa-circle-half-stroke"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-light" id="btnFullscreen" title="Pantalla completa (F11)">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Tabs content --}}
                        <div class="tab-content" id="editorTabsContent">

                            <div class="tab-pane fade show active p-0" id="tab-html" role="tabpanel">
                                <textarea id="html_code" name="custom_html" style="display:none;">{{ old('custom_html', $typeSetting->custom_html) }}</textarea>
                            </div>

                            <div class="tab-pane fade p-0" id="tab-css" role="tabpanel">
                                <textarea id="css_code" name="custom_css" style="display:none;">{{ old('custom_css', $typeSetting->custom_css) }}</textarea>
                            </div>

                            <div class="tab-pane fade p-3" id="tab-preview" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-semibold">Vista previa del campo</h6>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshPreview">
                                        <i class="fas fa-sync-alt me-1"></i> Actualizar
                                    </button>
                                </div>
                                <div id="previewContainer" class="p-4 rounded border bg-light">
                                    <style id="previewStyle"></style>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Campo de ejemplo</label>
                                        <input type="text" class="form-control {{ $typeSetting->default_css_class }}"
                                               id="previewField"
                                               placeholder="{{ $typeSetting->default_placeholder ?? 'Escribe aquí...' }}">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer bg-white border-top">
                            <button type="submit" class="btn btn-primary w-100">Guardar cambios</button>
                        </div>

                    </div>

                </form>

            </div>

            {{-- ── Columna lateral ── --}}
            <div class="col-lg-4">

                {{-- Acciones --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Acciones</h6>
                        <p class="text-muted mb-3">Gestiona este tipo de campo.</p>
                        <a href="{{ route('settings.forms.field-types.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                            Volver al listado
                        </a>
                        <button type="button" class="btn btn-outline-danger w-100" id="btnToggle"
                                data-url="{{ route('settings.forms.field-types.toggle', $typeSetting) }}"
                                data-enabled="{{ $typeSetting->is_enabled ? '1' : '0' }}">
                            {{ $typeSetting->is_enabled ? 'Deshabilitar tipo' : 'Habilitar tipo' }}
                        </button>
                    </div>
                </div>

                {{-- Atajos de teclado --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Atajos de teclado</h6>
                        <small class="text-muted">Acelera tu trabajo</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach([
                                ['Guardar', 'Ctrl+S'],
                                ['Buscar', 'Ctrl+F'],
                                ['Pantalla completa', 'F11'],
                                ['Comentar', 'Ctrl+/'],
                                ['Ajuste línea', 'Alt+Z'],
                            ] as [$keyLabel, $keyShortcut])
                                <div class="list-group-item px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">{{ $keyLabel }}</span>
                                        <kbd class="bg-black text-white px-2 py-1 rounded">{{ $keyShortcut }}</kbd>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Uso --}}
                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Información</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">Este tipo de campo se usa en el constructor de formularios.</p>
                        <ul class="text-muted mb-0">
                            <li class="mb-1">La <strong>clase CSS</strong> se aplica al input al crear el campo</li>
                            <li class="mb-1">El <strong>CSS personalizado</strong> se inyecta en el formulario público</li>
                            <li>El <strong>placeholder</strong> aparece como texto por defecto</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/theme/monokai.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/foldgutter.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/display/fullscreen.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/dialog/dialog.min.css">
<style>
    .CodeMirror { height: 420px; font-size: 13px; }
    .CodeMirror-scroll { min-height: 420px; }
    .CodeMirror-fullscreen { z-index: 9999 !important; }
    .CodeMirror-dialog { background: #f5f5f5; color: #333; border-top: 1px solid #ddd; padding: 6px 10px; }
    .CodeMirror-dialog input { background: #fff; color: #333; border: 1px solid #ccc; border-radius: 3px; padding: 2px 6px; }
    .CodeMirror-foldmarker { color: #0066cc; cursor: pointer; font-size: 11px; padding: 0 4px; background: rgba(0,0,0,.06); border-radius: 3px; }
    .editor-toolbar-row { display: flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f5f5f5; border-bottom: 1px solid #ddd; flex-wrap: wrap; transition: background .2s; }
    .editor-toolbar-row .btn { font-size: 11px; padding: 2px 8px; color: #444; border-color: #ccc; background: transparent; }
    .editor-toolbar-row .btn:hover { background: #e0e0e0; color: #000; }
    .editor-toolbar-row .btn.active { background: #d0d0d0; color: #000; }
    .editor-toolbar-row.dark { background: #1e1e1e; border-bottom-color: #444; }
    .editor-toolbar-row.dark .btn { color: #ccc; border-color: #555; }
    .editor-toolbar-row.dark .btn:hover { background: #333; color: #fff; }
    .editor-toolbar-row.dark .btn.active { background: #444; color: #fff; }
    .editor-toolbar-row.dark small { color: #888 !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closetag.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/comment/comment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/foldcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/foldgutter.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/xml-fold.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/brace-fold.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/display/fullscreen.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/search/searchcursor.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/search/search.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/dialog/dialog.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/selection/active-line.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.15.1/beautify-css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.15.1/beautify-html.min.js"></script>
<script>
$(function () {
    var CSRF = $('meta[name="csrf-token"]').attr('content');

    var editorOpts = {
        theme: 'default',
        lineNumbers: true,
        lineWrapping: false,
        autoCloseBrackets: true,
        autoCloseTags: true,
        matchBrackets: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        styleActiveLine: true,
        extraKeys: {
            'Ctrl-S': function () { saveAll(); $('#fieldTypeForm').submit(); },
            'Ctrl-F': 'findPersistent',
            'F11':    function (cm) { cm.setOption('fullScreen', !cm.getOption('fullScreen')); },
            'Esc':    function (cm) { if (cm.getOption('fullScreen')) { cm.setOption('fullScreen', false); } },
        },
    };

    var editorHtml = CodeMirror.fromTextArea(document.getElementById('html_code'), $.extend({}, editorOpts, { mode: 'htmlmixed' }));
    var editorCss  = CodeMirror.fromTextArea(document.getElementById('css_code'),  $.extend({}, editorOpts, { mode: 'css' }));

    function activeEditor() {
        if ($('#tab-html-btn').hasClass('active')) { return editorHtml; }
        return editorCss;
    }

    function saveAll() {
        editorHtml.save();
        editorCss.save();
    }

    // ── Refrescar al cambiar de tab ──────────────────────────────────────────
    $('#tab-html-btn').on('shown.bs.tab', function () { editorHtml.refresh(); });
    $('#tab-css-btn').on('shown.bs.tab',  function () { editorCss.refresh(); });

    // ── Live icon preview ────────────────────────────────────────────────────
    $('#icon').on('input', function () { $('#iconPreview').attr('class', $(this).val()); });

    // ── Preview del campo ────────────────────────────────────────────────────
    function refreshPreview() {
        $('#previewStyle').text(editorCss.getValue());
        $('#previewField').attr('class', 'form-control ' + $('#default_css_class').val());
        $('#previewField').attr('placeholder', $('#default_placeholder').val() || 'Escribe aquí...');
    }

    $('#tab-preview-btn').on('shown.bs.tab', refreshPreview);
    $('#btnRefreshPreview').on('click', refreshPreview);

    // ── Formatear ────────────────────────────────────────────────────────────
    $('#btnFormat').on('click', function () {
        var ed = activeEditor();
        if ($('#tab-html-btn').hasClass('active') && typeof html_beautify !== 'undefined') {
            ed.setValue(html_beautify(ed.getValue(), { indent_size: 2 }));
        } else if ($('#tab-css-btn').hasClass('active') && typeof css_beautify !== 'undefined') {
            ed.setValue(css_beautify(ed.getValue(), { indent_size: 2 }));
        }
        toastr.info('Código formateado.');
    });

    // ── Colapsar / Expandir ──────────────────────────────────────────────────
    $('#btnFoldAll').on('click', function () {
        var ed = activeEditor();
        for (var i = 0; i < ed.lineCount(); i++) { ed.foldCode({ line: i, ch: 0 }); }
    });
    $('#btnUnfoldAll').on('click', function () {
        var ed = activeEditor();
        for (var i = 0; i < ed.lineCount(); i++) { ed.foldCode({ line: i, ch: 0 }, null, 'unfold'); }
    });

    // ── Ajuste de línea ──────────────────────────────────────────────────────
    var wrapEnabled = false;
    $('#btnWrapLines').on('click', function () {
        wrapEnabled = !wrapEnabled;
        [editorHtml, editorCss].forEach(function (ed) { ed.setOption('lineWrapping', wrapEnabled); });
        $(this).toggleClass('active', wrapEnabled);
    });

    // ── Pantalla completa ────────────────────────────────────────────────────
    $('#btnFullscreen').on('click', function () {
        var ed = activeEditor();
        var isFs = !ed.getOption('fullScreen');
        [editorHtml, editorCss].forEach(function (e) { e.setOption('fullScreen', false); });
        ed.setOption('fullScreen', isFs);
        $(this).find('i').toggleClass('fa-expand', !isFs).toggleClass('fa-compress', isFs);
    });

    // ── Tema claro / oscuro ──────────────────────────────────────────────────
    var editorDark = false;
    $('#btnTheme').on('click', function () {
        editorDark = !editorDark;
        var theme = editorDark ? 'monokai' : 'default';
        [editorHtml, editorCss].forEach(function (ed) { ed.setOption('theme', theme); });
        $('.editor-toolbar-row').toggleClass('dark', editorDark);
        $(this).toggleClass('active', editorDark);
    });

    // ── Submit ───────────────────────────────────────────────────────────────
    $('#fieldTypeForm').on('submit', saveAll);

    // ── Toggle habilitar/deshabilitar ────────────────────────────────────────
    $('#btnToggle').on('click', function () {
        var btn = $(this);
        $.ajax({
            url: btn.data('url'),
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (res) {
                toastr.success(res.is_enabled ? 'Tipo habilitado.' : 'Tipo deshabilitado.');
                btn.data('enabled', res.is_enabled ? '1' : '0');
                btn.text(res.is_enabled ? 'Deshabilitar tipo' : 'Habilitar tipo');
                $('#is_enabled').val(res.is_enabled ? '1' : '0');
            },
            error: function () { toastr.error('Error al cambiar el estado.'); },
        });
    });

    // ── Mensajes de sesión ───────────────────────────────────────────────────
    @if(session('success')) toastr.success(@json(session('success'))); @endif
    @if(session('error'))   toastr.error(@json(session('error')));   @endif
});
</script>
@endpush
