@extends('layouts.theme')

@section('title', 'Editar formulario: ' . $form->name)

@push('css')
@include('forms::forms.partials._edit-styles')
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

                        <div class="mb-3">
                            <label class="form-label" for="configSendConfirmation">Enviar confirmación al usuario</label>
                            <select class="form-select" id="configSendConfirmation">
                                <option value="1" {{ $form->send_confirmation ? 'selected' : '' }}>Activado</option>
                                <option value="0" {{ !$form->send_confirmation ? 'selected' : '' }}>Desactivado</option>
                            </select>
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

                        <div class="mb-3">
                            <label class="form-label" for="configFloatingLabel">Labels flotantes</label>
                            <select class="form-select" id="configFloatingLabel">
                                <option value="1" {{ $form->floating_label ? 'selected' : '' }}>Activado</option>
                                <option value="0" {{ !$form->floating_label ? 'selected' : '' }}>Desactivado</option>
                            </select>
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
                    <div class="mb-3">
                        <label class="form-label fw-semibold small mb-1" for="honeypotToggle">Honeypot</label>
                        <div class="text-muted protection-hint mb-1">Campo oculto anti-bots</div>
                        <select class="form-select form-select-sm btn-protection-toggle" id="honeypotToggle" data-field="honeypot_enabled">
                            <option value="1" {{ $form->honeypot_enabled ? 'selected' : '' }}>Activado</option>
                            <option value="0" {{ !$form->honeypot_enabled ? 'selected' : '' }}>Desactivado</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small mb-1" for="captchaToggle">reCAPTCHA</label>
                        <div class="text-muted protection-hint mb-1">Verificación humana</div>
                        <select class="form-select form-select-sm btn-protection-toggle" id="captchaToggle" data-field="captcha_enabled">
                            <option value="1" {{ $form->captcha_enabled ? 'selected' : '' }}>Activado</option>
                            <option value="0" {{ !$form->captcha_enabled ? 'selected' : '' }}>Desactivado</option>
                        </select>
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
                                    <div>
                                        <label class="form-label fw-semibold mb-1" for="fieldRequired">Obligatorio</label>
                                        <div class="text-muted field-check-hint mb-1">El campo debe completarse</div>
                                        <select class="form-select form-select-sm" name="is_required" id="fieldRequired">
                                            <option value="1">Sí</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold mb-1" for="fieldCharCounter">Contador de caracteres</label>
                                        <div class="text-muted field-check-hint mb-1">Muestra caracteres escritos</div>
                                        <select class="form-select form-select-sm" name="show_char_counter" id="fieldCharCounter">
                                            <option value="1">Sí</option>
                                            <option value="0">No</option>
                                        </select>
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
@include('forms::forms.partials._edit-scripts')
@endpush
