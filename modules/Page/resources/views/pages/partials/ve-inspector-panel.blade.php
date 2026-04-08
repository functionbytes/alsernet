<div id="ve-inspector-panel" style="height:100%; display:flex; flex-direction:column; overflow:hidden;">

    {{-- Header --}}
    <div style="padding:10px 12px; border-bottom:1px solid #e9ecef; background:#f8f9fa; flex-shrink:0;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
            <div>
                <div style="font-size:10px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:.5px;">ELEMENTO</div>
                <span id="ve-inspector-element-name" style="font-size:13px; font-weight:700; color:#333;">Ninguno</span>
            </div>
            <div style="display:flex; gap:4px;">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-copy-styles"
                        title="Copiar estilos" style="font-size:11px; padding:2px 8px;">
                    <i class="fa-duotone fa-solid fa-copy"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-paste-styles"
                        title="Pegar estilos" style="font-size:11px; padding:2px 8px;" disabled>
                    <i class="fa-duotone fa-solid fa-paste"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-hide-element"
                        title="Ocultar elemento" style="font-size:11px; padding:2px 8px; display:none;">
                    <i class="fa-duotone fa-solid fa-eye-slash"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-deselect-element"
                        title="Deseleccionar" style="font-size:11px; padding:2px 8px;">
                    <i class="fa-duotone fa-solid fa-times"></i>
                </button>
            </div>
        </div>
        {{-- Breadcrumb --}}
        <div id="ve-inspector-breadcrumb"
             style="display:none; background:#fff; border:1px solid #e9ecef; border-radius:4px; padding:4px 8px; font-size:11px; overflow-x:auto; white-space:nowrap; margin-top:4px;">
        </div>
    </div>

    {{-- Empty state --}}
    <div id="ve-inspector-empty" style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#aaa; text-align:center; padding:24px;">
        <div style="font-size:12px; line-height:1.5;">Haz click en un elemento<br>del preview para inspeccionarlo</div>
    </div>

    {{-- Sections (shown when element selected) --}}
    <div id="ve-inspector-sections" style="display:none; flex:1; overflow-y:auto;">

        {{-- Visibility --}}
        <div id="ve-section-visibility" style="display:none;">
            <div class="ve-section-header" data-target="ve-sect-visibility">
                <span>Visibilidad</span>
                <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-visibility" style="padding:10px 12px;">
                <div style="font-size:11px; color:#666; margin-bottom:8px;">Ocultar en dispositivos:</div>
                <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary ve-visibility-btn"
                            data-class="d-none d-sm-block" title="Ocultar en móvil">
                        Móvil
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ve-visibility-btn"
                            data-class="d-sm-none d-md-block" title="Ocultar en tablet">
                        Tablet
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ve-visibility-btn"
                            data-class="d-md-none d-lg-block" title="Ocultar en desktop">
                        Desktop
                    </button>
                </div>
                <div style="border-top:1px solid #f0f0f0; padding-top:8px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="btn-toggle-hidden"
                            style="font-size:12px;">
                        Ocultar elemento
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-1" id="btn-toggle-shown"
                            style="font-size:12px; display:none;">
                        Mostrar elemento
                    </button>
                </div>
            </div>
        </div>

        {{-- Attributes --}}
        <div id="ve-section-attributes" style="display:none;">
            <div class="ve-section-header collapsed" data-target="ve-sect-attrs">
                <span>Atributos HTML</span>
                <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-attrs" style="display:none; padding:10px 12px;">
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">ID</label>
                    <input type="text" class="form-control form-control-sm" id="ve-attr-id" placeholder="elemento-id">
                </div>
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Clases CSS</label>
                    <input type="text" class="form-control form-control-sm" id="ve-attr-class" placeholder="clase1 clase2">
                </div>
                <div id="ve-attr-src-wrap" style="display:none; margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">src</label>
                    <input type="text" class="form-control form-control-sm" id="ve-attr-src" placeholder="https://...">
                </div>
                <div id="ve-attr-alt-wrap" style="display:none; margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">alt</label>
                    <input type="text" class="form-control form-control-sm" id="ve-attr-alt" placeholder="Texto alternativo">
                </div>
                <div id="ve-attr-href-wrap" style="display:none; margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">href</label>
                    <input type="url" class="form-control form-control-sm" id="ve-attr-href" placeholder="https://...">
                </div>
                <div id="ve-attr-placeholder-wrap" style="display:none; margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">placeholder</label>
                    <input type="text" class="form-control form-control-sm" id="ve-attr-placeholder" placeholder="Placeholder...">
                </div>
                <button type="button" class="btn btn-sm w-100 mt-2" id="btn-apply-attrs"
                        style="background:#1a1a1a;border-color:#1a1a1a;color:#fff;font-size:12px;">
                    Aplicar atributos
                </button>
            </div>
        </div>

        {{-- Link (only for <a>) --}}
        <div id="ve-section-link-wrapper" style="display:none;">
            <div class="ve-section-header" data-target="ve-sect-link">
                <span>Enlace</span>
                <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-link" style="padding:10px 12px;">
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">URL</label>
                    <input type="url" class="form-control form-control-sm" id="ve-link-href-inspector" placeholder="https://...">
                </div>
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Abrir en</label>
                    <select class="form-select form-select-sm" id="ve-link-target-inspector">
                        <option value="">Misma pestaña</option>
                        <option value="_blank">Nueva pestaña</option>
                    </select>
                </div>
                <button type="button" class="btn btn-sm w-100" id="btn-apply-link-inspector"
                        style="background:#1a1a1a;border-color:#1a1a1a;color:#fff;font-size:12px;">
                    Aplicar enlace
                </button>
            </div>
        </div>

        {{-- Typography --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header" data-target="ve-sect-typo">
                <span>Tipografía</span>
                <div style="display:flex;align-items:center;gap:4px;">
                    <button type="button" class="ve-reset-section-btn" data-section="typo"
                            title="Restablecer tipografía"
                            style="background:transparent;border:none;color:#bbb;padding:1px 5px;font-size:10px;border-radius:3px;cursor:pointer;line-height:1.6;"
                            onclick="event.stopPropagation()"><i class="fa-duotone fa-solid fa-undo-alt"></i></button>
                    <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-typo" style="padding:10px 12px;">
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Familia de fuente</label>
                    <select class="form-select form-select-sm ve-css-prop" data-prop="font-family">
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
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                    <div>
                        <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Tamaño</label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control ve-css-prop" data-prop="font-size" data-unit="px" placeholder="16" min="8" max="200">
                            <span class="input-group-text" style="font-size:10px;">px</span>
                        </div>
                    </div>
                    <div>
                        <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Peso</label>
                        <select class="form-select form-select-sm ve-css-prop" data-prop="font-weight">
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
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Color de texto</label>
                    <div style="display:flex; gap:6px;">
                        <input type="color" class="form-control form-control-sm form-control-color ve-css-prop"
                               data-prop="color" value="#000000" style="width:44px; padding:2px;">
                        <input type="text" class="form-control form-control-sm ve-color-text"
                               placeholder="#000000" style="font-family:monospace; font-size:11px;">
                    </div>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Alineación</label>
                    <div class="btn-group btn-group-sm w-100">
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-align" data-value="left" title="Izquierda"><i class="fa-duotone fa-solid fa-align-left"></i></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-align" data-value="center" title="Centro"><i class="fa-duotone fa-solid fa-align-center"></i></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-align" data-value="right" title="Derecha"><i class="fa-duotone fa-solid fa-align-right"></i></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-align" data-value="justify" title="Justificado"><i class="fa-duotone fa-solid fa-align-justify"></i></button>
                    </div>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">
                        Interlineado <small class="ve-range-value" style="color:#1a1a1a;">1.5</small>
                    </label>
                    <input type="range" class="form-range ve-css-prop"
                           data-prop="line-height" data-unit=""
                           min="1" max="3" step="0.1" value="1.5">
                </div>
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Transformación</label>
                    <select class="form-select form-select-sm ve-css-prop" data-prop="text-transform">
                        <option value="">Normal</option>
                        <option value="uppercase">MAYÚSCULAS</option>
                        <option value="lowercase">minúsculas</option>
                        <option value="capitalize">Capitalizar</option>
                    </select>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Decoración</label>
                    <div class="btn-group btn-group-sm w-100">
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-decoration" data-value="none" title="Sin decoración"><i class="fa-duotone fa-solid fa-minus"></i></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-decoration" data-value="underline" title="Subrayado"><u>S</u></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-decoration" data-value="line-through" title="Tachado"><s>T</s></button>
                        <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="text-decoration" data-value="overline" title="Línea superior"><span style="text-decoration:overline;">L</span></button>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">
                        Espaciado letras <small class="ve-range-value" style="color:#1a1a1a;">0px</small>
                    </label>
                    <input type="range" class="form-range ve-css-prop" data-prop="letter-spacing" data-unit="px" min="-3" max="15" step="0.5" value="0">
                </div>
            </div>
        </div>

        {{-- Spacing --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-spacing">
                <span>Espaciado</span>
                <div style="display:flex;align-items:center;gap:4px;">
                    <button type="button" class="ve-reset-section-btn" data-section="spacing"
                            title="Restablecer espaciado"
                            style="background:transparent;border:none;color:#bbb;padding:1px 5px;font-size:10px;border-radius:3px;cursor:pointer;line-height:1.6;"
                            onclick="event.stopPropagation()"><i class="fa-duotone fa-solid fa-undo-alt"></i></button>
                    <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-spacing" style="display:none; padding:10px 12px;">
                <div style="font-size:10px; font-weight:600; color:#888; text-align:center; margin-bottom:8px; text-transform:uppercase;">Padding (interior)</div>
                <div style="display:grid; grid-template-columns:1fr 56px 1fr; grid-template-rows:auto 56px auto; gap:4px; max-width:176px; margin:0 auto 12px;">
                    <div></div>
                    <input type="number" class="form-control form-control-sm text-center ve-css-prop" data-prop="padding-top" data-unit="px" placeholder="0" min="0" style="font-size:11px;">
                    <div></div>
                    <input type="number" class="form-control form-control-sm text-center ve-css-prop" data-prop="padding-left" data-unit="px" placeholder="0" min="0" style="font-size:11px;">
                    <div style="background:#e9ecef; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:11px; color:#aaa;">P</div>
                    <input type="number" class="form-control form-control-sm text-center ve-css-prop" data-prop="padding-right" data-unit="px" placeholder="0" min="0" style="font-size:11px;">
                    <div></div>
                    <input type="number" class="form-control form-control-sm text-center ve-css-prop" data-prop="padding-bottom" data-unit="px" placeholder="0" min="0" style="font-size:11px;">
                    <div></div>
                </div>
                <div style="font-size:10px; font-weight:600; color:#888; text-align:center; margin-bottom:8px; text-transform:uppercase;">Margin (exterior)</div>
                <div style="display:grid; grid-template-columns:1fr 56px 1fr; grid-template-rows:auto 56px auto; gap:4px; max-width:176px; margin:0 auto;">
                    <div></div>
                    <input type="number" class="form-control form-control-sm text-center ve-css-prop" data-prop="margin-top" data-unit="px" placeholder="0" style="font-size:11px;">
                    <div></div>
                    <input type="number" class="form-control form-control-sm text-center ve-css-prop" data-prop="margin-left" data-unit="px" placeholder="0" style="font-size:11px;">
                    <div style="background:#e9ecef; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:11px; color:#aaa;">M</div>
                    <input type="number" class="form-control form-control-sm text-center ve-css-prop" data-prop="margin-right" data-unit="px" placeholder="0" style="font-size:11px;">
                    <div></div>
                    <input type="number" class="form-control form-control-sm text-center ve-css-prop" data-prop="margin-bottom" data-unit="px" placeholder="0" style="font-size:11px;">
                    <div></div>
                </div>
            </div>
        </div>

        {{-- Background --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-bg">
                <span>Fondo</span>
                <div style="display:flex;align-items:center;gap:4px;">
                    <button type="button" class="ve-reset-section-btn" data-section="bg"
                            title="Restablecer fondo"
                            style="background:transparent;border:none;color:#bbb;padding:1px 5px;font-size:10px;border-radius:3px;cursor:pointer;line-height:1.6;"
                            onclick="event.stopPropagation()"><i class="fa-duotone fa-solid fa-undo-alt"></i></button>
                    <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-bg" style="display:none; padding:10px 12px;">
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Color de fondo</label>
                    <div style="display:flex; gap:6px;">
                        <input type="color" class="form-control form-control-sm form-control-color ve-css-prop"
                               data-prop="background-color" value="#ffffff" style="width:44px; padding:2px;">
                        <input type="text" class="form-control form-control-sm ve-color-text"
                               placeholder="transparent" style="font-family:monospace; font-size:11px;">
                    </div>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">
                        Opacidad <small class="ve-range-value" style="color:#1a1a1a;">1</small>
                    </label>
                    <input type="range" class="form-range ve-css-prop"
                           data-prop="opacity" data-unit=""
                           min="0" max="1" step="0.05" value="1">
                </div>
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Imagen de fondo (archivo)</label>
                    <div class="input-group input-group-sm">
                        <input type="file" class="form-control" id="ve-bg-image-file" accept="image/*" style="font-size:11px;">
                        <button class="btn btn-outline-secondary" type="button" id="btn-clear-bg-image" title="Quitar imagen">
                            <i class="fa-duotone fa-solid fa-times"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Imagen de fondo (URL)</label>
                    <div class="input-group input-group-sm">
                        <input type="url" class="form-control" id="ve-bg-image-url" placeholder="https://...">
                        <button class="btn btn-outline-secondary" type="button" id="btn-apply-bg-url" title="Aplicar URL">
                            <i class="fa-duotone fa-solid fa-check"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Border --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-border">
                <span>Borde</span>
                <div style="display:flex;align-items:center;gap:4px;">
                    <button type="button" class="ve-reset-section-btn" data-section="border"
                            title="Restablecer borde"
                            style="background:transparent;border:none;color:#bbb;padding:1px 5px;font-size:10px;border-radius:3px;cursor:pointer;line-height:1.6;"
                            onclick="event.stopPropagation()"><i class="fa-duotone fa-solid fa-undo-alt"></i></button>
                    <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-border" style="display:none; padding:10px 12px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                    <div>
                        <label style="font-size:10px; color:#888; display:block; margin-bottom:3px;">Grosor</label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control ve-css-prop" data-prop="border-width" data-unit="px" placeholder="0" min="0">
                            <span class="input-group-text" style="font-size:10px;">px</span>
                        </div>
                    </div>
                    <div>
                        <label style="font-size:10px; color:#888; display:block; margin-bottom:3px;">Estilo</label>
                        <select class="form-select form-select-sm ve-css-prop" data-prop="border-style">
                            <option value="none">Ninguno</option>
                            <option value="solid">Sólido</option>
                            <option value="dashed">Discontinuo</option>
                            <option value="dotted">Puntos</option>
                            <option value="double">Doble</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="font-size:10px; color:#888; display:block; margin-bottom:3px;">Color del borde</label>
                    <input type="color" class="form-control form-control-sm form-control-color ve-css-prop"
                           data-prop="border-color" value="#dee2e6" style="width:100%;">
                </div>
                <div>
                    <label style="font-size:10px; color:#888; display:block; margin-bottom:3px;">
                        Esquinas redondeadas <small class="ve-range-value" style="color:#1a1a1a;">0px</small>
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
                <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-shadow" style="display:none; padding:10px 12px;">
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:6px;">Sombra de caja</label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:6px;">
                        <div>
                            <label style="font-size:10px; color:#888;">Offset X</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control ve-shadow-input" id="ve-shadow-x" placeholder="0">
                                <span class="input-group-text" style="font-size:10px;">px</span>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:10px; color:#888;">Offset Y</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control ve-shadow-input" id="ve-shadow-y" placeholder="4">
                                <span class="input-group-text" style="font-size:10px;">px</span>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:10px; color:#888;">Difuminado</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control ve-shadow-input" id="ve-shadow-blur" placeholder="8" min="0">
                                <span class="input-group-text" style="font-size:10px;">px</span>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:10px; color:#888;">Expansión</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control ve-shadow-input" id="ve-shadow-spread" placeholder="0">
                                <span class="input-group-text" style="font-size:10px;">px</span>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; gap:6px; margin-bottom:6px; align-items:center;">
                        <input type="color" class="form-control form-control-sm form-control-color ve-shadow-input" id="ve-shadow-color" value="#000000" style="width:44px; padding:2px;">
                        <label style="font-size:10px; color:#888; margin:0;">Color</label>
                        <div class="form-check ms-auto mb-0">
                            <input class="form-check-input ve-shadow-input" type="checkbox" id="ve-shadow-inset">
                            <label class="form-check-label" for="ve-shadow-inset" style="font-size:11px; color:#555;">Interior</label>
                        </div>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <button type="button" class="btn btn-sm btn-primary flex-grow-1" id="btn-apply-shadow"
                                style="background:#1a1a1a;border-color:#1a1a1a;font-size:12px;">
                            Aplicar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-clear-shadow"
                                style="font-size:12px;" title="Quitar sombra">
                            <i class="fa-duotone fa-solid fa-times"></i>
                        </button>
                    </div>
                </div>
                <div style="border-top:1px solid #f0f0f0; padding-top:10px; margin-top:4px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:6px;">Sombra de texto</label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:6px;">
                        <div>
                            <label style="font-size:10px; color:#888;">Offset X</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" id="ve-tshadow-x" placeholder="0">
                                <span class="input-group-text" style="font-size:10px;">px</span>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:10px; color:#888;">Offset Y</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" id="ve-tshadow-y" placeholder="1">
                                <span class="input-group-text" style="font-size:10px;">px</span>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:10px; color:#888;">Difuminado</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" id="ve-tshadow-blur" placeholder="2" min="0">
                                <span class="input-group-text" style="font-size:10px;">px</span>
                            </div>
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <input type="color" class="form-control form-control-sm form-control-color" id="ve-tshadow-color" value="#000000" style="width:44px; padding:2px;">
                        </div>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <button type="button" class="btn btn-sm btn-primary flex-grow-1" id="btn-apply-tshadow"
                                style="background:#1a1a1a;border-color:#1a1a1a;font-size:12px;">
                            Aplicar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-clear-tshadow"
                                style="font-size:12px;" title="Quitar sombra">
                            <i class="fa-duotone fa-solid fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Size --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-size">
                <span>Tamaño</span>
                <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-size" style="display:none; padding:10px 12px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                    <div>
                        <label style="font-size:10px; color:#888; display:block; margin-bottom:3px;">Ancho</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control ve-css-prop" data-prop="width" data-unit="" placeholder="auto">
                            <span class="input-group-text" style="font-size:10px;">px/%</span>
                        </div>
                    </div>
                    <div>
                        <label style="font-size:10px; color:#888; display:block; margin-bottom:3px;">Alto</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control ve-css-prop" data-prop="height" data-unit="" placeholder="auto">
                            <span class="input-group-text" style="font-size:10px;">px/%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Position --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-position">
                <span>Posición</span>
                <div style="display:flex;align-items:center;gap:4px;">
                    <button type="button" class="ve-reset-section-btn" data-section="position"
                            title="Restablecer posición"
                            style="background:transparent;border:none;color:#bbb;padding:1px 5px;font-size:10px;border-radius:3px;cursor:pointer;line-height:1.6;"
                            onclick="event.stopPropagation()"><i class="fa-duotone fa-solid fa-undo-alt"></i></button>
                    <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-position" style="display:none; padding:10px 12px;">
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Posición</label>
                    <select class="form-select form-select-sm ve-css-prop" data-prop="position" id="ve-position-select">
                        <option value="">Normal (static)</option>
                        <option value="relative">Relativa</option>
                        <option value="absolute">Absoluta</option>
                        <option value="fixed">Fija</option>
                        <option value="sticky">Sticky</option>
                    </select>
                </div>
                <div id="ve-position-coords" style="display:none; margin-bottom:8px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                        <div>
                            <label style="font-size:10px; color:#888; display:block; margin-bottom:2px;">Top</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control ve-css-prop" data-prop="top" data-unit="px" placeholder="auto">
                                <span class="input-group-text" style="font-size:10px;">px</span>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:10px; color:#888; display:block; margin-bottom:2px;">Right</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control ve-css-prop" data-prop="right" data-unit="px" placeholder="auto">
                                <span class="input-group-text" style="font-size:10px;">px</span>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:10px; color:#888; display:block; margin-bottom:2px;">Bottom</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control ve-css-prop" data-prop="bottom" data-unit="px" placeholder="auto">
                                <span class="input-group-text" style="font-size:10px;">px</span>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:10px; color:#888; display:block; margin-bottom:2px;">Left</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control ve-css-prop" data-prop="left" data-unit="px" placeholder="auto">
                                <span class="input-group-text" style="font-size:10px;">px</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Z-index</label>
                    <input type="number" class="form-control form-control-sm ve-css-prop" data-prop="z-index" data-unit="" placeholder="auto" step="1">
                </div>
            </div>
        </div>

        {{-- Flexbox / Grid --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-flex">
                <span>Display / Flex</span>
                <div style="display:flex;align-items:center;gap:4px;">
                    <button type="button" class="ve-reset-section-btn" data-section="flex"
                            title="Restablecer flex"
                            style="background:transparent;border:none;color:#bbb;padding:1px 5px;font-size:10px;border-radius:3px;cursor:pointer;line-height:1.6;"
                            onclick="event.stopPropagation()"><i class="fa-duotone fa-solid fa-undo-alt"></i></button>
                    <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
                </div>
            </div>
            <div id="ve-sect-flex" style="display:none; padding:10px 12px;">
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Display</label>
                    <select class="form-select form-select-sm ve-css-prop" data-prop="display" id="ve-display-select">
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
                <div id="ve-flex-controls" style="display:none;">
                    <div style="margin-bottom:8px;">
                        <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Dirección</label>
                        <div class="btn-group btn-group-sm w-100">
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="row" title="Fila →"><i class="fa-duotone fa-solid fa-arrow-right"></i></button>
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="column" title="Columna ↓"><i class="fa-duotone fa-solid fa-arrow-down"></i></button>
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="row-reverse" title="Fila inversa ←"><i class="fa-duotone fa-solid fa-arrow-left"></i></button>
                            <button type="button" class="btn btn-outline-secondary ve-css-btn" data-prop="flex-direction" data-value="column-reverse" title="Columna inversa ↑"><i class="fa-duotone fa-solid fa-arrow-up"></i></button>
                        </div>
                    </div>
                    <div style="margin-bottom:8px;">
                        <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Alineación horizontal</label>
                        <select class="form-select form-select-sm ve-css-prop" data-prop="justify-content">
                            <option value="">Normal</option>
                            <option value="flex-start">Inicio</option>
                            <option value="center">Centro</option>
                            <option value="flex-end">Final</option>
                            <option value="space-between">Space between</option>
                            <option value="space-around">Space around</option>
                            <option value="space-evenly">Space evenly</option>
                        </select>
                    </div>
                    <div style="margin-bottom:8px;">
                        <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Alineación vertical</label>
                        <select class="form-select form-select-sm ve-css-prop" data-prop="align-items">
                            <option value="">Normal</option>
                            <option value="stretch">Stretch</option>
                            <option value="flex-start">Inicio</option>
                            <option value="center">Centro</option>
                            <option value="flex-end">Final</option>
                            <option value="baseline">Baseline</option>
                        </select>
                    </div>
                    <div style="margin-bottom:8px;">
                        <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Wrap</label>
                        <select class="form-select form-select-sm ve-css-prop" data-prop="flex-wrap">
                            <option value="">nowrap</option>
                            <option value="wrap">wrap</option>
                            <option value="wrap-reverse">wrap-reverse</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">Gap</label>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control ve-css-prop" data-prop="gap" data-unit="px" placeholder="0" min="0">
                        <span class="input-group-text" style="font-size:10px;">px</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Transform --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-transform">
                <span>Transform</span>
                <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-transform" style="display:none; padding:10px 12px;">
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">
                        Rotar <small id="ve-rotate-val" style="color:#1a1a1a;">0°</small>
                    </label>
                    <input type="range" id="ve-transform-rotate" min="-180" max="180" step="1" value="0" class="form-range">
                </div>
                <div style="margin-bottom:8px;">
                    <label style="font-size:11px; color:#666; display:block; margin-bottom:3px;">
                        Escala <small id="ve-scale-val" style="color:#1a1a1a;">1×</small>
                    </label>
                    <input type="range" id="ve-transform-scale" min="0.1" max="3" step="0.05" value="1" class="form-range">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:10px;">
                    <div>
                        <label style="font-size:10px; color:#888; display:block; margin-bottom:2px;">Translate X</label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" id="ve-transform-tx" placeholder="0">
                            <span class="input-group-text" style="font-size:10px;">px</span>
                        </div>
                    </div>
                    <div>
                        <label style="font-size:10px; color:#888; display:block; margin-bottom:2px;">Translate Y</label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" id="ve-transform-ty" placeholder="0">
                            <span class="input-group-text" style="font-size:10px;">px</span>
                        </div>
                    </div>
                </div>
                <div style="display:flex; gap:6px;">
                    <button type="button" class="btn btn-sm flex-grow-1" id="btn-apply-transform"
                            style="background:#1a1a1a;border-color:#1a1a1a;color:#fff;font-size:12px;">
                        Aplicar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-clear-transform" title="Quitar transform">
                        <i class="fa-duotone fa-solid fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Custom CSS --}}
        <div class="ve-inspector-section">
            <div class="ve-section-header collapsed" data-target="ve-sect-css">
                <span>CSS personalizado</span>
                <i class="fa-duotone fa-solid fa-chevron-down ve-section-chevron"></i>
            </div>
            <div id="ve-sect-css" style="display:none; padding:10px 12px;">
                <textarea class="form-control" id="ve-custom-css" rows="5"
                          style="font-family:monospace; font-size:11px;"
                          placeholder="color: red;&#10;margin-top: 20px;"></textarea>
                <button type="button" class="btn btn-sm btn-primary mt-2 w-100" id="btn-apply-custom-css"
                        style="background:#1a1a1a;border-color:#1a1a1a;font-size:12px;">
                    Aplicar CSS
                </button>
            </div>
        </div>

    </div>
</div>

<style>
.ve-section-header { cursor: pointer; }
.ve-section-header:hover { background: #f0f0f5 !important; }
.ve-section-header > span {
    font-size: 12px;
    color: #444;
    font-weight: 600;
}
#ve-inspector-sections label {
    font-size: 11px !important;
    color: #555 !important;
}
</style>

<script>
(function ($) {
    'use strict';

    /* ── Collapsible sections ───────────────────────────────────────── */
    $(document).on('click', '.ve-section-header', function () {
        const targetId = $(this).data('target');
        const $target  = $('#' + targetId);
        const $chevron = $(this).find('.ve-section-chevron');

        if ($target.is(':visible')) {
            $target.slideUp(150);
            $(this).addClass('collapsed');
        } else {
            $target.slideDown(150);
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
                $('#ve-section-link-wrapper').show();
                $('#ve-link-href-inspector').val(e.data.href || '');
                $('#ve-link-target-inspector').val(e.data.linkTarget || '');
            } else {
                $('#ve-section-link-wrapper').hide();
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
        if (p.showToast) p.showToast('<i class="fa-duotone fa-solid fa-copy me-1"></i>Estilos copiados');
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
        if (p.showToast) p.showToast('<i class="fa-duotone fa-solid fa-paste me-1"></i>Estilos aplicados');
    });

    function selectElement(tag, styles) { showElementInfo(tag, styles || {}, {}); }

    const GOOGLE_FONTS = ['Roboto','Open Sans','Lato','Montserrat','Poppins','Inter','Playfair Display'];

    function showElementInfo(tag, styles, data) {
        data = data || {};
        $('#ve-inspector-element-name').text('<' + tag + '>');
        $('#ve-inspector-empty').hide();
        $('#ve-inspector-sections').show();
        $('#ve-section-visibility').show();
        $('#ve-section-attributes').show();
        $('#btn-hide-element').show();
        populateFields(styles);
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
    }

    function deselect() {
        $('#ve-inspector-element-name').text('Ninguno');
        $('#ve-inspector-sections').hide();
        $('#ve-inspector-empty').show();
        $('#ve-section-visibility').hide();
        $('#ve-section-attributes').hide();
        $('#btn-hide-element').hide();
        $('#ve-inspector-breadcrumb').hide();
        clearFields();
    }

    $('#btn-deselect-element').on('click', function () {
        deselect();
        const frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage({ type: 've-deselect' }, '*');
    });

    /* ── Populate fields ────────────────────────────────────────────── */
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
        $('#btn-toggle-hidden').hide();
        $('#btn-toggle-shown').show();
        $('#btn-hide-element').hide();
    });

    $('#btn-toggle-shown').on('click', function () {
        applyStyle('display', '');
        $('#btn-toggle-hidden').show();
        $('#btn-toggle-shown').hide();
        $('#btn-hide-element').show();
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

})(jQuery);
</script>
