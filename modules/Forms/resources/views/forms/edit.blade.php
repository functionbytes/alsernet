@extends('layouts.theme')

@section('title', 'Editar formulario: ' . $form->name)

@push('css')
<style>
    .field-item {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        transition: box-shadow 0.15s ease;
    }
    .field-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,.1); }
    .field-item.ui-sortable-helper { box-shadow: 0 4px 16px rgba(0,0,0,.15); }
    .drag-handle { cursor: grab; color: #adb5bd; }
    .drag-handle:active { cursor: grabbing; }
    .field-type-badge { font-size: .7rem; }
    .btn-field-type {
        font-size: .75rem;
        padding: .25rem .5rem;
        white-space: nowrap;
    }
</style>
@endpush

@section('content')

    @include('core::components.card', ['title' => 'Constructor de formulario'])

    <div class="widget-content searchable-container list">

        <div class="card">
            <div class="card-header border-bottom">

                {{-- Header --}}
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                            <small class="text-muted">{{ $form->slug }}</small>
                        </div>
                        @if ($form->is_active)
                            <span class="badge bg-light-success text-success">Activo</span>
                        @else
                            <span class="badge bg-light-danger text-danger">Inactivo</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.forms.preview', $form) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-desktop me-1"></i> Preview
                        </a>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-inbox me-1"></i> Submissions
                        </a>
                    </div>
                </div>

                {{-- Tabs --}}
                @include('forms::settings.partials.tabs', ['active' => 'fields', 'tabClass' => 'nav-tabs card-header-tabs'])

            </div>

            <div class="card-body">
            <div class="row g-4">

                {{-- Panel izquierdo: builder --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h6 class="fw-bold mb-0">Campos del formulario</h6>
                        </div>
                        <div class="card-body border-bottom">
                            {{-- Botones para agregar campos --}}
                            <div class="mb-2">
                                <small class="text-muted fw-semibold d-block mb-2">Básicos</small>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ([
                                        'text'     => ['fas fa-font',         'Texto corto'],
                                        'textarea' => ['fas fa-align-left',   'Texto largo'],
                                        'email'    => ['fas fa-at',           'Email'],
                                        'phone'    => ['fas fa-phone',        'Teléfono'],
                                        'number'   => ['fas fa-hashtag',      'Número'],
                                        'date'     => ['fas fa-calendar-alt', 'Fecha'],
                                        'time'     => ['fas fa-clock',        'Hora'],
                                        'url'      => ['fas fa-link',         'URL'],
                                    ] as $type => [$icon, $label])
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-field-type btn-add-field"
                                                data-type="{{ $type }}"
                                                title="{{ $label }}">
                                            <i class="{{ $icon }} me-1"></i>{{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted fw-semibold d-block mb-2">Selección</small>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ([
                                        'select'       => ['fas fa-caret-square-down', 'Select'],
                                        'radio'        => ['far fa-dot-circle',        'Radio'],
                                        'checkbox'     => ['far fa-check-square',      'Checkbox'],
                                        'image_choice' => ['far fa-image',             'Imagen'],
                                    ] as $type => [$icon, $label])
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-field-type btn-add-field"
                                                data-type="{{ $type }}"
                                                title="{{ $label }}">
                                            <i class="{{ $icon }} me-1"></i>{{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted fw-semibold d-block mb-2">Avanzados</small>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ([
                                        'file'         => ['fas fa-paperclip',     'Archivo'],
                                        'rating'       => ['fas fa-star',           'Rating'],
                                        'slider'       => ['fas fa-sliders-h',      'Slider'],
                                        'nps'          => ['fas fa-chart-bar',      'NPS'],
                                        'likert'       => ['fas fa-table',          'Likert'],
                                        'signature'    => ['fas fa-signature',      'Firma'],
                                        'calculation'  => ['fas fa-calculator',     'Cálculo'],
                                        'address'      => ['fas fa-map-marker-alt', 'Dirección'],
                                        'color_picker' => ['fas fa-palette',        'Color'],
                                    ] as $type => [$icon, $label])
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-field-type btn-add-field"
                                                data-type="{{ $type }}"
                                                title="{{ $label }}">
                                            <i class="{{ $icon }} me-1"></i>{{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted fw-semibold d-block mb-2">Layout</small>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ([
                                        'section_header' => ['fas fa-heading',       'Sección'],
                                        'html_block'     => ['fas fa-code',          'HTML'],
                                        'divider'        => ['fas fa-minus',         'Divisor'],
                                        'spacer'         => ['fas fa-arrows-alt-v',  'Espacio'],
                                    ] as $type => [$icon, $label])
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-field-type btn-add-field"
                                                data-type="{{ $type }}"
                                                title="{{ $label }}">
                                            <i class="{{ $icon }} me-1"></i>{{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <small class="text-muted fw-semibold d-block mb-2">Legal</small>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ([
                                        'consent'            => ['fas fa-user-check',    'Consentimiento'],
                                        'newsletter_consent' => ['fas fa-newspaper',     'Newsletter'],
                                        'hidden'             => ['far fa-eye-slash',     'Campo oculto'],
                                        'password'           => ['fas fa-key',           'Contraseña'],
                                    ] as $type => [$icon, $label])
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-field-type btn-add-field"
                                                data-type="{{ $type }}"
                                                title="{{ $label }}">
                                            <i class="{{ $icon }} me-1"></i>{{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Lista de campos --}}
                        <div class="card-body">
                            <div id="fieldsList">
                                @forelse ($form->fields as $field)
                                    @include('forms::forms.partials.field-item', ['field' => $field])
                                @empty
                                    <div id="emptyState" class="text-center py-5">
                                        <i class="fas fa-th-list fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">Sin campos aún</h6>
                                        <p class="text-muted">Haz clic en los botones de arriba para añadir el primer campo</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        @if ($form->is_multi_step)
                            <div class="card-footer border-top">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAddStep">
                                    <i class="fas fa-plus me-1"></i> Agregar paso
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Panel derecho: info --}}
                <div class="col-lg-4">

                    {{-- Shortcodes --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Shortcodes</h6>
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Por ID</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control font-monospace"
                                           value='[form id="{{ $form->id }}"]' readonly>
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-copy-shortcode"
                                            data-shortcode='[form id="{{ $form->id }}"]'
                                            title="Copiar">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="form-label small text-muted mb-1">Por slug</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control font-monospace"
                                           value='[form slug="{{ $form->slug }}"]' readonly>
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-copy-shortcode"
                                            data-shortcode='[form slug="{{ $form->slug }}"]'
                                            title="Copiar">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Estadísticas</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Campos</span>
                                <span class="fw-semibold" id="statsFieldCount">{{ $form->fields->count() }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Submissions</span>
                                <a href="{{ route('settings.forms.submissions.index', $form) }}" class="fw-semibold text-decoration-none">
                                    {{ $form->submissions()->count() }}
                                </a>
                            </div>
                            @if ($form->is_multi_step && $form->steps_config)
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Pasos</span>
                                    <span class="fw-semibold">{{ count($form->steps_config) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Protección anti-spam --}}
                    <div class="card mb-3">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Protección</h6>
                            <small class="text-muted">Anti-spam y reCAPTCHA</small>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="fw-semibold small">Honeypot</div>
                                    <div class="text-muted" style="font-size:.75rem">Campo oculto anti-bots</div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input btn-protection-toggle" type="checkbox"
                                           id="honeypotToggle"
                                           data-field="honeypot_enabled"
                                           {{ $form->honeypot_enabled ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold small">reCAPTCHA</div>
                                    <div class="text-muted" style="font-size:.75rem">Verificación humana</div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input btn-protection-toggle" type="checkbox"
                                           id="captchaToggle"
                                           data-field="captcha_enabled"
                                           {{ $form->captcha_enabled ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Acciones rápidas --}}
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Acciones</h6>
                            <div class="d-flex flex-column gap-2">
                                <a href="{{ route('settings.forms.preview', $form) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-desktop me-2"></i> Preview
                                </a>
                                <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-inbox me-2"></i> Ver submissions
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnShowQr">
                                    <i class="fas fa-qrcode me-2"></i> QR Code
                                </button>
                                <a href="{{ route('settings.forms.export-json', $form) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-file-export me-2"></i> Exportar JSON
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            </div>{{-- /.card-body --}}
        </div>{{-- /.card --}}
    </div>{{-- /.widget-content --}}

{{-- Modal agregar/editar campo --}}
<div class="modal fade" id="fieldModal" tabindex="-1" aria-labelledby="fieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fieldModalLabel">Agregar campo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fieldId">
                <div class="row g-3">

                    {{-- Tipo + Label --}}
                    <div class="col-md-4">
                        <label class="form-label">Tipo de campo <span class="text-danger">*</span></label>
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
                        <input type="text" name="label" id="fieldLabel" class="form-control"
                               placeholder="Ej: Nombre completo">
                        <div class="invalid-feedback" id="labelError"></div>
                    </div>

                    {{-- Key + Ancho --}}
                    <div class="col-md-6">
                        <label class="form-label">
                            Clave (key)
                            <small class="text-muted">auto-generada</small>
                        </label>
                        <input type="text" name="key" id="fieldKey" class="form-control"
                               placeholder="nombre_completo">
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

                    {{-- Placeholder (oculto para layout) --}}
                    <div class="col-12" id="placeholderGroup">
                        <label class="form-label">Placeholder</label>
                        <input type="text" name="placeholder" id="fieldPlaceholder" class="form-control">
                    </div>

                    {{-- Opciones (select/radio/checkbox/image_choice) --}}
                    <div class="col-12 d-none" id="optionsGroup">
                        <label class="form-label">Opciones</label>
                        <div id="optionsList"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addOption">
                            <i class="fas fa-plus me-1"></i> Añadir opción
                        </button>
                    </div>

                    {{-- HTML (html_block) --}}
                    <div class="col-12 d-none" id="htmlContentGroup">
                        <label class="form-label">Contenido HTML</label>
                        <textarea name="html_content" id="fieldHtmlContent" class="form-control" rows="4"
                                  placeholder="<p>Texto de ayuda...</p>"></textarea>
                    </div>

                    {{-- Consentimiento (consent/newsletter_consent) --}}
                    <div class="col-12 d-none" id="consentTextGroup">
                        <label class="form-label">Texto de consentimiento</label>
                        <textarea name="consent_text" id="fieldConsentText" class="form-control" rows="3"
                                  placeholder="Acepto los términos y condiciones..."></textarea>
                    </div>

                    {{-- Min/Max/Step (slider/number/rating/nps) --}}
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

                    {{-- Fórmula (calculation) --}}
                    <div class="col-12 d-none" id="formulaGroup">
                        <label class="form-label">
                            Fórmula
                            <small class="text-muted">Ej: {precio} * {cantidad}</small>
                        </label>
                        <input type="text" name="formula" id="fieldFormula" class="form-control"
                               placeholder="{campo_a} + {campo_b}">
                    </div>

                    {{-- Filas Likert --}}
                    <div class="col-12 d-none" id="likertRowsGroup">
                        <label class="form-label">Preguntas de la escala</label>
                        <div id="likertRowsList"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addLikertRow">
                            <i class="fas fa-plus me-1"></i> Añadir pregunta
                        </button>
                    </div>

                    {{-- Opciones avanzadas --}}
                    <div class="col-12">
                        <a data-bs-toggle="collapse" href="#advancedOptions" class="text-decoration-none small">
                            <i class="fas fa-cog me-1"></i> Opciones avanzadas
                        </a>
                        <div class="collapse" id="advancedOptions">
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label small">Texto de ayuda</label>
                                    <input type="text" name="help_text" id="fieldHelpText" class="form-control form-control-sm"
                                           placeholder="Texto debajo del campo">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Posición del label</label>
                                    <select name="label_position" id="fieldLabelPosition" class="form-select form-select-sm">
                                        <option value="top">Arriba</option>
                                        <option value="floating">Flotante (Material)</option>
                                        <option value="hidden">Oculto</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">URL param para auto-rellenar</label>
                                    <input type="text" name="auto_populate_param" id="fieldAutoPopulate"
                                           class="form-control form-control-sm" placeholder="Ej: name">
                                </div>
                                @if ($form->is_multi_step && !empty($form->steps_config))
                                <div class="col-md-6">
                                    <label class="form-label small">Paso del formulario</label>
                                    <select name="step_number" id="fieldStepNumber" class="form-select form-select-sm">
                                        @foreach ($form->steps_config as $step)
                                            <option value="{{ $step['number'] }}">{{ $step['title'] ?? 'Paso ' . $step['number'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @elseif ($form->is_multi_step)
                                <div class="col-md-6">
                                    <label class="form-label small">Paso del formulario</label>
                                    <input type="number" name="step_number" id="fieldStepNumber"
                                           class="form-control form-control-sm" value="1" min="1"
                                           placeholder="Número de paso">
                                    <div class="form-text" style="font-size:.7rem">Agrega pasos primero usando "Agregar paso"</div>
                                </div>
                                @endif
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" name="is_required" class="form-check-input" id="fieldRequired">
                                        <label class="form-check-label small" for="fieldRequired">Obligatorio</label>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" name="show_char_counter" class="form-check-input" id="fieldCharCounter">
                                        <label class="form-check-label small" for="fieldCharCounter">Contador</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveField">
                    <i class="fas fa-save me-1"></i> Guardar campo
                </button>
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
                <img id="qrImage" src="{{ route('settings.forms.qrcode', $form) }}"
                     alt="QR Code {{ $form->name }}"
                     class="img-fluid rounded mb-3"
                     style="max-width: 250px;">
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
<script>
    const FORM_ID   = {{ $form->id }};
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    const urls = {
        fieldsStore:  '{{ route('settings.forms.fields.store', $form) }}',
        fieldsReorder:'{{ route('settings.forms.fields.reorder', $form) }}',
        fieldEditBase:'{{ route('settings.forms.fields.index', $form) }}',
    };

    const LAYOUT_TYPES          = ['section_header', 'html_block', 'divider', 'spacer'];
    const TYPES_WITH_OPTIONS    = ['select', 'radio', 'checkbox', 'image_choice'];
    const TYPES_WITH_MIN_MAX    = ['slider', 'number', 'rating', 'nps'];
    const TYPES_WITH_CONSENT    = ['consent', 'newsletter_consent'];

    // ─── Sortable ──────────────────────────────────────────────────────────────
    $('#fieldsList').sortable({
        handle: '.drag-handle',
        placeholder: 'ui-state-highlight mb-2',
        update: function () {
            const order = [];
            $('#fieldsList .field-item').each(function (index) {
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

    // ─── Abrir modal para agregar --─────────────────────────────────────────────
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
        $('#minValueGroup, #maxValueGroup, #stepValueGroup').toggleClass('d-none', !TYPES_WITH_MIN_MAX.includes(type));
        $('#formulaGroup').toggleClass('d-none', type !== 'calculation');
        $('#likertRowsGroup').toggleClass('d-none', type !== 'likert');
        $('#placeholderGroup').toggleClass('d-none', isLayout || type === 'signature');
    });

    // ─── Auto-generar key desde label ─────────────────────────────────────────
    $('#fieldLabel').on('input', function () {
        if ($('#fieldId').val()) return; // no sobreescribir si estamos editando
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
        $('#optionsList').append(buildOptionRow('', idx));
    });

    $(document).on('click', '.btn-remove-option', function () {
        $(this).closest('.option-row').remove();
    });

    function buildOptionRow(value, idx) {
        return `<div class="input-group input-group-sm mb-1 option-row">
            <input type="text" class="form-control" name="options[]" value="${value}" placeholder="Opción ${idx + 1}">
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

        // Opciones (formato {value, label} requerido por el backend)
        const options = [];
        $('#optionsList .option-row input').each(function () {
            const v = $(this).val().trim();
            if (v) options.push({ value: v, label: v });
        });
        if (options.length) payload.options = options;

        // Filas Likert
        const likertRows = [];
        $('#likertRowsList .likert-row input').each(function () {
            const v = $(this).val().trim();
            if (v) likertRows.push(v);
        });
        if (likertRows.length) payload.likert_rows = likertRows;

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
        if (field.step_number) $('#fieldStepNumber').val(field.step_number);

        if (field.options && Array.isArray(field.options)) {
            field.options.forEach((opt, idx) => {
                const label = typeof opt === 'object' ? (opt.label ?? opt.value ?? '') : opt;
                $('#optionsList').append(buildOptionRow(label, idx));
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
                if ($('#fieldsList .field-item').length === 0) {
                    $('#emptyState').removeClass('d-none');
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
                // revert toggle on error
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

    // ─── Resetear modal al cerrarlo (X, backdrop, Escape) ────────────────────
    $('#fieldModal').on('hidden.bs.modal', function () {
        resetFieldModal();
    });

    // ─── QR Modal ─────────────────────────────────────────────────────────────
    $('#btnShowQr').on('click', function () {
        new bootstrap.Modal(document.getElementById('qrModal')).show();
    });

    // ─── Copiar shortcode ─────────────────────────────────────────────────────
    $(document).on('click', '.btn-copy-shortcode', function () {
        const shortcode = $(this).data('shortcode');
        navigator.clipboard.writeText(shortcode).then(function () {
            toastr.success('Shortcode copiado al portapapeles');
        });
    });

    // ─── Helpers ───────────────────────────────────────────────────────────────
    function resetFieldModal() {
        $('#fieldId').val('');
        $('#fieldType').val('text').trigger('change');
        $('#fieldLabel, #fieldKey, #fieldPlaceholder, #fieldHelpText, #fieldAutoPopulate').val('');
        $('#fieldHtmlContent, #fieldConsentText, #fieldFormula').val('');
        $('#fieldWidth').val('full');
        $('#fieldLabelPosition').val('top');
        $('#fieldRequired, #fieldCharCounter').prop('checked', false);
        $('#fieldMinValue').val(0);
        $('#fieldMaxValue').val(5);
        $('#fieldStepValue').val(1);
        $('#optionsList, #likertRowsList').empty();
        $('#advancedOptions').collapse('hide');
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
        $('#statsFieldCount').text($('#fieldsList .field-item').length);
    }
</script>
@endpush
