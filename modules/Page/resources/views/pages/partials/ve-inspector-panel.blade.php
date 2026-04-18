<div id="ve-inspector-panel" class="ve-panel-root">

    {{-- Header --}}
    <div class="ve-panel-header">
        <div class="ve-panel-header-row">
            <div>
                <div class="ve-label-tag">Elemento</div>
                <span id="ve-inspector-element-name" class="ve-element-name">Ninguno</span>
                <div id="ve-sc-context-label" class="ve-hidden ve-sc-context-label"></div>
            </div>
            <div class="ve-btn-row-gap4">
                <button type="button" class="btn btn-outline-secondary ve-btn-icon" id="btn-copy-styles"
                        title="Copiar estilos">
                    <i class="fa-solid fa-copy"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary ve-btn-icon" id="btn-paste-styles"
                        title="Pegar estilos" disabled>
                    <i class="fa-solid fa-paste"></i>
                </button>
                {{-- Improvement 12: Undo CSS button --}}
                <button type="button" class="btn btn-outline-secondary ve-btn-icon ve-hidden" id="btn-undo-style"
                        title="Deshacer último cambio CSS">
                    <i class="fa-solid fa-undo-alt"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary ve-btn-icon ve-hidden" id="btn-hide-element"
                        title="Ocultar elemento">
                    <i class="fa-solid fa-eye-slash"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary ve-btn-icon" id="btn-deselect-element"
                        title="Deseleccionar">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        </div>
        {{-- Breadcrumb + dims --}}
        <div class="ve-breadcrumb-row">
            <div id="ve-inspector-breadcrumb" class="ve-breadcrumb ve-hidden ve-breadcrumb-text"></div>
            <span id="ve-element-dims-display" class="ve-breadcrumb-path ve-monospace ve-dims-display"></span>
        </div>
    </div>

    {{-- Empty state --}}
    <div id="ve-inspector-empty" class="ve-empty-state">
        <div class="ve-empty-text">Haz click en un elemento<br>del preview para inspeccionarlo</div>
    </div>

    {{-- Sections (shown when element selected) --}}
    <div id="ve-inspector-sections" class="ve-hidden ve-sections-wrap">

        {{-- Visibility --}}
        <div id="ve-section-visibility" class="ve-hidden">
            <div class="ve-section-header" data-target="ve-sect-visibility">
                <span>Visibilidad</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-visibility" class="ve-sect-body">
                <div class="ve-hint-text">Ocultar en dispositivos:</div>
                <div class="ve-flex-wrap-gap mb-10px">
                    <button type="button" class="btn btn-outline-secondary ve-visibility-btn"
                            data-class="d-none d-sm-block" title="Ocultar en móvil">
                        Móvil
                    </button>
                    <button type="button" class="btn btn-outline-secondary ve-visibility-btn"
                            data-class="d-sm-none d-md-block" title="Ocultar en tablet">
                        Tablet
                    </button>
                    <button type="button" class="btn btn-outline-secondary ve-visibility-btn"
                            data-class="d-md-none d-lg-block" title="Ocultar en desktop">
                        Desktop
                    </button>
                </div>
                <div class="ve-border-top-pad">
                    <button type="button" class="btn btn-outline-secondary w-100 ve-btn-text" id="btn-toggle-hidden">
                        Ocultar elemento
                    </button>
                    <button type="button" class="btn btn-outline-secondary w-100 mt-1 ve-btn-text ve-hidden" id="btn-toggle-shown">
                        Mostrar elemento
                    </button>
                </div>
            </div>
        </div>

        {{-- Attributes --}}
        <div id="ve-section-attributes" class="ve-hidden">
            <div class="ve-section-header collapsed" data-target="ve-sect-attrs">
                <span>Atributos HTML</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-attrs" class="ve-hidden ve-sect-body">
                <div class="ve-field">
                    <label>ID</label>
                    <input type="text" class="form-control" id="ve-attr-id" placeholder="elemento-id">
                </div>
                <div class="ve-field">
                    <label>Clases CSS</label>
                    <input type="text" class="form-control" id="ve-attr-class" placeholder="clase1 clase2">
                </div>
                <div id="ve-attr-src-wrap" class="ve-hidden ve-field">
                    <label>src</label>
                    <input type="text" class="form-control" id="ve-attr-src" placeholder="https://...">
                </div>
                <div id="ve-attr-alt-wrap" class="ve-hidden ve-field">
                    <label>alt</label>
                    <input type="text" class="form-control" id="ve-attr-alt" placeholder="Texto alternativo">
                </div>
                <div id="ve-attr-href-wrap" class="ve-hidden ve-field">
                    <label>href</label>
                    <input type="url" class="form-control" id="ve-attr-href" placeholder="https://...">
                </div>
                <div id="ve-attr-placeholder-wrap" class="ve-hidden ve-field">
                    <label>placeholder</label>
                    <input type="text" class="form-control" id="ve-attr-placeholder" placeholder="Placeholder...">
                </div>
                <button type="button" class="btn ve-btn-primary w-100 mt-2" id="btn-apply-attrs">
                    Aplicar atributos
                </button>

                {{-- Bootstrap quick classes (Improvement 13) --}}
                <div class="mt-3">
                    <div class="ve-hint-text ve-hint-strong mb-1">Clases rápidas</div>
                    <div id="ve-bs-classes-wrap">
                        <div class="ve-bs-cat-label">Texto</div>
                        <div class="ve-flex-wrap-gap mb-1">
                            <span class="ve-bs-chip" data-class="text-center">center</span>
                            <span class="ve-bs-chip" data-class="text-start">start</span>
                            <span class="ve-bs-chip" data-class="text-end">end</span>
                            <span class="ve-bs-chip" data-class="fw-bold">bold</span>
                            <span class="ve-bs-chip" data-class="fw-semibold">semibold</span>
                            <span class="ve-bs-chip" data-class="fw-normal">normal</span>
                            <span class="ve-bs-chip" data-class="fst-italic">italic</span>
                            <span class="ve-bs-chip" data-class="text-uppercase">UPPER</span>
                            <span class="ve-bs-chip" data-class="text-capitalize">Capitalize</span>
                            <span class="ve-bs-chip" data-class="text-lowercase">lower</span>
                        </div>
                        <div class="ve-bs-cat-label">Espaciado</div>
                        <div class="ve-flex-wrap-gap mb-1">
                            <span class="ve-bs-chip" data-class="p-2">p-2</span>
                            <span class="ve-bs-chip" data-class="p-3">p-3</span>
                            <span class="ve-bs-chip" data-class="p-4">p-4</span>
                            <span class="ve-bs-chip" data-class="px-3">px-3</span>
                            <span class="ve-bs-chip" data-class="py-3">py-3</span>
                            <span class="ve-bs-chip" data-class="m-0">m-0</span>
                            <span class="ve-bs-chip" data-class="mb-3">mb-3</span>
                            <span class="ve-bs-chip" data-class="mb-4">mb-4</span>
                            <span class="ve-bs-chip" data-class="mt-3">mt-3</span>
                            <span class="ve-bs-chip" data-class="mt-4">mt-4</span>
                        </div>
                        <div class="ve-bs-cat-label">Visual</div>
                        <div class="ve-flex-wrap-gap mb-1">
                            <span class="ve-bs-chip" data-class="rounded">rounded</span>
                            <span class="ve-bs-chip" data-class="rounded-pill">pill</span>
                            <span class="ve-bs-chip" data-class="rounded-0">sq</span>
                            <span class="ve-bs-chip" data-class="shadow">shadow</span>
                            <span class="ve-bs-chip" data-class="shadow-sm">shadow-sm</span>
                            <span class="ve-bs-chip" data-class="shadow-lg">shadow-lg</span>
                            <span class="ve-bs-chip" data-class="border">border</span>
                            <span class="ve-bs-chip" data-class="border-0">border-0</span>
                            <span class="ve-bs-chip" data-class="overflow-hidden">overflow</span>
                            <span class="ve-bs-chip" data-class="d-none">d-none</span>
                        </div>
                        <div class="ve-bs-cat-label">Layout</div>
                        <div class="ve-flex-wrap-gap mb-1">
                            <span class="ve-bs-chip" data-class="d-flex">flex</span>
                            <span class="ve-bs-chip" data-class="align-items-center">align-c</span>
                            <span class="ve-bs-chip" data-class="justify-content-center">just-c</span>
                            <span class="ve-bs-chip" data-class="flex-wrap">wrap</span>
                            <span class="ve-bs-chip" data-class="w-100">w-100</span>
                            <span class="ve-bs-chip" data-class="container">container</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form field config (only for inputs with data-form-field-id) --}}
        <div id="ve-section-form-field" class="ve-hidden">
            <div class="ve-section-header" data-target="ve-sect-form-field">
                <span>Configuración del campo <span id="ve-ff-type-badge" class="badge bg-secondary ms-1 ve-hidden ve-badge-xs"></span></span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-form-field" class="ve-sect-body">
                <div class="ve-field">
                    <label>Etiqueta (label)</label>
                    <input type="text" class="form-control" id="ve-ff-label" placeholder="Nombre del campo">
                </div>
                <div class="ve-field" id="ve-ff-placeholder-wrap">
                    <label>Placeholder</label>
                    <input type="text" class="form-control" id="ve-ff-placeholder" placeholder="Texto de ejemplo...">
                </div>
                <div class="ve-field">
                    <label>Texto de ayuda</label>
                    <input type="text" class="form-control" id="ve-ff-help-text" placeholder="Descripción breve...">
                </div>
                <div class="ve-field d-flex align-items-center gap-2">
                    <input type="checkbox" class="form-check-input" id="ve-ff-required">
                    <label for="ve-ff-required" style="margin:0">Obligatorio</label>
                </div>
                <button type="button" class="btn ve-btn-primary w-100 mt-2" id="btn-save-form-field">
                    <i class="fa-solid fa-save me-1"></i> Guardar campo
                </button>
                <div id="ve-ff-status" class="mt-1" style="font-size:11px;min-height:16px;"></div>
            </div>
        </div>

        {{-- Shortcode editor (only for elements inside data-ve-sc sentinels) --}}
        <div id="ve-section-shortcode" class="ve-hidden">
            <div class="ve-section-header" data-target="ve-sect-shortcode">
                <span>Bloque: <span id="ve-sc-name-badge" class="badge bg-secondary ms-1 ve-badge-xs"></span></span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-shortcode" class="ve-sect-body">
                <div class="ve-sc-tabs mb-2">
                    <button type="button" class="ve-sc-tab active" data-tab="attrs">Atributos</button>
                    <button type="button" class="ve-sc-tab" data-tab="items">Ítems</button>
                    <button type="button" class="ve-sc-tab" data-tab="raw">Raw</button>
                </div>
                <div id="ve-sc-description" class="ve-hint-text ve-hidden" style="margin-bottom:6px;font-style:italic;"></div>
                <div id="ve-sc-pane-attrs">
                    <div id="ve-sc-attrs-list"></div>
                </div>
                <div id="ve-sc-pane-items" class="ve-hidden">
                    <div class="ve-sc-items-toolbar">
                        <button type="button" class="ve-sc-toolbar-btn" id="btn-sc-expand-all" title="Expandir todos">
                            <i class="fa-solid fa-chevron-down"></i> Expandir
                        </button>
                        <button type="button" class="ve-sc-toolbar-btn" id="btn-sc-collapse-all" title="Colapsar todos">
                            <i class="fa-solid fa-chevron-up"></i> Colapsar
                        </button>
                        <button type="button" class="ve-sc-toolbar-btn" id="btn-sc-items-view-toggle" title="Cambiar vista" data-view="cards">
                            <i class="fa-solid fa-table"></i> Tabla
                        </button>
                    </div>
                    <div id="ve-sc-items-list"></div>
                    <button type="button" class="btn btn-outline-secondary w-100 mt-1 ve-btn-text" id="btn-sc-add-item">
                        <i class="fa-solid fa-plus me-1"></i> Agregar ítem
                    </button>
                </div>
                <div id="ve-sc-pane-raw" class="ve-hidden">
                    <div class="ve-field">
                        <label>Shortcode</label>
                        <textarea class="form-control ve-monospace" id="ve-sc-raw-input" rows="5" style="font-size:11px;"></textarea>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-1 w-100" id="btn-sc-copy">
                        <i class="fa-solid fa-copy me-1"></i> Copiar shortcode
                    </button>
                    <p class="ve-hint-text">Edita el shortcode directamente. Ten cuidado con la sintaxis.</p>
                </div>
                <div class="ve-sc-footer mt-2">
                    <button type="button" class="btn ve-btn-primary w-100" id="btn-sc-apply" title="Aplicar (Ctrl+Enter)">
                        <i class="fas fa-check me-1"></i> Aplicar
                        <kbd class="ve-sc-apply-kbd">Ctrl+↵</kbd>
                    </button>
                    <div class="ve-sc-action-groups">
                        {{-- Historial --}}
                        <div class="ve-sc-action-group" id="ve-sc-group-history">
                            <button type="button" class="btn btn-outline-secondary ve-sc-icon-btn ve-hidden" id="btn-sc-undo" title="Revertir al estado anterior">
                                <i class="fas fa-rotate-left"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-sc-icon-btn ve-hidden" id="btn-sc-reset" title="Restablecer al original">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                        {{-- Bloque --}}
                        <div class="ve-sc-action-group" id="ve-sc-group-block">
                            <button type="button" class="btn btn-outline-secondary ve-sc-icon-btn ve-hidden" id="btn-sc-move-up" title="Mover bloque arriba">
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-sc-icon-btn ve-hidden" id="btn-sc-move-down" title="Mover bloque abajo">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-sc-icon-btn ve-hidden" id="btn-sc-duplicate" title="Duplicar bloque">
                                <i class="fas fa-clone"></i>
                            </button>
                        </div>
                        {{-- IO / Guardar --}}
                        <div class="ve-sc-action-group" id="ve-sc-group-io">
                            <button type="button" class="btn btn-outline-secondary ve-sc-icon-btn ve-hidden" id="btn-sc-export-json" title="Exportar configuración">
                                <i class="fas fa-download"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-sc-icon-btn ve-hidden" id="btn-sc-import-json" title="Importar configuración">
                                <i class="fas fa-upload"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-sc-icon-btn ve-hidden" id="btn-sc-save-preset" title="Guardar como preset">
                                <i class="fas fa-bookmark"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-sc-icon-btn ve-hidden" id="btn-sc-save-page" title="Guardar página">
                                <i class="fas fa-floppy-disk"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="ve-sc-status" class="mt-1" style="font-size:11px;min-height:16px;"></div>
                {{-- Improvement F: Presets chips container --}}
                <div id="ve-sc-presets-wrap" class="ve-hidden mt-1"></div>
            </div>
        </div>

        {{-- Link (only for <a>) --}}
        <div id="ve-section-link-wrapper" class="ve-hidden">
            <div class="ve-section-header" data-target="ve-sect-link">
                <span>Enlace</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-link" class="ve-sect-body">
                <div class="ve-field">
                    <label>URL</label>
                    <div class="ve-link-url-wrap">
                        <input type="text" class="form-control" id="ve-link-href-inspector" placeholder="https://...">
                        <button type="button" class="btn btn-outline-secondary btn-sm ve-link-page-btn mt-1 w-100" id="btn-pick-page-link" title="Seleccionar una página registrada">
                            <i class="fa-solid fa-file-lines me-1"></i> Seleccionar página
                        </button>
                        <div id="ve-link-page-picker-wrap" class="ve-hidden mt-1">
                            <select id="ve-link-page-select" class="form-select form-select-sm" style="width:100%"></select>
                            <div id="ve-link-page-preview" class="ve-hint-text mt-1" style="word-break:break-all;"></div>
                        </div>
                    </div>
                </div>
                <div class="ve-field">
                    <label>Abrir en</label>
                    <select class="form-select form-select" id="ve-link-target-inspector">
                        <option value="">Misma pestaña</option>
                        <option value="_blank">Nueva pestaña</option>
                    </select>
                </div>
                <button type="button" class="btn ve-btn-primary w-100" id="btn-apply-link-inspector">
                    Aplicar enlace
                </button>
            </div>
        </div>

        {{-- Typography --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header" data-target="ve-sect-typo">
                <span>Tipografía</span>
                <div class="ve-section-header-actions">
                    <button type="button" class="ve-reset-section-btn" data-section="typo"
                            title="Restablecer tipografía"
                            onclick="event.stopPropagation()"><i class="fa-solid fa-undo-alt"></i></button>
                    <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-typo" class="ve-sect-body">
                <div class="ve-field">
                    <label>Familia de fuente</label>
                    <select class="form-select form-select ve-css-prop" data-prop="font-family">
                        <option value="">Predeterminada</option>
                        <option value="'Arial', sans-serif">Arial</option>
                        <option value="'Helvetica Neue', Helvetica, sans-serif">Helvetica</option>
                        <option value="'Georgia', serif">Georgia</option>
                        <option value="'Times New Roman', serif">Times New Roman</option>
                        <option value="'Courier New', monospace">Courier New</option>
                        <option value="'Trebuchet MS', sans-serif">Trebuchet MS</option>
                        <option value="'Verdana', sans-serif">Verdana</option>
                        <option value="'Roboto', sans-serif">Roboto</option>
                        <option value="'Open Sans', sans-serif">Open Sans</option>
                        <option value="'Lato', sans-serif">Lato</option>
                        <option value="'Montserrat', sans-serif">Montserrat</option>
                        <option value="'Poppins', sans-serif">Poppins</option>
                        <option value="'Inter', sans-serif">Inter</option>
                        <option value="'Playfair Display', serif">Playfair Display</option>
                    </select>
                </div>
                <div class="ve-grid-2 ve-field">
                    <div>
                        <label>Tamaño</label>
                        <div class="input-group">
                            <input type="number" class="form-control ve-css-prop" data-prop="font-size" data-unit="px" placeholder="16" min="8" max="200">
                            <span class="input-group-text ve-unit-text ve-unit-toggle" data-units="px,rem,em">px</span>
                        </div>
                    </div>
                    <div>
                        <label>Peso</label>
                        <select class="form-select form-select ve-css-prop" data-prop="font-weight">
                            <option value="">Normal</option>
                            <option value="300">Light (300)</option>
                            <option value="400">Regular (400)</option>
                            <option value="500">Medium (500)</option>
                            <option value="600">Semi-bold (600)</option>
                            <option value="700">Bold (700)</option>
                            <option value="900">Black (900)</option>
                        </select>
                    </div>
                </div>
                <div class="ve-field">
                    <label>Color de texto</label>
                    <div class="ve-color-row">
                        <input type="color" class="form-control form-control-color ve-css-prop ve-color-picker-input"
                               data-prop="color" value="#000000">
                        <input type="text" class="form-control ve-color-text ve-monospace"
                               placeholder="#000000">
                    </div>
                </div>
                <div class="ve-field">
                    <label>Alineación</label>
                    <div class="btn-group btn-group-sm w-100">
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-align" data-value="left" title="Izquierda"><i class="fa-solid fa-align-left"></i></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-align" data-value="center" title="Centro"><i class="fa-solid fa-align-center"></i></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-align" data-value="right" title="Derecha"><i class="fa-solid fa-align-right"></i></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-align" data-value="justify" title="Justificado"><i class="fa-solid fa-align-justify"></i></button>
                    </div>
                </div>
                <div class="ve-field">
                    <label>
                        Interlineado <small class="ve-range-value ve-range-dark">1.5</small>
                    </label>
                    <input type="range" class="form-range ve-css-prop"
                           data-prop="line-height" data-unit=""
                           min="1" max="3" step="0.1" value="1.5">
                </div>
                <div class="ve-field">
                    <label>Transformación</label>
                    <select class="form-select form-select ve-css-prop" data-prop="text-transform">
                        <option value="">Normal</option>
                        <option value="uppercase">MAYÚSCULAS</option>
                        <option value="lowercase">minúsculas</option>
                        <option value="capitalize">Capitalizar</option>
                    </select>
                </div>
                <div class="ve-field">
                    <label>Decoración</label>
                    <div class="btn-group btn-group-sm w-100">
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-decoration" data-value="none" title="Sin decoración"><i class="fa-solid fa-minus"></i></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-decoration" data-value="underline" title="Subrayado"><u>S</u></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-decoration" data-value="line-through" title="Tachado"><s>T</s></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-decoration" data-value="overline" title="Línea superior"><span class="ve-overline">L</span></button>
                    </div>
                </div>
                <div>
                    <label>
                        Espaciado letras <small class="ve-range-value ve-range-dark">0px</small>
                    </label>
                    <input type="range" class="form-range ve-css-prop" data-prop="letter-spacing" data-unit="px" min="-3" max="15" step="0.5" value="0">
                </div>
            </div>
        </div>

        {{-- Spacing --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-spacing">
                <span>Espaciado</span>
                <div class="ve-section-header-actions">
                    <button type="button" class="ve-reset-section-btn" data-section="spacing"
                            title="Restablecer espaciado"
                            onclick="event.stopPropagation()"><i class="fa-solid fa-undo-alt"></i></button>
                    <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-spacing" class="ve-hidden ve-sect-body">
                <div class="ve-spacing-presets mb-1">
                    <span class="ve-spacing-quick-label">Quick:</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm ve-spacing-preset" data-value="0">0</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ve-spacing-preset" data-value="4px">4</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ve-spacing-preset" data-value="8px">8</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ve-spacing-preset" data-value="16px">16</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ve-spacing-preset" data-value="24px">24</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ve-spacing-preset" data-value="32px">32</button>
                </div>
                <div class="ve-spacing-title">Padding (interior)</div>
                <div class="ve-box-model ve-box-model-mb">
                    <div></div>
                    <input type="number" class="form-control text-center ve-css-prop ve-box-input" data-prop="padding-top" data-unit="px" placeholder="0" min="0">
                    <div></div>
                    <input type="number" class="form-control text-center ve-css-prop ve-box-input" data-prop="padding-left" data-unit="px" placeholder="0" min="0">
                    <div class="ve-box-center">P</div>
                    <input type="number" class="form-control text-center ve-css-prop ve-box-input" data-prop="padding-right" data-unit="px" placeholder="0" min="0">
                    <div></div>
                    <input type="number" class="form-control text-center ve-css-prop ve-box-input" data-prop="padding-bottom" data-unit="px" placeholder="0" min="0">
                    <div></div>
                </div>
                <div class="ve-spacing-title">Margin (exterior)</div>
                <div class="ve-box-model">
                    <div></div>
                    <input type="number" class="form-control text-center ve-css-prop ve-box-input" data-prop="margin-top" data-unit="px" placeholder="0">
                    <div></div>
                    <input type="number" class="form-control text-center ve-css-prop ve-box-input" data-prop="margin-left" data-unit="px" placeholder="0">
                    <div class="ve-box-center">M</div>
                    <input type="number" class="form-control text-center ve-css-prop ve-box-input" data-prop="margin-right" data-unit="px" placeholder="0">
                    <div></div>
                    <input type="number" class="form-control text-center ve-css-prop ve-box-input" data-prop="margin-bottom" data-unit="px" placeholder="0">
                    <div></div>
                </div>
            </div>
        </div>

        {{-- Layout CSS section removed (duplicate of ve-sect-layout below) --}}
        <div id="ve-section-layout-css" class="ve-hidden" style="display:none!important">
            <div id="ve-sect-layout-css" class="ve-hidden ve-sect-body">

                {{-- Display --}}
                <div class="ve-field">
                    <label>Display</label>
                    <select class="form-select form-select-sm" id="ve-css-display">
                        <option value="">— sin cambiar —</option>
                        <option value="block">block</option>
                        <option value="flex">flex</option>
                        <option value="grid">grid</option>
                        <option value="inline">inline</option>
                        <option value="inline-flex">inline-flex</option>
                        <option value="inline-block">inline-block</option>
                        <option value="none">none</option>
                    </select>
                </div>

                {{-- Flex sub-opciones --}}
                <div id="ve-css-flex-opts" class="ve-hidden">
                    <div class="ve-field">
                        <label>flex-direction</label>
                        <select class="form-select form-select-sm" id="ve-css-flex-direction">
                            <option value="row">row</option>
                            <option value="row-reverse">row-reverse</option>
                            <option value="column">column</option>
                            <option value="column-reverse">column-reverse</option>
                        </select>
                    </div>
                    <div class="ve-field">
                        <label>justify-content</label>
                        <select class="form-select form-select-sm" id="ve-css-justify-content">
                            <option value="">—</option>
                            <option value="flex-start">flex-start</option>
                            <option value="center">center</option>
                            <option value="flex-end">flex-end</option>
                            <option value="space-between">space-between</option>
                            <option value="space-around">space-around</option>
                            <option value="space-evenly">space-evenly</option>
                        </select>
                    </div>
                    <div class="ve-field">
                        <label>align-items</label>
                        <select class="form-select form-select-sm" id="ve-css-align-items">
                            <option value="">—</option>
                            <option value="flex-start">flex-start</option>
                            <option value="center">center</option>
                            <option value="flex-end">flex-end</option>
                            <option value="stretch">stretch</option>
                            <option value="baseline">baseline</option>
                        </select>
                    </div>
                    <div class="ve-field">
                        <label>flex-wrap</label>
                        <select class="form-select form-select-sm" id="ve-css-flex-wrap">
                            <option value="nowrap">nowrap</option>
                            <option value="wrap">wrap</option>
                            <option value="wrap-reverse">wrap-reverse</option>
                        </select>
                    </div>
                </div>

                {{-- Grid sub-opciones --}}
                <div id="ve-css-grid-opts" class="ve-hidden">
                    <div class="ve-field">
                        <label>grid-template-columns</label>
                        <input type="text" class="form-control form-control-sm" id="ve-css-grid-cols" placeholder="repeat(3,1fr)">
                    </div>
                </div>

                {{-- Gap (flex y grid) --}}
                <div id="ve-css-gap-wrap" class="ve-hidden ve-field">
                    <label>gap</label>
                    <input type="text" class="form-control form-control-sm" id="ve-css-gap" placeholder="1rem">
                </div>

                {{-- Position --}}
                <div class="ve-field mt-2">
                    <label>position</label>
                    <select class="form-select form-select-sm" id="ve-css-position">
                        <option value="">— sin cambiar —</option>
                        <option value="static">static</option>
                        <option value="relative">relative</option>
                        <option value="absolute">absolute</option>
                        <option value="fixed">fixed</option>
                        <option value="sticky">sticky</option>
                    </select>
                </div>

                {{-- top/right/bottom/left (solo cuando position != static/vacío) --}}
                <div id="ve-css-inset-wrap" class="ve-hidden">
                    <div class="ve-grid-2-gap6">
                        <div class="ve-field">
                            <label>top</label>
                            <input type="text" class="form-control form-control-sm" id="ve-css-top" placeholder="auto">
                        </div>
                        <div class="ve-field">
                            <label>right</label>
                            <input type="text" class="form-control form-control-sm" id="ve-css-right" placeholder="auto">
                        </div>
                        <div class="ve-field">
                            <label>bottom</label>
                            <input type="text" class="form-control form-control-sm" id="ve-css-bottom" placeholder="auto">
                        </div>
                        <div class="ve-field">
                            <label>left</label>
                            <input type="text" class="form-control form-control-sm" id="ve-css-left" placeholder="auto">
                        </div>
                    </div>
                </div>

                {{-- z-index --}}
                <div class="ve-field mt-1">
                    <label>z-index</label>
                    <input type="number" class="form-control form-control-sm" id="ve-css-z-index" placeholder="auto" min="-999" max="9999">
                </div>

                {{-- overflow --}}
                <div class="ve-field">
                    <label>overflow</label>
                    <select class="form-select form-select-sm" id="ve-css-overflow">
                        <option value="">— sin cambiar —</option>
                        <option value="visible">visible</option>
                        <option value="hidden">hidden</option>
                        <option value="scroll">scroll</option>
                        <option value="auto">auto</option>
                        <option value="clip">clip</option>
                    </select>
                </div>

            </div>
        </div>

        {{-- Layout --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-layout">
                <span>Layout</span>
                <div class="ve-section-header-actions">
                    <button type="button" class="ve-reset-section-btn" data-section="layout"
                            title="Restablecer layout"
                            onclick="event.stopPropagation()"><i class="fa-solid fa-undo-alt"></i></button>
                    <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-layout" class="ve-hidden ve-sect-body">

                {{-- Display --}}
                <div class="ve-field">
                    <label>Display</label>
                    <select class="form-select form-select ve-css-prop" data-prop="display" id="ve-layout-display-select">
                        <option value="">Predeterminado</option>
                        <option value="block">block</option>
                        <option value="flex">flex</option>
                        <option value="inline-flex">inline-flex</option>
                        <option value="grid">grid</option>
                        <option value="inline-block">inline-block</option>
                        <option value="inline">inline</option>
                        <option value="none">none (ocultar)</option>
                    </select>
                </div>

                {{-- Flex sub-options --}}
                <div id="ve-layout-flex-controls" class="ve-hidden">
                    <div class="ve-field">
                        <label>Dirección</label>
                        <div class="btn-group btn-group-sm w-100">
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="row" title="Fila →"><i class="fa-solid fa-arrow-right"></i></button>
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="column" title="Columna ↓"><i class="fa-solid fa-arrow-down"></i></button>
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="row-reverse" title="Fila inversa ←"><i class="fa-solid fa-arrow-left"></i></button>
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="column-reverse" title="Columna inversa ↑"><i class="fa-solid fa-arrow-up"></i></button>
                        </div>
                    </div>
                    <div class="ve-field">
                        <label>Alineación horizontal</label>
                        <select class="form-select form-select ve-css-prop" data-prop="justify-content">
                            <option value="">Normal</option>
                            <option value="flex-start">Inicio</option>
                            <option value="center">Centro</option>
                            <option value="flex-end">Final</option>
                            <option value="space-between">Space between</option>
                            <option value="space-around">Space around</option>
                            <option value="space-evenly">Space evenly</option>
                        </select>
                    </div>
                    <div class="ve-field">
                        <label>Alineación vertical</label>
                        <select class="form-select form-select ve-css-prop" data-prop="align-items">
                            <option value="">Normal</option>
                            <option value="stretch">Stretch</option>
                            <option value="flex-start">Inicio</option>
                            <option value="center">Centro</option>
                            <option value="flex-end">Final</option>
                            <option value="baseline">Baseline</option>
                        </select>
                    </div>
                    <div class="ve-field">
                        <label>Wrap</label>
                        <select class="form-select form-select ve-css-prop" data-prop="flex-wrap">
                            <option value="">nowrap</option>
                            <option value="wrap">wrap</option>
                            <option value="wrap-reverse">wrap-reverse</option>
                        </select>
                    </div>
                    <div class="ve-field">
                        <label>Gap</label>
                        <div class="input-group">
                            <input type="number" class="form-control ve-css-prop" data-prop="gap" data-unit="px" placeholder="0" min="0">
                            <span class="input-group-text ve-unit-text ve-unit-toggle" data-units="px,rem,em,%">px</span>
                        </div>
                    </div>
                </div>

                {{-- Grid sub-options --}}
                <div id="ve-layout-grid-controls" class="ve-hidden">
                    <div class="ve-field">
                        <label>Columnas</label>
                        <input type="text" class="form-control ve-css-prop" data-prop="grid-template-columns" data-unit="" placeholder="repeat(3, 1fr)">
                    </div>
                    <div class="ve-field">
                        <label>Gap</label>
                        <div class="input-group">
                            <input type="number" class="form-control ve-css-prop" data-prop="gap" data-unit="px" placeholder="0" min="0">
                            <span class="input-group-text ve-unit-text ve-unit-toggle" data-units="px,rem,em,%">px</span>
                        </div>
                    </div>
                </div>

                {{-- Position --}}
                <div class="ve-field mt-1">
                    <label>Posición</label>
                    <select class="form-select form-select ve-css-prop" data-prop="position" id="ve-layout-position-select">
                        <option value="">Normal (static)</option>
                        <option value="relative">Relativa</option>
                        <option value="absolute">Absoluta</option>
                        <option value="sticky">Sticky</option>
                        <option value="fixed">Fija</option>
                    </select>
                </div>

                {{-- Position coords --}}
                <div id="ve-layout-position-coords" class="ve-hidden ve-field">
                    <div class="ve-grid-2-gap6">
                        <div>
                            <label>Top</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-css-prop" data-prop="top" data-unit="px" placeholder="auto">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div>
                            <label>Right</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-css-prop" data-prop="right" data-unit="px" placeholder="auto">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div>
                            <label>Bottom</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-css-prop" data-prop="bottom" data-unit="px" placeholder="auto">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div>
                            <label>Left</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-css-prop" data-prop="left" data-unit="px" placeholder="auto">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Z-index --}}
                <div class="ve-field">
                    <label>Z-index</label>
                    <input type="number" class="form-control ve-css-prop" data-prop="z-index" data-unit="" placeholder="auto" step="1">
                </div>

                {{-- Overflow --}}
                <div class="ve-field">
                    <label>Overflow</label>
                    <select class="form-select form-select ve-css-prop" data-prop="overflow">
                        <option value="">Auto</option>
                        <option value="hidden">hidden</option>
                        <option value="scroll">scroll</option>
                        <option value="visible">visible</option>
                        <option value="auto">auto</option>
                    </select>
                </div>

            </div>
        </div>

        {{-- Background --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-bg">
                <span>Fondo</span>
                <div class="ve-section-header-actions">
                    <button type="button" class="ve-reset-section-btn" data-section="bg"
                            title="Restablecer fondo"
                            onclick="event.stopPropagation()"><i class="fa-solid fa-undo-alt"></i></button>
                    <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-bg" class="ve-hidden ve-sect-body">
                <div class="ve-field">
                    <label>Color de fondo</label>
                    <div class="ve-color-row">
                        <input type="color" class="form-control form-control-color ve-css-prop ve-color-picker-input"
                               data-prop="background-color" value="#ffffff">
                        <input type="text" class="form-control ve-color-text ve-monospace"
                               placeholder="transparent">
                    </div>
                </div>
                <div class="ve-field">
                    <label>
                        Opacidad <small class="ve-range-value ve-range-dark">1</small>
                    </label>
                    <input type="range" class="form-range ve-css-prop"
                           data-prop="opacity" data-unit=""
                           min="0" max="1" step="0.05" value="1">
                </div>
                <div class="ve-field">
                    <label>Imagen de fondo</label>
                    {{-- Hidden file input kept for JS compatibility --}}
                    <input type="file" id="ve-bg-image-file" accept="image/*" class="ve-hidden">
                    <div class="ve-action-row">
                        <button type="button" class="btn btn-outline-secondary ve-btn-media-picker" id="btn-bg-media-picker">
                            <i class="fa-solid fa-image"></i>
                            <span>Seleccionar imagen</span>
                        </button>
                        <button class="btn btn-outline-secondary ve-btn-square" type="button" id="btn-clear-bg-image" title="Quitar imagen">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    <div id="ve-bg-preview" class="ve-hidden ve-bg-preview">
                        <img id="ve-bg-preview-img" class="ve-bg-preview-img" alt="">
                    </div>
                </div>
                <div>
                    <label>o pegar URL</label>
                    <div class="ve-action-row">
                        <input type="url" class="form-control ve-flex-fill" id="ve-bg-image-url" placeholder="https://...">
                        <button class="btn ve-btn-square ve-btn-primary" type="button" id="btn-apply-bg-url" title="Aplicar URL">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Border --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-border">
                <span>Borde</span>
                <div class="ve-section-header-actions">
                    <button type="button" class="ve-reset-section-btn" data-section="border"
                            title="Restablecer borde"
                            onclick="event.stopPropagation()"><i class="fa-solid fa-undo-alt"></i></button>
                    <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-border" class="ve-hidden ve-sect-body">
                <div class="ve-grid-2 ve-field">
                    <div>
                        <label>Grosor</label>
                        <div class="input-group">
                            <input type="number" class="form-control ve-css-prop" data-prop="border-width" data-unit="px" placeholder="0" min="0">
                            <span class="input-group-text ve-unit-text ve-unit-toggle" data-units="px,rem,em">px</span>
                        </div>
                    </div>
                    <div>
                        <label>Estilo</label>
                        <select class="form-select form-select ve-css-prop" data-prop="border-style">
                            <option value="none">Ninguno</option>
                            <option value="solid">Sólido</option>
                            <option value="dashed">Discontinuo</option>
                            <option value="dotted">Puntos</option>
                            <option value="double">Doble</option>
                        </select>
                    </div>
                </div>
                <div class="ve-field">
                    <label>Color del borde</label>
                    <input type="color" class="form-control form-control-color ve-css-prop ve-color-full"
                           data-prop="border-color" value="#dee2e6">
                </div>
                <div>
                    <label>
                        Esquinas redondeadas <small class="ve-range-value ve-range-dark">0px</small>
                    </label>
                    <input type="range" class="form-range ve-css-prop"
                           data-prop="border-radius" data-unit="px"
                           min="0" max="50" step="1" value="0">
                </div>
            </div>
        </div>

        {{-- Shadow --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-shadow">
                <span>Sombra</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-shadow" class="ve-hidden ve-sect-body">
                <div class="ve-field">
                    <label>Sombra de caja</label>
                    <div class="ve-grid-2 ve-mb-6">
                        <div>
                            <label>Offset X</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-shadow-input" id="ve-shadow-x" placeholder="0">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div>
                            <label>Offset Y</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-shadow-input" id="ve-shadow-y" placeholder="4">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div>
                            <label>Difuminado</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-shadow-input" id="ve-shadow-blur" placeholder="8" min="0">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div>
                            <label>Expansión</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-shadow-input" id="ve-shadow-spread" placeholder="0">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                    </div>
                    <div class="ve-color-row ve-mb-6">
                        <input type="color" class="form-control form-control-color ve-shadow-input ve-color-picker-input" id="ve-shadow-color" value="#000000">
                        <label class="ve-label-inline">Color</label>
                        <div class="form-check ms-auto mb-0">
                            <input class="form-check-input ve-shadow-input" type="checkbox" id="ve-shadow-inset">
                            <label class="form-check-label ve-check-label" for="ve-shadow-inset">Interior</label>
                        </div>
                    </div>
                    <div class="ve-action-row">
                        <button type="button" class="btn ve-btn-primary flex-grow-1" id="btn-apply-shadow">
                            Aplicar
                        </button>
                        <button type="button" class="btn btn-outline-secondary ve-inspector-clear-btn" id="btn-clear-shadow" title="Quitar sombra">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <div class="ve-subsection-divider">
                    <label>Sombra de texto</label>
                    <div class="ve-grid-2 ve-mb-6">
                        <div>
                            <label>Offset X</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="ve-tshadow-x" placeholder="0">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div>
                            <label>Offset Y</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="ve-tshadow-y" placeholder="1">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div>
                            <label>Difuminado</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="ve-tshadow-blur" placeholder="2" min="0">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div class="ve-color-align-end">
                            <input type="color" class="form-control form-control-color ve-color-picker-input" id="ve-tshadow-color" value="#000000">
                        </div>
                    </div>
                    <div class="ve-action-row">
                        <button type="button" class="btn ve-btn-primary flex-grow-1" id="btn-apply-tshadow">
                            Aplicar
                        </button>
                        <button type="button" class="btn btn-outline-secondary ve-inspector-clear-btn" id="btn-clear-tshadow" title="Quitar sombra">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Size --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-size">
                <span>Tamaño</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-size" class="ve-hidden ve-sect-body">
                <div class="ve-grid-2">
                    <div>
                        <label>Ancho</label>
                        <div class="input-group">
                            <input type="text" class="form-control ve-css-prop" data-prop="width" data-unit="" placeholder="auto">
                            <span class="input-group-text ve-unit-text">px/%</span>
                        </div>
                    </div>
                    <div>
                        <label>Alto</label>
                        <div class="input-group">
                            <input type="text" class="form-control ve-css-prop" data-prop="height" data-unit="" placeholder="auto">
                            <span class="input-group-text ve-unit-text">px/%</span>
                        </div>
                    </div>
                </div>
                <div class="ve-field mt-2">
                    <label>Aspect ratio</label>
                    <div class="btn-group btn-group-sm w-100">
                        <button type="button" class="btn btn-outline-secondary ve-aspect-btn" data-ratio="auto">Auto</button>
                        <button type="button" class="btn btn-outline-secondary ve-aspect-btn" data-ratio="1/1" title="Cuadrado">1:1</button>
                        <button type="button" class="btn btn-outline-secondary ve-aspect-btn" data-ratio="4/3">4:3</button>
                        <button type="button" class="btn btn-outline-secondary ve-aspect-btn" data-ratio="16/9" title="Video">16:9</button>
                        <button type="button" class="btn btn-outline-secondary ve-aspect-btn" data-ratio="21/9" title="Ultrawide">21:9</button>
                    </div>
                </div>
                <div class="ve-field">
                    <label>Cursor</label>
                    <select class="form-select form-select-sm ve-css-prop" data-prop="cursor">
                        <option value="">Predeterminado</option>
                        <option value="pointer">pointer (mano)</option>
                        <option value="default">default (flecha)</option>
                        <option value="text">text (texto)</option>
                        <option value="move">move</option>
                        <option value="grab">grab</option>
                        <option value="grabbing">grabbing</option>
                        <option value="crosshair">crosshair</option>
                        <option value="not-allowed">not-allowed</option>
                        <option value="wait">wait</option>
                        <option value="help">help</option>
                        <option value="zoom-in">zoom-in</option>
                        <option value="zoom-out">zoom-out</option>
                        <option value="none">none (oculto)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Position --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-position">
                <span>Posición</span>
                <div class="ve-section-header-actions">
                    <button type="button" class="ve-reset-section-btn" data-section="position"
                            title="Restablecer posición"
                            onclick="event.stopPropagation()"><i class="fa-solid fa-undo-alt"></i></button>
                    <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-position" class="ve-hidden ve-sect-body">
                <div class="ve-field">
                    <label>Posición</label>
                    <select class="form-select form-select ve-css-prop" data-prop="position" id="ve-position-select">
                        <option value="">Normal (static)</option>
                        <option value="relative">Relativa</option>
                        <option value="absolute">Absoluta</option>
                        <option value="fixed">Fija</option>
                        <option value="sticky">Sticky</option>
                    </select>
                </div>
                <div id="ve-position-coords" class="ve-hidden ve-field">
                    <div class="ve-grid-2-gap6">
                        <div>
                            <label>Top</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-css-prop" data-prop="top" data-unit="px" placeholder="auto">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div>
                            <label>Right</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-css-prop" data-prop="right" data-unit="px" placeholder="auto">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div>
                            <label>Bottom</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-css-prop" data-prop="bottom" data-unit="px" placeholder="auto">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                        <div>
                            <label>Left</label>
                            <div class="input-group">
                                <input type="number" class="form-control ve-css-prop" data-prop="left" data-unit="px" placeholder="auto">
                                <span class="input-group-text ve-unit-text">px</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <label>Z-index</label>
                    <input type="number" class="form-control ve-css-prop" data-prop="z-index" data-unit="" placeholder="auto" step="1">
                </div>
            </div>
        </div>

        {{-- Flexbox / Grid --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-flex">
                <span>Display / Flex</span>
                <div class="ve-section-header-actions">
                    <button type="button" class="ve-reset-section-btn" data-section="flex"
                            title="Restablecer flex"
                            onclick="event.stopPropagation()"><i class="fa-solid fa-undo-alt"></i></button>
                    <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-flex" class="ve-hidden ve-sect-body">
                <div class="ve-field">
                    <label>Display</label>
                    <select class="form-select form-select ve-css-prop" data-prop="display" id="ve-display-select">
                        <option value="">Predeterminado</option>
                        <option value="block">block</option>
                        <option value="flex">flex</option>
                        <option value="inline-flex">inline-flex</option>
                        <option value="grid">grid</option>
                        <option value="inline-grid">inline-grid</option>
                        <option value="inline">inline</option>
                        <option value="inline-block">inline-block</option>
                        <option value="none">none (ocultar)</option>
                    </select>
                </div>
                <div id="ve-flex-controls" class="ve-hidden">
                    <div class="ve-field">
                        <label>Dirección</label>
                        <div class="btn-group btn-group-sm w-100">
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="row" title="Fila →"><i class="fa-solid fa-arrow-right"></i></button>
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="column" title="Columna ↓"><i class="fa-solid fa-arrow-down"></i></button>
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="row-reverse" title="Fila inversa ←"><i class="fa-solid fa-arrow-left"></i></button>
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="column-reverse" title="Columna inversa ↑"><i class="fa-solid fa-arrow-up"></i></button>
                        </div>
                    </div>
                    <div class="ve-field">
                        <label>Alineación horizontal</label>
                        <select class="form-select form-select ve-css-prop" data-prop="justify-content">
                            <option value="">Normal</option>
                            <option value="flex-start">Inicio</option>
                            <option value="center">Centro</option>
                            <option value="flex-end">Final</option>
                            <option value="space-between">Space between</option>
                            <option value="space-around">Space around</option>
                            <option value="space-evenly">Space evenly</option>
                        </select>
                    </div>
                    <div class="ve-field">
                        <label>Alineación vertical</label>
                        <select class="form-select form-select ve-css-prop" data-prop="align-items">
                            <option value="">Normal</option>
                            <option value="stretch">Stretch</option>
                            <option value="flex-start">Inicio</option>
                            <option value="center">Centro</option>
                            <option value="flex-end">Final</option>
                            <option value="baseline">Baseline</option>
                        </select>
                    </div>
                    <div class="ve-field">
                        <label>Wrap</label>
                        <select class="form-select form-select ve-css-prop" data-prop="flex-wrap">
                            <option value="">nowrap</option>
                            <option value="wrap">wrap</option>
                            <option value="wrap-reverse">wrap-reverse</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label>Gap</label>
                    <div class="input-group">
                        <input type="number" class="form-control ve-css-prop" data-prop="gap" data-unit="px" placeholder="0" min="0">
                        <span class="input-group-text ve-unit-text ve-unit-toggle" data-units="px,rem,em,%">px</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Transform --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-transform">
                <span>Transform</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-transform" class="ve-hidden ve-sect-body">
                <div class="ve-field">
                    <label>
                        Rotar <small id="ve-rotate-val" class="ve-range-dark">0°</small>
                    </label>
                    <input type="range" id="ve-transform-rotate" min="-180" max="180" step="1" value="0" class="form-range">
                </div>
                <div class="ve-field">
                    <label>
                        Escala <small id="ve-scale-val" class="ve-range-dark">1×</small>
                    </label>
                    <input type="range" id="ve-transform-scale" min="0.1" max="3" step="0.05" value="1" class="form-range">
                </div>
                <div class="ve-grid-2-gap6 ve-field">
                    <div>
                        <label>Translate X</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="ve-transform-tx" placeholder="0">
                            <span class="input-group-text ve-unit-text">px</span>
                        </div>
                    </div>
                    <div>
                        <label>Translate Y</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="ve-transform-ty" placeholder="0">
                            <span class="input-group-text ve-unit-text">px</span>
                        </div>
                    </div>
                </div>
                <div class="ve-action-row">
                    <button type="button" class="btn ve-btn-primary flex-grow-1" id="btn-apply-transform">
                        Aplicar
                    </button>
                    <button type="button" class="btn btn-outline-secondary ve-inspector-clear-btn" id="btn-clear-transform" title="Quitar transform">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Gradient builder --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-gradient">
                <span>Gradiente</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-gradient" class="ve-hidden ve-sect-body">
                <div class="ve-gradient-preview" id="ve-gradient-preview"></div>
                <div class="ve-field">
                    <label>Dirección</label>
                    <select class="form-select" id="ve-gradient-direction">
                        <option value="to bottom">Arriba → Abajo</option>
                        <option value="to right">Izquierda → Derecha</option>
                        <option value="to bottom right">Diagonal ↘</option>
                        <option value="to top right">Diagonal ↗</option>
                        <option value="135deg">135°</option>
                        <option value="45deg">45°</option>
                    </select>
                </div>
                <div class="ve-gradient-row">
                    <input type="color" class="ve-color-picker" id="ve-gradient-color1" value="#b10100">
                    <label>Color 1</label>
                </div>
                <div class="ve-gradient-row">
                    <input type="color" class="ve-color-picker" id="ve-gradient-color2" value="#333333">
                    <label>Color 2</label>
                </div>
                <div class="d-flex gap-2 mt-1">
                    <button type="button" class="btn ve-btn-primary flex-grow-1" id="btn-apply-gradient">Aplicar gradiente</button>
                    <button type="button" class="btn btn-outline-secondary ve-inspector-clear-btn" id="btn-clear-gradient" title="Quitar gradiente"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </div>

        {{-- Motion Effects --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-motion">
                <span>Animaciones</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-motion" class="ve-hidden ve-sect-body">
                <div class="ve-field">
                    <label>Efecto de entrada</label>
                    <select class="form-select ve-motion-select" id="ve-motion-effect">
                        <option value="">Ninguno</option>
                        <option value="fade-in">Fade In</option>
                        <option value="fade-in-up">Fade In Up</option>
                        <option value="fade-in-down">Fade In Down</option>
                        <option value="fade-in-left">Fade In Left</option>
                        <option value="fade-in-right">Fade In Right</option>
                        <option value="zoom-in">Zoom In</option>
                        <option value="slide-up">Slide Up</option>
                        <option value="slide-down">Slide Down</option>
                    </select>
                </div>
                <div class="ve-field">
                    <label>Duración</label>
                    <select class="form-select" id="ve-motion-duration">
                        <option value="0.3s">Rápida (0.3s)</option>
                        <option value="0.6s" selected>Normal (0.6s)</option>
                        <option value="1s">Lenta (1s)</option>
                        <option value="1.5s">Muy lenta (1.5s)</option>
                    </select>
                </div>
                <div class="ve-field">
                    <label>Retraso</label>
                    <select class="form-select" id="ve-motion-delay">
                        <option value="0s" selected>Sin retraso</option>
                        <option value="0.1s">0.1s</option>
                        <option value="0.2s">0.2s</option>
                        <option value="0.3s">0.3s</option>
                        <option value="0.5s">0.5s</option>
                        <option value="1s">1s</option>
                    </select>
                </div>
                <div class="d-flex gap-2 mt-1">
                    <button type="button" class="btn ve-btn-primary flex-grow-1" id="btn-apply-motion">Aplicar animación</button>
                    <button type="button" class="btn btn-outline-secondary ve-inspector-clear-btn" id="btn-remove-motion" title="Quitar animación"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </div>

        {{-- Custom CSS --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-css">
                <span>CSS personalizado</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-css" class="ve-hidden ve-sect-body">
                <textarea class="form-control ve-monospace ve-btn-text" id="ve-custom-css" rows="5"
                          placeholder="color: red;&#10;margin-top: 20px;"></textarea>
                <button type="button" class="btn ve-btn-primary mt-2 w-100" id="btn-apply-custom-css">
                    Aplicar CSS
                </button>
            </div>
        </div>

        {{-- CSS Filters --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-filters">
                <span>Filtros CSS</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-filters" class="ve-hidden ve-sect-body">
                <div class="ve-field">
                    <label>Blur <small class="ve-range-value ve-range-dark" id="flt-blur-val">0px</small></label>
                    <input type="range" class="form-range ve-filter-range" id="flt-blur" data-filter="blur" data-unit="px" min="0" max="20" value="0" step="0.5">
                </div>
                <div class="ve-field">
                    <label>Brillo <small class="ve-range-value ve-range-dark" id="flt-brightness-val">100%</small></label>
                    <input type="range" class="form-range ve-filter-range" id="flt-brightness" data-filter="brightness" data-unit="%" min="0" max="200" value="100" step="5">
                </div>
                <div class="ve-field">
                    <label>Contraste <small class="ve-range-value ve-range-dark" id="flt-contrast-val">100%</small></label>
                    <input type="range" class="form-range ve-filter-range" id="flt-contrast" data-filter="contrast" data-unit="%" min="0" max="200" value="100" step="5">
                </div>
                <div class="ve-field">
                    <label>Saturación <small class="ve-range-value ve-range-dark" id="flt-saturate-val">100%</small></label>
                    <input type="range" class="form-range ve-filter-range" id="flt-saturate" data-filter="saturate" data-unit="%" min="0" max="300" value="100" step="5">
                </div>
                <div class="ve-field">
                    <label>Escala de grises <small class="ve-range-value ve-range-dark" id="flt-grayscale-val">0%</small></label>
                    <input type="range" class="form-range ve-filter-range" id="flt-grayscale" data-filter="grayscale" data-unit="%" min="0" max="100" value="0" step="5">
                </div>
                <div class="ve-field">
                    <label>Tono (hue) <small class="ve-range-value ve-range-dark" id="flt-hue-rotate-val">0deg</small></label>
                    <input type="range" class="form-range ve-filter-range" id="flt-hue-rotate" data-filter="hue-rotate" data-unit="deg" min="0" max="360" value="0" step="5">
                </div>
                <div class="ve-field">
                    <label>Opacidad <small class="ve-range-value ve-range-dark" id="flt-opacity-val">100%</small></label>
                    <input type="range" class="form-range ve-filter-range" id="flt-opacity" data-filter="opacity" data-unit="%" min="0" max="100" value="100" step="5">
                </div>
                <div class="d-flex gap-2 mt-1">
                    <button type="button" class="btn ve-btn-primary btn-sm flex-grow-1" id="btn-apply-filters">Aplicar filtros</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ve-inspector-clear-btn" id="btn-reset-filters" title="Restablecer filtros"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </div>

        {{-- CSS Transitions --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-transitions">
                <span>Transición</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-transitions" class="ve-hidden ve-sect-body">
                <div class="ve-field">
                    <label>Propiedad</label>
                    <select class="form-select form-select-sm" id="tr-property">
                        <option value="all">Todas (all)</option>
                        <option value="opacity">Opacidad</option>
                        <option value="transform">Transform</option>
                        <option value="color">Color</option>
                        <option value="background-color">Fondo</option>
                        <option value="border-color">Borde</option>
                        <option value="box-shadow">Sombra</option>
                        <option value="width">Ancho</option>
                        <option value="height">Alto</option>
                        <option value="margin">Margen</option>
                        <option value="padding">Padding</option>
                        <option value="font-size">Fuente</option>
                        <option value="filter">Filtro</option>
                    </select>
                </div>
                <div class="ve-grid-2 ve-field">
                    <div>
                        <label>Duración <span id="tr-dur-val" class="ve-range-value ve-range-dark">300ms</span></label>
                        <input type="range" class="form-range" id="tr-duration" min="0" max="2000" value="300" step="50">
                    </div>
                    <div>
                        <label>Delay <span id="tr-delay-val" class="ve-range-value ve-range-dark">0ms</span></label>
                        <input type="range" class="form-range" id="tr-delay" min="0" max="1000" value="0" step="50">
                    </div>
                </div>
                <div class="ve-field">
                    <label>Curva</label>
                    <select class="form-select form-select-sm" id="tr-easing">
                        <option value="ease">ease</option>
                        <option value="ease-in">ease-in</option>
                        <option value="ease-out">ease-out</option>
                        <option value="ease-in-out">ease-in-out</option>
                        <option value="linear">linear</option>
                        <option value="cubic-bezier(0.34,1.56,0.64,1)">Spring (bounce)</option>
                    </select>
                </div>
                <div class="d-flex gap-2 mt-1">
                    <button type="button" class="btn ve-btn-primary btn-sm flex-grow-1" id="btn-apply-transition">Aplicar</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ve-inspector-clear-btn" id="btn-clear-transition" title="Quitar transición"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </div>

        {{-- Advanced / Extra --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-extra">
                <span>Avanzado</span>
                <i class="fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-extra" class="ve-hidden ve-sect-body">
                <div class="ve-field">
                    <label>Mix blend mode</label>
                    <select class="form-select form-select-sm ve-css-prop" data-prop="mix-blend-mode">
                        <option value="normal">Normal</option>
                        <option value="multiply">Multiply</option>
                        <option value="screen">Screen</option>
                        <option value="overlay">Overlay</option>
                        <option value="darken">Darken</option>
                        <option value="lighten">Lighten</option>
                        <option value="color-dodge">Color dodge</option>
                        <option value="color-burn">Color burn</option>
                        <option value="hard-light">Hard light</option>
                        <option value="soft-light">Soft light</option>
                        <option value="difference">Difference</option>
                        <option value="exclusion">Exclusion</option>
                        <option value="hue">Hue</option>
                        <option value="saturation">Saturation</option>
                        <option value="color">Color</option>
                        <option value="luminosity">Luminosity</option>
                    </select>
                </div>
                <div class="ve-field">
                    <label>Will-change</label>
                    <select class="form-select form-select-sm ve-css-prop" data-prop="will-change">
                        <option value="auto">auto</option>
                        <option value="transform">transform</option>
                        <option value="opacity">opacity</option>
                        <option value="scroll-position">scroll-position</option>
                        <option value="contents">contents</option>
                    </select>
                </div>
                <div class="ve-field">
                    <label>Pointer events</label>
                    <select class="form-select form-select-sm ve-css-prop" data-prop="pointer-events">
                        <option value="">Predeterminado</option>
                        <option value="none">none (sin interacción)</option>
                        <option value="auto">auto</option>
                        <option value="all">all</option>
                    </select>
                </div>
                <div class="ve-field">
                    <label>User select</label>
                    <select class="form-select form-select-sm ve-css-prop" data-prop="user-select">
                        <option value="">Predeterminado</option>
                        <option value="none">none (no seleccionable)</option>
                        <option value="text">text</option>
                        <option value="all">all</option>
                    </select>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* ── Panel header (shared) ──────────────────────────────── */
.ve-panel-header {
    padding: 10px 12px;
    border-bottom: 1px solid var(--ve-border, #eee);
    flex-shrink: 0;
}

.ve-panel-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}

.ve-label-tag {
    font-size: 10px;
    font-weight: 600;
    color: var(--ve-text-muted, #999);
    text-transform: uppercase;
    letter-spacing: .5px;
}

.ve-element-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--ve-text, #333);
}

.ve-btn-row-gap4 {
    display: flex;
    gap: 4px;
}

.ve-btn-icon {
    font-size: 11px;
    padding: 2px 8px;
}

.ve-breadcrumb {
    background: var(--ve-bg, #fff);
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 11px;
    overflow-x: auto;
    white-space: nowrap;
    margin-top: 4px;
}

/* ── Empty state ─────────────────────────────────────────── */
.ve-empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #aaa;
    text-align: center;
    padding: 24px;
}

.ve-empty-text {
    font-size: 12px;
    line-height: 1.5;
}

/* ── Sections wrapper ────────────────────────────────────── */
.ve-sections-wrap {
    flex: 1;
    overflow-y: auto;
}

/* ── Section headers ─────────────────────────────────────── */
.ve-section-header {
    cursor: pointer;
}
.ve-section-header:hover { background: #f0f0f5 !important; }
.ve-section-header > span {
    font-size: 12px;
    color: #444;
    font-weight: 600;
}

.ve-section-header-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ── Section body ────────────────────────────────────────── */
.ve-sect-body {
    padding: 10px 12px;
}

/* ── Fields & labels ─────────────────────────────────────── */
#ve-inspector-sections label {
    font-size: 11px !important;
    color: #555 !important;
}

.ve-field {
    margin-bottom: 8px;
}

.ve-label-inline {
    font-size: 10px;
    color: var(--ve-text-soft, #888);
    margin: 0;
}

.ve-check-label {
    font-size: 11px;
    color: #555;
}

/* ── Reset section button ────────────────────────────────── */
.ve-reset-section-btn {
    background: transparent;
    border: none;
    color: var(--ve-text-faint, #bbb);
    padding: 1px 5px;
    font-size: 10px;
    border-radius: 3px;
    cursor: pointer;
    line-height: 1.6;
}

/* ── Utility: hidden ─────────────────────────────────────── */
.ve-hidden { display: none; }

/* ── Visibility section ──────────────────────────────────── */
.ve-hint-text {
    font-size: 11px;
    color: var(--ve-text-soft, #666);
    margin-bottom: 8px;
}

.ve-flex-wrap-gap {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.mb-10px { margin-bottom: 10px; }

.ve-border-top-pad {
    border-top: 1px solid #f0f0f0;
    padding-top: 8px;
}

/* ── Primary action button ───────────────────────────────── */
.ve-btn-primary {
    background: var(--ve-primary, #b10100);
    border-color: var(--ve-primary, #b10100);
    color: #fff;
    font-size: 12px;
}
.ve-btn-primary:hover {
    background: #8e0000;
    border-color: #8e0000;
    color: #fff;
}

/* ── Small text buttons ──────────────────────────────────── */
.ve-btn-text {
    font-size: 12px;
}

/* ── Grids ───────────────────────────────────────────────── */
.ve-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.ve-grid-2-gap6 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
}

/* ── Color inputs ────────────────────────────────────────── */
.ve-color-row {
    display: flex;
    gap: 6px;
}

.ve-color-picker-input {
    width: 44px;
    padding: 2px;
}

.ve-color-full {
    width: 100%;
}

.ve-monospace {
    font-family: monospace;
    font-size: 11px;
}

/* ── Range value display ─────────────────────────────────── */
.ve-range-dark { color: #1a1a1a; }

/* ── Unit text in input-groups ───────────────────────────── */
.ve-unit-text { font-size: 10px; }

/* ── Spacing box-model grid ──────────────────────────────── */
.ve-box-model {
    display: grid;
    grid-template-columns: 1fr 56px 1fr;
    grid-template-rows: auto 56px auto;
    gap: 4px;
    max-width: 176px;
    margin: 0 auto;
}

.ve-box-model-mb { margin-bottom: 12px; }

.ve-box-center {
    background: #e9ecef;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #aaa;
}

.ve-box-input { font-size: 11px; }

/* ── Spacing section titles ──────────────────────────────── */
.ve-spacing-title {
    font-size: 10px;
    font-weight: 600;
    color: #888;
    text-align: center;
    margin-bottom: 8px;
    text-transform: uppercase;
}

/* ── Background section ──────────────────────────────────── */
.ve-action-row {
    display: flex;
    gap: 6px;
    align-items: center;
}

.ve-btn-media-picker {
    flex: 1;
    font-size: 12px;
    padding: 7px 12px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.ve-btn-square {
    width: 34px;
    height: 34px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
}

.ve-flex-fill { flex: 1; }

.ve-bg-preview {
    margin-top: 6px;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid var(--ve-border, #eee);
}

.ve-bg-preview-img {
    width: 100%;
    height: 60px;
    object-fit: cover;
}

/* ── Shadow section ──────────────────────────────────────── */
.ve-mb-6 { margin-bottom: 6px; }

.ve-subsection-divider {
    border-top: 1px solid #f0f0f0;
    padding-top: 10px;
    margin-top: 4px;
}

.ve-color-align-end {
    display: flex;
    align-items: flex-end;
}

/* ── Overline decoration ─────────────────────────────────── */
.ve-overline { text-decoration: overline; }

/* ── Brand color palette (shortcode color pickers) ──────────── */
.ve-brand-palette { display:flex; gap:4px; margin-top:4px; flex-wrap:wrap; }
.ve-brand-swatch  { width:18px; height:18px; border-radius:3px; cursor:pointer; border:1px solid rgba(0,0,0,.15); flex-shrink:0; }
.ve-brand-swatch:hover { transform:scale(1.2); }

/* ── Shortcode editor ────────────────────────────────────── */
.ve-sc-tabs { display:flex; gap:4px; }
.ve-sc-tab {
    flex:1; padding:4px 0; font-size:11px; font-weight:600;
    background:#f5f5f5; border:1px solid #ddd; border-radius:4px;
    cursor:pointer; transition:all .15s;
}
.ve-sc-tab:hover { background:#ebebeb; }
.ve-sc-tab.active { background:#90bb13; color:#fff; border-color:#90bb13; }
.ve-sc-item-card { border:1px solid #e8e8e8; border-radius:6px; margin-bottom:6px; overflow:hidden; }
.ve-sc-item-card.ve-sc-item-collapsed .ve-sc-item-body { display:none; }
.ve-sc-item-header {
    display:flex; align-items:center; gap:5px;
    padding:5px 8px; background:#f8f8f8; font-size:11px; font-weight:600; color:#444;
}
.ve-sc-drag-handle { cursor:grab; color:#bbb; font-size:11px; flex-shrink:0; }
.ve-sc-drag-handle:active { cursor:grabbing; }
.ve-sc-item-toggle { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; cursor:pointer; }
.ve-sc-item-toggle:hover { color:#90bb13; }
.ve-sc-item-label { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ve-sc-item-delete { font-size:11px; opacity:.6; flex-shrink:0; }
.ve-sc-item-delete:hover { opacity:1; }
.ve-sc-item-body { padding:6px 8px; }
.ve-sc-item-body .ve-field { margin-bottom:6px; }
.ve-sc-sortable-placeholder { height:36px; border:2px dashed #90bb13; border-radius:6px; margin-bottom:6px; background:#f0f8e0; }
#ve-sc-items-list.ui-sortable .ve-sc-drag-handle { color:#ccc; }
#ve-sc-items-list.ui-sortable .ve-sc-drag-handle:hover { color:#888; }
/* Footer: apply full-width + grouped secondary buttons */
.ve-sc-footer { display:flex; flex-direction:column; gap:6px; }
.ve-sc-apply-kbd { font-size:9px; opacity:0.7; margin-left:4px; background:rgba(255,255,255,0.2); border:none; padding:1px 3px; border-radius:2px; }
.ve-sc-action-groups { display:flex; gap:0; justify-content:space-between; align-items:center; min-height:28px; }
.ve-sc-action-group { display:flex; gap:2px; }
.ve-sc-action-group + .ve-sc-action-group { border-left:1px solid #e5e5e5; padding-left:6px; }
.ve-sc-icon-btn { padding:3px 7px; font-size:12px; line-height:1; }
.ve-sc-icon-btn:not(.ve-hidden) ~ .ve-sc-action-group { border-left-color:#e5e5e5; }
/* Hide group wrapper border when ALL buttons in the group are hidden */
#ve-sc-group-history:not(:has(:not(.ve-hidden))),
#ve-sc-group-block:not(:has(:not(.ve-hidden))),
#ve-sc-group-io:not(:has(:not(.ve-hidden))) { display:none; }
/* Items toolbar */
.ve-sc-items-toolbar { display:flex; gap:4px; margin-bottom:6px; }
.ve-sc-toolbar-btn {
    flex:1; padding:3px 0; font-size:10px; font-weight:600;
    background:#f5f5f5; border:1px solid #ddd; border-radius:4px;
    cursor:pointer; color:#666; transition:all .15s;
}
.ve-sc-toolbar-btn:hover { background:#ebebeb; color:#333; }
/* Duplicate button */
.ve-sc-item-dupe { font-size:11px; opacity:.5; flex-shrink:0; }
.ve-sc-item-dupe:hover { opacity:1; color:#90bb13 !important; }
/* Image preview */
.ve-sc-image-wrap { display:flex; flex-direction:column; gap:4px; }
.ve-sc-img-preview { min-height:0; }
.ve-sc-img-thumb { max-width:100%; max-height:80px; border-radius:4px; border:1px solid #eee; object-fit:cover; display:block; }

/* ── Improvement 3: Required field error ────────────────────────── */
.ve-field-required-error label { color: #b10100 !important; }
.ve-field-required-error input,
.ve-field-required-error textarea { border-color: #b10100 !important; }

/* ── Improvement 13: Bootstrap quick classes chips ──────────────── */
.ve-bs-chip { display:inline-block; padding:1px 7px; font-size:10px; background:#f0f0f0; border-radius:10px; cursor:pointer; border:1px solid #ddd; user-select:none; transition:all .15s; }
.ve-bs-chip:hover { background:#e0e0e0; }
.ve-bs-chip.active { background:#90bb13; color:#fff; border-color:#90bb13; }
.ve-bs-cat-label { font-size:9px; color:#999; text-transform:uppercase; letter-spacing:.5px; margin:4px 0 2px; }

/* ── Improvement 14: Unit toggle ────────────────────────────────── */
.ve-unit-toggle { cursor:pointer; user-select:none; }
.ve-unit-toggle:hover { background:#e9ecef; color:#90bb13; }

/* ── Improvement A: Attrs search ───────────────────────────────── */
.ve-sc-attrs-search-wrap { position:sticky; top:0; background:#fff; z-index:1; padding-bottom:4px; }

/* ── Improvement D: Char counter ───────────────────────────────── */
.ve-char-count { font-size:10px; color:#aaa; text-align:right; margin-top:2px; }

/* ── Improvement E: Items table view ───────────────────────────── */
.ve-items-table { font-size:11px; }
.ve-items-table th { font-size:10px; color:#999; padding:2px 6px; }
.ve-items-table td { padding:2px 6px; }
.ve-items-table td input.form-control { font-size:11px; padding:2px 4px; height:auto; }

/* ── Improvement F: Preset chips ───────────────────────────────── */
.ve-sc-preset-chip { display:inline-block; padding:2px 8px; font-size:10px; background:#f0f0f0; border-radius:10px; cursor:pointer; border:1px solid #ddd; margin:2px; }
.ve-sc-preset-chip:hover { background:#e0e0e0; }
.ve-sc-preset-delete { margin-left:4px; color:#aaa; cursor:pointer; }
.ve-sc-preset-delete:hover { color:#b10100; }
</style>

<script>
(function ($) {
    'use strict';

    /* ── Collapsible sections ───────────────────────────────────────── */
    $(document).on('click', '.ve-section-header', function () {
        const targetId = $(this).data('target');
        const $target  = $('#' + targetId);

        if (!$(this).hasClass('collapsed')) {
            $target.slideUp(150, function () { $target.addClass('ve-hidden'); });
            $(this).addClass('collapsed');
        } else {
            $target.removeClass('ve-hidden').hide().slideDown(150);
            $(this).removeClass('collapsed');
        }
    });

    /* ── Selection ──────────────────────────────────────────────────── */
    window.veInspector = { select: selectElement, deselect: deselect };

    window.addEventListener('message', function (e) {
        if (!e.data) return;
        if (e.data.type === 've-element-selected') {
            window._veCurrentNodeId = e.data.nodeId;
            window._veLastSelectedStyles = e.data.styles || {};
            showElementInfo(e.data.tag, e.data.styles, e.data);
            if (e.data.isLink) {
                $('#ve-section-link-wrapper').removeClass('ve-hidden');
                $('#ve-link-href-inspector').val(e.data.href || '');
                $('#ve-link-target-inspector').val(e.data.linkTarget || '');
            } else {
                $('#ve-section-link-wrapper').addClass('ve-hidden');
            }
        }
    });

    /* ── Copy / Paste styles (Feature 4) ────────────────────────────── */
    $('#btn-copy-styles').on('click', function () {
        var styles = window._veLastSelectedStyles;
        if (!styles || !Object.keys(styles).length) {
            alert('No hay elemento seleccionado.');
            return;
        }
        var p = window.parent || window;
        p._veCopiedStyles = $.extend({}, styles);
        $('#btn-paste-styles').prop('disabled', false);
        if (p.showToast) p.showToast('<i class="fa-solid fa-copy me-1"></i>Estilos copiados');
    });

    $('#btn-paste-styles').on('click', function () {
        var p = window.parent || window;
        var copied = p._veCopiedStyles;
        if (!copied || !Object.keys(copied).length) return;
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;
        var styleMap = {
            'font-size': 'fontSize', 'font-weight': 'fontWeight', 'font-family': 'fontFamily',
            'color': 'color', 'text-align': 'textAlign', 'line-height': 'lineHeight',
            'padding-top': 'paddingTop', 'padding-right': 'paddingRight',
            'padding-bottom': 'paddingBottom', 'padding-left': 'paddingLeft',
            'margin-top': 'marginTop', 'margin-right': 'marginRight',
            'margin-bottom': 'marginBottom', 'margin-left': 'marginLeft',
            'background-color': 'backgroundColor', 'opacity': 'opacity',
            'border-width': 'borderWidth', 'border-style': 'borderStyle',
            'border-color': 'borderColor', 'border-radius': 'borderRadius'
        };
        Object.entries(copied).forEach(function (entry) {
            var cssProp = entry[0];
            var cssVal  = entry[1];
            if (!cssVal) return;
            var jsProp  = styleMap[cssProp] || cssProp;
            frame.contentWindow.postMessage({
                type:  've-apply-style',
                prop:  jsProp,
                value: cssVal
            }, '*');
        });
        if (p.showToast) p.showToast('<i class="fa-solid fa-paste me-1"></i>Estilos aplicados');
    });

    function selectElement(tag, styles) { showElementInfo(tag, styles || {}, {}); }

    const GOOGLE_FONTS = ['Roboto','Open Sans','Lato','Montserrat','Poppins','Inter','Playfair Display'];

    function veScCheckDirty() {
        if (window._veScDirty && window._veScCurrent) {
            return confirm('El bloque tiene cambios sin aplicar. ¿Continuar de todas formas?');
        }
        return true;
    }

    function showElementInfo(tag, styles, data) {
        if (!veScCheckDirty()) return;
        data = data || {};
        $('#ve-inspector-element-name').text('<' + tag + '>');
        $('#ve-inspector-empty').addClass('ve-hidden');
        $('#ve-inspector-sections').removeClass('ve-hidden');
        $('#ve-section-visibility').removeClass('ve-hidden');
        $('#ve-section-attributes').removeClass('ve-hidden');
        $('#ve-section-layout-css').removeClass('ve-hidden');
        $('#btn-hide-element').removeClass('ve-hidden');
        populateFields(styles);
        syncLayoutSectionUI(styles);
        syncCssBtnGroups(styles);
        // Update visibility toggle state
        const isHidden = styles['display'] === 'none';
        $('#btn-toggle-hidden').toggle(!isHidden);
        $('#btn-toggle-shown').toggle(isHidden);
        // Populate attribute fields
        $('#ve-attr-id').val(data.elId || '');
        $('#ve-attr-class').val(data.elClass || '');
        $('#ve-attr-src').val(data.elSrc || '');
        $('#ve-attr-alt').val(data.elAlt || '');
        $('#ve-attr-href').val(data.elHref || data.href || '');
        $('#ve-attr-placeholder').val(data.elPlaceholder || '');
        // Show/hide tag-specific attribute fields
        const showSrc = ['img','video','audio','iframe','source','embed'].includes(tag);
        const showAlt = ['img'].includes(tag);
        const showHref = ['a'].includes(tag);
        const showPh = ['input','textarea'].includes(tag);
        $('#ve-attr-src-wrap').toggle(showSrc);
        $('#ve-attr-alt-wrap').toggle(showAlt);
        $('#ve-attr-href-wrap').toggle(showHref);
        $('#ve-attr-placeholder-wrap').toggle(showPh);

        // Form field section
        const fieldId    = data.elFormFieldId || null;
        const inputType  = data.elInputType || '';
        const noPlaceholder = ['radio', 'checkbox'].includes(inputType);
        if (fieldId && ['input','textarea','select'].includes(tag)) {
            $('#ve-section-form-field').removeClass('ve-hidden');
            // Auto-expand if user had collapsed the section
            $('#ve-sect-form-field').removeClass('ve-hidden');
            $('.ve-section-header[data-target="ve-sect-form-field"]').removeClass('collapsed');
            $('#ve-ff-label').val(data.elFormFieldLabel || '');
            $('#ve-ff-placeholder').val(data.elPlaceholder || '');
            $('#ve-ff-help-text').val(data.elFormFieldHelp || '');
            $('#ve-ff-required').prop('checked', data.elFormFieldRequired === '1');
            $('#ve-ff-status').text('');
            $('#ve-sect-form-field .ve-ff-error').remove();
            $('#ve-ff-placeholder-wrap').toggle(!noPlaceholder);
            const badgeLabel = inputType || tag;
            $('#ve-ff-type-badge').text(badgeLabel).removeClass('ve-hidden');
            window._veFormFieldId   = fieldId;
            window._veFormFieldType = inputType;
        } else {
            $('#ve-section-form-field').addClass('ve-hidden');
            $('#ve-ff-type-badge').addClass('ve-hidden');
            window._veFormFieldId   = null;
            window._veFormFieldType = null;
        }

        // Shortcode sentinel section
        const veSc       = data.elVeSc || '';
        const veScNodeId = data.elVeScNodeId || '';
        if (veSc && veScNodeId) {
            const scParsed = veParseShortcode(veDecodeVeSc(veSc));
            if (scParsed) {
                $('#ve-sc-context-label').text('Dentro de [' + scParsed.name + ']').removeClass('ve-hidden');
            }
            showScEditor(veSc, veScNodeId);
        } else {
            $('#ve-sc-context-label').addClass('ve-hidden');
            $('#ve-section-shortcode').addClass('ve-hidden');
            window._veScCurrent = null;
            window._veScDirty   = false;
            window._veScHistory = [];
        }
    }

    function deselect() {
        if (!veScCheckDirty()) return;
        $('#ve-inspector-element-name').text('Ninguno');
        $('#ve-inspector-sections').addClass('ve-hidden');
        $('#ve-inspector-empty').removeClass('ve-hidden');
        $('#ve-section-visibility').addClass('ve-hidden');
        $('#ve-section-attributes').addClass('ve-hidden');
        $('#ve-section-layout-css').addClass('ve-hidden');
        $('#ve-section-form-field').addClass('ve-hidden');
        $('#ve-section-shortcode').addClass('ve-hidden');
        $('#ve-sc-context-label').addClass('ve-hidden');
        $('#ve-ff-type-badge').addClass('ve-hidden');
        $('#btn-hide-element').addClass('ve-hidden');
        $('#ve-inspector-breadcrumb').addClass('ve-hidden');
        window._veFormFieldId   = null;
        window._veFormFieldType = null;
        window._veScCurrent     = null;
        window._veScOriginal    = null;
        window._veScDirty       = false;
        window._veScHistory     = [];
        $('#btn-sc-reset').addClass('ve-hidden');
        clearFields();
        $('#ve-layout-flex-controls, #ve-layout-grid-controls, #ve-layout-position-coords').hide();
        $('#ve-layout-flex-controls .ve-css-btn').removeClass('active');
    }

    $('#btn-deselect-element').on('click', function () {
        deselect();
        const frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage({ type: 've-deselect' }, '*');
    });

    /* ── Populate fields ────────────────────────────────────────────── */
    function syncLayoutSectionUI(styles) {
        const display  = styles['display']  || '';
        const position = styles['position'] || '';
        const isFlex   = display === 'flex' || display === 'inline-flex';
        const isGrid   = display === 'grid';
        const hasPos   = position !== '' && position !== 'static';

        $('#ve-layout-flex-controls').toggle(isFlex);
        $('#ve-layout-grid-controls').toggle(isGrid);
        $('#ve-layout-position-coords').toggle(hasPos);

        // Mark active flex-direction button
        const flexDir = styles['flex-direction'] || 'row';
        $('#ve-layout-flex-controls .ve-css-btn[data-prop="flex-direction"]')
            .removeClass('active')
            .filter('[data-value="' + flexDir + '"]').addClass('active');

        // Auto-expand layout section when display or position is non-default
        if (isFlex || isGrid || hasPos) {
            $('#ve-sect-layout').removeClass('ve-hidden');
            $('.ve-section-header[data-target="ve-sect-layout"]').removeClass('collapsed');
        }
    }

    function syncCssBtnGroups(styles) {
        // Sync active state on toggle-button groups (text-align, text-decoration)
        $('.ve-css-btn[data-prop]').each(function () {
            const $btn  = $(this);
            const prop  = $btn.data('prop');
            const val   = $btn.data('value');
            const cur   = (styles[prop] || '').toLowerCase().split(/\s+/)[0];
            $btn.toggleClass('active', cur === String(val).toLowerCase());
        });
    }

    function populateFields(styles) {
        $('.ve-css-prop').each(function () {
            const $input = $(this);
            const prop   = $input.data('prop');
            const unit   = $input.data('unit') || '';
            const raw    = styles[prop] || '';
            const value  = raw.replace(unit, '').trim();

            if ($input.is('select')) {
                $input.val(raw || '');
            } else if ($input.attr('type') === 'color') {
                const hex = cssColorToHex(raw);
                if (hex) $input.val(hex);
                $input.closest('div').find('.ve-color-text').val(raw || '');
            } else if ($input.attr('type') === 'range') {
                $input.val(parseFloat(value) || parseFloat($input.val()));
                $input.closest('div').find('.ve-range-value').text(unit ? value + unit : value);
            } else {
                $input.val(value || '');
            }
        });
    }

    function clearFields() {
        $('.ve-css-prop').each(function () {
            const $input = $(this);
            if ($input.is('select')) $input.val('');
            else if ($input.attr('type') === 'color') $input.val('#000000');
            else $input.val('');
        });
        $('.ve-shadow-input').val('').prop('checked', false);
        $('#ve-custom-css').val('');
    }

    /* ── Apply style to iframe ──────────────────────────────────────── */
    function applyStyle(prop, value) {
        const frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;
        frame.contentWindow.postMessage({ type: 've-apply-style', prop: prop, value: value }, '*');
    }

    /* ── CSS prop inputs ────────────────────────────────────────────── */
    $(document).on('input change', '.ve-css-prop', function () {
        const $input = $(this);
        const prop   = $input.data('prop');
        const unit   = $input.data('unit') || '';

        if ($input.attr('type') === 'range') {
            const val = $input.val();
            $input.closest('div').find('.ve-range-value').text(unit ? val + unit : val);
            applyStyle(prop, val + unit);
            return;
        }
        if ($input.attr('type') === 'color') {
            const hex = $input.val();
            $input.closest('div').find('.ve-color-text').val(hex);
            applyStyle(prop, hex);
            return;
        }
        const raw = ($input.val() || '').trim();
        if (!raw) return;
        const value = unit && !/[a-z%]/.test(raw) ? raw + unit : raw;
        applyStyle(prop, value);

        // Google Fonts: load font in iframe on font-family change
        if (prop === 'font-family') {
            const fontMatch = value.match(/^'([^']+)'/);
            const fontName  = fontMatch ? fontMatch[1] : null;
            if (fontName && GOOGLE_FONTS.includes(fontName)) {
                const frame = document.getElementById('ve-preview-frame');
                if (frame && frame.contentWindow) {
                    frame.contentWindow.postMessage({ type: 've-load-font', fontName: fontName }, '*');
                }
            }
        }
    });

    $(document).on('input', '.ve-color-text', function () {
        const $text  = $(this);
        const hex    = $text.val().trim();
        const $color = $text.closest('div').find('.ve-css-prop[type="color"]');
        const prop   = $color.data('prop');
        if (/^#[0-9a-fA-F]{6}$/.test(hex)) { $color.val(hex); applyStyle(prop, hex); }
    });

    $(document).on('click', '.ve-css-btn', function () {
        const $btn  = $(this);
        const prop  = $btn.data('prop');
        const value = $btn.data('value');
        $btn.closest('.btn-group').find('.ve-css-btn').removeClass('active');
        $btn.addClass('active');
        applyStyle(prop, value);
    });

    /* ── Box shadow ─────────────────────────────────────────────────── */
    function buildBoxShadow() {
        const x      = parseInt($('#ve-shadow-x').val())      || 0;
        const y      = parseInt($('#ve-shadow-y').val())      || 4;
        const blur   = parseInt($('#ve-shadow-blur').val())   || 8;
        const spread = parseInt($('#ve-shadow-spread').val()) || 0;
        const color  = $('#ve-shadow-color').val()            || '#000000';
        const inset  = $('#ve-shadow-inset').is(':checked')   ? 'inset ' : '';
        // Convert hex+alpha color to rgba
        const r = parseInt(color.slice(1,3), 16);
        const g = parseInt(color.slice(3,5), 16);
        const b = parseInt(color.slice(5,7), 16);
        const a = color.length === 9 ? parseInt(color.slice(7,9), 16) / 255 : 0.2;
        return inset + x + 'px ' + y + 'px ' + blur + 'px ' + spread + 'px rgba(' + r + ',' + g + ',' + b + ',' + a.toFixed(2) + ')';
    }

    $('#btn-apply-shadow').on('click', function () { applyStyle('box-shadow', buildBoxShadow()); });
    $('#btn-clear-shadow').on('click', function () { applyStyle('box-shadow', 'none'); });

    /* ── Text shadow ────────────────────────────────────────────────── */
    function buildTextShadow() {
        const x    = parseInt($('#ve-tshadow-x').val())    || 0;
        const y    = parseInt($('#ve-tshadow-y').val())    || 1;
        const blur = parseInt($('#ve-tshadow-blur').val()) || 2;
        const color = $('#ve-tshadow-color').val()         || '#000000';
        const r = parseInt(color.slice(1,3), 16);
        const g = parseInt(color.slice(3,5), 16);
        const b = parseInt(color.slice(5,7), 16);
        const a = color.length === 9 ? parseInt(color.slice(7,9), 16) / 255 : 0.4;
        return x + 'px ' + y + 'px ' + blur + 'px rgba(' + r + ',' + g + ',' + b + ',' + a.toFixed(2) + ')';
    }

    $('#btn-apply-tshadow').on('click', function () { applyStyle('text-shadow', buildTextShadow()); });
    $('#btn-clear-tshadow').on('click', function () { applyStyle('text-shadow', 'none'); });

    /* ── Custom CSS ─────────────────────────────────────────────────── */
    $('#btn-apply-custom-css').on('click', function () {
        const css = $('#ve-custom-css').val().trim();
        if (!css) return;
        const frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;
        frame.contentWindow.postMessage({ type: 've-apply-custom-css', css: css }, '*');
    });

    /* ── Visibility: hide/show element ──────────────────────────────── */
    $('#btn-toggle-hidden, #btn-hide-element').on('click', function () {
        applyStyle('display', 'none');
        $('#btn-toggle-hidden').addClass('ve-hidden');
        $('#btn-toggle-shown').removeClass('ve-hidden');
        $('#btn-hide-element').addClass('ve-hidden');
    });

    $('#btn-toggle-shown').on('click', function () {
        applyStyle('display', '');
        $('#btn-toggle-hidden').removeClass('ve-hidden');
        $('#btn-toggle-shown').addClass('ve-hidden');
        $('#btn-hide-element').removeClass('ve-hidden');
    });

    /* ── Visibility: responsive class toggles ────────────────────────── */
    $(document).on('click', '.ve-visibility-btn', function () {
        const $btn  = $(this);
        const cls   = $btn.data('class');
        const frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;
        $btn.toggleClass('active btn-secondary btn-outline-secondary');
        const add = $btn.hasClass('active');
        frame.contentWindow.postMessage({
            type:   've-toggle-class',
            nodeId: window._veCurrentNodeId,
            cls:    cls,
            add:    add,
        }, '*');
    });

    /* ── Apply HTML attributes ──────────────────────────────────────── */
    $('#btn-apply-attrs').on('click', function () {
        const frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;
        const attrs = [
            { attr: 'id',          val: $('#ve-attr-id').val().trim() },
            { attr: 'class',       val: $('#ve-attr-class').val().trim() },
            { attr: 'src',         val: $('#ve-attr-src').val().trim() },
            { attr: 'alt',         val: $('#ve-attr-alt').val().trim() },
            { attr: 'href',        val: $('#ve-attr-href').val().trim() },
            { attr: 'placeholder', val: $('#ve-attr-placeholder').val().trim() },
        ];
        attrs.forEach(function (a) {
            frame.contentWindow.postMessage({
                type:   've-apply-attribute',
                nodeId: window._veCurrentNodeId,
                attr:   a.attr,
                value:  a.val,
            }, '*');
        });
    });

    /* ── Form field: save to database ──────────────────────────────── */
    $('#btn-save-form-field').on('click', function () {
        const fieldId = window._veFormFieldId;
        if (!fieldId) return;

        const payload  = {};
        const noPlaceholderType = ['radio', 'checkbox'].includes(window._veFormFieldType || '');

        payload.label       = $('#ve-ff-label').val().trim();
        payload.help_text   = $('#ve-ff-help-text').val().trim();
        payload.is_required = $('#ve-ff-required').is(':checked') ? 1 : 0;
        if (!noPlaceholderType) payload.placeholder = $('#ve-ff-placeholder').val().trim();

        $('#ve-ff-status').html('<span style="color:#888">Guardando...</span>');

        $.ajax({
            url:     '/panel/forms/fields/' + fieldId + '/editor',
            method:  'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data:    payload,
            success: function () {
                $('#ve-ff-status').html('<span style="color:green"><i class="fas fa-check me-1"></i>Guardado</span>');
                const frame = document.getElementById('ve-preview-frame');
                if (frame && frame.contentWindow && window._veCurrentNodeId) {
                    // Fix 4: update live data attributes so re-clicking shows updated values
                    const attrUpdates = {
                        'placeholder':             payload.placeholder,
                        'data-form-field-label':   payload.label,
                        'data-form-field-help':    payload.help_text,
                        'data-form-field-required': String(payload.is_required),
                    };
                    $.each(attrUpdates, function (attr, value) {
                        if (value !== undefined) {
                            frame.contentWindow.postMessage({
                                type: 've-apply-attribute', nodeId: window._veCurrentNodeId,
                                attr: attr, value: value,
                            }, '*');
                        }
                    });
                }
                // Reload iframe so label/help_text changes render server-side
                var doReload = function () {
                    var $f            = $('#ve-preview-frame');
                    var restoreNodeId = window._veCurrentNodeId;
                    var restoreScroll = 0;
                    try { restoreScroll = $f[0].contentWindow.scrollY || 0; } catch (ex) {}
                    $f.one('load', function () {
                        var fw = this.contentWindow;
                        if (!fw) return;
                        if (restoreScroll > 0) { try { fw.scrollTo(0, restoreScroll); } catch (ex) {} }
                        if (restoreNodeId) { fw.postMessage({ type: 've-select-by-id', nodeId: restoreNodeId }, '*'); }
                    });
                    $f.attr('src', $f.attr('src'));
                };
                setTimeout(function () {
                    if (window._veIsModified) {
                        if (confirm('Hay cambios sin guardar en el editor. Recargar la vista previa descartará los cambios de estilos. ¿Continuar?')) {
                            doReload();
                        }
                    } else {
                        doReload();
                    }
                }, 800);
                setTimeout(function () { $('#ve-ff-status').text(''); }, 3000);
            },
            error: function (xhr) {
                $('#ve-sect-form-field .ve-ff-error').remove();
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const fieldMap = { label: '#ve-ff-label', placeholder: '#ve-ff-placeholder', help_text: '#ve-ff-help-text', is_required: '#ve-ff-required' };
                    $.each(xhr.responseJSON.errors, function (key, msgs) {
                        const $el = $(fieldMap[key]);
                        if ($el.length) {
                            $el.after('<div class="ve-ff-error" style="color:#b10100;font-size:10px;margin-top:2px;">' + msgs[0] + '</div>');
                        }
                    });
                    $('#ve-ff-status').html('<span style="color:#b10100">Corrige los errores</span>');
                } else {
                    $('#ve-ff-status').html('<span style="color:#b10100">Error al guardar</span>');
                }
            },
        });
    });

    /* ── Form field: Ctrl+S / Cmd+S shortcut ───────────────────────── */
    $(document).on('keydown', '#ve-sect-form-field', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('#btn-save-form-field').trigger('click');
        }
    });

    /* ═══════════════════════════════════════════════════════════════
       SHORTCODE SENTINEL EDITOR
       ═══════════════════════════════════════════════════════════════ */

    /* ── Decode/encode data-ve-sc ───────────────────────────────────── */
    function veDecodeVeSc(encoded) {
        return encoded
            .replace(/&#91;/g, '[')
            .replace(/&#93;/g, ']')
            .replace(/&quot;/g, '"')
            .replace(/&amp;/g, '&');
    }

    function veEncodeVeSc(sc) {
        return sc
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/\[/g, '&#91;')
            .replace(/\]/g, '&#93;');
    }

    /* ── Parse shortcode string ─────────────────────────────────────── */
    function veParseAttrs(attrsStr) {
        const attrs = {};
        const re = /([a-z0-9_-]+)=(?:"([^"]*)"|'([^']*)')/gi;
        let m;
        while ((m = re.exec(attrsStr)) !== null) {
            attrs[m[1]] = m[2] !== undefined ? m[2] : m[3];
        }
        return attrs;
    }

    function veParseChildTags(content) {
        const items = [];
        const re = /\[([a-z0-9_-]+)([^\]]*?)\s*\/?]/gi;
        let m;
        while ((m = re.exec(content)) !== null) {
            items.push({ name: m[1], attrs: veParseAttrs(m[2]) });
        }
        return items;
    }

    function veParseShortcode(sc) {
        // Try [tag attrs]content[/tag]
        const m = sc.match(/^\[([a-z0-9_-]+)((?:\s+[a-z0-9_-]+=(?:"[^"]*"|'[^']*'))*)\s*\]([\s\S]*?)\[\/[a-z0-9_-]+\]$/i);
        if (m) {
            return { name: m[1], attrs: veParseAttrs(m[2]), content: m[3], items: veParseChildTags(m[3]), selfClosing: false };
        }
        // Try self-closing [tag attrs /] or [tag attrs]
        const m2 = sc.match(/^\[([a-z0-9_-]+)((?:\s+[a-z0-9_-]+=(?:"[^"]*"|'[^']*'))*)\s*\/?\]$/i);
        if (m2) {
            return { name: m2[1], attrs: veParseAttrs(m2[2]), content: '', items: [], selfClosing: true };
        }
        return null;
    }

    /* ── Build shortcode string ─────────────────────────────────────── */
    function veEscAttrVal(v) {
        return String(v || '').replace(/"/g, '&quot;').replace(/\[/g, '&#91;').replace(/\]/g, '&#93;');
    }

    function veBuildTag(name, attrs, items, selfClosing, textContent) {
        let attrsStr = '';
        Object.keys(attrs).forEach(function (k) {
            attrsStr += ' ' + k + '="' + veEscAttrVal(attrs[k]) + '"';
        });
        if (selfClosing) return '[' + name + attrsStr + ' /]';
        let body = '';
        if (items && items.length) {
            items.forEach(function (item) {
                if (!item.name) return;
                let iAttrs = '';
                Object.keys(item.attrs).forEach(function (k) { iAttrs += ' ' + k + '="' + veEscAttrVal(item.attrs[k]) + '"'; });
                body += '[' + item.name + iAttrs + ']';
            });
        } else if (textContent !== undefined && textContent !== null) {
            body = textContent;
        }
        return '[' + name + attrsStr + ']' + body + '[/' + name + ']';
    }

    /* ── Build attrs UI ─────────────────────────────────────────────── */
    function veBuildAttrsUI(parsedSc) {
        const $list    = $('#ve-sc-attrs-list').empty();
        const meta     = (window.veScMetaMap || {})[parsedSc.name] || {};
        const rawMeta  = meta.attributes || [];
        const metaAttrs = Array.isArray(rawMeta)
            ? rawMeta
            : Object.keys(rawMeta).map(function (k) { return { name: k, label: rawMeta[k], type: 'text' }; });

        if (metaAttrs.length === 0 && Object.keys(parsedSc.attrs).length === 0) {
            $list.append('<p class="ve-hint-text">Este bloque no tiene atributos configurables.</p>');
            return;
        }

        metaAttrs.forEach(function (ma) {
            const val      = parsedSc.attrs[ma.name] !== undefined ? parsedSc.attrs[ma.name] : (ma.default || '');
            const type     = ma.type || 'text';
            const reqMark  = ma.required ? ' <span style="color:#b10100">*</span>' : '';
            const labelTxt = (ma.label || ma.name) + reqMark;
            if (type === 'checkbox') {
                const input = veBuildFieldInput(ma, val, 've-sc-attr-check');
                $list.append('<div class="ve-field d-flex align-items-center gap-2">' + input + '<label style="margin:0">' + labelTxt + '</label></div>');
            } else {
                $list.append('<div class="ve-field"><label>' + labelTxt + '</label>' + veBuildFieldInput(ma, val, 've-sc-attr-input') + '</div>');
            }
        });

        // Extra attrs not in meta
        Object.keys(parsedSc.attrs).forEach(function (k) {
            if (metaAttrs.some(function (ma) { return ma.name === k; })) return;
            $list.append(
                '<div class="ve-field"><label class="text-muted">' + k + '</label>' +
                '<input type="text" class="form-control ve-sc-attr-input" data-attr="' + k + '" value="' + $('<div>').text(parsedSc.attrs[k]).html() + '"></div>'
            );
        });

        // Content textarea for non-self-closing shortcodes without structured items
        if (!parsedSc.selfClosing && !meta.items) {
            const contentEsc = $('<div>').text(parsedSc.content || '').html();
            $list.append(
                '<div class="ve-field mt-2 ve-sc-content-wrap">' +
                '<label>Contenido</label>' +
                '<textarea class="form-control ve-sc-attr-content" id="ve-sc-content-input" rows="3">' + contentEsc + '</textarea>' +
                '<p class="ve-hint-text mt-1">Texto libre dentro del bloque.</p>' +
                '</div>'
            );
        }

        // Update attrs tab count badge
        const attrCount = $('#ve-sc-attrs-list .ve-sc-attr-input, #ve-sc-attrs-list .ve-sc-attr-check').length;
        $('.ve-sc-tab[data-tab="attrs"]').text(attrCount > 0 ? 'Atributos (' + attrCount + ')' : 'Atributos');

        // Improvement A: show search field when 5+ fields
        if (attrCount >= 5) {
            $list.prepend(
                '<div class="ve-sc-attrs-search-wrap mb-1">' +
                '<input type="text" class="form-control form-control-sm ve-monospace" id="ve-sc-attrs-search" placeholder="Filtrar atributos...">' +
                '</div>'
            );
        }
    }

    /* ── Build one field input ──────────────────────────────────────── */
    function veBuildFieldInput(ma, val, cls) {
        const type        = ma.type || 'text';
        const esc         = $('<div>').text(val).html();
        // Improvement 7: smart placeholder from ma.placeholder or ma.default
        const placeholder = ma.placeholder || (ma.default !== undefined && ma.default !== '' ? ma.default : '');
        const phAttr      = placeholder ? ' placeholder="' + $('<div>').text(placeholder).html() + '"' : '';
        // Improvement 3: required data attribute
        const reqAttr     = ma.required ? ' data-required="1"' : '';
        if (type === 'textarea') {
            const mlAttr  = ma.maxlength ? ' maxlength="' + ma.maxlength + '" data-maxlength="' + ma.maxlength + '"' : '';
            const counter = ma.maxlength
                ? '<div class="ve-char-count">' + (val ? val.length : 0) + ' / ' + ma.maxlength + '</div>'
                : '';
            return '<textarea class="form-control ' + cls + '" rows="2" data-attr="' + ma.name + '"' + phAttr + reqAttr + mlAttr + '>' + esc + '</textarea>' + counter;
        }
        if (type === 'color') {
            const hex     = /^#[0-9a-fA-F]{6}$/.test(val) ? val : '#000000';
            const swatches = ['#90bb13','#13C672','#FA896B','#FEC90F','#1e1e2e','#ffffff','#f4f6f8','#333333'];
            const swatchHtml = '<div class="ve-brand-palette">' +
                swatches.map(function (c) {
                    return '<div class="ve-brand-swatch" style="background:' + c + '" data-color="' + c + '" title="' + c + '"></div>';
                }).join('') +
                '</div>';
            return '<div class="ve-color-row">' +
                '<input type="color" class="form-control form-control-color ' + cls + '" data-attr="' + ma.name + '" value="' + hex + '">' +
                '<input type="text" class="form-control ve-sc-color-text" placeholder="#000000" value="' + esc + '">' +
                '</div>' + swatchHtml;
        }
        if (type === 'image') {
            const thumb = val ? '<img src="' + esc + '" class="ve-sc-img-thumb" alt="" onerror="this.style.display=\'none\'">' : '';
            // Improvement 4: add media picker button
            return '<div class="ve-sc-image-wrap">' +
                '<input type="text" class="form-control ' + cls + ' ve-sc-image-url" data-attr="' + ma.name + '" value="' + esc + '" placeholder="https://...">' +
                '<button type="button" class="btn btn-outline-secondary btn-sm mt-1 w-100 ve-sc-img-pick-btn" data-attr="' + ma.name + '">' +
                '<i class="fa-solid fa-image me-1"></i> Seleccionar imagen</button>' +
                '<div class="ve-sc-img-preview">' + thumb + '</div>' +
                '</div>';
        }
        if (type === 'number') {
            const min = ma.min !== undefined ? ' min="' + ma.min + '"' : '';
            const max = ma.max !== undefined ? ' max="' + ma.max + '"' : '';
            return '<input type="number" class="form-control ' + cls + '" data-attr="' + ma.name + '" value="' + esc + '"' + min + max + '>';
        }
        if (type === 'select' && ma.options) {
            let opts = '';
            ma.options.forEach(function (o) {
                opts += '<option value="' + o.value + '"' + (o.value === val ? ' selected' : '') + '>' + o.label + '</option>';
            });
            return '<select class="form-select form-select ' + cls + '" data-attr="' + ma.name + '">' + opts + '</select>';
        }
        if (type === 'checkbox') {
            const chk = (val === '1' || val === 'true') ? ' checked' : '';
            return '<input type="checkbox" class="form-check-input ' + cls + '" data-attr="' + ma.name + '"' + chk + '>';
        }
        if (type === 'icon') {
            const preview = val ? '<div class="mt-1 text-center"><i class="' + esc + ' fa-2x ve-icon-primary"></i></div>' : '';
            return '<div class="ve-sc-icon-wrap">' +
                '<input type="text" class="form-control ' + cls + ' ve-sc-icon-input" data-attr="' + ma.name + '" value="' + esc + '" placeholder="fas fa-star">' +
                '<button type="button" class="btn btn-outline-secondary btn-sm mt-1 w-100 ve-sc-icon-pick-btn" data-attr="' + ma.name + '">' +
                '<i class="fa-solid fa-icons me-1"></i> Elegir icono</button>' +
                preview +
                '</div>';
        }
        // Improvement C: datalist with localStorage history for text fields
        const dlId      = 've-dl-' + ma.name;
        const history   = JSON.parse(localStorage.getItem('ve-attr-history-' + ma.name) || '[]');
        const datalist  = history.length
            ? '<datalist id="' + dlId + '">' + history.map(function (v) { return '<option value="' + $('<div>').text(v).html() + '">'; }).join('') + '</datalist>'
            : '';
        const listAttr  = history.length ? ' list="' + dlId + '"' : '';
        return '<input type="text" class="form-control ' + cls + '" data-attr="' + ma.name + '" value="' + esc + '"' + listAttr + '>' + datalist;
    }

    /* ── Build item card ────────────────────────────────────────────── */
    function veBuildItemCard(item, idx, itemMeta) {
        const rawItemMeta = (itemMeta && itemMeta.attributes) ? itemMeta.attributes : [];
        const metaAttrs   = Array.isArray(rawItemMeta)
            ? rawItemMeta
            : Object.keys(rawItemMeta).map(function (k) { return { name: k, label: rawItemMeta[k], type: 'text' }; });
        let fields = '';
        if (metaAttrs.length) {
            metaAttrs.forEach(function (ma) {
                const val  = item.attrs[ma.name] !== undefined ? item.attrs[ma.name] : (ma.default || '');
                const type = ma.type || 'text';
                if (type === 'checkbox') {
                    const input = veBuildFieldInput(ma, val, 've-sc-item-attr');
                    fields += '<div class="ve-field d-flex align-items-center gap-2">' + input + '<label style="margin:0">' + (ma.label || ma.name) + '</label></div>';
                } else {
                    fields += '<div class="ve-field"><label>' + (ma.label || ma.name) + '</label>' + veBuildFieldInput(ma, val, 've-sc-item-attr') + '</div>';
                }
            });
        } else {
            Object.keys(item.attrs).forEach(function (k) {
                fields += '<div class="ve-field"><label>' + k + '</label>' +
                    '<input type="text" class="form-control ve-sc-item-attr" data-attr="' + k + '" value="' + $('<div>').text(item.attrs[k]).html() + '"></div>';
            });
        }
        const firstKey  = metaAttrs[0] ? metaAttrs[0].name : Object.keys(item.attrs)[0];
        const rawLabel  = (firstKey && item.attrs[firstKey]) ? item.attrs[firstKey] : ('Ítem ' + (idx + 1));
        const shortLabel = rawLabel.length > 32 ? rawLabel.slice(0, 32) + '…' : rawLabel;
        return $(
            '<div class="ve-sc-item-card" data-item-idx="' + idx + '" data-item-name="' + item.name + '">' +
            '<div class="ve-sc-item-header">' +
            '<span class="ve-sc-drag-handle" title="Arrastrar para reordenar"><i class="fa-solid fa-grip-vertical"></i></span>' +
            '<span class="ve-sc-item-label ve-sc-item-toggle">' + $('<span>').text(shortLabel).html() + '</span>' +
            '<button type="button" class="btn btn-link text-secondary p-0 ve-sc-item-dupe" title="Duplicar"><i class="fa-solid fa-copy"></i></button>' +
            '<button type="button" class="btn btn-link text-danger p-0 ve-sc-item-delete" title="Eliminar"><i class="fa-solid fa-trash-can"></i></button>' +
            '</div>' +
            '<div class="ve-sc-item-body">' + fields + '</div>' +
            '</div>'
        );
    }

    /* ── Build items UI ─────────────────────────────────────────────── */
    function veBuildItemsUI(parsedSc) {
        const $list    = $('#ve-sc-items-list');
        if ($list.hasClass('ui-sortable')) { try { $list.sortable('destroy'); } catch (e) {} }
        $list.empty();

        const meta     = (window.veScMetaMap || {})[parsedSc.name] || {};
        const itemMeta = meta.items || null;

        if (!parsedSc.items || parsedSc.items.length === 0) {
            $list.append('<p class="ve-hint-text">No hay ítems. Usa el botón + para agregar.</p>');
            $('.ve-sc-items-toolbar').hide();
        } else {
            parsedSc.items.forEach(function (item, idx) {
                $list.append(veBuildItemCard(item, idx, itemMeta));
            });
            veInitSortable();
            $('.ve-sc-items-toolbar').show();
        }

        const hasItems = !parsedSc.selfClosing && (itemMeta || parsedSc.items.length > 0);
        const itemCount = parsedSc.items ? parsedSc.items.length : 0;
        $('.ve-sc-tab[data-tab="items"]').toggle(hasItems).text(hasItems ? 'Ítems' + (itemCount > 0 ? ' (' + itemCount + ')' : '') : '');

        window._veScItemName = itemMeta ? itemMeta.name : null;
        window._veScItemMeta = itemMeta;
    }

    /* ── Init sortable drag-to-reorder ──────────────────────────────── */
    function veInitSortable() {
        const $list = $('#ve-sc-items-list');
        if (typeof $list.sortable !== 'function') return;
        $list.sortable({
            handle:      '.ve-sc-drag-handle',
            items:       '.ve-sc-item-card',
            axis:        'y',
            tolerance:   'pointer',
            placeholder: 've-sc-sortable-placeholder',
        });
    }

    /* ── Collect attrs from UI ──────────────────────────────────────── */
    function veCollectAttrs() {
        const attrs = {};
        const originalAttrs = (window._veScCurrent && window._veScCurrent.parsed)
            ? window._veScCurrent.parsed.attrs : {};
        $('#ve-sc-attrs-list .ve-sc-attr-input').each(function () {
            const key = $(this).data('attr');
            let val;
            if ($(this).attr('type') === 'color') {
                const textVal = $(this).closest('.ve-color-row').find('.ve-sc-color-text').val();
                val = /^#[0-9a-fA-F]{6}$/.test(textVal) ? textVal : $(this).val();
            } else {
                val = $(this).val();
            }
            // Skip meta-only attrs that were never in the original and have no value
            if (val === '' && !(key in originalAttrs)) return;
            attrs[key] = val;
        });
        $('#ve-sc-attrs-list .ve-sc-attr-check').each(function () {
            attrs[$(this).data('attr')] = $(this).is(':checked') ? '1' : '0';
        });
        return attrs;
    }

    /* ── Collect items from UI ──────────────────────────────────────── */
    function veCollectItems() {
        const items = [];
        $('#ve-sc-items-list .ve-sc-item-card').each(function () {
            const name  = $(this).data('item-name');
            const attrs = {};
            $(this).find('.ve-sc-item-attr').each(function () {
                if ($(this).attr('type') === 'color') {
                    const textVal = $(this).closest('.ve-color-row').find('.ve-sc-color-text').val();
                    attrs[$(this).data('attr')] = /^#[0-9a-fA-F]{6}$/.test(textVal) ? textVal : $(this).val();
                } else if ($(this).attr('type') === 'checkbox') {
                    attrs[$(this).data('attr')] = $(this).is(':checked') ? '1' : '0';
                } else {
                    attrs[$(this).data('attr')] = $(this).val();
                }
            });
            items.push({ name: name, attrs: attrs });
        });
        return items;
    }

    /* ── Show the editor ────────────────────────────────────────────── */
    function showScEditor(encodedSc, scNodeId) {
        const sc     = veDecodeVeSc(encodedSc);
        const parsed = veParseShortcode(sc);
        if (!parsed) {
            // Corrupted shortcode — still show section in raw mode with error
            window._veScCurrent  = { parsed: null, scNodeId: scNodeId, raw: sc };
            window._veScOriginal = sc;
            window._veScHistory  = [];
            window._veScDirty    = false;
            $('#ve-sc-name-badge').text('[?]');
            $('#ve-sc-description').addClass('ve-hidden');
            $('#ve-sc-status').html('<span style="color:#b10100">Shortcode inválido — edita en modo Raw</span>');
            $('#btn-sc-undo').addClass('ve-hidden');
            $('#btn-sc-reset').addClass('ve-hidden');
            $('.ve-sc-tab').hide();
            $('.ve-sc-tab[data-tab="raw"]').show().addClass('active');
            $('#ve-sc-pane-attrs, #ve-sc-pane-items').addClass('ve-hidden');
            $('#ve-sc-pane-raw').removeClass('ve-hidden');
            $('#ve-sc-raw-input').val(sc);
            $('#ve-section-shortcode').removeClass('ve-hidden');
            $('#ve-sect-shortcode').removeClass('ve-hidden');
            $('.ve-section-header[data-target="ve-sect-shortcode"]').removeClass('collapsed');
            $('#btn-sc-save-page').toggleClass('ve-hidden', !window._veIsModified);
            return;
        }

        const meta      = (window.veScMetaMap || {})[parsed.name] || {};
        const metaAttrs = Array.isArray(meta.attributes) ? meta.attributes : Object.keys(meta.attributes || {});
        const hasAttrs  = metaAttrs.length > 0 || Object.keys(parsed.attrs).length > 0;
        const hasItems  = !parsed.selfClosing && (meta.items || parsed.items.length > 0);
        const hasMeta  = hasAttrs || hasItems;

        window._veScCurrent  = { parsed: parsed, scNodeId: scNodeId, raw: sc };
        window._veScOriginal = sc;
        window._veScHistory  = [];
        window._veScDirty    = false;

        $('#ve-sc-name-badge').text('[' + parsed.name + ']');
        const scDesc = meta.description || '';
        $('#ve-sc-description').toggleClass('ve-hidden', !scDesc).text(scDesc);
        $('#ve-sc-status').text('');
        $('#btn-sc-undo').addClass('ve-hidden');
        $('#btn-sc-reset').addClass('ve-hidden');

        // Tabs visibility
        $('.ve-sc-tab[data-tab="attrs"]').toggle(hasAttrs);
        $('.ve-sc-tab[data-tab="items"]').toggle(hasItems);
        $('.ve-sc-tab[data-tab="raw"]').show();

        // Raw textarea always available
        $('#ve-sc-raw-input').val(sc);

        if (hasMeta) {
            veBuildAttrsUI(parsed);
            veBuildItemsUI(parsed);
            veScSwitchTab(hasAttrs ? 'attrs' : 'items');
        } else {
            // No meta: open raw directly
            veScSwitchTab('raw');
        }

        $('#ve-section-shortcode').removeClass('ve-hidden');
        $('#ve-sect-shortcode').removeClass('ve-hidden');
        $('.ve-section-header[data-target="ve-sect-shortcode"]').removeClass('collapsed');
        $('#btn-sc-save-page').toggleClass('ve-hidden', !window._veIsModified);

        // Improvements 5 & 6: show duplicate and move buttons when shortcode is active
        $('#btn-sc-duplicate, #btn-sc-move-up, #btn-sc-move-down').removeClass('ve-hidden');

        // Improvements B & F: show export/import/preset buttons
        $('#btn-sc-export-json, #btn-sc-import-json, #btn-sc-save-preset').removeClass('ve-hidden');

        // Render saved presets for this shortcode
        veRenderPresets(parsed.name);

        // Scroll the section into view
        setTimeout(function () {
            const $section = $('#ve-section-shortcode');
            const $scroll  = $section.closest('.ve-panel-root');
            if ($section.length && $scroll.length) {
                const offset = $section.position().top + $scroll.scrollTop() - 8;
                $scroll.animate({ scrollTop: offset }, 200);
            }
        }, 60);
    }

    /* ── Shortcode presets (stub — extendable) ──────────────────────── */
    function veRenderPresets(scName) {
        var $wrap = $('#ve-sc-presets-wrap');
        if (!$wrap.length) return;
        var stored = [];
        try { stored = JSON.parse(localStorage.getItem('ve_sc_presets_' + scName) || '[]'); } catch (e) {}
        if (!stored.length) { $wrap.addClass('ve-hidden'); return; }
        $wrap.removeClass('ve-hidden');
        var $list = $wrap.find('.ve-sc-presets-list').empty();
        stored.forEach(function (preset, idx) {
            $('<button type="button" class="ve-sc-preset-btn">')
                .text(preset.name || 'Preset ' + (idx + 1))
                .attr('title', 'Cargar preset')
                .on('click', function () {
                    if (preset.attrs) {
                        Object.keys(preset.attrs).forEach(function (k) {
                            $('#ve-sc-attr-' + k).val(preset.attrs[k]).trigger('input');
                        });
                    }
                    window.showToast && window.showToast('Preset "' + (preset.name || 'Preset') + '" aplicado');
                })
                .appendTo($list);
        });
    }

    function veScSwitchTab(tab) {
        $('.ve-sc-tab').removeClass('active');
        $('.ve-sc-tab[data-tab="' + tab + '"]').addClass('active');
        $('#ve-sc-pane-attrs').toggleClass('ve-hidden', tab !== 'attrs');
        $('#ve-sc-pane-items').toggleClass('ve-hidden', tab !== 'items');
        $('#ve-sc-pane-raw').toggleClass('ve-hidden', tab !== 'raw');
        // Sync raw textarea with current form state when switching to raw tab
        if (tab === 'raw' && window._veScCurrent && window._veScCurrent.parsed) {
            const p = window._veScCurrent.parsed;
            const textContent = (!p.selfClosing && !((window.veScMetaMap || {})[p.name] || {}).items)
                ? ($('#ve-sc-content-input').val() || '')
                : undefined;
            const synced = veBuildTag(p.name, veCollectAttrs(), veCollectItems(), p.selfClosing, textContent);
            $('#ve-sc-raw-input').val(synced);
        }
    }

    /* ── Tab switcher ───────────────────────────────────────────────── */
    $(document).on('click', '.ve-sc-tab', function () {
        veScSwitchTab($(this).data('tab'));
    });

    /* ── Shortcode dirty tracking ───────────────────────────────────── */
    $(document).on('input change', '#ve-sect-shortcode input, #ve-sect-shortcode textarea, #ve-sect-shortcode select', function () {
        window._veScDirty = true;
    });

    /* ── Collapse/expand item card ──────────────────────────────────── */
    $(document).on('click', '.ve-sc-item-toggle', function () {
        $(this).closest('.ve-sc-item-card').find('.ve-sc-item-body').slideToggle(150);
        $(this).closest('.ve-sc-item-card').toggleClass('ve-sc-item-collapsed');
    });

    /* ── Real-time label update in item header ──────────────────────── */
    $(document).on('input', '.ve-sc-item-attr, .ve-sc-item-attr textarea', function () {
        const $card  = $(this).closest('.ve-sc-item-card');
        const $first = $card.find('.ve-sc-item-attr').first();
        if ($(this).is($first)) {
            const raw   = $(this).val() || ('Ítem ' + ($card.index() + 1));
            const short = raw.length > 32 ? raw.slice(0, 32) + '…' : raw;
            $card.find('.ve-sc-item-label').text(short);
        }
    });

    /* ── Color picker ↔ text sync (attrs) ──────────────────────────── */
    $(document).on('input', '#ve-sc-attrs-list input[type="color"]', function () {
        $(this).closest('.ve-color-row').find('.ve-sc-color-text').val($(this).val());
    });
    $(document).on('input', '#ve-sc-attrs-list .ve-sc-color-text', function () {
        const val = $(this).val();
        if (/^#[0-9a-fA-F]{6}$/.test(val)) {
            $(this).closest('.ve-color-row').find('input[type="color"]').val(val);
        }
    });
    $(document).on('input', '#ve-sc-items-list input[type="color"]', function () {
        $(this).closest('.ve-color-row').find('.ve-sc-color-text').val($(this).val());
    });
    $(document).on('input', '#ve-sc-items-list .ve-sc-color-text', function () {
        const val = $(this).val();
        if (/^#[0-9a-fA-F]{6}$/.test(val)) {
            $(this).closest('.ve-color-row').find('input[type="color"]').val(val);
        }
    });

    /* ── Add item ───────────────────────────────────────────────────── */
    $(document).on('click', '#btn-sc-add-item', function () {
        if (!window._veScCurrent) return;
        const itemName = window._veScItemName || (window._veScCurrent.parsed.name + '-item');
        const itemMeta = window._veScItemMeta || null;
        const newItem  = { name: itemName, attrs: {} };
        if (itemMeta && itemMeta.attributes) {
            itemMeta.attributes.forEach(function (ma) { newItem.attrs[ma.name] = ma.default || ''; });
        }
        const idx = $('#ve-sc-items-list .ve-sc-item-card').length;
        $('#ve-sc-items-list .ve-hint-text').remove();
        $('#ve-sc-items-list').append(veBuildItemCard(newItem, idx, itemMeta));
        veInitSortable();
        $('.ve-sc-items-toolbar').show();
        const newCount = $('#ve-sc-items-list .ve-sc-item-card').length;
        $('.ve-sc-tab[data-tab="items"]').text('Ítems (' + newCount + ')');
    });

    /* ── Delete item ────────────────────────────────────────────────── */
    $(document).on('click', '.ve-sc-item-delete', function () {
        $(this).closest('.ve-sc-item-card').remove();
        if ($('#ve-sc-items-list .ve-sc-item-card').length === 0) {
            $('#ve-sc-items-list').append('<p class="ve-hint-text">No hay ítems. Usa el botón + para agregar.</p>');
        }
    });

    /* ── Duplicate item ─────────────────────────────────────────────── */
    $(document).on('click', '.ve-sc-item-dupe', function () {
        const $original = $(this).closest('.ve-sc-item-card');
        const $clone    = $original.clone(true);
        const newIdx    = $('#ve-sc-items-list .ve-sc-item-card').length;
        $clone.attr('data-item-idx', newIdx);
        $clone.find('.ve-sc-item-body').show();
        $clone.removeClass('ve-sc-item-collapsed');
        $original.after($clone);
        window._veScDirty = true;
    });

    /* ── Expand / Collapse all items ────────────────────────────────── */
    $(document).on('click', '#btn-sc-expand-all', function () {
        $('#ve-sc-items-list .ve-sc-item-card').each(function () {
            $(this).removeClass('ve-sc-item-collapsed');
            $(this).find('.ve-sc-item-body').show();
        });
    });
    $(document).on('click', '#btn-sc-collapse-all', function () {
        $('#ve-sc-items-list .ve-sc-item-card').each(function () {
            $(this).addClass('ve-sc-item-collapsed');
            $(this).find('.ve-sc-item-body').hide();
        });
    });

    /* ── Image URL → live preview ───────────────────────────────────── */
    $(document).on('input', '.ve-sc-image-url', function () {
        const url   = $(this).val().trim();
        const $prev = $(this).closest('.ve-sc-image-wrap').find('.ve-sc-img-preview');
        if (url) {
            const safeUrl = $('<div>').text(url).html();
            $prev.html('<img src="' + safeUrl + '" class="ve-sc-img-thumb" alt="" onerror="this.style.display=\'none\'">');
        } else {
            $prev.empty();
        }
    });

    /* ── Guardar página desde inspector ─────────────────────────────── */
    $(document).on('click', '#btn-sc-save-page', function () {
        $('#btn-save').trigger('click');
    });

    /* ── Mostrar btn-sc-save-page cuando hay cambios en la página ───── */
    $(document).on('ve-modified-changed', function (e, isDirty) {
        $('#btn-sc-save-page').toggleClass('ve-hidden', !isDirty);
    });

    /* ── Ctrl+Enter para aplicar shortcode ─────────────────────────── */
    $(document).on('keydown', '#ve-sect-shortcode', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            $('#btn-sc-apply').trigger('click');
        }
    });

    /* ── Apply button ───────────────────────────────────────────────── */
    $(document).on('click', '#btn-sc-apply', function () {
        if (!window._veScCurrent) return;
        const frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;

        const parsed    = window._veScCurrent.parsed;
        const scNodeId  = window._veScCurrent.scNodeId;
        const activeTab = $('.ve-sc-tab.active').data('tab') || 'attrs';

        let newSc;
        if (activeTab === 'raw') {
            newSc = $('#ve-sc-raw-input').val().trim();
        } else {
            // Validate required fields
            const scMeta    = (window.veScMetaMap || {})[parsed.name] || {};
            const rawScMeta = scMeta.attributes || [];
            const scMetaArr = Array.isArray(rawScMeta)
                ? rawScMeta
                : Object.keys(rawScMeta).map(function (k) { return { name: k, label: rawScMeta[k], type: 'text' }; });
            const missing = [];
            scMetaArr.forEach(function (ma) {
                if (!ma.required) return;
                if (!$('#ve-sc-attrs-list [data-attr="' + ma.name + '"]').val()) {
                    missing.push(ma.label || ma.name);
                }
            });
            if (missing.length) {
                $('#ve-sc-status').html('<span style="color:#b10100"><i class="fa-solid fa-triangle-exclamation me-1"></i>Requeridos: ' + missing.join(', ') + '</span>');
                return;
            }
            const textContent = (parsed && !parsed.selfClosing && !scMeta.items)
                ? ($('#ve-sc-content-input').val() || '')
                : undefined;
            newSc = veBuildTag(parsed.name, veCollectAttrs(), veCollectItems(), parsed.selfClosing, textContent);
        }
        if (!newSc) return;

        // Improvement C: save attr values to history before applying
        if (activeTab !== 'raw') {
            var collectedForHistory = veCollectAttrs();
            Object.keys(collectedForHistory).forEach(function (k) {
                saveAttrHistory(k, collectedForHistory[k]);
            });
        }

        $('#ve-sc-status').html('<span class="text-muted">Aplicando…</span>');

        $.ajax({
            url:     window.EXPAND_SHORTCODE_URL || '',
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data:    { shortcode: newSc },
            success: function (resp) {
                // Push to undo history before updating state
                window._veScHistory = window._veScHistory || [];
                window._veScHistory.push(window._veScCurrent.raw);
                $('#btn-sc-undo').removeClass('ve-hidden');
                $('#btn-sc-reset').removeClass('ve-hidden');

                const html      = resp.html || '';
                const encodedSc = veEncodeVeSc(newSc);
                frame.contentWindow.postMessage({
                    type:      've-update-shortcode-sentinel',
                    scNodeId:  scNodeId,
                    encodedSc: encodedSc,
                    html:      html,
                }, '*');

                const reparsed = veParseShortcode(newSc);
                if (reparsed) window._veScCurrent.parsed = reparsed;
                window._veScCurrent.raw = newSc;
                window._veScDirty = false;
                $('#ve-sc-raw-input').val(newSc);

                // Rebuild attrs/items UI to reflect applied state
                if (window._veScCurrent.parsed) {
                    veBuildAttrsUI(window._veScCurrent.parsed);
                    veBuildItemsUI(window._veScCurrent.parsed);
                }

                $('#ve-sc-status').html('<span style="color:#13C672"><i class="fa-solid fa-check me-1"></i>Aplicado</span>');
                setTimeout(function () { $('#ve-sc-status').text(''); }, 2500);
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error al expandir bloque';
                $('#ve-sc-status').html('<span style="color:#b10100">' + msg + '</span>');
            },
        });
    });

    /* ── Undo button ────────────────────────────────────────────────── */
    $(document).on('click', '#btn-sc-undo', function () {
        if (!window._veScCurrent || !window._veScHistory || !window._veScHistory.length) return;
        const frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;

        const prevSc   = window._veScHistory.pop();
        const scNodeId = window._veScCurrent.scNodeId;

        if (!window._veScHistory.length) $('#btn-sc-undo').addClass('ve-hidden');
        $('#ve-sc-status').html('<span class="text-muted">Revirtiendo…</span>');

        $.ajax({
            url:     window.EXPAND_SHORTCODE_URL || '',
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data:    { shortcode: prevSc },
            success: function (resp) {
                const encodedSc = veEncodeVeSc(prevSc);
                frame.contentWindow.postMessage({
                    type:      've-update-shortcode-sentinel',
                    scNodeId:  scNodeId,
                    encodedSc: encodedSc,
                    html:      resp.html || '',
                }, '*');
                const reparsed = veParseShortcode(prevSc);
                if (reparsed) {
                    window._veScCurrent.parsed = reparsed;
                    veBuildAttrsUI(reparsed);
                    veBuildItemsUI(reparsed);
                }
                window._veScCurrent.raw = prevSc;
                $('#ve-sc-raw-input').val(prevSc);
                window._veScDirty = false;
                $('#ve-sc-status').html('<span style="color:#13C672">Revertido</span>');
                setTimeout(function () { $('#ve-sc-status').text(''); }, 2000);
            },
            error: function () {
                $('#ve-sc-status').html('<span style="color:#b10100">Error al revertir</span>');
            },
        });
    });

    /* ── Copy shortcode ─────────────────────────────────────────────── */
    $(document).on('click', '#btn-sc-copy', function () {
        const val = $('#ve-sc-raw-input').val();
        if (!val) return;
        navigator.clipboard.writeText(val).then(function () {
            $('#btn-sc-copy').html('<i class="fa-solid fa-check me-1"></i> Copiado');
            setTimeout(function () {
                $('#btn-sc-copy').html('<i class="fa-solid fa-copy me-1"></i> Copiar shortcode');
            }, 2000);
        });
    });

    /* ── Reset shortcode to original ───────────────────────────────── */
    $(document).on('click', '#btn-sc-reset', function () {
        if (!window._veScOriginal || !window._veScCurrent) return;
        const frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;
        const prevSc   = window._veScOriginal;
        const scNodeId = window._veScCurrent.scNodeId;
        $('#ve-sc-status').html('<span class="text-muted">Restableciendo…</span>');
        $.ajax({
            url:     window.EXPAND_SHORTCODE_URL || '',
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data:    { shortcode: prevSc },
            success: function (resp) {
                frame.contentWindow.postMessage({
                    type:      've-update-shortcode-sentinel',
                    scNodeId:  scNodeId,
                    encodedSc: veEncodeVeSc(prevSc),
                    html:      resp.html || '',
                }, '*');
                const reparsed = veParseShortcode(prevSc);
                if (reparsed) {
                    window._veScCurrent.parsed = reparsed;
                    veBuildAttrsUI(reparsed);
                    veBuildItemsUI(reparsed);
                }
                window._veScCurrent.raw = prevSc;
                window._veScHistory     = [];
                window._veScDirty       = false;
                $('#ve-sc-raw-input').val(prevSc);
                $('#btn-sc-undo').addClass('ve-hidden');
                $('#btn-sc-reset').addClass('ve-hidden');
                $('#ve-sc-status').html('<span style="color:#13C672">Restablecido al original</span>');
                setTimeout(function () { $('#ve-sc-status').text(''); }, 2000);
            },
            error: function () {
                $('#ve-sc-status').html('<span style="color:#b10100">Error al restablecer</span>');
            },
        });
    });

    /* ── Brand color swatches ───────────────────────────────────────── */
    $(document).on('click', '.ve-brand-swatch', function () {
        const color = $(this).data('color');
        const $field = $(this).closest('.ve-field');
        $field.find('input[type="color"]').val(color).trigger('input');
        $field.find('.ve-sc-color-text').val(color).trigger('input');
    });

    /* ── Spacing presets ────────────────────────────────────────────── */
    $(document).on('click', '.ve-spacing-preset', function () {
        const val      = $(this).data('value');
        const $section = $(this).closest('.ve-sect-body');
        $section.find('.ve-css-prop[data-prop*="padding"], .ve-css-prop[data-prop*="margin"]').each(function () {
            $(this).val(val).trigger('change');
        });
    });

    /* ── Background image upload ────────────────────────────────────── */
    $('#ve-bg-image-file').on('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (ev) {
            const dataUrl = ev.target.result;
            applyStyle('background-image', 'url("' + dataUrl + '")');
            applyStyle('background-size', 'cover');
            applyStyle('background-position', 'center');
        };
        reader.readAsDataURL(file);
    });

    $('#btn-clear-bg-image').on('click', function () {
        applyStyle('background-image', 'none');
        $('#ve-bg-image-file').val('');
    });

    /* ── Position: show/hide coords ────────────────────────────────────── */
    $(document).on('change', '#ve-position-select', function () {
        const val = $(this).val();
        $('#ve-position-coords').toggle(val !== '' && val !== 'static');
    });

    /* ── Display: show/hide flex controls ─────────────────────────────── */
    $(document).on('change', '#ve-display-select', function () {
        const val = $(this).val();
        $('#ve-flex-controls').toggle(val === 'flex' || val === 'inline-flex' || val === 'grid' || val === 'inline-grid');
    });

    /* ── Layout section: display sub-options ───────────────────────────── */
    $(document).on('change', '#ve-layout-display-select', function () {
        const val = $(this).val();
        $('#ve-layout-flex-controls').toggle(val === 'flex' || val === 'inline-flex');
        $('#ve-layout-grid-controls').toggle(val === 'grid');
    });

    /* ── Layout section: position coords ───────────────────────────────── */
    $(document).on('change', '#ve-layout-position-select', function () {
        const val = $(this).val();
        $('#ve-layout-position-coords').toggle(val !== '' && val !== 'static');
    });


    /* ── Transform apply / clear ────────────────────────────────────────── */
    $('#ve-transform-rotate').on('input', function () {
        $('#ve-rotate-val').text($(this).val() + '°');
    });
    $('#ve-transform-scale').on('input', function () {
        $('#ve-scale-val').text($(this).val() + '×');
    });

    function buildTransform() {
        const rotate = parseFloat($('#ve-transform-rotate').val()) || 0;
        const scale  = parseFloat($('#ve-transform-scale').val())  || 1;
        const tx     = parseFloat($('#ve-transform-tx').val())     || 0;
        const ty     = parseFloat($('#ve-transform-ty').val())     || 0;
        const parts  = [];
        if (rotate !== 0) parts.push('rotate(' + rotate + 'deg)');
        if (scale  !== 1) parts.push('scale(' + scale + ')');
        if (tx     !== 0) parts.push('translateX(' + tx + 'px)');
        if (ty     !== 0) parts.push('translateY(' + ty + 'px)');
        return parts.length ? parts.join(' ') : 'none';
    }

    $('#btn-apply-transform').on('click', function () {
        applyStyle('transform', buildTransform());
    });
    $('#btn-clear-transform').on('click', function () {
        applyStyle('transform', 'none');
        $('#ve-transform-rotate').val(0);
        $('#ve-transform-scale').val(1);
        $('#ve-transform-tx, #ve-transform-ty').val('');
        $('#ve-rotate-val').text('0°');
        $('#ve-scale-val').text('1×');
    });

    /* ── Background image URL ─────────────────────────────────────────── */
    $('#btn-apply-bg-url').on('click', function () {
        const url = $('#ve-bg-image-url').val().trim();
        if (!url) return;
        applyStyle('background-image', 'url("' + url + '")');
        applyStyle('background-size', 'cover');
        applyStyle('background-position', 'center');
    });

    /* ── Reset section styles ────────────────────────────────────────── */
    const SECTION_PROPS = {
        typo:    ['font-size','font-weight','font-family','color','text-align','line-height','letter-spacing','text-transform','text-decoration'],
        spacing: ['padding-top','padding-right','padding-bottom','padding-left','margin-top','margin-right','margin-bottom','margin-left'],
        bg:      ['background-color','opacity','background-image','background-size','background-position'],
        border:  ['border-width','border-style','border-color','border-radius'],
        position:['position','top','right','bottom','left','z-index'],
        flex:    ['display','flex-direction','justify-content','align-items','flex-wrap','gap'],
    };

    $(document).on('click', '.ve-reset-section-btn', function () {
        const section = $(this).data('section');
        const props   = SECTION_PROPS[section];
        if (!props) return;
        const frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;
        frame.contentWindow.postMessage({
            type:   've-remove-style',
            nodeId: window._veCurrentNodeId,
            props:  props,
        }, '*');
        // Clear inputs visually
        props.forEach(function (p) {
            $('.ve-css-prop[data-prop="' + p + '"]').val('');
        });
    });

    /* ── Page picker for links ──────────────────────────────────────── */
    var _vePagePickerInit = false;

    function initPagePickerSelect() {
        if (_vePagePickerInit) return;
        _vePagePickerInit = true;
        $('#ve-link-page-select').select2({
            placeholder: 'Buscar página...',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: '{{ route("pages.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) {
                    return {
                        results: (data || []).map(function (p) {
                            return { id: p.public_url, text: p.title, url: p.public_url, status: p.status };
                        }),
                    };
                },
            },
            templateResult: function (p) {
                if (!p.id) return p.text;
                var badge = p.status === 'published' ? '<span style="font-size:9px;color:#13C672;">● Publicada</span>' : '<span style="font-size:9px;color:#aaa;">● Borrador</span>';
                return $('<span>' + p.text + ' ' + badge + '<br><small style="color:#bbb;">' + (p.url || '') + '</small></span>');
            },
        });
        $('#ve-link-page-select').on('select2:select', function (e) {
            var url = e.params.data.url || e.params.data.id;
            $('#ve-link-href-inspector').val(url);
            $('#ve-link-page-preview').text(url);
        });
    }

    $(document).on('click', '#btn-pick-page-link', function () {
        var $wrap = $('#ve-link-page-picker-wrap');
        if ($wrap.hasClass('ve-hidden')) {
            $wrap.removeClass('ve-hidden');
            initPagePickerSelect();
            setTimeout(function () { $('#ve-link-page-select').select2('open'); }, 100);
            $(this).html('<i class="fa-solid fa-times me-1"></i> Cerrar selector');
        } else {
            $wrap.addClass('ve-hidden');
            $(this).html('<i class="fa-solid fa-file-lines me-1"></i> Seleccionar página');
        }
    });

    /* ── Apply link ─────────────────────────────────────────────────── */
    $(document).on('click', '#btn-apply-link-inspector', function () {
        const frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;
        frame.contentWindow.postMessage({
            type:   've-apply-link',
            nodeId: window._veCurrentNodeId,
            href:   $('#ve-link-href-inspector').val(),
            target: $('#ve-link-target-inspector').val(),
        }, '*');
        // Close page picker if open
        $('#ve-link-page-picker-wrap').addClass('ve-hidden');
        $('#btn-pick-page-link').html('<i class="fa-solid fa-file-lines me-1"></i> Seleccionar página');
    });

    /* ── Utility ────────────────────────────────────────────────────── */
    function cssColorToHex(color) {
        if (!color || color === 'transparent') return null;
        if (/^#[0-9a-fA-F]{6}$/.test(color)) return color;
        const match = color.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        if (match) {
            return '#' + [match[1], match[2], match[3]]
                .map(n => parseInt(n).toString(16).padStart(2, '0'))
                .join('');
        }
        return null;
    }

    function veDebounce(fn, delay) {
        var timer;
        return function () {
            var ctx  = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    }

    /* ═══════════════════════════════════════════════════════════════
       IMPROVEMENT 1: Live preview debounced (600ms)
       ═══════════════════════════════════════════════════════════════ */
    var _vePreviewTimer = null;

    function veTriggerLivePreview() {
        if (!window._veScCurrent || !window._veScCurrent.parsed) return;
        clearTimeout(_vePreviewTimer);
        $('#ve-sc-status').html('<span style="color:#999;font-size:10px;">Vista previa…</span>');
        _vePreviewTimer = setTimeout(function () {
            var parsed    = window._veScCurrent.parsed;
            var scNodeId  = window._veScCurrent.scNodeId;
            var scMeta    = (window.veScMetaMap || {})[parsed.name] || {};
            var textContent = (!parsed.selfClosing && !scMeta.items)
                ? ($('#ve-sc-content-input').val() || '')
                : undefined;
            var previewSc = veBuildTag(parsed.name, veCollectAttrs(), veCollectItems(), parsed.selfClosing, textContent);
            var frame = document.getElementById('ve-preview-frame');
            if (!frame || !frame.contentWindow || !window.EXPAND_SHORTCODE_URL) {
                $('#ve-sc-status').text('');
                return;
            }
            $.ajax({
                url:     window.EXPAND_SHORTCODE_URL,
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data:    { shortcode: previewSc },
                success: function (resp) {
                    frame.contentWindow.postMessage({
                        type:      've-update-shortcode-sentinel',
                        scNodeId:  scNodeId,
                        encodedSc: veEncodeVeSc(previewSc),
                        html:      resp.html || '',
                    }, '*');
                    $('#ve-sc-status').text('');
                },
                error: function () {
                    $('#ve-sc-status').text('');
                },
            });
        }, 600);
    }

    $(document).on('input change', '.ve-sc-attr-input, .ve-sc-item-attr, #ve-sc-content-input', veTriggerLivePreview);

    /* ═══════════════════════════════════════════════════════════════
       IMPROVEMENT 3: Required field validation with style
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('input', '#ve-sc-attrs-list [data-required="1"]', function () {
        $(this).closest('.ve-field').removeClass('ve-field-required-error');
    });

    /* ═══════════════════════════════════════════════════════════════
       IMPROVEMENT 4: Media picker for image fields
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('click', '.ve-sc-img-pick-btn', function () {
        var attrName = $(this).data('attr');
        var $btn     = $(this);
        if (typeof window.openMediaPicker === 'function') {
            window.openMediaPicker(function (url) {
                $btn.closest('.ve-sc-image-wrap').find('.ve-sc-image-url[data-attr="' + attrName + '"]').val(url).trigger('input');
                $btn.closest('.ve-sc-image-wrap').find('.ve-sc-img-preview').html('<img src="' + url + '" class="ve-sc-img-thumb" alt="">');
            });
        } else {
            var $modal = $('#media-picker-modal, #ve-media-picker-modal');
            if ($modal.length) {
                window._vePendingImgAttr = attrName;
                window._vePendingImgBtn  = $btn;
                new bootstrap.Modal($modal[0]).show();
            }
        }
    });

    /* ═══════════════════════════════════════════════════════════════
       IMPROVEMENT 5: Duplicate sentinel
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('click', '#btn-sc-duplicate', function () {
        if (!window._veScCurrent) return;
        var frame = document.getElementById('ve-preview-frame');
        if (!frame) return;
        frame.contentWindow.postMessage({
            type:    've-duplicate-sentinel',
            scNodeId: window._veScCurrent.scNodeId,
        }, '*');
    });

    /* ═══════════════════════════════════════════════════════════════
       IMPROVEMENT 6: Move sentinel up/down
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('click', '#btn-sc-move-up', function () {
        if (!window._veScCurrent) return;
        document.getElementById('ve-preview-frame').contentWindow.postMessage({
            type:      've-move-sentinel',
            scNodeId:  window._veScCurrent.scNodeId,
            direction: 'up',
        }, '*');
    });

    $(document).on('click', '#btn-sc-move-down', function () {
        if (!window._veScCurrent) return;
        document.getElementById('ve-preview-frame').contentWindow.postMessage({
            type:      've-move-sentinel',
            scNodeId:  window._veScCurrent.scNodeId,
            direction: 'down',
        }, '*');
    });

    /* ═══════════════════════════════════════════════════════════════
       IMPROVEMENT 11: Copy/paste styles (enhanced)
       ═══════════════════════════════════════════════════════════════ */
    var _veCopiedStyles = null;

    $('#btn-copy-styles').off('click').on('click', function () {
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !window._veCurrentNodeId) return;
        frame.contentWindow.postMessage({ type: 've-get-computed-styles', nodeId: window._veCurrentNodeId }, '*');
    });

    window.addEventListener('message', function (ev) {
        if (ev.data && ev.data.type === 've-computed-styles-result') {
            _veCopiedStyles = ev.data.styles;
            $('#btn-paste-styles').prop('disabled', false);
            var $status = $('<small>').text('Estilos copiados').css({ color: '#999', fontSize: '10px' });
            $('#ve-inspector-sections').find('.ve-panel-header-row').after($status);
            setTimeout(function () { $status.fadeOut(400, function () { $(this).remove(); }); }, 2000);
        }
    });

    $('#btn-paste-styles').off('click').on('click', function () {
        if (!_veCopiedStyles || !window._veCurrentNodeId) return;
        var frame = document.getElementById('ve-preview-frame');
        if (!frame) return;
        frame.contentWindow.postMessage({
            type:    've-apply-styles',
            nodeId:  window._veCurrentNodeId,
            styles:  _veCopiedStyles,
        }, '*');
    });

    /* ═══════════════════════════════════════════════════════════════
       IMPROVEMENT 12: CSS undo history
       ═══════════════════════════════════════════════════════════════ */
    window._veCssHistory = [];

    var _origApplyStyle = applyStyle;
    applyStyle = function (prop, value) {
        // Record previous value before applying
        var prevVal = (window._veLastSelectedStyles || {})[prop] || '';
        _origApplyStyle(prop, value);
        if (window._veCurrentNodeId && prop && value !== prevVal) {
            window._veCssHistory.push({
                nodeId:  window._veCurrentNodeId,
                prop:    prop,
                prevVal: prevVal,
                newVal:  value,
            });
            $('#btn-undo-style').removeClass('ve-hidden');
        }
    };

    $('#btn-undo-style').on('click', function () {
        if (!window._veCssHistory || !window._veCssHistory.length) return;
        var last  = window._veCssHistory.pop();
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({
                type:  've-apply-style',
                prop:  last.prop,
                value: last.prevVal,
            }, '*');
        }
        if (!window._veCssHistory.length) {
            $('#btn-undo-style').addClass('ve-hidden');
        }
    });

    /* ═══════════════════════════════════════════════════════════════
       IMPROVEMENT 13: Bootstrap quick classes chip handler
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('click', '.ve-bs-chip', function () {
        var cls   = $(this).data('class');
        var frame = document.getElementById('ve-preview-frame');
        if (!window._veCurrentNodeId || !frame) return;
        frame.contentWindow.postMessage({
            type:      've-toggle-class',
            nodeId:    window._veCurrentNodeId,
            className: cls,
        }, '*');
        $(this).toggleClass('active');
    });

    /* ═══════════════════════════════════════════════════════════════
       IMPROVEMENT 14: Dynamic unit selector
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('click', '.ve-unit-toggle', function () {
        var units   = $(this).data('units').split(',');
        var cur     = $(this).text().trim();
        var idx     = units.indexOf(cur);
        var next    = units[(idx + 1) % units.length];
        var $input  = $(this).closest('.input-group').find('.ve-css-prop');
        var val     = parseFloat($input.val()) || 0;
        var pxVal   = cur === 'px'  ? val
                    : cur === 'rem' ? val * 16
                    : cur === 'em'  ? val * 16
                    : cur === '%'   ? val
                    : val;
        var newVal  = next === 'px'  ? pxVal
                    : next === 'rem' ? +(pxVal / 16).toFixed(2)
                    : next === 'em'  ? +(pxVal / 16).toFixed(2)
                    : next === '%'   ? +(pxVal).toFixed(1)
                    : val;
        $input.val(newVal).data('unit', next).trigger('change');
        $(this).text(next);
    });

    /* ═══════════════════════════════════════════════════════════════
       IMPROVEMENT 19: Save/restore inspector scroll position
       ═══════════════════════════════════════════════════════════════ */
    var PAGE_ID = '{{ $page->id ?? 0 }}';
    var $panelScroll = $('#ve-inspector-panel');

    $panelScroll.on('scroll', veDebounce(function () {
        localStorage.setItem('ve-inspector-scroll-' + PAGE_ID, $(this).scrollTop());
    }, 300));

    var _savedScroll = localStorage.getItem('ve-inspector-scroll-' + PAGE_ID);
    if (_savedScroll) {
        setTimeout(function () {
            $panelScroll.scrollTop(parseInt(_savedScroll));
        }, 500);
    }

    /* ── CSS Filters ───────────────────────────────────────────────── */
    function buildFilterString() {
        var filters = [];
        $('.ve-filter-range').each(function () {
            var f = $(this).data('filter');
            var u = $(this).data('unit');
            var v = parseFloat($(this).val());
            if ((f === 'brightness' || f === 'contrast' || f === 'saturate') && v === 100) return;
            if ((f === 'blur' || f === 'grayscale' || f === 'hue-rotate') && v === 0) return;
            if (f === 'opacity' && v === 100) return;
            var val  = (u === '%') ? (v / 100) : v;
            var unit = (u === '%') ? '' : u;
            filters.push(f + '(' + val + unit + ')');
        });
        return filters.length ? filters.join(' ') : 'none';
    }

    $(document).on('input', '.ve-filter-range', function () {
        var f = $(this).data('filter');
        var u = $(this).data('unit');
        $('#flt-' + f + '-val').text($(this).val() + u);
    });

    $(document).on('click', '#btn-apply-filters', function () {
        applyStyle('filter', buildFilterString());
    });

    $(document).on('click', '#btn-reset-filters', function () {
        var defaults = { blur: 0, brightness: 100, contrast: 100, saturate: 100, grayscale: 0, 'hue-rotate': 0, opacity: 100 };
        $('.ve-filter-range').each(function () {
            var f = $(this).data('filter');
            $(this).val(defaults[f] || 0).trigger('input');
        });
        applyStyle('filter', 'none');
    });

    /* ── CSS Transitions ────────────────────────────────────────────── */
    $(document).on('input', '#tr-duration', function () {
        $('#tr-dur-val').text($(this).val() + 'ms');
    });

    $(document).on('input', '#tr-delay', function () {
        $('#tr-delay-val').text($(this).val() + 'ms');
    });

    $(document).on('click', '#btn-apply-transition', function () {
        var val = $('#tr-property').val() + ' ' + $('#tr-duration').val() + 'ms ' + $('#tr-easing').val() + ' ' + $('#tr-delay').val() + 'ms';
        applyStyle('transition', val);
    });

    $(document).on('click', '#btn-clear-transition', function () {
        applyStyle('transition', 'none');
    });

    /* ── Aspect ratio ───────────────────────────────────────────────── */
    $(document).on('click', '.ve-aspect-btn', function () {
        $('.ve-aspect-btn').removeClass('active');
        $(this).addClass('active');
        applyStyle('aspect-ratio', $(this).data('ratio'));
    });

})(jQuery);
</script>
