@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Editar plantilla de prompt'])

    <form id="formTemplate" method="POST" action="{{ route('settings.suppliers.templates.update', $template->uid) }}">
        @csrf
        @method('PUT')

        <div class="row g-3">

            {{-- Left Column: Template Editor --}}
            <div class="col-12 col-lg-8">
                <div class="card">
                    {{-- Header --}}
                    <div class="card-header d-flex align-items-center gap-2">
                        <div class="flex-grow-1 min-w-0">
                            <h5 class="pb-header-title">Editor de la plantilla</h5>
                            <p class="pb-header-sub">Modificando: <strong>{{ $template->label }}</strong></p>
                        </div>
                        <a href="{{ route('settings.suppliers.templates.index') }}" class="pb-badge text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>

                    {{-- Nombre --}}
                    <div class="card-body border-bottom">
                        <label for="label" class="form-label fw-semibold" style="font-size:.85rem">
                            Nombre de la plantilla <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('label') is-invalid @enderror"
                               id="label" name="label" value="{{ old('label', $template->label) }}" required
                               placeholder="Ej: Template: Descripción Producto E-commerce">
                        <small class="text-muted" style="font-size:.74rem">Nombre descriptivo para identificar la plantilla</small>
                        @error('label')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Tabs --}}
                    <ul class="nav nav-pills user-profile-tab" id="promptTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"
                                    id="template-tab" data-bs-toggle="tab"
                                    data-bs-target="#template-panel" type="button" role="tab">
                                Instrucciones
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                                    id="preview-tab" data-bs-toggle="tab"
                                    data-bs-target="#preview-panel" type="button" role="tab">
                                Vista previa
                            </button>
                        </li>
                    </ul>

                    {{-- Tab Content --}}
                    <div class="tab-content">

                        {{-- Tab 1: Template Editor --}}
                        <div class="tab-pane fade show active" id="template-panel" role="tabpanel">

                            <div class="card-body">
                                <div class="card pb-editor-card">
                                    <div class="card-header">
                                        <div class="pb-toolbar-left">
                                            <button type="button" class="pb-toolbar-btn" id="btnInsertVar" title="Ir a variables">
                                                <i class="fas fa-code"></i> Variable
                                            </button>
                                            <span class="pb-toolbar-sep"></span>
                                            <button type="button" class="pb-toolbar-btn" id="btnClearEditor" title="Limpiar">
                                                <i class="fas fa-eraser"></i>
                                            </button>
                                            <button type="button" class="pb-toolbar-btn" id="btnCopyPrompt" title="Copiar">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                        <div class="pb-toolbar-right">
                                            <span class="pb-editor-stat">
                                                <span class="pb-stat-val" id="charCount">0</span> chars
                                            </span>
                                            <span class="pb-editor-stat">
                                                <span class="pb-stat-val" id="lineCount">0</span> lineas
                                            </span>
                                            <span class="pb-editor-stat">
                                                <span class="pb-stat-val" id="varUsedCount">0</span> vars
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <textarea class="form-control pb-editor @error('prompt_template') is-invalid @enderror"
                                                  name="prompt_template" id="promptTemplate"
                                                  rows="16" required
                                                  placeholder="Escribe las instrucciones para la IA...">{{ old('prompt_template', $template->prompt_template) }}</textarea>
                                    </div>
                                    <div class="card-footer">
                                        <span class="pb-hint">
                                            <i class="fas fa-lightbulb"></i> Usa <code>@{{ variable }}</code> para datos dinámicos
                                        </span>
                                        <span class="pb-hint">
                                            <i class="fas fa-arrow-down"></i> Clic en las variables de abajo para insertar
                                        </span>
                                    </div>
                                </div>
                                @error('prompt_template')
                                    <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Variables & Attributes Panel --}}
                            @php
                                $promptVariables = [
                                    'product_name'         => ['label' => 'Nombre del producto', 'group' => 'Producto'],
                                    'product_code'         => ['label' => 'Código del producto', 'group' => 'Producto'],
                                    'product_status'       => ['label' => 'Estado (Activo/Inactivo)', 'group' => 'Producto'],
                                    'reference'            => ['label' => 'Referencia', 'group' => 'Producto'],
                                    'category'             => ['label' => 'Categoría principal', 'group' => 'Producto'],
                                    'brand'                => ['label' => 'Marca', 'group' => 'Producto'],
                                    'supplier'             => ['label' => 'Nombre del proveedor', 'group' => 'Proveedor'],
                                    'supplier_code'        => ['label' => 'Código del proveedor', 'group' => 'Proveedor'],
                                    'supplier_email'       => ['label' => 'Email del proveedor', 'group' => 'Proveedor'],
                                    'supplier_description' => ['label' => 'Descripción del proveedor', 'group' => 'Proveedor'],
                                    'supplier_info'        => ['label' => 'Info completa del proveedor', 'group' => 'Proveedor'],
                                    'short_description'    => ['label' => 'Descripción corta existente', 'group' => 'Contenido'],
                                    'long_description'     => ['label' => 'Descripción larga existente', 'group' => 'Contenido'],
                                    'specifications'       => ['label' => 'Especificaciones técnicas', 'group' => 'Contenido'],
                                    'features'             => ['label' => 'Características destacadas', 'group' => 'Contenido'],
                                    'attributes'           => ['label' => 'Listado completo de variantes', 'group' => 'Atributos'],
                                    'attributes_count'     => ['label' => 'Nº total de variantes', 'group' => 'Atributos'],
                                    'attributes_codes'     => ['label' => 'Códigos (Code 1) separados por coma', 'group' => 'Atributos'],
                                    'attributes_codes2'    => ['label' => 'Códigos secundarios (Code 2)', 'group' => 'Atributos'],
                                    'attributes_ean13'     => ['label' => 'EAN-13 separados por coma', 'group' => 'Atributos'],
                                    'attributes_upc'       => ['label' => 'UPC separados por coma', 'group' => 'Atributos'],
                                    'attributes_references' => ['label' => 'Referencias separadas por coma', 'group' => 'Atributos'],
                                ];
                                $groupedVars = collect($promptVariables)->groupBy(fn ($v) => $v['group'], preserveKeys: true);
                            @endphp

                            <div class="pb-vars-panel">
                                {{-- Product picker --}}
                                <div class="mb-3">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div>
                                            <h6 class="pb-section-title">Producto de muestra</h6>
                                            <p class="pb-section-sub">Carga un producto real para previsualizar valores y variantes</p>
                                        </div>
                                    </div>
                                    <select class="form-control form-control-sm" id="samplePromptProduct">
                                        <option value="">Buscar por código o nombre...</option>
                                    </select>
                                </div>

                                {{-- Variables grouped --}}
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="pb-section-title">Variables disponibles</h6>
                                        <p class="pb-section-sub">Haz clic en cualquier variable para insertarla en el editor</p>
                                    </div>
                                    <span class="pb-badge" id="varCount">{{ count($promptVariables) }} vars</span>
                                </div>

                                @foreach($groupedVars as $groupName => $vars)
                                    <div class="pb-var-group">
                                        <div class="pb-var-group-header">
                                            {{ $groupName }}
                                            <span class="pb-count">{{ $vars->count() }}</span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($vars as $var => $meta)
                                                @php $varTag = '{{ '.$var.' }}'; @endphp
                                                <span class="pb-var-chip variable-btn"
                                                      data-variable="{{ $varTag }}"
                                                      data-var-key="{{ $var }}"
                                                      data-bs-toggle="tooltip" title="{{ $meta['label'] }}">
                                                    {{ $varTag }}<span class="var-value-preview d-none ms-1"></span>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                                {{-- Sample values preview --}}
                                <div id="sampleValuesPanel" class="d-none mt-3 pb-values-panel">
                                    <div class="pb-var-group-header mb-2">
                                        Valores reales del producto
                                    </div>
                                    <div id="sampleValuesList" style="max-height:200px;overflow-y:auto"></div>
                                </div>

                                {{-- Attributes browser --}}
                                <div id="attributesPanel" class="d-none mt-3">
                                    <div class="d-flex justify-content-between align-items-end mb-2 gap-2 flex-wrap">
                                        <div>
                                            <h6 class="pb-section-title">
                                                Atributos / variantes
                                                <span class="pb-badge ms-1" id="attrCount">0</span>
                                            </h6>
                                            <p class="pb-section-sub">Inserta códigos, EAN o UPC reales directamente al template</p>
                                        </div>
                                        <div class="input-group input-group-sm" style="max-width:240px">
                                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search small text-muted"></i></span>
                                            <input type="text" class="form-control form-control-sm border-start-0" id="attrFilter" placeholder="Filtrar...">
                                        </div>
                                    </div>
                                    <div class="border rounded bg-white" style="max-height:300px;overflow-y:auto">
                                        <table class="table table-hover pb-attr-table" id="attrTable">
                                            <thead>
                                                <tr>
                                                    <th>Code 1</th>
                                                    <th>Code 2</th>
                                                    <th>EAN-13</th>
                                                    <th>UPC</th>
                                                    <th>Referencia</th>
                                                    <th>Nombre</th>
                                                    <th style="width:130px" class="text-end">Insertar</th>
                                                </tr>
                                            </thead>
                                            <tbody id="attrTableBody"></tbody>
                                        </table>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <strong>C</strong> Code 1 · <strong>2</strong> Code 2 · <strong>E</strong> EAN · <strong>U</strong> UPC · <strong>R</strong> Ref · <strong>N</strong> Nombre
                                    </small>
                                </div>
                            </div>

                        </div>

                        {{-- Tab 2: Preview --}}
                        <div class="tab-pane fade card-body" id="preview-panel" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
                                <div>
                                    <h6 class="pb-section-title">Vista previa</h6>
                                    <p class="pb-section-sub">Previsualiza el template con valores de muestra</p>
                                </div>
                                <button type="button" class="btn btn-sm" id="btnRefreshPreview"
                                        style="background:var(--pb-primary);color:#fff;font-size:.8rem">
                                    <i class="fas fa-sync-alt me-1"></i> Actualizar
                                </button>
                            </div>
                            <pre id="previewContent" class="pb-preview mb-0"></pre>
                        </div>

                    </div>

                    {{-- Action Buttons --}}
                    <div class="card-footer pb-sticky-actions">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Actualizar plantilla
                        </button>
                        <a href="{{ route('settings.suppliers.templates.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>

            {{-- Right Column: Configuration --}}
            <div class="col-12 col-lg-4">
                <div class="card">
                    {{-- Header --}}
                    <div class="card-header d-flex align-items-center gap-2">
                        <div>
                            <h6 class="pb-section-title">Configuración</h6>
                            <p class="pb-section-sub">Parámetros y clasificación de la plantilla</p>
                        </div>
                    </div>

                    {{-- Tipo y Categoría --}}
                    <div class="card-body border-bottom">
                        <div class="mb-3">
                            <label class="form-label">
                                Tipo de contenido <span class="text-danger">*</span>
                                <i class="fas fa-circle-question ms-1 text-muted small" data-bs-toggle="tooltip" title="Qué tipo de contenido generará esta plantilla"></i>
                            </label>
                            <select name="content_type" class="form-control select2 @error('content_type') is-invalid @enderror" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="description" {{ old('content_type', $template->content_type) == 'description' ? 'selected' : '' }}>Descripción completa</option>
                                <option value="short_description" {{ old('content_type', $template->content_type) == 'short_description' ? 'selected' : '' }}>Descripción corta</option>
                                <option value="title" {{ old('content_type', $template->content_type) == 'title' ? 'selected' : '' }}>Título del producto</option>
                                <option value="seo_title" {{ old('content_type', $template->content_type) == 'seo_title' ? 'selected' : '' }}>Título SEO</option>
                                <option value="seo_description" {{ old('content_type', $template->content_type) == 'seo_description' ? 'selected' : '' }}>Meta descripción SEO</option>
                                <option value="seo_keywords" {{ old('content_type', $template->content_type) == 'seo_keywords' ? 'selected' : '' }}>Palabras clave SEO</option>
                                <option value="features" {{ old('content_type', $template->content_type) == 'features' ? 'selected' : '' }}>Características destacadas</option>
                                <option value="specifications" {{ old('content_type', $template->content_type) == 'specifications' ? 'selected' : '' }}>Especificaciones técnicas</option>
                                <option value="benefits" {{ old('content_type', $template->content_type) == 'benefits' ? 'selected' : '' }}>Beneficios del producto</option>
                                <option value="metadata" {{ old('content_type', $template->content_type) == 'metadata' ? 'selected' : '' }}>Metadata personalizada</option>
                            </select>
                            @error('content_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Categoría de la plantilla</label>
                            <input type="text" name="template_category" class="form-control @error('template_category') is-invalid @enderror"
                                   value="{{ old('template_category', $template->template_category) }}"
                                   placeholder="Ej: ecommerce, seo, marketing">
                            <small class="text-muted" style="font-size:.74rem">Opcional. Ayuda a organizar las plantillas</small>
                            @error('template_category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Parámetros de salida --}}
                    <div class="card-body border-bottom">
                        <div class="mb-3">
                            <label class="form-label">Idioma de salida <span class="text-danger">*</span></label>
                            <select name="output_language" class="form-control select2 @error('output_language') is-invalid @enderror" required>
                                <option value="es" {{ old('output_language', $template->output_language) == 'es' ? 'selected' : '' }}>Español</option>
                                <option value="en" {{ old('output_language', $template->output_language) == 'en' ? 'selected' : '' }}>Inglés</option>
                                <option value="fr" {{ old('output_language', $template->output_language) == 'fr' ? 'selected' : '' }}>Francés</option>
                                <option value="de" {{ old('output_language', $template->output_language) == 'de' ? 'selected' : '' }}>Alemán</option>
                            </select>
                            @error('output_language')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tono de comunicación <span class="text-danger">*</span></label>
                            <select name="tone" class="form-control select2 @error('tone') is-invalid @enderror" required>
                                <option value="professional" {{ old('tone', $template->tone) == 'professional' ? 'selected' : '' }}>Profesional - Formal y empresarial</option>
                                <option value="casual" {{ old('tone', $template->tone) == 'casual' ? 'selected' : '' }}>Casual - Relajado y cercano</option>
                                <option value="enthusiastic" {{ old('tone', $template->tone) == 'enthusiastic' ? 'selected' : '' }}>Entusiasta - Dinámico y enérgico</option>
                                <option value="formal" {{ old('tone', $template->tone) == 'formal' ? 'selected' : '' }}>Formal - Serio y corporativo</option>
                                <option value="friendly" {{ old('tone', $template->tone) == 'friendly' ? 'selected' : '' }}>Amigable - Cálido y acogedor</option>
                                <option value="persuasive" {{ old('tone', $template->tone) == 'persuasive' ? 'selected' : '' }}>Persuasivo - Orientado a la venta</option>
                                <option value="technical" {{ old('tone', $template->tone) == 'technical' ? 'selected' : '' }}>Técnico - Detallado y especializado</option>
                                <option value="elegant" {{ old('tone', $template->tone) == 'elegant' ? 'selected' : '' }}>Elegante - Refinado y sofisticado</option>
                            </select>
                            @error('tone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">
                                Prioridad
                                <i class="fas fa-circle-question ms-1 text-muted small" data-bs-toggle="tooltip" title="Cuando varios prompts coinciden, se usa el de mayor prioridad"></i>
                            </label>
                            <input type="number" class="form-control @error('priority') is-invalid @enderror"
                                   name="priority" value="{{ old('priority', $template->priority) }}" min="0" max="100">
                            <small class="text-muted"><strong>0-25:</strong> Baja · <strong>26-75:</strong> Media · <strong>76-100:</strong> Alta</small>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Switches --}}
                    <div class="card-body border-bottom">
                        <div class="pb-switch-card">
                            <span class="pb-switch-icon"><i class="fas fa-chart-line"></i></span>
                            <div class="pb-switch-body">
                                <div class="pb-switch-label">Optimización SEO</div>
                                <p class="pb-switch-desc">Incluir palabras clave y técnicas de posicionamiento</p>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="seo_focus" name="seo_focus" value="1"
                                       @if(old('seo_focus', $template->seo_focus) == '1') checked @endif>
                            </div>
                        </div>
                    </div>

                    {{-- Notas internas --}}
                    <div class="card-body border-bottom">
                        <label class="form-label">Notas internas</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                  rows="3" placeholder="Notas de uso interno sobre esta plantilla (opcional)">{{ old('notes', $template->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Tips --}}
                    <div class="card-body">
                        <h6 class="pb-section-title">Consejos</h6>
                        <ul class="pb-tips mt-2">
                            <li><strong>Sé específico:</strong> define longitud, formato y estilo</li>
                            <li><strong>Contexto:</strong> describe la audiencia objetivo</li>
                            <li><strong>Ejemplos:</strong> muestra el formato esperado</li>
                            <li><strong>Límites:</strong> indica qué NO incluir</li>
                            <li><strong>Itera:</strong> ajusta según resultados</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </form>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('[data-bs-toggle="tooltip"]').each(function () { new bootstrap.Tooltip(this); });

    // Editor stats
    const $textarea = $('#promptTemplate');
    function updateEditorStats() {
        const val = $textarea.val() || '';
        $('#charCount').text(val.length);
        $('#lineCount').text(val ? val.split('\n').length : 0);
        const varMatches = val.match(/\{\{[^}]+\}\}/g);
        $('#varUsedCount').text(varMatches ? varMatches.length : 0);
    }
    $textarea.on('input keyup', updateEditorStats);
    updateEditorStats();

    // Toolbar
    $('#btnClearEditor').on('click', function () {
        if (!$textarea.val().trim() || confirm('¿Limpiar todo el contenido del editor?')) {
            $textarea.val('').trigger('input').focus();
        }
    });
    $('#btnCopyPrompt').on('click', function () {
        navigator.clipboard.writeText($textarea.val()).then(() => toastr.success('Copiado al portapapeles'));
    });
    $('#btnInsertVar').on('click', function () {
        $('html, body').animate({ scrollTop: $('.pb-var-group').first().offset().top - 100 }, 300);
    });

    // Insert variable
    $(document).on('click', '.variable-btn', function () {
        const variable = $(this).data('variable');
        const ta = document.getElementById('promptTemplate');
        const start = ta.selectionStart, end = ta.selectionEnd;
        ta.value = ta.value.substring(0, start) + variable + ta.value.substring(end);
        ta.selectionStart = ta.selectionEnd = start + variable.length;
        ta.focus();
        updateEditorStats();
    });

    // ── Sample product loader ──
    const searchUrl = '{{ route("settings.suppliers.prompts.builder.products.search") }}';
    const sampleUrlTpl = '{{ route("settings.suppliers.prompts.builder.products.sample", ["product" => "__ID__"]) }}';

    const defaultSample = {
        product_name: 'Rifle de caza Benelli R1', product_code: 'RIF-001', product_status: 'Activo',
        reference: 'RIF-001', category: 'Rifles', brand: 'Benelli',
        supplier: 'Armería España', supplier_code: 'ARM-ES', supplier_email: 'ventas@armeria.es',
        supplier_description: 'Proveedor especializado en armas deportivas',
        supplier_info: 'Armería España (ARM-ES) — Proveedor especializado',
        short_description: 'Rifle semiautomático de alta precisión',
        long_description: 'Descripción larga de ejemplo del producto',
        specifications: 'Peso: 3.2 kg\nCalibre: 30-06', features: 'Culata regulable\nMira telescópica',
        attributes: '- VAR-01 | Talla M | Cod2: SC-01 | EAN-13: 8412345678901 | UPC: 012345678905 | Ref: XYZ\n- VAR-02 | Talla L | Cod2: SC-02 | EAN-13: 8412345678902 | UPC: 012345678912 | Ref: ABC',
        attributes_count: '2',
        attributes_codes: 'VAR-01, VAR-02', attributes_codes2: 'SC-01, SC-02',
        attributes_ean13: '8412345678901, 8412345678902', attributes_upc: '012345678905, 012345678912',
        attributes_references: 'XYZ, ABC',
    };
    let currentSample = { ...defaultSample };
    let currentAttributes = [];

    $('#samplePromptProduct').select2({
        placeholder: 'Buscar producto por código o nombre…',
        allowClear: true, width: '100%', minimumInputLength: 2,
        ajax: { url: searchUrl, dataType: 'json', delay: 250, data: params => ({ q: params.term }) },
    });

    $('#samplePromptProduct').on('select2:select', e => loadSampleProduct(e.params.data.id));
    $('#samplePromptProduct').on('select2:clear', function () {
        currentSample = { ...defaultSample }; currentAttributes = [];
        $('#attributesPanel, #sampleValuesPanel').addClass('d-none');
        $('.var-value-preview').addClass('d-none').text('');
        refreshPreview();
    });

    function loadSampleProduct(productId) {
        $.get(sampleUrlTpl.replace('__ID__', productId), function (res) {
            if (!res.success) { toastr.error('No se pudo cargar'); return; }
            currentSample = { ...defaultSample, ...res.variables };
            currentAttributes = res.attributes || [];
            $('.variable-btn').each(function () {
                const key = $(this).data('var-key'), val = currentSample[key];
                const $p = $(this).find('.var-value-preview');
                if (val != null && String(val).trim()) {
                    $p.removeClass('d-none').text('= ' + (String(val).length > 25 ? String(val).slice(0,25)+'…' : val));
                } else { $p.addClass('d-none').text(''); }
            });
            renderSampleValues(); renderAttributes(currentAttributes);
            $('#sampleValuesPanel, #attributesPanel').removeClass('d-none');
            $('#attrCount').text(currentAttributes.length);
            refreshPreview();
            toastr.success('Producto cargado', 'Datos de muestra');
        });
    }

    function renderSampleValues() {
        const rows = Object.entries(currentSample)
            .filter(([k,v]) => v != null && String(v).trim())
            .map(([k,v]) => {
                const val = String(v).length > 100 ? String(v).slice(0,100)+'…' : v;
                return `<div class="pb-values-row"><code>${k}</code><span class="text-muted" style="white-space:pre-wrap;font-size:.78rem">${$('<div>').text(val).html()}</span></div>`;
            }).join('');
        $('#sampleValuesList').html(rows || '<em class="text-muted">Sin valores</em>');
    }

    function renderAttributes(attrs) {
        const esc = v => $('<div>').text(v ?? '').html();
        const dash = '<span class="text-muted">—</span>';
        const rows = attrs.map((a, idx) => {
            const badge = a.available
                ? '<span class="badge bg-success-subtle text-success ms-1" style="font-size:.6rem">OK</span>'
                : '';
            return `<tr data-idx="${idx}">
                <td class="font-monospace small">${esc(a.code)||dash}${badge}</td>
                <td class="font-monospace small">${esc(a.code_secundary)||dash}</td>
                <td class="font-monospace small">${esc(a.ean13)||dash}</td>
                <td class="font-monospace small">${esc(a.upc)||dash}</td>
                <td class="font-monospace small text-muted">${esc(a.reference)||dash}</td>
                <td class="small">${esc(a.name)}</td>
                <td class="text-end"><div class="btn-group btn-group-sm pb-attr-insert-group">
                    <button type="button" class="btn btn-outline-secondary attr-insert" data-field="code">C</button>
                    <button type="button" class="btn btn-outline-secondary attr-insert" data-field="code_secundary">2</button>
                    <button type="button" class="btn btn-outline-secondary attr-insert" data-field="ean13">E</button>
                    <button type="button" class="btn btn-outline-secondary attr-insert" data-field="upc">U</button>
                    <button type="button" class="btn btn-outline-secondary attr-insert" data-field="reference">R</button>
                    <button type="button" class="btn btn-outline-secondary attr-insert" data-field="name">N</button>
                </div></td></tr>`;
        }).join('');
        $('#attrTableBody').html(rows || '<tr><td colspan="7" class="text-center text-muted py-3"><em>Sin atributos</em></td></tr>');
    }

    $('#attrFilter').on('input', function () {
        const q = $(this).val().toLowerCase().trim();
        if (!q) { renderAttributes(currentAttributes); $('#attrCount').text(currentAttributes.length); return; }
        const f = currentAttributes.filter(a => [a.code,a.code_secundary,a.reference,a.ean13,a.upc,a.name].some(v => v && String(v).toLowerCase().includes(q)));
        renderAttributes(f); $('#attrCount').text(f.length);
    });

    $(document).on('click', '.attr-insert', function () {
        const idx = $(this).closest('tr').data('idx'), field = $(this).data('field');
        const value = currentAttributes[idx]?.[field] ?? '';
        if (!value) { toastr.warning('Sin valor en ' + field); return; }
        insertAtCursor(value);
    });

    function insertAtCursor(text) {
        const ta = document.getElementById('promptTemplate');
        const s = ta.selectionStart, e = ta.selectionEnd;
        ta.value = ta.value.substring(0,s) + text + ta.value.substring(e);
        ta.selectionStart = ta.selectionEnd = s + text.length;
        ta.focus(); updateEditorStats();
    }

    function refreshPreview() {
        let preview = $('#promptTemplate').val();
        Object.entries(currentSample).forEach(([k,v]) => {
            preview = preview.replace(new RegExp('\\{\\{\\s*'+k+'\\s*\\}\\}','g'), v ?? '');
        });
        $('#previewContent').text(preview || '(El template está vacío)');
    }
    $('#btnRefreshPreview, #preview-tab').on('click', refreshPreview);

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
