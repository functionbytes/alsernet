@extends('layouts.theme')

@section('title', 'Editar Prompt - ' . $prompt->label)

@section('content')

    @include('core::components.card', ['title' => 'Editar prompt de IA'])

    <form id="promptEditForm" action="{{ route('settings.suppliers.prompts.update', $prompt->uid) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">

            {{-- Left Column: Template Editor --}}
            <div class="col-12 col-lg-8">
                <div class="card">
                    {{-- Header --}}
                    <div class="card-header d-flex align-items-center gap-2">
                        <div class="flex-grow-1 min-w-0">
                            <h5 class="pb-header-title">Editor del prompt</h5>
                            <p class="pb-header-sub">Escribe las instrucciones que guiarán a la IA</p>
                        </div>
                        <span class="pb-badge"><i class="fas fa-code-branch"></i> v{{ $prompt->version }}</span>
                    </div>

                    {{-- Etiqueta --}}
                    <div class="card-body border-bottom">
                        <label for="label" class="form-label fw-semibold" style="font-size:.85rem">
                            Etiqueta <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('label') is-invalid @enderror"
                               id="label" name="label" value="{{ old('label', $prompt->label) }}" required
                               placeholder="ej: Descripción de productos para marketplace">
                        <small class="text-muted" style="font-size:.74rem">Nombre descriptivo que identificará este prompt</small>
                        @error('label')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Version banner --}}
                    <div class="px-3 py-2 border-bottom" style="background: var(--pb-primary-soft);">
                        <small class="d-flex align-items-center gap-2" style="font-size:.76rem;color:var(--pb-muted)">
                            <i class="fas fa-circle-info" style="color: var(--pb-primary);"></i>
                            Si modificas las instrucciones, la versión pasará de
                            <strong class="text-dark">v{{ $prompt->version }}</strong> a
                            <strong class="text-dark">v{{ $prompt->version + 1 }}</strong>.
                        </small>
                    </div>

                    {{-- Tabs --}}
                    <ul class="nav nav-pills user-profile-tab" id="promptTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active" id="template-tab" data-bs-toggle="tab"
                                    data-bs-target="#template-panel" type="button" role="tab">
                               Instrucciones
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 " id="preview-tab" data-bs-toggle="tab"
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
                                                  placeholder="Escribe las instrucciones para la IA...">{{ old('prompt_template', $prompt->prompt_template) }}</textarea>
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
                                $groupIcons = [
                                    'Producto'  => 'fa-box',
                                    'Proveedor' => 'fa-truck',
                                    'Contenido' => 'fa-file-lines',
                                    'Atributos' => 'fa-layer-group',
                                ];
                            @endphp

                            <div class="pb-vars-panel">
                                {{-- Product picker --}}
                                <div class=" mb-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div>
                                                <h6 class="pb-section-title" >Producto de muestra</h6>
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
                                        <h6 class="pb-section-title"><Variables disponibles</h6>
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
                                            <p class="pb-section-sub">Inserta códigos, EAN o UPC reales directamente al prompt</p>
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
                                    <p class="pb-section-sub">Previsualiza el prompt con valores de muestra</p>
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

                                <button type="submit" class="btn  btn-primary w-100 mb-1" >
                                    Actualizar prompt
                                </button>
                                <a href="{{ route('settings.suppliers.prompts.index') }}" class="btn btn-light w-100 " >
                                     Cancelar
                                </a>

                        </div>
                    </div>
                </div>


            {{-- Right Column: Configuration --}}
            <div class="col-12 col-lg-4">

                {{-- Template Selector --}}
                @if($templates->isNotEmpty())
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="pb-side-icon"><i class="fas fa-clone"></i></span>
                        <div>
                            <h6 class="pb-section-title">Usar plantilla</h6>
                            <p class="pb-section-sub">Parte de una plantilla predefinida</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <select class="form-control select2" id="templateSelector">
                            <option value="">Seleccionar plantilla...</option>
                            @php $currentCategory = null; @endphp
                            @foreach($templates as $tpl)
                                @if($tpl->template_category !== $currentCategory)
                                    @if($currentCategory !== null)</option>@endif
                                    @php $currentCategory = $tpl->template_category; @endphp
                                    <optgroup label="{{ ucfirst($tpl->template_category ?: 'Sin categoría') }}">
                                @endif
                                <option value="{{ $tpl->uid }}"
                                    data-label="{{ $tpl->label }}"
                                    data-content-type="{{ $tpl->content_type }}"
                                    data-prompt-template="{{ htmlspecialchars($tpl->prompt_template) }}"
                                    data-output-language="{{ $tpl->output_language }}"
                                    data-tone="{{ $tpl->tone }}"
                                    data-priority="{{ $tpl->priority }}"
                                    data-seo-focus="{{ $tpl->seo_focus ? '1' : '0' }}">
                                    {{ $tpl->label }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-2" style="font-size:.72rem">
                            <i class="fas fa-info-circle me-1"></i>
                            Seleccionar una plantilla rellena los campos. Puedes modificarlos libremente.
                        </small>
                    </div>
                </div>
                @endif

                <div class="card">
                    {{-- Header --}}
                    <div class="card-header d-flex align-items-center gap-2" >
                        <div>
                            <h6 class="pb-section-title">Configuración</h6>
                            <p class="pb-section-sub">Alcance y parámetros del prompt</p>
                        </div>
                    </div>

                    {{-- Alcance y Tipo --}}
                    <div class="card-body border-bottom">
                        <div class="mb-3">
                            <label class="form-label">
                                Alcance <span class="text-danger">*</span>
                                <i class="fas fa-circle-question ms-1 text-muted small" data-bs-toggle="tooltip" title="Define dónde se aplicará este prompt"></i>
                            </label>
                            <select class="form-control select2 @error('scope') is-invalid @enderror"
                                    name="scope" id="promptScope" required>
                                <option value="">Seleccionar...</option>
                                @php $selectedScope = old('scope', $prompt->scope); @endphp
                                <option value="global" {{ $selectedScope === 'global' ? 'selected' : '' }}>Global - Todos los productos</option>
                                <option value="supplier" {{ $selectedScope === 'supplier' ? 'selected' : '' }}>Por proveedor</option>
                                <option value="category" {{ $selectedScope === 'category' ? 'selected' : '' }}>Por categoría</option>
                                <option value="supplier_category" {{ $selectedScope === 'supplier_category' ? 'selected' : '' }}>Por proveedor + categoría</option>
                                <option value="source" {{ $selectedScope === 'source' ? 'selected' : '' }}>Por fuente</option>
                            </select>
                            @error('scope')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3" id="supplierContainer" style="display: none;">
                            <label class="form-label">Proveedor específico</label>
                            <select class="form-control select2" name="supplier_id" id="supplierSelect">
                                <option value="">Seleccionar proveedor...</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id', $prompt->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" id="categoryContainer" style="display: none;">

                            {{-- Toggle modo de selección --}}
                            @php
                                $selectedSubIds = old('subfamily_ids',
                                    $prompt->subfamily_ids
                                        ?? ($prompt->subfamily_id ? [$prompt->subfamily_id] : [])
                                );
                                // Usar el modo guardado explícitamente en BD; si no existe, inferir por conteo
                                $savedMode    = $prompt->subfamily_mode ?? (count((array)$selectedSubIds) > 1 ? 'multi' : 'single');
                                $isMultiMode  = old('subfamily_mode', $savedMode) === 'multi';
                            @endphp
                            <div class="mb-0">
                                <label class="form-label fw-semibold mb-2">Modo de selección</label>
                                <div class="d-flex flex-column gap-2">

                                    {{-- Opción: Una subfamilia --}}
                                    <label for="mode_single" class="subfamily-mode-card {{ !$isMultiMode ? 'active' : '' }}">
                                        <input type="radio" name="subfamily_mode" id="mode_single" value="single"
                                               {{ !$isMultiMode ? 'checked' : '' }}
                                               class="visually-hidden">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="mode-title mb-0">Una subfamilia</p>
                                                <p class="mode-desc">Filtra por Deporte → Categoría → Familia y elige una subfamilia concreta.</p>
                                            </div>
                                            <i class="fas fa-filter fs-5 ms-3 mode-icon"></i>
                                        </div>
                                    </label>

                                    <label for="mode_multi" class="subfamily-mode-card {{ $isMultiMode ? 'active' : '' }}">
                                        <input type="radio" name="subfamily_mode" id="mode_multi" value="multi"
                                               {{ $isMultiMode ? 'checked' : '' }}
                                               class="visually-hidden">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="mode-title mb-0">Varias subfamilias</p>
                                                <p class="mode-desc">Búsqueda directa: selecciona múltiples subfamilias de cualquier familia.</p>
                                            </div>
                                            <i class="fas fa-layer-group fs-5 ms-3 mode-icon"></i>
                                        </div>
                                    </label>

                                </div>
                            </div>
                            <hr class="mt-3 mb-3">

                            {{-- Modo único: cascada completa Deporte → Categoría → Familia → Subfamilia --}}
                            <div id="mode-single-fields" style="{{ $isMultiMode ? 'display:none' : '' }}">
                                <label class="form-label">Deporte</label>
                                <select class="form-control select2" id="sportFilterSelect">
                                    <option value="">Todos los deportes...</option>
                                    @foreach($sports as $sport)
                                        <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                                    @endforeach
                                </select>

                                <label class="form-label mt-2">Categoría</label>
                                <select class="form-control select2" id="erpCatFilterSelect">
                                    <option value="">Todas las categorías...</option>
                                    @foreach($erpcategorias as $ec)
                                        <option value="{{ $ec->erp_categoria_id }}"
                                                data-sport-id="{{ $ec->sport_id }}">
                                            {{ $ec->erp_categoria_name }}
                                        </option>
                                    @endforeach
                                </select>

                                <label class="form-label mt-2">Familia</label>
                                <select class="form-control select2" id="familyFilterSelect">
                                    <option value="">Todas las familias...</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                                data-sport-id="{{ $category->sport_id }}"
                                                data-erp-cat-id="{{ $category->erp_categoria_id }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <label class="form-label mt-2">Subfamilia <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="subfamilySingle">
                                    <option value="">Seleccionar subfamilia...</option>
                                    @foreach($subfamilies as $sf)
                                        <option value="{{ $sf->id }}" data-family-id="{{ $sf->category_id }}"
                                            {{ !$isMultiMode && in_array($sf->id, (array)$selectedSubIds) ? 'selected' : '' }}>
                                            {{ $sf->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Modo múltiple: solo subfamilia con búsqueda directa --}}
                            <div id="mode-multi-fields" style="{{ !$isMultiMode ? 'display:none' : '' }}">
                                <label class="form-label">Subfamilias <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="subfamilyMulti" multiple>
                                    @foreach($subfamilies as $sf)
                                        <option value="{{ $sf->id }}" data-family-id="{{ $sf->category_id }}"
                                            {{ $isMultiMode && in_array($sf->id, (array)$selectedSubIds) ? 'selected' : '' }}>
                                            {{ $sf->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted  mt-2">Escribe para buscar. Puedes seleccionar varias.</small>
                            </div>

                            {{-- Campo oculto que envía el valor final al controlador --}}
                            <div id="subfamily-hidden-fields"></div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">
                                Tipo de contenido <span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2 @error('content_type') is-invalid @enderror" name="content_type" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="description" {{ old('content_type', $prompt->content_type) === 'description' ? 'selected' : '' }}>Descripción completa</option>
                                <option value="short_description" {{ old('content_type', $prompt->content_type) === 'short_description' ? 'selected' : '' }}>Descripción corta</option>
                                <option value="title" {{ old('content_type', $prompt->content_type) === 'title' ? 'selected' : '' }}>Título del producto</option>
                                <option value="seo_title" {{ old('content_type', $prompt->content_type) === 'seo_title' ? 'selected' : '' }}>Título SEO</option>
                                <option value="seo_description" {{ old('content_type', $prompt->content_type) === 'seo_description' ? 'selected' : '' }}>Meta descripción SEO</option>
                                <option value="seo_keywords" {{ old('content_type', $prompt->content_type) === 'seo_keywords' ? 'selected' : '' }}>Palabras clave SEO</option>
                                <option value="features" {{ old('content_type', $prompt->content_type) === 'features' ? 'selected' : '' }}>Características destacadas</option>
                                <option value="specifications" {{ old('content_type', $prompt->content_type) === 'specifications' ? 'selected' : '' }}>Especificaciones técnicas</option>
                                <option value="benefits" {{ old('content_type', $prompt->content_type) === 'benefits' ? 'selected' : '' }}>Beneficios del producto</option>
                                <option value="metadata" {{ old('content_type', $prompt->content_type) === 'metadata' ? 'selected' : '' }}>Metadata personalizada</option>
                            </select>
                            @error('content_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Parámetros de salida --}}
                    <div class="card-body border-bottom">
                        <div class="mb-3">
                            <label class="form-label">Idioma de salida <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('output_language') is-invalid @enderror" name="output_language" required>
                                <option value="es" {{ old('output_language', $prompt->output_language) === 'es' ? 'selected' : '' }}>Español</option>
                                <option value="en" {{ old('output_language', $prompt->output_language) === 'en' ? 'selected' : '' }}>Inglés</option>
                                <option value="fr" {{ old('output_language', $prompt->output_language) === 'fr' ? 'selected' : '' }}>Francés</option>
                                <option value="de" {{ old('output_language', $prompt->output_language) === 'de' ? 'selected' : '' }}>Alemán</option>
                            </select>
                            @error('output_language')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tono <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('tone') is-invalid @enderror" name="tone" required>
                                <option value="professional" {{ old('tone', $prompt->tone) === 'professional' ? 'selected' : '' }}>Profesional</option>
                                <option value="casual" {{ old('tone', $prompt->tone) === 'casual' ? 'selected' : '' }}>Casual</option>
                                <option value="technical" {{ old('tone', $prompt->tone) === 'technical' ? 'selected' : '' }}>Técnico</option>
                                <option value="friendly" {{ old('tone', $prompt->tone) === 'friendly' ? 'selected' : '' }}>Amigable</option>
                                <option value="formal" {{ old('tone', $prompt->tone) === 'formal' ? 'selected' : '' }}>Formal</option>
                            </select>
                            @error('tone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">
                                Prioridad
                                <i class="fas fa-circle-question ms-1 text-muted small" data-bs-toggle="tooltip" title="Mayor prioridad = se usa primero cuando hay varios prompts"></i>
                            </label>
                            <input type="number" class="form-control @error('priority') is-invalid @enderror"
                                   name="priority" value="{{ old('priority', $prompt->priority) }}" min="0" max="100">
                            <small class="text-muted"><strong>0-25:</strong> Baja · <strong>26-75:</strong> Media · <strong>76-100:</strong> Alta</small>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                       @if(old('seo_focus', $prompt->seo_focus ? '1' : '0') === '1') checked @endif>
                            </div>
                        </div>

                        {{-- Búsqueda web siempre activa — campo oculto para compatibilidad --}}
                        <input type="hidden" name="enable_web_search" value="1">

                        {{-- Modelo IA específico --}}
                        <div class="pb-switch-card align-items-start flex-column gap-2 pb-3">
                            <div class="d-flex align-items-center gap-2 w-100">
                                <span class="pb-switch-icon"><i class="fas fa-microchip"></i></span>
                                <div class="pb-switch-body">
                                    <div class="pb-switch-label">Modelo IA</div>
                                    <p class="pb-switch-desc mb-0">Modelo específico para este prompt. Vacío = usar el global.</p>
                                </div>
                            </div>
                            <select class="form-select select2 w-100" name="ai_model" id="ai_model">
                                <option value="">— Usar modelo global por defecto —</option>
                                <optgroup label="OpenAI">
                                    <option value="gpt-4o-mini" @selected(old('ai_model', $prompt->ai_model) === 'gpt-4o-mini')>GPT-4o Mini — rápido y económico</option>
                                    <option value="gpt-4o" @selected(old('ai_model', $prompt->ai_model) === 'gpt-4o')>GPT-4o — calidad superior</option>
                                </optgroup>
                                <optgroup label="Anthropic">
                                    <option value="claude-3-5-haiku" @selected(old('ai_model', $prompt->ai_model) === 'claude-3-5-haiku')>Claude 3.5 Haiku</option>
                                    <option value="claude-3-5-sonnet" @selected(old('ai_model', $prompt->ai_model) === 'claude-3-5-sonnet')>Claude 3.5 Sonnet</option>
                                </optgroup>
                                <optgroup label="Google Gemini (búsqueda web incluida)">
                                    <option value="gemini-2.5-flash" @selected(in_array(old('ai_model', $prompt->ai_model), ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-2.0-flash-lite']))>Gemini 2.5 Flash — recomendado</option>
                                    <option value="gemini-2.5-pro" @selected(old('ai_model', $prompt->ai_model) === 'gemini-2.5-pro')>Gemini 2.5 Pro — máxima calidad</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="pb-switch-card">
                            <span class="pb-switch-icon"><i class="fas fa-toggle-on"></i></span>
                            <div class="pb-switch-body">
                                <div class="pb-switch-label">Prompt activo</div>
                                <p class="pb-switch-desc">Si desactivas, no se usará en la generación automática</p>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="is_active" name="is_active" value="1"
                                       @if(old('is_active', $prompt->is_active ? '1' : '0') === '1') checked @endif>
                            </div>
                        </div>
                    </div>

                    {{-- Version --}}
                    <div class="card-body border-bottom">
                        <h6 class="pb-section-title mb-2">Versión</h6>
                        <div class="pb-version-info"><span class="label">Versión actual</span><span class="pb-badge">v{{ $prompt->version }}</span></div>
                        <div class="pb-version-info"><span class="label">Creado</span><span class="fw-semibold" style="font-size:.82rem">{{ $prompt->created_at->format('d/m/Y H:i') }}</span></div>
                        <div class="pb-version-info"><span class="label">Modificado</span><span class="fw-semibold" style="font-size:.82rem">{{ $prompt->updated_at->format('d/m/Y H:i') }}</span></div>
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

                {{-- Historial de versiones --}}
                @if($versions->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="pb-side-icon"><i class="fas fa-clock-rotate-left"></i></span>
                        <div>
                            <h6 class="pb-section-title">Historial de versiones</h6>
                            <p class="pb-section-sub">{{ $versions->count() }} versión(es) anteriores guardadas</p>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($versions as $ver)
                                <li class="list-group-item px-3 py-2 d-flex align-items-center gap-2"
                                    style="font-size:.8rem">
                                    <span class="badge bg-secondary-subtle text-secondary border"
                                          style="font-size:.7rem;white-space:nowrap">v{{ $ver->version }}</span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="text-truncate fw-semibold">{{ $ver->label }}</div>
                                        <div class="text-muted small">
                                            {{ $ver->created_at?->format('d/m/Y H:i') }}
                                            @if($ver->savedBy)
                                                · {{ $ver->savedBy->full_name }}
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm flex-shrink-0 btn-ver-version"
                                            data-version-id="{{ $ver->id }}"
                                            data-version-num="{{ $ver->version }}"
                                            data-version-url="{{ route('settings.suppliers.prompts.version.show', [$prompt->uid, $ver->id]) }}"
                                            title="Ver instrucciones de v{{ $ver->version }}"
                                            style="font-size:.72rem;padding:.2rem .5rem">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>

            {{-- Modal de versión --}}
            <div class="modal fade" id="version-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-clock-rotate-left me-2 text-muted"></i>
                                <span id="version-modal-title">Versión anterior</span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div id="version-modal-meta" class="px-3 py-2 border-bottom bg-light"
                                 style="font-size:.78rem;color:#555"></div>
                            <pre id="version-modal-content"
                                 style="margin:0;padding:1rem;font-size:.78rem;line-height:1.6;white-space:pre-wrap;word-break:break-word;max-height:55vh;overflow-y:auto;background:#fff"></pre>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-warning btn-sm" id="btn-restore-version">
                                <i class="fas fa-rotate-left me-1"></i>Restaurar esta versión en el editor
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

{{-- Modal conflicto de subfamilias --}}
<div class="modal fade" id="subfamilyConflictModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-warning-subtle"
                          style="width:32px;height:32px;flex-shrink:0;border:1px solid rgba(255,193,7,.3);">
                        <i class="fas fa-triangle-exclamation text-warning" style="font-size:.8rem;"></i>
                    </span>
                    <h6 class="modal-title fw-bold mb-0">Subfamilias ya asignadas</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="text-muted small mb-3">Las siguientes subfamilias ya pertenecen a otro prompt. Cada subfamilia solo puede estar en un prompt a la vez.</p>
                <ul class="list-group list-group-flush border rounded mb-0" id="conflictList"></ul>
            </div>
            <div class="modal-footer border-top px-4 py-3 d-flex flex-column gap-2">
                <button type="button" class="btn btn-primary w-100" id="btnReassignAndSave">Reasignar a este prompt</button>
                <button type="button" class="btn btn-outline-secondary w-100" id="btnClearAndSave">Quitar conflictivas y guardar</button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('[data-bs-toggle="tooltip"]').each(function () { new bootstrap.Tooltip(this); });

    // Scope
    $('#promptScope').on('change', function () {
        const scope = $(this).val();
        $('#supplierContainer, #categoryContainer').hide();
        if (scope === 'supplier' || scope === 'supplier_category') $('#supplierContainer').slideDown(200);
        if (scope === 'category' || scope === 'supplier_category') $('#categoryContainer').slideDown(200);
    }).trigger('change');

    // ── Cascade deporte → categoría ERP → familia → subfamilia ─────────────
    @php
        $jsErpCats  = $erpcategorias->map(fn($c) => ['id' => $c->erp_categoria_id, 'name' => $c->erp_categoria_name, 'sport_id' => $c->sport_id]);
        $jsFamilies = $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'sport_id' => $c->sport_id, 'erp_cat_id' => $c->erp_categoria_id]);
        $jsSubs     = $subfamilies->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'family_id' => $s->category_id]);
        $jsSports   = $sports->map(fn($s) => ['id' => $s->id, 'name' => $s->name]);
    @endphp
    const allErpCats     = @json($jsErpCats);
    const allFamilies    = @json($jsFamilies);
    const allSubfamilies = @json($jsSubs);
    const allSports      = @json($jsSports);
    const initialSubfamilyIds = @json(old('subfamily_ids',
        $prompt->subfamily_ids ?? ($prompt->subfamily_id ? [$prompt->subfamily_id] : [])
    ));
    const initialSubfamilyId = initialSubfamilyIds.length ? initialSubfamilyIds[0] : 0;

    // Lookups para construir el label jerárquico de subfamilia
    const _sportById   = Object.fromEntries(allSports.map(s => [s.id, s.name]));
    const _erpCatById  = Object.fromEntries(allErpCats.map(c => [c.id, c.name]));
    const _familyById  = Object.fromEntries(allFamilies.map(f => [f.id, f]));

    function subfamilyLabel(s) {
        const fam     = _familyById[s.family_id] || {};
        const sport   = _sportById[fam.sport_id]  || '';
        const erpCat  = _erpCatById[fam.erp_cat_id] || '';
        const family  = fam.name || '';
        const parts   = [sport, erpCat, family].filter(Boolean);
        return parts.length ? parts.join(' - ') + ' / ' + s.name : s.name;
    }

    function s2Rebuild($el) {
        if ($.fn.select2 && $el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
    }
    function s2Init($el, opts) {
        if ($.fn.select2) $el.select2(Object.assign({ width: '100%' }, opts || {}));
    }

    // Resalta la coincidencia de búsqueda en el texto de la opción
    function s2HighlightResult(data) {
        if (data.loading || !data.text) return data.text;
        const term = $('.select2-container--open .select2-search__field').val() || '';
        if (!term.trim()) return data.text;
        // Escapar el texto para evitar XSS, luego subrayar coincidencias
        const safe    = $('<div>').text(data.text).html();
        const escaped = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const highlighted = safe.replace(
            new RegExp('(' + escaped + ')', 'gi'),
            '<span style="text-decoration:underline;font-weight:700;color:inherit;">$1</span>'
        );
        return $('<span>').html(highlighted);
    }

    // Opciones base para selects de subfamilia con highlight
    const subfamiliaS2Opts = {
        placeholder:    'Buscar subfamilia(s)...',
        allowClear:     false,
        templateResult: s2HighlightResult,
    };
    const subfamiliaS2OptsSingle = {
        placeholder:    'Seleccionar subfamilia...',
        allowClear:     true,
        templateResult: s2HighlightResult,
    };

    function rebuildErpCatSelect(sportId) {
        const $ec = $('#erpCatFilterSelect');
        s2Rebuild($ec);
        $ec.empty().append('<option value="">Todas las categorías...</option>');
        allErpCats.forEach(c => {
            if (!sportId || c.sport_id == sportId) {
                $ec.append(`<option value="${c.id}" data-sport-id="${c.sport_id}">${c.name}</option>`);
            }
        });
        s2Init($ec);
    }

    function rebuildFamilySelect(sportId, erpCatId, keepFamilyId) {
        const $fam = $('#familyFilterSelect');
        s2Rebuild($fam);
        $fam.empty().append('<option value="">Todas las familias...</option>');
        allFamilies.forEach(f => {
            const matchSport  = !sportId  || f.sport_id   == sportId;
            const matchErpCat = !erpCatId || f.erp_cat_id == erpCatId;
            if (matchSport && matchErpCat) {
                const sel = (keepFamilyId && f.id == keepFamilyId) ? ' selected' : '';
                $fam.append(`<option value="${f.id}" data-sport-id="${f.sport_id}" data-erp-cat-id="${f.erp_cat_id}"${sel}>${f.name}</option>`);
            }
        });
        s2Init($fam);
    }

    // Reconstruye el select single (#subfamilySingle) filtrando por familia
    function rebuildSubfamilySingle(familyId, keepId) {
        const $sf = $('#subfamilySingle');
        s2Rebuild($sf);
        $sf.empty().append('<option value="">Seleccionar subfamilia...</option>');
        allSubfamilies.forEach(s => {
            if (!familyId || s.family_id == familyId) {
                const sel   = (keepId && s.id == keepId) ? ' selected' : '';
                const label = subfamilyLabel(s);
                $sf.append(`<option value="${s.id}" data-family-id="${s.family_id}"${sel}>${label}</option>`);
            }
        });
        s2Init($sf, subfamiliaS2OptsSingle);
        syncHiddenFields();
    }

    // Flag para bloquear cascada durante la precarga inicial
    let _preloading = false;

    $('#sportFilterSelect').on('change', function () {
        if (_preloading) return;
        rebuildErpCatSelect($(this).val());
        rebuildFamilySelect($(this).val(), null, null);
        rebuildSubfamilySingle(null, null);
    });

    $('#erpCatFilterSelect').on('change', function () {
        if (_preloading) return;
        const sportId = $('#sportFilterSelect').val();
        rebuildFamilySelect(sportId, $(this).val(), null);
        rebuildSubfamilySingle(null, null);
    });

    $('#familyFilterSelect').on('change', function () {
        if (_preloading) return;
        rebuildSubfamilySingle($(this).val(), null);
    });

    // ── Modo de selección: single (cascada) / multi (búsqueda directa) ──────────
    function syncHiddenFields() {
        const mode = $('input[name="subfamily_mode"]:checked').val();
        const $hidden = $('#subfamily-hidden-fields');
        $hidden.empty();
        if (mode === 'single') {
            const val = $('#subfamilySingle').val();
            if (val) $hidden.append(`<input type="hidden" name="subfamily_ids[]" value="${val}">`);
        } else {
            $('#subfamilyMulti').val()?.forEach(v => {
                $hidden.append(`<input type="hidden" name="subfamily_ids[]" value="${v}">`);
            });
        }
    }

    // Reconstruye el multiselect con labels jerárquicos (se llama una sola vez al entrar en modo multi)
    let _multiBuilt = false;
    function rebuildSubfamilyMulti(keepIds) {
        const keep = (keepIds || []).map(Number);
        const $sf  = $('#subfamilyMulti');
        s2Rebuild($sf);
        $sf.empty();
        allSubfamilies.forEach(s => {
            const sel   = keep.includes(Number(s.id)) ? ' selected' : '';
            const label = subfamilyLabel(s);
            $sf.append(`<option value="${s.id}" data-family-id="${s.family_id}"${sel}>${label}</option>`);
        });
        s2Init($sf, subfamiliaS2Opts);
        _multiBuilt = true;
    }

    function applyMode(mode) {
        if (mode === 'single') {
            $('#mode-single-fields').show();
            $('#mode-multi-fields').hide();
        } else {
            $('#mode-single-fields').hide();
            $('#mode-multi-fields').show();
            // Construir con jerarquía la primera vez que se activa el modo multi
            if (!_multiBuilt) rebuildSubfamilyMulti($('#subfamilyMulti').val());
        }
        syncHiddenFields();
    }

    function refreshModeCards() {
        $('input[name="subfamily_mode"]').each(function () {
            $(this).closest('label.subfamily-mode-card')
                   .toggleClass('active', $(this).is(':checked'));
        });
    }

    $('input[name="subfamily_mode"]').on('change', function () {
        refreshModeCards();
        applyMode($(this).val());
    });

    // Clic en la tarjeta activa los radios aunque ya estén checked
    $(document).on('click', 'label.subfamily-mode-card', function () {
        const $radio = $(this).find('input[type="radio"]');
        $radio.prop('checked', true).trigger('change');
    });

    // Sync hidden en cada cambio de selects
    $('#subfamilySingle').on('change', syncHiddenFields);
    $('#subfamilyMulti').on('change', syncHiddenFields);

    // Inicializar Select2
    s2Init($('#sportFilterSelect'));
    s2Init($('#erpCatFilterSelect'));
    s2Init($('#familyFilterSelect'));
    s2Init($('#subfamilySingle'), subfamiliaS2OptsSingle);
    s2Init($('#subfamilyMulti'),  subfamiliaS2Opts);

    // Pre-seleccionar al editar
    if (initialSubfamilyIds.length) {
        const isMulti = initialSubfamilyIds.length > 1;
        if (isMulti) {
            // Modo múltiple: construir con jerarquía y pre-cargar selección
            rebuildSubfamilyMulti(initialSubfamilyIds);
        } else {
            // Modo único: pre-cargar cascada + subfamilySingle
            const sf = allSubfamilies.find(s => s.id == initialSubfamilyIds[0]);
            if (sf) {
                const fam = allFamilies.find(f => f.id == sf.family_id);
                _preloading = true;
                if (fam) {
                    $('#sportFilterSelect').val(fam.sport_id).trigger('change.select2');
                    rebuildErpCatSelect(fam.sport_id);
                    if (fam.erp_cat_id) $('#erpCatFilterSelect').val(fam.erp_cat_id).trigger('change.select2');
                    rebuildFamilySelect(fam.sport_id, fam.erp_cat_id, fam.id);
                    $('#familyFilterSelect').val(fam.id).trigger('change.select2');
                    rebuildSubfamilySingle(sf.family_id, sf.id);
                }
                _preloading = false;
            }
        }
    }

    applyMode($('input[name="subfamily_mode"]:checked').val() || 'single');

    // Sincronizar subfamilySingle cuando cambia la familia (modo único)
    // (rebuildSubfamilySelect ya no existe en modo único — usamos rebuildSubfamilySingle)
    syncHiddenFields();

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

    // Template selector
    $('#templateSelector').on('change', function () {
        const opt = $(this).find('option:selected');
        if (!opt.val()) return;
        $('#promptTemplate').val(opt.data('prompt-template')).trigger('input');
        $('select[name="content_type"]').val(opt.data('content-type')).trigger('change');
        $('select[name="output_language"]').val(opt.data('output-language')).trigger('change');
        $('select[name="tone"]').val(opt.data('tone')).trigger('change');
        $('input[name="priority"]').val(opt.data('priority'));
        $('#seo_focus').prop('checked', opt.data('seo-focus') == 1);
        toastr.info('Plantilla aplicada', 'Plantilla cargada');
    });

    const originalTemplate = document.getElementById('promptTemplate')?.value || '';
    const conflictCheckUrl = '{{ route("settings.suppliers.prompts.check-subfamily-conflicts") }}';
    const currentPromptUid = '{{ $prompt->uid }}';
    const $promptForm = $('#promptEditForm');
    let _conflictingIds = [];

    function getSelectedSubfamilyIds() {
        return $('#subfamily-hidden-fields input[name="subfamily_ids[]"]').map(function () {
            return parseInt(this.value);
        }).get().filter(Boolean);
    }

    function doSubmit() {
        const ta = document.getElementById('promptTemplate');
        if (ta && ta.value !== originalTemplate) {
            toastr.info('La versión se actualizará de v{{ $prompt->version }} a v{{ $prompt->version + 1 }}', 'Control de versiones', { timeOut: 3000 });
        }
        $promptForm[0].submit();
    }

    $promptForm.on('submit', function (e) {
        e.preventDefault();
        syncHiddenFields();
        const ids = getSelectedSubfamilyIds();
        if (!ids.length) { doSubmit(); return; }

        $.post(conflictCheckUrl, {
            _token: $('meta[name="csrf-token"]').attr('content'),
            subfamily_ids: ids,
            exclude_uid: currentPromptUid,
        }, function (res) {
            if (!res.conflicts || !res.conflicts.length) { doSubmit(); return; }

            _conflictingIds = res.conflicts.map(c => c.subfamily_id);
            const $list = $('#conflictList').empty();
            res.conflicts.forEach(c => {
                $list.append(`
                    <li class="list-group-item py-2 px-3">
                        <div class="fw-semibold small">${c.subfamily_name}</div>
                        <div class="text-muted" style="font-size:.78rem">Asignada al prompt: <strong>${c.prompt_label}</strong></div>
                    </li>`);
            });
            new bootstrap.Modal(document.getElementById('subfamilyConflictModal')).show();
        });
    });

    $('#btnClearAndSave').on('click', function () {
        const mode = $('input[name="subfamily_mode"]:checked').val();
        if (mode === 'single') {
            if (_conflictingIds.includes(parseInt($('#subfamilySingle').val()))) {
                $('#subfamilySingle').val(null).trigger('change');
            }
        } else {
            const current = ($('#subfamilyMulti').val() || []).map(Number);
            const filtered = current.filter(id => !_conflictingIds.includes(id));
            $('#subfamilyMulti').val(filtered.map(String)).trigger('change');
        }
        syncHiddenFields();
        bootstrap.Modal.getInstance(document.getElementById('subfamilyConflictModal')).hide();
        doSubmit();
    });

    $('#btnReassignAndSave').on('click', function () {
        $promptForm.find('input[name="force_reassign"]').remove();
        $promptForm.append('<input type="hidden" name="force_reassign" value="1">');
        bootstrap.Modal.getInstance(document.getElementById('subfamilyConflictModal')).hide();
        doSubmit();
    });

    // ── Historial de versiones ─────────────────────────────────────────────
    let loadedVersionTemplate = '';

    $(document).on('click', '.btn-ver-version', function () {
        const url     = $(this).data('version-url');
        const verNum  = $(this).data('version-num');

        $('#version-modal-title').text('Versión v' + verNum);
        $('#version-modal-content').text('Cargando…');
        $('#version-modal-meta').text('');
        loadedVersionTemplate = '';

        const modal = new bootstrap.Modal(document.getElementById('version-modal'));
        modal.show();

        $.get(url, function (res) {
            if (!res.success) { $('#version-modal-content').text('Error al cargar.'); return; }
            const v = res.version;
            loadedVersionTemplate = v.prompt_template;

            const meta = [
                v.scope, v.content_type, v.output_language, v.tone,
                v.created_at,
                v.saved_by ? ('Guardado por: ' + v.saved_by) : null,
            ].filter(Boolean).join(' · ');

            $('#version-modal-meta').text(meta);
            $('#version-modal-content').text(v.prompt_template || '(vacío)');
        }).fail(function () {
            $('#version-modal-content').text('Error al cargar la versión.');
        });
    });

    $('#btn-restore-version').on('click', function () {
        if (!loadedVersionTemplate) return;
        if (!confirm('¿Restaurar esta versión en el editor? Perderás los cambios no guardados.')) return;
        document.getElementById('promptTemplate').value = loadedVersionTemplate;
        updateEditorStats();
        bootstrap.Modal.getInstance(document.getElementById('version-modal'))?.hide();
        toastr.info('Versión restaurada en el editor. Guarda para confirmar el cambio.');
    });
});
</script>
@endpush
