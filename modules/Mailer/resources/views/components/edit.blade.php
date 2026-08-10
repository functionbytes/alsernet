@extends('layouts.theme')

@section('page_title', 'Editar Componente: ' . $component->subject)

@section('page_header')
    @include('core::components.card', [
    'title' => 'Editor de Componente de Email'
    ])
@endsection

@section('content')

    @include('mailer::partials.alerts')

    {{-- Main Form --}}
    <form method="POST" action="{{ route('mailers.components.update', $component->uid) }}" id="formEdit">
        @csrf
        @method('PATCH')
        <input type="hidden" name="lang_id" value="{{ $currentLangId ?? 1 }}">

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
                                    <small class="text-muted">Edita el contenido del componente</small>
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
                                       id="subject" name="subject" value="{{ old('subject', $translation->subject ?? '') }}"
                                       placeholder="Ej: Header principal, Footer de emails..." required>
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-12">
                                <label for="type" class="form-label fw-semibold">
                                    Tipo
                                </label>
                                <select class="form-select select2 @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="partial" @if($component->type === 'partial') selected @endif>Parcial</option>
                                    <option value="layout" @if($component->type === 'layout') selected @endif>Layout</option>
                                    <option value="component" @if($component->type === 'component') selected @endif>Componente</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-12">
                                <label for="current_lang_display" class="form-label fw-semibold">
                                    Idioma actual
                                </label>
                                <input type="text" class="form-control" id="current_lang_display"
                                       value="{{ $translation->lang?->title ?? 'No definido' }}" disabled readonly>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-info-circle me-1"></i>Ver otras traducciones abajo
                                </small>
                            </div>

                            <div class="col-12 col-md-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_protected" name="is_protected" value="1" @if(old('is_protected', $component->is_protected)) checked @endif>
                                    <label class="form-check-label" for="is_protected">
                                        <strong>Componente protegido</strong>
                                        <small class="d-block text-muted">Si activas esta opción, el componente no podrá ser eliminado sin desactivar primero la protección</small>
                                    </label>
                                </div>
                            </div>
                        </div>


                        <div class="alert alert-info border-0 mb-0 mt-3 d-flex align-items-start">
                            <i class="fas fa-info-circle fs-5 me-2 mt-1"></i>
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span><strong>Alias:</strong> <code class="text-primary">{{ $component->alias }}</code></span>
                                    <span class="text-muted">•</span>
                                    <span><strong>Código:</strong> <code class="text-primary">{{ $component->code }}</code></span>
                                    @if (in_array($component->alias, ['email_template_header', 'email_template_footer', 'email_template_wrapper']))
                                        <span class="badge bg-warning-subtle text-warning">
                                            Componente del sistema
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabs Navigation --}}
                    <ul class="nav nav-tabs nav-fill border-bottom" id="editorTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="code-tab" data-bs-toggle="tab" data-bs-target="#code-panel" type="button" role="tab" aria-controls="code-panel" aria-selected="true">
                                Código
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="preview-tab" data-bs-toggle="tab"  data-bs-target="#preview-panel" type="button" role="tab" aria-controls="preview-panel" aria-selected="false">
                                Vista
                            </button>
                        </li>
                    </ul>

                    {{-- Tabs Content --}}
                    <div class="tab-content" id="editorTabsContent">

                        {{-- Tab 1: Code Editor --}}
                        <div class="tab-pane fade show active p-0" id="code-panel" role="tabpanel" aria-labelledby="code-tab">

                            {{-- Variables Panel Below Editor --}}
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

                            <textarea class="form-control d-none" id="content" name="content">{{ old('content', $translation->content ?? '') }}</textarea>

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
                                        <button type="button" class="btn btn-outline-primary active" id="btnDesktopViewEdit" data-width="100%" data-bs-toggle="tooltip" aria-label="Vista Desktop (100%)" data-bs-original-title="Vista Desktop (100%)">
                                            <i class="fas fa-desktop me-1"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="btnMobileViewEdit" data-width="375px" data-bs-toggle="tooltip" aria-label="Vista Mobile (375px)" data-bs-original-title="Vista Mobile (375px)">
                                            <i class="fas fa-mobile-screen me-1"></i>
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary" id="btnRefreshPreviewEdit" data-bs-toggle="tooltip" aria-label="Actualizar vista previa" data-bs-original-title="Actualizar vista previa">
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
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Guardar
                        </button>

                        <a href="{{ route('mailers.components.preview', ['uid' => $component->uid, 'lang_id' => $currentLangId ?? 1]) }}" class="btn btn-info w-100 mb-1" target="_blank">
                            Vista previa
                        </a>

                        <a href="{{ route('mailers.components.index') }}" class="btn btn-secondary w-100">
                            Volver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Right Column: Preview & Variables --}}
            <div class="col-12 col-lg-4">
                {{-- Traducciones / Idiomas --}}
                <div class="card mb-3">
                    <div class="card-header bg-warning-subtle border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="mb-0 fw-bold">
                                    Traducciones por idioma
                                </h6>
                                <small class="text-muted">Estado de completitud de cada idioma</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            {{-- Idioma Actual (Siendo Editado) --}}
                            <div class="list-group-item active bg-primary-subtle border-primary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <strong class="text-black">{{ $translation->lang?->title ?? 'Sin idioma' }}</strong>
                                                <span class="badge bg-primary">
                                                    Editando
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                @if ($translation->subject && $translation->content)
                                                    Completa
                                                @else
                                                    Incompleta
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Otros Idiomas Disponibles --}}
                            @if (!empty($langs) && $langs->count() > 1)
                                @foreach ($langs as $lang)
                                    @if ($lang->id !== $currentLangId)
                                        @php
                                            $langTranslation = $component->translations()->where('lang_id', $lang->id)->first();
                                            $isComplete = $langTranslation && !empty($langTranslation->subject) && !empty($langTranslation->content);
                                            $statusBadge = $isComplete
                                                ? ['class' => 'bg-success-subtle text-success',  'text' => 'Completa']
                                                : ['class' => 'bg-warning-subtle text-warning', 'text' => 'Pendiente'];
                                        @endphp
                                        <a href="{{ route('mailers.components.edit', ['uid' => $component->uid, 'lang_id' => $lang->id]) }}"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-3 py-3 hover-shadow-sm"
                                           data-bs-toggle="tooltip" title="Cambiar a {{ $lang->title }}">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <strong>{{ $lang->title }}</strong>
                                                        <span class="badge {{ $statusBadge['class'] }}">
                                                            {{ $statusBadge['text'] }}
                                                        </span>
                                                    </div>
                                                    @if ($langTranslation)
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>Actualizado: {{ $langTranslation->updated_at->diffForHumans() }}
                                                        </small>
                                                    @else
                                                        <small class="text-muted">
                                                            <i class="fas fa-plus-circle me-1"></i>No creada aún
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                            <i class="fas fa-chevron-right text-muted flex-shrink-0"></i>
                                        </a>
                                    @endif
                                @endforeach
                            @else
                                <div class="list-group-item text-center py-4">
                                    <i class="fas fa-language fs-1 text-muted d-block mb-2"></i>
                                    <small class="text-muted">Solo hay un idioma disponible</small>
                                </div>
                            @endif
                        </div>
                    </div>
                    {{-- Footer con resumen --}}
                    <div class="card-footer bg-light p-2  text-muted">
                        <div class="d-flex gap-3 flex-wrap">
                            <span>
                                <i class="fas fa-check-circle text-success me-1"></i>
                                <strong>{{ $component->translations->where('subject', '!=', null)->where('content', '!=', null)->count() }}</strong> completas
                            </span>
                            <span>
                                <i class="fas fa-exclamation-circle text-warning me-1"></i>
                                <strong>{{ $langs->count() - $component->translations->where('subject', '!=', null)->where('content', '!=', null)->count() }}</strong> pendientes
                            </span>
                        </div>
                    </div>
                </div>


                {{-- Keyboard Shortcuts --}}
                <div class="card">
                    <div class="card-header  border-bottom p-3">
                        <div class="d-flex align-items-center">
                            <div>
                                <h6 class="mb-0 fw-bold">Atajos de teclado</h6>
                                <small class="text-muted">Acelera tu trabajo con estos atajos</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Guardar plantilla</span>
                                    <kbd class="bg-black text-white px-2 py-1 rounded">Ctrl+S</kbd>
                                </div>
                            </div>
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Autocompletar</span>
                                    <kbd class="bg-black text-white px-2 py-1 rounded">Ctrl+Space</kbd>
                                </div>
                            </div>
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Comentar/Descomentar</span>
                                    <kbd class="bg-black text-white px-2 py-1 rounded">Ctrl+/</kbd>
                                </div>
                            </div>
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Expandir Emmet</span>
                                    <kbd class="bg-black text-white px-2 py-1 rounded">Tab</kbd>
                                </div>
                            </div>
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Envolver con Emmet</span>
                                    <kbd class="bg-black text-white px-2 py-1 rounded">Ctrl+Alt+Enter</kbd>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light p-2 text-center">
                        <small class="text-muted">
                            <i class="fas fa-lightbulb me-1"></i>
                            Usa <strong>Emmet</strong> para escribir HTML más rápido
                        </small>
                    </div>
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

    const currentLangId = {{ $currentLangId ?? 1 }};
    let isPreviewExpanded = false;
    let previewTimeout;
    let hasChanges = false;

    // Delegate to shared utilities
    function updateEditorStatus(s, i, c) { MailerEditor.updateEditorStatus(s, i, c); }
    function updatePreviewStatus(s) { MailerEditor.updatePreviewStatus(s); }
    function formatCode() { MailerEditor.formatCode(editor); }
    function togglePreview() { isPreviewExpanded = MailerEditor.togglePreview(isPreviewExpanded); updatePreview(); }
    function toggleVariables() { MailerEditor.toggleVariables(); }

    // Update Preview (AJAX call to backend for edit view)
    function updatePreview() {
        const previewUrl = `{{ route('mailers.components.preview-ajax', $component->uid) }}?lang_id=${currentLangId}`;

        $.ajax({
            url: previewUrl,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    const $container = $('#previewContainer');
                    const $containerTab = $('#previewContainerTab');
                    const height = isPreviewExpanded ? '800px' : '400px';

                    // sandbox="allow-same-origin" (sin allow-scripts): sin esto, un <script>
                    // en el HTML del componente se ejecuta con el origen del panel (srcdoc
                    // sin sandbox hereda el origen del padre) — mismo tratamiento que ya
                    // llevan las páginas de preview estáticas (components/preview.blade.php).
                    const $iframe = $('<iframe>').attr('sandbox', 'allow-same-origin').css({ 'width': '100%', 'min-height': height, 'border': 'none', 'display': 'block', 'background': 'white' });
                    const $iframeTab = $('<iframe>').attr('sandbox', 'allow-same-origin').css({ 'width': '100%', 'border': 'none', 'display': 'block', 'background': 'white', 'min-height': '500px' });

                    $container.empty().append($iframe);
                    $containerTab.empty().append($iframeTab);
                    $iframe[0].srcdoc = data.html;
                    $iframeTab[0].srcdoc = data.html;

                    updatePreviewStatus('En vivo');
                }
            },
            error: function() {
                updatePreviewStatus('Error');
                const errorHtml = '<div class="alert alert-danger m-3"><i class="fas fa-exclamation-circle me-2"></i>Error al cargar vista previa</div>';
                $('#previewContainer').html(errorHtml);
                $('#previewContainerTab').html(errorHtml);
            }
        });
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

        $(document).off('click.compEditVar').on('click.compEditVar', '.variable-insert', function(e) {
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
    $('#btnDesktopViewEdit').on('click', function(e) {
        e.preventDefault();
        $('#previewContainerTab').css('width', '100%');
        $('#btnDesktopViewEdit').addClass('active');
        $('#btnMobileViewEdit').removeClass('active');
    });

    // Button: Mobile View (Preview Tab)
    $('#btnMobileViewEdit').on('click', function(e) {
        e.preventDefault();
        $('#previewContainerTab').css('width', '375px');
        $('#btnMobileViewEdit').addClass('active');
        $('#btnDesktopViewEdit').removeClass('active');
    });

    // Button: Refresh Preview (Edit Tab)
    $('#btnRefreshPreviewEdit').on('click', function(e) {
        e.preventDefault();
        updatePreview();
        $(this).prop('disabled', true);
        setTimeout(() => $(this).prop('disabled', false), 1000);
    });

    // Ctrl+S to save
    editor.setOption('extraKeys', {
        'Ctrl-S': function(cm) {
            $('#formEdit').submit();
        },
        'Ctrl-/': 'toggleComment'
    });

    // Warn on unsaved changes when navigating away
    window.addEventListener('beforeunload', function(e) {
        if (hasChanges) {
            e.preventDefault();
            return '';
        }
    });

    // Sync textarea before submit - CRITICAL: Must sync even if empty!
    $('#formEdit').on('submit', function(e) {
        // Get CodeMirror content (even if empty)
        const editorContent = editor.getValue();

        // Sync to textarea - this is what gets sent to server
        $('#content').val(editorContent);

        // Clear unsaved changes flag so beforeunload doesn't trigger
        hasChanges = false;

        // Disable submit button to prevent double-submit
        const $btn = $(this).find('[type="submit"]');
        $btn.prop('disabled', true);

        // Show saving indicator
        toastr.info('Guardando cambios...', 'Información', {
            timeOut: 0,
            extendedTimeOut: 0
        });
    });

    // Also sync on language switch to prevent loss of data
    $('a[data-bs-toggle="tab"]').on('click', function(e) {
        if (editor) {
            const currentContent = editor.getValue();
            $('#content').val(currentContent);
        }
    });

    // Helper function to sync before language switch
    window.syncBeforeLangSwitch = function() {
        if (editor) {
            const currentContent = editor.getValue();
            $('#content').val(currentContent);
        }
        return true; // Allow navigation
    };
});
</script>
@endpush

@endsection
