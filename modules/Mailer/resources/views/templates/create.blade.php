@extends('layouts.theme')

@section('page_title', 'Crear Plantilla de Email')

@section('page_header')
    @include('core::components.card', [
    'title' => 'Crear Nueva Plantilla de Email',
    ])
@endsection

@section('content')

    @include('mailer::partials.alerts')

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
                                    <select class="form-select form-select-sm select2" id="variableSelector">
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
                                    Preheader <span class="text-muted">(Opcional)</span>
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
                                    Layout base <span class="text-muted">(Opcional)</span>
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
                                    Descripción <span class="text-muted">(Opcional)</span>
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
                                <textarea class="form-control d-none" id="content" name="content">{{ old('content', $baseContent ?? '') }}</textarea>
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
                                <div id="previewContainerTab" style="min-height: 550px; overflow-y: auto; background: #f5f6f8; display: flex; justify-content: center; padding: 20px;">
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

<!-- Shared Mailer Editor utilities -->
<script src="{{ asset('js/modules/mailer-editor.js') }}"></script>

<script>
$(document).ready(function() {

    // Initialize Bootstrap Tooltips
    $('[data-bs-toggle="tooltip"]').each(function() {
        new bootstrap.Tooltip(this);
    });

    // Initialize Select2
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({ allowClear: false, width: '100%' });
    }

    // Initialize CodeMirror via shared utility (templates have extra hint vars)
    var extraVars = ['ORDER_ID', 'ORDER_NUMBER', 'ORDER_TOTAL', 'ORDER_STATUS', 'ORDER_DATE', 'DOCUMENT_TYPE', 'UPLOAD_LINK', 'EXPIRATION_DATE'];
    MailerEditor.registerHintHelpers(extraVars);
    const editor = MailerEditor.initCodeMirror();
    if (!editor) return;
    MailerEditor.bindAutocomplete(editor);

    let previewTimeout;
    let hasChanges = false;

    // Delegate to shared utilities
    function updateEditorStatus(s, i, c) { MailerEditor.updateEditorStatus(s, i, c); }
    function formatCode() { MailerEditor.formatCode(editor); }
    function insertVariable(name) { MailerEditor.insertVariable(name, editor); }

    // Update Preview (local, no backend call for create)
    function updatePreview() {
        const html = editor.getValue();
        const $container = $('#previewContainerTab');
        const $iframe = $('<iframe>').css({ 'width': '100%', 'min-height': '550px', 'border': 'none', 'display': 'block', 'background': 'white' });

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
                '<div class="text-center py-4 text-muted"><i class="fas fa-info-circle fs-3 mb-2 d-block"></i><p class="mb-0 small">Selecciona un modulo para ver las variables disponibles</p></div>'
            );
            return;
        }

        $('#variablesPanel').html(
            '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm mb-2" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mb-0 small">Cargando variables...</p></div>'
        );

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
            error: function() {
                $('#variablesPanel').html(
                    '<div class="alert alert-danger m-2"><i class="fas fa-exclamation-circle me-2"></i>Error al cargar variables</div>'
                );
            }
        });
    }

    // Render Variables (template style: sidebar list + selector dropdown, skip "Cliente")
    function renderVariables(variableGroups) {
        let html = '';

        $.each(variableGroups, function(groupIdx, group) {
            if (group.group === 'Cliente') return true;

            $.each(group.items, function(idx, variable) {
                html += '<div class="mb-2 pb-2 border-bottom variable-insert" data-variable-name="' + variable.name + '">';
                html += '<a class="text-decoration-none d-block" onclick="return false;">';
                html += '<code class="d-inline-block bg-light px-2 py-1 text-primary fw-bold">{' + variable.name + '}</code>';
                html += '</a></div>';
            });
        });

        $('#variablesPanel').html(html);

        let selectorOptions = '<option value="">-- Selecciona una variable --</option>';
        $.each(variableGroups, function(groupIdx, group) {
            if (group.group === 'Cliente') return true;
            selectorOptions += '<optgroup label="' + group.group + '">';
            $.each(group.items, function(idx, variable) {
                selectorOptions += '<option value="' + variable.name + '">{' + variable.name + '}</option>';
            });
            selectorOptions += '</optgroup>';
        });
        $('#variableSelector').html(selectorOptions);

        $(document).off('click.tplCreateVar').on('click.tplCreateVar', '.variable-insert', function(e) {
            e.preventDefault();
            insertVariable($(this).data('variable-name'));
        });
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
