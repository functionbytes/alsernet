@php
    $isEdit = !is_null($shortcode);
    $action = $isEdit
        ? route('settings.shortcodes.update', $shortcode->id)
        : route('settings.shortcodes.store');
    $existingFields = $isEdit ? ($shortcode->config_fields ?? []) : [];
    $availableCategories = \Modules\Template\Models\ShortcodeCategory::active()->get();
@endphp

@include('core::components.card', ['title' => $isEdit ? 'Editar shortcode: ' . $shortcode->name : 'Nuevo shortcode'])

@include('core::components.alerts')

<div class="widget-content searchable-container list">

    <div class="row g-4 align-items-start">

        {{-- ── Columna principal ── --}}
        <div class="col-lg-8">
            <form action="{{ $action }}" method="POST" id="shortcodeForm">
                @csrf
                @if($isEdit) @method('PUT') @endif

                {{-- ===== Card 1: Info + Campos + Template inserción ===== --}}
                <div class="card mb-3">

                    {{-- Información básica --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Información básica</h6>
                        <p class="text-muted mb-3">Define el nombre, clave técnica e icono del shortcode.</p>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name"
                                       value="{{ old('name', $shortcode?->name) }}"
                                       required maxlength="150" autofocus>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label for="key" class="form-label fw-semibold">Clave técnica <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('key') is-invalid @enderror"
                                       id="key" name="key"
                                       value="{{ old('key', $shortcode?->key) }}"
                                       required maxlength="100"
                                       placeholder="mi-shortcode" pattern="[a-z0-9\-]+">
                                <small class="text-muted d-block mt-1">Solo letras minúsculas, números y guiones.</small>
                                @error('key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label for="description" class="form-label fw-semibold">Descripción</label>
                                <input type="text" class="form-control @error('description') is-invalid @enderror"
                                       id="description" name="description"
                                       value="{{ old('description', $shortcode?->description) }}"
                                       maxlength="255" placeholder="Breve descripción del shortcode">
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label for="icon" class="form-label fw-semibold">Icono <span class="text-muted fw-normal">(Font Awesome)</span></label>

                                    <input type="text" class="form-control @error('icon') is-invalid @enderror"
                                           id="icon" name="icon"
                                           value="{{ old('icon', $shortcode?->icon ?? 'fas fa-code') }}"
                                           maxlength="100" placeholder="fas fa-code">

                                @error('icon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label for="sort_order" class="form-label fw-semibold">Orden</label>
                                <input type="number" class="form-control"
                                       id="sort_order" name="sort_order"
                                       value="{{ old('sort_order', $shortcode?->sort_order ?? 0) }}" min="0">
                            </div>
                            <div class="col-md-12">
                                <label for="category" class="form-label fw-semibold">Categoria</label>
                                <select class="form-select select2" name="category" id="category">
                                    @foreach($availableCategories as $cat)
                                        <option value="{{ $cat->slug }}" {{ old('category', $shortcode?->category ?? 'otros') === $cat->slug ? 'selected' : '' }}>
                                            {{ $cat->label }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Agrupa el shortcode en el panel del editor visual.</small>
                                @error('category')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Estado</label>
                                <select class="form-select select2" name="is_active" id="is_active">
                                    <option value="1" {{ old('is_active', $shortcode?->is_active ?? true) ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ !old('is_active', $shortcode?->is_active ?? true) ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                <small class="text-muted d-block mt-1">Los shortcodes inactivos no aparecen en el editor de páginas.</small>
                            </div>
                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Campos de configuración --}}
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Campos de configuración</h6>
                                <p class="text-muted mb-3">
                                    Define los campos que verá el editor al insertar este shortcode.
                                    El ID de cada campo es el placeholder que puedes usar en las plantillas (<code>{campo_id}</code>).
                                </p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addFieldBtn">
                                <i class="fas fa-plus me-1"></i> Agregar
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

                    <hr class="my-0">

                    {{-- Plantilla de inserción --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Plantilla de inserción</h6>
                        <p class="text-muted mb-3">
                            Texto que se inserta en el editor al usar este shortcode.
                            Usa <code>{campo_id}</code> como placeholder.
                        </p>

                        <label for="shortcode_template" class="form-label fw-semibold">Template</label>
                        <input type="text" class="form-control font-monospace @error('shortcode_template') is-invalid @enderror"
                               id="shortcode_template" name="shortcode_template"
                               value="{{ old('shortcode_template', $shortcode?->shortcode_template) }}"
                               maxlength="500"
                               placeholder='[mi-shortcode param="{bc_param}"][/mi-shortcode]'>
                        @error('shortcode_template')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="templatePreview"
                             class="mt-2 p-2 rounded bg-light small font-monospace text-secondary"
                             style="display:none; word-break:break-all;"></div>
                    </div>

                </div>

                {{-- ===== Card 2: Editor de código ===== --}}
                <div class="card">

                    {{-- Header --}}
                    <div class="card-header border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0 fw-bold">Editor de código</h5>
                                <small class="text-muted">HTML, CSS y JavaScript del shortcode</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge text-info">
                                    <i class="fas fa-keyboard me-1"></i>Ctrl+S para guardar
                                </span>
                                <span class="badge bg-black text-white" id="editorStatus">Listo</span>
                            </div>
                        </div>
                    </div>


                    {{-- Toolbar de edición --}}
                    <div class="editor-toolbar-row">
                        <button type="button" class="btn btn-sm btn-outline-light" id="btnFormat" title="Formatear código (Alt+Shift+F)">
                            <i class="fas fa-wand-magic-sparkles me-1"></i> Formatear
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-light" id="btnFoldAll" title="Colapsar todo">
                            <i class="fas fa-compress-alt me-1"></i> Colapsar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-light" id="btnUnfoldAll" title="Expandir todo">
                            <i class="fas fa-expand-alt me-1"></i> Expandir
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-light" id="btnWrapLines" title="Ajustar líneas largas">
                            <i class="fas fa-align-left me-1"></i> Ajuste de línea
                        </button>
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <small class="text-secondary">Ctrl+F buscar · Ctrl+H reemplazar · F11 pantalla completa</small>
                            <button type="button" class="btn btn-sm btn-outline-light" id="btnTheme" title="Tema claro / oscuro">
                                <i class="fas fa-circle-half-stroke"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light" id="btnFullscreen" title="Pantalla completa (F11)">
                                <i class="fas fa-expand"></i>
                            </button>
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
                            <button class="nav-link" id="tab-js-btn" data-bs-toggle="tab" data-bs-target="#tab-js" type="button" role="tab">
                                Javascript
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-preview-btn" data-bs-toggle="tab" data-bs-target="#tab-preview" type="button" role="tab">
                                Vista
                            </button>
                        </li>
                    </ul>

                    {{-- Tabs content --}}
                    <div class="tab-content" id="editorTabsContent">

                        {{-- HTML --}}
                        <div class="tab-pane fade show active p-0" id="tab-html" role="tabpanel">
                            <textarea id="render_template" name="render_template" style="display:none;">{{ old('render_template', $shortcode?->render_template) }}</textarea>
                        </div>

                        {{-- CSS --}}
                        <div class="tab-pane fade p-0" id="tab-css" role="tabpanel">
                            <textarea id="css_code" name="css_code" style="display:none;">{{ old('css_code', $shortcode?->css_code) }}</textarea>
                        </div>

                        {{-- JS --}}
                        <div class="tab-pane fade p-0" id="tab-js" role="tabpanel">
                            <textarea id="js_code" name="js_code" style="display:none;">{{ old('js_code', $shortcode?->js_code) }}</textarea>
                        </div>

                        {{-- Vista previa --}}
                        <div class="tab-pane fade p-3" id="tab-preview" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div>
                                    <h6 class="mb-1 fw-semibold text-dark">Vista previa</h6>
                                    <small class="text-muted">Renderiza HTML + CSS + JS en tiempo real</small>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary active" id="btnDesktop" title="Desktop">
                                            <i class="fas fa-desktop"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="btnMobile" title="Mobile (375px)">
                                            <i class="fas fa-mobile-screen"></i>
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshPreviewTab" title="Actualizar preview">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnOpenPreviewWindow" title="Abrir en ventana separada">
                                        <i class="fas fa-up-right-from-square"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="previewWrapper" style="transition: width .3s ease;">
                                <div id="previewContainer"
                                     style="min-height:400px; max-height:600px; overflow-y:auto; background:#f8f9fa; border-radius:4px; border:1px solid #dee2e6;">
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-eye fa-2x mb-2 d-block"></i>
                                        <p class="mb-0 small">Haz clic en "Vista" para cargar el preview</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer bg-white border-top">
                        <button type="submit" class="btn btn-primary w-100">
                            {{ $isEdit ? 'Guardar cambios' : 'Crear shortcode' }}
                        </button>
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
                    <p class="text-muted mb-3">Gestiona este shortcode.</p>
                    <a href="{{ route('settings.shortcodes.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                        Volver al listado
                    </a>
                    @if($isEdit)
                        <button type="button" class="btn btn-outline-danger w-100" id="deleteBtnSidebar"
                                data-url="{{ route('settings.shortcodes.destroy', $shortcode->id) }}"
                                data-name="{{ $shortcode->name }}">
                            Eliminar shortcode
                        </button>
                    @endif
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
                            ['Autocompletar', 'Ctrl+Space'],
                            ['Comentar', 'Ctrl+/'],
                            ['Expandir Emmet', 'Tab'],
                        ] as [$label, $key])
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">{{ $label }}</span>
                                    <kbd class="bg-black text-white px-2 py-1 rounded">{{ $key }}</kbd>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Variables disponibles --}}
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Variables disponibles</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">Usa estas variables en las plantillas:</p>
                    <ul class="text-muted mb-0">
                        <li class="mb-1"><code>{campo_id}</code> — Valor del campo configurado</li>
                        <li class="mb-1"><code>{content}</code> — Contenido interior del shortcode</li>
                    </ul>
                </div>
            </div>

            {{-- Ejemplos --}}
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Ejemplos</h6>
                </div>
                <div class="card-body">

                    <h6 class="fw-semibold mb-2">Plantilla de inserción</h6>
                    <code class="d-block bg-light p-2 rounded mb-3 small">[boton url="{bc_url}" estilo="{bc_style}"]{bc_text}[/boton]</code>

                    <hr class="my-3">

                    <h6 class="fw-semibold mb-2">HTML</h6>
                    <code class="d-block bg-light p-2 rounded mb-3 small">&lt;div class="alerta"&gt;{bc_message}&lt;/div&gt;</code>

                    <hr class="my-3">

                    <h6 class="fw-semibold mb-2">CSS</h6>
                    <code class="d-block bg-light p-2 rounded mb-3 small">.alerta { padding: 1rem; background: #f0f; }</code>

                    <hr class="my-3">

                    <h6 class="fw-semibold mb-2">JavaScript</h6>
                    <code class="d-block bg-light p-2 rounded small">document.currentScript.previousElementSibling</code>
                    <small class="text-muted d-block mt-1">Referencia el elemento HTML del shortcode.</small>

                </div>
            </div>

        </div>

    </div>
</div>

{{-- Template oculta para nuevos campos --}}
<template id="fieldRowTemplate">
    @include('template::shortcodes.partials.field-row', ['field' => [], 'index' => '__INDEX__'])
</template>

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/theme/monokai.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/show-hint.min.css">
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
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closetag.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/show-hint.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/html-hint.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/css-hint.min.js"></script>
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
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify-html.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify-css.js"></script>
<script>
$(function () {
    var CSRF          = $('meta[name="csrf-token"]').attr('content');
    var fieldIndex    = {{ count($existingFields) }};
    var previewTimeout;
    var THEME_CSS     = @json($themeCssUrls ?? []);
    var THEME_JS      = @json($themeJsUrls ?? []);

    // ── Inicializar CodeMirror editors ──────────────────────────────────────

    function makeExtraKeys(cm) {
        return {
            'Ctrl-Space': 'autocomplete',
            'Ctrl-/': 'toggleComment',
            'Ctrl-S': function () { syncEditors(); $('#shortcodeForm').submit(); },
            'Ctrl-F': 'findPersistent',
            'Ctrl-H': 'replace',
            'F11': function (editor) {
                editor.setOption('fullScreen', !editor.getOption('fullScreen'));
                $('#btnFullscreen i').toggleClass('fa-expand fa-compress', !editor.getOption('fullScreen'))
                                     .toggleClass('fa-compress fa-expand', editor.getOption('fullScreen'));
            },
            'Esc': function (editor) {
                if (editor.getOption('fullScreen')) {
                    editor.setOption('fullScreen', false);
                    $('#btnFullscreen i').removeClass('fa-compress').addClass('fa-expand');
                }
            },
        };
    }

    var editorHtml = CodeMirror.fromTextArea(document.getElementById('render_template'), {
        mode: 'htmlmixed',
        theme: 'default',
        lineNumbers: true,
        autoCloseTags: true,
        autoCloseBrackets: true,
        matchBrackets: true,
        styleActiveLine: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        foldOptions: { widget: ' ▾ ··· ', minFoldSize: 2 },
        extraKeys: makeExtraKeys(),
    });

    var editorCss = CodeMirror.fromTextArea(document.getElementById('css_code'), {
        mode: 'css',
        theme: 'default',
        lineNumbers: true,
        autoCloseBrackets: true,
        matchBrackets: true,
        styleActiveLine: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        extraKeys: makeExtraKeys(),
    });

    var editorJs = CodeMirror.fromTextArea(document.getElementById('js_code'), {
        mode: 'javascript',
        theme: 'default',
        lineNumbers: true,
        autoCloseBrackets: true,
        matchBrackets: true,
        styleActiveLine: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        extraKeys: makeExtraKeys(),
    });

    // ── Refrescar editor al mostrar su tab (necesario por el display:none) ──
    $('#tab-css-btn').on('shown.bs.tab', function () { editorCss.refresh(); });
    $('#tab-js-btn').on('shown.bs.tab', function () { editorJs.refresh(); });
    $('#tab-html-btn').on('shown.bs.tab', function () { editorHtml.refresh(); });

    // ── Auto-update preview al cambiar código ────────────────────────────────
    function schedulePreview() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(function () {
            if ($('#tab-preview').hasClass('active')) {
                renderPreview();
            }
        }, 1000);
    }

    editorHtml.on('change', schedulePreview);
    editorCss.on('change', schedulePreview);
    editorJs.on('change', schedulePreview);

    // ── Renderizar preview en iframe (client-side) ───────────────────────────
    function renderPreview() {
        var html = editorHtml.getValue();
        var css  = editorCss.getValue();
        var js   = editorJs.getValue();

        // Split closing tags to prevent browser logger injection
        var closeStyle  = '<' + '/style>';
        var openScript  = '<' + 'script>';
        var closeScript = '<' + '/script>';
        var closeHead   = '<' + '/head>';
        var closeBody   = '<' + '/body>';
        var closeHtml   = '<' + '/html>';

        // Build theme CSS <link> tags from URL list
        var themeCssLinks = THEME_CSS.map(function (url) {
            return '<link rel="stylesheet" href="' + url + '">';
        }).join('');

        // Build theme JS <script> tags from URL list
        var themeJsTags = THEME_JS.map(function (url) {
            return '<' + 'script src="' + url + '"><' + '/script>';
        }).join('');

        var doc = '<!DOCTYPE html><html><head>'
            + '<meta charset="UTF-8">'
            + '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            + '<base href="' + window.location.origin + '/">'
            + themeCssLinks
            + (css ? '<style>' + css + closeStyle : '')
            + closeHead + '<body>'
            + html
            + themeJsTags
            + (js ? openScript + js + closeScript : '')
            + closeBody + closeHtml;

        var blob = new Blob([doc], { type: 'text/html' });
        var url  = URL.createObjectURL(blob);

        var $iframe = $('<iframe>').css({
            width: '100%',
            'min-height': '380px',
            border: 'none',
            display: 'block',
            background: 'white',
        });

        var oldUrl = $('#previewContainer iframe').data('blobUrl');
        if (oldUrl) { URL.revokeObjectURL(oldUrl); }

        $('#previewContainer').empty().append($iframe);
        $iframe.data('blobUrl', url);
        $iframe[0].src = url;
    }

    // ── Mostrar preview al activar el tab ────────────────────────────────────
    $('#tab-preview-btn').on('shown.bs.tab', function () {
        renderPreview();
    });

    // ── Botones de refresh ───────────────────────────────────────────────────
    $('#btnRefreshPreview, #btnRefreshPreviewTab').on('click', function () {
        $('#tab-preview-btn').tab('show');
        setTimeout(renderPreview, 150);
    });

    // ── Desktop / Mobile preview ─────────────────────────────────────────────
    $('#btnDesktop').on('click', function () {
        $('#previewWrapper').css('width', '100%');
        $(this).addClass('active');
        $('#btnMobile').removeClass('active');
    });

    $('#btnMobile').on('click', function () {
        $('#previewWrapper').css('width', '375px');
        $(this).addClass('active');
        $('#btnDesktop').removeClass('active');
    });

    // ── Abrir preview en ventana separada ────────────────────────────────────
    $('#btnOpenPreviewWindow').on('click', function () {
        var html = editorHtml.getValue();
        var css  = editorCss.getValue();
        var js   = editorJs.getValue();

        var closeStyle  = '<' + '/style>';
        var openScript  = '<' + 'script>';
        var closeScript = '<' + '/script>';
        var closeHead   = '<' + '/head>';
        var closeBody   = '<' + '/body>';
        var closeHtml   = '<' + '/html>';

        var themeCssLinks = THEME_CSS.map(function (url) {
            return '<link rel="stylesheet" href="' + url + '">';
        }).join('');

        var themeJsTags = THEME_JS.map(function (url) {
            return '<' + 'script src="' + url + '"><' + '/script>';
        }).join('');

        var doc = '<!DOCTYPE html><html><head>'
            + '<meta charset="UTF-8">'
            + '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            + '<base href="' + window.location.origin + '/">'
            + themeCssLinks
            + (css ? '<style>' + css + closeStyle : '')
            + closeHead + '<body>'
            + html
            + themeJsTags
            + (js ? openScript + js + closeScript : '')
            + closeBody + closeHtml;

        var blob = new Blob([doc], { type: 'text/html' });
        var url  = URL.createObjectURL(blob);
        window.open(url, '_blank', 'width=1200,height=800,resizable=yes,scrollbars=yes');
    });

    // ── Obtener editor activo ─────────────────────────────────────────────────
    function activeEditor() {
        var id = $('.nav-tabs .nav-link.active').attr('id');
        if (id === 'tab-html-btn') { return editorHtml; }
        if (id === 'tab-css-btn')  { return editorCss; }
        if (id === 'tab-js-btn')   { return editorJs; }
        return editorHtml;
    }

    // ── Formatear código ─────────────────────────────────────────────────────
    $('#btnFormat').on('click', function () {
        var id = $('.nav-tabs .nav-link.active').attr('id');
        if (id === 'tab-html-btn' && typeof html_beautify !== 'undefined') {
            editorHtml.setValue(html_beautify(editorHtml.getValue(), { indent_size: 2, wrap_line_length: 120 }));
        } else if (id === 'tab-css-btn' && typeof css_beautify !== 'undefined') {
            editorCss.setValue(css_beautify(editorCss.getValue(), { indent_size: 2 }));
        } else if (id === 'tab-js-btn' && typeof js_beautify !== 'undefined') {
            editorJs.setValue(js_beautify(editorJs.getValue(), { indent_size: 2 }));
        }
        toastr.info('Código formateado.');
    });

    // ── Colapsar / Expandir todo ──────────────────────────────────────────────
    $('#btnFoldAll').on('click', function () {
        var ed = activeEditor();
        for (var i = 0; i < ed.lineCount(); i++) {
            ed.foldCode({ line: i, ch: 0 });
        }
    });

    $('#btnUnfoldAll').on('click', function () {
        var ed = activeEditor();
        for (var i = 0; i < ed.lineCount(); i++) {
            ed.foldCode({ line: i, ch: 0 }, null, 'unfold');
        }
    });

    // ── Ajuste de línea (word wrap) ───────────────────────────────────────────
    var wrapEnabled = false;
    $('#btnWrapLines').on('click', function () {
        wrapEnabled = !wrapEnabled;
        [editorHtml, editorCss, editorJs].forEach(function (ed) {
            ed.setOption('lineWrapping', wrapEnabled);
        });
        $(this).toggleClass('active', wrapEnabled);
    });

    // ── Pantalla completa ─────────────────────────────────────────────────────
    $('#btnFullscreen').on('click', function () {
        var ed = activeEditor();
        var isFullscreen = !ed.getOption('fullScreen');
        [editorHtml, editorCss, editorJs].forEach(function (e) {
            e.setOption('fullScreen', false);
        });
        ed.setOption('fullScreen', isFullscreen);
        $(this).find('i').toggleClass('fa-expand', !isFullscreen).toggleClass('fa-compress', isFullscreen);
    });

    // ── Toggle tema claro / oscuro ────────────────────────────────────────────
    var editorDark = false;
    $('#btnTheme').on('click', function () {
        editorDark = !editorDark;
        var theme = editorDark ? 'monokai' : 'default';
        [editorHtml, editorCss, editorJs].forEach(function (ed) {
            ed.setOption('theme', theme);
        });
        $('.editor-toolbar-row').toggleClass('dark', editorDark);
        $(this).toggleClass('active', editorDark);
    });

    // ── Sincronizar textareas antes de submit ────────────────────────────────
    function syncEditors() {
        editorHtml.save();
        editorCss.save();
        editorJs.save();
    }

    $('#shortcodeForm').on('submit', function () {
        syncEditors();
    });

    // ── Tooltips ─────────────────────────────────────────────────────────────
    $('[data-bs-toggle="tooltip"]').each(function () {
        new bootstrap.Tooltip(this);
    });

    // ── Auto-generar key desde nombre ────────────────────────────────────────
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

    // ── Preview icono ─────────────────────────────────────────────────────────
    $('#icon').on('input', function () {
        $('#iconPreview i').attr('class', $(this).val() || 'fas fa-code');
    });

    // ── Drag & drop en campos (Sortable) ─────────────────────────────────────
    if (typeof Sortable !== 'undefined') {
        Sortable.create(document.getElementById('fieldsContainer'), {
            handle: '.field-drag-handle',
            animation: 150,
            onEnd: syncFieldsJson,
        });
    }

    // ── Agregar campo ─────────────────────────────────────────────────────────
    $('#addFieldBtn').on('click', function () {
        $('#emptyFieldsMsg').remove();
        var tpl = $('#fieldRowTemplate').html().replace(/__INDEX__/g, fieldIndex);
        $('#fieldsContainer').append(tpl);
        fieldIndex++;
        syncFieldsJson();
    });

    // ── Eliminar campo ────────────────────────────────────────────────────────
    $(document).on('click', '.remove-field-btn', function () {
        $(this).closest('.field-row').remove();
        if ($('.field-row').length === 0) {
            $('#fieldsContainer').html(
                '<p class="text-muted text-center py-3 border rounded bg-light mb-0" id="emptyFieldsMsg">Sin campos — este shortcode no mostrará formulario de configuración.</p>'
            );
        }
        syncFieldsJson();
    });

    // ── Copiar placeholder ────────────────────────────────────────────────────
    $(document).on('click', '.copy-placeholder-btn', function () {
        var id = $(this).closest('.field-row').find('.field-id').val();
        if (!id) { return; }
        var placeholder = '{' + id + '}';
        navigator.clipboard.writeText(placeholder).then(function () {
            toastr.info(placeholder + ' copiado al portapapeles.');
        });
    });

    // ── Mostrar/ocultar grupos al cambiar tipo ────────────────────────────────
    $(document).on('change', '.field-type-select', function () {
        var $row = $(this).closest('.field-row');
        $row.find('.field-options-group').toggle($(this).val() === 'select');
        $row.find('.field-rows-group').toggle($(this).val() === 'textarea');
        syncFieldsJson();
    });

    $(document).on('input change', '.field-row input, .field-row select, .field-row textarea', function () {
        syncFieldsJson();
    });

    // ── Preview en vivo de shortcode_template ─────────────────────────────────
    $('#shortcode_template').on('input', updateTemplatePreview);

    function updateTemplatePreview() {
        var tpl = $('#shortcode_template').val().trim();
        var $preview = $('#templatePreview');
        if (!tpl) { $preview.hide(); return; }
        var html = $('<div>').text(tpl).html()
            .replace(/\{([^}]+)\}/g, '<span class="text-primary fw-semibold">{$1}</span>');
        $preview.html(html).show();
    }

    // ── Sincronizar JSON de config_fields ─────────────────────────────────────
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

    // ── Eliminar shortcode (AJAX) ─────────────────────────────────────────────
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

    // ── Init ──────────────────────────────────────────────────────────────────
    $('.select2').select2({ width: '100%' });

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
