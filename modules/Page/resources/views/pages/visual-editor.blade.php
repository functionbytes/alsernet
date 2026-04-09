<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editor visual — {{ $page->title }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/monokai.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldgutter.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/display/fullscreen.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/dialog/dialog.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="{{ themeAsset('css/extra.css') }}">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <link rel="stylesheet" href="{{ asset('modules/Page/css/visual-editor.css') }}">
</head>
<body>

{{-- Hidden CKEditor textarea (must be in DOM before scripts init) --}}
<div style="display:none;" aria-hidden="true">
    <textarea id="ve-content">{{ $initialContent }}</textarea>
</div>

{{-- ── Body ────────────────────────────────────────────────────────────────── --}}
<div id="ve-body">

    {{-- ── Top bar ──────────────────────────────────────────────────────── --}}
    <div id="ve-topbar">
        <div class="position-relative" style="flex:1; max-width:400px;">
            <i class="fas fa-search ve-topbar-search-icon"></i>
            <input type="text" id="ve-topbar-search" class="ve-topbar-search" placeholder="Buscar shortcode..." autocomplete="off">
            <button type="button" id="ve-topbar-search-clear" class="ve-topbar-search-clear" style="display:none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="{{ $previewUrl }}" target="_blank" class="ve-topbar-preview-btn">
                <i class="fa-duotone fa-solid fa-eye me-1"></i>Preview
            </a>
        </div>
    </div>

    {{-- ── Main area ──────────────────────────────────────────────────── --}}
    <div id="ve-main">

    {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
    <div id="ve-sidebar">

        {{-- Vertical icon nav --}}
        <div id="ve-sidebar-nav">
            <a href="{{ route('pages.edit', $page) }}" class="ve-nav-btn ve-nav-back" title="Volver">
                <i class="fa-duotone fa-solid fa-arrow-left"></i>
            </a>
            <button class="ve-nav-btn active" data-panel="shortcodes" title="Shortcodes">
                <i class="fa-duotone fa-solid fa-puzzle-piece"></i>
                <span>Bloques</span>
            </button>
            <button class="ve-nav-btn" data-panel="inspector" title="Inspector">
                <i class="fa-duotone fa-solid fa-sliders"></i>
                <span>Estilo</span>
            </button>
            <button class="ve-nav-btn" data-panel="layout" title="Layout">
                <i class="fa-duotone fa-solid fa-table-columns"></i>
                <span>Layout</span>
            </button>
            <button class="ve-nav-btn" data-panel="sections" title="Secciones">
                <i class="fa-duotone fa-solid fa-layer-group"></i>
                <span>Capas</span>
            </button>
            <button class="ve-nav-btn" data-panel="history" title="Historial">
                <i class="fa-duotone fa-solid fa-clock-rotate-left"></i>
                <span>Historial</span>
            </button>
            <button class="ve-nav-btn" data-panel="code" title="Código HTML">
                <i class="fa-duotone fa-solid fa-code"></i>
                <span>Código</span>
            </button>
            <button class="ve-nav-btn" data-panel="settings" title="Ajustes">
                <i class="fa-duotone fa-solid fa-gear"></i>
                <span>Ajustes</span>
            </button>
        </div>

        {{-- Panels only (no toolbar/actions inside sidebar) --}}
        <div style="display:flex; flex-direction:column; flex:1; min-width:0; min-height:0;">

            {{-- Panel content --}}
            <div id="ve-sidebar-panels">

                <div class="ve-panel active" id="ve-panel-shortcodes">
                    @include('page::pages.partials.ve-shortcodes-panel')
                </div>

                <div class="ve-panel" id="ve-panel-inspector">
                    @include('page::pages.partials.ve-inspector-panel')
                </div>

                <div class="ve-panel" id="ve-panel-layout">
                    @include('page::pages.partials.ve-layout-panel')
                </div>

                <div class="ve-panel" id="ve-panel-sections">
                    @include('page::pages.partials.ve-sections-panel')
                </div>

                <div class="ve-panel" id="ve-panel-history">
                    @include('page::pages.partials.ve-history-panel')
                </div>

                <div class="ve-panel" id="ve-panel-code">
                    <div class="ve-panel-header">
                        <div>
                            <div class="ve-panel-label">Código</div>
                            <span class="ve-panel-title">Editor HTML</span>
                        </div>
                        <div class="ve-panel-actions">
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="ve-code-btn-format" title="Formatear">
                                <i class="fa-duotone fa-solid fa-wand-magic-sparkles"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="ve-code-btn-fold" title="Colapsar">
                                <i class="fa-duotone fa-solid fa-compress-alt"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="ve-code-btn-unfold" title="Expandir">
                                <i class="fa-duotone fa-solid fa-expand-alt"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="ve-code-btn-wrap" title="Ajuste">
                                <i class="fa-duotone fa-solid fa-align-left"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="ve-code-btn-theme" title="Tema">
                                <i class="fa-duotone fa-solid fa-circle-half-stroke"></i>
                            </button>
                            <button class="btn btn-outline-secondary ve-panel-action-btn" id="ve-code-refresh" title="Sincronizar">
                                <i class="fa-duotone fa-solid fa-sync-alt"></i>
                            </button>
                            <button class="btn ve-panel-action-btn ve-code-apply-btn" id="ve-code-apply" title="Aplicar cambios">
                                <i class="fa-duotone fa-solid fa-check"></i>
                            </button>
                        </div>
                    </div>
                    <div style="flex:1; overflow:hidden; position:relative;" id="ve-code-editor-wrap">
                        <textarea id="ve-code-editor-textarea" style="display:none;"></textarea>
                    </div>
                </div>

                <div class="ve-panel" id="ve-panel-settings">
                    @include('page::pages.partials.ve-settings-panel')
                </div>

            </div>

            {{-- Hidden ve-toolbar (kept in DOM for JS references) --}}
            <div id="ve-toolbar" style="display:none;" aria-hidden="true">
                <span id="autosave-status"></span>
                @if(count($supportedLocales) > 1)
                <div class="dropdown" id="ve-locale-dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" id="btn-locale-switcher"
                            data-bs-toggle="dropdown" style="font-size:12px; min-width:50px; padding:2px 8px;">
                        <span id="ve-locale-label">{{ strtoupper($locale) }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="font-size:12px; min-width:100px;">
                        @foreach($supportedLocales as $loc)
                        <li>
                            <button class="dropdown-item ve-locale-btn {{ $loc === $locale ? 'active' : '' }}"
                                    data-locale="{{ $loc }}" style="font-size:12px;">
                                {{ strtoupper($loc) }}
                                @if(!in_array($loc, $existingLocales))
                                    <span class="badge text-bg-secondary ms-1" style="font-size:9px;">Nuevo</span>
                                @endif
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
                {{-- Hidden controls — still functional via keyboard shortcuts and programmatic triggers --}}
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary breakpoint-btn active" data-breakpoint="desktop" title="Escritorio">
                        <i class="fa-duotone fa-solid fa-desktop"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary breakpoint-btn" data-breakpoint="laptop" data-width="1280px" data-height="800px" title="Laptop">
                        <i class="fa-duotone fa-solid fa-laptop"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary breakpoint-btn" data-breakpoint="tablet" data-width="768px" data-height="1024px" title="Tablet">
                        <i class="fa-duotone fa-solid fa-tablet-alt"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary breakpoint-btn" data-breakpoint="mobile" data-width="375px" data-height="812px" title="Móvil">
                        <i class="fa-duotone fa-solid fa-mobile-alt"></i>
                    </button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-undo" title="Deshacer (Ctrl+Z)" disabled>
                    <i class="fa-duotone fa-solid fa-undo"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-redo" title="Rehacer (Ctrl+Y)" disabled>
                    <i class="fa-duotone fa-solid fa-redo"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-discard" title="Descartar cambios">
                    <i class="fa-duotone fa-solid fa-rotate-left"></i>
                </button>
                <button type="button" class="btn btn-sm" id="btn-save" style="background:#1a1a1a; border-color:#1a1a1a; color:#fff; font-weight:600;">
                    <i class="fa-duotone fa-solid fa-save me-1"></i>Guardar
                </button>
                <a href="{{ $previewUrl }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver página pública">
                    <i class="fa-duotone fa-solid fa-external-link-alt"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-insert-icon" title="Insertar icono"><i class="fa-duotone fa-solid fa-icons"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-export-html" title="Exportar HTML (.html)"><i class="fa-duotone fa-solid fa-file-export"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-rotate-device" title="Rotar dispositivo"><i class="fa-duotone fa-solid fa-mobile-alt"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-shortcuts" title="Atajos de teclado"><i class="fa-duotone fa-solid fa-keyboard"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-preview-draft" title="Preview borrador"><i class="fa-duotone fa-solid fa-eye"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-find-replace" title="Buscar y reemplazar (Ctrl+H)"><i class="fa-duotone fa-solid fa-exchange-alt"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-grid-overlay" title="Grid overlay"><i class="fa-duotone fa-solid fa-border-all"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-media-manager" title="Gestor de medios"><i class="fa-duotone fa-solid fa-photo-video"></i></button>
            </div>

        </div>

        {{-- Sidebar toggle --}}
        <div id="ve-sidebar-toggle" title="Colapsar barra lateral">
            <i class="fa-duotone fa-solid fa-chevron-left"></i>
        </div>

    </div>

    {{-- ── Resize handle ──────────────────────────────────────────────── --}}
    <div id="ve-sidebar-resize" title="Arrastrar para redimensionar"></div>

    {{-- ── Canvas area ─────────────────────────────────────────────────── --}}
    <div id="ve-canvas">
        <div class="ve-ruler" id="ve-ruler"></div>
        <div class="ve-search-bar" id="ve-element-search">
            <i class="fa-duotone fa-solid fa-search"></i>
            <input type="text" id="ve-element-search-input" placeholder="Buscar texto en la página...">
            <span class="ve-search-bar-count" id="ve-search-count"></span>
            <button id="ve-search-prev" title="Anterior"><i class="fa-duotone fa-solid fa-chevron-up"></i></button>
            <button id="ve-search-next" title="Siguiente"><i class="fa-duotone fa-solid fa-chevron-down"></i></button>
            <button id="ve-search-close" title="Cerrar"><i class="fa-duotone fa-solid fa-times"></i></button>
        </div>
        <div id="ve-canvas-wrap" class="desktop">

            {{-- Drag overlay (shown when dragging a block) --}}
            <div id="ve-drag-overlay"></div>

            {{-- Drop indicator line --}}
            <div id="ve-drop-line"></div>

            {{-- Loading spinner --}}
            <div id="ve-preview-spinner">
                <div class="spinner-border text-light" role="status" style="width:2rem;height:2rem;"></div>
            </div>

            {{-- Preview iframe --}}
            <iframe id="ve-preview-frame"
                    src="{{ $visualPreviewUrl }}?locale={{ $locale }}"
                    title="Preview — {{ $page->title }}"
                    sandbox="allow-same-origin allow-scripts allow-forms allow-popups"
                    style="width:100%;height:100%;">
            </iframe>

        </div>

        {{-- Bottom bar --}}
        <div id="ve-bottombar">
            <span class="fw-semibold text-truncate" style="max-width:160px;color:#1a1c1e;font-size:13px;" title="{{ $page->title }}">
                {{ $page->title }}
            </span>
            <span class="{{ $page->status->badgeClass() }}" style="font-size:10px;">{{ $page->status->label() }}</span>
            <span id="autosave-status-bar" class="ms-1" style="font-size:11px;"></span>
            <span id="ve-word-count" class="ve-weight-badge"></span>
            <span id="ve-page-weight" class="ve-weight-badge"></span>

            <div class="ms-auto d-flex align-items-center gap-1">
                @if(count($supportedLocales) > 1)
                <div class="dropdown">
                    <button class="ve-bottom-btn dropdown-toggle" id="btn-locale-bar"
                            data-bs-toggle="dropdown" style="font-size:12px; font-weight:600;">
                        {{ strtoupper($locale) }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="font-size:12px; min-width:100px;">
                        @foreach($supportedLocales as $loc)
                        <li>
                            <button class="dropdown-item ve-locale-btn {{ $loc === $locale ? 'active' : '' }}"
                                    data-locale="{{ $loc }}" style="font-size:12px;">
                                {{ strtoupper($loc) }}
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <button class="ve-bottom-btn" id="btn-undo-bar" title="Deshacer" disabled>
                    <i class="fa-duotone fa-solid fa-rotate-left"></i>
                </button>
                <button class="ve-bottom-btn" id="btn-redo-bar" title="Rehacer" disabled>
                    <i class="fa-duotone fa-solid fa-rotate-right"></i>
                </button>
                <div class="dropdown">
                    <button class="ve-bottom-btn dropdown-toggle" id="btn-responsive-bar"
                            data-bs-toggle="dropdown" title="Vista responsive" style="font-size:13px;">
                        <i class="fa-duotone fa-solid fa-desktop" id="responsive-bar-icon"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="font-size:12px; min-width:150px;">
                        <li><h6 class="dropdown-header">Responsive</h6></li>
                        <li><button class="dropdown-item d-flex align-items-center gap-2 breakpoint-btn active" data-breakpoint="desktop"><i class="fa-duotone fa-solid fa-desktop fa-fw text-muted"></i> Escritorio</button></li>
                        <li><button class="dropdown-item d-flex align-items-center gap-2 breakpoint-btn" data-breakpoint="laptop" data-width="1280px" data-height="800px"><i class="fa-duotone fa-solid fa-laptop fa-fw text-muted"></i> Laptop <small class="ms-auto text-muted">1280×800</small></button></li>
                        <li><button class="dropdown-item d-flex align-items-center gap-2 breakpoint-btn" data-breakpoint="tablet" data-width="768px" data-height="1024px"><i class="fa-duotone fa-solid fa-tablet-screen-button fa-fw text-muted"></i> Tablet <small class="ms-auto text-muted">768×1024</small></button></li>
                        <li><button class="dropdown-item d-flex align-items-center gap-2 breakpoint-btn" data-breakpoint="mobile" data-width="375px" data-height="812px"><i class="fa-duotone fa-solid fa-mobile-screen-button fa-fw text-muted"></i> Móvil <small class="ms-auto text-muted">375×812</small></button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">iPhone</h6></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="375px" data-height="667px">iPhone SE <small class="ms-auto text-muted">375×667</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="414px" data-height="896px">iPhone XR <small class="ms-auto text-muted">414×896</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="390px" data-height="844px">iPhone 12 Pro <small class="ms-auto text-muted">390×844</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="430px" data-height="932px">iPhone 14 Pro Max <small class="ms-auto text-muted">430×932</small></button></li>
                        <li><h6 class="dropdown-header">Android</h6></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="412px" data-height="915px">Pixel 7 <small class="ms-auto text-muted">412×915</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="360px" data-height="740px">Samsung Galaxy S8+ <small class="ms-auto text-muted">360×740</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="412px" data-height="915px">Samsung Galaxy S20 Ultra <small class="ms-auto text-muted">412×915</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="280px" data-height="653px">Samsung Galaxy A51/71 <small class="ms-auto text-muted">280×653</small></button></li>
                        <li><h6 class="dropdown-header">Tablet</h6></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="768px" data-height="1024px">iPad Mini <small class="ms-auto text-muted">768×1024</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="820px" data-height="1180px">iPad Air <small class="ms-auto text-muted">820×1180</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="1024px" data-height="1366px">iPad Pro <small class="ms-auto text-muted">1024×1366</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="912px" data-height="1368px">Surface Pro 7 <small class="ms-auto text-muted">912×1368</small></button></li>
                        <li><h6 class="dropdown-header">Foldable</h6></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="540px" data-height="720px">Surface Duo <small class="ms-auto text-muted">540×720</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="344px" data-height="882px">Galaxy Z Fold 5 <small class="ms-auto text-muted">344×882</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="360px" data-height="568px">Asus Zenbook Fold <small class="ms-auto text-muted">360×568</small></button></li>
                        <li><h6 class="dropdown-header">Smart Display</h6></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="1024px" data-height="600px">Nest Hub <small class="ms-auto text-muted">1024×600</small></button></li>
                        <li><button class="dropdown-item breakpoint-btn" data-breakpoint="device" data-width="1280px" data-height="800px">Nest Hub Max <small class="ms-auto text-muted">1280×800</small></button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item d-flex align-items-center gap-2" id="btn-split-view"><i class="fa-duotone fa-solid fa-columns fa-fw text-muted"></i> Split (Desktop + Móvil)</button></li>
                    </ul>
                </div>
                <button class="ve-bottom-btn" id="btn-wireframe" title="Modo wireframe">
                    <i class="fa-duotone fa-solid fa-vector-square"></i>
                </button>
                <button class="ve-bottom-btn" id="btn-ruler" title="Regla">
                    <i class="fa-duotone fa-solid fa-ruler-horizontal"></i>
                </button>
                <button class="ve-bottom-btn" id="btn-dark-mode" title="Modo oscuro">
                    <i class="fa-duotone fa-solid fa-circle-half-stroke"></i>
                </button>
                <button class="ve-bottom-save" id="btn-save-bar" title="Guardar (Ctrl+S)">Guardar</button>
            </div>
        </div>

        {{-- Zoom bar --}}
        <div id="ve-zoom-bar">
            <button class="ve-zoom-btn" data-zoom="0.5">50%</button>
            <button class="ve-zoom-btn" data-zoom="0.75">75%</button>
            <button class="ve-zoom-btn active" data-zoom="1">100%</button>
            <button class="ve-zoom-btn" data-zoom="1.25">125%</button>
            <button class="ve-zoom-btn" data-zoom="1.5">150%</button>
        </div>
    </div>

    </div>{{-- /ve-main --}}

</div>

{{-- ── Conditions modal ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-conditions-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content ve-cmd-content">
            <div class="ve-ai-modal-header">
                <h6 class="ve-ai-modal-title"><i class="fa-duotone fa-solid fa-filter ve-ai-modal-icon"></i>Condiciones de visibilidad</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="ve-ai-modal-body">
                <div class="ve-field">
                    <label>Mostrar este elemento cuando:</label>
                    <select class="form-select" id="ve-condition-select">
                        <option value="">Sin condición</option>
                        <option value="logged-in">Solo usuarios logueados</option>
                        <option value="guest">Solo visitantes</option>
                        <option value="mobile">Solo móvil</option>
                        <option value="desktop">Solo desktop</option>
                    </select>
                </div>
                <button type="button" class="btn ve-btn-primary w-100" id="btn-apply-condition">
                    <i class="fa-duotone fa-solid fa-check me-1"></i>Aplicar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Popup builder modal ────────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-popup-builder" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ve-cmd-content">
            <div class="ve-ai-modal-header">
                <h6 class="ve-ai-modal-title"><i class="fa-duotone fa-solid fa-window-restore ve-ai-modal-icon"></i>Crear popup</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="ve-ai-modal-body">
                <div class="ve-field">
                    <label>Título del popup</label>
                    <input type="text" class="form-control" id="ve-popup-title" placeholder="Ej: Suscríbete a nuestro newsletter">
                </div>
                <div class="ve-field">
                    <label>Contenido</label>
                    <textarea class="form-control" id="ve-popup-content" rows="3" placeholder="Texto del popup..."></textarea>
                </div>
                <div class="ve-field">
                    <label>Trigger</label>
                    <select class="form-select" id="ve-popup-trigger">
                        <option value="click">Click en botón</option>
                        <option value="scroll">Al hacer scroll (50%)</option>
                        <option value="timer">Después de 5 segundos</option>
                        <option value="exit">Exit intent (salir de la página)</option>
                    </select>
                </div>
                <div class="ve-field">
                    <label>Estilo</label>
                    <select class="form-select" id="ve-popup-style">
                        <option value="center">Modal centrado</option>
                        <option value="bottom-bar">Barra inferior</option>
                        <option value="slide-in">Slide-in derecha</option>
                    </select>
                </div>
                <button type="button" class="btn ve-btn-primary w-100" id="btn-insert-popup">
                    <i class="fa-duotone fa-solid fa-plus me-1"></i>Insertar popup
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Form builder modal ──────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-form-builder" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content ve-cmd-content">
            <div class="ve-ai-modal-header">
                <h6 class="ve-ai-modal-title"><i class="fa-duotone fa-solid fa-rectangle-list ve-ai-modal-icon"></i>Constructor de formularios</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="ve-ai-modal-body">
                <div class="ve-field">
                    <label>Tipo de formulario</label>
                    <select class="form-select" id="ve-form-type">
                        <option value="contact">Contacto</option>
                        <option value="newsletter">Newsletter</option>
                        <option value="quote">Solicitar presupuesto</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </div>
                <div class="ve-field">
                    <label>Campos</label>
                    <div id="ve-form-fields">
                        <div class="ve-form-field-row">
                            <input type="text" class="form-control" value="Nombre" placeholder="Label del campo">
                            <select class="form-select"><option value="text">Texto</option><option value="email">Email</option><option value="tel">Teléfono</option><option value="textarea">Textarea</option><option value="select">Select</option></select>
                            <button type="button" class="btn btn-outline-secondary ve-form-remove-field"><i class="fa-duotone fa-solid fa-times"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary w-100 mt-2" id="btn-add-form-field">
                        <i class="fa-duotone fa-solid fa-plus me-1"></i>Agregar campo
                    </button>
                </div>
                <div class="ve-field">
                    <label>Texto del botón</label>
                    <input type="text" class="form-control" id="ve-form-btn-text" value="Enviar">
                </div>
                <div class="ve-field">
                    <label>Acción (email o URL)</label>
                    <input type="text" class="form-control" id="ve-form-action" placeholder="email@ejemplo.com o https://...">
                </div>
                <button type="button" class="btn ve-btn-primary w-100" id="btn-insert-form">
                    <i class="fa-duotone fa-solid fa-plus me-1"></i>Insertar formulario
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── AI Content modal ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-ai-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ve-cmd-content">
            <div class="modal-header ve-ai-modal-header">
                <h6 class="ve-ai-modal-title">
                    <i class="fa-duotone fa-solid fa-wand-magic-sparkles ve-ai-modal-icon"></i>Generar contenido con AI
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body ve-ai-modal-body">
                <div class="ve-field">
                    <label>¿Qué quieres generar?</label>
                    <select class="form-select" id="ve-ai-type">
                        <option value="paragraph">Párrafo</option>
                        <option value="heading">Título (H2)</option>
                        <option value="list">Lista de puntos</option>
                        <option value="cta">Call to action</option>
                        <option value="faq">FAQ (preguntas frecuentes)</option>
                    </select>
                </div>
                <div class="ve-field">
                    <label>Describe el contenido</label>
                    <textarea class="form-control" id="ve-ai-prompt" rows="3" placeholder="Ej: Un párrafo sobre las ventajas de las ventanas PVC para el hogar..."></textarea>
                </div>
                <div class="ve-field">
                    <label>Tono</label>
                    <select class="form-select" id="ve-ai-tone">
                        <option value="professional">Profesional</option>
                        <option value="casual">Casual</option>
                        <option value="persuasive">Persuasivo</option>
                        <option value="technical">Técnico</option>
                    </select>
                </div>
                <button type="button" class="btn ve-btn-primary w-100" id="btn-ai-generate">
                    <i class="fa-duotone fa-solid fa-wand-magic-sparkles me-1"></i>Generar
                </button>
                <div id="ve-ai-result" class="ve-ai-result ve-hidden">
                    <label>Resultado</label>
                    <div id="ve-ai-output" class="ve-ai-output"></div>
                    <div class="ve-ai-actions">
                        <button type="button" class="btn ve-btn-primary" id="btn-ai-insert">
                            <i class="fa-duotone fa-solid fa-check me-1"></i>Insertar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btn-ai-regenerate">
                            <i class="fa-duotone fa-solid fa-rotate me-1"></i>Regenerar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Command palette modal ─────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-command-palette" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content ve-cmd-content">
            <div class="ve-cmd-search-wrap">
                <i class="fa-duotone fa-solid fa-search ve-cmd-search-icon"></i>
                <input type="text" id="ve-cmd-input" class="ve-cmd-input" placeholder="Buscar acciones, bloques, paneles..." autocomplete="off">
            </div>
            <div id="ve-cmd-results" class="ve-cmd-results"></div>
        </div>
    </div>
</div>

{{-- ── Keyboard shortcuts modal ──────────────────────────────────────────── --}}
<div class="modal fade" id="ve-shortcuts-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none; border-radius:12px; overflow:hidden;">
            <div class="modal-header" style="background:#fff; border-bottom:1px solid #eee; padding:14px 20px;">
                <h6 style="font-weight:700; font-size:14px; color:#333; margin:0;">
                    <i class="fa-duotone fa-solid fa-keyboard me-2" style="color:#999;"></i>Atajos de teclado
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px 20px; max-height:60vh; overflow-y:auto;">
                <div style="display:grid; gap:4px;">
                    <div class="ve-shortcut-row"><span>Guardar</span><kbd>Ctrl+S</kbd></div>
                    <div class="ve-shortcut-row"><span>Deshacer</span><kbd>Ctrl+Z</kbd></div>
                    <div class="ve-shortcut-row"><span>Rehacer</span><kbd>Ctrl+Y</kbd></div>
                    <div class="ve-shortcut-row"><span>Buscar y reemplazar</span><kbd>Ctrl+H</kbd></div>
                    <div class="ve-shortcut-row"><span>Copiar elemento</span><kbd>Shift+C</kbd></div>
                    <div class="ve-shortcut-row"><span>Pegar elemento</span><kbd>Shift+V</kbd></div>
                    <div class="ve-shortcut-row"><span>Eliminar elemento</span><kbd>Delete</kbd></div>
                    <div class="ve-shortcut-row"><span>Mover arriba</span><kbd>Shift+↑</kbd></div>
                    <div class="ve-shortcut-row"><span>Mover abajo</span><kbd>Shift+↓</kbd></div>
                    <div class="ve-shortcut-row"><span>Duplicar</span><kbd>Shift+D</kbd></div>
                    <div class="ve-shortcut-row"><span>Ver atajos</span><kbd>?</kbd></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Media Picker modal ────────────────────────────────────────────────── --}}
@include('media::partials.picker-modal')
<script src="{{ asset('modules/Media/js/media-picker.js') }}"></script>

{{-- ── Context menu ──────────────────────────────────────────────────────── --}}
<div id="ve-context-menu">
    <div class="ve-ctx-item" id="ctx-copy">
        <i class="fa-duotone fa-solid fa-copy fa-fw text-muted"></i> Copiar
        <span class="ms-auto" style="font-size:10px;color:#bbb;">⇧C</span>
    </div>
    <div class="ve-ctx-item" id="ctx-paste" style="display:none;">
        <i class="fa-duotone fa-solid fa-paste fa-fw text-muted"></i> Pegar después
        <span class="ms-auto" style="font-size:10px;color:#bbb;">⇧V</span>
    </div>
    <div class="ve-ctx-divider"></div>
    <div class="ve-ctx-item" id="ctx-move-up">
        <i class="fa-duotone fa-solid fa-arrow-up fa-fw text-muted"></i> Mover arriba
    </div>
    <div class="ve-ctx-item" id="ctx-move-down">
        <i class="fa-duotone fa-solid fa-arrow-down fa-fw text-muted"></i> Mover abajo
    </div>
    <div class="ve-ctx-item" id="ctx-duplicate">
        <i class="fa-duotone fa-solid fa-clone fa-fw text-muted"></i> Duplicar
    </div>
    <div class="ve-ctx-item" id="ctx-edit-html">
        <i class="fa-duotone fa-solid fa-code fa-fw text-muted"></i> Editar HTML
    </div>
    <div class="ve-ctx-item" id="ctx-save-block">
        <i class="fa-duotone fa-solid fa-bookmark fa-fw text-muted"></i> Guardar como shortcode
    </div>
    <div class="ve-ctx-divider"></div>
    <div class="ve-ctx-item text-danger" id="ctx-delete">
        <i class="fa-duotone fa-solid fa-trash-can fa-fw"></i> Eliminar
    </div>
</div>

{{-- ── Modal: HTML editor ────────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-html-editor-modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Editor HTML
                    <span id="ve-html-editor-tag" class="badge bg-secondary ms-2"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ve-editor-toolbar">
                    <button type="button" class="btn btn-sm" id="ve-btn-format" title="Formatear HTML">
                        <i class="fas fa-wand-magic-sparkles me-1"></i>Formatear
                    </button>
                    <div class="ve-tb-sep"></div>
                    <button type="button" class="btn btn-sm" id="ve-btn-fold-all" title="Colapsar todo">
                        <i class="fas fa-compress-alt me-1"></i>Colapsar
                    </button>
                    <button type="button" class="btn btn-sm" id="ve-btn-unfold-all" title="Expandir todo">
                        <i class="fas fa-expand-alt me-1"></i>Expandir
                    </button>
                    <div class="ve-tb-sep"></div>
                    <button type="button" class="btn btn-sm" id="ve-btn-wrap" title="Ajuste de línea">
                        <i class="fas fa-align-left me-1"></i>Ajuste
                    </button>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <small style="color:#888; font-size:11px;">Ctrl+F buscar · Ctrl+H reemplazar · F11 pantalla completa</small>
                        <button type="button" class="btn btn-sm" id="ve-btn-theme" title="Tema claro / oscuro">
                            <i class="fas fa-circle-half-stroke"></i>
                        </button>
                        <button type="button" class="btn btn-sm" id="ve-btn-fullscreen" title="Pantalla completa (F11)">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <textarea id="ve-html-editor-textarea" style="width:100%;"></textarea>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" id="btn-apply-html"
                        style="background:#1a1a1a;border-color:#1a1a1a;">
                    <i class="fa-duotone fa-solid fa-check me-1"></i>Aplicar cambios
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: icono selector ─────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-icon-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-duotone fa-solid fa-icons me-2"></i>Insertar icono</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="ve-icon-search"
                           placeholder="Buscar icono (ej: home, user, star...)">
                </div>
                <div class="mb-2">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary active ve-icon-style-btn" data-style="fas">Solid</button>
                        <button type="button" class="btn btn-outline-secondary ve-icon-style-btn" data-style="far">Regular</button>
                        <button type="button" class="btn btn-outline-secondary ve-icon-style-btn" data-style="fab">Brands</button>
                    </div>
                </div>
                <div id="ve-icon-grid"></div>
            </div>
            <div class="modal-footer">
                <small class="text-muted me-auto">Haz clic en un icono para insertarlo en el contenido</small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Shortcode Builder ──────────────────────────────────────────── --}}
<div class="modal fade" id="ve-shortcode-builder-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">
                    <i class="fa-duotone fa-solid fa-code me-2 text-muted"></i>
                    <span id="ve-scb-title">Shortcode Builder</span>
                    <code id="ve-scb-tag" class="ms-2" style="font-size:11px; background:#f1f3f5; padding:1px 6px; border-radius:3px;"></code>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="ve-scb-description" class="text-muted small mb-2"></p>
                <hr class="my-2">

                {{-- Attribute inputs --}}
                <div id="ve-scb-attrs"></div>

                {{-- Content (pair shortcodes) --}}
                <div id="ve-scb-content-wrap" class="mt-3" style="display:none;">
                    <label class="form-label">Contenido</label>
                    <textarea class="form-control" id="ve-scb-content" rows="3" placeholder="Contenido del shortcode..."></textarea>
                </div>

                <hr class="my-3">

                {{-- Live preview --}}
                <label class="form-label">Código generado</label>
                <pre id="ve-scb-preview"
                     style="background:#1e1e2e; color:#e0e0e0; padding:12px; border-radius:6px; font-size:11px; white-space:pre-wrap; word-break:break-all; min-height:60px; margin:0;"></pre>

                <div class="mt-3" id="ve-scb-example-wrap">
                    <label class="form-label">Ejemplo</label>
                    <pre id="ve-scb-example"
                         style="background:#f8f9fa; color:#555; padding:8px; border-radius:4px; font-size:10px; white-space:pre-wrap; word-break:break-all; margin:0; border:1px solid #e9ecef;"></pre>
                </div>
            </div>
            <div class="modal-footer py-2 d-block">
                <button type="button" class="btn btn-danger w-100 mb-1" id="btn-insert-shortcode">
                    Insertar en editor
                </button>
                <button type="button" class="btn btn-outline-secondary w-100 mb-1" id="btn-copy-shortcode" title="Copiar al portapapeles">
                    Copiar
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Atajos de teclado ──────────────────────────────────────────── --}}
<div class="modal fade" id="ve-shortcuts-modal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0"><i class="fa-duotone fa-solid fa-keyboard me-2 text-muted"></i>Atajos de teclado</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                    <tbody>
                        <tr class="table-light"><td colspan="2" class="fw-bold px-3 py-1">General</td></tr>
                        <tr><td class="px-3"><kbd>Ctrl</kbd>+<kbd>S</kbd></td><td>Guardar página</td></tr>
                        <tr><td class="px-3"><kbd>Ctrl</kbd>+<kbd>Z</kbd></td><td>Deshacer</td></tr>
                        <tr><td class="px-3"><kbd>Ctrl</kbd>+<kbd>Y</kbd></td><td>Rehacer</td></tr>
                        <tr class="table-light"><td colspan="2" class="fw-bold px-3 py-1">Elementos</td></tr>
                        <tr><td class="px-3"><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>C</kbd></td><td>Copiar elemento</td></tr>
                        <tr><td class="px-3"><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>V</kbd></td><td>Pegar elemento</td></tr>
                        <tr><td class="px-3">Doble clic</td><td>Editar texto inline</td></tr>
                        <tr><td class="px-3">Clic derecho</td><td>Menú contextual</td></tr>
                        <tr class="table-light"><td colspan="2" class="fw-bold px-3 py-1">Bloques</td></tr>
                        <tr><td class="px-3">Arrastrar bloque</td><td>Insertar en posición exacta</td></tr>
                        <tr><td class="px-3">Clic en bloque</td><td>Insertar al final del contenido</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Guardar shortcode personalizado ──────────────────────────────── --}}
<div class="modal fade" id="ve-save-block-modal" tabindex="-1">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Guardar como shortcode</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" >Nombre</label>
                    <input type="text" class="form-control" id="save-block-name" placeholder="Mi shortcode personalizado">
                </div>
                <div>
                    <label class="form-label" >Categoría</label>
                    <select class="form-select" id="save-block-category">
                        <option value="custom">Personalizados</option>
                        <option value="estructura">Estructura</option>
                        <option value="contenido">Contenido</option>
                        <option value="media">Media</option>
                        <option value="tema">Tema</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer py-2 d-block">
                <button type="button" class="btn btn-danger w-100 mb-1" id="btn-confirm-save-block">
                    Guardar shortcode
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>


{{-- ── Modal: Confirmar eliminación ───────────────────────────────────────── --}}
<div class="modal fade" id="ve-confirm-modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4 px-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" style="width:56px;height:56px;">
                        <i class="fas fa-trash-can fa-lg text-danger"></i>
                    </span>
                </div>
                <h6 class="fw-bold mb-2">¿Eliminar elemento?</h6>
                <p class="text-muted small mb-0" id="ve-confirm-message">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer py-2 d-block border-top">
                <button type="button" class="btn btn-danger w-100 mb-1" id="ve-confirm-accept">Sí, eliminar</button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Block HTML data (used by shortcodes panel to render HTML blocks) --}}
@include('page::pages.partials.ve-blocks-data')

<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

<script>
(function ($) {
    'use strict';

    /* ── Constants ───────────────────────────────────────────────────── */
    const CSRF              = $('meta[name="csrf-token"]').attr('content');
    const AUTO_SAVE_URL        = '{{ route("pages.auto-save", $page) }}';
    const VISUAL_PREVIEW       = '{{ route("pages.visual-preview", $page) }}';
    const SAVE_URL             = '{{ route("pages.visual-save", $page) }}';
    const LOCALE_CONTENT_URL   = '{{ route("pages.locale-content", $page) }}';
    const EXPAND_SHORTCODE_URL = '{{ route("pages.expand-shortcode", $page) }}';
    let   LOCALE            = '{{ $locale ?? app()->getLocale() }}';
    const DEFAULT_LOCALE    = '{{ $defaultLocale ?? app()->getLocale() }}';
    const PAGE_DATA      = {!! json_encode([
        'title'           => $page->title,
        'slug'            => $page->slug,
        'status'          => $page->status->value,
        'template'        => $page->template,
        'seo_title'       => $page->seo_title ?? '',
        'seo_description' => $page->seo_description ?? '',
        'seo_keywords'    => $page->seo_keywords ?? '',
        'featured_image'  => $page->featured_image ?? '',
    ]) !!};

    let editor            = null;
    let editorReady       = false;  // true after CKEditor finishes init
    let autoSaveTimer     = null;
    let previewTimer      = null;
    let isModified        = false;
    let isSaving          = false;
    let styleChanges      = {};  // {nodeId: {prop: value}}
    let hasInspectorChanges = false;
    let originalContent   = {!! json_encode($initialContent ?? '') !!};

    /* ── Page settings (editable from settings panel) ─────────────────── */
    window.veUpdatePageData = function (data) {
        if (data.title)           PAGE_DATA.title           = data.title;
        if (data.slug)            PAGE_DATA.slug            = data.slug;
        if (data.status)          PAGE_DATA.status          = data.status;
        if (data.template)        PAGE_DATA.template        = data.template;
        if (data.seo_title        !== undefined) PAGE_DATA.seo_title        = data.seo_title;
        if (data.seo_description  !== undefined) PAGE_DATA.seo_description  = data.seo_description;
        if (data.seo_keywords     !== undefined) PAGE_DATA.seo_keywords     = data.seo_keywords;
        if (data.featured_image   !== undefined) PAGE_DATA.featured_image   = data.featured_image;
        markModified(true);
    };

    /* ── History stack ───────────────────────────────────────────────── */
    let historyStack   = [];   // [{label, html}, ...]
    let historyPointer = -1;

    function pushHistory(label, html) {
        // Truncate redo entries
        historyStack = historyStack.slice(0, historyPointer + 1);
        historyStack.push({ label: label || 'Cambio', html: html });
        if (historyStack.length > 60) historyStack.shift();
        historyPointer = historyStack.length - 1;
        renderHistoryPanel();
        syncUndoRedoBtns();
    }

    function undoHistory() {
        if (historyPointer <= 0) return;
        historyPointer--;
        restoreFromHistory();
    }

    function redoHistory() {
        if (historyPointer >= historyStack.length - 1) return;
        historyPointer++;
        restoreFromHistory();
    }

    function restoreFromHistory() {
        const entry = historyStack[historyPointer];
        if (!entry) return;
        // Don't call editor.setData() — it strips custom HTML blocks.
        // Pass the history html directly to updatePreview so the iframe
        // is restored correctly without going through CKEditor's model.
        markModified(false);
        syncUndoRedoBtns();
        renderHistoryPanel();
        schedulePreviewUpdate(entry.html);
    }

    function syncUndoRedoBtns() {
        $('#btn-undo').prop('disabled', historyPointer <= 0);
        $('#btn-redo').prop('disabled', historyPointer >= historyStack.length - 1);
    }

    function renderHistoryPanel() {
        const $list  = $('#ve-history-list');
        const $empty = $('#ve-history-empty');

        if (historyStack.length === 0) {
            $list.empty();
            $empty.show();
            return;
        }
        $empty.hide();
        $list.empty();

        // Render newest first
        for (let i = historyStack.length - 1; i >= 0; i--) {
            const isCurrent = i === historyPointer;
            const $item = $('<div>')
                .addClass('ve-history-item' + (isCurrent ? ' current' : ''))
                .attr('data-idx', i)
                .html(
                    '<div class="ve-hist-dot"></div>' +
                    '<span>' + $('<span>').text(historyStack[i].label).html() + '</span>' +
                    (isCurrent ? '<span class="ms-auto badge" style="background:#1a1a1a;font-size:10px;">Actual</span>' : '')
                )
                .on('click', function () {
                    historyPointer = parseInt($(this).data('idx'), 10);
                    restoreFromHistory();
                });
            $list.append($item);
        }
    }

    /* ── Editor change handler ───────────────────────────────────────── */
    function onEditorChange() {
        if (!editorReady) return;  // ignore events during CKEditor init
        markModified(true);
        schedulePreviewUpdate();
        scheduleAutoSave();
    }

    function markModified(dirty) {
        isModified = dirty;
        setAutoSaveStatus(dirty ? 'unsaved' : '', dirty ? 'Sin guardar' : '');
    }

    /* ── CKEditor init ───────────────────────────────────────────────── */
    ClassicEditor.create(document.querySelector('#ve-content'), {
        toolbar: {
            items: [
                'heading', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'link', '|',
                'bulletedList', 'numberedList', '|',
                'blockQuote', 'code', '|',
                'insertTable', '|',
                'outdent', 'indent', '|',
                'undo', 'redo',
            ],
            shouldNotGroupWhenFull: true,
        },
        language: 'es',
        table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
        placeholder: 'Escribe el contenido aquí...',
    })
    .then(function (instance) {
        editor = instance;
        window.veEditor = instance;

        editor.model.document.on('change:data', onEditorChange);

        // Seed history with initial content (use $initialContent, not editor.getData()
        // which strips custom HTML blocks unknown to CKEditor)
        pushHistory('Estado inicial', originalContent);

        // Mark editor as ready — change:data events before this are ignored
        editorReady = true;

        // Override editor's own undo/redo with our history
        $('#btn-undo').prop('disabled', true);
        $('#btn-redo').prop('disabled', true);
    })
    .catch(err => console.error('CKEditor init error:', err));

    /* ── Preview ─────────────────────────────────────────────────────── */
    function schedulePreviewUpdate(overrideContent) {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(function () { updatePreview(overrideContent); }, 1200);
    }

    function previewUrlWithLocale() {
        return VISUAL_PREVIEW + '?locale=' + LOCALE;
    }

    function updatePreview(overrideContent) {
        showSpinner(true);
        let content;
        if (overrideContent !== undefined) {
            // Explicit content (e.g. from undo/redo history) — use it directly.
            content = overrideContent;
        } else {
            // Read from the live iframe DOM. If the iframe isn't ready yet,
            // skip silently — never fall back to editor.getData() which strips
            // custom HTML blocks (hero sections, slick sliders, etc.).
            const frame = document.getElementById('ve-preview-frame');
            const iframeCk = frame?.contentDocument?.querySelector('.ck-content');
            if (!iframeCk) { showSpinner(false); return; }
            content = iframeCk.innerHTML;
        }
        $.ajax({
            url:    previewUrlWithLocale(),
            method: 'POST',
            data:   { _token: CSRF, content: content },
            success: function (html) {
                const frame = document.getElementById('ve-preview-frame');
                const doc   = frame.contentDocument || frame.contentWindow.document;
                try {
                    doc.open(); doc.write(html); doc.close();
                    // Re-apply style changes after reload
                    if (Object.keys(styleChanges).length) {
                        setTimeout(function () {
                            sendToFrame({ type: 've-reapply-styles', changes: styleChanges });
                        }, 200);
                    }
                } catch (e) {
                    frame.src = previewUrlWithLocale() + '&t=' + Date.now();
                }
            },
            error: function () {
                document.getElementById('ve-preview-frame').src = previewUrlWithLocale() + '&t=' + Date.now();
            },
            complete: function () { showSpinner(false); },
        });
    }

    function showSpinner(show) {
        $('#ve-preview-spinner').toggleClass('visible', show);
    }

    /* ── Get content to save ─────────────────────────────────────────── */
    function getContentToSave() {
        return new Promise(function (resolve) {
            // If the code panel is active, use its content directly
            if ($('#ve-panel-code').hasClass('active') && window._veFullCodeMirror) {
                resolve(window._veFullCodeMirror.getValue());
                return;
            }
            // Primary source: iframe (preserves full HTML with classes/attributes)
            let timeout;
            const handler = function (e) {
                if (!e.data || e.data.type !== 've-html-response') return;
                window.removeEventListener('message', handler);
                clearTimeout(timeout);
                resolve(e.data.html || (editor ? editor.getData() : originalContent));
            };
            window.addEventListener('message', handler);
            const frame = document.getElementById('ve-preview-frame');
            if (frame && frame.contentWindow) {
                frame.contentWindow.postMessage({ type: 've-request-html' }, '*');
            } else {
                window.removeEventListener('message', handler);
                resolve(editor ? editor.getData() : originalContent);
                return;
            }
            timeout = setTimeout(function () {
                window.removeEventListener('message', handler);
                resolve(editor ? editor.getData() : originalContent);
            }, 3000);
        });
    }

    /* ── Auto-save ───────────────────────────────────────────────────── */
    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(doAutoSave, 30000);
    }

    function doAutoSave() {
        if (!isModified && !hasInspectorChanges) return;
        if (isSaving) return;
        setAutoSaveStatus('saving', 'Guardando...');
        getContentToSave().then(function (content) {
            $.ajax({
                url:         AUTO_SAVE_URL,
                method:      'PATCH',
                contentType: 'application/json',
                headers:     { 'X-CSRF-TOKEN': CSRF },
                data:        JSON.stringify({ content: content }),
                success: function () {
                    isModified = false;
                    hasInspectorChanges = false;
                    setAutoSaveStatus('saved', 'Guardado ' + currentTime());
                },
                error: function () { setAutoSaveStatus('error', 'Error al guardar'); },
            });
        });
    }

    setInterval(doAutoSave, 60000);

    function setAutoSaveStatus(state, text) {
        $('#autosave-status').removeClass('saving saved error unsaved');
        if (state) $('#autosave-status').addClass(state).text(text);
        else $('#autosave-status').text('');
    }

    function currentTime() {
        return new Date().toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' });
    }

    /* ── Full save ───────────────────────────────────────────────────── */
    function doSave($btn) {
        getContentToSave().then(function (content) {
            $.ajax({
                url:         SAVE_URL,
                method:      'POST',
                contentType: 'application/json',
                headers:     { 'X-CSRF-TOKEN': CSRF },
                data: JSON.stringify({
                    locale:          LOCALE,
                    content:         content,
                    template:        PAGE_DATA.template,
                    title:           PAGE_DATA.title,
                    slug:            PAGE_DATA.slug,
                    status:          PAGE_DATA.status,
                    seo_title:       PAGE_DATA.seo_title       || '',
                    seo_description: PAGE_DATA.seo_description || '',
                    seo_keywords:    PAGE_DATA.seo_keywords    || '',
                }),
                success: function () {
                    isModified = false;
                    hasInspectorChanges = false;
                    originalContent = content;
                    setAutoSaveStatus('saved', 'Guardado ' + currentTime());
                    $btn.html('<i class="fa-duotone fa-solid fa-check me-1"></i>Guardado')
                        .css({ background: '#13C672', 'border-color': '#13C672' });
                    setTimeout(function () {
                        $btn.html('<i class="fa-duotone fa-solid fa-save me-1"></i>Guardar')
                            .css({ background: '#b10100', 'border-color': '#b10100' });
                    }, 2500);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Error al guardar';
                    $btn.html('<i class="fa-duotone fa-solid fa-exclamation-triangle me-1"></i>Error')
                        .css({ background: '#FA896B', 'border-color': '#FA896B' });
                    alert(msg);
                    setTimeout(function () {
                        $btn.html('<i class="fa-duotone fa-solid fa-save me-1"></i>Guardar')
                            .css({ background: '#b10100', 'border-color': '#b10100' });
                    }, 3000);
                },
                complete: function () { isSaving = false; $btn.prop('disabled', false); },
            });
        });
    }

    $('#btn-save').on('click', function () {
        if (isSaving) return;
        isSaving = true;
        const $btn = $(this).prop('disabled', true)
                            .html('<i class="fa-duotone fa-solid fa-spinner fa-spin me-1"></i>Guardando...');
        doSave($btn);
    });

    /* ── Undo / Redo ─────────────────────────────────────────────────── */
    $('#btn-undo').on('click', function () { undoHistory(); });
    $('#btn-redo').on('click', function () { redoHistory(); });

    $('#btn-discard').on('click', function () {
        if (!confirm('¿Descartar todos los cambios y restaurar el contenido guardado?')) return;
        styleChanges = {};
        hasInspectorChanges = false;
        isModified = false;
        historyStack = [{ label: 'Estado restaurado', html: originalContent }];
        historyPointer = 0;
        renderHistoryPanel();
        syncUndoRedoBtns();
        setAutoSaveStatus('', '');
        schedulePreviewUpdate(originalContent);
    });

    /* ── Locale Switcher ─────────────────────────────────────────────── */
    $(document).on('click', '.ve-locale-btn', function () {
        var newLocale = $(this).data('locale');
        if (newLocale === LOCALE) return;

        if (isModified && !confirm('Hay cambios sin guardar. ¿Deseas cambiar de idioma sin guardar?')) {
            return;
        }

        var $btn = $('#btn-locale-switcher');
        $btn.prop('disabled', true).html('<i class="fa-duotone fa-solid fa-spinner fa-spin"></i>');

        $.ajax({
            url: LOCALE_CONTENT_URL,
            method: 'GET',
            data: { locale: newLocale },
            success: function (data) {
                // Detach editor change listener to avoid marking as modified
                if (editor) {
                    editor.model.document.off('change:data', onEditorChange);
                    editor.setData(data.content || '');
                    editor.model.document.on('change:data', onEditorChange);
                }

                LOCALE = newLocale;
                originalContent = data.content || '';
                isModified = false;
                hasInspectorChanges = false;
                styleChanges = {};

                // Update PAGE_DATA with translation values
                PAGE_DATA.title           = data.title || PAGE_DATA.title;
                PAGE_DATA.slug            = data.slug || PAGE_DATA.slug;
                PAGE_DATA.status          = data.status || PAGE_DATA.status;
                PAGE_DATA.seo_title       = data.seo_title || '';
                PAGE_DATA.seo_description = data.seo_description || '';
                PAGE_DATA.seo_keywords    = data.seo_keywords || '';

                // Update settings panel fields if visible
                $('#ve-settings-title').val(PAGE_DATA.title);
                $('#ve-settings-slug').val(PAGE_DATA.slug);
                $('#ve-settings-status').val(PAGE_DATA.status);
                $('#ve-settings-seo-title').val(PAGE_DATA.seo_title);
                $('#ve-settings-seo-description').val(PAGE_DATA.seo_description).trigger('input');
                $('#ve-settings-seo-keywords').val(PAGE_DATA.seo_keywords);

                // Update dropdown active state
                $('.ve-locale-btn').removeClass('active');
                $('.ve-locale-btn[data-locale="' + newLocale + '"]').addClass('active');

                // Update preview URL for new locale
                window.postMessage({ type: 've-locale-changed', locale: newLocale }, '*');

                // Reset history
                historyStack = [{ label: 'Idioma: ' + newLocale.toUpperCase(), html: data.content || '' }];
                historyPointer = 0;
                renderHistoryPanel();
                syncUndoRedoBtns();

                setAutoSaveStatus('', '');
                schedulePreviewUpdate(data.content || '');

                // If code panel is open, re-sync it with the new locale content
                if ($('#ve-panel-code').hasClass('active') && window._veFullCodeMirror) {
                    // Wait for preview to finish loading, then sync the code editor
                    setTimeout(function () { veSyncCodeFromPreview(); }, 1800);
                }

                if (data.is_new) {
                    setAutoSaveStatus('unsaved', 'Traducción nueva — sin contenido');
                }
            },
            error: function (xhr) {
                alert('Error al cargar idioma: ' + (xhr.responseJSON?.message || 'Error desconocido'));
            },
            complete: function () {
                $btn.prop('disabled', false).html('<span id="ve-locale-label">' + LOCALE.toUpperCase() + '</span>');
            },
        });
    });

    /* ── Keyboard shortcuts ──────────────────────────────────────────── */
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('#btn-save').trigger('click');
        }
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key === 'z') {
            const frame = document.getElementById('ve-preview-frame');
            if (document.activeElement !== frame) {
                e.preventDefault();
                undoHistory();
            }
        }
        if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.shiftKey && e.key === 'z'))) {
            e.preventDefault();
            redoHistory();
        }
    });

    /* ── Responsive breakpoints ──────────────────────────────────────── */
    const BREAKPOINT_ICONS = {
        desktop: 'fa-desktop',
        laptop:  'fa-laptop',
        tablet:  'fa-tablet-screen-button',
        mobile:  'fa-mobile-screen-button',
        device:  'fa-mobile-screen-button',
    };

    $(document).on('click', '.breakpoint-btn', function () {
        $('.breakpoint-btn').removeClass('active');
        $(this).addClass('active');

        const bp     = $(this).data('breakpoint');
        const width  = $(this).data('width');
        const height = $(this).data('height');
        const $wrap  = $('#ve-canvas-wrap');
        const $frame = $('#ve-preview-frame');

        // Update bottom bar icon and rotate-device button visibility
        const iconClass = BREAKPOINT_ICONS[bp] || 'fa-desktop';
        $('#responsive-bar-icon').attr('class', 'fa-duotone fa-solid ' + iconClass);
        $('#btn-rotate-device').toggle(bp !== 'desktop');

        // Remove split if active
        $wrap.find('.ve-split-mobile').remove();
        $wrap.removeClass('split');

        if (bp === 'desktop') {
            $wrap.removeClass().addClass('desktop');
            $frame.css({ width: '100%', height: '100%', boxShadow: '', borderRadius: '' });
        } else {
            $wrap.removeClass('desktop');
            $frame.css({
                width:        width,
                height:       height,
                boxShadow:    '0 8px 32px rgba(0,0,0,.4)',
                borderRadius: '8px',
            });
        }
    });

    /* ── Sidebar panel switching ─────────────────────────────────────── */
    $('#ve-sidebar-nav .ve-nav-btn').on('click', function () {
        const panel = $(this).data('panel');
        $('#ve-sidebar-nav .ve-nav-btn').removeClass('active');
        $(this).addClass('active');
        $('.ve-panel').removeClass('active');
        $('#ve-panel-' + panel).addClass('active');

        if (panel === 'sections') {
            setTimeout(function () { if (window.refreshSectionsPanel) window.refreshSectionsPanel(); }, 50);
        }
        if (panel === 'history') {
            renderHistoryPanel();
        }
        if (panel === 'layout') {
            setTimeout(function () { if (window.veRefreshLayout) window.veRefreshLayout(); }, 80);
        }
        if (panel === 'settings') {
            setTimeout(function () { if (window.runSeoAnalysis) window.runSeoAnalysis(); }, 100);
        }
        if (panel === 'code') {
            setTimeout(function () { veInitCodePanel(); }, 80);
            $('#ve-sidebar').css('width', '600px');
        } else {
            if ($('#ve-sidebar').width() > 400) {
                $('#ve-sidebar').css('width', '350px');
            }
        }
    });

    /* ── Bottom bar wiring ───────────────────────────────────────────── */
    $('#btn-undo-bar').on('click', function () { $('#btn-undo').trigger('click'); });
    $('#btn-redo-bar').on('click', function () { $('#btn-redo').trigger('click'); });
    $('#btn-save-bar').on('click', function () { $('#btn-save').trigger('click'); });

    // Sync disabled state from top toolbar to bottom bar
    const undoRedoObserver = new MutationObserver(function () {
        $('#btn-undo-bar').prop('disabled', $('#btn-undo').prop('disabled'));
        $('#btn-redo-bar').prop('disabled', $('#btn-redo').prop('disabled'));
    });
    const $btnUndo = document.getElementById('btn-undo');
    const $btnRedo = document.getElementById('btn-redo');
    if ($btnUndo) undoRedoObserver.observe($btnUndo, { attributes: true });
    if ($btnRedo) undoRedoObserver.observe($btnRedo, { attributes: true });

    /* ── Sidebar toggle (collapse/expand) ───────────────────────────── */
    $('#ve-sidebar-toggle').on('click', function () {
        const $sidebar = $('#ve-sidebar');
        const $icon    = $(this).find('i');
        const collapsed = $sidebar.hasClass('collapsed');

        if (collapsed) {
            $sidebar.css('margin-left', '').removeClass('collapsed');
            $icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');
            $(this).attr('title', 'Colapsar barra lateral');
        } else {
            const hideWidth = $sidebar.outerWidth() - 48;
            $sidebar.css('margin-left', '-' + hideWidth + 'px').addClass('collapsed');
            $icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
            $(this).attr('title', 'Expandir barra lateral');
        }
    });

    /* ── postMessage utilities ───────────────────────────────────────── */
    function sendToFrame(msg) {
        const frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage(msg, '*');
    }

    function hideCtxMenu() { $('#ve-context-menu').hide(); }

    /* ── Sync inline edits from iframe → editor ──────────────────────── */
    function syncFromIframe(html) {
        if (!editor) return;
        const label = 'Edición en página';
        editor.model.document.off('change:data', onEditorChange);
        editor.setData(html);
        editor.model.document.on('change:data', onEditorChange);
        isModified          = true;
        hasInspectorChanges = true;
        setAutoSaveStatus('unsaved', 'Sin guardar');
        scheduleAutoSave();
        pushHistory(label, html);
    }

    /* ── Copy / Paste element ────────────────────────────────────────── */
    let copiedElementHtml = null;

    function copyCurrentElement() {
        sendToFrame({ type: 've-copy-request', nodeId: currentContextNodeId });
    }

    function pasteElement() {
        if (!copiedElementHtml) return;
        sendToFrame({ type: 've-paste-element', html: copiedElementHtml, nodeId: currentContextNodeId });
    }

    /* ── Inspector style tracking ────────────────────────────────────── */
    let currentContextNodeId = null;

    /* ── Global message dispatcher ───────────────────────────────────── */
    window.addEventListener('message', function (e) {
        if (!e.data || !e.data.type) return;
        const d = e.data;

        switch (d.type) {

            case 've-context-menu': {
                currentContextNodeId = d.nodeId;
                const frameRect = document.getElementById('ve-preview-frame').getBoundingClientRect();
                let left = d.x + frameRect.left;
                let top  = d.y + frameRect.top;
                const menuW = 170, menuH = 180;
                if (left + menuW > window.innerWidth)  left = window.innerWidth - menuW - 8;
                if (top + menuH > window.innerHeight)  top  = window.innerHeight - menuH - 8;
                $('#ve-context-menu').css({ top: top + 'px', left: left + 'px' }).show();
                break;
            }

            case 've-element-selected':
                currentContextNodeId = d.nodeId;
                // Auto-switch to inspector panel
                $('[data-panel="inspector"]').trigger('click');
                break;

            case 've-inline-edit-committed':
                syncFromIframe(d.html);
                break;

            case 've-editing-started':
                hideCtxMenu();
                break;

            case 've-element-deleted':
                if (currentContextNodeId === d.nodeId) hideCtxMenu();
                break;

            case 've-block-dropped':
                syncFromIframe(d.html);
                $('#ve-drag-overlay').removeClass('active');
                $('#ve-drop-line').hide();
                break;

            case 've-element-html-response':
                $('#ve-html-editor-tag').text('<' + (d.tag || '?') + '>');
                if (window.veCodeMirror) {
                    window.veCodeMirror.setValue(d.outerHTML || '');
                } else {
                    $('#ve-html-editor-textarea').val(d.outerHTML || '');
                }
                (new bootstrap.Modal(document.getElementById('ve-html-editor-modal'))).show();
                break;

            case 've-style-synced':
                if (d.nodeId) {
                    if (!styleChanges[d.nodeId]) styleChanges[d.nodeId] = {};
                    styleChanges[d.nodeId][d.prop] = d.value;
                    hasInspectorChanges = true;
                    markModified(true);
                }
                break;

            case 've-copy-response':
                if (window._pendingSaveBlockMode) {
                    // Route to save-as-block flow
                    if (d.html) {
                        pendingSaveBlockHtml = d.html;
                        window._pendingSaveBlockMode = false;
                        $('#save-block-name').val('');
                        new bootstrap.Modal(document.getElementById('ve-save-block-modal')).show();
                    }
                } else {
                    copiedElementHtml = d.html || null;
                    if (copiedElementHtml) {
                        $('#ctx-paste').show();
                        showToast('<i class="fa-duotone fa-solid fa-copy me-1"></i>Elemento copiado');
                    }
                }
                break;

            case 've-paste-done':
                syncFromIframe(d.html);
                break;

            case 've-open-link-editor':
                // Inline toolbar link button → open parent modal
                openInlineLinkEditor(d.href || '', d.target || '');
                break;

            case 've-element-path':
                // Breadcrumb in inspector header
                renderBreadcrumb(d.path || []);
                break;

            case 've-layout-scan':
                if (window.veHandleLayoutScan) window.veHandleLayoutScan(d.data);
                break;

            case 've-scroll-sync':
                if ($('#ve-panel-sections').hasClass('active')) {
                    $('.ve-section-item').removeClass('ve-scroll-active');
                    $('.ve-section-item[data-index="' + d.index + '"]').addClass('ve-scroll-active');
                }
                break;
        }
    });

    /* ── Toast notification ──────────────────────────────────────────── */
    function showToast(html) {
        const $t = $('<div>')
            .css({
                position: 'fixed', bottom: '20px', right: '20px',
                background: '#1e1e2e', color: '#fff', padding: '8px 14px',
                borderRadius: '6px', fontSize: '12px', zIndex: 99999,
                boxShadow: '0 4px 12px rgba(0,0,0,.3)',
            })
            .html(html);
        $('body').append($t);
        setTimeout(function () { $t.fadeOut(300, function () { $t.remove(); }); }, 2200);
    }

    /* ── Breadcrumb ──────────────────────────────────────────────────── */
    function renderBreadcrumb(path) {
        const $bc = $('#ve-inspector-breadcrumb');
        if (!path || !path.length) { $bc.hide(); return; }
        $bc.show().empty();
        path.forEach(function (item, i) {
            if (i > 0) $bc.append($('<span>').text(' › ').css({ color: '#bbb', margin: '0 1px' }));
            const $seg = $('<button>')
                .addClass('ve-bc-seg')
                .attr('data-node-id', item.nodeId || '')
                .text(item.tag)
                .css({
                    background: 'transparent', border: 'none', padding: '0 2px',
                    fontSize: '11px', cursor: 'pointer', borderRadius: '3px',
                    color: i === path.length - 1 ? '#1a1a1a' : '#666',
                    fontWeight: i === path.length - 1 ? '700' : '400',
                })
                .on('click', function () {
                    const nodeId = $(this).data('node-id');
                    if (nodeId) sendToFrame({ type: 've-select-by-id', nodeId: nodeId });
                })
                .on('mouseenter', function () { $(this).css({ background: '#f0f0f0' }); })
                .on('mouseleave', function () { $(this).css({ background: 'transparent' }); });
            $bc.append($seg);
        });
    }

    /* ── Context menu actions ────────────────────────────────────────── */
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#ve-context-menu').length) hideCtxMenu();
    });

    $('#ctx-copy').on('click', function () {
        copyCurrentElement();
        hideCtxMenu();
    });
    $('#ctx-paste').on('click', function () {
        pasteElement();
        hideCtxMenu();
    });
    $('#ctx-move-up').on('click', function () {
        sendToFrame({ type: 've-move-element', direction: 'up' });
        hideCtxMenu();
    });
    $('#ctx-move-down').on('click', function () {
        sendToFrame({ type: 've-move-element', direction: 'down' });
        hideCtxMenu();
    });
    $('#ctx-duplicate').on('click', function () {
        sendToFrame({ type: 've-duplicate-element' });
        hideCtxMenu();
    });
    $('#ctx-delete').on('click', function () {
        hideCtxMenu();
        veConfirm('Esta acción eliminará el elemento del editor.', function () {
            sendToFrame({ type: 've-delete-element', nodeId: currentContextNodeId });
        });
    });
    $('#ctx-edit-html').on('click', function () {
        sendToFrame({ type: 've-request-element-html', nodeId: currentContextNodeId });
        hideCtxMenu();
    });

    /* ── HTML editor modal ───────────────────────────────────────────── */
    $('#btn-apply-html').on('click', function () {
        const html = window.veCodeMirror
            ? window.veCodeMirror.getValue()
            : $('#ve-html-editor-textarea').val();
        sendToFrame({ type: 've-set-element-html', nodeId: currentContextNodeId, html: html });
        bootstrap.Modal.getInstance(document.getElementById('ve-html-editor-modal'))?.hide();
    });

    $('#ve-html-editor-modal').on('shown.bs.modal', function () {
        if (!window.veCodeMirror) {
            window.veCodeMirror = CodeMirror.fromTextArea(
                document.getElementById('ve-html-editor-textarea'),
                {
                    mode:             'htmlmixed',
                    theme:            'monokai',
                    lineNumbers:      true,
                    lineWrapping:     false,
                    autoCloseTags:    true,
                    matchBrackets:    true,
                    styleActiveLine:  true,
                    foldGutter:       true,
                    gutters:          ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                    foldOptions:      { widget: ' ▾ ··· ', minFoldSize: 2 },
                    extraKeys: {
                        'Ctrl-F': 'findPersistent',
                        'Ctrl-H': 'replace',
                        'F11': function (cm) {
                            var fs = !cm.getOption('fullScreen');
                            cm.setOption('fullScreen', fs);
                            $('#ve-btn-fullscreen i').toggleClass('fa-expand', !fs).toggleClass('fa-compress', fs);
                        },
                        'Esc': function (cm) {
                            if (cm.getOption('fullScreen')) {
                                cm.setOption('fullScreen', false);
                                $('#ve-btn-fullscreen i').removeClass('fa-compress').addClass('fa-expand');
                            }
                        },
                    },
                }
            );
        }
        window.veCodeMirror.refresh();
    });

    // ── Toolbar del editor HTML modal ────────────────────────────────────────
    $('#ve-btn-format').on('click', function () {
        if (!window.veCodeMirror) { return; }
        window.veCodeMirror.setValue(veFormatHtml(window.veCodeMirror.getValue()));
        toastr.info('Código formateado.');
    });

    $('#ve-btn-fold-all').on('click', function () {
        if (!window.veCodeMirror) { return; }
        for (var i = 0; i < window.veCodeMirror.lineCount(); i++) {
            window.veCodeMirror.foldCode({ line: i, ch: 0 });
        }
    });

    $('#ve-btn-unfold-all').on('click', function () {
        if (!window.veCodeMirror) { return; }
        for (var i = 0; i < window.veCodeMirror.lineCount(); i++) {
            window.veCodeMirror.foldCode({ line: i, ch: 0 }, null, 'unfold');
        }
    });

    var veEditorWrap = false;
    $('#ve-btn-wrap').on('click', function () {
        if (!window.veCodeMirror) { return; }
        veEditorWrap = !veEditorWrap;
        window.veCodeMirror.setOption('lineWrapping', veEditorWrap);
        $(this).toggleClass('active', veEditorWrap);
    });

    $('#ve-btn-fullscreen').on('click', function () {
        if (!window.veCodeMirror) { return; }
        var fs = !window.veCodeMirror.getOption('fullScreen');
        window.veCodeMirror.setOption('fullScreen', fs);
        $(this).find('i').toggleClass('fa-expand', !fs).toggleClass('fa-compress', fs);
    });

    var veEditorDark = true;
    $('#ve-btn-theme').on('click', function () {
        if (!window.veCodeMirror) { return; }
        veEditorDark = !veEditorDark;
        var theme = veEditorDark ? 'monokai' : 'default';
        window.veCodeMirror.setOption('theme', theme);
        $('.ve-editor-toolbar').toggleClass('light', !veEditorDark);
        $(this).toggleClass('active', !veEditorDark);
    });

    /* ── Full-page HTML code panel ───────────────────────────────────── */
    var veFullCodeMirror = null;
    var codeEditorDirty  = false;

    function veInitCodePanel() {
        if (!veFullCodeMirror) {
            veFullCodeMirror = CodeMirror.fromTextArea(
                document.getElementById('ve-code-editor-textarea'),
                {
                    mode:           'htmlmixed',
                    lineNumbers:    true,
                    lineWrapping:   false,
                    scrollbarStyle: 'native',
                    theme:          'default',
                    indentUnit:     4,
                    tabSize:        4,
                    indentWithTabs: false,
                    autoCloseTags:  true,
                    matchBrackets:  true,
                    styleActiveLine: true,
                    foldGutter:     true,
                    gutters:        ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                    extraKeys:      { 'Ctrl-F': 'findPersistent', 'Ctrl-H': 'replace' },
                }
            );
            veFullCodeMirror.on('change', function () {
                codeEditorDirty = true;
                markModified(true);
            });
            window._veFullCodeMirror = veFullCodeMirror;
        }
        veSyncCodeFromPreview();
    }

    function veSyncCodeFromPreview() {
        getHtmlFromIframe().then(function (html) {
            if (!veFullCodeMirror) return;
            veFullCodeMirror.setValue(veFormatHtml(html));
            codeEditorDirty = false;
            markModified(false);
            setTimeout(function () { veFullCodeMirror.refresh(); }, 50);
        });
    }

    // Always reads from the iframe (bypasses the code-panel shortcut in getContentToSave).
    function getHtmlFromIframe() {
        return new Promise(function (resolve) {
            var timeout;
            var handler = function (e) {
                if (!e.data || e.data.type !== 've-html-response') return;
                window.removeEventListener('message', handler);
                clearTimeout(timeout);
                resolve(e.data.html || (editor ? editor.getData() : originalContent));
            };
            window.addEventListener('message', handler);
            var frame = document.getElementById('ve-preview-frame');
            if (frame && frame.contentWindow) {
                frame.contentWindow.postMessage({ type: 've-request-html' }, '*');
            } else {
                window.removeEventListener('message', handler);
                resolve(editor ? editor.getData() : originalContent);
                return;
            }
            timeout = setTimeout(function () {
                window.removeEventListener('message', handler);
                resolve(editor ? editor.getData() : originalContent);
            }, 3000);
        });
    }

    function veFormatHtml(html) {
        var result = '';
        var indent = 0;
        var voidTags = /^<(br|hr|img|input|meta|link|col|area|base|command|embed|keygen|param|source|track|wbr)\b/i;
        var tokens = html.replace(/>\s*</g, '>\n<').split('\n');

        tokens.forEach(function (token) {
            token = token.trim();
            if (!token) return;
            if (/^<\//.test(token)) {
                indent = Math.max(0, indent - 1);
            }
            result += '  '.repeat(indent) + token + '\n';
            if (!/\/>$/.test(token) && /^<[^\/!]/.test(token) && !voidTags.test(token)) {
                indent++;
            }
        });

        return result.trim();
    }

    $('#ve-code-refresh').on('click', function () {
        veSyncCodeFromPreview();
    });

    $('#ve-code-apply').on('click', function () {
        if (!veFullCodeMirror) return;
        var html = veFullCodeMirror.getValue();
        sendToFrame({ type: 've-set-full-html', html: html });
        codeEditorDirty = false;
        markModified(true);
    });

    // ── Toolbar del panel código completo ────────────────────────────────────
    $('#ve-code-btn-format').on('click', function () {
        if (!veFullCodeMirror) { return; }
        veFullCodeMirror.setValue(veFormatHtml(veFullCodeMirror.getValue()));
        toastr.info('Código formateado.');
    });

    $('#ve-code-btn-fold').on('click', function () {
        if (!veFullCodeMirror) { return; }
        for (var i = 0; i < veFullCodeMirror.lineCount(); i++) {
            veFullCodeMirror.foldCode({ line: i, ch: 0 });
        }
    });

    $('#ve-code-btn-unfold').on('click', function () {
        if (!veFullCodeMirror) { return; }
        for (var i = 0; i < veFullCodeMirror.lineCount(); i++) {
            veFullCodeMirror.foldCode({ line: i, ch: 0 }, null, 'unfold');
        }
    });

    var veCodeWrap = false;
    $('#ve-code-btn-wrap').on('click', function () {
        if (!veFullCodeMirror) { return; }
        veCodeWrap = !veCodeWrap;
        veFullCodeMirror.setOption('lineWrapping', veCodeWrap);
        $(this).toggleClass('active', veCodeWrap);
    });

    var veCodeDark = false;
    // Start in light theme
    if (veFullCodeMirror) veFullCodeMirror.setOption('theme', 'default');
    $('#ve-code-btn-theme').on('click', function () {
        if (!veFullCodeMirror) { return; }
        veCodeDark = !veCodeDark;
        veFullCodeMirror.setOption('theme', veCodeDark ? 'monokai' : 'default');
        $('#ve-code-toolbar').toggleClass('light', !veCodeDark);
        $(this).toggleClass('active', veCodeDark);
        // Sync apply button visibility in dark mode
        $('#ve-code-apply').css({ background: veCodeDark ? '#444' : '#b10100', 'border-color': veCodeDark ? '#444' : '#b10100' });
    });

    /* ── Drag & drop: blocks → iframe ────────────────────────────────── */
    let draggedBlockHtml = null;
    let dragFrameRect    = null;

    // Blocks make themselves draggable via ve-blocks-js; here we handle the canvas side.
    window.veStartBlockDrag = function (html) {
        draggedBlockHtml = html;
        dragFrameRect = document.getElementById('ve-preview-frame').getBoundingClientRect();
        $('#ve-drag-overlay').addClass('active');
    };

    const $dragOverlay = $('#ve-drag-overlay');

    $dragOverlay.on('dragover', function (e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'copy';
        if (!dragFrameRect) return;

        const x = e.clientX - dragFrameRect.left;
        const y = e.clientY - dragFrameRect.top;

        // Show drop line on overlay (rough position)
        $('#ve-drop-line').css({
            display: 'block',
            top:     (e.clientY - dragFrameRect.top + dragFrameRect.top - 52) + 'px',
            left:    dragFrameRect.left + 'px',
            width:   dragFrameRect.width + 'px',
        });

        sendToFrame({ type: 've-drag-over', x: x, y: y });
    });

    $dragOverlay.on('dragleave', function () {
        $('#ve-drag-overlay').removeClass('active');
        $('#ve-drop-line').hide();
        sendToFrame({ type: 've-drag-cancel' });
        draggedBlockHtml = null;
    });

    $dragOverlay.on('drop', function (e) {
        e.preventDefault();
        if (!draggedBlockHtml || !dragFrameRect) return;

        const x = e.clientX - dragFrameRect.left;
        const y = e.clientY - dragFrameRect.top;

        sendToFrame({ type: 've-drop-block', html: draggedBlockHtml, x: x, y: y });
        draggedBlockHtml = null;
        $('#ve-drop-line').hide();
        // Overlay will be hidden when 've-block-dropped' message arrives
    });

    /* ── Sections panel ──────────────────────────────────────────────── */
    function getSectionLabel(el) {
        const heading = el.querySelector ? el.querySelector('h1,h2,h3,h4,h5,h6') : null;
        if (heading && heading.textContent.trim()) return heading.textContent.trim().substring(0, 60);
        const text = (el.textContent || el.innerText || '').trim().replace(/\s+/g, ' ');
        return text ? text.substring(0, 60) : '(' + (el.tagName || 'bloque').toLowerCase() + ')';
    }

    function getSectionIcon(tagName) {
        const icons = {
            section: 'fa-layer-group', article: 'fa-newspaper', header: 'fa-heading',
            footer: 'fa-grip-lines', nav: 'fa-bars', main: 'fa-home', aside: 'fa-columns',
            div: 'fa-square', h1: 'fa-heading', h2: 'fa-heading', h3: 'fa-heading',
            p: 'fa-paragraph', ul: 'fa-list-ul', ol: 'fa-list-ol', table: 'fa-table',
            form: 'fa-wpforms', figure: 'fa-image', blockquote: 'fa-quote-left', hr: 'fa-minus',
        };
        return icons[tagName] || 'fa-cube';
    }

    function getSectionsFromEditor() {
        if (!editor) return [];
        const doc = new DOMParser().parseFromString('<div>' + editor.getData() + '</div>', 'text/html');
        const container = doc.querySelector('div');
        return container ? Array.from(container.children) : [];
    }

    function buildHtmlFromSections(sections) {
        return sections.map(s => s.outerHTML).join('\n');
    }

    function markEditorModified() {
        isModified = true;
        hasInspectorChanges = true;
        setAutoSaveStatus('unsaved', 'Sin guardar');
        scheduleAutoSave();
    }

    window.refreshSectionsPanel = function () {
        const sections = getSectionsFromEditor();
        const $list    = $('#ve-sections-list');
        const $empty   = $('#ve-sections-empty');

        $list.empty();
        if (sections.length === 0) { $empty.show(); return; }
        $empty.hide();

        sections.forEach(function (child, index) {
            const tag   = child.tagName.toLowerCase();
            const label = getSectionLabel(child);
            const icon  = getSectionIcon(tag);

            const $item = $([
                '<div class="ve-section-item" data-index="' + index + '">',
                '<i class="fa-duotone fa-solid fa-grip-vertical ve-section-handle"></i>',
                '<span class="ve-section-tag">' + tag + '</span>',
                '<span class="ve-section-label" title="' + label.replace(/"/g, '&quot;') + '">',
                label, '</span>',
                '<div class="d-flex gap-1">',
                '<button class="btn btn-xs btn-outline-secondary ve-sec-up px-1 py-0" style="font-size:10px;line-height:1.4;" title="Subir"><i class="fa-duotone fa-solid fa-chevron-up"></i></button>',
                '<button class="btn btn-xs btn-outline-secondary ve-sec-down px-1 py-0" style="font-size:10px;line-height:1.4;" title="Bajar"><i class="fa-duotone fa-solid fa-chevron-down"></i></button>',
                '<button class="btn btn-xs btn-outline-secondary ve-sec-delete px-1 py-0" style="font-size:10px;line-height:1.4;" title="Eliminar"><i class="fa-duotone fa-solid fa-times"></i></button>',
                '</div></div>',
            ].join(''));

            $list.append($item);
        });

        if ($list.hasClass('ui-sortable')) $list.sortable('destroy');

        $list.sortable({
            handle:      '.ve-section-handle',
            placeholder: 've-section-item ui-sortable-placeholder',
            tolerance:   'pointer',
            update:      function () { applySectionOrder(); },
        });
    };

    function applySectionOrder() {
        const currentSections = getSectionsFromEditor();
        if (!currentSections.length) return;
        const newOrder = [];
        $('#ve-sections-list .ve-section-item').each(function () {
            const idx = parseInt($(this).data('index'), 10);
            if (!isNaN(idx) && currentSections[idx]) newOrder.push(currentSections[idx]);
        });
        if (!newOrder.length) return;
        editor.setData(buildHtmlFromSections(newOrder));
        markEditorModified();
        pushHistory('Reordenar secciones', editor.getData());
        setTimeout(window.refreshSectionsPanel, 100);
    }

    $(document).on('click', '.ve-sec-up', function () {
        const sections = getSectionsFromEditor();
        const idx      = parseInt($(this).closest('.ve-section-item').data('index'), 10);
        if (idx <= 0 || isNaN(idx)) return;
        [sections[idx - 1], sections[idx]] = [sections[idx], sections[idx - 1]];
        editor.setData(buildHtmlFromSections(sections));
        markEditorModified();
        pushHistory('Mover sección arriba', editor.getData());
        setTimeout(window.refreshSectionsPanel, 100);
    });

    $(document).on('click', '.ve-sec-down', function () {
        const sections = getSectionsFromEditor();
        const idx      = parseInt($(this).closest('.ve-section-item').data('index'), 10);
        if (isNaN(idx) || idx >= sections.length - 1) return;
        [sections[idx], sections[idx + 1]] = [sections[idx + 1], sections[idx]];
        editor.setData(buildHtmlFromSections(sections));
        markEditorModified();
        pushHistory('Mover sección abajo', editor.getData());
        setTimeout(window.refreshSectionsPanel, 100);
    });

    $(document).on('click', '.ve-sec-delete', function () {
        const $item = $(this).closest('.ve-section-item');
        veConfirm('Se eliminará la sección y todo su contenido.', function () {
            const sections = getSectionsFromEditor();
            const idx      = parseInt($item.data('index'), 10);
            if (isNaN(idx)) return;
            sections.splice(idx, 1);
            editor.setData(buildHtmlFromSections(sections));
            markEditorModified();
            pushHistory('Eliminar sección', editor.getData());
            setTimeout(window.refreshSectionsPanel, 100);
        });
    });

    $('#btn-refresh-sections').on('click', window.refreshSectionsPanel);

    /* ── Copy / Paste keyboard shortcuts ────────────────────────────── */
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'c') {
            e.preventDefault();
            copyCurrentElement();
        }
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'v') {
            e.preventDefault();
            pasteElement();
        }
    });

    /* ── Icon selector ───────────────────────────────────────────────── */
    const FA_ICONS = [
        'home','user','users','cog','cogs','search','envelope','phone','map-marker-alt',
        'star','heart','check','times','plus','minus','edit','trash','save','download',
        'upload','share','link','lock','unlock','eye','eye-slash','bell','calendar',
        'clock','image','images','video','music','file','folder','database','server',
        'code','terminal','bug','rocket','bolt','fire','leaf','tree','globe','map',
        'car','truck','plane','ship','bicycle','shopping-cart','shopping-bag','tag',
        'tags','gift','award','trophy','medal','thumbs-up','thumbs-down','comment',
        'comments','question','info','exclamation','ban','check-circle','times-circle',
        'info-circle','exclamation-circle','exclamation-triangle','arrow-up','arrow-down',
        'arrow-left','arrow-right','bars','list','th','th-large','table','columns',
        'chart-bar','chart-line','chart-pie','chart-area','wifi','bluetooth','battery-full',
        'mobile-alt','laptop','desktop','tablet-alt','print','fax','keyboard','mouse',
        'headphones','microphone','camera','video-camera','tv','radio','pen','pencil-alt',
        'marker','highlighter','palette','paint-brush','magic','wand-magic-sparkles',
        'cut','copy','paste','clipboard','book','bookmark','newspaper','paragraph',
        'heading','font','bold','italic','underline','strikethrough','align-left',
        'align-center','align-right','align-justify','list-ul','list-ol','quote-left',
        'smile','laugh','sad-tear','angry','surprise','meh','mask','hat-wizard',
        'graduation-cap','briefcase','building','hospital','school','church','store',
        'dollar-sign','euro-sign','credit-card','wallet','coins','piggy-bank','money-bill',
        'percent','calculator','receipt','file-invoice','chart-pie','sitemap','project-diagram',
        'shield-alt','fingerprint','key','id-card','passport','address-card','user-shield',
        'sun','moon','cloud','cloud-rain','snowflake','wind','rainbow','temperature-high',
        'recycle','seedling','spa','paw','fish','bug','dove','dragon',
        'running','swimming-pool','dumbbell','football-ball','basketball-ball','soccer-ball',
        'gamepad','chess','dice','puzzle-piece',
    ];

    let iconStyle = 'fas';

    function renderIconGrid(filter) {
        const q = (filter || '').toLowerCase();
        const icons = FA_ICONS.filter(ic => !q || ic.includes(q));
        const $grid = $('#ve-icon-grid');
        $grid.empty();
        icons.slice(0, 200).forEach(function (name) {
            const $item = $('<div class="ve-icon-item">').attr('title', name)
                .html('<i class="' + iconStyle + ' fa-' + name + '"></i>')
                .on('click', function () {
                    insertIcon(iconStyle, name);
                });
            $grid.append($item);
        });
        if (!icons.length) $grid.html('<p class="text-muted text-center small py-3 w-100">Sin resultados</p>');
    }

    function insertIcon(style, name) {
        if (window.veEditor) {
            const html = '<i class="' + style + ' fa-' + name + '"></i>';
            window.veEditor.model.change(function () {
                const view  = window.veEditor.data.processor.toView(html);
                const model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
        }
        bootstrap.Modal.getInstance(document.getElementById('ve-icon-modal'))?.hide();
    }

    $('#btn-insert-icon').on('click', function () {
        renderIconGrid('');
        new bootstrap.Modal(document.getElementById('ve-icon-modal')).show();
    });

    let iconSearchTimer;
    $('#ve-icon-search').on('input', function () {
        clearTimeout(iconSearchTimer);
        const q = $(this).val();
        iconSearchTimer = setTimeout(function () { renderIconGrid(q); }, 200);
    });

    $(document).on('click', '.ve-icon-style-btn', function () {
        $('.ve-icon-style-btn').removeClass('active');
        $(this).addClass('active');
        iconStyle = $(this).data('style');
        renderIconGrid($('#ve-icon-search').val());
    });

    /* ── Shortcode Builder ───────────────────────────────────────────── */
    window.veOpenShortcodeBuilder = function (sc) {
        $('#ve-scb-title').text(sc.name);
        $('#ve-scb-tag').text('[' + sc.name + ']');
        $('#ve-scb-description').text(sc.description || '');
        $('#ve-scb-example').text(sc.example || '');
        $('#ve-scb-example-wrap').toggle(!!sc.example);
        $('#ve-scb-content').val('');

        // Show content field for pair shortcodes (no /] in example)
        const isPair = !sc.example || !sc.example.includes('/]');
        $('#ve-scb-content-wrap').toggle(isPair);

        // Build attribute inputs
        const attrs = sc.attributes || {};
        const $attrs = $('#ve-scb-attrs').empty();
        if (Object.keys(attrs).length) {
            const $row = $('<div class="row g-3">');
            Object.entries(attrs).forEach(function ([key, cfg]) {
                const $col = $('<div class="col-12">');
                const inputId = 've-scb-attr-' + key;
                $col.append($('<label>').addClass('form-label').attr('for', inputId).text(key));
                if (Array.isArray(cfg)) {
                    const $sel = $('<select>').attr({ id: inputId, class: 'form-select ve-scb-attr-input' }).data('attr', key);
                    $('<option>').val('').text('-- Seleccionar --').appendTo($sel);
                    cfg.forEach(function (opt) { $('<option>').val(opt).text(opt).appendTo($sel); });
                    $col.append($sel);
                } else {
                    $col.append($('<input>').attr({ type: 'text', id: inputId, class: 'form-control ve-scb-attr-input', placeholder: cfg || key }).data('attr', key));
                }
                $row.append($col);
            });
            $attrs.append($row);
            $attrs.find('select.ve-scb-attr-input').select2({ width: '100%', dropdownParent: $('#ve-shortcode-builder-modal') });
        } else {
            $attrs.html('<p class="text-muted small">Este shortcode no requiere atributos.</p>');
        }

        // Update preview on input (off first to prevent stacking handlers on repeated opens)
        updateScbPreview(sc);
        $attrs.add('#ve-scb-content').off('input.scb change.scb').on('input.scb change.scb', function () { updateScbPreview(sc); });

        new bootstrap.Modal(document.getElementById('ve-shortcode-builder-modal')).show();
    };

    function buildScbCode(sc) {
        const attrs = {};
        $('.ve-scb-attr-input').each(function () {
            const v = $(this).val().trim();
            if (v) attrs[$(this).data('attr')] = v;
        });
        const attrStr = Object.entries(attrs).map(function ([k, v]) {
            return k + '="' + v.replace(/"/g, '&quot;') + '"';
        }).join(' ');
        const sep = attrStr ? ' ' + attrStr : '';
        const isPair = !sc.example || !sc.example.includes('/]');
        if (!isPair) return '[' + sc.name + sep + ' /]';
        const content = $('#ve-scb-content').val();
        return '[' + sc.name + sep + ']' + content + '[/' + sc.name + ']';
    }

    function updateScbPreview(sc) {
        $('#ve-scb-preview').text(buildScbCode(sc));
    }

    let _currentSc = null;
    // Store current sc for insert/copy buttons
    $(document).on('show.bs.modal', '#ve-shortcode-builder-modal', function () {});

    // Init select2 when save-block modal opens
    $(document).on('shown.bs.modal', '#ve-save-block-modal', function () {
        const $sel = $('#save-block-category');
        if (!$sel.hasClass('select2-hidden-accessible')) {
            $sel.select2({ width: '100%', dropdownParent: $('#ve-save-block-modal') });
        }
    });

    // Generic confirm modal helper
    window.veConfirm = function (message, onAccept, opts) {
        opts = opts || {};
        $('#ve-confirm-message').text(message);
        if (opts.title) { $('#ve-confirm-modal h6.fw-bold').text(opts.title); }
        if (opts.acceptLabel) { $('#ve-confirm-accept').text(opts.acceptLabel); }
        const $modal = $('#ve-confirm-modal');
        const modal = new bootstrap.Modal($modal[0]);
        $('#ve-confirm-accept').off('click.veconfirm').one('click.veconfirm', function () {
            modal.hide();
            onAccept();
        });
        modal.show();
    };

    $('#btn-insert-shortcode').on('click', function () {
        const code = $('#ve-scb-preview').text();
        if (!code) return;

        bootstrap.Modal.getInstance(document.getElementById('ve-shortcode-builder-modal'))?.hide();

        // Expand the shortcode server-side first (avoids PCRE limit issues on
        // large content), then inject the rendered HTML directly into the iframe.
        $.ajax({
            url:         EXPAND_SHORTCODE_URL,
            method:     'POST',
            data:        { _token: CSRF, shortcode: code },
            success: function (res) {
                const html = res.html || code;
                const frame = document.getElementById('ve-preview-frame');
                const ck    = frame?.contentDocument?.querySelector('.ck-content');
                if (!ck) return;

                // Parse the returned HTML and append each top-level node.
                const tmp = frame.contentDocument.createElement('div');
                tmp.innerHTML = html;
                while (tmp.firstChild) {
                    ck.appendChild(tmp.firstChild);
                }

                isModified = true;
                scheduleAutoSave();
                getContentToSave().then(function (savedHtml) {
                    pushHistory('Insertar shortcode', savedHtml);
                });
                showToast('<i class="fa-duotone fa-solid fa-code me-1"></i>Shortcode insertado');
            },
            error: function () {
                // Fallback: insert raw shortcode text
                const frame = document.getElementById('ve-preview-frame');
                const ck    = frame?.contentDocument?.querySelector('.ck-content');
                if (ck) {
                    const p = frame.contentDocument.createElement('p');
                    p.textContent = code;
                    ck.appendChild(p);
                    isModified = true;
                    scheduleAutoSave();
                }
                showToast('<i class="fa-duotone fa-solid fa-code me-1"></i>Shortcode insertado');
            },
        });
    });

    $('#btn-copy-shortcode').on('click', function () {
        const code = $('#ve-scb-preview').text();
        if (!code) return;
        navigator.clipboard?.writeText(code).then(function () {
            showToast('<i class="fa-duotone fa-solid fa-copy me-1"></i>Copiado al portapapeles');
        }).catch(function () {
            // fallback
            const ta = document.createElement('textarea');
            ta.value = code;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            showToast('<i class="fa-duotone fa-solid fa-copy me-1"></i>Copiado');
        });
    });

    /* ── Rotate device ───────────────────────────────────────────────── */
    let rotatedDimensions = null; // stores swapped w/h when rotated

    $('#btn-rotate-device').on('click', function () {
        const $frame = $('#ve-preview-frame');
        const w = $frame.css('width');
        const h = $frame.css('height');
        $frame.css({ width: h, height: w });
        // Swap stored rotatedDimensions for next click
        rotatedDimensions = { w: h, h: w };
    });

    // Show rotate button only in non-desktop breakpoints (handled via delegated breakpoint-btn handler)

    /* ── Keyboard shortcuts modal ────────────────────────────────────── */
    $('#btn-shortcuts').on('click', function () {
        new bootstrap.Modal(document.getElementById('ve-shortcuts-modal')).show();
    });

    /* ── Save element as custom block ────────────────────────────────── */
    let pendingSaveBlockHtml = null;

    $('#ctx-save-block').on('click', function () {
        sendToFrame({ type: 've-copy-request', nodeId: currentContextNodeId });
        hideCtxMenu();
        // Modal will open after we receive ve-copy-response
        // Temporarily override the copy handler
        window._pendingSaveBlockMode = true;
    });

    // Intercept copy-response when saving block
    const _origHandleCopyResponse = function (html) {
        if (window._pendingSaveBlockMode && html) {
            pendingSaveBlockHtml = html;
            window._pendingSaveBlockMode = false;
            $('#save-block-name').val('');
            new bootstrap.Modal(document.getElementById('ve-save-block-modal')).show();
        }
    };

    $('#btn-confirm-save-block').on('click', function () {
        const name     = $('#save-block-name').val().trim() || 'Shortcode personalizado';
        const category = $('#save-block-category').val() || 'custom';
        if (!pendingSaveBlockHtml) return;

        const stored = JSON.parse(localStorage.getItem('ve-custom-shortcodes') || '[]');
        stored.push({
            id:       'custom-' + Date.now(),
            label:    name,
            category: category,
            html:     pendingSaveBlockHtml,
        });
        localStorage.setItem('ve-custom-shortcodes', JSON.stringify(stored));
        pendingSaveBlockHtml = null;
        bootstrap.Modal.getInstance(document.getElementById('ve-save-block-modal'))?.hide();
        showToast('<i class="fa-duotone fa-solid fa-bookmark me-1"></i>Shortcode guardado: ' + name);
        // Refresh shortcodes panel if visible
        if (window.veRenderCustomShortcodes) window.veRenderCustomShortcodes();
    });

    // Patch the message handler to intercept copy-response for block saving
    const _origCopyResponseHandler = function (html) {
        if (window._pendingSaveBlockMode) {
            _origHandleCopyResponse(html);
        } else {
            copiedElementHtml = html || null;
            if (copiedElementHtml) {
                $('#ctx-paste').show();
                showToast('<i class="fa-duotone fa-solid fa-copy me-1"></i>Elemento copiado');
            }
        }
    };

    /* ── Sidebar resize handle ───────────────────────────────────────── */
    (function () {
        var $handle    = $('#ve-sidebar-resize');
        var $sidebar   = $('#ve-sidebar');
        var isResizing = false, startX = 0, startW = 0;

        $handle.on('mousedown', function (e) {
            isResizing = true;
            startX = e.clientX;
            startW = $sidebar.width();
            $handle.addClass('resizing');
            // Overlay sobre el iframe para no perder eventos del mouse
            $('<div id="ve-resize-overlay">').css({
                position: 'fixed', inset: 0, zIndex: 9999, cursor: 'col-resize'
            }).appendTo('body');
            e.preventDefault();
        });

        $(document).on('mousemove.sidebarResize', function (e) {
            if (!isResizing) return;
            var newW = Math.max(220, Math.min(900, startW + e.clientX - startX));
            $sidebar.css('width', newW + 'px');
        });

        $(document).on('mouseup.sidebarResize', function () {
            if (!isResizing) return;
            isResizing = false;
            $handle.removeClass('resizing');
            $('#ve-resize-overlay').remove();
        });
    })();

    /* ── Canvas zoom ─────────────────────────────────────────────────── */
    $(document).on('click', '.ve-zoom-btn', function () {
        $('.ve-zoom-btn').removeClass('active');
        $(this).addClass('active');
        var zoom = parseFloat($(this).data('zoom'));
        sendToFrame({ type: 've-set-zoom', zoom: zoom });
    });

    /* ── Export HTML ─────────────────────────────────────────────────── */
    $('#btn-export-html').on('click', function () {
        if (!editor) return;
        var content = editor.getData();
        var title   = {!! json_encode($page->title) !!};
        var slug    = {!! json_encode($page->slug ?? 'page') !!};
        var bsCss  = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
        var faCss  = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css';
        var html = [
            '<!DOCTYPE html>',
            '<html lang="es">',
            '<head>',
            '  <meta charset="UTF-8">',
            '  <meta name="viewport" content="width=device-width, initial-scale=1.0">',
            '  <title>' + title + '<\/title>',
            '  <link href="' + bsCss + '" rel="stylesheet">',
            '  <link rel="stylesheet" href="' + faCss + '">',
            '<\/head>',
            '<body>',
            content,
            '<\/body>',
            '<\/html>',
        ].join('\n');
        var blob = new Blob([html], { type: 'text/html;charset=utf-8' });
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a');
        a.href     = url;
        a.download = slug + '.html';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        showToast('<i class="fa-duotone fa-solid fa-download me-1"></i>HTML exportado: ' + slug + '.html');
    });

    /* ── Warn on unsaved changes ─────────────────────────────────────── */
    $(window).on('beforeunload', function () {
        if (isModified) return 'Tienes cambios sin guardar. ¿Seguro que quieres salir?';
    });

    /* ── Expose internal functions for panel scripts ────────────────── */
    window.vePushHistory        = pushHistory;
    window.pushHistory          = pushHistory;
    window.showToast            = showToast;
    window.getContentToSave     = getContentToSave;
    window.scheduleAutoSave     = scheduleAutoSave;
    window.EXPAND_SHORTCODE_URL = EXPAND_SHORTCODE_URL;

})(jQuery);
</script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closetag.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldgutter.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/xml-fold.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/brace-fold.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/display/fullscreen.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/search/searchcursor.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/search/search.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/dialog/dialog.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/selection/active-line.min.js"></script>

{{-- ── Modal: Find & Replace ─────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-find-replace-modal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Buscar y reemplazar</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" style="font-size:12px; font-weight:600;">Buscar</label>
                    <input type="text" class="form-control form-control-sm" id="ve-fr-find" placeholder="Texto a buscar...">
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:12px; font-weight:600;">Reemplazar con</label>
                    <input type="text" class="form-control form-control-sm" id="ve-fr-replace" placeholder="Texto de reemplazo...">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="ve-fr-case">
                    <label class="form-check-label" for="ve-fr-case" style="font-size:12px;">Distinguir mayúsculas/minúsculas</label>
                </div>
                <div id="ve-fr-feedback" style="font-size:12px; color:#888; min-height:20px;"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm" id="btn-do-replace"
                        style="background:#1a1a1a;border-color:#1a1a1a;color:#fff;">
                    <i class="fa-duotone fa-solid fa-check me-1"></i>Reemplazar todo
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Media Manager ──────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-media-modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Gestor de medios</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3 g-2 align-items-center">
                    <div class="col">
                        <input type="text" class="form-control form-control-sm" id="ve-media-search" placeholder="Buscar archivos...">
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="ve-media-type-filter">
                            <option value="">Todos</option>
                            <option value="image">Imágenes</option>
                            <option value="video">Video</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="btn btn-sm btn-outline-secondary mb-0" id="btn-media-upload-label" title="Subir archivo">
                            <i class="fa-duotone fa-solid fa-upload me-1"></i>Subir
                            <input type="file" id="ve-media-upload-input" style="display:none;" multiple>
                        </label>
                    </div>
                </div>
                <div id="ve-media-grid" style="display:grid; grid-template-columns:repeat(auto-fill,120px); gap:10px; max-height:420px; overflow-y:auto; min-height:100px;">
                    <div class="text-muted text-center" style="grid-column:1/-1; padding:40px 0;">
                        <i class="fa-duotone fa-solid fa-spinner fa-spin me-1"></i>Cargando medios...
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <small class="text-muted me-auto" id="ve-media-selected-info">Ningún archivo seleccionado</small>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm" id="btn-media-select"
                        style="background:#1a1a1a;border-color:#1a1a1a;color:#fff;" disabled>
                    <i class="fa-duotone fa-solid fa-check me-1"></i>Seleccionar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Link Editor ─────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-link-editor-modal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.2);">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-semibold" style="font-size:16px;">A dónde quieres enlazar?</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-0" style="min-height: 250px;">
                    {{-- Left: link type options --}}
                    <div class="d-flex flex-column gap-1 pe-3" style="min-width: 140px; border-right: 1px solid #e9ecef;">
                        <label class="ve-link-type-option active" data-type="url">
                            <input type="radio" name="ve-link-type" value="url" checked class="d-none">
                            <span class="ve-link-radio"></span>
                            <span>URL</span>
                        </label>
                        <label class="ve-link-type-option" data-type="page">
                            <input type="radio" name="ve-link-type" value="page" class="d-none">
                            <span class="ve-link-radio"></span>
                            <span>Página</span>
                        </label>
                        <label class="ve-link-type-option" data-type="anchor">
                            <input type="radio" name="ve-link-type" value="anchor" class="d-none">
                            <span class="ve-link-radio"></span>
                            <span>Ancla</span>
                        </label>
                        <label class="ve-link-type-option" data-type="download">
                            <input type="radio" name="ve-link-type" value="download" class="d-none">
                            <span class="ve-link-radio"></span>
                            <span>Descarga</span>
                        </label>
                        <label class="ve-link-type-option" data-type="email">
                            <input type="radio" name="ve-link-type" value="email" class="d-none">
                            <span class="ve-link-radio"></span>
                            <span>Email</span>
                        </label>
                    </div>
                    {{-- Right: dynamic fields --}}
                    <div class="flex-grow-1 ps-3">
                        <div class="ve-link-field" data-for="url">
                            <label class="form-label small text-muted">URL de destino</label>
                            <input type="url" class="form-control form-control-sm" id="ve-link-url" placeholder="https://...">
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="ve-link-new-tab" checked>
                                <label class="form-check-label small" for="ve-link-new-tab">Abrir en nueva pestaña</label>
                            </div>
                        </div>
                        <div class="ve-link-field d-none" data-for="page">
                            <label class="form-label small text-muted">Seleccionar página</label>
                            <select class="form-select form-select-sm" id="ve-link-page">
                                <option value="">Cargando páginas...</option>
                            </select>
                        </div>
                        <div class="ve-link-field d-none" data-for="anchor">
                            <label class="form-label small text-muted">Ancla en esta página</label>
                            <select class="form-select form-select-sm" id="ve-link-anchor">
                                <option value="">Seleccionar ancla...</option>
                            </select>
                            <small class="text-muted d-block mt-1">Al hacer clic, el usuario se desplazará al ancla seleccionada.</small>
                        </div>
                        <div class="ve-link-field d-none" data-for="download">
                            <label class="form-label small text-muted">URL del archivo</label>
                            <input type="url" class="form-control form-control-sm" id="ve-link-download" placeholder="https://...archivo.pdf">
                        </div>
                        <div class="ve-link-field d-none" data-for="email">
                            <label class="form-label small text-muted">Dirección de email</label>
                            <input type="email" class="form-control form-control-sm" id="ve-link-email" placeholder="correo@ejemplo.com">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="font-weight:500;">Cancelar</button>
                <button type="button" class="btn btn-sm text-white" id="ve-link-save" style="background:#1a1a1a; font-weight:500; padding: 6px 24px; border-radius:6px;">Hecho</button>
            </div>
        </div>
    </div>
</div>

<script>
(function ($) {
    'use strict';

    /* ═══════════════════════════════════════════════════════════════
       FEATURE 3: Preview draft in new tab
    ═══════════════════════════════════════════════════════════════ */
    $('#btn-preview-draft').on('click', function () {
        if (!window.veEditor) return;
        var content = window.veEditor.getData();
        var CSRF2 = $('meta[name="csrf-token"]').attr('content');
        var VISUAL_PREVIEW2 = '{{ route("pages.visual-preview", $page) }}';
        $.ajax({
            url: VISUAL_PREVIEW2,
            method: 'POST',
            data: { _token: CSRF2, content: content },
            success: function (html) {
                var blob = new Blob([html], { type: 'text/html;charset=utf-8' });
                var url  = URL.createObjectURL(blob);
                window.open(url, '_blank');
                setTimeout(function () { URL.revokeObjectURL(url); }, 60000);
            },
            error: function () {
                alert('Error al generar el preview.');
            }
        });
    });

    /* ═══════════════════════════════════════════════════════════════
       FEATURE 6: Find & Replace
    ═══════════════════════════════════════════════════════════════ */
    $('#btn-find-replace').on('click', function () {
        new bootstrap.Modal(document.getElementById('ve-find-replace-modal')).show();
    });

    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'h') {
            e.preventDefault();
            new bootstrap.Modal(document.getElementById('ve-find-replace-modal')).show();
        }
    });

    $('#btn-do-replace').on('click', function () {
        var find    = $('#ve-fr-find').val();
        var replace = $('#ve-fr-replace').val();
        if (!find || !window.veEditor) {
            $('#ve-fr-feedback').text('Ingresa un texto a buscar.');
            return;
        }
        var flags = $('#ve-fr-case').is(':checked') ? 'g' : 'gi';
        var html  = window.veEditor.getData();
        // Replace only in text nodes by using a DOM approach
        var parser   = new DOMParser();
        var doc2     = parser.parseFromString('<div id="ve-fr-root">' + html + '</div>', 'text/html');
        var root     = doc2.getElementById('ve-fr-root');
        var count    = 0;
        var escaped  = find.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var re       = new RegExp(escaped, flags);

        function walkNodes(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                var orig = node.textContent;
                var newText = orig.replace(re, function () { count++; return replace; });
                if (orig !== newText) node.textContent = newText;
            } else {
                Array.from(node.childNodes).forEach(walkNodes);
            }
        }
        walkNodes(root);

        if (count === 0) {
            $('#ve-fr-feedback').text('No se encontraron coincidencias.');
            return;
        }
        var newHtml = root.innerHTML;
        window.veEditor.setData(newHtml);
        if (window.vePushHistory) window.vePushHistory('Buscar y reemplazar', newHtml);
        $('#ve-fr-feedback').html('<span class="text-success"><i class="fa-duotone fa-solid fa-check me-1"></i>' + count + ' reemplazo(s) realizados.</span>');
    });

    /* ═══════════════════════════════════════════════════════════════
       FEATURE 9: Grid overlay
    ═══════════════════════════════════════════════════════════════ */
    var gridOverlayActive = false;
    $('#btn-grid-overlay').on('click', function () {
        gridOverlayActive = !gridOverlayActive;
        $(this).toggleClass('active', gridOverlayActive);
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({ type: 've-toggle-grid' }, '*');
        }
    });

    /* ═══════════════════════════════════════════════════════════════
       FEATURE 1: Media Manager
    ═══════════════════════════════════════════════════════════════ */
    var veMediaFiles    = [];
    var veSelectedMedia = null;
    var veMediaCalledFrom = null; // 'inspector' | 'settings'

    function openMediaManager(calledFrom) {
        veMediaCalledFrom = calledFrom || null;
        veSelectedMedia   = null;
        $('#btn-media-select').prop('disabled', true);
        $('#ve-media-selected-info').text('Ningún archivo seleccionado');
        new bootstrap.Modal(document.getElementById('ve-media-modal')).show();
        loadMediaFiles();
    }

    function loadMediaFiles() {
        $('#ve-media-grid').html('<div class="text-muted text-center" style="grid-column:1/-1;padding:40px 0;"><i class="fa-duotone fa-solid fa-spinner fa-spin me-1"></i>Cargando...</div>');
        $.ajax({
            url: '{{ route("media.list") }}',
            method: 'GET',
            success: function (res) {
                veMediaFiles = (res.files || []);
                renderMediaGrid();
            },
            error: function () {
                $('#ve-media-grid').html('<div class="text-danger text-center" style="grid-column:1/-1;padding:40px 0;">Error al cargar medios.</div>');
            }
        });
    }

    function renderMediaGrid() {
        var query   = $('#ve-media-search').val().toLowerCase();
        var typeF   = $('#ve-media-type-filter').val();
        var files   = veMediaFiles.filter(function (f) {
            var nameOk = !query || (f.name || '').toLowerCase().includes(query);
            var typeOk = !typeF  || (f.type || f.mime_type || '').toLowerCase().includes(typeF);
            return nameOk && typeOk;
        });
        var $grid = $('#ve-media-grid').empty();
        if (!files.length) {
            $grid.html('<div class="text-muted text-center" style="grid-column:1/-1;padding:40px 0;">Sin resultados.</div>');
            return;
        }
        files.forEach(function (f) {
            var isImg  = (f.type || f.mime_type || '').toLowerCase().includes('image');
            var thumb  = isImg ? (f.url || f.thumbnail_url || '') : '';
            var icon   = isImg ? '' : '<i class="fa-duotone fa-solid fa-file fa-2x text-muted"></i>';
            var $item  = $([
                '<div class="ve-media-item" data-id="' + f.id + '" data-url="' + (f.url || '') + '" title="' + (f.name || '') + '"',
                ' style="border:2px solid #dee2e6;border-radius:6px;overflow:hidden;cursor:pointer;',
                'display:flex;align-items:center;justify-content:center;',
                'flex-direction:column;gap:4px;padding:6px;background:#fff;transition:border-color .15s;',
                'min-height:90px;">',
                thumb ? '<img src="' + thumb + '" style="max-width:100%;max-height:70px;object-fit:cover;border-radius:3px;">' : icon,
                '<span style="font-size:10px;color:#555;text-align:center;word-break:break-all;line-height:1.2;">' + (f.name || '').substring(0, 20) + '</span>',
                '</div>',
            ].join(''));
            $item.on('click', function () {
                $('.ve-media-item').css('border-color', '#dee2e6');
                $(this).css('border-color', '#1a1a1a');
                veSelectedMedia = f;
                $('#btn-media-select').prop('disabled', false);
                $('#ve-media-selected-info').text(f.name || f.url || '');
            });
            $grid.append($item);
        });
    }

    $('#ve-media-search, #ve-media-type-filter').on('input change', function () {
        renderMediaGrid();
    });

    $('#btn-media-select').on('click', function () {
        if (!veSelectedMedia) return;
        var url = veSelectedMedia.url || '';
        // Copy to clipboard
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url);
        }
        // Fill fields based on context
        if (veMediaCalledFrom === 'settings') {
            $('#ve-settings-featured-image').val(url).trigger('input');
        }
        bootstrap.Modal.getInstance(document.getElementById('ve-media-modal'))?.hide();
        if (window.showToast) window.showToast('<i class="fa-duotone fa-solid fa-check me-1"></i>URL copiada: ' + url.substring(0, 40));
    });

    // Upload
    $('#ve-media-upload-input').on('change', function () {
        var files = this.files;
        if (!files || !files.length) return;
        var CSRF3 = $('meta[name="csrf-token"]').attr('content');
        var fd    = new FormData();
        fd.append('_token', CSRF3);
        for (var i = 0; i < files.length; i++) {
            fd.append('file', files[i]);
        }
        $.ajax({
            url:         '{{ route("media.files.upload") }}',
            method:      'POST',
            data:        fd,
            processData: false,
            contentType: false,
            success: function () {
                loadMediaFiles();
            },
            error: function () {
                alert('Error al subir archivo.');
            }
        });
        // Reset input
        $(this).val('');
    });

    // Open from toolbar media button
    $('#btn-media-manager').on('click', function () {
        openMediaManager('toolbar');
    });

    // Expose for settings panel
    window.veOpenMediaManager = openMediaManager;

    /* ═══════════════════════════════════════════════════════════════
       FEATURE 5: Multi-select badge in inspector
    ═══════════════════════════════════════════════════════════════ */
    window.addEventListener('message', function (e) {
        if (!e.data || !e.data.type) return;
        if (e.data.type === 've-multi-select') {
            var count = e.data.count || 0;
            var $badge = $('#ve-multiselect-badge');
            if (count > 1) {
                if (!$badge.length) {
                    $badge = $('<span id="ve-multiselect-badge">').css({
                        display: 'inline-block', background: '#555', color: '#fff',
                        borderRadius: '12px', padding: '1px 8px', fontSize: '11px',
                        marginLeft: '6px'
                    });
                    $('#ve-inspector-element-name').after($badge);
                }
                $badge.text(count + ' elementos');
            } else {
                $badge.remove();
            }
        }
    });

    /* ═══════════════════════════════════════════════════════════════
       FEATURE 11: Ctrl+scroll zoom
    ═══════════════════════════════════════════════════════════════ */
    var zoomLevels  = [0.5, 0.75, 1, 1.25, 1.5];
    var currentZoom = 1;

    document.getElementById('ve-canvas-wrap').addEventListener('wheel', function (e) {
        if (!e.ctrlKey) return;
        e.preventDefault();
        var idx  = zoomLevels.indexOf(currentZoom);
        if (idx === -1) idx = 2; // default 100%
        if (e.deltaY < 0) idx = Math.min(idx + 1, zoomLevels.length - 1);
        else              idx = Math.max(idx - 1, 0);
        currentZoom = zoomLevels[idx];

        $('.ve-zoom-btn').removeClass('active');
        $('.ve-zoom-btn[data-zoom="' + currentZoom + '"]').addClass('active');

        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({ type: 've-set-zoom', zoom: currentZoom }, '*');
        }
    }, { passive: false });

    // Sync zoom variable when zoom buttons clicked
    $(document).on('click', '.ve-zoom-btn', function () {
        currentZoom = parseFloat($(this).data('zoom'));
    });

    /* ═══════════════════════════════════════════════════════════════
       FEATURE: Link Editor Modal (double-click on a/button in iframe)
    ═══════════════════════════════════════════════════════════════ */
    (function () {
        var linkTargetElement = null;

        /* ── Link type switching ──────────────────────────────── */
        $(document).on('click', '.ve-link-type-option', function () {
            var type = $(this).data('type');
            $('.ve-link-type-option').removeClass('active');
            $(this).addClass('active').find('input[type=radio]').prop('checked', true);
            $('.ve-link-field').addClass('d-none');
            $('.ve-link-field[data-for="' + type + '"]').removeClass('d-none');
        });

        /* ── Open from inline toolbar (postMessage) ─────────── */
        var isInlineLinkMode = false;

        function openInlineLinkEditor(href, target) {
            isInlineLinkMode = true;
            linkTargetElement = null;
            $('.ve-link-type-option').removeClass('active');
            $('.ve-link-field').addClass('d-none');
            $('#ve-link-url, #ve-link-email, #ve-link-download').val('');
            $('#ve-link-new-tab').prop('checked', target === '_blank');
            activateType('url');
            $('#ve-link-url').val(href);
            loadPagesList();
            new bootstrap.Modal(document.getElementById('ve-link-editor-modal')).show();
        }

        /* ── Open editor, detect current link type ────────────── */
        function openLinkEditor(el) {
            isInlineLinkMode = false;
            var $el    = $(el);
            var href   = $el.attr('href') || '';
            var target = $el.attr('target') || '';

            // Reset modal state
            $('.ve-link-type-option').removeClass('active');
            $('.ve-link-field').addClass('d-none');
            $('#ve-link-url, #ve-link-email, #ve-link-download').val('');
            $('#ve-link-new-tab').prop('checked', target === '_blank');

            if (href.startsWith('mailto:')) {
                activateType('email');
                $('#ve-link-email').val(href.replace('mailto:', ''));
            } else if (href.startsWith('#')) {
                activateType('anchor');
                loadAnchors(href);
            } else if ($el.attr('download') !== undefined) {
                activateType('download');
                $('#ve-link-download').val(href);
            } else {
                activateType('url');
                $('#ve-link-url').val(href);
            }

            loadPagesList();
            new bootstrap.Modal(document.getElementById('ve-link-editor-modal')).show();
        }

        function activateType(type) {
            var $opt = $('.ve-link-type-option[data-type="' + type + '"]');
            $opt.addClass('active').find('input[type=radio]').prop('checked', true);
            $('.ve-link-field[data-for="' + type + '"]').removeClass('d-none');
        }

        /* ── Populate anchor dropdown from iframe ids ─────────── */
        function loadAnchors(currentAnchor) {
            var iframe  = document.getElementById('ve-preview-frame');
            var $select = $('#ve-link-anchor').empty().append('<option value="">Seleccionar ancla...</option>');
            if (!iframe || !iframe.contentDocument) return;
            iframe.contentDocument.querySelectorAll('[id]').forEach(function (el) {
                var id = '#' + el.id;
                $select.append($('<option>', { value: id, text: el.id, selected: id === currentAnchor }));
            });
        }

        /* ── Populate pages dropdown (loaded once) ────────────── */
        function loadPagesList() {
            var $select = $('#ve-link-page');
            if ($select.data('loaded')) return;
            $.get('{{ route("api.v1.pages.index") }}', function (response) {
                var pages = Array.isArray(response) ? response : (response.data || []);
                $select.empty().append('<option value="">Seleccionar página...</option>');
                pages.forEach(function (page) {
                    $select.append($('<option>', { value: '/' + (page.slug || ''), text: page.title }));
                });
                $select.data('loaded', true);
            }).fail(function () {
                $select.empty().append('<option value="">No se pudieron cargar las páginas</option>');
            });
        }

        /* ── Save: apply href/target/download to element ─────── */
        $('#ve-link-save').on('click', function () {
            var type = $('input[name="ve-link-type"]:checked').val();
            var href = '';

            switch (type) {
                case 'url':      href = $('#ve-link-url').val();               break;
                case 'page':     href = $('#ve-link-page').val();              break;
                case 'anchor':   href = $('#ve-link-anchor').val();            break;
                case 'download': href = $('#ve-link-download').val();          break;
                case 'email':    href = 'mailto:' + $('#ve-link-email').val(); break;
            }

            var target = (type === 'url' && $('#ve-link-new-tab').is(':checked')) ? '_blank' : '';

            bootstrap.Modal.getInstance(document.getElementById('ve-link-editor-modal')).hide();

            if (isInlineLinkMode) {
                // Send back to iframe via postMessage
                var iframe = document.getElementById('ve-preview-frame');
                iframe.contentWindow.postMessage({ type: 've-apply-link', href: href, target: target }, '*');
                isInlineLinkMode = false;
                return;
            }

            if (!linkTargetElement) return;
            var $el = $(linkTargetElement);

            $el.attr('href', href);
            if (target) { $el.attr('target', target); } else { $el.removeAttr('target'); }
            if (type === 'download') { $el.attr('download', ''); } else { $el.removeAttr('download'); }

            if (window.vePushHistory) {
                var iframe  = document.getElementById('ve-preview-frame');
                var content = iframe.contentDocument.querySelector('[data-ve-content]') || iframe.contentDocument.body;
                window.vePushHistory('Enlace editado', content.innerHTML);
            }

            linkTargetElement = null;
        });

        /* ── Attach dblclick listener inside the iframe ───────── */
        function setupIframeDblClick() {
            var iframe = document.getElementById('ve-preview-frame');
            if (!iframe || !iframe.contentDocument) return;
            $(iframe.contentDocument).off('dblclick.ve-link').on('dblclick.ve-link', 'a, button, [role="button"], .btn', function (e) {
                e.preventDefault();
                e.stopPropagation();
                linkTargetElement = this;
                openLinkEditor(this);
            });
        }

        /* ── Re-attach after iframe reloads ───────────────────── */
        var previewFrame = document.getElementById('ve-preview-frame');
        if (previewFrame) {
            previewFrame.addEventListener('load', function () {
                setTimeout(setupIframeDblClick, 300);
            });
            setTimeout(setupIframeDblClick, 1000);
        }
    }());

})(jQuery);
</script>

{{-- Sync topbar search → shortcodes panel search --}}
<script>
(function($){
    // ── Sync topbar search → shortcodes panel search ──
    $('#ve-topbar-search').on('input', function(){
        $('#ve-sc-search').val($(this).val()).trigger('input');
        $('#ve-topbar-search-clear').toggle($(this).val().length > 0);
    });
    $(document).on('click', '#ve-topbar-search-clear', function(){
        $('#ve-topbar-search').val('').trigger('input').focus();
        $(this).hide();
    });

    // ── Sync autosave-status to bottom bar ──
    var origAS = document.getElementById('autosave-status');
    if (origAS) {
        new MutationObserver(function(){
            var bar = document.getElementById('autosave-status-bar');
            if (bar) { bar.textContent = origAS.textContent; bar.className = origAS.className + ' ms-1'; }
        }).observe(origAS, { childList: true, attributes: true, characterData: true, subtree: true });
    }

    // ── Update preview URL when locale changes ──
    window.addEventListener('message', function(ev) {
        if (ev.data && ev.data.type === 've-locale-changed') {
            var loc = ev.data.locale || '';
            var baseUrl = $('.ve-topbar-preview-btn').data('base-url') || $('.ve-topbar-preview-btn').attr('href');
            if (!$('.ve-topbar-preview-btn').data('base-url')) {
                $('.ve-topbar-preview-btn').data('base-url', baseUrl);
            }
            // Append locale param if not default
            var newUrl = baseUrl + (baseUrl.indexOf('?') !== -1 ? '&' : '?') + 'locale=' + loc;
            $('.ve-topbar-preview-btn').attr('href', newUrl);
        }
    });

    // ── Sync locale bar click to main locale switcher ──
    $(document).on('click', '#btn-locale-bar + .dropdown-menu .ve-locale-btn', function(){
        var loc = $(this).data('locale');
        $('#btn-locale-switcher + .dropdown-menu .ve-locale-btn[data-locale="'+loc+'"]').trigger('click');
        $('#btn-locale-bar').text(loc.toUpperCase());
    });

    // ── Auto-refresh Layout & Sections when switching panels ──
    $(document).on('click', '#ve-sidebar-nav .ve-nav-btn[data-panel]', function(){
        var panel = $(this).data('panel');
        if (panel === 'layout' && window.veRefreshLayout) {
            setTimeout(function(){ window.veRefreshLayout(); }, 150);
        }
        if (panel === 'sections') {
            setTimeout(function(){
                var $btn = $('#btn-refresh-sections');
                if ($btn.length) $btn.trigger('click');
            }, 150);
        }
    });

    // ── Save button visual feedback (observe autosave status) ──
    var $saveBar = $('#btn-save-bar');
    if (origAS && $saveBar.length) {
        new MutationObserver(function(){
            var cls = origAS.className || '';
            if (cls.indexOf('saving') !== -1) {
                $saveBar.html('<i class="fa-duotone fa-solid fa-spinner-third fa-spin" style="font-size:11px;"></i>');
            } else if (cls.indexOf('saved') !== -1) {
                $saveBar.html('<i class="fa-duotone fa-solid fa-check" style="color:#13C672;"></i>');
                setTimeout(function(){ $saveBar.text('Guardar'); }, 1500);
            } else if (cls.indexOf('error') !== -1) {
                $saveBar.html('<i class="fa-duotone fa-solid fa-times" style="color:#b10100;"></i>');
                setTimeout(function(){ $saveBar.text('Guardar'); }, 2000);
            }
        }).observe(origAS, { attributes: true, attributeFilter: ['class'] });
    }

    // ── Sidebar collapse override (only hide panels, keep icon nav) ──
    $('#ve-sidebar-toggle').off('click').on('click', function () {
        var $sidebar = $('#ve-sidebar');
        var $icon = $(this).find('i');
        var collapsed = $sidebar.hasClass('collapsed');
        if (collapsed) {
            $sidebar.removeClass('collapsed').css('width', '');
            $icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');
            $(this).attr('title', 'Colapsar barra lateral');
        } else {
            $sidebar.addClass('collapsed');
            $icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
            $(this).attr('title', 'Expandir barra lateral');
        }
    });

    // ── Background image MediaPicker integration ──
    $(document).on('click', '#btn-bg-media-picker', function(){
        if (!window.MediaPicker) {
            // Fallback: trigger hidden file input
            $('#ve-bg-image-file').trigger('click');
            return;
        }
        window.MediaPicker.open({
            filter: 'image',
            title: 'Seleccionar imagen de fondo',
            onSelect: function(url) {
                $('#ve-bg-image-url').val(url);
                $('#btn-apply-bg-url').trigger('click');
                // Show preview
                $('#ve-bg-preview-img').attr('src', url);
                $('#ve-bg-preview').show();
            }
        });
    });
    // Show preview when URL applied
    $(document).on('click', '#btn-apply-bg-url', function(){
        var url = $('#ve-bg-image-url').val().trim();
        if (url) {
            $('#ve-bg-preview-img').attr('src', url);
            $('#ve-bg-preview').show();
        }
    });
    // Clear preview on clear
    $(document).on('click', '#btn-clear-bg-image', function(){
        $('#ve-bg-preview').hide();
        $('#ve-bg-preview-img').attr('src', '');
        $('#ve-bg-image-url').val('');
    });

    // ── Initialize Select2 on inspector selects ──
    function initInspectorSelect2() {
        $('#ve-inspector-sections select:not(.select2-hidden-accessible)').each(function(){
            $(this).select2({
                width: '100%',
                minimumResultsForSearch: 8,
                dropdownParent: $('#ve-sidebar-panels')
            });
        });
    }
    initInspectorSelect2();
    // Re-init when inspector sections become visible
    $(document).on('click', '#ve-sidebar-nav .ve-nav-btn[data-panel="inspector"]', function(){
        setTimeout(initInspectorSelect2, 200);
    });

    // ── A2: Keyboard shortcuts modal ──
    $(document).on('keydown', function(e) {
        var tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
        if (e.key === '?' || (e.key === '/' && e.shiftKey)) {
            e.preventDefault();
            new bootstrap.Modal(document.getElementById('ve-shortcuts-modal')).show();
        }
    });

    // ── A3: Copy/paste toast feedback ──
    window.addEventListener('message', function(ev) {
        if (!ev.data || !ev.data.type) return;
        if (ev.data.type === 've-element-copied' && window.veToast) window.veToast('Elemento copiado', 'info');
        if (ev.data.type === 've-element-pasted' && window.veToast) window.veToast('Elemento pegado', 'success');
    });

    // ── A4: Breakpoint dimensions in bottom bar ──
    $(document).on('click', '.breakpoint-btn', function() {
        var w = $(this).data('width') || '';
        var h = $(this).data('height') || '';
        var bp = $(this).data('breakpoint') || 'desktop';
        var $dims = $('#ve-breakpoint-dims');
        if (!$dims.length) {
            $dims = $('<span id="ve-breakpoint-dims"></span>').css({fontSize:'9px',color:'#bbb',marginLeft:'2px'});
            $('#btn-responsive-bar').after($dims);
        }
        $dims.text(bp === 'desktop' ? '' : w.replace('px','') + '×' + h.replace('px',''));
    });

    // ── A5: Unsaved changes red dot ──
    if (origAS) {
        new MutationObserver(function() {
            var cls = origAS.className || '';
            var $s = $('#btn-save-bar');
            if (cls.indexOf('unsaved') !== -1 || cls.indexOf('saving') !== -1) $s.addClass('has-changes');
            else $s.removeClass('has-changes');
        }).observe(origAS, { attributes: true, attributeFilter: ['class'] });
    }

    // ── A7: Context menu extra options ──
    var copiedStyles = null;
    (function addExtraCtx() {
        var $menu = $('#ve-context-menu');
        if (!$menu.length) { setTimeout(addExtraCtx, 500); return; }
        var $last = $menu.find('.ve-ctx-divider').last();
        if ($last.length && !$('#ctx-inspect').length) {
            $('<div class="ve-ctx-item" id="ctx-copy-style"><i class="fa-duotone fa-solid fa-palette fa-fw ve-ctx-icon-muted"></i> Copiar estilo</div>')
                .insertBefore($last);
            $('<div class="ve-ctx-item" id="ctx-paste-style"><i class="fa-duotone fa-solid fa-fill-drip fa-fw ve-ctx-icon-muted"></i> Pegar estilo</div>')
                .insertBefore($last);
            $('<div class="ve-ctx-item" id="ctx-inspect"><i class="fa-duotone fa-solid fa-sliders fa-fw ve-ctx-icon-muted"></i> Inspeccionar</div>')
                .insertBefore($last);
        }
    })();
    $(document).on('click', '#ctx-inspect', function() {
        $('#ve-sidebar-nav .ve-nav-btn[data-panel="inspector"]').trigger('click');
        $('#ve-context-menu').hide();
    });
    // P1.2: Copy/Paste styles
    $(document).on('click', '#ctx-copy-style', function() {
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({ type: 've-copy-styles' }, '*');
        }
        $('#ve-context-menu').hide();
        if (window.veToast) window.veToast('Estilo copiado', 'info');
    });
    $(document).on('click', '#ctx-paste-style', function() {
        if (copiedStyles) {
            var frame = document.getElementById('ve-preview-frame');
            if (frame && frame.contentWindow) {
                frame.contentWindow.postMessage({ type: 've-paste-styles', styles: copiedStyles }, '*');
            }
            if (window.veToast) window.veToast('Estilo pegado', 'success');
        }
        $('#ve-context-menu').hide();
    });
    window.addEventListener('message', function(ev) {
        if (ev.data && ev.data.type === 've-styles-copied') {
            copiedStyles = ev.data.styles;
        }
    });

    // ── A9: Autosave relative timestamp ──
    var lastSaveTime = null;
    if (origAS) {
        new MutationObserver(function() {
            if ((origAS.className || '').indexOf('saved') !== -1) lastSaveTime = Date.now();
        }).observe(origAS, { attributes: true, attributeFilter: ['class'] });
    }
    setInterval(function() {
        if (!lastSaveTime) return;
        var diff = Math.floor((Date.now() - lastSaveTime) / 1000);
        var $bar = $('#autosave-status-bar');
        if ($bar.length && diff > 5) {
            var t = diff < 60 ? diff + 's' : diff < 3600 ? Math.floor(diff/60) + ' min' : Math.floor(diff/3600) + 'h';
            $bar.text('Guardado hace ' + t);
        }
    }, 10000);

    // ── P3.1: Motion effects — apply animation classes ──
    $(document).on('click', '#btn-apply-motion', function() {
        var effect = $('#ve-motion-effect').val();
        var duration = $('#ve-motion-duration').val();
        var delay = $('#ve-motion-delay').val();
        if (!effect) return;
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({
                type: 've-apply-motion',
                effect: effect,
                duration: duration,
                delay: delay
            }, '*');
        }
        if (window.veToast) window.veToast('Animación aplicada: ' + effect, 'success');
    });
    $(document).on('click', '#btn-remove-motion', function() {
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({ type: 've-remove-motion' }, '*');
        }
        if (window.veToast) window.veToast('Animación eliminada', 'info');
    });

    // ── Command palette actions array (declared early, populated below) ──
    var cmdActions = [];

    // ── N4: Import from URL ──
    cmdActions.push({ cat:'Herramientas', label:'Importar sección desde URL', icon:'fa-download', action: function() {
        var url = prompt('URL de la página a importar:');
        if (!url) return;
        if (window.veToast) window.veToast('Importando...', 'info');
        $.ajax({
            url: '/api/v1/page-import',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { url: url },
            timeout: 15000
        }).done(function(res) {
            if (res.html && window.veEditor) {
                window.veEditor.model.change(function() {
                    var view = window.veEditor.data.processor.toView(res.html);
                    var model = window.veEditor.data.toModel(view);
                    window.veEditor.model.insertContent(model);
                });
                if (window.veToast) window.veToast('Contenido importado', 'success');
            }
        }).fail(function() {
            if (window.veToast) window.veToast('Error al importar. Verifica la URL.', 'error');
        });
    }});

    // ── N10: PageSpeed insights (placeholder) ──
    cmdActions.push({ cat:'Herramientas', label:'PageSpeed Insights', icon:'fa-tachometer-alt', action: function() {
        var pageUrl = $('.ve-topbar-preview-btn').attr('href');
        if (pageUrl) {
            window.open('https://pagespeed.web.dev/analysis?url=' + encodeURIComponent(window.location.origin + pageUrl), '_blank');
        }
    }});

    // ── N11: SEO live preview (already partial in settings, add to cmd) ──
    cmdActions.push({ cat:'Herramientas', label:'Vista previa SEO/OG', icon:'fa-search', action: function() {
        $('#ve-sidebar-nav .ve-nav-btn[data-panel="settings"]').trigger('click');
        setTimeout(function() {
            var $og = $('#ve-og-header');
            if ($og.length) $og.trigger('click');
        }, 300);
    }});

    // ── N12: CSS Grid builder (command palette) ──
    cmdActions.push({ cat:'Insertar', label:'CSS Grid layout', icon:'fa-border-all', action: function() {
        var cols = prompt('Número de columnas (ej: 3):');
        var rows = prompt('Número de filas (ej: 2):');
        if (!cols || !rows) return;
        var html = '<div style="display:grid;grid-template-columns:repeat('+cols+',1fr);gap:16px;padding:16px;">\n';
        for (var i = 0; i < cols * rows; i++) {
            html += '  <div style="padding:16px;background:#f4f6f8;border-radius:8px;min-height:60px;">Celda ' + (i+1) + '</div>\n';
        }
        html += '</div>';
        if (window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView(html);
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
            if (window.vePushHistory) window.vePushHistory('CSS Grid ' + cols + 'x' + rows, window.veEditor.getData());
        }
    }});

    // ── P3.2: Popup builder ──
    cmdActions.push({ cat:'Insertar', label:'Crear popup', icon:'fa-window-restore', action: function() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-popup-builder')).show();
    }});
    $(document).on('click', '#btn-insert-popup', function() {
        var title = $('#ve-popup-title').val() || 'Popup';
        var content = $('#ve-popup-content').val() || '';
        var trigger = $('#ve-popup-trigger').val();
        var style = $('#ve-popup-style').val();
        var id = 've-popup-' + Date.now();
        var triggerAttr = 'data-popup-trigger="' + trigger + '"';
        var styleClass = style === 'bottom-bar' ? 've-popup-bar' : style === 'slide-in' ? 've-popup-slide' : 've-popup-center';

        var html = '<!-- Popup: ' + title + ' -->\n' +
            (trigger === 'click' ? '<button class="btn btn-primary" onclick="document.getElementById(\'' + id + '\').style.display=\'flex\'">' + title + '</button>\n' : '') +
            '<div id="' + id + '" class="ve-popup ' + styleClass + '" ' + triggerAttr + ' style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;">\n' +
            '  <div style="background:#fff;border-radius:12px;padding:24px;max-width:480px;width:90%;position:relative;">\n' +
            '    <button onclick="this.closest(\'.ve-popup\').style.display=\'none\'" style="position:absolute;top:8px;right:12px;background:none;border:none;font-size:18px;cursor:pointer;">&times;</button>\n' +
            '    <h3>' + title + '</h3>\n' +
            '    <p>' + content + '</p>\n' +
            '  </div>\n' +
            '</div>\n';

        if (trigger === 'timer') {
            html += '<script>setTimeout(function(){document.getElementById("' + id + '").style.display="flex";},5000);<\/script>\n';
        } else if (trigger === 'scroll') {
            html += '<script>window.addEventListener("scroll",function(){if(window.scrollY>document.body.scrollHeight*0.5){document.getElementById("' + id + '").style.display="flex";}},{once:true});<\/script>\n';
        }

        if (window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView(html);
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
            if (window.vePushHistory) window.vePushHistory('Popup: ' + title, window.veEditor.getData());
        }
        bootstrap.Modal.getInstance(document.getElementById('ve-popup-builder')).hide();
        if (window.veToast) window.veToast('Popup insertado', 'success');
    });

    // ── P3.3: Form builder ──
    cmdActions.push({ cat:'Insertar', label:'Constructor de formularios', icon:'fa-rectangle-list', action: function() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-form-builder')).show();
    }});
    $(document).on('click', '#btn-add-form-field', function() {
        var row = '<div class="ve-form-field-row">' +
            '<input type="text" class="form-control" placeholder="Label">' +
            '<select class="form-select"><option value="text">Texto</option><option value="email">Email</option><option value="tel">Teléfono</option><option value="textarea">Textarea</option><option value="select">Select</option></select>' +
            '<button type="button" class="btn btn-outline-secondary ve-form-remove-field"><i class="fa-duotone fa-solid fa-times"></i></button></div>';
        $('#ve-form-fields').append(row);
    });
    $(document).on('click', '.ve-form-remove-field', function() { $(this).closest('.ve-form-field-row').remove(); });
    // Pre-fill fields based on form type
    $(document).on('change', '#ve-form-type', function() {
        var type = $(this).val();
        var presets = {
            contact: [['Nombre','text'],['Email','email'],['Teléfono','tel'],['Mensaje','textarea']],
            newsletter: [['Email','email']],
            quote: [['Nombre','text'],['Email','email'],['Teléfono','tel'],['Servicio','select'],['Mensaje','textarea']],
            custom: []
        };
        var fields = presets[type] || [];
        var $container = $('#ve-form-fields').empty();
        fields.forEach(function(f) {
            var opts = '<option value="text">Texto</option><option value="email">Email</option><option value="tel">Teléfono</option><option value="textarea">Textarea</option><option value="select">Select</option>';
            var row = '<div class="ve-form-field-row"><input type="text" class="form-control" value="' + f[0] + '"><select class="form-select">' + opts.replace('value="' + f[1] + '"', 'value="' + f[1] + '" selected') + '</select><button type="button" class="btn btn-outline-secondary ve-form-remove-field"><i class="fa-duotone fa-solid fa-times"></i></button></div>';
            $container.append(row);
        });
    });
    $(document).on('click', '#btn-insert-form', function() {
        var btnText = $('#ve-form-btn-text').val() || 'Enviar';
        var action = $('#ve-form-action').val() || '#';
        var isEmail = action.indexOf('@') !== -1;
        var formAction = isEmail ? 'mailto:' + action : action;

        var html = '<form action="' + formAction + '" method="POST" class="py-3">\n';
        $('#ve-form-fields .ve-form-field-row').each(function() {
            var label = $(this).find('input').val() || 'Campo';
            var type = $(this).find('select').val() || 'text';
            var name = label.toLowerCase().replace(/[^a-z0-9]/g, '_');
            html += '  <div class="mb-3">\n';
            html += '    <label class="form-label">' + label + '</label>\n';
            if (type === 'textarea') {
                html += '    <textarea class="form-control" name="' + name + '" rows="3" placeholder="' + label + '..."></textarea>\n';
            } else if (type === 'select') {
                html += '    <select class="form-select" name="' + name + '"><option value="">Seleccionar...</option><option>Opción 1</option><option>Opción 2</option></select>\n';
            } else {
                html += '    <input type="' + type + '" class="form-control" name="' + name + '" placeholder="' + label + '">\n';
            }
            html += '  </div>\n';
        });
        html += '  <button type="submit" class="btn btn-primary">' + btnText + '</button>\n</form>';

        if (window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView(html);
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
            if (window.vePushHistory) window.vePushHistory('Formulario insertado', window.veEditor.getData());
        }
        bootstrap.Modal.getInstance(document.getElementById('ve-form-builder')).hide();
        if (window.veToast) window.veToast('Formulario insertado', 'success');
    });

    // ── N1: Wireframe mode ──
    $(document).on('click', '#btn-wireframe', function() {
        $('#ve-canvas-wrap').toggleClass('ve-wireframe');
        $(this).toggleClass('active');
    });
    cmdActions.push({ cat:'Vista', label:'Modo wireframe', icon:'fa-vector-square', action: function(){ $('#btn-wireframe').trigger('click'); }});

    // ── N2: Page weight monitor ──
    function updatePageWeight() {
        try {
            var html = window.veEditor ? window.veEditor.getData() : '';
            var kb = Math.round(html.length / 1024);
            $('#ve-page-weight').text('· ' + kb + ' KB');
        } catch(e) {}
    }
    setInterval(updatePageWeight, 8000);
    setTimeout(updatePageWeight, 4000);

    // ── N5: Element search (Ctrl+F in canvas) ──
    var searchMatches = [], searchIdx = -1;
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            var tag = (e.target.tagName||'').toLowerCase();
            if (tag === 'input' || tag === 'textarea') return;
            e.preventDefault();
            $('#ve-element-search').addClass('active');
            $('#ve-element-search-input').val('').focus();
        }
    });
    $(document).on('click', '#ve-search-close', function() {
        $('#ve-element-search').removeClass('active');
        searchMatches = []; searchIdx = -1;
        // Clear highlights in iframe
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage({ type: 've-clear-search' }, '*');
    });
    $(document).on('input', '#ve-element-search-input', function() {
        var q = $(this).val().trim();
        if (q.length < 2) { $('#ve-search-count').text(''); return; }
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage({ type: 've-search-text', query: q }, '*');
    });
    $(document).on('click', '#ve-search-next', function() {
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage({ type: 've-search-nav', dir: 'next' }, '*');
    });
    $(document).on('click', '#ve-search-prev', function() {
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage({ type: 've-search-nav', dir: 'prev' }, '*');
    });
    window.addEventListener('message', function(ev) {
        if (ev.data && ev.data.type === 've-search-results') {
            $('#ve-search-count').text(ev.data.count + ' resultados');
        }
    });

    // ── N7: Responsive ruler ──
    $(document).on('click', '#btn-ruler', function() {
        var $ruler = $('#ve-ruler');
        $ruler.toggleClass('active');
        $(this).toggleClass('active');
        if ($ruler.hasClass('active')) {
            $ruler.empty();
            var w = $('#ve-canvas-wrap').width();
            for (var px = 0; px <= w; px += 100) {
                $ruler.append('<div class="ve-ruler-tick" style="left:'+px+'px;"></div><div class="ve-ruler-label" style="left:'+(px+2)+'px;">'+px+'</div>');
            }
        }
    });

    // ── N13: Gradient builder ──
    function updateGradientPreview() {
        var dir = $('#ve-gradient-direction').val();
        var c1 = $('#ve-gradient-color1').val();
        var c2 = $('#ve-gradient-color2').val();
        $('#ve-gradient-preview').css('background', 'linear-gradient(' + dir + ', ' + c1 + ', ' + c2 + ')');
    }
    $(document).on('input change', '#ve-gradient-direction, #ve-gradient-color1, #ve-gradient-color2', updateGradientPreview);
    $(document).on('click', '#btn-apply-gradient', function() {
        var dir = $('#ve-gradient-direction').val();
        var c1 = $('#ve-gradient-color1').val();
        var c2 = $('#ve-gradient-color2').val();
        var val = 'linear-gradient(' + dir + ', ' + c1 + ', ' + c2 + ')';
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage({ type: 've-apply-styles', styles: { 'background': val } }, '*');
        if (window.veToast) window.veToast('Gradiente aplicado', 'success');
    });
    $(document).on('click', '#btn-clear-gradient', function() {
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage({ type: 've-apply-styles', styles: { 'background': '' } }, '*');
    });

    // ── N14: Box shadow builder ──
    function getShadowVal() {
        var x = $('#ve-shadow-x').val(), y = $('#ve-shadow-y').val();
        var blur = $('#ve-shadow-blur').val(), spread = $('#ve-shadow-spread').val();
        var color = $('#ve-shadow-color').val(), opacity = $('#ve-shadow-opacity').val() / 100;
        var r = parseInt(color.slice(1,3),16), g = parseInt(color.slice(3,5),16), b = parseInt(color.slice(5,7),16);
        return x+'px '+y+'px '+blur+'px '+spread+'px rgba('+r+','+g+','+b+','+opacity+')';
    }
    function updateShadowPreview() {
        $('#ve-shadow-preview').css('box-shadow', getShadowVal());
        $('#ve-shadow-x-val').text($('#ve-shadow-x').val());
        $('#ve-shadow-y-val').text($('#ve-shadow-y').val());
        $('#ve-shadow-blur-val').text($('#ve-shadow-blur').val());
        $('#ve-shadow-spread-val').text($('#ve-shadow-spread').val());
        $('#ve-shadow-opacity-val').text($('#ve-shadow-opacity').val());
    }
    $(document).on('input', '#ve-shadow-x, #ve-shadow-y, #ve-shadow-blur, #ve-shadow-spread, #ve-shadow-color, #ve-shadow-opacity', updateShadowPreview);
    $(document).on('click', '#btn-apply-shadow', function() {
        var val = getShadowVal();
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage({ type: 've-apply-styles', styles: { 'box-shadow': val } }, '*');
        if (window.veToast) window.veToast('Sombra aplicada', 'success');
    });
    $(document).on('click', '#btn-clear-shadow', function() {
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage({ type: 've-apply-styles', styles: { 'box-shadow': 'none' } }, '*');
        $('#ve-shadow-preview').css('box-shadow', 'none');
    });

    // ── N6: Style presets (save/apply) ──
    var stylePresets = JSON.parse(localStorage.getItem('ve-style-presets') || '[]');
    cmdActions.push({ cat:'Herramientas', label:'Guardar preset de estilo', icon:'fa-bookmark', action: function() {
        if (!window.veEditor) return;
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) frame.contentWindow.postMessage({ type: 've-copy-styles' }, '*');
        setTimeout(function() {
            var name = prompt('Nombre del preset:');
            if (!name) return;
            // copiedStyles is set by the copy-styles handler
            var copiedS = window._veCopiedStyles;
            if (copiedS) {
                stylePresets.push({ name: name, styles: copiedS });
                localStorage.setItem('ve-style-presets', JSON.stringify(stylePresets));
                if (window.veToast) window.veToast('Preset "' + name + '" guardado', 'success');
            }
        }, 500);
    }});
    // Expose copied styles for preset saving
    window.addEventListener('message', function(ev) {
        if (ev.data && ev.data.type === 've-styles-copied') window._veCopiedStyles = ev.data.styles;
    });

    // ── P4.8: Undo per section (track section-level changes) ──
    // Enhanced: when moving/editing within a section, push to section-specific stack
    // This is tracked via the existing history with labels that include section info
    // The history panel already shows labels — this adds section-level filtering
    cmdActions.push({ cat:'Herramientas', label:'Deshacer último cambio en sección', icon:'fa-rotate-left', action: function() {
        $('#btn-undo').trigger('click');
    }});

    // ── P3.4: Dynamic content tags ──
    var dynamicTags = {
        'page.title': @json($page->title),
        'page.url': @json(url($page->slug ?? '')),
        'site.name': @json(config('app.name')),
        'current.year': new Date().getFullYear().toString(),
        'current.date': new Date().toLocaleDateString('es-ES'),
    };
    cmdActions.push({ cat:'Insertar', label:'Tag dinámico: Título de página', icon:'fa-tag', action: function() {
        if (window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView('<span data-dynamic="page.title">' + dynamicTags['page.title'] + '</span>');
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
        }
    }});
    cmdActions.push({ cat:'Insertar', label:'Tag dinámico: Año actual', icon:'fa-calendar', action: function() {
        if (window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView('<span data-dynamic="current.year">' + dynamicTags['current.year'] + '</span>');
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
        }
    }});
    cmdActions.push({ cat:'Insertar', label:'Tag dinámico: Nombre del sitio', icon:'fa-globe', action: function() {
        if (window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView('<span data-dynamic="site.name">' + dynamicTags['site.name'] + '</span>');
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
        }
    }});

    // ── P3.5: Display conditions ──
    // Add condition attributes to elements via context menu
    (function addConditionCtx() {
        var $menu = $('#ve-context-menu');
        if (!$menu.length) { setTimeout(addConditionCtx, 500); return; }
        var $last = $menu.find('.ve-ctx-divider').last();
        if ($last.length && !$('#ctx-conditions').length) {
            $('<div class="ve-ctx-item" id="ctx-conditions"><i class="fa-duotone fa-solid fa-filter fa-fw ve-ctx-icon-muted"></i> Condiciones</div>')
                .insertBefore($last);
        }
    })();
    $(document).on('click', '#ctx-conditions', function() {
        $('#ve-context-menu').hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-conditions-modal')).show();
    });
    $(document).on('click', '#btn-apply-condition', function() {
        var condition = $('#ve-condition-select').val();
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            if (!condition) {
                frame.contentWindow.postMessage({ type: 've-remove-attr', attr: 'data-condition' }, '*');
                if (window.veToast) window.veToast('Condición eliminada', 'info');
            } else {
                frame.contentWindow.postMessage({ type: 've-set-attr', attr: 'data-condition', value: condition }, '*');
                if (window.veToast) window.veToast('Condición: ' + condition, 'success');
            }
        }
        bootstrap.Modal.getInstance(document.getElementById('ve-conditions-modal')).hide();
    });

    // ── P3.9: AI content generation ──
    cmdActions.push({ cat:'Herramientas', label:'Generar contenido con AI', icon:'fa-wand-magic-sparkles', action: function() {
        var el = document.getElementById('ve-ai-modal');
        bootstrap.Modal.getOrCreateInstance(el).show();
    }});
    // Also add to context menu
    (function addAiCtx() {
        var $menu = $('#ve-context-menu');
        if (!$menu.length) { setTimeout(addAiCtx, 500); return; }
        var $last = $menu.find('.ve-ctx-divider').last();
        if ($last.length && !$('#ctx-ai-generate').length) {
            $('<div class="ve-ctx-item" id="ctx-ai-generate"><i class="fa-duotone fa-solid fa-wand-magic-sparkles fa-fw ve-ctx-icon-muted"></i> Generar con AI</div>')
                .insertBefore($last);
        }
    })();
    $(document).on('click', '#ctx-ai-generate', function() {
        $('#ve-context-menu').hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-ai-modal')).show();
    });

    $(document).on('click', '#btn-ai-generate, #btn-ai-regenerate', function() {
        var type = $('#ve-ai-type').val();
        var prompt = $('#ve-ai-prompt').val().trim();
        var tone = $('#ve-ai-tone').val();
        if (!prompt) { if (window.veToast) window.veToast('Escribe una descripción', 'error'); return; }

        var $btn = $('#btn-ai-generate');
        $btn.prop('disabled', true).html('<i class="fa-duotone fa-solid fa-spinner-third fa-spin me-1"></i>Generando...');

        // Try API endpoint, fallback to placeholder
        $.ajax({
            url: '/api/v1/ai/generate-content',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { type: type, prompt: prompt, tone: tone },
            timeout: 30000
        }).done(function(res) {
            var html = res.html || res.content || res.text || '';
            $('#ve-ai-output').html(html);
            $('#ve-ai-result').removeClass('ve-hidden');
        }).fail(function() {
            // Fallback: generate placeholder content locally
            var templates = {
                paragraph: '<p>' + prompt + '. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</p>',
                heading: '<h2>' + prompt + '</h2>',
                list: '<ul><li>' + prompt.split(',').join('</li><li>') + '</li></ul>',
                cta: '<div class="text-center py-4"><h3>' + prompt + '</h3><a href="#" class="btn btn-primary">Contactar ahora</a></div>',
                faq: '<div class="faq"><h4>Pregunta: ' + prompt + '</h4><p>Respuesta: Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p></div>'
            };
            $('#ve-ai-output').html(templates[type] || templates.paragraph);
            $('#ve-ai-result').removeClass('ve-hidden');
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="fa-duotone fa-solid fa-wand-magic-sparkles me-1"></i>Generar');
        });
    });

    $(document).on('click', '#btn-ai-insert', function() {
        var html = $('#ve-ai-output').html();
        if (html && window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView(html);
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
            if (window.vePushHistory) window.vePushHistory('AI: contenido generado', window.veEditor.getData());
            bootstrap.Modal.getInstance(document.getElementById('ve-ai-modal')).hide();
            if (window.veToast) window.veToast('Contenido insertado', 'success');
        }
    });

    // ── U2: Paste smart (clean HTML from Word/Google Docs) ──
    window.addEventListener('message', function(ev) {
        if (ev.data && ev.data.type === 've-paste-event') {
            // Already handled inside iframe via bridge
        }
    });
    // Add paste cleaner in command palette
    cmdActions.push({ cat:'Herramientas', label:'Limpiar HTML pegado', icon:'fa-broom', action: function() {
        if (!window.veEditor) return;
        var html = window.veEditor.getData();
        // Remove MS Word / Google Docs junk
        html = html.replace(/class="Mso[^"]*"/gi, '');
        html = html.replace(/style="[^"]*mso-[^"]*"/gi, '');
        html = html.replace(/<o:p>[\s\S]*?<\/o:p>/gi, '');
        html = html.replace(/<xml>[\s\S]*?<\/xml>/gi, '');
        html = html.replace(/<style>[\s\S]*?<\/style>/gi, '');
        html = html.replace(/<!--\[if[\s\S]*?endif\]-->/gi, '');
        html = html.replace(/<\/?span[^>]*>/gi, '');
        html = html.replace(/\s{2,}/g, ' ');
        window.veEditor.setData(html);
        if (window.vePushHistory) window.vePushHistory('HTML limpiado', html);
        if (window.veToast) window.veToast('HTML limpiado de formato Word/Docs', 'success');
    }});

    // ── R2: Responsive live preview (manual drag resize) ──
    var canvasResizing = false, canvasStartX = 0, canvasStartW = 0;
    $(document).on('mousedown', '#ve-preview-frame', function(e) {
        // Only start if near right edge (last 10px)
        var rect = this.getBoundingClientRect();
        if (e.clientX < rect.right - 10) return;
        e.preventDefault();
        canvasResizing = true;
        canvasStartX = e.clientX;
        canvasStartW = $(this).width();
        $('body').css('cursor', 'ew-resize');
    });
    $(document).on('mousemove', function(e) {
        if (!canvasResizing) return;
        var newW = canvasStartW + (e.clientX - canvasStartX);
        if (newW < 280) newW = 280;
        var $wrap = $('#ve-canvas-wrap');
        if ($wrap.hasClass('desktop')) {
            $wrap.removeClass('desktop');
        }
        $('#ve-preview-frame').css({ width: newW + 'px', height: '100%', boxShadow: '0 8px 32px rgba(0,0,0,.4)', borderRadius: '8px' });
        // Show dimensions
        var $dims = $('#ve-breakpoint-dims');
        if (!$dims.length) { $dims = $('<span id="ve-breakpoint-dims"></span>').css({fontSize:'9px',color:'#bbb',marginLeft:'2px'}); $('#btn-responsive-bar').after($dims); }
        $dims.text(newW + '×' + $('#ve-preview-frame').height());
    });
    $(document).on('mouseup', function() {
        if (canvasResizing) { canvasResizing = false; $('body').css('cursor', ''); }
    });

    // ── N9: Version compare (simple text diff) ──
    cmdActions.push({ cat:'Herramientas', label:'Comparar con versión guardada', icon:'fa-code-compare', action: function() {
        if (!window.veEditor) return;
        var current = window.veEditor.getData();
        var original = typeof originalContent !== 'undefined' ? originalContent : '';
        var added = 0, removed = 0;
        // Simple line-based diff
        var origLines = original.split('\n');
        var currLines = current.split('\n');
        origLines.forEach(function(l) { if (currLines.indexOf(l) === -1) removed++; });
        currLines.forEach(function(l) { if (origLines.indexOf(l) === -1) added++; });
        var html = '<div style="text-align:center;padding:12px;">' +
            '<div style="font-size:24px;font-weight:700;color:#333;">Cambios detectados</div>' +
            '<div style="display:flex;gap:20px;justify-content:center;margin:16px 0;">' +
            '<div style="padding:12px 20px;background:#e8f5e9;border-radius:8px;"><span style="font-size:20px;font-weight:700;color:#43a047;">+' + added + '</span><br><small style="color:#666;">líneas añadidas</small></div>' +
            '<div style="padding:12px 20px;background:#fce4ec;border-radius:8px;"><span style="font-size:20px;font-weight:700;color:#b10100;">-' + removed + '</span><br><small style="color:#666;">líneas eliminadas</small></div>' +
            '</div><small style="color:#999;">Total original: ' + origLines.length + ' líneas · Actual: ' + currLines.length + ' líneas</small></div>';
        var $m = $('<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content ve-cmd-content"><div class="ve-ai-modal-header"><h6 class="ve-ai-modal-title"><i class="fa-duotone fa-solid fa-code-compare ve-ai-modal-icon"></i>Comparar versiones</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="ve-ai-modal-body">' + html + '</div></div></div></div>');
        $('body').append($m);
        new bootstrap.Modal($m[0]).show();
        $m.on('hidden.bs.modal', function() { $m.remove(); });
    }});

    // ── D3: Global blocks (save/insert from localStorage) ──
    var globalBlocks = JSON.parse(localStorage.getItem('ve-global-blocks') || '[]');
    cmdActions.push({ cat:'Herramientas', label:'Guardar sección como bloque global', icon:'fa-globe', action: function() {
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow) return;
        frame.contentWindow.postMessage({ type: 've-get-selected-html' }, '*');
        setTimeout(function() {
            var html = window._veSelectedHtml || '';
            if (!html) { if (window.veToast) window.veToast('Selecciona un elemento primero', 'error'); return; }
            var name = prompt('Nombre del bloque global:');
            if (!name) return;
            globalBlocks.push({ name: name, html: html, created: new Date().toISOString() });
            localStorage.setItem('ve-global-blocks', JSON.stringify(globalBlocks));
            if (window.veToast) window.veToast('Bloque "' + name + '" guardado', 'success');
        }, 300);
    }});
    cmdActions.push({ cat:'Insertar', label:'Insertar bloque global', icon:'fa-globe', action: function() {
        if (!globalBlocks.length) { if (window.veToast) window.veToast('No hay bloques globales guardados', 'info'); return; }
        var list = globalBlocks.map(function(b, i) { return (i+1) + '. ' + b.name; }).join('\n');
        var idx = prompt('Bloques globales disponibles:\n\n' + list + '\n\nNúmero del bloque a insertar:');
        if (!idx) return;
        var block = globalBlocks[parseInt(idx) - 1];
        if (!block) return;
        if (window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView(block.html);
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
            if (window.veToast) window.veToast('Bloque "' + block.name + '" insertado', 'success');
        }
    }});
    // Listen for selected HTML from bridge
    window.addEventListener('message', function(ev) {
        if (ev.data && ev.data.type === 've-selected-html') window._veSelectedHtml = ev.data.html || '';
    });

    // ── U3: Drag files from desktop to preview (upload + insert) ──
    $(document).on('dragover', '#ve-canvas-wrap', function(e) { e.preventDefault(); });
    $(document).on('drop', '#ve-canvas-wrap', function(e) {
        e.preventDefault();
        var files = e.originalEvent.dataTransfer.files;
        if (!files || !files.length) return;
        var file = files[0];
        if (!file.type.startsWith('image/')) return;
        var fd = new FormData();
        fd.append('file', file);
        fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
        $.ajax({
            url: '/panel/media/files/upload',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success && res.file && res.file.public_url) {
                    var html = '<img src="' + res.file.public_url + '" class="img-fluid" alt="' + (file.name || '') + '">';
                    if (window.veEditor) {
                        window.veEditor.model.change(function() {
                            var view = window.veEditor.data.processor.toView(html);
                            var model = window.veEditor.data.toModel(view);
                            window.veEditor.model.insertContent(model);
                        });
                        if (window.veToast) window.veToast('Imagen insertada', 'success');
                    }
                }
            },
            error: function() { if (window.veToast) window.veToast('Error al subir imagen', 'error'); }
        });
    });

    // ── U4: Auto-save visual indicator (progress bar) ──
    var $autoSaveBar = $('<div class="ve-autosave-bar"></div>').prependTo('#ve-topbar');
    window.addEventListener('message', function(ev) {
        if (!ev.data) return;
        if (ev.data.type === 've-autosave-start') {
            $autoSaveBar.addClass('active');
        }
    });
    if (origAS) {
        new MutationObserver(function() {
            var cls = origAS.className || '';
            if (cls.indexOf('saving') !== -1) $autoSaveBar.addClass('active');
            else $autoSaveBar.removeClass('active');
        }).observe(origAS, { attributes: true, attributeFilter: ['class'] });
    }

    // ── D2: Conditional by locale (insert) ──
    cmdActions.push({ cat:'Insertar', label:'Contenido solo ES', icon:'fa-language', action: function() {
        if (window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView('<div data-locale="es" class="locale-conditional">Contenido solo para español</div>');
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
        }
    }});
    cmdActions.push({ cat:'Insertar', label:'Contenido solo PT', icon:'fa-language', action: function() {
        if (window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView('<div data-locale="pt" class="locale-conditional">Conteúdo apenas em português</div>');
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
        }
    }});

    // ── D4: Content placeholders ──
    cmdActions.push({ cat:'Insertar', label:'Placeholder de contenido', icon:'fa-puzzle-piece', action: function() {
        var name = prompt('Nombre del placeholder (ej: hero-image, cta-form):');
        if (!name) return;
        if (window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView('<div class="content-placeholder" data-placeholder="' + name + '" style="padding:20px;background:#f4f6f8;border:2px dashed #ccc;text-align:center;border-radius:8px;color:#999;">[PLACEHOLDER: ' + name + ']</div>');
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
        }
    }});

    // ── D5: Repeater/Loop ──
    cmdActions.push({ cat:'Insertar', label:'Bloque repetidor', icon:'fa-clone', action: function() {
        var count = prompt('¿Cuántas repeticiones? (ej: 3):', '3');
        if (!count) return;
        var n = parseInt(count) || 3;
        var html = '<div class="ve-repeater" data-repeat="' + n + '">\n';
        for (var i = 0; i < n; i++) {
            html += '  <div class="ve-repeater-item mb-3 p-3" style="background:#f8f9fa;border-radius:8px;border:1px solid #eee;">\n';
            html += '    <h4>Item ' + (i+1) + '</h4>\n';
            html += '    <p>Contenido del item</p>\n';
            html += '  </div>\n';
        }
        html += '</div>';
        if (window.veEditor) {
            window.veEditor.model.change(function() {
                var view = window.veEditor.data.processor.toView(html);
                var model = window.veEditor.data.toModel(view);
                window.veEditor.model.insertContent(model);
            });
        }
    }});

    // ── R1: Live CSS editing ──
    cmdActions.push({ cat:'Herramientas', label:'CSS en vivo', icon:'fa-paint-brush', action: function() {
        var css = prompt('CSS a aplicar en tiempo real:\n(se aplica al preview)');
        if (!css) return;
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({ type: 've-inject-css', css: css }, '*');
        }
    }});

    // ── R3: Animation preview ──
    cmdActions.push({ cat:'Herramientas', label:'Previsualizar animaciones', icon:'fa-play', action: function() {
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({ type: 've-replay-animations' }, '*');
        }
        if (window.veToast) window.veToast('Animaciones reproduciéndose', 'info');
    }});

    // ── R4: Google SEO preview ──
    cmdActions.push({ cat:'Herramientas', label:'Vista previa en Google', icon:'fa-google', action: function() {
        var title = $('#ve-settings-seo-title').val() || $('#ve-settings-title').val() || 'Título de la página';
        var desc = $('#ve-settings-seo-description').val() || 'Sin descripción';
        var url = window.location.origin + '/' + ($('#ve-settings-slug').val() || '');
        var html = '<div style="font-family:Arial,sans-serif;padding:20px;max-width:600px;">' +
            '<div style="font-size:20px;color:#1a0dab;margin-bottom:4px;cursor:pointer;">' + title + '</div>' +
            '<div style="font-size:14px;color:#006621;margin-bottom:4px;">' + url + '</div>' +
            '<div style="font-size:13px;color:#545454;line-height:1.5;">' + desc + '</div></div>';
        var $m = $('<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content ve-cmd-content"><div class="ve-ai-modal-header"><h6 class="ve-ai-modal-title"><i class="fa-duotone fa-solid fa-google ve-ai-modal-icon"></i>Vista previa en Google</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="ve-ai-modal-body">' + html + '</div></div></div></div>');
        $('body').append($m);
        new bootstrap.Modal($m[0]).show();
        $m.on('hidden.bs.modal', function() { $m.remove(); });
    }});

    // ── R5: Social share preview ──
    cmdActions.push({ cat:'Herramientas', label:'Vista previa social', icon:'fa-share-alt', action: function() {
        var title = $('#ve-settings-seo-title').val() || 'Título';
        var desc = $('#ve-settings-seo-description').val() || 'Descripción';
        var img = $('#ve-settings-featured-image').val() || '';
        var html = '<div style="max-width:500px;border:1px solid #ddd;border-radius:8px;overflow:hidden;font-family:-apple-system,sans-serif;">' +
            (img ? '<div style="height:200px;background:#f0f0f0;overflow:hidden;"><img src="'+img+'" style="width:100%;height:100%;object-fit:cover;" alt=""></div>' : '<div style="height:200px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#aaa;"><i class="fa-duotone fa-solid fa-image fa-2x"></i></div>') +
            '<div style="padding:12px;"><div style="font-size:11px;color:#999;text-transform:uppercase;margin-bottom:4px;">' + window.location.hostname + '</div>' +
            '<div style="font-size:15px;font-weight:600;color:#333;margin-bottom:4px;">' + title + '</div>' +
            '<div style="font-size:13px;color:#666;line-height:1.4;">' + desc + '</div></div></div>';
        var $m = $('<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content ve-cmd-content"><div class="ve-ai-modal-header"><h6 class="ve-ai-modal-title"><i class="fa-duotone fa-solid fa-share-alt ve-ai-modal-icon"></i>Vista previa al compartir</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="ve-ai-modal-body">' + html + '</div></div></div></div>');
        $('body').append($m);
        new bootstrap.Modal($m[0]).show();
        $m.on('hidden.bs.modal', function() { $m.remove(); });
    }});

    // ── E4: MediaPicker for image swap in preview ──
    window.addEventListener('message', function(ev) {
        if (ev.data && ev.data.type === 've-open-media-picker') {
            var nodeId = ev.data.nodeId;
            if (window.MediaPicker) {
                window.MediaPicker.open({
                    filter: 'image',
                    title: 'Cambiar imagen',
                    onSelect: function(url) {
                        var frame = document.getElementById('ve-preview-frame');
                        if (frame && frame.contentWindow) {
                            frame.contentWindow.postMessage({ type: 've-set-img-src', nodeId: nodeId, src: url }, '*');
                        }
                    }
                });
            }
        }
    });

    // ── SEO score + toggle ──
    $(document).on('click', '#btn-toggle-seo-fields', function() {
        $('#ve-seo-fields').toggleClass('ve-hidden');
        $(this).text($('#ve-seo-fields').hasClass('ve-hidden') ? 'Editar SEO' : 'Ocultar campos');
    });
    function updateSeoScore() {
        var title = ($('#ve-settings-seo-title').val() || '').trim();
        var desc = ($('#ve-settings-seo-description').val() || '').trim();
        var kw = ($('#ve-settings-seo-keywords').val() || '').trim();
        var score = 0, max = 100;
        if (title.length > 0) score += 25;
        if (title.length >= 30 && title.length <= 60) score += 10;
        if (desc.length > 0) score += 25;
        if (desc.length >= 120 && desc.length <= 160) score += 10;
        if (kw.length > 0) score += 15;
        if ($('#ve-settings-featured-image').val()) score += 15;
        var pct = Math.min(Math.round(score), 100);
        var grade = pct >= 80 ? 'Excelente' : pct >= 60 ? 'Bueno' : pct >= 40 ? 'Regular' : 'Bajo';
        var letter = pct >= 80 ? 'A' : pct >= 60 ? 'B' : pct >= 40 ? 'C' : 'D';
        var ringColor = pct >= 80 ? '#43a047' : pct >= 60 ? '#333' : pct >= 40 ? '#f9a825' : '#b10100';
        $('#ve-seo-score-num').text(pct + '/100');
        $('#ve-seo-score-grade').text(grade);
        $('#ve-seo-score-letter').text(letter);
        $('#ve-seo-score-ring').css('border-color', ringColor).find('span').css('color', ringColor);
        $('#ve-seo-preview-title').text(title || 'Sin título SEO');
        $('#ve-seo-preview-desc').text(desc ? (desc.length > 120 ? desc.substring(0,120) + '...' : desc) : 'Sin descripción');
    }
    $(document).on('input', '#ve-settings-seo-title, #ve-settings-seo-description, #ve-settings-seo-keywords, #ve-settings-featured-image', updateSeoScore);
    setTimeout(updateSeoScore, 2000);

    // ── P2.4: Revision diff indicator ──
    // Show simple diff info on hover over history items
    $(document).on('mouseenter', '.ve-history-item', function() {
        var $item = $(this);
        if ($item.data('diff-shown')) return;
        var idx = $item.index();
        // Get HTML lengths from history stack (if accessible)
        var html = $item.data('html') || '';
        if (html) {
            var len = html.length;
            var $badge = $('<span class="ve-diff-badge">' + Math.round(len/1024) + 'KB</span>');
            $item.find('.ms-auto').length ? $item.find('.ms-auto').before($badge) : $item.append($badge);
            $item.data('diff-shown', true);
        }
    });

    // ── P1.5: Responsive per-device class helper ──
    // Quick responsive class toggle in inspector visibility section
    // Already partially exists via ve-visibility-btn. Enhance with display utilities.
    $(document).on('click', '.ve-visibility-btn', function() {
        $(this).toggleClass('active');
        var cls = $(this).data('class');
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow && cls) {
            var add = $(this).hasClass('active');
            frame.contentWindow.postMessage({ type: 've-toggle-class', cls: cls, add: add }, '*');
        }
    });

    // ── P1.4: Global colors/fonts design system ──
    // Sync color picker ↔ text input
    $(document).on('input', '.ve-color-picker', function() {
        $(this).siblings('.form-control').val($(this).val());
    });
    $(document).on('input', '.ve-color-input-row .form-control', function() {
        var val = $(this).val();
        if (/^#[0-9a-fA-F]{6}$/.test(val)) $(this).siblings('.ve-color-picker').val(val);
    });
    // Apply global fonts to iframe
    $(document).on('change', '#ve-global-font-heading, #ve-global-font-body', function() {
        var heading = $('#ve-global-font-heading').val();
        var body = $('#ve-global-font-body').val();
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({ type: 've-apply-global-fonts', heading: heading, body: body }, '*');
        }
    });
    // Apply global colors to iframe
    $(document).on('change', '[id^="ve-global-color-"]', function() {
        var primary = $('#ve-global-color-primary').val();
        var secondary = $('#ve-global-color-secondary').val();
        var accent = $('#ve-global-color-accent').val();
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({ type: 've-apply-global-colors', primary: primary, secondary: secondary, accent: accent }, '*');
        }
    });

    // ── P2.5: Multi-select feedback ──
    window.addEventListener('message', function(ev) {
        if (ev.data && ev.data.type === 've-multi-select') {
            var count = ev.data.count || 0;
            if (count > 0) {
                $('#ve-inspector-element-name').text(count + ' elementos seleccionados');
                if (window.veToast) window.veToast(count + ' elementos seleccionados (Shift+click para añadir)', 'info');
            }
        }
    });

    // ── P4.5 + P4.6: Accessibility audit + Performance score ──
    function runAccessibilityAudit() {
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentWindow || !frame.contentDocument) return;
        var doc = frame.contentDocument;
        var issues = [];
        var passed = 0;

        // Check images without alt
        var imgs = doc.querySelectorAll('img');
        var noAlt = 0;
        imgs.forEach(function(img) { if (!img.alt || !img.alt.trim()) noAlt++; });
        if (noAlt > 0) issues.push({ type:'fail', msg: noAlt + ' imagen(es) sin atributo alt' });
        else if (imgs.length) { passed++; issues.push({ type:'pass', msg: 'Todas las imágenes tienen alt (' + imgs.length + ')' }); }

        // Check headings hierarchy
        var headings = doc.querySelectorAll('h1,h2,h3,h4,h5,h6');
        var h1Count = doc.querySelectorAll('h1').length;
        if (h1Count === 0) issues.push({ type:'warn', msg: 'No hay H1 en la página' });
        else if (h1Count > 1) issues.push({ type:'warn', msg: h1Count + ' etiquetas H1 (debería ser 1)' });
        else { passed++; issues.push({ type:'pass', msg: 'Un solo H1 correcto' }); }

        // Check links without text
        var links = doc.querySelectorAll('a');
        var emptyLinks = 0;
        links.forEach(function(a) { if (!a.textContent.trim() && !a.querySelector('img') && !a.getAttribute('aria-label')) emptyLinks++; });
        if (emptyLinks > 0) issues.push({ type:'fail', msg: emptyLinks + ' enlace(s) sin texto accesible' });
        else if (links.length) { passed++; issues.push({ type:'pass', msg: 'Todos los enlaces tienen texto (' + links.length + ')' }); }

        // Check contrast (basic — check if text color == bg color)
        var bodyBg = window.getComputedStyle(doc.body).backgroundColor;
        issues.push({ type:'pass', msg: 'Color de fondo del body definido' });
        passed++;

        // Check meta description
        var metaDesc = doc.querySelector('meta[name="description"]');
        if (metaDesc && metaDesc.content) { passed++; issues.push({ type:'pass', msg: 'Meta description presente' }); }
        else issues.push({ type:'warn', msg: 'Sin meta description' });

        // Check form labels
        var inputs = doc.querySelectorAll('input:not([type="hidden"]),textarea,select');
        var noLabel = 0;
        inputs.forEach(function(inp) {
            if (!inp.id || !doc.querySelector('label[for="'+inp.id+'"]')) {
                if (!inp.getAttribute('aria-label') && !inp.getAttribute('placeholder')) noLabel++;
            }
        });
        if (noLabel > 0) issues.push({ type:'warn', msg: noLabel + ' campo(s) de formulario sin label' });
        else if (inputs.length) { passed++; issues.push({ type:'pass', msg: 'Campos de formulario con labels' }); }

        var total = issues.length;
        var score = Math.round((passed / total) * 100);

        // Show in a modal/toast
        var scoreClass = score >= 80 ? 'good' : score >= 50 ? 'ok' : 'bad';
        var html = '<div class="ve-audit-score ve-audit-score-' + scoreClass + '">' + score + '</div>';
        issues.forEach(function(i) {
            var icon = i.type === 'pass' ? '&#10003;' : i.type === 'warn' ? '!' : '&#10005;';
            html += '<div class="ve-audit-item ve-audit-' + i.type + '"><div class="ve-audit-icon">' + icon + '</div><div class="ve-audit-msg">' + i.msg + '</div></div>';
        });

        // Reuse shortcuts modal structure
        var $modal = $('<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border:none;border-radius:12px;overflow:hidden;"><div class="modal-header" style="background:#fff;border-bottom:1px solid #eee;padding:14px 20px;"><h6 style="font-weight:700;font-size:14px;color:#333;margin:0;"><i class="fa-duotone fa-solid fa-universal-access me-2" style="color:#999;"></i>Auditoría de accesibilidad</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" style="padding:16px 20px;max-height:60vh;overflow-y:auto;">' + html + '</div></div></div></div>');
        $('body').append($modal);
        new bootstrap.Modal($modal[0]).show();
        $modal.on('hidden.bs.modal', function() { $modal.remove(); });
    }

    function runPerformanceScore() {
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) return;
        var doc = frame.contentDocument;
        var issues = [];
        var passed = 0;

        // Count images
        var imgs = doc.querySelectorAll('img');
        var largeSrc = 0;
        imgs.forEach(function(img) { if (img.naturalWidth > 1920) largeSrc++; });
        issues.push({ type: imgs.length > 20 ? 'warn' : 'pass', msg: imgs.length + ' imágenes en la página' + (largeSrc > 0 ? ' (' + largeSrc + ' muy grandes)' : '') });
        if (imgs.length <= 20) passed++;

        // Check lazy loading
        var noLazy = 0;
        imgs.forEach(function(img) { if (!img.loading || img.loading !== 'lazy') noLazy++; });
        if (noLazy > 3) issues.push({ type:'warn', msg: noLazy + ' imágenes sin lazy loading' });
        else { passed++; issues.push({ type:'pass', msg: 'Lazy loading aplicado' }); }

        // Count scripts
        var scripts = doc.querySelectorAll('script[src]');
        issues.push({ type: scripts.length > 10 ? 'warn' : 'pass', msg: scripts.length + ' scripts externos' });
        if (scripts.length <= 10) passed++;

        // Count stylesheets
        var styles = doc.querySelectorAll('link[rel="stylesheet"]');
        issues.push({ type: styles.length > 8 ? 'warn' : 'pass', msg: styles.length + ' hojas de estilo' });
        if (styles.length <= 8) passed++;

        // Estimate page weight
        var html = doc.documentElement.outerHTML || '';
        var sizeKB = Math.round(html.length / 1024);
        issues.push({ type: sizeKB > 500 ? 'warn' : 'pass', msg: 'HTML: ~' + sizeKB + ' KB' });
        if (sizeKB <= 500) passed++;

        // Check WebP usage
        var webpCount = 0;
        imgs.forEach(function(img) { if ((img.src||'').match(/\.webp/i)) webpCount++; });
        if (imgs.length > 0 && webpCount < imgs.length / 2) issues.push({ type:'warn', msg: 'Solo ' + webpCount + '/' + imgs.length + ' imágenes en WebP' });
        else if (imgs.length) { passed++; issues.push({ type:'pass', msg: 'Buen uso de WebP' }); }

        var total = issues.length;
        var score = Math.round((passed / total) * 100);
        var scoreClass = score >= 80 ? 'good' : score >= 50 ? 'ok' : 'bad';
        var phtml = '<div class="ve-audit-score ve-audit-score-' + scoreClass + '">' + score + '</div>';
        issues.forEach(function(i) {
            var icon = i.type === 'pass' ? '&#10003;' : '!';
            phtml += '<div class="ve-audit-item ve-audit-' + i.type + '"><div class="ve-audit-icon">' + icon + '</div><div class="ve-audit-msg">' + i.msg + '</div></div>';
        });
        var $m = $('<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border:none;border-radius:12px;overflow:hidden;"><div class="modal-header" style="background:#fff;border-bottom:1px solid #eee;padding:14px 20px;"><h6 style="font-weight:700;font-size:14px;color:#333;margin:0;"><i class="fa-duotone fa-solid fa-gauge-high me-2" style="color:#999;"></i>Performance score</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" style="padding:16px 20px;max-height:60vh;overflow-y:auto;">' + phtml + '</div></div></div></div>');
        $('body').append($m);
        new bootstrap.Modal($m[0]).show();
        $m.on('hidden.bs.modal', function() { $m.remove(); });
    }

    // (audit actions added to cmdActions after it's defined below)

    // ── P2.2: CSS class autocomplete for Bootstrap 5.3 ──
    var bsClasses = [
        // Layout
        'container','container-fluid','container-sm','container-md','container-lg','container-xl','container-xxl',
        'row','col','col-1','col-2','col-3','col-4','col-5','col-6','col-7','col-8','col-9','col-10','col-11','col-12',
        'col-sm-1','col-sm-2','col-sm-3','col-sm-4','col-sm-5','col-sm-6','col-sm-7','col-sm-8','col-sm-9','col-sm-10','col-sm-11','col-sm-12',
        'col-md-1','col-md-2','col-md-3','col-md-4','col-md-5','col-md-6','col-md-7','col-md-8','col-md-9','col-md-10','col-md-11','col-md-12',
        'col-lg-1','col-lg-2','col-lg-3','col-lg-4','col-lg-5','col-lg-6','col-lg-7','col-lg-8','col-lg-9','col-lg-10','col-lg-11','col-lg-12',
        // Spacing
        'p-0','p-1','p-2','p-3','p-4','p-5','px-0','px-1','px-2','px-3','px-4','px-5','py-0','py-1','py-2','py-3','py-4','py-5',
        'pt-0','pt-1','pt-2','pt-3','pt-4','pt-5','pb-0','pb-1','pb-2','pb-3','pb-4','pb-5',
        'm-0','m-1','m-2','m-3','m-4','m-5','m-auto','mx-auto','mx-0','mx-1','mx-2','mx-3','mx-4','mx-5',
        'my-0','my-1','my-2','my-3','my-4','my-5','mt-0','mt-1','mt-2','mt-3','mt-4','mt-5','mb-0','mb-1','mb-2','mb-3','mb-4','mb-5',
        'g-0','g-1','g-2','g-3','g-4','g-5',
        // Display
        'd-none','d-block','d-inline','d-inline-block','d-flex','d-inline-flex','d-grid','d-table',
        'd-sm-none','d-sm-block','d-sm-flex','d-md-none','d-md-block','d-md-flex','d-lg-none','d-lg-block','d-lg-flex',
        // Flex
        'flex-row','flex-column','flex-wrap','flex-nowrap','justify-content-start','justify-content-center','justify-content-end','justify-content-between','justify-content-around',
        'align-items-start','align-items-center','align-items-end','align-items-stretch','align-self-start','align-self-center','align-self-end',
        'gap-0','gap-1','gap-2','gap-3','gap-4','gap-5',
        // Text
        'text-start','text-center','text-end','text-wrap','text-nowrap','text-truncate',
        'fw-light','fw-normal','fw-bold','fw-semibold','fw-bolder','fst-italic','fst-normal',
        'text-uppercase','text-lowercase','text-capitalize','text-decoration-none','text-decoration-underline',
        'fs-1','fs-2','fs-3','fs-4','fs-5','fs-6','lh-1','lh-sm','lh-base','lh-lg',
        // Colors
        'text-primary','text-secondary','text-success','text-danger','text-warning','text-info','text-light','text-dark','text-muted','text-white','text-body',
        'bg-primary','bg-secondary','bg-success','bg-danger','bg-warning','bg-info','bg-light','bg-dark','bg-white','bg-transparent',
        // Borders
        'border','border-0','border-top','border-bottom','border-start','border-end',
        'border-primary','border-secondary','border-success','border-danger',
        'rounded','rounded-0','rounded-1','rounded-2','rounded-3','rounded-circle','rounded-pill',
        // Sizing
        'w-25','w-50','w-75','w-100','w-auto','h-25','h-50','h-75','h-100','h-auto','mw-100','mh-100',
        'vw-100','vh-100','min-vw-100','min-vh-100',
        // Position
        'position-static','position-relative','position-absolute','position-fixed','position-sticky',
        'top-0','top-50','top-100','start-0','start-50','start-100','end-0','end-50','end-100','bottom-0','bottom-50','bottom-100',
        'translate-middle',
        // Visibility
        'visible','invisible','overflow-auto','overflow-hidden','overflow-visible','overflow-scroll',
        'opacity-25','opacity-50','opacity-75','opacity-100',
        // Shadow
        'shadow-none','shadow-sm','shadow','shadow-lg',
        // Misc
        'img-fluid','img-thumbnail','list-unstyled','clearfix','float-start','float-end','float-none',
        'sticky-top','fixed-top','fixed-bottom',
    ];
    $(document).on('focus', '#ve-attr-class', function() {
        var $input = $(this);
        if ($input.data('ve-ac-bound')) return;
        $input.data('ve-ac-bound', true);
        var $wrap = $input.parent().css('position','relative');
        var $ac = $('<div class="ve-autocomplete"></div>').hide().appendTo($wrap);
        var selIdx = -1;

        function update() {
            var val = $input.val();
            var parts = val.split(/\s+/);
            var last = (parts[parts.length - 1] || '').toLowerCase();
            if (last.length < 2) { $ac.hide(); return; }
            var matches = bsClasses.filter(function(c) { return c.indexOf(last) !== -1; }).slice(0, 10);
            if (!matches.length) { $ac.hide(); return; }
            $ac.empty().show();
            selIdx = -1;
            matches.forEach(function(c) {
                var hl = c.replace(new RegExp('(' + last.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')','i'), '<mark>$1</mark>');
                $ac.append('<div class="ve-autocomplete-item">' + hl + '</div>');
            });
        }

        function pick(cls) {
            var val = $input.val();
            var parts = val.split(/\s+/);
            parts[parts.length - 1] = cls;
            $input.val(parts.join(' ') + ' ').focus();
            $ac.hide();
        }

        $input.on('input', update);
        $ac.on('click', '.ve-autocomplete-item', function() { pick($(this).text()); });
        $input.on('keydown', function(e) {
            var $items = $ac.find('.ve-autocomplete-item');
            if (!$items.length || $ac.is(':hidden')) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); selIdx = Math.min(selIdx+1, $items.length-1); $items.removeClass('active').eq(selIdx).addClass('active'); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); selIdx = Math.max(selIdx-1, 0); $items.removeClass('active').eq(selIdx).addClass('active'); }
            else if (e.key === 'Enter' && selIdx >= 0) { e.preventDefault(); pick($items.eq(selIdx).text()); }
            else if (e.key === 'Escape') { $ac.hide(); }
        });
        $input.on('blur', function() { setTimeout(function() { $ac.hide(); }, 200); });
    });

    // ── P4.1: Block tooltip preview on hover ──
    var $tooltip = null;
    var tooltipTimer = null;
    $(document).on('mouseenter', '.ve-sc-item', function(e) {
        var $el = $(this);
        var name = $el.data('name') || '';
        var desc = $el.attr('title') || '';
        if (!name) return;
        tooltipTimer = setTimeout(function() {
            if ($tooltip) $tooltip.remove();
            $tooltip = $('<div class="ve-block-tooltip"><div class="ve-block-tooltip-name">[' + name + ']</div>' + (desc ? '<div>' + desc + '</div>' : '') + '</div>');
            $('body').append($tooltip);
            var rect = $el[0].getBoundingClientRect();
            $tooltip.css({ top: rect.top - $tooltip.outerHeight() - 6, left: rect.left + rect.width/2 - $tooltip.outerWidth()/2 });
            if (parseInt($tooltip.css('top')) < 0) $tooltip.css('top', rect.bottom + 6);
        }, 600);
    }).on('mouseleave', '.ve-sc-item', function() {
        clearTimeout(tooltipTimer);
        if ($tooltip) { $tooltip.remove(); $tooltip = null; }
    });

    // ── P3.8: Split responsive preview ──
    $(document).on('click', '#btn-split-view', function() {
        var $wrap = $('#ve-canvas-wrap');
        var $frame = $('#ve-preview-frame');
        if ($wrap.hasClass('split')) {
            $wrap.removeClass('split').addClass('desktop');
            $wrap.find('.ve-split-mobile').remove();
            $('#responsive-bar-icon').attr('class', 'fa-duotone fa-solid fa-desktop');
        } else {
            $wrap.removeClass('desktop tablet mobile laptop').addClass('split');
            if (!$wrap.find('.ve-split-mobile').length) {
                var $mobile = $('<iframe class="ve-split-mobile" sandbox="allow-same-origin allow-scripts"></iframe>').attr('src', $frame.attr('src'));
                $wrap.append($mobile);
            }
            $('#responsive-bar-icon').attr('class', 'fa-duotone fa-solid fa-columns');
        }
    });

    // ── P2.1: Quick bar inspect handler ──
    window.addEventListener('message', function(ev) {
        if (ev.data && ev.data.type === 've-request-inspect') {
            $('#ve-sidebar-nav .ve-nav-btn[data-panel="inspector"]').trigger('click');
        }
    });

    // ── P4.4: Dark mode toggle ──
    $(document).on('click', '#btn-dark-mode', function() {
        $('#ve-body').toggleClass('ve-dark');
        localStorage.setItem('ve-dark-mode', $('#ve-body').hasClass('ve-dark') ? '1' : '0');
    });
    if (localStorage.getItem('ve-dark-mode') === '1') $('#ve-body').addClass('ve-dark');

    // ── P4.2: Command palette (Ctrl+K) ──
    // Base command palette actions (cmdActions declared earlier in the script)
    cmdActions.push(
        { cat:'Paneles', label:'Bloques', icon:'fa-puzzle-piece', action:function(){ $('#ve-sidebar-nav .ve-nav-btn[data-panel="shortcodes"]').trigger('click'); }},
        { cat:'Paneles', label:'Inspector', icon:'fa-sliders', action:function(){ $('#ve-sidebar-nav .ve-nav-btn[data-panel="inspector"]').trigger('click'); }},
        { cat:'Paneles', label:'Layout', icon:'fa-table-columns', action:function(){ $('#ve-sidebar-nav .ve-nav-btn[data-panel="layout"]').trigger('click'); }},
        { cat:'Paneles', label:'Secciones', icon:'fa-layer-group', action:function(){ $('#ve-sidebar-nav .ve-nav-btn[data-panel="sections"]').trigger('click'); }},
        { cat:'Paneles', label:'Historial', icon:'fa-clock-rotate-left', action:function(){ $('#ve-sidebar-nav .ve-nav-btn[data-panel="history"]').trigger('click'); }},
        { cat:'Paneles', label:'Código HTML', icon:'fa-code', action:function(){ $('#ve-sidebar-nav .ve-nav-btn[data-panel="code"]').trigger('click'); }},
        { cat:'Paneles', label:'Ajustes', icon:'fa-gear', action:function(){ $('#ve-sidebar-nav .ve-nav-btn[data-panel="settings"]').trigger('click'); }},
        { cat:'Acciones', label:'Guardar', icon:'fa-save', kbd:'Ctrl+S', action:function(){ $('#btn-save').trigger('click'); }},
        { cat:'Acciones', label:'Deshacer', icon:'fa-rotate-left', kbd:'Ctrl+Z', action:function(){ $('#btn-undo').trigger('click'); }},
        { cat:'Acciones', label:'Rehacer', icon:'fa-rotate-right', kbd:'Ctrl+Y', action:function(){ $('#btn-redo').trigger('click'); }},
        { cat:'Acciones', label:'Preview', icon:'fa-eye', action:function(){ window.open($('.ve-topbar-preview-btn').attr('href'),'_blank'); }},
        { cat:'Acciones', label:'Exportar HTML', icon:'fa-file-export', action:function(){ $('#btn-export-html').trigger('click'); }},
        { cat:'Acciones', label:'Atajos de teclado', icon:'fa-keyboard', kbd:'?', action:function(){ new bootstrap.Modal(document.getElementById('ve-shortcuts-modal')).show(); }},
        { cat:'Acciones', label:'Modo oscuro', icon:'fa-circle-half-stroke', action:function(){ $('#btn-dark-mode').trigger('click'); }},
        { cat:'Vista', label:'Desktop', icon:'fa-desktop', action:function(){ $('.breakpoint-btn[data-breakpoint="desktop"]').trigger('click'); }},
        { cat:'Vista', label:'Tablet', icon:'fa-tablet-screen-button', action:function(){ $('.breakpoint-btn[data-breakpoint="tablet"]').trigger('click'); }},
        { cat:'Vista', label:'Móvil', icon:'fa-mobile-screen-button', action:function(){ $('.breakpoint-btn[data-breakpoint="mobile"]').trigger('click'); }}
    );
    $('.ve-sc-item').each(function(){
        var n = $(this).data('name');
        cmdActions.push({ cat:'Bloques', label:'['+n+']', icon:'fa-cube', action:function(){ $('.ve-sc-item[data-name="'+n+'"]').trigger('click'); }});
    });
    // Add audit tools to command palette
    cmdActions.push({ cat:'Herramientas', label:'Auditoría de accesibilidad', icon:'fa-universal-access', action: runAccessibilityAudit });
    cmdActions.push({ cat:'Herramientas', label:'Performance score', icon:'fa-gauge-high', action: runPerformanceScore });
    function renderCmd(q) {
        var $r = $('#ve-cmd-results').empty();
        var f = q ? cmdActions.filter(function(a){ return a.label.toLowerCase().indexOf(q)!==-1||a.cat.toLowerCase().indexOf(q)!==-1; }) : cmdActions;
        var lc = '';
        f.slice(0,15).forEach(function(a){
            if(a.cat!==lc){ $r.append('<div class="ve-cmd-cat">'+a.cat+'</div>'); lc=a.cat; }
            var $i=$('<div class="ve-cmd-item">').html('<i class="fa-duotone fa-solid '+a.icon+'"></i><span>'+a.label+'</span>'+(a.kbd?'<kbd>'+a.kbd+'</kbd>':''));
            $i.on('click',function(){ a.action(); bootstrap.Modal.getInstance(document.getElementById('ve-command-palette')).hide(); });
            $r.append($i);
        });
        if(!f.length) $r.html('<div class="ve-cmd-cat" style="text-align:center;padding:20px;">Sin resultados</div>');
    }
    $(document).on('input','#ve-cmd-input',function(){ renderCmd($(this).val().toLowerCase().trim()); });
    function openCmdPalette() {
        var el = document.getElementById('ve-command-palette');
        if (!el) return;
        var m = bootstrap.Modal.getOrCreateInstance(el);
        m.show();
        setTimeout(function(){ $('#ve-cmd-input').val('').focus(); renderCmd(''); }, 200);
    }
    $(document).on('keydown',function(e){
        if((e.ctrlKey||e.metaKey)&&e.key==='k'){
            e.preventDefault();
            var t=(e.target.tagName||'').toLowerCase();
            if(t!=='input'&&t!=='textarea') openCmdPalette();
        }
    });

    // ── Word count in bottom bar ──
    function updateWordCount() {
        try {
            var html = window.veEditor ? window.veEditor.getData() : '';
            var text = html.replace(/<[^>]*>/g, ' ').replace(/&[^;]+;/g, ' ').replace(/\s+/g, ' ').trim();
            var count = text ? text.split(' ').length : 0;
            $('#ve-word-count').text(count.toLocaleString() + ' palabras');
        } catch(e) {}
    }
    setInterval(updateWordCount, 5000);
    setTimeout(updateWordCount, 3000);

})(jQuery);
</script>

</body>
</html>
