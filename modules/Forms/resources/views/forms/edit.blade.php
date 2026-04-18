@extends('layouts.theme')

@section('title', 'Editar formulario: ' . $form->name)

@push('css')
<style>
    /* Component library */
    .component-search-input { max-width: 260px; }
    .component-library-body { max-height: 190px; overflow-y: auto; }

    .field-type-tiles {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .3rem;
    }
    .field-type-tile {
        display: flex;
        align-items: center;
        gap: .3rem;
        padding: .3rem .5rem;
        border: 1px solid #dee2e6;
        border-radius: .375rem;
        background: #fff;
        cursor: pointer;
        font-size: .72rem;
        line-height: 1.2;
        color: #495057;
        transition: background .15s, border-color .15s, color .15s;
        overflow: hidden;
    }
    .field-type-tile span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .field-type-tile:hover { background: #f0f7e0; border-color: #90bb13; color: #5a7a0a; }
    .field-type-tile i { font-size: .75rem; flex-shrink: 0; }

    /* Config card scrollable body */
    .config-card-body { max-height: calc(100vh - 260px); overflow-y: auto; }

    /* Formula chips */
    .formula-chip { font-size: .7rem; line-height: 1; padding: .2rem .5rem; height: auto; }
    .formula-chip code { font-size: .7rem; color: #5a7a0a; }
    .formula-chip-label { font-size: .67rem; }
    .formula-chip:hover code { color: #fff; }
    .formula-chip:hover .formula-chip-label { color: rgba(255,255,255,.8); }

    /* Advanced toggle icon */
    .advanced-toggle-icon { transition: transform .2s; }

    /* Field type badge in modal header */
    #fieldTypeBadge { font-size: .72rem; }

    /* Check hint text under switch labels */
    .field-check-hint { font-size: .7rem; margin-top: -.1rem; display: block; }

    /* Modal field tabs */
    .nav-tabs-modal { gap: 0; border-bottom: 1px solid #dee2e6; }
    .nav-tabs-modal .nav-link {
        font-size: .8rem;
        color: #6c757d;
        border: none;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        background: transparent;
        padding: .5rem .9rem;
        transition: color .15s, border-color .15s;
    }
    .nav-tabs-modal .nav-link:hover { color: #5a7a0a; border-bottom-color: #d4e88a; }
    .nav-tabs-modal .nav-link.active {
        color: #5a7a0a;
        border-bottom-color: #90bb13;
        font-weight: 600;
    }
    .field-switches-bar { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: .375rem; }
    .field-modal-tab-pane { min-height: 220px; }

    /* Step header rows */
    .step-header-row td { background: #f0f7e0; padding: .3rem .75rem; font-size: .78rem; font-weight: 600; color: #4a6c0e; border-top: 2px solid #c8e6a0; letter-spacing: .02em; }
    .step-badge { display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; background: #90bb13; color: #fff; font-size: .7rem; font-weight: 700; margin-right: .35rem; }

    /* Form canvas */
    .canvas-header { background: #f8f9fa; }
    .fields-list-body {
        max-height: calc(100vh - 560px);
        min-height: 200px;
        overflow-y: auto;
    }

    /* Fields table */
    .fields-table { table-layout: fixed; }
    .fields-table thead th {
        font-size: .7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #6c757d;
        padding: .4rem .6rem;
        border-bottom: 1px solid #dee2e6;
        background: #f8f9fa;
    }
    .fields-table .col-drag    { width: 32px; }
    .fields-table .col-label   { width: auto; }
    .fields-table .col-key     { width: 130px; }
    .fields-table .col-actions { width: 40px; }
    .fields-table .min-width-0 { min-width: 0; }

    /* Field item (tr) */
    .field-item td {
        padding: .45rem .6rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    .field-item td:first-child { border-left: 4px solid #dee2e6; padding-left: .5rem; }
    .field-item:last-child td  { border-bottom: none; }
    .field-item:hover td       { background: #f8f9fa; }
    .field-item.ui-sortable-helper td { background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.12); }
    .field-sort-placeholder td { background: #f0f7e0 !important; }

    .field-item .field-actions { opacity: 1; }
    .drag-handle { cursor: grab; color: #adb5bd; }
    .drag-handle:active { cursor: grabbing; }
    .field-type-badge { font-size: .68rem; }
    .field-key-badge  { max-width: 110px; }

    /* Accent colors by type group */
    .field-accent-basic    td:first-child { border-left-color: #4f8ef7; }
    .field-accent-select   td:first-child { border-left-color: #9f6ef5; }
    .field-accent-advanced td:first-child { border-left-color: #f5963e; }
    .field-accent-layout   td:first-child { border-left-color: #94a3b8; }
    .field-accent-legal    td:first-child { border-left-color: #22c55e; }
    .protection-hint { font-size: .72rem; }
    .qr-preview { max-width: 250px; }

    /* Fix Bootstrap visibility:collapse in table context */
    #fieldModal .tab-pane { visibility: visible; }
    .nav-tabs-builder {
        gap: 0;
        border-bottom: none;
    }
    .nav-tabs-builder .nav-link {
        font-size: .8rem;
        color: #6c757d;
        border: none;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        background: transparent;
        padding: .55rem .9rem;
        transition: color .15s, border-color .15s;
        white-space: nowrap;
    }
    .nav-tabs-builder .nav-link:hover {
        color: #b10100;
        border-bottom-color: #d4e88a;
    }
    .nav-tabs-builder .nav-link.active {
        color: #b10100 !important;
        border-bottom-color: #b10100 !important;
        background: transparent !important;
        font-weight: 600;
    }

    /* Preview tab viewport */
    .preview-tab-viewport {
        background: #e9ecef;
        min-height: 600px;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 24px 16px;
        overflow: auto;
    }
    .preview-tab-device-wrap { transition: width .3s ease; }
    .preview-tab-device-wrap.device-desktop { width: 100%; }
    .preview-tab-device-wrap.device-tablet  { width: 768px; }
    .preview-tab-device-wrap.device-mobile  { width: 390px; }

    .preview-tab-device-wrap.device-desktop .preview-tab-shell {
        border-radius: 8px 8px 0 0;
        border: 1px solid #dee2e6;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(0,0,0,.12);
    }
    .preview-tab-device-wrap.device-tablet .preview-tab-shell {
        background: #1c1c1e;
        border-radius: 20px;
        padding: 18px 10px;
        box-shadow: 0 0 0 2px #3a3a3c, 0 20px 48px rgba(0,0,0,.35);
    }
    .preview-tab-device-wrap.device-tablet .preview-tab-iframe-wrap { border-radius: 8px; overflow: hidden; }
    .preview-tab-device-wrap.device-mobile .preview-tab-shell {
        background: #1c1c1e;
        border-radius: 48px;
        padding: 0 8px;
        box-shadow: 0 0 0 2px #3a3a3c, 0 24px 56px rgba(0,0,0,.45);
    }
    .preview-tab-device-wrap.device-mobile .preview-tab-iframe-wrap { border-radius: 36px; overflow: hidden; }

    .preview-tab-browser-bar {
        background: #f1f3f4;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        border-bottom: 1px solid #dee2e6;
    }
    #previewTabFrame {
        width: 100%;
        height: 620px;
        border: none;
        display: block;
        background: white;
    }
    .preview-tab-device-wrap.device-desktop #previewTabFrame { height: 580px; }
    .btn-preview-device.active { background: #212529; color: white; border-color: #212529; }
</style>
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

@section('content')

    @include('core::components.card', ['title' => 'Constructor de formulario'])

    <div class="row g-3">

        {{-- Columna principal: Constructor --}}
        <div class="col-12 col-lg-8">
            <div class="card">

                {{-- Header --}}
                <div class="card-header border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div>
                                <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                                <small class="text-muted">{{ $form->slug }}</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge bg-light text-secondary border">
                                <i class="fas fa-list me-1"></i>
                                <span id="statsFieldCountHeader">{{ $form->fields->count() }}</span> campos
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Configuración</h6>
                        <small class="text-muted">Datos del formulario</small>
                    </div>

                    <div class="config-card-body p-3">

                        <div class="mb-4">
                            <label class="form-label" for="configName">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="configName" class="form-control"
                                   value="{{ $form->name }}" maxlength="150">
                            <div class="form-text">Nombre visible del formulario</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="configDescription">Descripción <span class="text-muted fw-normal">(Opcional)</span></label>
                            <textarea id="configDescription" class="form-control"
                                      rows="3" maxlength="1000">{{ $form->description }}</textarea>
                            <div class="form-text">Descripción y propósito del formulario</div>
                        </div>

                        @if ($categories->isNotEmpty())
                            <div class="mb-4">
                                <label class="form-label" for="configCategory">Categoría <span class="text-muted fw-normal">(Opcional)</span></label>
                                <select id="configCategory" class="form-select">
                                    <option value="">Sin categoría</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ $form->category_id == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Categoría del formulario</div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label" for="configIsActive">Estado</label>
                            <select id="configIsActive" class="form-select config-select2">
                                <option value="1" {{ $form->is_active ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ ! $form->is_active ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            <div class="form-text">Disponibilidad del formulario</div>
                        </div>

                        <hr class="my-3">

                        {{-- Botón de envío --}}
                        <p class="text-uppercase fw-semibold small text-muted mb-2">Botón de envío</p>

                        <div class="mb-3">
                            <label class="form-label" for="configButtonText">Texto del botón</label>
                            <input type="text" id="configButtonText" class="form-control"
                                   value="{{ $form->submit_button_text ?? 'Enviar' }}">
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="configButtonPosition">Posición del botón</label>
                            <select id="configButtonPosition" class="form-select config-select2">
                                <option value="left"   {{ $form->button_position === 'left'   ? 'selected' : '' }}>Izquierda</option>
                                <option value="center" {{ $form->button_position === 'center' ? 'selected' : '' }}>Centro</option>
                                <option value="right"  {{ $form->button_position === 'right'  ? 'selected' : '' }}>Derecha</option>
                                <option value="full"   {{ $form->button_position === 'full'   ? 'selected' : '' }}>Ancho completo</option>
                            </select>
                        </div>

                        <hr class="my-3">

                        {{-- Acción al enviar --}}
                        <p class="text-uppercase fw-semibold small text-muted mb-2">Acción al enviar</p>

                        <div class="mb-3">
                            <label class="form-label" for="configSuccessMessage">Mensaje de éxito</label>
                            <textarea id="configSuccessMessage" class="form-control"
                                      rows="3">{{ $form->success_message }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="configRedirectUrl">URL de redirección</label>
                            <input type="url" id="configRedirectUrl" class="form-control"
                                   value="{{ $form->redirect_url }}">
                            <div class="form-text">Si se llena, redirige en vez de mostrar mensaje</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="configSuccessAnimation">Animación de éxito</label>
                            <select id="configSuccessAnimation" class="form-select config-select2">
                                <option value=""         {{ !$form->success_animation             ? 'selected' : '' }}>Ninguna</option>
                                <option value="confetti" {{ $form->success_animation === 'confetti'  ? 'selected' : '' }}>Confetti</option>
                                <option value="checkmark"{{ $form->success_animation === 'checkmark' ? 'selected' : '' }}>Check animado</option>
                                <option value="fireworks"{{ $form->success_animation === 'fireworks' ? 'selected' : '' }}>Fuegos artificiales</option>
                            </select>
                        </div>

                        <hr class="my-3">

                        {{-- Notificaciones --}}
                        <p class="text-uppercase fw-semibold small text-muted mb-2">Notificaciones</p>

                        <div class="mb-3">
                            <label class="form-label" for="configAdminEmail">Email de notificación admin</label>
                            <input type="email" id="configAdminEmail" class="form-control"
                                   value="{{ $form->admin_notification_email }}">
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="configSendConfirmation"
                                   {{ $form->send_confirmation ? 'checked' : '' }}>
                            <label class="form-check-label" for="configSendConfirmation">
                                Enviar confirmación al usuario
                            </label>
                        </div>

                        <div id="confirmationFields" class="{{ $form->send_confirmation ? '' : 'd-none' }}">
                            <div class="mb-3">
                                <label class="form-label" for="configEmailFieldKey">Clave del campo email</label>
                                <input type="text" id="configEmailFieldKey" class="form-control"
                                       value="{{ $form->email_field_key }}">
                                <div class="form-text">Ej: email</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="configConfirmationSubject">Asunto del email de confirmación</label>
                                <input type="text" id="configConfirmationSubject" class="form-control"
                                       value="{{ $form->confirmation_subject }}">
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="configConfirmationMessage">Mensaje de confirmación</label>
                                <textarea id="configConfirmationMessage" class="form-control"
                                          rows="3">{{ $form->confirmation_message }}</textarea>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Límites y retención --}}
                        <p class="text-uppercase fw-semibold small text-muted mb-2">Límites y retención</p>

                        <div class="mb-3">
                            <label class="form-label" for="configMaxSubmissions">Máximo de envíos</label>
                            <input type="number" id="configMaxSubmissions" class="form-control"
                                   min="0" value="{{ $form->max_submissions }}">
                            <div class="form-text">0 = sin límite</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="configRetentionDays">Retener datos (días)</label>
                            <input type="number" id="configRetentionDays" class="form-control"
                                   min="0" value="{{ $form->retention_days }}">
                            <div class="form-text">0 = conservar siempre</div>
                        </div>

                        <hr class="my-3">

                        {{-- Integración --}}
                        <p class="text-uppercase fw-semibold small text-muted mb-2">Integración</p>

                        <div class="mb-4">
                            <label class="form-label" for="configWebhookUrl">Webhook URL</label>
                            <input type="url" id="configWebhookUrl" class="form-control"
                                   value="{{ $form->webhook_url }}">
                            <div class="form-text">POST JSON al recibir un envío</div>
                        </div>

                        <hr class="my-3">

                        {{-- Estilo --}}
                        <p class="text-uppercase fw-semibold small text-muted mb-2">Estilo</p>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="configFloatingLabel"
                                   {{ $form->floating_label ? 'checked' : '' }}>
                            <label class="form-check-label" for="configFloatingLabel">
                                Labels flotantes
                            </label>
                        </div>

                        <hr class="my-3">

                        <small class="text-muted">
                            <span class="fw-medium">Slug:</span> {{ $form->slug }}
                            &nbsp;&middot;&nbsp;
                            <span class="fw-medium">ID:</span> {{ $form->id }}
                        </small>

                    </div>

                </div>


                {{-- Toolbar de edición (visible sólo en tabs de código) --}}
                <div class="editor-toolbar-row d-none" id="editorToolbar">
                    <button type="button" class="btn btn-sm btn-outline-light" id="btnFormat"
                            data-bs-toggle="tooltip" data-bs-placement="bottom" title="Formatear código (Alt+Shift+F)">
                        <i class="fas fa-wand-magic-sparkles"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" id="btnFoldAll"
                            data-bs-toggle="tooltip" data-bs-placement="bottom" title="Colapsar todo">
                        <i class="fas fa-compress-alt"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" id="btnUnfoldAll"
                            data-bs-toggle="tooltip" data-bs-placement="bottom" title="Expandir todo">
                        <i class="fas fa-expand-alt"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" id="btnWrapLines"
                            data-bs-toggle="tooltip" data-bs-placement="bottom" title="Ajuste de línea">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <small class="text-secondary">Ctrl+S guardar · Ctrl+F buscar · F11 pantalla completa</small>
                        <button type="button" class="btn btn-sm btn-outline-light" id="btnEditorTheme"
                                data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tema claro / oscuro">
                            <i class="fas fa-circle-half-stroke"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-light" id="btnFullscreen"
                                data-bs-toggle="tooltip" data-bs-placement="bottom" title="Pantalla completa (F11)">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>

                {{-- Tabs de navegación --}}
                <div class="border-bottom bg-white">
                    <div class="nav nav-tabs nav-tabs-builder px-3" id="formBuilderTabs" role="tablist">
                        <button class="nav-link active" id="constructor-tab"
                                data-bs-toggle="tab" data-bs-target="#constructor-panel"
                                type="button" role="tab" aria-selected="true">
                            Constructor
                        </button>
                        <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 " id="estilo-tab"
                                data-bs-toggle="tab" data-bs-target="#estilo-panel"
                                type="button" role="tab" aria-selected="false">
                            Estilo
                        </button>
                        <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 " id="js-tab"
                                data-bs-toggle="tab" data-bs-target="#js-panel"
                                type="button" role="tab" aria-selected="false">
                            Javascript
                        </button>
                        <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 " id="estructura-tab"
                                data-bs-toggle="tab" data-bs-target="#estructura-panel"
                                type="button" role="tab" aria-selected="false">
                            Estructura
                        </button>
                        <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 " id="vista-tab"
                                data-bs-toggle="tab" data-bs-target="#vista-panel"
                                type="button" role="tab" aria-selected="false">
                            Vista
                        </button>
                    </div>
                </div>

                {{-- Nombre del formulario --}}
                <div class="px-3 py-2 border-bottom bg-white">
                    <h6 class="mb-0 fw-bold text-truncate" id="formTitleDisplay">{{ $form->name }}</h6>
                    @if($form->description)
                    <small class="text-muted text-truncate d-block" id="formDescDisplay">{{ $form->description }}</small>
                    @endif
                </div>

                {{-- Tab content --}}
                <div class="tab-content" id="formBuilderTabsContent">

                    {{-- Tab 1: Constructor --}}
                    <div class="tab-pane fade show active p-0" id="constructor-panel" role="tabpanel" aria-labelledby="constructor-tab">

                        {{-- Component library --}}
                        <div class="border-bottom">
                            <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                                <div class="input-group input-group-sm flex-grow-1 component-search-input">
                                    <span class="input-group-text bg-transparent border-end-0 pe-1">
                                        <i class="fas fa-search text-muted small"></i>
                                    </span>
                                    <input type="search" class="form-control border-start-0 ps-0"
                                           placeholder="Buscar componente..." id="fieldTypeSearch">
                                </div>
                                <small class="text-muted ms-auto">Componentes</small>
                            </div>
                            <div class="p-3 component-library-body">
                                <div class="field-type-tiles">
                                    @foreach ($fieldTypeSettings as $groupName => $types)
                                        @foreach ($types->where('is_enabled', true) as $fieldType)
                                            <button type="button" class="field-type-tile btn-add-field"
                                                    data-type="{{ $fieldType->type }}" title="{{ $fieldType->label }}">
                                                <i class="{{ $fieldType->icon }}"></i>
                                                <span>{{ $fieldType->label }}</span>
                                            </button>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Form canvas --}}
                        <div>
                            <div class="canvas-header d-flex align-items-center justify-content-between py-2 px-3 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold small">Campos del formulario</span>
                                    <span class="badge bg-light text-secondary border rounded-pill" id="statsFieldCount">{{ $form->fields->count() }}</span>
                                </div>
                                @if ($form->is_multi_step)
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAddStep">
                                        <i class="fas fa-plus me-1"></i> Agregar paso
                                    </button>
                                @endif
                            </div>
                            <div class="fields-list-body">
                                <div id="emptyState" class="text-center py-4 {{ $form->fields->isNotEmpty() ? 'd-none' : '' }}">
                                    <i class="fas fa-layer-group fa-2x text-muted mb-2"></i>
                                    <p class="text-muted small mb-0">Selecciona un componente para agregar campos</p>
                                </div>
                                <table class="table fields-table mb-0 {{ $form->fields->isEmpty() ? 'd-none' : '' }}" id="fieldsTable">
                                    <thead>
                                        <tr>
                                            <th class="col-drag"></th>
                                            <th class="col-label">Campo</th>
                                            <th class="col-key">Clave</th>
                                            <th class="col-actions"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="fieldsList">
                                        @if ($form->is_multi_step && !empty($form->steps_config))
                                            @php
                                                $stepTitles = collect($form->steps_config)->keyBy('number')->map(fn($s) => $s['title'] ?? $s['name'] ?? ('Paso ' . $s['number']));
                                                $currentStep = null;
                                            @endphp
                                            @foreach ($form->fields->sortBy('step_number') as $field)
                                                @if ($field->step_number !== $currentStep)
                                                    @php $currentStep = $field->step_number; @endphp
                                                    <tr class="step-header-row">
                                                        <td colspan="4">
                                                            <span class="step-badge">{{ $currentStep }}</span>
                                                            {{ $stepTitles->get($currentStep, 'Paso ' . $currentStep) }}
                                                        </td>
                                                    </tr>
                                                @endif
                                                @include('forms::forms.partials.field-item', ['field' => $field])
                                            @endforeach
                                        @else
                                            @foreach ($form->fields as $field)
                                                @include('forms::forms.partials.field-item', ['field' => $field])
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="border-top d-flex align-items-center justify-content-between py-2 px-3">
                                <small class="text-muted">Arrastra los campos para reordenarlos</small>
                                <small class="text-muted"><span id="statsFieldCountFooter">{{ $form->fields->count() }}</span> campos</small>
                            </div>
                        </div>

                    </div>

                    {{-- Tab 2: Estilo CSS --}}
                    <div class="tab-pane fade p-0" id="estilo-panel" role="tabpanel" aria-labelledby="estilo-tab">
                        <textarea id="custom_css" style="display:none;">{{ old('custom_css', $form->custom_css) }}</textarea>
                    </div>

                    {{-- Tab 3: Javascript --}}
                    <div class="tab-pane fade p-0" id="js-panel" role="tabpanel" aria-labelledby="js-tab">
                        <textarea id="custom_js" style="display:none;">{{ old('custom_js', $form->custom_js) }}</textarea>
                    </div>

                    {{-- Tab 4: Estructura HTML --}}
                    <div class="tab-pane fade p-0" id="estructura-panel" role="tabpanel" aria-labelledby="estructura-tab">
                        <div class="d-flex align-items-center gap-2 p-2 border-bottom">
                            <small class="text-muted me-auto">HTML generado por el constructor</small>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshEstructura" title="Recargar">
                                <i class="fas fa-rotate-right"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCopyEstructura" title="Copiar HTML">
                                <i class="fas fa-copy me-1"></i> Copiar
                            </button>
                        </div>
                        <textarea id="estructura_html" style="display:none;"></textarea>
                    </div>

                    {{-- Tab 5: Vista previa --}}
                    <div class="tab-pane fade p-0" id="vista-panel" role="tabpanel" aria-labelledby="vista-tab">

                        {{-- Toolbar --}}
                        <div class="d-flex align-items-center justify-content-between gap-2 p-3 border-bottom flex-wrap">
                            <div class="btn-group btn-group-sm" id="previewDeviceBtns">
                                <button type="button" class="btn btn-outline-secondary btn-preview-device active" data-device="desktop" title="Escritorio">
                                    <i class="fas fa-desktop"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-preview-device" data-device="tablet" title="Tablet">
                                    <i class="fas fa-tablet-screen-button"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-preview-device" data-device="mobile" title="Móvil">
                                    <i class="fas fa-mobile-screen"></i>
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshPreviewForm" title="Recargar">
                                    <i class="fas fa-rotate-right"></i>
                                </button>
                                <a href="{{ route('settings.forms.preview', $form) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                </a>
                            </div>
                        </div>

                        {{-- Viewport --}}
                        <div class="preview-tab-viewport" id="previewTabViewport">
                            <div class="preview-tab-device-wrap device-desktop" id="previewTabDeviceWrap">
                                <div class="preview-tab-shell">

                                    {{-- Browser bar (desktop) --}}
                                    <div class="preview-tab-browser-bar" id="previewTabBrowserBar">
                                        <span class="browser-dot browser-dot-red"></span>
                                        <span class="browser-dot browser-dot-amber"></span>
                                        <span class="browser-dot browser-dot-green"></span>
                                        <span class="browser-url-bar">{{ route('forms.public.preview.public', [$form, $previewToken]) }}</span>
                                    </div>

                                    {{-- Mobile notch (hidden by default) --}}
                                    <div class="device-mobile-notch d-none" id="previewTabMobileNotch">
                                        <div class="device-mobile-notch-bar"></div>
                                    </div>

                                    {{-- Iframe --}}
                                    <div class="preview-tab-iframe-wrap">
                                        <iframe id="previewTabFrame" src="" title="Vista previa: {{ $form->name }}"></iframe>
                                    </div>

                                    {{-- Mobile home bar (hidden by default) --}}
                                    <div class="device-mobile-home d-none" id="previewTabMobileHome">
                                        <div class="device-mobile-home-bar"></div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="p-2 border-top">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i> Vista previa con token seguro. El formulario no enviará datos reales.
                            </small>
                        </div>
                    </div>

                </div>

                {{-- Footer con acciones --}}
                <div class="card-footer bg-white border-top">
                    <button type="button" class="btn btn-primary w-100 mb-2 d-none" id="btnSaveCode">
                        Guardar cambios
                        <span class="badge bg-black ms-1" id="editorStatus">Listo</span>
                    </button>
                    <a href="{{ route('settings.forms.preview', $form) }}" target="_blank" class="btn btn-primary w-100 mb-2" id="btnFooterPreview">
                        Vista previa
                    </a>
                    <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary w-100 mb-2">
                        Ver submissions
                    </a>
                    <button type="button" class="btn btn-outline-secondary flex-fill w-100 mb-2" id="btnShowQr">
                        QR Code
                    </button>
                   <a href="{{ route('settings.forms.export-json', $form) }}" class="btn btn-outline-secondary flex-fill w-100 mb-2">
                       JSON
                    </a>
                    <button type="button" class="btn btn-info w-100" id="btnSaveConfig">
                        <span class="btn-save-text">Guardar configuración</span>
                    </button>
                </div>

            </div>
        </div>

        {{-- Columna lateral: info y configuración --}}
        <div class="col-12 col-lg-4">

            {{-- Configuración del formulario --}}

            {{-- Publicación --}}
            <div class="card mb-3">
                <div class="card-header border-bottom p-3">
                    <h6 class="mb-0 fw-bold">Publicación</h6>
                    <small class="text-muted">Shortcodes y accesos del formulario</small>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <button type="button"
                                class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-3 py-2 btn-copy-shortcode"
                                data-shortcode='[form id="{{ $form->id }}"]'>
                            <span class="small"><i class="fas fa-code me-2 text-muted"></i>Shortcode por ID</span>
                        </button>
                        <button type="button"
                                class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-3 py-2 btn-copy-shortcode"
                                data-shortcode='[form slug="{{ $form->slug }}"]'>
                            <span class="small"><i class="fas fa-code me-2 text-muted"></i>Shortcode por slug</span>
                        </button>
                        <a href="{{ route('settings.forms.preview', $form) }}" target="_blank"
                           class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-3 py-2 text-dark text-decoration-none">
                            <span class="small"><i class="fas fa-desktop me-2 text-muted"></i>Vista previa</span>
                        </a>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}"
                           class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-3 py-2 text-dark text-decoration-none">
                            <span class="small"><i class="fas fa-inbox me-2 text-muted"></i>Ver submissions</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Analytics --}}
            <div class="card mb-3">
                <div class="card-header border-bottom p-3">
                    <h6 class="mb-0 fw-bold">Analytics</h6>
                    <small class="text-muted">Estadísticas del formulario</small>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Campos</span>
                        <span class="fw-semibold small" id="statsFieldCountSidebar">{{ $form->fields->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Responses</span>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="fw-semibold small text-decoration-none">
                            {{ $form->submissions()->count() }}
                        </a>
                    </div>
                    @if ($form->is_multi_step && $form->steps_config)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Pasos</span>
                            <span class="fw-semibold small">{{ count($form->steps_config) }}</span>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-light p-2">
                    <a href="{{ route('settings.forms.analytics', $form) }}" class="btn btn-info w-100">
                        Ver analytics completo
                    </a>
                </div>
            </div>

            {{-- Atajos de teclado (visible solo en tabs de código) --}}
            <div class="card mb-3 d-none" id="keyboardShortcutsCard">
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
                            ['Buscar', 'Ctrl+F'],
                            ['Pantalla completa', 'F11'],
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

            {{-- Seguridad --}}
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h6 class="mb-0 fw-bold">Configuración de seguridad</h6>
                    <small class="text-muted">Protección contra spam y bots</small>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="fw-semibold small">Honeypot</div>
                            <div class="text-muted protection-hint">Campo oculto anti-bots</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input btn-protection-toggle" type="checkbox"
                                   id="honeypotToggle" data-field="honeypot_enabled"
                                   {{ $form->honeypot_enabled ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold small">reCAPTCHA</div>
                            <div class="text-muted protection-hint">Verificación humana</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input btn-protection-toggle" type="checkbox"
                                   id="captchaToggle" data-field="captcha_enabled"
                                   {{ $form->captcha_enabled ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

@php
    $otherLocales = \Illuminate\Support\Facades\DB::table('locales')
        ->where('is_active', true)
        ->where('code', '!=', 'es')
        ->select('code', 'name')
        ->get();
@endphp

{{-- Modal agregar/editar campo --}}
<div class="modal fade" id="fieldModal" tabindex="-1" aria-labelledby="fieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header pb-2">
                <div class="d-flex flex-column">
                    <h5 class="modal-title mb-1" id="fieldModalLabel">Agregar campo</h5>
                    <span class="badge bg-light text-secondary border fw-normal" id="fieldTypeBadge" style="width:fit-content">
                        <i class="fas fa-question me-1"></i><span id="fieldTypeBadgeLabel">-</span>
                    </span>
                </div>
                <button type="button" class="btn-close ms-auto align-self-start mt-1" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            {{-- Fila principal: siempre visible --}}
            <div class="px-3 pt-3 pb-0">
                <input type="hidden" id="fieldId">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" id="fieldType">
                            <optgroup label="Básicos">
                                <option value="text">Texto corto</option>
                                <option value="textarea">Texto largo</option>
                                <option value="email">Email</option>
                                <option value="phone">Teléfono</option>
                                <option value="number">Número</option>
                                <option value="date">Fecha</option>
                                <option value="time">Hora</option>
                                <option value="url">URL</option>
                            </optgroup>
                            <optgroup label="Selección">
                                <option value="select">Select</option>
                                <option value="radio">Radio</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="image_choice">Imagen</option>
                            </optgroup>
                            <optgroup label="Avanzados">
                                <option value="file">Archivo</option>
                                <option value="rating">Rating</option>
                                <option value="slider">Slider</option>
                                <option value="nps">NPS</option>
                                <option value="likert">Likert</option>
                                <option value="signature">Firma</option>
                                <option value="calculation">Cálculo</option>
                                <option value="address">Dirección</option>
                                <option value="color_picker">Selector de color</option>
                            </optgroup>
                            <optgroup label="Layout">
                                <option value="section_header">Sección</option>
                                <option value="html_block">HTML</option>
                                <option value="divider">Divisor</option>
                                <option value="spacer">Espacio</option>
                            </optgroup>
                            <optgroup label="Legal">
                                <option value="consent">Consentimiento</option>
                                <option value="newsletter_consent">Newsletter</option>
                                <option value="hidden">Campo oculto</option>
                                <option value="password">Contraseña</option>
                            </optgroup>
                        </select>
                        <div class="invalid-feedback" id="typeError"></div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Etiqueta <span class="text-danger">*</span></label>
                        <input type="text" name="label" id="fieldLabel" class="form-control" placeholder="Ej: Nombre completo">
                        <div class="invalid-feedback" id="labelError"></div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="modal-body pt-2">
                <ul class="nav nav-tabs border-0 user-profile-tab mb-3" id="fieldModalTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-campo-btn" data-bs-toggle="tab" data-bs-target="#tabCampo" type="button" role="tab">
                           Campo
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 " id="tab-avanzado-btn" data-bs-toggle="tab" data-bs-target="#tabAvanzado" type="button" role="tab">
                           Avanzado
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-condiciones-btn" data-bs-toggle="tab" data-bs-target="#tabCondiciones" type="button" role="tab">
                            <i class="fas fa-code-branch me-1"></i>Condiciones
                            <span class="badge bg-warning text-dark ms-1 d-none" id="condicionesBadge" style="font-size:.65rem">0</span>
                        </button>
                    </li>
                    @if($otherLocales->isNotEmpty())
                    <li class="nav-item" role="presentation">
                        <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 " id="tab-traducciones-btn" data-bs-toggle="tab" data-bs-target="#tabTraducciones" type="button" role="tab">
                            Traducciones
                            <span class="badge bg-secondary ms-1" style="font-size:.65rem">{{ $otherLocales->count() }}</span>
                        </button>
                    </li>
                    @endif
                </ul>

                <div class="tab-content">

                    {{-- Tab: Campo --}}
                    <div class="tab-pane fade show active field-modal-tab-pane" id="tabCampo" role="tabpanel">
                        <div class="row g-3">

                            {{-- Key + Ancho --}}
                            <div class="col-md-6">
                                <label class="form-label">Clave <small class="text-muted fw-normal">auto-generada</small></label>
                                <input type="text" name="key" id="fieldKey" class="form-control font-monospace" placeholder="nombre_completo">
                                <div class="invalid-feedback" id="keyError"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ancho</label>
                                <select name="width" id="fieldWidth" class="form-select">
                                    <option value="full">Completo (100%)</option>
                                    <option value="half">Medio (50%)</option>
                                    <option value="third">Tercio (33%)</option>
                                    <option value="quarter">Cuarto (25%)</option>
                                </select>
                            </div>

                            {{-- Placeholder --}}
                            <div class="col-12" id="placeholderGroup">
                                <label class="form-label">Placeholder</label>
                                <input type="text" name="placeholder" id="fieldPlaceholder" class="form-control">
                            </div>

                            {{-- Opciones --}}
                            <div class="col-12 d-none" id="optionsGroup">
                                <label class="form-label">Opciones</label>
                                <div id="optionsList"></div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addOption">
                                    <i class="fas fa-plus me-1"></i> Añadir opción
                                </button>
                            </div>

                            {{-- HTML --}}
                            <div class="col-12 d-none" id="htmlContentGroup">
                                <label class="form-label">Contenido HTML</label>
                                <textarea name="html_content" id="fieldHtmlContent" class="form-control" rows="4"
                                          placeholder="<p>Texto de ayuda...</p>"></textarea>
                            </div>

                            {{-- Consentimiento --}}
                            <div class="col-12 d-none" id="consentTextGroup">
                                <label class="form-label">Texto de consentimiento</label>
                                <textarea name="consent_text" id="fieldConsentText" class="form-control" rows="3"
                                          placeholder="Acepto los términos y condiciones..."></textarea>
                            </div>

                            {{-- Min / Max / Step --}}
                            <div class="col-4 d-none" id="minValueGroup">
                                <label class="form-label">Mínimo</label>
                                <input type="number" name="min_value" id="fieldMinValue" class="form-control" value="0">
                            </div>
                            <div class="col-4 d-none" id="maxValueGroup">
                                <label class="form-label">Máximo</label>
                                <input type="number" name="max_value" id="fieldMaxValue" class="form-control" value="5">
                            </div>
                            <div class="col-4 d-none" id="stepValueGroup">
                                <label class="form-label">Paso</label>
                                <input type="number" name="step_value" id="fieldStepValue" class="form-control" value="1">
                            </div>

                            {{-- Fórmula --}}
                            <div class="col-12 d-none" id="formulaGroup">
                                <label class="form-label">Fórmula de cálculo</label>
                                <input type="text" name="formula" id="fieldFormula" class="form-control font-monospace"
                                       placeholder="{campo_a} + {campo_b} * 1.16">
                                <div class="mt-2 p-2 border rounded-2 bg-light" id="formulaShortcutsPanel">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <small class="text-muted fw-semibold"><i class="fas fa-tag me-1"></i>Variables disponibles</small>
                                        <small class="text-muted">&mdash; clic para insertar</small>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1" id="formulaShortcuts">
                                        <small class="text-muted fst-italic">Sin campos numéricos aún</small>
                                    </div>
                                </div>
                                <div class="form-text mt-1">
                                    Operadores: <code>+</code> <code>-</code> <code>*</code> <code>/</code> <code>( )</code> &nbsp;·&nbsp;
                                    Variables: <code>{clave}</code>
                                </div>
                            </div>

                            {{-- Likert --}}
                            <div class="col-12 d-none" id="likertRowsGroup">
                                <label class="form-label">Preguntas de la escala</label>
                                <div id="likertRowsList"></div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addLikertRow">
                                    <i class="fas fa-plus me-1"></i> Añadir pregunta
                                </button>
                            </div>

                            {{-- Switches --}}
                            <div class="col-12">
                                <div class="field-switches-bar d-flex flex-wrap gap-4 p-3">
                                    <div class="form-check form-switch mb-0">
                                        <input type="checkbox" name="is_required" class="form-check-input" id="fieldRequired">
                                        <label class="form-check-label" for="fieldRequired">
                                            <span class="fw-semibold">Obligatorio</span>
                                            <span class="text-muted field-check-hint">El campo debe completarse</span>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input type="checkbox" name="show_char_counter" class="form-check-input" id="fieldCharCounter">
                                        <label class="form-check-label" for="fieldCharCounter">
                                            <span class="fw-semibold">Contador de caracteres</span>
                                            <span class="text-muted field-check-hint">Muestra caracteres escritos</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Tab: Avanzado --}}
                    <div class="tab-pane fade field-modal-tab-pane" id="tabAvanzado" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Texto de ayuda</label>
                                <input type="text" name="help_text" id="fieldHelpText" class="form-control"
                                       placeholder="Descripción breve bajo el campo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Posición del label</label>
                                <select name="label_position" id="fieldLabelPosition" class="form-select">
                                    <option value="top">Arriba</option>
                                    <option value="floating">Flotante</option>
                                    <option value="hidden">Oculto</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Auto-rellenar desde URL
                                    <span class="text-muted fw-normal">&mdash; param GET</span>
                                </label>
                                <input type="text" name="auto_populate_param" id="fieldAutoPopulate" class="form-control"
                                       placeholder="Ej: name, email, phone">
                                <div class="form-text">URL: <code>?name=Juan</code></div>
                            </div>
                            @if ($form->is_multi_step && !empty($form->steps_config))
                            <div class="col-md-6">
                                <label class="form-label">Paso del formulario</label>
                                <select name="step_number" id="fieldStepNumber" class="form-select">
                                    @foreach ($form->steps_config as $step)
                                        <option value="{{ $step['number'] }}">{{ $step['title'] ?? 'Paso ' . $step['number'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @elseif ($form->is_multi_step)
                            <div class="col-md-6">
                                <label class="form-label">Paso del formulario</label>
                                <input type="number" name="step_number" id="fieldStepNumber"
                                       class="form-control" value="1" min="1" placeholder="Número de paso">
                                <div class="form-text">Crea pasos con "Agregar paso"</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Tab: Condiciones --}}
                    <div class="tab-pane fade field-modal-tab-pane" id="tabCondiciones" role="tabpanel">
                        <p class="text-muted small mb-3">
                            Define cuándo se muestra este campo. Si no hay reglas, el campo siempre es visible.
                        </p>
                        <div id="conditionRulesList" class="d-flex flex-column gap-2 mb-3"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addConditionRule">
                            <i class="fas fa-plus me-1"></i> Añadir regla
                        </button>
                        <div class="form-text mt-2">
                            Las reglas se evalúan con <strong>AND</strong> por defecto. Marca "O" en una regla para evaluarla con OR.
                        </div>
                    </div>

                    {{-- Tab: Traducciones --}}
                    @if($otherLocales->isNotEmpty())
                    <div class="tab-pane fade field-modal-tab-pane" id="tabTraducciones" role="tabpanel">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <p class="text-muted small mb-0">Etiqueta y placeholder por idioma. Vacío = valor en español.</p>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAutoTranslate">
                                <i class="fas fa-magic me-1"></i>Traducir con DeepL
                            </button>
                        </div>
                        <div class="row g-2">
                            @foreach($otherLocales as $loc)
                            <div class="col-12">
                                <div class="border rounded-2 p-3">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-secondary">{{ strtoupper($loc->code) }}</span>
                                        <span class="fw-semibold small">{{ $loc->name }}</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <input type="text"
                                                   class="form-control form-control-sm field-trans-label"
                                                   data-locale="{{ $loc->code }}"
                                                   placeholder="Etiqueta en {{ $loc->name }}">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text"
                                                   class="form-control form-control-sm field-trans-placeholder"
                                                   data-locale="{{ $loc->code }}"
                                                   placeholder="Placeholder en {{ $loc->name }}">
                                        </div>
                                        <div class="col-12 field-trans-consent-group d-none">
                                            <textarea class="form-control form-control-sm field-trans-consent"
                                                      data-locale="{{ $loc->code }}"
                                                      rows="2"
                                                      placeholder="Texto de consentimiento en {{ $loc->name }}"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>
            </div>

            <div class="modal-footer flex-column">
                <button type="button" class="btn btn-primary w-100 mb-2" id="saveField">
                     Guardar campo
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal QR Code --}}
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalLabel">QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
                <img id="qrImage" data-src="{{ route('settings.forms.qrcode', $form) }}"
                     alt="QR Code {{ $form->name }}"
                     class="img-fluid rounded mb-3 qr-preview">
                <div>
                    <a href="{{ route('settings.forms.qrcode', $form) }}" download="qrcode-{{ $form->slug }}.png"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download me-1"></i> Descargar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ url('others/filemanager/js/jquery-ui-1.12.1/jquery-ui.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/xml-fold.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify-html.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/foldcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/foldgutter.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/brace-fold.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/display/fullscreen.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/search/searchcursor.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/search/search.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/dialog/dialog.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/selection/active-line.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/comment/comment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify-css.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify.js"></script>
<script>
    const FORM_ID   = {{ $form->id }};
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    const urls = {
        fieldsStore:  '{{ route('settings.forms.fields.store', $form) }}',
        fieldsReorder:'{{ route('settings.forms.fields.reorder', $form) }}',
        fieldEditBase:'{{ route('settings.forms.fields.index', $form) }}',
        preview:      '{{ route('settings.forms.preview', $form) }}',
    };

    const LAYOUT_TYPES          = ['section_header', 'html_block', 'divider', 'spacer'];
    const TYPES_WITH_OPTIONS    = ['select', 'radio', 'checkbox', 'image_choice'];
    const TYPES_WITH_MIN_MAX    = ['slider', 'number', 'rating', 'nps'];
    const TYPES_WITH_CONSENT    = ['consent', 'newsletter_consent'];

    // ─── Sortable ──────────────────────────────────────────────────────────────
    $('#fieldsList').sortable({
        handle: '.drag-handle',
        items: 'tr',
        placeholder: 'field-sort-placeholder',
        forcePlaceholderSize: true,
        update: function () {
            const order = [];
            $('#fieldsList tr.field-item').each(function (index) {
                order.push({ id: $(this).data('id'), sort_order: index });
            });
            $.ajax({
                url: urls.fieldsReorder,
                method: 'POST',
                data: { items: order },
                headers: { 'X-CSRF-TOKEN': csrfToken },
            });
        }
    });

    // ─── Vista previa en tab ───────────────────────────────────────────────────
    const previewPublicUrl = '{{ route('forms.public.preview.public', [$form, $previewToken]) }}';
    const previewDeviceConfig = {
        desktop: { showBrowser: true,  showMobile: false },
        tablet:  { showBrowser: false, showMobile: false },
        mobile:  { showBrowser: false, showMobile: true  },
    };

    $('#vista-tab').on('shown.bs.tab', function () {
        if (!$('#previewTabFrame').attr('src')) {
            $('#previewTabFrame').attr('src', previewPublicUrl);
        }
    });

    $('#btnRefreshPreviewForm').on('click', function () {
        const frame = document.getElementById('previewTabFrame');
        if (frame.src) {
            frame.src = frame.src;
        } else {
            frame.src = previewPublicUrl;
        }
    });

    $(document).on('click', '.btn-preview-device', function () {
        const device = $(this).data('device');
        const cfg    = previewDeviceConfig[device];

        $('.btn-preview-device').removeClass('active');
        $(this).addClass('active');

        $('#previewTabDeviceWrap')
            .removeClass('device-desktop device-tablet device-mobile')
            .addClass('device-' + device);

        $('#previewTabBrowserBar').toggleClass('d-none', !cfg.showBrowser);
        $('#previewTabMobileNotch, #previewTabMobileHome').toggleClass('d-none', !cfg.showMobile);
    });

    // ─── Abrir modal para agregar ──────────────────────────────────────────────
    $(document).on('click', '.btn-add-field', function () {
        const type = $(this).data('type');
        resetFieldModal();
        $('#fieldModalLabel').text('Agregar campo');
        $('#fieldId').val('');
        $('#fieldType').val(type).trigger('change');
        new bootstrap.Modal(document.getElementById('fieldModal')).show();
    });

    // ─── Mostrar/ocultar grupos según tipo ─────────────────────────────────────
    $('#fieldType').on('change', function () {
        const type = $(this).val();
        const isLayout  = LAYOUT_TYPES.includes(type);

        $('#optionsGroup').toggleClass('d-none', !TYPES_WITH_OPTIONS.includes(type));
        $('#htmlContentGroup').toggleClass('d-none', type !== 'html_block');
        $('#consentTextGroup').toggleClass('d-none', !TYPES_WITH_CONSENT.includes(type));
        $('.field-trans-consent-group').toggleClass('d-none', !TYPES_WITH_CONSENT.includes(type));
        $('#minValueGroup, #maxValueGroup, #stepValueGroup').toggleClass('d-none', !TYPES_WITH_MIN_MAX.includes(type));
        $('#formulaGroup').toggleClass('d-none', type !== 'calculation');
        $('#likertRowsGroup').toggleClass('d-none', type !== 'likert');
        $('#placeholderGroup').toggleClass('d-none', isLayout || type === 'signature');
    });

    // ─── Auto-generar key desde label ─────────────────────────────────────────
    $('#fieldLabel').on('input', function () {
        if ($('#fieldId').val()) return;
        const key = $(this).val()
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_|_$/g, '');
        $('#fieldKey').val(key);
    });

    // ─── Añadir opción (select/radio/checkbox) ─────────────────────────────────
    $(document).on('click', '#addOption', function () {
        const idx = $('#optionsList .option-row').length;
        $('#optionsList').append(buildOptionRow('', '', idx));
    });

    $(document).on('click', '.btn-remove-option', function () {
        $(this).closest('.option-row').remove();
    });

    function buildOptionRow(label, value, idx) {
        const displayValue = value ?? label;
        return `<div class="input-group input-group-sm mb-1 option-row">
            <input type="text" class="form-control option-label" value="${label}" placeholder="Etiqueta ${idx + 1}">
            <input type="text" class="form-control option-value" value="${displayValue}" placeholder="Valor ${idx + 1}" style="max-width:120px">
            <button type="button" class="btn btn-outline-danger btn-remove-option">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    }

    // ─── Añadir pregunta Likert ────────────────────────────────────────────────
    $(document).on('click', '#addLikertRow', function () {
        const idx = $('#likertRowsList .likert-row').length;
        $('#likertRowsList').append(
            `<div class="input-group input-group-sm mb-1 likert-row">
                <input type="text" class="form-control" name="likert_rows[]" placeholder="Pregunta ${idx + 1}">
                <button type="button" class="btn btn-outline-danger btn-remove-likert">
                    <i class="fas fa-times"></i>
                </button>
            </div>`
        );
    });

    $(document).on('click', '.btn-remove-likert', function () {
        $(this).closest('.likert-row').remove();
    });

    // ─── Guardar campo ─────────────────────────────────────────────────────────
    $('#saveField').on('click', function () {
        clearFieldErrors();

        const fieldId = $('#fieldId').val();
        const isEdit  = !!fieldId;

        const payload = {
            type:             $('#fieldType').val(),
            label:            $('#fieldLabel').val(),
            key:              $('#fieldKey').val(),
            width:            $('#fieldWidth').val(),
            placeholder:      $('#fieldPlaceholder').val(),
            help_text:        $('#fieldHelpText').val(),
            label_position:   $('#fieldLabelPosition').val(),
            is_required:      $('#fieldRequired').is(':checked') ? 1 : 0,
            show_char_counter:$('#fieldCharCounter').is(':checked') ? 1 : 0,
            html_content:     $('#fieldHtmlContent').val(),
            consent_text:     $('#fieldConsentText').val(),
            min_value:        $('#fieldMinValue').val(),
            max_value:        $('#fieldMaxValue').val(),
            step_value:       $('#fieldStepValue').val(),
            formula:             $('#fieldFormula').val(),
            auto_populate_param: $('#fieldAutoPopulate').val(),
            step_number:         $('#fieldStepNumber').val() || null,
        };

        const options = [];
        $('#optionsList .option-row').each(function () {
            const label = $(this).find('.option-label').val().trim();
            const value = $(this).find('.option-value').val().trim();
            if (label) options.push({ value: value || label, label });
        });
        if (options.length) payload.options = options;

        const likertRows = [];
        $('#likertRowsList .likert-row input').each(function () {
            const v = $(this).val().trim();
            if (v) likertRows.push(v);
        });
        if (likertRows.length) payload.likert_rows = likertRows;

        const translations = {};
        $('.field-trans-label').each(function () {
            const locale = $(this).data('locale');
            const label = $(this).val().trim();
            if (label) {
                translations[locale] = translations[locale] || {};
                translations[locale].label = label;
            }
        });
        $('.field-trans-placeholder').each(function () {
            const locale = $(this).data('locale');
            const ph = $(this).val().trim();
            if (ph) {
                translations[locale] = translations[locale] || {};
                translations[locale].placeholder = ph;
            }
        });
        $('.field-trans-consent').each(function () {
            const locale = $(this).data('locale');
            const ct = $(this).val().trim();
            if (ct) {
                translations[locale] = translations[locale] || {};
                translations[locale].consent_text = ct;
            }
        });
        if (Object.keys(translations).length) payload.translations = translations;

        payload.conditions = collectConditions();

        const url    = isEdit ? `${urls.fieldEditBase}/${fieldId}` : urls.fieldsStore;
        const method = isEdit ? 'PATCH' : 'POST';

        $('#saveField').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url, method,
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                toastr.success(isEdit ? 'Campo actualizado' : 'Campo añadido');
                bootstrap.Modal.getInstance(document.getElementById('fieldModal')).hide();
                reloadFieldsList();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    if (errors.type)  showFieldError('typeError',  '#fieldType',  errors.type[0]);
                    if (errors.label) showFieldError('labelError', '#fieldLabel', errors.label[0]);
                    if (errors.key)   showFieldError('keyError',   '#fieldKey',   errors.key[0]);
                } else {
                    toastr.error('Error al guardar el campo');
                }
            },
            complete: function () {
                $('#saveField').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar campo');
            }
        });
    });

    // ─── Editar campo ──────────────────────────────────────────────────────────
    $(document).on('click', '.btn-edit-field', function () {
        const fieldId = $(this).data('id');

        $.get(`${urls.fieldEditBase}/${fieldId}/edit`, function (res) {
            resetFieldModal();
            populateFieldModal(res.field);
            $('#fieldModalLabel').text('Editar campo');
            $('#fieldId').val(fieldId);
            new bootstrap.Modal(document.getElementById('fieldModal')).show();
        }).fail(function () {
            toastr.error('Error al cargar el campo');
        });
    });

    function populateFieldModal(field) {
        $('#fieldType').val(field.type).trigger('change');
        $('#fieldLabel').val(field.label);
        $('#fieldKey').val(field.key);
        $('#fieldWidth').val(field.width || 'full');
        $('#fieldPlaceholder').val(field.placeholder);
        $('#fieldHelpText').val(field.help_text);
        $('#fieldLabelPosition').val(field.label_position || 'top');
        $('#fieldRequired').prop('checked', field.is_required);
        $('#fieldCharCounter').prop('checked', field.show_char_counter);
        $('#fieldHtmlContent').val(field.html_content);
        $('#fieldConsentText').val(field.consent_text);
        $('#fieldMinValue').val(field.min_value ?? 0);
        $('#fieldMaxValue').val(field.max_value ?? 5);
        $('#fieldStepValue').val(field.step_value ?? 1);
        $('#fieldFormula').val(field.formula);
        if (field.step_number) { $('#fieldStepNumber').val(field.step_number); }

        const trans = field.translations || {};
        $('.field-trans-label').each(function () {
            const locale = $(this).data('locale');
            $(this).val(trans[locale]?.label || '');
        });
        $('.field-trans-placeholder').each(function () {
            const locale = $(this).data('locale');
            $(this).val(trans[locale]?.placeholder || '');
        });
        $('.field-trans-consent').each(function () {
            const locale = $(this).data('locale');
            $(this).val(trans[locale]?.consent_text || '');
        });

        if (field.options && Array.isArray(field.options)) {
            field.options.forEach((opt, idx) => {
                const label = typeof opt === 'object' ? (opt.label ?? opt.value ?? '') : opt;
                const value = typeof opt === 'object' ? (opt.value ?? opt.label ?? '') : opt;
                $('#optionsList').append(buildOptionRow(label, value, idx));
            });
        }
        if (field.likert_rows && Array.isArray(field.likert_rows)) {
            field.likert_rows.forEach((row, idx) => {
                $('#likertRowsList').append(
                    `<div class="input-group input-group-sm mb-1 likert-row">
                        <input type="text" class="form-control" name="likert_rows[]" value="${row}" placeholder="Pregunta ${idx + 1}">
                        <button type="button" class="btn btn-outline-danger btn-remove-likert"><i class="fas fa-times"></i></button>
                    </div>`
                );
            });
        }

        populateConditions(field.conditions || []);
    }

    // ─── Eliminar campo ────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete-field', function () {
        const fieldId = $(this).data('id');
        const label   = $(this).data('label');
        if (!confirm(`¿Eliminar el campo "${label}"?`)) return;

        $.ajax({
            url: `${urls.fieldEditBase}/${fieldId}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                toastr.success('Campo eliminado');
                $(`#field-item-${fieldId}`).remove();
                updateFieldCount();
                if ($('#fieldsList tr.field-item').length === 0) {
                    $('#emptyState').removeClass('d-none');
                    $('#fieldsTable').addClass('d-none');
                }
            },
            error: function () {
                toastr.error('Error al eliminar el campo');
            }
        });
    });

    // ─── Duplicar campo ───────────────────────────────────────────────────────
    $(document).on('click', '.btn-duplicate-field', function () {
        var btn = $(this);
        var url = btn.data('url');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                if (response.success) {
                    toastr.success('Campo duplicado correctamente.');
                    reloadFieldsList();
                }
            },
            error: function () {
                toastr.error('Error al duplicar el campo.');
                btn.prop('disabled', false).html('<i class="fas fa-copy"></i>');
            }
        });
    });

    // ─── Toggles de protección (honeypot / captcha) ───────────────────────────
    $(document).on('change', '.btn-protection-toggle', function () {
        var field = $(this).data('field');
        var value = $(this).is(':checked') ? 1 : 0;
        var payload = {};
        payload[field] = value;

        $.ajax({
            url: '{{ route('settings.forms.protection', $form) }}',
            method: 'PATCH',
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                toastr.success('Configuración guardada');
            },
            error: function () {
                toastr.error('Error al guardar');
                var $chk = $('[data-field="' + field + '"]');
                $chk.prop('checked', !$chk.is(':checked'));
            }
        });
    });

    // ─── Agregar paso ─────────────────────────────────────────────────────────
    $('#btnAddStep').on('click', function () {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Agregando...');

        $.ajax({
            url: '{{ route('settings.forms.steps.add', $form) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                toastr.success('Paso ' + res.step.number + ' agregado correctamente');
                location.reload();
            },
            error: function () {
                toastr.error('Error al agregar el paso');
                btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i> Agregar paso');
            }
        });
    });

    // ─── Resetear modal al cerrarlo ───────────────────────────────────────────
    $('#fieldModal').on('hidden.bs.modal', function () {
        resetFieldModal();
    });

    // ─── QR Modal ─────────────────────────────────────────────────────────────
    $('#btnShowQr').on('click', function () {
        var $img = $('#qrImage');
        if (!$img.attr('src')) {
            $img.attr('src', $img.data('src'));
        }
        new bootstrap.Modal(document.getElementById('qrModal')).show();
    });

    // ─── Copiar shortcode ─────────────────────────────────────────────────────
    $(document).on('click', '.btn-copy-shortcode', function () {
        const shortcode = $(this).data('shortcode');
        navigator.clipboard.writeText(shortcode).then(function () {
            toastr.success('Shortcode copiado al portapapeles');
        });
    });

    // ─── Filtrar tipos de campo en sidebar ────────────────────────────────────
    $('#fieldTypeSearch').on('input', function () {
        const query = $(this).val().toLowerCase().trim();
        $('.field-type-tile').each(function () {
            const label = $(this).find('span').text().toLowerCase();
            const type  = $(this).data('type').toLowerCase();
            $(this).toggle(!query || label.includes(query) || type.includes(query));
        });
    });

    // ─── Helpers ───────────────────────────────────────────────────────────────
    function resetFieldModal() {
        $('#fieldId').val('');
        $('#fieldType').val('text').trigger('change');
        $('#fieldLabel, #fieldKey, #fieldPlaceholder, #fieldHelpText, #fieldAutoPopulate').val('');
        $('.field-trans-label, .field-trans-placeholder, .field-trans-consent').val('');
        $('#fieldHtmlContent, #fieldConsentText, #fieldFormula').val('');
        $('#fieldWidth').val('full');
        $('#fieldLabelPosition').val('top');
        $('#fieldRequired, #fieldCharCounter').prop('checked', false);
        $('#fieldMinValue').val(0);
        $('#fieldMaxValue').val(5);
        $('#fieldStepValue').val(1);
        $('#optionsList, #likertRowsList, #conditionRulesList').empty();
        updateConditionsBadge();
        $('#tab-campo-btn').tab('show');
        clearFieldErrors();
    }

    function clearFieldErrors() {
        ['typeError', 'labelError', 'keyError'].forEach(id => $('#' + id).text('').hide());
        $('#fieldType, #fieldLabel, #fieldKey').removeClass('is-invalid');
    }

    function showFieldError(divId, inputSelector, message) {
        $(inputSelector).addClass('is-invalid');
        $('#' + divId).text(message).show();
    }

    function reloadFieldsList() {
        location.reload();
    }

    function updateFieldCount() {
        const count = $('#fieldsList tr.field-item').length;
        $('#statsFieldCount, #statsFieldCountFooter, #statsFieldCountHeader, #statsFieldCountSidebar').text(count);
    }

    // ── CodeMirror: editores de CSS y JS ──────────────────────────────────────
    const CODE_TABS = ['estilo-tab', 'js-tab'];

    function makeEditorExtraKeys(editor) {
        return {
            'Ctrl-Space': 'autocomplete',
            'Ctrl-/': 'toggleComment',
            'Ctrl-S': function () { saveCodeEditors(); },
            'Ctrl-F': 'findPersistent',
            'Ctrl-H': 'replace',
            'F11': function (ed) {
                ed.setOption('fullScreen', !ed.getOption('fullScreen'));
                $('#btnFullscreen i').toggleClass('fa-expand fa-compress');
            },
            'Esc': function (ed) {
                if (ed.getOption('fullScreen')) {
                    ed.setOption('fullScreen', false);
                    $('#btnFullscreen i').removeClass('fa-compress').addClass('fa-expand');
                }
            },
        };
    }

    const editorCss = CodeMirror.fromTextArea(document.getElementById('custom_css'), {
        mode: 'css',
        theme: 'default',
        lineNumbers: true,
        autoCloseBrackets: true,
        matchBrackets: true,
        styleActiveLine: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        extraKeys: makeEditorExtraKeys(),
    });

    const editorJs = CodeMirror.fromTextArea(document.getElementById('custom_js'), {
        mode: 'javascript',
        theme: 'default',
        lineNumbers: true,
        autoCloseBrackets: true,
        matchBrackets: true,
        styleActiveLine: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        extraKeys: makeEditorExtraKeys(),
    });

    const editorHtml = CodeMirror.fromTextArea(document.getElementById('estructura_html'), {
        mode: 'htmlmixed',
        theme: 'default',
        lineNumbers: true,
        readOnly: true,
        styleActiveLine: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        extraKeys: { 'F11': function (cm) { cm.setOption('fullScreen', !cm.getOption('fullScreen')); } },
    });

    const htmlSourceUrl = '{{ route('settings.forms.html-source', $form) }}';
    let estructuraLoaded = false;

    function loadEstructuraHtml() {
        editorHtml.setValue('Cargando...');
        $.get(htmlSourceUrl, function (data) {
            const formatted = typeof html_beautify !== 'undefined'
                ? html_beautify(data.html, { indent_size: 4, wrap_line_length: 120 })
                : data.html;
            editorHtml.setValue(formatted);
            editorHtml.refresh();
            estructuraLoaded = true;
        }).fail(function () {
            editorHtml.setValue('Error al cargar el HTML.');
        });
    }

    $('#estructura-tab').on('shown.bs.tab', function () {
        editorHtml.refresh();
        if (!estructuraLoaded) { loadEstructuraHtml(); }
    });

    $('#btnRefreshEstructura').on('click', function () { loadEstructuraHtml(); });

    $('#btnCopyEstructura').on('click', function () {
        const html = editorHtml.getValue();
        if (!html) { return; }
        navigator.clipboard.writeText(html).then(function () {
            toastr.success('HTML copiado al portapapeles.');
        }).catch(function () {
            toastr.error('No se pudo copiar.');
        });
    });

    // Refrescar editor al mostrar su tab
    $('#estilo-tab').on('shown.bs.tab', function () { editorCss.refresh(); });
    $('#js-tab').on('shown.bs.tab', function () { editorJs.refresh(); });

    // Mostrar/ocultar toolbar y botón guardar según el tab activo
    $('#formBuilderTabs button').on('shown.bs.tab', function () {
        const id = $(this).attr('id');
        const isCodeTab = CODE_TABS.includes(id);
        $('#editorToolbar').toggleClass('d-none', !isCodeTab);
        $('#btnSaveCode').toggleClass('d-none', !isCodeTab);
        $('#btnFooterPreview').toggleClass('d-none', isCodeTab);
        $('#keyboardShortcutsCard').toggleClass('d-none', !isCodeTab);
    });

    // Guardar CSS y JS vía AJAX
    function saveCodeEditors() {
        const $btn = $('#btnSaveCode');
        $btn.prop('disabled', true);
        $('#editorStatus').text('Guardando...');

        $.ajax({
            url: '{{ route('settings.forms.update', $form) }}',
            method: 'POST',
            data: {
                _method: 'PATCH',
                _token: csrfToken,
                name: '{{ addslashes($form->name) }}',
                custom_css: editorCss.getValue(),
                custom_js: editorJs.getValue(),
            },
            success: function () {
                $('#editorStatus').text('Guardado');
                toastr.success('Cambios guardados correctamente.');
                setTimeout(function () { $('#editorStatus').text('Listo'); }, 2000);
            },
            error: function () {
                toastr.error('Error al guardar los cambios.');
                $('#editorStatus').text('Error');
            },
            complete: function () {
                $btn.prop('disabled', false);
            },
        });
    }

    $('#btnSaveCode').on('click', saveCodeEditors);

    // Toolbar: obtener editor activo
    function activeCodeEditor() {
        return $('#estilo-tab').hasClass('active') ? editorCss : editorJs;
    }

    // Formatear
    $('#btnFormat').on('click', function () {
        if ($('#estilo-tab').hasClass('active') && typeof css_beautify !== 'undefined') {
            editorCss.setValue(css_beautify(editorCss.getValue(), { indent_size: 2 }));
        } else if ($('#js-tab').hasClass('active') && typeof js_beautify !== 'undefined') {
            editorJs.setValue(js_beautify(editorJs.getValue(), { indent_size: 2 }));
        }
        toastr.info('Código formateado.');
    });

    // Colapsar / Expandir
    $('#btnFoldAll').on('click', function () {
        const ed = activeCodeEditor();
        for (let i = 0; i < ed.lineCount(); i++) { ed.foldCode({ line: i, ch: 0 }); }
    });
    $('#btnUnfoldAll').on('click', function () {
        const ed = activeCodeEditor();
        for (let i = 0; i < ed.lineCount(); i++) { ed.foldCode({ line: i, ch: 0 }, null, 'unfold'); }
    });

    // Ajuste de línea
    let wrapEnabled = false;
    $('#btnWrapLines').on('click', function () {
        wrapEnabled = !wrapEnabled;
        [editorCss, editorJs].forEach(ed => ed.setOption('lineWrapping', wrapEnabled));
        $(this).toggleClass('active', wrapEnabled);
    });

    // Pantalla completa
    $('#btnFullscreen').on('click', function () {
        const ed = activeCodeEditor();
        const isFs = !ed.getOption('fullScreen');
        [editorCss, editorJs].forEach(e => e.setOption('fullScreen', false));
        ed.setOption('fullScreen', isFs);
        $(this).find('i').toggleClass('fa-expand', !isFs).toggleClass('fa-compress', isFs);
    });

    // Tema claro / oscuro
    let editorDark = false;
    $('#btnEditorTheme').on('click', function () {
        editorDark = !editorDark;
        const theme = editorDark ? 'monokai' : 'default';
        [editorCss, editorJs].forEach(ed => ed.setOption('theme', theme));
        $('#editorToolbar').toggleClass('dark', editorDark);
        $(this).toggleClass('active', editorDark);
    });

    // ── Tooltips ─────────────────────────────────────────────────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // ─── Campos disponibles para condiciones ───────────────────────────────────
    window._formFields = @json($form->fields->where('is_visible', true)->map(fn ($f) => ['key' => $f->key, 'label' => $f->label])->values());

    function buildConditionRow(rule) {
        rule = rule || {};
        const fieldsOptions = (window._formFields || []).map(function (f) {
            return '<option value="' + f.key + '"' + (rule.field === f.key ? ' selected' : '') + '>' + f.label + '</option>';
        }).join('');

        const operators = [
            ['equals', 'es igual a'], ['not_equals', 'no es igual a'],
            ['contains', 'contiene'], ['not_empty', 'no está vacío'],
            ['empty', 'está vacío'], ['greater_than', 'mayor que'], ['less_than', 'menor que'],
        ];
        const opOptions = operators.map(function (o) {
            return '<option value="' + o[0] + '"' + (rule.operator === o[0] ? ' selected' : '') + '>' + o[1] + '</option>';
        }).join('');

        const hideValue = ['not_empty', 'empty'].includes(rule.operator);

        return $('<div class="condition-rule d-flex align-items-center gap-2 flex-wrap">' +
            '<select class="form-select form-select-sm cond-field" style="max-width:160px">' + fieldsOptions + '</select>' +
            '<select class="form-select form-select-sm cond-operator" style="max-width:150px">' + opOptions + '</select>' +
            '<input type="text" class="form-control form-control-sm cond-value' + (hideValue ? ' d-none' : '') + '" style="max-width:130px" placeholder="Valor" value="' + (rule.value || '') + '">' +
            '<div class="form-check form-check-inline mb-0 ms-1">' +
                '<input class="form-check-input cond-logic-or" type="checkbox"' + (rule.logic === 'OR' ? ' checked' : '') + ' title="Usar OR en vez de AND">' +
                '<label class="form-check-label small text-muted">O</label>' +
            '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-condition"><i class="fas fa-times"></i></button>' +
        '</div>');
    }

    function collectConditions() {
        const rules = [];
        $('#conditionRulesList .condition-rule').each(function () {
            const field    = $(this).find('.cond-field').val();
            const operator = $(this).find('.cond-operator').val();
            const value    = $(this).find('.cond-value').val();
            const isOr     = $(this).find('.cond-logic-or').is(':checked');
            if (!field) { return; }
            const rule = { field, operator, value };
            if (isOr) { rule.logic = 'OR'; }
            rules.push(rule);
        });
        return rules;
    }

    function populateConditions(conditions) {
        $('#conditionRulesList').empty();
        (conditions || []).forEach(function (rule) {
            $('#conditionRulesList').append(buildConditionRow(rule));
        });
        updateConditionsBadge();
    }

    function updateConditionsBadge() {
        const count = $('#conditionRulesList .condition-rule').length;
        $('#condicionesBadge').text(count).toggleClass('d-none', count === 0);
    }

    $(document).on('click', '#addConditionRule', function () {
        const defaultField = (window._formFields || [])[0];
        $('#conditionRulesList').append(buildConditionRow({ field: defaultField ? defaultField.key : '', operator: 'equals', value: '' }));
        updateConditionsBadge();
    });

    $(document).on('click', '.btn-remove-condition', function () {
        $(this).closest('.condition-rule').remove();
        updateConditionsBadge();
    });

    $(document).on('change', '.cond-operator', function () {
        const hideValue = ['not_empty', 'empty'].includes($(this).val());
        $(this).closest('.condition-rule').find('.cond-value').toggleClass('d-none', hideValue);
    });

    // ─── Auto-translate con DeepL ────────────────────────────────────────────────
    $(document).on('click', '#btnAutoTranslate', function () {
        const fieldId = $('#fieldId').val();
        if (!fieldId) {
            toastr.warning('Guarda el campo primero antes de traducir.');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Traduciendo...');

        $.ajax({
            url: urls.fieldBase + '/' + fieldId + '/translate',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (!res.success && !res.translations) {
                    toastr.error(res.message || 'Error al traducir.');
                    return;
                }

                const trans = res.translations || {};
                $('.field-trans-label').each(function () {
                    const locale = $(this).data('locale');
                    if (trans[locale]?.label) $(this).val(trans[locale].label);
                });
                $('.field-trans-placeholder').each(function () {
                    const locale = $(this).data('locale');
                    if (trans[locale]?.placeholder) $(this).val(trans[locale].placeholder);
                });
                $('.field-trans-consent').each(function () {
                    const locale = $(this).data('locale');
                    if (trans[locale]?.consent_text) $(this).val(trans[locale].consent_text);
                });

                const hasErrors = res.errors && Object.keys(res.errors).length;
                if (hasErrors) {
                    toastr.warning(res.message || 'Traducción con errores parciales.');
                } else {
                    toastr.success(res.message || 'Traducciones generadas correctamente.');
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Error al conectar con DeepL.';
                toastr.error(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i>Traducir con DeepL');
            },
        });
    });

    // ─── Formula shortcodes ─────────────────────────────────────────────────────
    const allFormFields = @json($form->fields->map(fn ($f) => ['key' => $f->name, 'label' => $f->label, 'type' => $f->type])->values());

    function renderFormulaShortcuts() {
        const $wrap = $('#formulaShortcuts');
        $wrap.empty();
        const numericTypes = ['number', 'slider', 'rating', 'nps', 'calculation'];
        const fields = allFormFields.filter(f => numericTypes.includes(f.type));
        if (!fields.length) {
            $wrap.html('<small class="text-muted fst-italic">Sin campos numéricos aún</small>');
            return;
        }
        fields.forEach(function (f) {
            $('<button>', { type: 'button', class: 'btn btn-sm btn-outline-secondary formula-chip' })
                .html('<code>{' + f.key + '}</code> <span class="text-muted formula-chip-label">' + f.label + '</span>')
                .on('click', function () {
                    const el = document.getElementById('fieldFormula');
                    const sc = '{' + f.key + '}';
                    const s = el.selectionStart, e = el.selectionEnd;
                    el.value = el.value.substring(0, s) + sc + el.value.substring(e);
                    el.focus();
                    el.setSelectionRange(s + sc.length, s + sc.length);
                })
                .appendTo($wrap);
        });
    }

    renderFormulaShortcuts();
    $(document).on('shown.bs.tab', '#constructor-tab', renderFormulaShortcuts);

    // Advanced options chevron toggle
    $('#advancedOptions').on('show.bs.collapse hide.bs.collapse', function () {
        $('#btnAdvancedOptions .advanced-toggle-icon').toggleClass('fa-chevron-down fa-chevron-up');
    });

    // Update type badge in modal header
    const typeIconMap = {
        text: 'fas fa-font', textarea: 'fas fa-align-left', email: 'fas fa-at',
        phone: 'fas fa-phone', number: 'fas fa-hashtag', date: 'fas fa-calendar-alt',
        time: 'fas fa-clock', url: 'fas fa-link', select: 'fas fa-caret-square-down',
        radio: 'far fa-dot-circle', checkbox: 'far fa-check-square', image_choice: 'far fa-image',
        file: 'fas fa-paperclip', rating: 'fas fa-star', slider: 'fas fa-sliders-h',
        nps: 'fas fa-chart-bar', likert: 'fas fa-table', signature: 'fas fa-signature',
        calculation: 'fas fa-calculator', address: 'fas fa-map-marker-alt',
        section_header: 'fas fa-heading', html_block: 'fas fa-code',
        divider: 'fas fa-minus', spacer: 'fas fa-arrows-alt-v',
        consent: 'fas fa-user-check', newsletter_consent: 'fas fa-newspaper',
        hidden: 'far fa-eye-slash', password: 'fas fa-key', color_picker: 'fas fa-palette',
    };
    const typeLabels = {
        text: 'Texto corto', textarea: 'Texto largo', email: 'Email', phone: 'Teléfono',
        number: 'Número', date: 'Fecha', time: 'Hora', url: 'URL', select: 'Select',
        radio: 'Radio', checkbox: 'Checkbox', image_choice: 'Imagen', file: 'Archivo',
        rating: 'Rating', slider: 'Slider', nps: 'NPS', likert: 'Likert',
        signature: 'Firma', calculation: 'Cálculo', address: 'Dirección',
        section_header: 'Sección', html_block: 'HTML', divider: 'Divisor',
        spacer: 'Espacio', consent: 'Consentimiento', newsletter_consent: 'Newsletter',
        hidden: 'Oculto', password: 'Contraseña', color_picker: 'Color',
    };
    function updateTypeBadge(type) {
        const icon = typeIconMap[type] || 'fas fa-question';
        const label = typeLabels[type] || type;
        $('#fieldTypeBadge').html('<i class="' + icon + ' me-1"></i>' + label);
    }
    $('#fieldType').on('change', function () { updateTypeBadge($(this).val()); });

    // ─── Configuración del formulario ─────────────────────────────────────────
    $('.config-select2').select2({ width: '100%', minimumResultsForSearch: Infinity });

    $('#configSendConfirmation').on('change', function () {
        $('#confirmationFields').toggleClass('d-none', !this.checked);
    });

    $('#btnSaveConfig').on('click', function () {
        const $btn = $(this);
        const $text = $btn.find('.btn-save-text');
        $btn.prop('disabled', true);
        $text.text('Guardando...');

        $.ajax({
            url: '{{ route('settings.forms.update', $form) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: {
                _method: 'PATCH',
                name:                     $('#configName').val(),
                description:              $('#configDescription').val(),
                category_id:              $('#configCategory').val() || '',
                is_active:                $('#configIsActive').val(),
                submit_button_text:       $('#configButtonText').val(),
                button_position:          $('#configButtonPosition').val(),
                success_message:          $('#configSuccessMessage').val(),
                redirect_url:             $('#configRedirectUrl').val(),
                success_animation:        $('#configSuccessAnimation').val(),
                admin_notification_email: $('#configAdminEmail').val(),
                send_confirmation:        $('#configSendConfirmation').is(':checked') ? 1 : 0,
                email_field_key:          $('#configEmailFieldKey').val(),
                confirmation_subject:     $('#configConfirmationSubject').val(),
                confirmation_message:     $('#configConfirmationMessage').val(),
                max_submissions:          $('#configMaxSubmissions').val() || '',
                retention_days:           $('#configRetentionDays').val() || '',
                webhook_url:              $('#configWebhookUrl').val(),
                floating_label:           $('#configFloatingLabel').is(':checked') ? 1 : 0,
                honeypot_enabled:         $('#honeypotToggle').is(':checked') ? 1 : 0,
                captcha_enabled:          $('#captchaToggle').is(':checked') ? 1 : 0,
                custom_css:               typeof editorCss !== 'undefined' ? editorCss.getValue() : '',
                custom_js:                typeof editorJs !== 'undefined' ? editorJs.getValue() : '',
            },
            success: function (res) {
                toastr.success('Configuración guardada correctamente.');
                if (res.name) {
                    $('h5.mb-0.fw-bold').first().text(res.name);
                    $('#formTitleDisplay').text(res.name);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    const first = Object.values(errors)[0]?.[0];
                    toastr.error(first || 'Por favor corrige los errores.');
                } else {
                    toastr.error('Error al guardar la configuración.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false);
                $text.text('Guardar configuración');
            },
        });
    });
</script>
@endpush
