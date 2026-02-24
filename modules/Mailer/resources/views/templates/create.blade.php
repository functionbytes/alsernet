@extends('layouts.theme')

@section('page_title', 'Crear Plantilla de Email')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Crear Nueva Plantilla de Email',
    ])

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fs-4 me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="fas fa-exclamation-circle fs-4 me-2 mt-1"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-2">Errores de Validación</h6>
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
    <form method="POST" action="{{ route('mailers.templates.store') }}" id="formCreate">
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
                                    <small class="text-muted">Crea el contenido de la plantilla</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge text-info">
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
                        <div class="d-flex gap-3 align-items-center justify-content-between flex-wrap">
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
                            </div>

                            <!-- Variable Selector -->
                            <div class="flex-grow-1 mb-2" style="max-width: 400px;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-primary text-white">
                                        <i class="fas fa-code me-1"></i>Variable
                                    </span>
                                    <select class="form-select form-select-sm" id="variableSelector">
                                        <option value="">-- Selecciona una variable --</option>
                                    </select>
                                    <button class="btn btn-primary" type="button" id="btnInsertVariable"
                                            data-bs-toggle="tooltip" title="Insertar variable en el cursor">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="d-flex gap-2 align-items-center mb-2">
                                <small class="text-muted d-none d-md-inline">
                                    <i class="fas fa-lightbulb me-1"></i>Usa Emmet para escribir más rápido
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- Template Info --}}
                    <div class="card-body border-bottom">
                        <div class="row g-3">
                            <div class="col-12 col-md-12">
                                <label for="key" class="form-label fw-semibold">
                                    Clave (Key) <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('key') is-invalid @enderror"
                                       id="key" name="key" value="{{ old('key') }}"
                                       placeholder="order_confirmation" required>
                                <small class="text-muted">Identificador único para usar en código</small>
                                @error('key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-12">
                                <label for="name" class="form-label fw-semibold">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}"
                                       placeholder="Confirmación de Pedido" required>
                                <small class="text-muted">Nombre descriptivo de la plantilla</small>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-12">
                                <label for="subject" class="form-label fw-semibold">
                                    Asunto del email <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                       id="subject" name="subject" value="{{ old('subject') }}"
                                       placeholder="Confirmación de pedido #{ORDER_NUMBER}" required>
                                <small class="text-muted">Puedes usar variables: {CUSTOMER_NAME}, {ORDER_NUMBER}</small>
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-12">
                                <label for="preheader" class="form-label fw-semibold">
                                    Preheader <span class="text-muted small">(Opcional)</span>
                                </label>
                                <input type="text" class="form-control @error('preheader') is-invalid @enderror"
                                       id="preheader" name="preheader" value="{{ old('preheader') }}"
                                       placeholder="Texto de vista previa en bandeja de entrada"
                                       maxlength="255">
                                <small class="text-muted">Aparece junto al asunto en Gmail, Outlook, etc.</small>
                                @error('preheader')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-12">
                                <label for="module" class="form-label fw-semibold">
                                    Módulo <span class="text-danger">*</span>
                                </label>
                                <select class="form-select select2 @error('module') is-invalid @enderror"
                                        id="module" name="module" required>
                                    <option value="">-- Selecciona --</option>
                                    <option value="core" @if(old('module', $module ?? '') == 'core') selected @endif>Core (Sistema)</option>
                                    <option value="documents" @if(old('module', $module ?? '') == 'documents') selected @endif>Documentos</option>
                                    <option value="orders" @if(old('module', $module ?? '') == 'orders') selected @endif>Órdenes</option>
                                    <option value="notifications" @if(old('module', $module ?? '') == 'notifications') selected @endif>Notificaciones</option>
                                </select>
                                <small class="text-muted">Determina las variables disponibles</small>
                                @error('module')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-12">
                                <label for="layout_id" class="form-label fw-semibold">
                                    Layout base <span class="text-muted small">(Opcional)</span>
                                </label>
                                <select class="form-select select2 @error('layout_id') is-invalid @enderror" id="layout_id" name="layout_id">
                                    <option value="">Sin layout (solo contenido)</option>
                                    @if(isset($layouts))
                                        @foreach($layouts as $layout)
                                            <option value="{{ $layout->id }}"
                                                @if(old('layout_id') == $layout->id) selected @endif>
                                                {{ $layout->alias }} - {{ $layout->translate($currentLangId)?->subject ?? 'Sin nombre' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <small class="text-muted">Layout personalizado para esta plantilla</small>
                                @error('layout_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-12">
                                <label for="lang_id" class="form-label fw-semibold">
                                    Idioma base <span class="text-danger">*</span>
                                </label>
                                <select class="form-select select2 @error('lang_id') is-invalid @enderror" id="lang_id" name="lang_id" required>
                                    <option value="">Selecciona un idioma</option>
                                    @if(isset($langs))
                                        @foreach($langs as $language)
                                            <option value="{{ $language->id }}" @if(old('lang_id', $langId ?? '') == $language->id) selected @endif>
                                                {{ $language->title }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <small class="text-muted">Se crearán versiones para todos los idiomas</small>
                                @error('lang_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label fw-semibold">
                                    Descripción <span class="text-muted small">(Opcional)</span>
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description" name="description" rows="2"
                                          placeholder="Descripción breve de para qué se usa esta plantilla">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_enabled" name="is_enabled" value="1" checked>
                                    <label class="form-check-label" for="is_enabled">
                                        <strong>Plantilla habilitada</strong>
                                        <small class="d-block text-muted">Si desactivas esta opción, la plantilla no se podrá usar en el sistema</small>
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_protected" name="is_protected" value="1">
                                    <label class="form-check-label" for="is_protected">
                                        <strong>Plantilla protegida</strong>
                                        <small class="d-block text-muted">Si activas esta opción, la plantilla no podrá ser eliminada sin desactivar primero la protección</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Multi-language Alert --}}
                        <div class="alert alert-info border-0 mb-0 mt-3 d-flex align-items-start">
                            <i class="fas fa-info-circle fs-5 me-2 mt-1"></i>
                            <div class="flex-grow-1">
                                <strong>Auto-generación de traducciones</strong>
                                <p class="mb-0 mt-1 small">
                                    Al crear esta plantilla, se generarán automáticamente versiones para todos los idiomas disponibles en el sistema.
                                    El contenido inicial será el mismo para todos los idiomas, y podrás editarlos individualmente después.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Tabs: Code Editor & Preview --}}
                    <div class="card-body p-0">
                        <ul class="nav nav-tabs nav-fill border-bottom" id="editorTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="code-tab" data-bs-toggle="tab" data-bs-target="#code-panel" type="button" role="tab" aria-controls="code-panel" aria-selected="true">
                                    <i class="fas fa-code me-2"></i>Editor de Código
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview-panel" type="button" role="tab" aria-controls="preview-panel" aria-selected="false">
                                    <i class="fas fa-eye me-2"></i>Vista Previa
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content" id="editorTabsContent">
                            <div class="tab-pane fade show active" id="code-panel" role="tabpanel" aria-labelledby="code-tab">
                                <textarea class="form-control" id="content" name="content" style="display: none;">{{ old('content', $baseContent ?? '') }}</textarea>
                            </div>
                            <div class="tab-pane fade p-3" id="preview-panel" role="tabpanel" aria-labelledby="preview-tab">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
                                    <div>
                                        <h6 class="mb-1 fw-semibold text-dark">
                                            <i class="fas fa-eye me-2 text-primary"></i>Vista previa del email
                                        </h6>
                                        <small class="text-muted d-block">
                                            Cambia entre vistas de escritorio y móvil para ver cómo se verá tu email
                                        </small>
                                    </div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Device preview">
                                            <button type="button" class="btn btn-outline-primary active" id="btnDesktopViewCreate" data-width="100%"
                                                    data-bs-toggle="tooltip" title="Vista Desktop (100%)">
                                                <i class="fas fa-desktop me-1"></i><span class="d-none d-sm-inline">Desktop</span>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" id="btnMobileViewCreate" data-width="375px"
                                                    data-bs-toggle="tooltip" title="Vista Mobile (375px)">
                                                <i class="fas fa-mobile-screen me-1"></i><span class="d-none d-sm-inline">Mobile</span>
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-success" id="btnRefreshPreviewCreate"
                                                data-bs-toggle="tooltip" title="Actualizar vista previa">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="previewContainerTab" style="min-height: 550px; overflow-y: auto; background: #f8f9fa; display: flex; justify-content: center; padding: 20px;">
                                    <div class="text-center py-5">
                                        <i class="fas fa-code fs-1 text-muted mb-3 d-block"></i>
                                        <p class="text-muted mb-0">Vista previa en vivo</p>
                                        <small class="text-muted">Cambia a la pestaña "Vista Previa" para ver el resultado</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="card-footer bg-white border-top">
                        <button type="submit" class="btn btn-primary w-100  mb-1">
                            Crear
                        </button>
                        <a href="{{ route('mailers.templates.index') }}" class="btn btn-secondary w-100">
                            Volver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Right Column: Variables --}}
            <div class="col-lg-4">
                <!-- Variables disponibles -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            Variables disponibles
                        </h6>
                        <p class="card-text text-muted mb-3">
                            Haz clic en cualquier variable para insertarla en el cursor del editor.
                        </p>
                        <div class="small">
                            <div class="mb-2">
                                <code class="variable-tag">{reviewer_name}</code>
                                <span class="text-muted d-block ps-2">Nombre del reviewer</span>
                            </div>
                            <div class="mb-2">
                                <code class="variable-tag">{location_name}</code>
                                <span class="text-muted d-block ps-2">Nombre del negocio/ubicación</span>
                            </div>
                            <div class="mb-2">
                                <code class="variable-tag">{star_rating}</code>
                                <span class="text-muted d-block ps-2">Calificación en estrellas</span>
                            </div>
                            <div class="mb-2">
                                <code class="variable-tag">{comment_summary}</code>
                                <span class="text-muted d-block ps-2">Resumen del comentario</span>
                            </div>
                            <div class="mb-0">
                                <code class="variable-tag">{date}</code>
                                <span class="text-muted d-block ps-2">Fecha de la reseña</span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            Ejemplo de uso
                        </h6>
                        <div class=" text-muted bg-light p-3 rounded">
                            <p class="mb-0">
                                Hola {reviewer_name}, muchas gracias por tu reseña de {star_rating} estrellas en {location_name}.
                                Tu opinión es muy importante para nosotros y nos ayuda a mejorar cada día.
                            </p>
                        </div>
                    </div>
                    <hr>
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            Consejos para escribir respuestas
                        </h6>
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Sé personal y auténtico en tus respuestas</li>
                            <li class="mb-2">Agradece siempre el tiempo dedicado</li>
                            <li class="mb-2">Responde específicamente a los comentarios</li>
                            <li class="mb-2">Mantén un tono profesional y amable</li>
                            <li>Usa las variables para personalizar</li>
                        </ul>
                    </div>

                    <!-- Estadísticas de uso -->
                    @if($template->usage_count > 0)
                        <hr>
                        <div class="card-body">
                            <h6 class="card-title mb-3">
                                Estadísticas de uso
                            </h6>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-chart-line text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <div>
                                    <p class="mb-0  text-muted">Veces utilizada</p>
                                    <h4 class="mb-0">{{ $template->usage_count }}</h4>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>

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
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/show-hint.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/html-hint.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/css-hint.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/hint/show-hint.min.css">
<script src="https://cdn.jsdelivr.net/npm/emmet-codemirror@1.1.106/emmet.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify-html.js"></script>

<script>
console.log('🎨 CodeMirror Email Template Creator Loading...');

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

    const $contentTextarea = $('#content');
    if ($contentTextarea.length === 0) {
        console.error('❌ Textarea #content not found!');
        return;
    }

    console.log('📝 Textarea found, initializing CodeMirror...');

    // Initialize CodeMirror
    const editor = CodeMirror.fromTextArea($contentTextarea[0], {
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

    // Initialize Emmet
    try {
        if (typeof emmetCodeMirror !== 'undefined') {
            emmetCodeMirror(editor);
            console.log('✅ Emmet initialized successfully!');
        }
    } catch (e) {
        console.warn('⚠️ Emmet not available:', e);
    }

    // Smart Autocomplete - For email templates
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
            const cssProperties = [
                'color', 'background-color', 'background', 'opacity',
                'width', 'height', 'max-width', 'min-width', 'min-height', 'max-height',
                'padding', 'padding-top', 'padding-bottom', 'padding-left', 'padding-right',
                'margin', 'margin-top', 'margin-bottom', 'margin-left', 'margin-right',
                'border', 'border-color', 'border-radius', 'border-width', 'border-style',
                'border-top', 'border-bottom', 'border-left', 'border-right',
                'font-size', 'font-weight', 'font-family', 'font-style', 'line-height',
                'text-align', 'text-decoration', 'text-transform', 'letter-spacing',
                'text-indent', 'white-space', 'word-break', 'word-wrap',
                'display', 'position', 'overflow', 'overflow-x', 'overflow-y',
                'visibility', 'z-index', 'float', 'clear',
                'flex', 'flex-direction', 'flex-wrap', 'flex-grow', 'flex-shrink',
                'justify-content', 'align-items', 'align-content', 'gap',
                'box-shadow', 'text-shadow', 'transform', 'transition', 'animation'
            ];

            completions = cssProperties
                .filter(prop => prop.toLowerCase().startsWith(word))
                .map(prop => ({text: prop + ': ', displayText: `🎨 ${prop}`}));
        }
        else if (insideHref) {
            const urls = [
                '{SITE_URL}', '{RESET_LINK}', 'https://', 'http://', 'mailto:', 'tel:',
                '#', 'javascript:void(0)'
            ];

            completions = urls
                .filter(url => url.toLowerCase().startsWith(word))
                .map(url => ({text: url, displayText: `🔗 ${url}`}));
        }
        else if (insideSrc) {
            const images = [
                '{LOGO_URL}', 'https://', 'http://', 'data:image/png;base64,'
            ];

            completions = images
                .filter(img => img.toLowerCase().startsWith(word))
                .map(img => ({text: img, displayText: `🖼️ ${img}`}));
        }
        else if (insideAlt) {
            const alts = [
                'Logo', 'Banner', 'Product Image', 'Company Logo', 'Hero Image', 'Icon',
                'Button Image', 'Social Icon', 'Header Image', 'Footer Image'
            ];

            completions = alts
                .filter(alt => alt.toLowerCase().startsWith(word))
                .map(alt => ({text: alt, displayText: `📝 ${alt}`}));
        }
        else if (afterTagName) {
            const htmlAttributes = [
                'id', 'class', 'style', 'dir',
                'href', 'src', 'alt', 'title', 'target',
                'name', 'value', 'type', 'placeholder', 'required', 'disabled', 'readonly',
                'cellpadding', 'cellspacing', 'border', 'bordercolor', 'align', 'valign',
                'colspan', 'rowspan', 'bgcolor', 'width', 'height',
                'role', 'aria-label', 'aria-describedby',
                'onclick', 'onload', 'onmouseover', 'onmouseout',
                'action', 'method', 'enctype', 'tabindex', 'lang'
            ];

            completions = htmlAttributes
                .filter(attr => attr.toLowerCase().startsWith(word))
                .map(attr => ({text: attr + '="', displayText: `🏷️ ${attr}`}));
        }
        else if (insideTag) {
            const classes = [
                'email-wrapper', 'email-container', 'email-body', 'email-footer',
                'email-header', 'email-content', 'email-section',
                'container', 'container-fluid', 'row', 'col', 'col-12', 'col-6', 'col-4', 'col-3',
                'col-md-6', 'col-lg-6', 'col-xl-6',
                'mt-1', 'mt-2', 'mt-3', 'mt-4', 'mt-5', 'mb-1', 'mb-2', 'mb-3', 'mb-4', 'mb-5',
                'p-1', 'p-2', 'p-3', 'p-4', 'p-5', 'px-2', 'py-2', 'pt-2', 'pb-2',
                'ms-1', 'me-1', 'ms-auto',
                'd-flex', 'd-block', 'd-none', 'd-inline', 'd-grid',
                'flex-column', 'flex-row', 'justify-content-center', 'justify-content-between',
                'justify-content-end', 'align-items-center', 'align-items-start', 'gap-2', 'gap-3',
                'text-primary', 'text-success', 'text-danger', 'text-warning', 'text-muted',
                'bg-primary', 'bg-success', 'bg-danger', 'bg-warning', 'bg-light',
                'text-center', 'text-end', 'text-start', 'fw-bold', 'fw-normal', 'small', 'lead',
                'btn', 'btn-primary', 'btn-secondary', 'btn-success', 'btn-danger',
                'btn-outline-primary', 'btn-lg', 'btn-sm',
                'card', 'card-body', 'card-header', 'card-footer', 'card-title', 'section-block',
                'table', 'table-striped', 'table-hover', 'table-bordered', 'table-responsive',
                'form-control', 'form-group', 'form-label', 'input-group',
                'alert', 'alert-primary', 'alert-success', 'alert-danger', 'alert-warning',
                'rounded', 'shadow', 'border', 'h-100', 'w-100', 'overflow-hidden',
                'text-truncate', 'text-uppercase', 'text-lowercase'
            ];

            completions = classes
                .filter(cls => cls.toLowerCase().startsWith(word))
                .map(cls => ({text: cls, displayText: `📦 ${cls}`}));
        }
        else {
            const htmlTags = [
                'html', 'head', 'body', 'meta', 'title',
                'div', 'section', 'header', 'footer', 'main', 'article',
                'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
                'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'span', 'strong', 'em', 'u', 'code', 'pre', 'blockquote',
                'ul', 'ol', 'li', 'dl', 'dt', 'dd',
                'img', 'picture', 'figure', 'figcaption',
                'a', 'nav', 'menu',
                'form', 'input', 'button', 'label', 'select', 'textarea', 'fieldset',
                'br', 'hr', 'small', 'mark', 'del', 'ins', 'sub', 'sup',
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

    // Template Variables Hint
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
            'SITE_NAME', 'SITE_URL', 'SITE_EMAIL', 'LOGO_URL',
            'COMPANY_NAME', 'COMPANY_ADDRESS', 'COMPANY_CITY', 'COMPANY_STATE', 'COMPANY_ZIP',
            'COMPANY_COUNTRY', 'COMPANY_PHONE', 'COMPANY_EMAIL', 'COMPANY_WEBSITE',
            'CURRENT_YEAR', 'CURRENT_MONTH', 'CURRENT_DAY', 'CURRENT_DATE', 'CURRENT_TIME',
            'RECIPIENT_EMAIL', 'CUSTOMER_NAME', 'CUSTOMER_FIRST_NAME', 'CUSTOMER_LAST_NAME',
            'CUSTOMER_PHONE', 'CUSTOMER_ADDRESS',
            'EMAIL_SUBJECT', 'EMAIL_TITLE', 'CONTENT', 'FOOTER_CONTENT',
            'RESET_LINK', 'CONFIRM_LINK', 'ACTIVATION_LINK', 'UNSUBSCRIBE_LINK',
            'ORDER_ID', 'ORDER_NUMBER', 'ORDER_TOTAL', 'ORDER_STATUS', 'ORDER_DATE',
            'DOCUMENT_TYPE', 'UPLOAD_LINK', 'EXPIRATION_DATE',
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

    let previewTimeout;
    let hasChanges = false;

    // Update Editor Status
    function updateEditorStatus(status, icon = 'check-circle', color = 'success') {
        $('#editorStatus')
            .html(`<i class="fas fa-${icon} me-1"></i>${status}`)
            .attr('class', `badge bg-${color}`);
    }

    // Update Preview (local, no backend call for create)
    function updatePreview() {
        const html = editor.getValue();
        const $container = $('#previewContainerTab');
        const $iframe = $('<iframe>')
            .css({
                'width': '100%',
                'min-height': '550px',
                'border': 'none',
                'display': 'block',
                'background': 'white'
            });

        $container.empty().append($iframe);
        $iframe[0].srcdoc = html;
    }

    // Update preview when switching to preview tab
    $('#preview-tab').on('shown.bs.tab', function (e) {
        updatePreview();
    });

    // Load Variables based on module
    function loadVariables() {
        const module = $('#module').val();

        if (!module) {
            $('#variablesPanel').html(
                '<div class="text-center py-4 text-muted"><i class="fas fa-info-circle fs-3 mb-2 d-block"></i><p class="mb-0 small">Selecciona un módulo para ver las variables disponibles</p></div>'
            );
            return;
        }

        // Show loading
        $('#variablesPanel').html(
            '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm mb-2" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mb-0 small">Cargando variables...</p></div>'
        );

        // Call API to load variables for the module
        $.ajax({
            url: '{{ route('mailers.templates.variables-by-module') }}',
            type: 'GET',
            data: { module: module },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    renderVariables(data.variables);
                } else {
                    $('#variablesPanel').html(
                        '<div class="alert alert-warning m-2"><i class="fas fa-warning me-2"></i>No hay variables disponibles</div>'
                    );
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

    // Render Variables (grouped by category)
    function renderVariables(variableGroups) {
        // Render in sidebar panel
        let html = '';

        $.each(variableGroups, function(groupIdx, group) {
            // Skip "Cliente" category
            if (group.group === 'Cliente') return true;

            $.each(group.items, function(idx, variable) {
                html += `<div class="mb-2 pb-2 border-bottom variable-insert" data-variable-name="${variable.name}">`;
                html += `<a class="text-decoration-none d-block" onclick="return false;">`;
                html += `<code class="d-inline-block bg-light px-2 py-1 text-primary fw-bold">{${variable.name}}</code>`;
                html += `</a>`;
                html += `</div>`;
            });
        });

        $('#variablesPanel').html(html);

        // Render in top selector (toolbar)
        let selectorOptions = '<option value="">-- Selecciona una variable --</option>';

        $.each(variableGroups, function(groupIdx, group) {
            // Skip "Cliente" category
            if (group.group === 'Cliente') return true;

            selectorOptions += `<optgroup label="${group.group}">`;
            $.each(group.items, function(idx, variable) {
                selectorOptions += `<option value="${variable.name}">{${variable.name}}</option>`;
            });
            selectorOptions += `</optgroup>`;
        });

        $('#variableSelector').html(selectorOptions);

        // Add event listeners for sidebar
        $(document).on('click', '.variable-insert', function(e) {
            e.preventDefault();
            const variableName = $(this).data('variable-name');
            insertVariable(variableName);
        });
    }

    // Insert variable at cursor position
    function insertVariable(variableName) {
        const variable = `{${variableName}}`;
        const cursor = editor.getCursor();
        editor.replaceRange(variable, cursor);
        editor.focus();

        toastr.success(`Variable ${variable} insertada`, 'Éxito', {
            timeOut: 1500,
            progressBar: true
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

    // Listen to module change to update variables
    $('#module').on('change', function() {
        loadVariables();
    });

    // Initial load
    loadVariables();

    // Auto-update preview on change (only if preview tab is active)
    editor.on('change', function() {
        hasChanges = true;
        updateEditorStatus('Modificado', 'pencil', 'warning');
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(function() {
            // Only update if preview tab is active
            if ($('#preview-tab').hasClass('active')) {
                updatePreview();
            }
            updateEditorStatus('Listo', 'circle-check', 'success');
        }, 2000);
    });

    // Button: Refresh Preview
    $('#btnRefreshPreviewCreate').on('click', function(e) {
        e.preventDefault();
        updatePreview();
        // Switch to preview tab
        $('#preview-tab').tab('show');
        $(this).prop('disabled', true);
        setTimeout(() => $(this).prop('disabled', false), 1000);
    });

    // Device view switcher (Create view)
    $('#preview-panel #btnDesktopViewCreate, #preview-panel #btnMobileViewCreate').on('click', function() {
        const width = $(this).data('width');
        const $container = $('#previewContainerTab');

        // Update active button - only within the preview panel
        $('#preview-panel .btn-group .btn').removeClass('active');
        $(this).addClass('active');

        // Animate container width
        $container.css('max-width', width);

        // Visual feedback
        if (width === '375px') {
            toastr.info('Vista móvil activada', 'Vista Previa', {
                timeOut: 1500,
                progressBar: true
            });
        } else {
            toastr.info('Vista desktop activada', 'Vista Previa', {
                timeOut: 1500,
                progressBar: true
            });
        }
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

    // Button: Insert Variable from selector
    $('#btnInsertVariable').on('click', function(e) {
        e.preventDefault();
        const variableName = $('#variableSelector').val();

        if (!variableName) {
            toastr.warning('Por favor selecciona una variable', 'Atención', {
                timeOut: 2000
            });
            return;
        }

        insertVariable(variableName);

        // Reset selector
        $('#variableSelector').val('');
    });

    // Enter key on selector inserts variable
    $('#variableSelector').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $('#btnInsertVariable').click();
        }
    });

    // Change event on selector (optional: auto-insert on select)
    // Uncomment if you want auto-insert when selecting
    // $('#variableSelector').on('change', function() {
    //     const variableName = $(this).val();
    //     if (variableName) {
    //         insertVariable(variableName);
    //         $(this).val('');
    //     }
    // });

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

        toastr.info('Creando plantilla...', 'Información', {
            timeOut: 0,
            extendedTimeOut: 0
        });
    });
});
</script>
@endpush

@endsection
