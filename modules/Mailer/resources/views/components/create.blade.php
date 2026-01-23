@extends('layouts.theme')

@section('page_title', 'Crear Componente de email')

@section('content')
<div class="container-fluid">

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Crear Componente de email',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => route('manager.backups')],
            ['label' => 'Componentes', 'url' => route('mailers.components.index')],
            ['label' => 'Crear', 'active' => true]
        ]
    ])

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa fa-check-circle fs-4 me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="fa fa-exclamation-circle fs-4 me-2 mt-1"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-2">Errores de validación</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Main Form --}}
    <form method="POST" action="{{ route('mailers.components.store') }}" id="formCreate">
        @csrf

        <div class="row g-3">
            {{-- Left Column: Editor --}}
            <div class="col-12 col-lg-8">
                <div class="card">
                    {{-- Header --}}
                    <div class="card-header border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-bold">Editor de código</h5>
                                    <small class="text-muted">Crea el contenido del componente</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge  text-info ">
                                    <i class="fas fa-keyboard me-1"></i>Ctrl+S para guardar
                                </span>
                                <span class="badge bg-black text-white" id="editorStatus">
                                    Listo
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Toolbar --}}
                    <div class="card-body border-bottom p-3">
                        <div class="d-flex gap-3 align-items-center justify-content-between">
                            <!-- Action Buttons Group -->
                            <div class="btn-group mb-2" role="group" aria-label="Editor actions">
                                <button type="button" class="btn btn-secondary" id="btnFormatCode"
                                        data-bs-toggle="tooltip" title="Formatear código HTML">
                                    <i class="fas fa-wand-magic-sparkles"></i>
                                </button>
                                <button type="button" class="btn btn-secondary" id="btnRefreshPreview"
                                        data-bs-toggle="tooltip" title="Actualizar vista previa">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-secondary" id="btnToggleVariables"
                                        data-bs-toggle="tooltip" title="Mostrar panel de variables">
                                    <i class="fas fa-dollar-sign"></i>
                                </button>
                            </div>

                            <!-- Auto-save Indicator -->
                            <div class="d-flex gap-2 align-items-center ms-auto">
                                <small class="text-muted d-none d-md-inline">
                                    <i class="fas fa-circle-check text-success me-1"></i>Se guarda automáticamente
                                </small>
                                <div id="autoSaveIndicator" class="d-none">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Guardando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Component Info --}}
                    <div class="card-body border-bottom">
                        <div class="row g-3">
                            <div class="col-12 col-md-12">
                                <label for="subject" class="form-label fw-semibold">
                                    Nombre del componente
                                </label>
                                <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                       id="subject" name="subject" value="{{ old('subject', '') }}"
                                       placeholder="Ej: Header principal, Footer de emails..." required>
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="alias" class="form-label fw-semibold">
                                    Alias (ID Único)
                                </label>
                                <input type="text" class="form-control @error('alias') is-invalid @enderror"
                                       id="alias" name="alias" value="{{ old('alias', '') }}"
                                       placeholder="Ej: email_header, email_footer" required>
                                <small class="form-text text-muted">Identificador único del componente</small>
                                @error('alias')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="code" class="form-label fw-semibold">
                                    Código
                                </label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                       id="code" name="code" value="{{ old('code', '') }}"
                                       placeholder="Ej: header_01" maxlength="100" required>
                                <small class="form-text text-muted">Código de referencia (máx 100 caracteres)</small>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="type" class="form-label fw-semibold">
                                    Tipo
                                </label>
                                <select class="form-select select2 @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="partial" @if(old('type') === 'partial') selected @endif>Parcial</option>
                                    <option value="layout" @if(old('type') === 'layout') selected @endif>Layout</option>
                                    <option value="component" @if(old('type') === 'component') selected @endif>Componente</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_protected" name="is_protected" value="1" @if(old('is_protected')) checked @endif>
                                    <label class="form-check-label" for="is_protected">
                                        <strong>Componente protegido</strong>
                                        <small class="d-block text-muted">Si activas esta opción, el componente no podrá ser eliminado sin desactivar primero la protección</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="lang_id" class="form-label fw-semibold">
                                    Idioma Base
                                </label>
                                <select class="form-select select2 @error('lang_id') is-invalid @enderror" id="lang_id" name="lang_id" required>
                                    <option value="">Selecciona un idioma</option>
                                    @if(isset($langs))
                                        @foreach($langs as $language)
                                            <option value="{{ $language->id }}" @if(old('lang_id') == $language->id) selected @endif>
                                                {{ $language->title }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <small class="form-text text-muted">Se crearán versiones para todos los idiomas automáticamente</small>
                                @error('lang_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Multi-language Alert --}}
                        <div class="alert alert-info border-0 mb-0 mt-3 d-flex align-items-start">
                            <i class="fas fa-info-circle fs-5 me-2 mt-1"></i>
                            <div class="flex-grow-1">
                                <strong>Auto-generación de traducciones</strong>
                                <p class="mb-0 mt-1 small">
                                    Al crear este componente, se generarán automáticamente versiones para todos los idiomas disponibles en el sistema.
                                    El contenido inicial será el mismo para todos los idiomas, y podrás editarlos individualmente después.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Tabs Navigation --}}
                    <ul class="nav nav-tabs nav-fill border-bottom" id="editorTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="code-tab" data-bs-toggle="tab"
                                    data-bs-target="#code-panel" type="button" role="tab"
                                    aria-controls="code-panel" aria-selected="true">
                                <i class="fas fa-code me-2"></i>Código
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="preview-tab" data-bs-toggle="tab"
                                    data-bs-target="#preview-panel" type="button" role="tab"
                                    aria-controls="preview-panel" aria-selected="false">
                                <i class="fas fa-eye me-2"></i>Vista previa
                            </button>
                        </li>
                    </ul>

                    {{-- Tabs Content --}}
                    <div class="tab-content" id="editorTabsContent">
                        {{-- Tab 1: Code Editor --}}
                        <div class="tab-pane fade show active p-0" id="code-panel" role="tabpanel" aria-labelledby="code-tab">
                            <textarea class="form-control" id="content" name="content" style="display: none;">{{ old('content', '') }}</textarea>
                        </div>

                        {{-- Tab 2: Preview Panel --}}
                        <div class="tab-pane fade p-3" id="preview-panel" role="tabpanel" aria-labelledby="preview-tab">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
                                <div>
                                    <h6 class="mb-1 fw-semibold text-dark">
                                        Vista previa del email
                                    </h6>
                                    <small class="text-muted d-block">
                                        Cambia entre vistas de escritorio y móvil para ver cómo se verá tu email
                                    </small>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Device preview">
                                        <button type="button" class="btn btn-outline-primary active" id="btnDesktopViewCreate" data-width="100%" data-bs-toggle="tooltip" aria-label="Vista Desktop (100%)" data-bs-original-title="Vista Desktop (100%)">
                                            <i class="fas fa-desktop me-1"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="btnMobileViewCreate" data-width="375px" data-bs-toggle="tooltip" aria-label="Vista Mobile (375px)" data-bs-original-title="Vista Mobile (375px)">
                                            <i class="fas fa-mobile-screen me-1"></i>
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary" id="btnRefreshPreviewCreate" data-bs-toggle="tooltip" aria-label="Actualizar vista previa" data-bs-original-title="Actualizar vista previa">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="previewContainerTab" style="min-height: 500px; max-height: 700px; overflow-y: auto; background: #f8f9fa; border-radius: 4px;">
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary mb-3" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="text-muted mb-0">Cargando vista previa...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="card-footer bg-white border-top">
                        <a href="{{ route('mailers.components.index') }}" class="btn btn-secondary w-100 mb-1">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Crear componente
                        </button>
                    </div>
                </div>
            </div>

            {{-- Right Column: Preview & Variables --}}
            <div class="col-12 col-lg-4">
                {{-- Variables Panel --}}
                <div class="border-top p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                        <div>
                            <h6 class="mb-1 fw-semibold text-dark">
                                Variables disponibles
                            </h6>
                            <small class="text-muted d-block">
                                Haz clic en cualquier variable para insertarla en el editor
                            </small>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="btnLoadVariables" data-bs-toggle="tooltip" aria-label="Recargar variables" data-bs-original-title="Recargar variables">
                            <i class="fas fa-sync-alt me-1"></i>
                        </button>
                    </div>
                    <div id="variablesPanel" style="max-height: 350px; overflow-y: auto;">
                        <div class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm mb-2" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mb-0 small">Cargando variables...</p>
                        </div>
                    </div>
                </div>

                {{-- Live Preview --}}
                <div class="card">
                    <div class="card-header  border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="mb-0 fw-bold">
                               Vista previa
                            </h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary" id="previewStatus">En vivo</span>
                                <button type="button" class="btn btn-sm " id="btnTogglePreview"
                                        data-bs-toggle="tooltip" title="Expandir/Colapsar preview">
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0" id="previewWrapper">
                        <div id="previewContainer" style="min-height: 400px; max-height: 600px; overflow-y: auto; background: #f8f9fa;">
                            <div class="text-center py-5">
                                <div class="spinner-border text-success mb-3" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="text-muted mb-0">Cargando vista previa...</p>
                                <small class="text-muted">Se actualizará automáticamente al editar</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Actualización automática cada 2 segundos
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/theme/monokai.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closetag.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/matchbrackets.min.js"></script>

<!-- CodeMirror Autocomplete (Hint) -->
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/show-hint.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/html-hint.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/css-hint.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/show-hint.min.css">

<!-- Emmet for CodeMirror -->
<script src="https://cdn.jsdelivr.net/npm/emmet-codemirror@1.1.106/emmet.min.js"></script>

<!-- HTML/CSS/JS Beautifier -->
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify-html.js"></script>

<script>
// Prevent Quill from initializing on this page (we use CodeMirror instead)
console.log('🎨 CodeMirror Email Editor Loading...');

// Declare editor as global variable so it can be accessed in form submit handler
let editor;

$(document).ready(function() {
    console.log('✅ DOM Ready - Initializing CodeMirror');

    // Initialize Bootstrap Tooltips
    $('[data-bs-toggle="tooltip"]').each(function() {
        new bootstrap.Tooltip(this);
    });

    // Initialize Select2
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            allowClear: false,
            width: '100%'
        });
    }

    // Verify textarea exists before initializing CodeMirror
    const $contentTextarea = $('#content');
    if ($contentTextarea.length === 0) {
        console.error('❌ Textarea #content not found!');
        return;
    }

    console.log('📝 Textarea found, initializing CodeMirror...');

    // Initialize CodeMirror
    editor = CodeMirror.fromTextArea($contentTextarea[0], {
        mode: 'htmlmixed',
        theme: 'monokai',
        lineNumbers: true,
        lineWrapping: true,
        indentUnit: 4,
        tabSize: 4,
        indentWithTabs: false,
        autoCloseTags: true,
        autoCloseBrackets: true,
        styleActiveLine: true,
        matchBrackets: true,
        highlightSelectionMatches: {showToken: /\w/, annotateScrollbar: true},
        extraKeys: {
            'Ctrl-Space': 'autocomplete',
            'Ctrl-/': 'toggleComment',
            'Tab': 'emmetExpandAbbreviation',
            'Ctrl-Alt-Enter': 'emmetWrapWithAbbreviation'
        }
    });

    console.log('✨ CodeMirror initialized successfully!', editor);

    // Initialize Emmet for CodeMirror
    try {
        if (typeof emmetCodeMirror !== 'undefined') {
            emmetCodeMirror(editor);
            console.log('✅ Emmet initialized successfully!');
        }
    } catch (e) {
        console.warn('⚠️ Emmet not available:', e);
    }

    // Smart Autocomplete - Complete system for email templates
    CodeMirror.registerHelper('hint', 'smartHints', function(editor) {
        const cur = editor.getCursor();
        const token = editor.getTokenAt(cur);
        const line = editor.getLine(cur.line);
        const start = token.start;
        const end = cur.ch;
        const word = token.string.substring(0, end - start).toLowerCase();

        const beforeCursor = line.substring(0, cur.ch);
        const insideStyleAttr = beforeCursor.match(/style\s*=\s*["'][^"']*$/);
        const insideTag = beforeCursor.match(/<[^>]*$/);
        const afterTagName = beforeCursor.match(/<\w+\s+[\w\s-]*$/);
        const insideHref = beforeCursor.match(/href\s*=\s*["'][^"']*$/);
        const insideSrc = beforeCursor.match(/src\s*=\s*["'][^"']*$/);
        const insideAlt = beforeCursor.match(/alt\s*=\s*["'][^"']*$/);

        let completions = [];

        if (insideStyleAttr) {
            // 🎨 CSS Properties - Complete for emails
            const cssProperties = [
                // Colors & Background
                'color', 'background-color', 'background', 'opacity',
                // Dimensions
                'width', 'height', 'max-width', 'min-width', 'min-height', 'max-height',
                // Spacing
                'padding', 'padding-top', 'padding-bottom', 'padding-left', 'padding-right',
                'margin', 'margin-top', 'margin-bottom', 'margin-left', 'margin-right',
                // Borders
                'border', 'border-color', 'border-radius', 'border-width', 'border-style',
                'border-top', 'border-bottom', 'border-left', 'border-right',
                // Typography
                'font-size', 'font-weight', 'font-family', 'font-style', 'line-height',
                'text-align', 'text-decoration', 'text-transform', 'letter-spacing',
                'text-indent', 'white-space', 'word-break', 'word-wrap',
                // Layout
                'display', 'position', 'overflow', 'overflow-x', 'overflow-y',
                'visibility', 'z-index', 'float', 'clear',
                // Flexbox
                'flex', 'flex-direction', 'flex-wrap', 'flex-grow', 'flex-shrink',
                'justify-content', 'align-items', 'align-content', 'gap',
                // Effects
                'box-shadow', 'text-shadow', 'transform', 'transition', 'animation'
            ];

            completions = cssProperties
                .filter(prop => prop.toLowerCase().startsWith(word))
                .map(prop => ({text: prop + ': ', displayText: `🎨 ${prop}`}));
        }
        else if (insideHref) {
            // 🔗 URLs & Links
            const urls = [
                '{SITE_URL}', '{RESET_LINK}', 'https://', 'http://', 'mailto:', 'tel:',
                '#', 'javascript:void(0)'
            ];

            completions = urls
                .filter(url => url.toLowerCase().startsWith(word))
                .map(url => ({text: url, displayText: `🔗 ${url}`}));
        }
        else if (insideSrc) {
            // 🖼️ Image URLs & Variables
            const images = [
                '{LOGO_URL}', 'https://', 'http://', 'data:image/png;base64,'
            ];

            completions = images
                .filter(img => img.toLowerCase().startsWith(word))
                .map(img => ({text: img, displayText: `🖼️ ${img}`}));
        }
        else if (insideAlt) {
            // 📝 Common alt text
            const alts = [
                'Logo', 'Banner', 'Product Image', 'Company Logo', 'Hero Image', 'Icon',
                'Button Image', 'Social Icon', 'Header Image', 'Footer Image'
            ];

            completions = alts
                .filter(alt => alt.toLowerCase().startsWith(word))
                .map(alt => ({text: alt, displayText: `📝 ${alt}`}));
        }
        else if (afterTagName) {
            // 🏷️ HTML Attributes - Email focused
            const htmlAttributes = [
                // Basic
                'id', 'class', 'style', 'dir',
                // Links & Media
                'href', 'src', 'alt', 'title', 'target',
                // Form
                'name', 'value', 'type', 'placeholder', 'required', 'disabled', 'readonly',
                // Table (Email common)
                'cellpadding', 'cellspacing', 'border', 'bordercolor', 'align', 'valign',
                'colspan', 'rowspan', 'bgcolor', 'width', 'height',
                // Email specific
                'role', 'aria-label', 'aria-describedby',
                // Events
                'onclick', 'onload', 'onmouseover', 'onmouseout',
                // Others
                'action', 'method', 'enctype', 'tabindex', 'lang'
            ];

            completions = htmlAttributes
                .filter(attr => attr.toLowerCase().startsWith(word))
                .map(attr => ({text: attr + '="', displayText: `🏷️ ${attr}`}));
        }
        else if (insideTag) {
            // 📦 Classes - Bootstrap & Custom
            const classes = [
                // Email container classes
                'email-wrapper', 'email-container', 'email-body', 'email-footer',
                'email-header', 'email-content', 'email-section',
                // Bootstrap Layout
                'container', 'container-fluid', 'row', 'col', 'col-12', 'col-6', 'col-4', 'col-3',
                'col-md-6', 'col-lg-6', 'col-xl-6',
                // Spacing
                'mt-1', 'mt-2', 'mt-3', 'mt-4', 'mt-5', 'mb-1', 'mb-2', 'mb-3', 'mb-4', 'mb-5',
                'p-1', 'p-2', 'p-3', 'p-4', 'p-5', 'px-2', 'py-2', 'pt-2', 'pb-2',
                'ms-1', 'me-1', 'ms-auto',
                // Display & Flex
                'd-flex', 'd-block', 'd-none', 'd-inline', 'd-grid',
                'flex-column', 'flex-row', 'justify-content-center', 'justify-content-between',
                'justify-content-end', 'align-items-center', 'align-items-start', 'gap-2', 'gap-3',
                // Colors
                'text-primary', 'text-success', 'text-danger', 'text-warning', 'text-muted',
                'bg-primary', 'bg-success', 'bg-danger', 'bg-warning', 'bg-light',
                // Typography
                'text-center', 'text-end', 'text-start', 'fw-bold', 'fw-normal', 'small', 'lead',
                // Buttons
                'btn', 'btn-primary', 'btn-secondary', 'btn-success', 'btn-danger',
                'btn-outline-primary', 'btn-lg', 'btn-sm',
                // Cards & Sections
                'card', 'card-body', 'card-header', 'card-footer', 'card-title', 'section-block',
                // Tables
                'table', 'table-striped', 'table-hover', 'table-bordered', 'table-responsive',
                // Forms
                'form-control', 'form-group', 'form-label', 'input-group',
                // Alerts
                'alert', 'alert-primary', 'alert-success', 'alert-danger', 'alert-warning',
                // Utilities
                'rounded', 'shadow', 'border', 'h-100', 'w-100', 'overflow-hidden',
                'text-truncate', 'text-uppercase', 'text-lowercase'
            ];

            completions = classes
                .filter(cls => cls.toLowerCase().startsWith(word))
                .map(cls => ({text: cls, displayText: `📦 ${cls}`}));
        }
        else {
            // 🏷️ HTML Tags - Email template focused
            const htmlTags = [
                // Email structure
                'html', 'head', 'body', 'meta', 'title',
                // Containers
                'div', 'section', 'header', 'footer', 'main', 'article',
                // Tables (Email common)
                'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
                // Content
                'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'span', 'strong', 'em', 'u', 'code', 'pre', 'blockquote',
                // Lists
                'ul', 'ol', 'li', 'dl', 'dt', 'dd',
                // Media
                'img', 'picture', 'figure', 'figcaption',
                // Links & Navigation
                'a', 'nav', 'menu',
                // Forms
                'form', 'input', 'button', 'label', 'select', 'textarea', 'fieldset',
                // Text
                'br', 'hr', 'small', 'mark', 'del', 'ins', 'sub', 'sup',
                // Semantic
                'aside', 'address', 'time'
            ];

            completions = htmlTags
                .filter(tag => tag.toLowerCase().startsWith(word))
                .map(tag => ({text: tag, displayText: `🏷️ ${tag}`}));
        }

        return {
            from: CodeMirror.Pos(cur.line, start),
            to: CodeMirror.Pos(cur.line, end),
            list: completions
        };
    });

    // 🔤 Template Variables Hint
    CodeMirror.registerHelper('hint', 'variables', function(editor) {
        const cur = editor.getCursor();
        const token = editor.getTokenAt(cur);
        const line = editor.getLine(cur.line);
        const start = token.start;
        const end = cur.ch;

        const beforeCursor = line.substring(0, cur.ch);
        if (!beforeCursor.includes('{')) return;

        const word = token.string.substring(0, end - start).toUpperCase();

        const templateVariables = [
            // Site
            'SITE_NAME', 'SITE_URL', 'SITE_EMAIL', 'LOGO_URL',
            // Company
            'COMPANY_NAME', 'COMPANY_ADDRESS', 'COMPANY_CITY', 'COMPANY_STATE', 'COMPANY_ZIP',
            'COMPANY_COUNTRY', 'COMPANY_PHONE', 'COMPANY_EMAIL', 'COMPANY_WEBSITE',
            // Dates
            'CURRENT_YEAR', 'CURRENT_MONTH', 'CURRENT_DAY', 'CURRENT_DATE', 'CURRENT_TIME',
            // Recipient
            'RECIPIENT_EMAIL', 'CUSTOMER_NAME', 'CUSTOMER_FIRST_NAME', 'CUSTOMER_LAST_NAME',
            'CUSTOMER_PHONE', 'CUSTOMER_ADDRESS',
            // Email
            'EMAIL_SUBJECT', 'EMAIL_TITLE', 'CONTENT', 'FOOTER_CONTENT',
            // Links
            'RESET_LINK', 'CONFIRM_LINK', 'ACTIVATION_LINK', 'UNSUBSCRIBE_LINK',
            // Special
            'CURRENT_YEAR_FULL', 'MONTH_NAME', 'DAY_NAME'
        ];

        const completions = templateVariables
            .filter(v => v.startsWith(word))
            .map(v => ({text: v + '}', displayText: `🔤 {${v}}`}));

        return {
            from: CodeMirror.Pos(cur.line, start),
            to: CodeMirror.Pos(cur.line, end),
            list: completions
        };
    });

    // Enable autocomplete on input
    editor.on('inputRead', function(instance, changeObj) {
        if (changeObj.text[0] === ' ' || /\w/.test(changeObj.text[0]) || changeObj.text[0] === '-') {
            CodeMirror.commands.autocomplete(instance, null, {async: true});
        }
    });
    console.log('📊 Editor has', editor.lineCount(), 'lines');

    let isPreviewExpanded = false;
    let previewTimeout;
    let hasChanges = false;

    // Update Editor Status
    function updateEditorStatus(status, icon = 'check-circle', color = 'success') {
        $('#editorStatus')
            .html(`<i class="fas fa-${icon} me-1"></i>${status}`)
            .attr('class', `badge bg-${color}-subtle text-${color} border border-${color}`);
    }

    // Update Preview Status
    function updatePreviewStatus(status, icon = 'circle', color = 'success') {
        $('#previewStatus')
            .text(status)
            .attr('class', 'badge bg-primary');
    }

    // Update Preview (local, no backend call - updates both containers)
    function updatePreview() {
        updatePreviewStatus('Actualizando...', 'spinner', 'warning');

        const html = editor.getValue();
        const $container = $('#previewContainer');
        const $containerTab = $('#previewContainerTab');
        const height = isPreviewExpanded ? '800px' : '400px';

        const $iframe = $('<iframe>')
            .css({
                'width': '100%',
                'min-height': height,
                'border': 'none',
                'display': 'block',
                'background': 'white'
            });

        const $iframeTab = $('<iframe>')
            .css({
                'width': '100%',
                'border': 'none',
                'display': 'block',
                'background': 'white',
                'min-height': '500px'
            });

        $container.empty().append($iframe);
        $containerTab.empty().append($iframeTab);

        $iframe[0].srcdoc = html;
        $iframeTab[0].srcdoc = html;

        updatePreviewStatus('En vivo', 'circle-dot-filled', 'success');
    }

    // Load Variables
    function loadVariables() {
        const variablesUrl = '{{ route('mailers.components.variables') }}';

        $.ajax({
            url: variablesUrl,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    renderVariables(data.variables);
                }
            },
            error: function(error) {
                console.error('Error loading variables:', error);
                $('#variablesPanel').html(
                    '<div class="alert alert-danger m-2"><i class="fas fa-exclamation-circle me-2"></i>Error al cargar variables</div>'
                );
            }
        });
    }

    // Render Variables
    function renderVariables(variableGroups) {
        let html = '<div class="row g-1">';

        $.each(variableGroups, function(idx, group) {
            $.each(group.items, function(idx, variable) {
                html += `<div class="col-6 col-md-4 col-lg-3">`;
                html += `<div class="variable-card variable-insert" data-variable-name="${variable.name}" data-bs-toggle="tooltip" title="${variable.description}">`;
                html += `<code class="variable-code">{${variable.name}}</code>`;
                html += `</div>`;
                html += `</div>`;
            });
        });

        html += '</div>';

        $('#variablesPanel').html(html);

        // Initialize tooltips for new elements
        $('[data-bs-toggle="tooltip"]').each(function() {
            new bootstrap.Tooltip(this);
        });

        // Add event listeners with jQuery
        $(document).on('click', '.variable-insert', function(e) {
            e.preventDefault();
            const variableName = $(this).data('variable-name');
            const variable = `{${variableName}}`;
            const cursor = editor.getCursor();
            editor.replaceRange(variable, cursor);
            editor.focus();

            // Show feedback
            toastr.success(`Variable ${variable} insertada`, 'Éxito', {
                timeOut: 2000,
                progressBar: true
            });
        });
    }

    // Format Code
    function formatCode() {
        updateEditorStatus('Formateando...', 'spinner', 'info');
        const code = editor.getValue();

        try {
            const indentSize = 4;
            const indent = ' ';
            let result = [];
            let indentLevel = 0;

            const selfClosingTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

            let processed = code
                .replace(/>\s*</g, '>\n<')
                .replace(/}\s*(?=[a-zA-Z])/g, '}\n')
                .replace(/{/g, ' {\n')
                .replace(/;/g, ';\n')
                .split('\n');

            processed.forEach(function(line) {
                line = line.trim();
                if (line.length === 0) return;

                if (line.startsWith('}')) {
                    indentLevel = Math.max(0, indentLevel - 1);
                }

                if (line.startsWith('</')) {
                    indentLevel = Math.max(0, indentLevel - 1);
                }

                result.push(indent.repeat(indentLevel * indentSize) + line);

                if (line.endsWith('{')) {
                    indentLevel++;
                }

                if (line.startsWith('<') && !line.startsWith('</') && !line.startsWith('<!')) {
                    const tagMatch = line.match(/<(\w+)/);
                    if (tagMatch) {
                        const tagName = tagMatch[1].toLowerCase();
                        const isSelfClosing = selfClosingTags.includes(tagName) || line.endsWith('/>');

                        if (!isSelfClosing) {
                            indentLevel++;
                        }
                    }
                }
            });

            let formatted = result.join('\n').trim();
            formatted = formatted.replace(/\n\s*\n/g, '\n');

            editor.setValue(formatted);
            updateEditorStatus('Listo', 'circle-check', 'success');

            toastr.success('Código formateado correctamente', 'Éxito', {
                timeOut: 2000,
                progressBar: true
            });
        } catch (error) {
            console.error('Format error:', error);
            updateEditorStatus('Error', 'alert-circle', 'danger');
            toastr.error('Error al formatear el código', 'Error');
        }
    }

    // Toggle Preview Size
    function togglePreview() {
        isPreviewExpanded = !isPreviewExpanded;
        const $btn = $('#btnTogglePreview');
        const $container = $('#previewContainer');

        if (isPreviewExpanded) {
            $container.css('max-height', '1200px');
            $btn.html('<i class="fas fa-compress"></i>');
        } else {
            $container.css('max-height', '600px');
            $btn.html('<i class="fas fa-expand"></i>');
        }

        updatePreview();
    }

    // Toggle Variables Panel (Mobile)
    function toggleVariables() {
        $('#variablesCard').toggleClass('d-none');
    }

    // Initial load
    updatePreview();
    loadVariables();

    // Auto-update preview on change
    editor.on('change', function() {
        hasChanges = true;
        updateEditorStatus('Modificado', 'pencil', 'warning');
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(function() {
            updatePreview();
            updateEditorStatus('Listo', 'circle-check', 'success');
        }, 2000);
    });

    // Button: Refresh Preview (Toolbar)
    $('#btnRefreshPreview').on('click', function(e) {
        e.preventDefault();
        updatePreview();
    });

    // Button: Refresh Preview (Tab)
    $('#btnRefreshPreviewTab').on('click', function(e) {
        e.preventDefault();
        updatePreview();
        $(this).prop('disabled', true);
        setTimeout(() => $(this).prop('disabled', false), 1000);
    });

    // Tab: Update preview when preview tab is shown
    $('#preview-tab').on('shown.bs.tab', function (e) {
        updatePreview();
    });

    // Button: Load Variables
    $('#btnLoadVariables').on('click', function(e) {
        e.preventDefault();
        loadVariables();
        toastr.info('Recargando variables...', 'Información');
    });

    // Button: Format Code
    $('#btnFormatCode').on('click', function(e) {
        e.preventDefault();
        formatCode();
    });

    // Button: Toggle Preview
    $('#btnTogglePreview').on('click', function(e) {
        e.preventDefault();
        togglePreview();
    });

    // Button: Toggle Variables (Mobile)
    $('#btnToggleVariables').on('click', function(e) {
        e.preventDefault();
        toggleVariables();
    });

    // Button: Desktop View (Preview Tab)
    $('#btnDesktopViewCreate').on('click', function(e) {
        e.preventDefault();
        $('#previewContainerTab').css('width', '100%');
        $('#btnDesktopViewCreate').addClass('active');
        $('#btnMobileViewCreate').removeClass('active');
    });

    // Button: Mobile View (Preview Tab)
    $('#btnMobileViewCreate').on('click', function(e) {
        e.preventDefault();
        $('#previewContainerTab').css('width', '375px');
        $('#btnMobileViewCreate').addClass('active');
        $('#btnDesktopViewCreate').removeClass('active');
    });

    // Button: Refresh Preview (Create Tab)
    $('#btnRefreshPreviewCreate').on('click', function(e) {
        e.preventDefault();
        updatePreview();
        $(this).prop('disabled', true);
        setTimeout(() => $(this).prop('disabled', false), 1000);
    });

    // Ctrl+S to save
    editor.setOption('extraKeys', {
        'Ctrl-S': function(cm) {
            $('#formCreate').submit();
        },
        'Ctrl-/': 'toggleComment'
    });

    // Sync textarea before submit
    $('#formCreate').on('submit', function(e) {
        $('#content').val(editor.getValue());

        // Show saving indicator
        toastr.info('Guardando cambios...', 'Información', {
            timeOut: 0,
            extendedTimeOut: 0
        });
    });
});
</script>
@endpush

@endsection
