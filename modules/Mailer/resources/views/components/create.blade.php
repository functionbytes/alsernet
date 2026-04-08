@extends('layouts.theme')

@section('page_title', 'Crear Componente de email')

@section('content')
<div class="container-fluid">

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Crear Componente de email',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Componentes', 'url' => route('mailers.components.index')],
            ['label' => 'Crear', 'active' => true]
        ]
    ])

    @include('mailer::partials.alerts')

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

<!-- Shared Mailer Editor utilities -->
<script src="{{ asset('js/modules/mailer-editor.js') }}"></script>

<script>
// Declare editor as global variable so it can be accessed in form submit handler
let editor;

$(document).ready(function() {
    // Initialize Bootstrap Tooltips
    $('[data-bs-toggle="tooltip"]').each(function() {
        new bootstrap.Tooltip(this);
    });

    // Initialize Select2
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({ allowClear: false, width: '100%' });
    }

    // Initialize CodeMirror via shared utility
    MailerEditor.registerHintHelpers();
    editor = MailerEditor.initCodeMirror();
    if (!editor) return;
    MailerEditor.bindAutocomplete(editor);

    let isPreviewExpanded = false;
    let previewTimeout;
    let hasChanges = false;

    // Delegate to shared utilities
    function updateEditorStatus(s, i, c) { MailerEditor.updateEditorStatus(s, i, c); }
    function updatePreviewStatus(s) { MailerEditor.updatePreviewStatus(s); }
    function formatCode() { MailerEditor.formatCode(editor); }
    function togglePreview() { isPreviewExpanded = MailerEditor.togglePreview(isPreviewExpanded); updatePreview(); }
    function toggleVariables() { MailerEditor.toggleVariables(); }

    // Update Preview (local, no backend call - updates both containers)
    function updatePreview() {
        updatePreviewStatus('Actualizando...');

        const html = editor.getValue();
        const $container = $('#previewContainer');
        const $containerTab = $('#previewContainerTab');
        const height = isPreviewExpanded ? '800px' : '400px';

        const $iframe = $('<iframe>').css({ 'width': '100%', 'min-height': height, 'border': 'none', 'display': 'block', 'background': 'white' });
        const $iframeTab = $('<iframe>').css({ 'width': '100%', 'border': 'none', 'display': 'block', 'background': 'white', 'min-height': '500px' });

        $container.empty().append($iframe);
        $containerTab.empty().append($iframeTab);
        $iframe[0].srcdoc = html;
        $iframeTab[0].srcdoc = html;

        updatePreviewStatus('En vivo');
    }

    // Load Variables
    function loadVariables() {
        $.ajax({
            url: '{{ route('mailers.components.variables') }}',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    renderVariables(data.variables);
                }
            },
            error: function() {
                $('#variablesPanel').html(
                    '<div class="alert alert-danger m-2"><i class="fas fa-exclamation-circle me-2"></i>Error al cargar variables</div>'
                );
            }
        });
    }

    // Render Variables (component style: grid cards, no filtering)
    function renderVariables(variableGroups) {
        let html = '<div class="row g-1">';

        $.each(variableGroups, function(idx, group) {
            $.each(group.items, function(idx, variable) {
                html += '<div class="col-6 col-md-4 col-lg-3">';
                html += '<div class="variable-card variable-insert" data-variable-name="' + variable.name + '" data-bs-toggle="tooltip" title="' + (variable.description || '') + '">';
                html += '<code class="variable-code">{' + variable.name + '}</code>';
                html += '</div></div>';
            });
        });

        html += '</div>';
        $('#variablesPanel').html(html);

        $('[data-bs-toggle="tooltip"]').each(function() { new bootstrap.Tooltip(this); });

        $(document).off('click.compCreateVar').on('click.compCreateVar', '.variable-insert', function(e) {
            e.preventDefault();
            MailerEditor.insertVariable($(this).data('variable-name'), editor);
        });
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
