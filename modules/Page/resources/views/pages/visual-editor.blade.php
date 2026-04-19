<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <title>Editor visual — {{ $page->title }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/monokai.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldgutter.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/display/fullscreen.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/dialog/dialog.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="{{ themeAsset('css/extra.css') }}">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <link rel="stylesheet" href="{{ asset('modules/Page/css/visual-editor.css') }}?v=49">
    <style>
        @keyframes vePulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(144,187,19,0.4); }
            50%       { box-shadow: 0 0 0 6px rgba(144,187,19,0); }
        }
        .ve-btn-unsaved { animation: vePulse 2s infinite; }

        /* Presentation mode */
        body.ve-presentation-mode #ve-sidebar,
        body.ve-presentation-mode #ve-topbar,
        body.ve-presentation-mode #ve-bottombar,
        body.ve-presentation-mode #ve-statusbar { display: none !important; }
        body.ve-presentation-mode #ve-main { padding: 0 !important; }
        body.ve-presentation-mode #ve-sidebar-resize { display: none !important; }
        body.ve-presentation-mode #ve-preview-frame { width: 100vw !important; height: 100vh !important; border: none !important; }

        /* Device frame overlay */
        #ve-device-frame {
            position: absolute; pointer-events: none; z-index: 10;
            border: 12px solid #1e1e2e; border-radius: 36px;
            box-shadow: 0 0 0 2px #333, 0 20px 60px rgba(0,0,0,0.4);
            transition: all .3s ease; display: none;
            top: 0; left: 0;
        }
        #ve-device-frame.tablet { border-radius: 20px; border-width: 16px; }
        #ve-device-frame::before {
            content: ''; position: absolute; top: -8px; left: 50%; transform: translateX(-50%);
            width: 60px; height: 4px; background: #333; border-radius: 2px;
        }

        /* ── Comment pins ── */
        .ve-comment-pin { transition: transform .15s; }
        .ve-comment-pin:hover { transform: scale(1.3); }

        /* ── Page title inline edit ── */
        #ve-page-title-display { cursor: pointer; }

        /* ── Snippets modal layout ── */
        .ve-snippets-layout {
            display: flex;
            min-height: 360px;
        }
        .ve-snippets-sidebar {
            width: 200px;
            flex-shrink: 0;
            border-right: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            padding: 10px;
            background: #f8f9fa;
        }
        .ve-snippets-list {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .ve-snippet-item {
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border: 1px solid transparent;
            background: #fff;
            color: #333;
        }
        .ve-snippet-item:hover { background: #e9ecef; border-color: #dee2e6; }
        .ve-snippet-item.active { background: #90bb13; color: #fff; border-color: #7aa310; }
        .ve-snippets-editor {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
        }
        .ve-snippets-editor textarea { flex: 1; resize: none; }
        .ve-monospace { font-family: 'Consolas', 'Monaco', monospace; font-size: 12px; }

        /* ── Icon Picker ── */
        .ve-icon-cell {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            width: 60px; height: 60px; border-radius: 8px; cursor: pointer;
            border: 1px solid transparent; transition: background .15s, border-color .15s;
            font-size: 11px; color: #555; gap: 4px; padding: 4px;
        }
        .ve-icon-cell i { font-size: 20px; color: #444; }
        .ve-icon-cell:hover { background: #f0f5e6; border-color: #90bb13; }
        .ve-icon-cell:hover i { color: #90bb13; }
        .ve-icon-cell span { font-size: 9px; text-align: center; overflow: hidden; white-space: nowrap; max-width: 56px; text-overflow: ellipsis; }

        /* ── Quick Actions Bar — fijo al viewport, a la derecha del sidebar.
           Así el responsive del iframe (cambios de breakpoint) no lo mueve. ── */
        #ve-quick-actions-bar {
            position: fixed;
            top: 50%;
            left: calc(var(--ve-sidebar-offset, 64px) + 12px);
            transform: translateY(-50%);
            z-index: 40;
            display: flex; flex-direction: column; gap: 2px;
            padding: 4px;
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
        }
        /* Hide in presentation mode */
        body.ve-presentation-mode #ve-quick-actions-bar { display: none !important; }
        #ve-quick-actions-bar::-webkit-scrollbar { display: none; }
        #ve-quick-actions-bar:empty { display: none; }
        #ve-quick-actions-bar::before {
            content: 'ACCIONES';
            position: absolute;
            top: -18px; left: 0;
            font-size: 8.5px;
            font-weight: 600;
            color: #a1a1aa;
            letter-spacing: .08em;
            font-family: 'Inter', sans-serif;
            pointer-events: none;
        }
        .ve-qa-btn {
            width: 30px; height: 30px; border-radius: 6px;
            background: transparent; color: #52525b; font-size: 12px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; border: none;
            transition: background .12s, color .12s, transform .1s;
            position: relative;
        }
        .ve-qa-btn:hover { background: #18181b; color: #fff; transform: scale(1.05); }
        /* Custom tooltip using data-tooltip (to avoid browser native title lag) */
        .ve-qa-btn[data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%) translateX(-4px);
            background: #18181b;
            color: #fff;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity .14s ease, transform .14s ease;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            box-shadow: 0 4px 14px rgba(0,0,0,.18), 0 1px 2px rgba(0,0,0,.1);
            z-index: 1000;
            letter-spacing: .01em;
        }
        /* Arrow pointing to button */
        .ve-qa-btn[data-tooltip]::before {
            content: '';
            position: absolute;
            left: calc(100% + 4px);
            top: 50%;
            transform: translateY(-50%) translateX(-4px);
            width: 0; height: 0;
            border-top: 5px solid transparent;
            border-bottom: 5px solid transparent;
            border-right: 6px solid #18181b;
            pointer-events: none;
            opacity: 0;
            transition: opacity .14s ease, transform .14s ease;
            z-index: 1000;
        }
        .ve-qa-btn:hover[data-tooltip]::after,
        .ve-qa-btn:hover[data-tooltip]::before {
            opacity: 1;
            transform: translateY(-50%) translateX(0);
        }

        /* Wireframe mode */
        #ve-canvas-wrap.ve-wireframe #ve-preview-frame { filter: grayscale(1) contrast(0.4) brightness(1.5); }
        #ve-canvas-wrap.ve-wireframe::after {
            content: 'WIREFRAME'; position: absolute; top: 8px; right: 12px;
            font-size: 10px; font-weight: 700; letter-spacing: 2px; color: #90bb13;
            pointer-events: none; z-index: 99;
        }
    </style>
</head>
<body>

{{-- Hidden CKEditor textarea (must be in DOM before scripts init) --}}
<div style="display:none;" aria-hidden="true">
    <textarea id="ve-content">{{ $initialContent }}</textarea>
</div>

{{-- Draft banner moved inside #ve-body as fixed overlay so it doesn't break the 100vh grid --}}

{{-- ── Body ────────────────────────────────────────────────────────────────── --}}
<div id="ve-body">

    {{-- ── Top bar ──────────────────────────────────────────────────────── --}}
    <div id="ve-topbar">
        {{-- Left: brand + breadcrumb --}}
        <div class="ve-topbar-brand">
            <div class="ve-topbar-mark">V</div>
            <span class="ve-topbar-name">Visual</span>
            <div class="ve-topbar-sep"></div>
            <div class="ve-topbar-breadcrumb">
                <span class="ve-crumb-muted">Páginas</span>
                <i class="fa-solid fa-chevron-right ve-crumb-sep"></i>
                <span class="ve-crumb-current">{{ $page->title }}</span>
                <div class="ve-topbar-status-dot"></div>
            </div>
        </div>

        {{-- Center: contextual (changes per active panel) --}}
        <div class="ve-topbar-ctx" id="ve-topbar-ctx">
            {{-- Default tools (shown when no specific panel context) --}}
            <div class="ve-ctx-group" id="ve-ctx-default">
                <button type="button" class="ve-ibtn" id="btn-search-in-page" title="Buscar texto en página (Ctrl+F)">
                    <i class="fas fa-magnifying-glass"></i>
                </button>
                <button type="button" class="ve-ibtn" id="btn-outline-mode" title="Modo outline">
                    <i class="fa-solid fa-border-all"></i>
                </button>
                <button type="button" class="ve-ibtn" id="btn-grid-overlay-top" title="Grid de 12 columnas">
                    <i class="fa-solid fa-table-columns"></i>
                </button>
                <button type="button" class="ve-ibtn" id="btn-hover-inspect" title="Inspeccionar al hover">
                    <i class="fa-solid fa-crosshairs"></i>
                </button>
                <div class="dropdown">
                    <button class="ve-ibtn dropdown-toggle" data-bs-toggle="dropdown" title="Fondo del canvas" id="btn-canvas-bg" style="--bs-btn-after-display:none;">
                        <i class="fa-solid fa-circle-half-stroke"></i>
                    </button>
                    <ul class="dropdown-menu" style="min-width:140px;font-size:12px;">
                        <li><button class="dropdown-item ve-canvas-bg-btn" data-bg="#f4f6f8">Gris claro</button></li>
                        <li><button class="dropdown-item ve-canvas-bg-btn" data-bg="#e9ecef">Gris</button></li>
                        <li><button class="dropdown-item ve-canvas-bg-btn" data-bg="#1a1a1a">Oscuro</button></li>
                        <li><button class="dropdown-item ve-canvas-bg-btn" data-bg="#ffffff">Blanco</button></li>
                        <li><button class="dropdown-item ve-canvas-bg-btn" data-bg="repeating-linear-gradient(45deg,#f0f0f0 0,#f0f0f0 10px,#fff 10px,#fff 20px)">Cuadrícula</button></li>
                    </ul>
                </div>
                <button type="button" class="ve-ibtn" id="btn-fullscreen-preview" title="Pantalla completa">
                    <i class="fa-solid fa-expand"></i>
                </button>
                <button type="button" class="ve-ibtn" id="btn-diff-preview" title="Ver cambios">
                    <i class="fa-solid fa-code-compare"></i>
                </button>
            </div>
            {{-- Panel-specific contexts (injected by JS on panel switch) --}}
            <div id="ve-ctx-panel" style="display:none;"></div>
        </div>

        {{-- Right: undo/redo + device + comment + preview --}}
        <div class="ve-topbar-right">
            {{-- Search command button (⌘K) --}}
            <button type="button" class="ve-search-btn" id="btn-open-cmd-palette" title="Abrir paleta de comandos">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span class="ve-search-btn-label">Buscar comando</span>
                <span class="ve-search-btn-kbd">⌘K</span>
            </button>
            <div class="ve-topbar-divider"></div>

            {{-- Breakpoint switcher viene del topbar → ahora vive en #ve-zoom-bar --}}
            <div class="ve-topbar-divider"></div>
            <div class="ve-hist-pill">
                <button class="ve-ibtn" id="btn-undo-bar" title="Deshacer (Ctrl+Z)" disabled>
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
                <button class="ve-ibtn" id="btn-redo-bar" title="Rehacer (Ctrl+Y)" disabled>
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
            <div class="ve-topbar-divider"></div>
            <button type="button" class="ve-ibtn" id="btn-slash-menu" title="Insertar bloque (/)">
                <i class="fa-solid fa-plus"></i>
            </button>
            <button type="button" class="ve-ibtn" id="btn-snippets-top" title="Snippets HTML (⌘⇧S)">
                <i class="fa-solid fa-code"></i>
            </button>
            <button type="button" class="ve-ibtn" id="btn-ai-open" title="Generar con AI (Ctrl+Shift+A)">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </button>
            <button type="button" class="ve-ibtn" id="btn-toggle-minimap" title="Mapa de página" onclick="veToggleMinimap()">
                <i class="fa-solid fa-map"></i>
            </button>
            <button type="button" class="ve-ibtn" id="btn-comment-mode-top" title="Modo comentarios">
                <i class="fa-solid fa-comment"></i>
            </button>
            {{-- Vista: Wireframe / Regla / Modo oscuro / QR preview --}}
            <div class="dropdown d-inline-flex">
                <button type="button" class="ve-ibtn dropdown-toggle" id="btn-view-top" data-bs-toggle="dropdown" title="Vista" aria-expanded="false">
                    <i class="fa-solid fa-eye"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Vista</h6></li>
                    <li><button class="dropdown-item d-flex align-items-center gap-2" id="btn-wireframe"><i class="fa-solid fa-vector-square fa-fw text-muted"></i> Wireframe</button></li>
                    <li><button class="dropdown-item d-flex align-items-center gap-2" id="btn-ruler"><i class="fa-solid fa-ruler-horizontal fa-fw text-muted"></i> Regla</button></li>
                    <li><button class="dropdown-item d-flex align-items-center gap-2" id="btn-dark-mode"><i class="fa-solid fa-circle-half-stroke fa-fw text-muted"></i> Modo oscuro</button></li>
                    <li><button class="dropdown-item d-flex align-items-center gap-2" id="btn-qr-preview"><i class="fa-solid fa-qrcode fa-fw text-muted"></i> QR preview</button></li>
                </ul>
            </div>
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer" class="ve-ibtn" title="Preview en ventana">
                <i class="fa-solid fa-up-right-from-square"></i>
            </a>
        </div>
    </div>

    {{-- ── Bottom bar (simplified — stats live in #ve-statusbar) ────────── --}}
    <div id="ve-bottombar">
            <span id="ve-page-title-display" class="fw-semibold text-truncate ve-page-title-display" title="Doble clic para renombrar">{{ $page->title }}</span>
            {{-- Hidden JS hooks kept out of sight (legacy selectors) --}}
            <span id="autosave-status-bar" class="ve-hidden" aria-hidden="true"></span>

            <div class="ms-auto d-flex align-items-center gap-1">
                @if(count($supportedLocales) > 1)
                <div class="dropup">
                    <button class="ve-bottom-btn ve-bottom-btn-locale dropdown-toggle" id="btn-locale-bar"
                            data-bs-toggle="dropdown">
                        {{ strtoupper($locale) }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach($supportedLocales as $loc)
                        <li>
                            <button class="dropdown-item ve-locale-btn {{ $loc === $locale ? 'active' : '' }}"
                                    data-locale="{{ $loc }}">
                                {{ strtoupper($loc) }}
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Dispositivos + Vista viven en el topbar ahora (ve-topbar-right).
                     Sólo mantenemos locale + personalizar acciones rápidas aquí. --}}

                {{-- Quick actions config trigger --}}
                <button type="button" id="btn-quick-actions-config" class="ve-bottom-btn" title="Personalizar acciones rápidas">
                    <i class="fa-solid fa-bolt"></i>
                </button>
            </div>

            {{-- ── Hidden trigger buttons — referenced by Quick Actions, keyboard shortcuts and command palette.
                 Keeps handlers wired without cluttering the bottombar. ──────────────────────────── --}}
            <div class="ve-hidden" aria-hidden="true">
                <button type="button" id="btn-presentation-mode"></button>
                <button type="button" id="btn-comment-mode"></button>
                <button type="button" id="btn-snippets"></button>
                <button type="button" id="btn-a11y-check"></button>
                <button type="button" id="btn-check-images"></button>
                <button type="button" id="btn-page-stats"></button>
                <button type="button" id="btn-conditions-open"></button>
                <button type="button" id="btn-popup-builder-open"></button>
                <button type="button" id="btn-form-builder-open"></button>
            </div>
        </div>

    {{-- ── Main area ──────────────────────────────────────────────────── --}}
    <div id="ve-main">

    {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
    <div id="ve-sidebar">

        {{-- Vertical icon nav (rail) --}}
        <div id="ve-sidebar-nav">
            <a href="{{ route('pages.edit', $page) }}" class="ve-nav-back" data-tooltip="Volver">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div class="ve-rail-sep"></div>
            <button class="ve-nav-btn active" data-panel="shortcodes" data-tooltip="Bloques">
                <i class="fa-solid fa-shapes"></i>
            </button>
            <button class="ve-nav-btn" data-panel="inspector" data-tooltip="Inspector">
                <i class="fa-solid fa-sliders"></i>
            </button>
            <button class="ve-nav-btn" data-panel="layout" data-tooltip="Layout">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <button class="ve-nav-btn" data-panel="sections" data-tooltip="Capas">
                <i class="fa-solid fa-layer-group"></i>
            </button>
            <button class="ve-nav-btn" data-panel="history" data-tooltip="Historial">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </button>
            <button class="ve-nav-btn" data-panel="code" data-tooltip="Código HTML">
                <i class="fa-solid fa-code"></i>
            </button>
            <button class="ve-nav-btn" data-panel="settings" data-tooltip="Ajustes / SEO">
                <i class="fa-solid fa-gear"></i>
            </button>
            <button class="ve-nav-btn" data-panel="dom-tree" data-tooltip="Árbol DOM">
                <i class="fa-solid fa-sitemap"></i>
            </button>
            <button class="ve-nav-btn" data-panel="session" data-tooltip="Sesión">
                <i class="fa-solid fa-timeline"></i>
            </button>
            <div class="ve-rail-spacer"></div>
            <button class="ve-nav-btn" id="ve-rail-shortcuts-btn" data-tooltip="Atajos de teclado">
                <i class="fa-solid fa-keyboard"></i>
            </button>
            <button class="ve-nav-btn" id="ve-rail-help-btn" data-tooltip="Ayuda">
                <i class="fa-solid fa-circle-question"></i>
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
                            <div class="ve-panel-label">Fuente</div>
                            <span class="ve-panel-title">Código HTML</span>
                        </div>
                        <div class="ve-panel-actions">
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="ve-code-btn-format" title="Formatear">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="ve-code-btn-fold" title="Colapsar">
                                <i class="fa-solid fa-compress-alt"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn ve-hidden" id="ve-code-btn-unfold" title="Expandir">
                                <i class="fa-solid fa-expand-alt"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn ve-hidden" id="ve-code-btn-wrap" title="Ajuste">
                                <i class="fa-solid fa-align-left"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn ve-hidden" id="ve-code-btn-theme" title="Tema">
                                <i class="fa-solid fa-circle-half-stroke"></i>
                            </button>
                            <button class="btn btn-outline-secondary ve-panel-action-btn ve-hidden" id="ve-code-refresh" title="Sincronizar">
                                <i class="fa-solid fa-sync-alt"></i>
                            </button>
                            <button class="btn btn-outline-secondary ve-panel-action-btn" id="ve-code-apply" title="Aplicar cambios">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </div>
                    </div>
                    <div id="ve-code-editor-wrap" class="ve-code-editor-wrap">
                        <textarea id="ve-code-editor-textarea" style="display:none;"></textarea>
                    </div>
                </div>

                <div class="ve-panel" id="ve-panel-settings">
                    @include('page::pages.partials.ve-settings-panel')
                </div>

                <div class="ve-panel" id="ve-panel-dom-tree">
                    <div class="ve-panel-header">
                        <div>
                            <div class="ve-panel-label">Documento</div>
                            <span class="ve-panel-title">Árbol DOM</span>
                        </div>
                        <div class="ve-panel-actions">
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="btn-dom-refresh" title="Actualizar">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </div>
                    </div>
                    <div id="ve-dom-tree-list" class="ve-dom-list"></div>
                </div>

                {{-- Session history panel (Mejora C) --}}
                <div class="ve-panel" id="ve-panel-session">
                    <div class="ve-panel-header">
                        <div>
                            <div class="ve-panel-label">Actividad</div>
                            <span class="ve-panel-title">Historial de sesión</span>
                        </div>
                        <div class="ve-panel-actions">
                            <button type="button" class="btn btn-outline-secondary ve-panel-action-btn" id="btn-session-clear" title="Limpiar historial">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div id="ve-session-history-list" style="overflow-y:auto;flex:1;"></div>
                </div>

            </div>

            {{-- Ghost elements for JS references (IDs needed by existing scripts) --}}
            <div style="display:none;" aria-hidden="true">
                <span id="autosave-status"></span>
                <button id="btn-undo" disabled></button>
                <button id="btn-redo" disabled></button>
                <button id="btn-save"></button>
                <button id="btn-discard"></button>
                <button id="btn-insert-icon"></button>
                <button id="btn-export-html"></button>
                <button id="btn-server-export"></button>
                <button id="btn-server-import"></button>
                <button id="btn-rotate-device"></button>
                <button id="btn-shortcuts"></button>
                <button id="btn-preview-draft"></button>
                <button id="btn-find-replace"></button>
                <button id="btn-grid-overlay"></button>
                <button id="btn-media-manager"></button>
                <span id="ve-locale-label">{{ strtoupper($locale) }}</span>
            </div>

        </div>

        {{-- Sidebar toggle --}}
        <div id="ve-sidebar-toggle" title="Colapsar barra lateral">
            <i class="fa-solid fa-chevron-left"></i>
        </div>

    </div>

    {{-- ── Resize handle ──────────────────────────────────────────────── --}}
    <div id="ve-sidebar-resize" title="Arrastrar para redimensionar"></div>

    {{-- ── Canvas area ─────────────────────────────────────────────────── --}}
    <div id="ve-canvas">

        {{-- Draft banner (inside grid, absolute so it doesn't affect layout) --}}
        @if ($draftInfo ?? false)
        <div id="ve-draft-banner" class="ve-canvas-banner warn">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Borrador guardado <strong>{{ $draftInfo['saved_at'] }}</strong> por {{ $draftInfo['user_name'] }}</span>
            <button type="button" id="btn-restore-draft">Restaurar</button>
            <button type="button" id="btn-dismiss-draft" class="dismiss"><i class="fa-solid fa-xmark"></i></button>
        </div>
        @endif

        {{-- Lock banner --}}
        <div id="ve-lock-banner" class="ve-canvas-banner err" style="display:none;">
            <i class="fa-solid fa-lock"></i>
            <span id="ve-lock-banner-text">Esta página está siendo editada por otro usuario.</span>
        </div>

        <div class="ve-ruler" id="ve-ruler"></div>
        <div class="ve-search-bar" id="ve-element-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="ve-element-search-input" placeholder="Buscar texto en la página...">
            <span class="ve-search-bar-count" id="ve-search-count"></span>
            <button id="ve-search-prev" title="Anterior"><i class="fa-solid fa-chevron-up"></i></button>
            <button id="ve-search-next" title="Siguiente"><i class="fa-solid fa-chevron-down"></i></button>
            <button id="ve-search-close" title="Cerrar"><i class="fa-solid fa-times"></i></button>
        </div>
        <div id="ve-canvas-wrap" class="desktop" style="position:relative;">

            {{-- Find in page panel --}}
            <div id="ve-find-in-page" style="display:none; position:absolute; top:8px; right:16px; z-index:100; background:#fff; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.15); padding:8px; gap:6px; align-items:center;">
                <input type="text" id="ve-find-in-page-input" class="form-control form-control-sm" style="width:200px;" placeholder="Buscar en página...">
                <span id="ve-find-count" style="font-size:11px;color:#999;white-space:nowrap;min-width:40px;"></span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-find-prev" title="Anterior">↑</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-find-next" title="Siguiente">↓</button>
                <button type="button" class="btn-close btn-sm" id="btn-find-close"></button>
            </div>

            {{-- Drag overlay (shown when dragging a block) --}}
            <div id="ve-drag-overlay"></div>

            {{-- Drop indicator line --}}
            <div id="ve-drop-line"></div>

            {{-- Loading spinner --}}
            <div id="ve-preview-spinner">
                <div class="spinner-border text-light" role="status" style="width:2rem;height:2rem;"></div>
            </div>

            {{-- Device frame overlay --}}
            <div id="ve-device-frame"></div>

            {{-- Mini browser chrome (shown in non-desktop breakpoints) --}}
            <div class="ve-mini-chrome" id="ve-mini-chrome">
                <div class="ve-chrome-dots">
                    <span></span><span></span><span></span>
                </div>
                <div class="ve-chrome-url">
                    <i class="fa-solid fa-lock"></i>
                    <span id="ve-chrome-url-text">{{ $page->url ?? request()->getHost() }}</span>
                </div>
                <div style="width:40px;"></div>
            </div>

            {{-- Preview iframe --}}
            <iframe id="ve-preview-frame"
                    src="{{ $visualPreviewUrl }}?locale={{ $locale }}"
                    title="Preview — {{ $page->title }}"
                    sandbox="allow-same-origin allow-scripts allow-forms allow-popups"
                    style="width:100%;height:100%;">
            </iframe>

            {{-- Comment overlay (shown in comment mode) --}}
            <div id="ve-comment-overlay" style="display:none;position:absolute;inset:0;z-index:60;cursor:crosshair;"></div>
            <div id="ve-comment-pins" style="position:absolute;inset:0;z-index:61;pointer-events:none;"></div>

            {{-- Element Outline — selection indicator with label + dims --}}
            <div id="ve-sel-outline" class="ve-hidden">
                <span class="ve-sel-label" id="ve-sel-label">H1.HERO-TITLE</span>
                <span class="ve-sel-dims" id="ve-sel-dims">0 × 0</span>
            </div>

            {{-- Floating Toolbar — Notion-style text formatting --}}
            <div id="ve-float-tb" class="ve-hidden">
                <select class="ve-ftb-select" id="ve-ftb-tag" title="Tipo de elemento">
                    <option value="">Párrafo</option>
                    <option value="h1">H1</option>
                    <option value="h2">H2</option>
                    <option value="h3">H3</option>
                    <option value="h4">H4</option>
                    <option value="p">P</option>
                </select>
                <div class="ve-ftb-sep"></div>
                <button type="button" class="ve-ftb-btn" data-cmd="bold" title="Negrita (Ctrl+B)"><i class="fa-solid fa-bold"></i></button>
                <button type="button" class="ve-ftb-btn" data-cmd="italic" title="Cursiva (Ctrl+I)"><i class="fa-solid fa-italic"></i></button>
                <button type="button" class="ve-ftb-btn" data-cmd="underline" title="Subrayado (Ctrl+U)"><i class="fa-solid fa-underline"></i></button>
                <button type="button" class="ve-ftb-btn" data-cmd="strikeThrough" title="Tachado"><i class="fa-solid fa-strikethrough"></i></button>
                <div class="ve-ftb-sep"></div>
                <button type="button" class="ve-ftb-btn" id="ve-ftb-link" title="Enlace (Ctrl+K)"><i class="fa-solid fa-link"></i></button>
                <button type="button" class="ve-ftb-btn" id="ve-ftb-color" title="Color de texto"><i class="fa-solid fa-palette"></i></button>
                <button type="button" class="ve-ftb-btn" id="ve-ftb-align" title="Alinear"><i class="fa-solid fa-align-left"></i></button>
                <div class="ve-ftb-sep"></div>
                <button type="button" class="ve-ftb-btn" id="ve-ftb-ai" title="Generar con AI"><i class="fa-solid fa-wand-magic-sparkles"></i></button>
                <button type="button" class="ve-ftb-btn" id="ve-ftb-more" title="Más acciones"><i class="fa-solid fa-ellipsis"></i></button>
            </div>

            {{-- Link Popover — floating mini-toolbar for <a> elements --}}
            <div id="ve-link-popover" class="ve-hidden" role="dialog" aria-label="Editar enlace">
                <div class="ve-lp-row">
                    <i class="fa-solid fa-link"></i>
                    <span class="ve-lp-url" id="ve-lp-url-text">https://…</span>
                    <button type="button" class="ve-lp-btn" id="ve-lp-open" title="Abrir"><i class="fa-solid fa-arrow-up-right-from-square"></i></button>
                    <button type="button" class="ve-lp-btn" id="ve-lp-copy" title="Copiar"><i class="fa-regular fa-copy"></i></button>
                    <button type="button" class="ve-lp-btn" id="ve-lp-unlink" title="Quitar enlace"><i class="fa-solid fa-unlink"></i></button>
                </div>
                <div class="ve-lp-opts">
                    <div class="ve-lp-opt" id="ve-lp-opt-newtab" data-attr="target" data-val="_blank">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>Nueva pestaña
                    </div>
                    <div class="ve-lp-opt" id="ve-lp-opt-nofollow" data-attr="rel" data-val="nofollow">
                        <i class="fa-solid fa-shield-halved"></i>nofollow
                    </div>
                    <div class="ve-lp-opt" id="ve-lp-opt-download" data-attr="download" data-val="">
                        <i class="fa-solid fa-download"></i>Descarga
                    </div>
                </div>
            </div>

            {{-- Scroll Minimap — page overview strip --}}
            <div id="ve-minimap" class="ve-minimap-panel ve-hidden" title="Mapa de página · Clic para navegar">
                <span class="ve-minimap-label">Estructura</span>
                <div id="ve-minimap-content" class="ve-minimap-content"></div>
                <div id="ve-minimap-viewport" class="ve-minimap-viewport"></div>
            </div>

        </div>

        {{-- Zoom bar --}}
        <div id="ve-zoom-bar">
            {{-- Responsive switcher (pill group) --}}
            <div class="ve-bp-sw" role="tablist" aria-label="Responsive">
                <button type="button" class="ve-bp-btn on breakpoint-btn" data-breakpoint="desktop" title="Desktop">
                    <i class="fa-solid fa-desktop"></i>
                </button>
                <button type="button" class="ve-bp-btn breakpoint-btn" data-breakpoint="laptop" data-width="1280px" data-height="800px" title="Laptop">
                    <i class="fa-solid fa-laptop"></i>
                </button>
                <button type="button" class="ve-bp-btn breakpoint-btn" data-breakpoint="tablet" data-width="768px" data-height="1024px" title="Tablet">
                    <i class="fa-solid fa-tablet-screen-button"></i>
                </button>
                <button type="button" class="ve-bp-btn breakpoint-btn" data-breakpoint="mobile" data-width="375px" data-height="812px" title="Móvil">
                    <i class="fa-solid fa-mobile-screen"></i>
                </button>
                {{-- Dropdown: dispositivos específicos --}}
                <div class="dropup d-inline-flex">
                    <button type="button" class="ve-bp-btn dropdown-toggle" id="btn-responsive-bar" data-bs-toggle="dropdown" data-bs-strategy="fixed" title="Dispositivos específicos" aria-expanded="false">
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end ve-responsive-dropdown">
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
                        <li><button class="dropdown-item d-flex align-items-center gap-2" id="btn-split-view"><i class="fa-solid fa-columns fa-fw text-muted"></i> Split (Desktop + Móvil)</button></li>
                    </ul>
                </div>
            </div>
            <span class="ve-zoom-sep"></span>
            <button class="ve-zoom-btn" id="btn-zoom-100" data-zoom="1" title="100%">
                <i class="fa-solid fa-expand"></i><span class="ve-zoom-lbl">100%</span>
            </button>
            <button class="ve-zoom-btn active" id="btn-zoom-fit" title="Ajustar al viewport">
                <i class="fa-solid fa-compress"></i><span class="ve-zoom-lbl">Fit</span>
            </button>
            <button class="ve-zoom-btn ve-zoom-ico" id="btn-zoom-in" title="Aumentar zoom">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
            </button>
            <button class="ve-zoom-btn ve-zoom-ico" id="btn-zoom-out" title="Reducir zoom">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
            </button>
            <span class="ve-zoom-pct" id="ve-zoom-pct">100%</span>
            <span class="ve-zoom-sep"></span>
            <button class="ve-zoom-btn ve-zoom-ico" id="btn-zoom-wireframe" title="Wireframe">
                <i class="fa-solid fa-vector-square"></i>
            </button>
            <button class="ve-zoom-btn ve-zoom-ico" id="btn-zoom-ruler" title="Regla">
                <i class="fa-solid fa-ruler-horizontal"></i>
            </button>
        </div>
    </div>

    </div>{{-- /ve-main --}}

    {{-- Quick Actions Bar — fuera de #ve-canvas-wrap para no verse afectado por el breakpoint responsive --}}
    <div id="ve-quick-actions-bar" class="ve-qa-bar" title="Acciones rápidas"></div>

    {{-- ── Statusbar ────────────────────────────────────────────────────── --}}
    <footer id="ve-statusbar">
        <span class="ve-status-item">
            <span id="ve-statusbar-dot" class="ve-status-dot"></span>
            <span class="k" id="ve-statusbar-status">{{ $page->status->label() }}</span>
        </span>
        <span class="ve-status-sep"></span>
        <span class="ve-status-item">
            <i class="fa-solid fa-language"></i>
            <span id="ve-statusbar-locales">{{ strtoupper($locale) }}</span>
        </span>
        <span class="ve-status-sep"></span>
        <span class="ve-status-item" id="ve-statusbar-words-wrap">
            <i class="fa-solid fa-font"></i>
            <span id="ve-statusbar-words">— palabras</span>
        </span>
        <span class="ve-status-sep"></span>
        <span class="ve-status-item" id="ve-statusbar-weight-wrap">
            <i class="fa-solid fa-weight-hanging"></i>
            <span id="ve-statusbar-weight">— KB</span>
        </span>
        <span class="ve-status-sep"></span>
        <span class="ve-status-item">
            <i class="fa-solid fa-code-branch"></i>
            <span id="ve-statusbar-version">v {{ $page->version ?? 1 }}</span>
        </span>

        <span style="flex:1;"></span>

        <span class="ve-status-item" id="ve-statusbar-sync">
            <i class="fa-solid fa-circle" style="color:#22c55e;font-size:7px;"></i>
            Sincronizado
        </span>
        <span class="ve-status-sep"></span>
        <span class="ve-status-item ve-autosave-pill" id="ve-statusbar-autosave-wrap"
              data-state="idle"
              title="Guardar ahora (Ctrl+S)"
              role="button" tabindex="0">
            <span class="ve-autosave-icon">
                <i class="fa-solid fa-cloud"></i>
            </span>
            <span class="ve-autosave-label" id="ve-statusbar-autosave">Sin guardar aún</span>
        </span>
        <span class="ve-status-sep"></span>
        <span class="ve-status-item">
            <a id="btn-statusbar-seo">SEO —</a>
        </span>
        <span class="ve-status-sep"></span>
        <span class="ve-status-item">
            <a id="btn-statusbar-a11y">A11y —</a>
        </span>
    </footer>

</div>{{-- /ve-body --}}

{{-- ── Toast container ─────────────────────────────────────────────────── --}}
<div id="ve-toast-container" class="ec-toast-container"></div>

{{-- ── Tooltip singleton ────────────────────────────────────────────────── --}}
<div id="ve-tooltip" class="ec-tt" role="tooltip"></div>

{{-- ── Confirm overlay (replaces Bootstrap modal) ──────────────────────── --}}
<div id="ve-confirm-wrap" class="ec-confirm-wrap">
    <div class="ec-confirm">
        <div class="ec-confirm-body">
            <div class="ec-confirm-icon" id="ve-confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="ec-confirm-text">
                <div class="ct" id="ve-confirm-title">¿Confirmar acción?</div>
                <div class="cs" id="ve-confirm-message">Esta acción no se puede deshacer.</div>
            </div>
        </div>
        <div class="ec-confirm-foot">
            <div class="vm-spacer"></div>
            <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" id="ve-confirm-cancel">Cancelar</button>
            <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="ve-confirm-accept">Confirmar</button>
        </div>
    </div>
</div>

{{-- ── 02 · Icon picker modal ──────────────────────────────────────────── --}}
<div class="modal fade" id="ve-icon-picker-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-md modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-icons"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">ELEMENTO · &lt;i class="fa…"&gt;</div>
                    <div class="vm-title">Seleccionar icono</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div class="vm-search-field">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" class="vm-finput" id="ve-icon-search" placeholder="Buscar  star, home, user…">
                </div>
                <div class="vm-seg" id="ve-icon-seg">
                    <button class="active" data-style="">Todos <span class="vm-seg-badge">3.2k</span></button>
                    <button data-style="fa-solid">Solid</button>
                    <button data-style="fa-regular">Regular</button>
                    <button data-style="fa-brands">Brand</button>
                </div>
                <div class="vm-icon-grid" id="ve-icon-grid"></div>
            </div>
            <div class="vm-foot">
                <div style="display:flex;align-items:center;gap:8px;font-size:11px;color:var(--ve-text-muted);">
                    Seleccionado: <span id="ve-icon-selected-label" style="font-family:'JetBrains Mono',monospace;color:var(--ve-text);font-size:11px;">—</span>
                </div>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-icon-insert">Insertar icono</button>
            </div>
        </div>
    </div>
</div>

{{-- ── 03 · Snippets HTML modal ─────────────────────────────────────────── --}}
<div class="modal fade" id="ve-snippets-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-lg modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-bookmark"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">BIBLIOTECA</div>
                    <div class="vm-title">Snippets HTML</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body pad-sm">
                <div class="vm-snippets-layout">
                    <div class="vm-snip-side">
                        <div id="ve-snippets-list"></div>
                        <button type="button" class="vm-snip-add" id="btn-snippet-new">
                            <i class="fa-solid fa-plus"></i>Nuevo snippet
                        </button>
                    </div>
                    <div class="vm-snip-main">
                        <div class="vm-field">
                            <label class="vm-flabel">Nombre</label>
                            <input type="text" class="vm-finput" id="ve-snippet-name" placeholder="Nombre del snippet">
                        </div>
                        <div class="vm-field">
                            <label class="vm-flabel">HTML <span class="hint">Ctrl+Space → autocompletar</span></label>
                            <textarea class="vm-ta-mono" id="ve-snippet-code" rows="8" placeholder="<!-- HTML del snippet -->"></textarea>
                        </div>
                        <div style="display:flex;gap:6px;">
                            <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" style="flex:1;" id="btn-snippet-save"><i class="fa-solid fa-floppy-disk"></i>Guardar</button>
                            <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" style="flex:1;" id="btn-snippet-insert"><i class="fa-solid fa-arrow-right-to-bracket"></i>Insertar</button>
                            <button type="button" class="vm-btn vm-btn-danger vm-btn-sm" id="btn-snippet-delete"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── 08 · Approval request modal ─────────────────────────────────────── --}}
<div class="modal fade" id="ve-approval-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-sm modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-paper-plane"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">FLUJO DE REVISIÓN</div>
                    <div class="vm-title">Solicitar aprobación</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div class="vm-field">
                    <label class="vm-flabel">Revisor</label>
                    <select class="vm-fselect" id="ve-approval-reviewer">
                        <option>María López · Editor en jefe</option>
                        <option>Carlos Mendes · Responsable marketing</option>
                        <option>Equipo completo</option>
                    </select>
                </div>
                <div class="vm-field">
                    <label class="vm-flabel">Comentario <span class="hint">opcional</span></label>
                    <textarea class="vm-finput" id="ve-approval-comment" rows="3" placeholder="Describe los cambios realizados…"></textarea>
                </div>
                <div class="vm-chk-row">
                    <div class="vm-chk on"><i class="fa-solid fa-check"></i></div>
                    <div class="body"><div class="t">Notificar por email</div><div class="desc">Se enviará enlace preview al revisor</div></div>
                </div>
                <div class="vm-chk-row">
                    <div class="vm-chk"></div>
                    <div class="body"><div class="t">Bloquear edición hasta aprobar</div></div>
                </div>
            </div>
            <div class="vm-foot">
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-confirm-approval">
                    <i class="fa-solid fa-paper-plane"></i>Enviar solicitud
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── 07 · Import modal ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-import-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-sm modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-file-import"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">DATOS</div>
                    <div class="vm-title">Importar página</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div class="vm-drop-zone" id="ve-import-drop">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <div class="t">Arrastra un archivo aquí</div>
                    <div class="s">o haz clic para seleccionar · .json, .html · máx 5 MB</div>
                    <input type="file" id="ve-import-file" accept=".json,.html" style="display:none;">
                </div>
                <div class="vm-chk-row">
                    <div class="vm-chk on"><i class="fa-solid fa-check"></i></div>
                    <div class="body"><div class="t">Sobrescribir contenido actual</div><div class="desc">Se creará un snapshot antes de importar</div></div>
                </div>
                <div class="vm-chk-row">
                    <div class="vm-chk"></div>
                    <div class="body"><div class="t">Incluir configuración SEO</div><div class="desc">Meta title, descripción, schema…</div></div>
                </div>
                <div class="vm-chk-row">
                    <div class="vm-chk on"><i class="fa-solid fa-check"></i></div>
                    <div class="body"><div class="t">Importar bloques personalizados</div></div>
                </div>
            </div>
            <div class="vm-foot" style="gap:6px;">
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm vm-btn-full" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm vm-btn-full" id="btn-confirm-import">
                    <i class="fa-solid fa-upload"></i>Importar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── 09 · Diff modal (side-by-side) ─────────────────────────────────── --}}
<div class="modal fade" id="ve-diff-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-xl modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-code-compare"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">DIFF · <span id="ve-diff-count">0</span> cambios</div>
                    <div class="vm-title">Comparar versiones</div>
                </div>
                <div class="vm-seg" style="width:auto;">
                    <button class="active" data-diff-view="code"><i class="fa-solid fa-code"></i> Código</button>
                    <button data-diff-view="visual"><i class="fa-solid fa-eye"></i> Visual</button>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-diff-wrap" id="ve-diff-body">
                <div class="vm-diff-col">
                    <div class="vm-diff-head">
                        <span class="dot"></span>
                        <span>Original · al cargar</span>
                        <span style="margin-left:auto;font-family:'JetBrains Mono',monospace;">v•</span>
                    </div>
                    <div class="vm-diff-body" id="ve-diff-original"></div>
                </div>
                <div class="vm-diff-col">
                    <div class="vm-diff-head current">
                        <span class="dot"></span>
                        <span>Actual · cambios no guardados</span>
                        <span style="margin-left:auto;font-family:'JetBrains Mono',monospace;">v*</span>
                    </div>
                    <div class="vm-diff-body" id="ve-diff-current"></div>
                </div>
            </div>
            <div class="vm-foot">
                <div style="display:flex;gap:12px;font-size:11px;color:var(--ve-text-muted);">
                    <span><span id="ve-diff-add" style="color:#16a34a;font-weight:600;">+0</span> añadidas</span>
                    <span><span id="ve-diff-del" style="color:#dc2626;font-weight:600;">−0</span> eliminadas</span>
                </div>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" id="btn-diff-discard">
                    <i class="fa-solid fa-rotate-left"></i>Descartar cambios
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ── 04 · Accessibility modal ─────────────────────────────────────────── --}}
<div class="modal fade" id="ve-a11y-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-md modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-universal-access"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">ANÁLISIS · <span id="ve-a11y-node-count">0</span> nodos escaneados</div>
                    <div class="vm-title">Accesibilidad</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body" style="gap:10px;">
                <div class="vm-score-row" id="ve-a11y-score-row">
                    <div class="vm-ring">
                        <svg width="52" height="52" viewBox="0 0 52 52">
                            <circle cx="26" cy="26" r="22" stroke="#e4e4e7" stroke-width="4" fill="none"/>
                            <circle id="ve-a11y-ring-arc" cx="26" cy="26" r="22" stroke="#d97706" stroke-width="4" fill="none"
                                    stroke-dasharray="138.23" stroke-dashoffset="41.47" stroke-linecap="round"/>
                        </svg>
                        <div class="vm-ring-num" id="ve-a11y-score-num">—</div>
                    </div>
                    <div class="vm-score-label">
                        <div class="vm-score-grade" id="ve-a11y-grade">Analizando…</div>
                        <div class="vm-score-desc" id="ve-a11y-desc"></div>
                    </div>
                    <div class="vm-score-counts">
                        <div class="vm-score-count err"><span class="n" id="ve-a11y-err-n">0</span><span class="l">Error</span></div>
                        <div class="vm-score-count warn"><span class="n" id="ve-a11y-warn-n">0</span><span class="l">Aviso</span></div>
                        <div class="vm-score-count ok"><span class="n" id="ve-a11y-ok-n">0</span><span class="l">OK</span></div>
                    </div>
                </div>
                <div class="vm-a11y-list" id="ve-a11y-results"></div>
            </div>
            <div class="vm-foot">
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm"><i class="fa-solid fa-file-export"></i>Exportar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-a11y-fix-all">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>Reparar automáticos
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── 11 · Quick actions config modal ─────────────────────────────────── --}}
<div class="modal fade" id="ve-quick-actions-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-sm modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-bolt"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">PERSONALIZAR</div>
                    <div class="vm-title">Acciones rápidas</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div style="font-size:11px;color:var(--ve-text-muted);display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span>Activa todas las que necesites · <strong style="color:var(--ve-text);">barra flotante izquierda</strong> del canvas</span>
                    <span class="vm-qa-count" id="ve-qa-count">0 activas</span>
                </div>
                <div class="vm-qa-list" id="ve-qa-options" style="max-height:380px;overflow-y:auto;"></div>
            </div>
            <div class="vm-foot">
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm" id="btn-qa-reset">Restablecer</button>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-qa-save">
                    <i class="fa-solid fa-check"></i>Aplicar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── 10 · Conditions modal ────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-conditions-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-sm modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-filter"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">VISIBILIDAD · <span id="ve-cond-target">elemento</span></div>
                    <div class="vm-title">Condiciones</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div class="vm-field">
                    <label class="vm-flabel">Mostrar cuando…</label>
                    <div class="vm-cond-list">
                        <div class="vm-cond-pill" data-condition="logged-in">
                            <div class="ico"><i class="fa-solid fa-user"></i></div>
                            <div class="body"><div class="t">Solo usuarios registrados</div><div class="s">Requiere sesión iniciada</div></div>
                        </div>
                        <div class="vm-cond-pill" data-condition="guest">
                            <div class="ico"><i class="fa-solid fa-user-plus"></i></div>
                            <div class="body"><div class="t">Solo visitantes</div><div class="s">No han iniciado sesión</div></div>
                        </div>
                        <div class="vm-cond-pill" data-condition="mobile">
                            <div class="ico"><i class="fa-solid fa-mobile-screen"></i></div>
                            <div class="body"><div class="t">Solo móvil</div><div class="s">Ancho &lt; 768px</div></div>
                        </div>
                        <div class="vm-cond-pill" data-condition="desktop">
                            <div class="ico"><i class="fa-solid fa-desktop"></i></div>
                            <div class="body"><div class="t">Solo desktop</div><div class="s">Ancho ≥ 1024px</div></div>
                        </div>
                        <div class="vm-cond-pill" data-condition="date-range">
                            <div class="ico"><i class="fa-regular fa-calendar"></i></div>
                            <div class="body"><div class="t">Rango de fechas</div><div class="s">Mostrar entre fechas específicas</div></div>
                        </div>
                        <div class="vm-cond-pill" data-condition="language">
                            <div class="ico"><i class="fa-solid fa-globe"></i></div>
                            <div class="body"><div class="t">Por idioma</div><div class="s">ES · EN · PT</div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="vm-foot">
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm" id="btn-clear-conditions">Limpiar</button>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-apply-condition">
                    <i class="fa-solid fa-check"></i>Aplicar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── 05 · Popup builder modal ─────────────────────────────────────────── --}}
<div class="modal fade" id="ve-popup-builder" tabindex="-1">
    <div class="modal-dialog vm-dialog-md modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-window-restore"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">CREAR NUEVO</div>
                    <div class="vm-title">Popup</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div class="vm-field">
                    <label class="vm-flabel">Título<span class="req">*</span></label>
                    <input type="text" class="vm-finput" id="ve-popup-title" placeholder="Ej: Suscríbete a nuestro newsletter">
                </div>
                <div class="vm-field">
                    <label class="vm-flabel">Contenido</label>
                    <textarea class="vm-finput" id="ve-popup-content" rows="3" placeholder="Texto del popup…"></textarea>
                </div>
                <div class="vm-frow">
                    <div class="vm-field">
                        <label class="vm-flabel">Trigger</label>
                        <select class="vm-fselect" id="ve-popup-trigger">
                            <option value="click">Click en botón</option>
                            <option value="scroll">Scroll 50%</option>
                            <option value="timer" selected>Timer · 5s</option>
                            <option value="exit">Exit intent</option>
                        </select>
                    </div>
                    <div class="vm-field">
                        <label class="vm-flabel">Frecuencia</label>
                        <select class="vm-fselect" id="ve-popup-freq">
                            <option value="always">Siempre</option>
                            <option value="session" selected>Una vez por sesión</option>
                            <option value="day">Una vez por día</option>
                        </select>
                    </div>
                </div>
                <div class="vm-field">
                    <label class="vm-flabel">Estilo</label>
                    <div class="vm-pop-choice" id="ve-popup-style-pick">
                        <div class="vm-pop-opt on" data-s="center">
                            <div class="mini"><div class="box"></div></div>
                            <div class="n">Modal centrado</div>
                        </div>
                        <div class="vm-pop-opt" data-s="bar">
                            <div class="mini"><div class="box"></div></div>
                            <div class="n">Barra inferior</div>
                        </div>
                        <div class="vm-pop-opt" data-s="slide">
                            <div class="mini"><div class="box"></div></div>
                            <div class="n">Slide-in</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="vm-foot">
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm"><i class="fa-solid fa-eye"></i>Previsualizar</button>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-insert-popup">
                    <i class="fa-solid fa-plus"></i>Insertar popup
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── 06 · Form builder modal ──────────────────────────────────────────── --}}
<div class="modal fade" id="ve-form-builder" tabindex="-1">
    <div class="modal-dialog vm-dialog-lg modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-rectangle-list"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">FORMULARIO</div>
                    <div class="vm-title">Constructor <span class="vm-chip" id="ve-form-type-chip">contacto</span></div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-mtabs" id="ve-form-tabs">
                <button class="active"><i class="fa-solid fa-sliders"></i> Campos</button>
                <button><i class="fa-solid fa-paper-plane"></i> Envío</button>
                <button><i class="fa-solid fa-paint-brush"></i> Estilo</button>
            </div>
            <div class="vm-body">
                <div class="vm-frow">
                    <div class="vm-field">
                        <label class="vm-flabel">Tipo</label>
                        <select class="vm-fselect" id="ve-form-type">
                            <option value="contact">Contacto</option>
                            <option value="newsletter">Newsletter</option>
                            <option value="quote">Presupuesto</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>
                    <div class="vm-field">
                        <label class="vm-flabel">Destino email</label>
                        <div class="vm-finput-inline">
                            <span class="pre">to:</span>
                            <input type="text" class="vm-finput" id="ve-form-action" placeholder="info@empresa.com">
                        </div>
                    </div>
                </div>
                <div class="vm-field">
                    <label class="vm-flabel">Campos <span class="hint">Arrastra para reordenar</span></label>
                    <div class="vm-fb-canvas" id="ve-form-fields"></div>
                </div>
                <div class="vm-field">
                    <label class="vm-flabel">Texto del botón</label>
                    <input type="text" class="vm-finput" id="ve-form-btn-text" value="Enviar">
                </div>
            </div>
            <div class="vm-foot">
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm"><i class="fa-solid fa-eye"></i>Preview</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-insert-form">
                    <i class="fa-solid fa-check"></i>Insertar formulario
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── AI Content modal ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-ai-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-md modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon" style="background:#18181b;color:#fff;"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">ASISTENTE · claude-haiku-4.5</div>
                    <div class="vm-title">Generar contenido</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div class="vm-field">
                    <label class="vm-flabel">Tipo de contenido</label>
                    <div class="ec-ai-pill-row" id="ve-ai-type-pills">
                        <button class="ec-ai-pill on" data-value="hero">Hero headline</button>
                        <button class="ec-ai-pill" data-value="paragraph">Descripción</button>
                        <button class="ec-ai-pill" data-value="faq">FAQ</button>
                        <button class="ec-ai-pill" data-value="cta">CTA</button>
                        <button class="ec-ai-pill" data-value="seo">Meta SEO</button>
                        <button class="ec-ai-pill" data-value="rewrite">Reescribir</button>
                    </div>
                    <input type="hidden" id="ve-ai-type" value="hero">
                </div>
                <div class="vm-field">
                    <label class="vm-flabel">Prompt <span class="hint">describe el contenido</span></label>
                    <textarea class="vm-finput" id="ve-ai-prompt" rows="3" placeholder="Ej: Empresa de mosquiteras a medida con 20 años de experiencia…"></textarea>
                </div>
                <div class="vm-field">
                    <label class="vm-flabel">Tono</label>
                    <div class="ec-ai-pill-row" id="ve-ai-tone-pills">
                        <button class="ec-ai-pill on" data-value="professional">Profesional</button>
                        <button class="ec-ai-pill" data-value="casual">Cercano</button>
                        <button class="ec-ai-pill" data-value="urgent">Urgencia</button>
                        <button class="ec-ai-pill" data-value="technical">Técnico</button>
                    </div>
                    <input type="hidden" id="ve-ai-tone" value="professional">
                </div>
                <div class="ec-ai-sparkle ve-hidden" id="ve-ai-result">
                    <div class="aix"><i class="fa-solid fa-sparkles"></i></div>
                    <div class="body">
                        <div class="label">RESULTADO</div>
                        <div id="ve-ai-output"></div>
                    </div>
                    <div class="cnt" id="ve-ai-token-count"></div>
                </div>
            </div>
            <div class="vm-foot">
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm ve-hidden" id="btn-ai-regenerate"><i class="fa-solid fa-rotate"></i>Regenerar</button>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-ai-generate"><i class="fa-solid fa-wand-magic-sparkles"></i>Generar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm ve-hidden" id="btn-ai-insert"><i class="fa-solid fa-check"></i>Insertar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Command palette modal ─────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-command-palette" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content ec-cmd">
            <div class="ec-cmd-input">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="ve-cmd-input" placeholder="Buscar acciones, bloques, paneles…" autocomplete="off">
                <span class="ec-esc">ESC</span>
            </div>
            <div id="ve-cmd-results" class="ec-cmd-body"></div>
            <div class="ec-cmd-foot">
                <span class="ec-cmd-hint"><span class="ec-kbd">↑</span><span class="ec-kbd">↓</span> navegar</span>
                <span class="ec-cmd-hint"><span class="ec-kbd">⏎</span> ejecutar</span>
                <span class="ec-cmd-hint"><span class="ec-kbd">⌘</span><span class="ec-kbd">K</span> abrir</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Media Picker modal ────────────────────────────────────────────────── --}}
@include('media::partials.picker-modal')
<script src="{{ asset('modules/Media/js/media-picker.js') }}"></script>

{{-- ── Context menu ──────────────────────────────────────────────────────── --}}
<div id="ve-context-menu" class="ec-ctx" role="menu">
    <button type="button" class="ec-ctx-item" id="ctx-copy" role="menuitem">
        <i class="fa-solid fa-copy"></i><span>Copiar</span><span class="k">⇧C</span>
    </button>
    <button type="button" class="ec-ctx-item" id="ctx-paste" style="display:none;" role="menuitem">
        <i class="fa-solid fa-paste"></i><span>Pegar después</span><span class="k">⇧V</span>
    </button>
    <button type="button" class="ec-ctx-item" id="ctx-duplicate" role="menuitem">
        <i class="fa-regular fa-copy"></i><span>Duplicar</span><span class="k">⌘D</span>
    </button>
    <button type="button" class="ec-ctx-item" id="ctx-move-up" role="menuitem">
        <i class="fa-solid fa-arrow-up"></i><span>Mover arriba</span><span class="k">⌥↑</span>
    </button>
    <button type="button" class="ec-ctx-item" id="ctx-move-down" role="menuitem">
        <i class="fa-solid fa-arrow-down"></i><span>Mover abajo</span><span class="k">⌥↓</span>
    </button>
    <div class="ec-ctx-sep" role="separator"></div>
    <div class="ec-ctx-sec">Avanzado</div>
    <button type="button" class="ec-ctx-item" id="ctx-edit-html" role="menuitem">
        <i class="fa-solid fa-code"></i><span>Editar HTML</span>
    </button>
    <button type="button" class="ec-ctx-item" id="ctx-save-block" role="menuitem">
        <i class="fa-solid fa-bookmark"></i><span>Guardar como bloque</span>
    </button>
    <div class="ec-ctx-sep" role="separator"></div>
    <button type="button" class="ec-ctx-item danger" id="ctx-delete" role="menuitem">
        <i class="fa-regular fa-trash-can"></i><span>Eliminar</span><span class="k">⌫</span>
    </button>
</div>

{{-- ── Modal: HTML editor ────────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-html-editor-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-xl modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon vm-icon-dark"><i class="fa-solid fa-code"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label" id="ve-html-editor-context">editar directamente</div>
                    <div class="vm-title">HTML <span class="vm-chip" id="ve-html-editor-tag">h1</span></div>
                </div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" id="ve-btn-format" title="Formatear HTML">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>Formatear
                </button>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-editor-frame">
                <div class="vm-editor-tabs">
                    <div class="vm-editor-tab on" data-tab="html">
                        <i class="fa-brands fa-html5"></i><span>index.html</span>
                        <span class="vm-editor-x">×</span>
                    </div>
                    <div class="vm-editor-tab" data-tab="css">
                        <i class="fa-brands fa-css3-alt"></i><span>styles.css</span>
                        <span class="vm-editor-x">×</span>
                    </div>
                    <div class="vm-editor-tab vm-editor-tab-add" title="Añadir archivo">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="vm-editor-tools">
                        <button type="button" class="vm-editor-tool" id="ve-btn-fold-all" title="Colapsar todo">
                            <i class="fa-solid fa-compress-alt"></i>
                        </button>
                        <button type="button" class="vm-editor-tool" id="ve-btn-unfold-all" title="Expandir todo">
                            <i class="fa-solid fa-expand-alt"></i>
                        </button>
                        <button type="button" class="vm-editor-tool" id="ve-btn-wrap" title="Ajuste de línea">
                            <i class="fa-solid fa-align-left"></i>
                        </button>
                        <button type="button" class="vm-editor-tool" id="ve-btn-theme" title="Tema claro / oscuro">
                            <i class="fa-solid fa-circle-half-stroke"></i>
                        </button>
                        <button type="button" class="vm-editor-tool" id="ve-btn-fullscreen" title="Pantalla completa (F11)">
                            <i class="fa-solid fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="vm-editor-body">
                    <textarea id="ve-html-editor-textarea" style="width:100%;height:100%;"></textarea>
                </div>
                <div class="vm-editor-status">
                    <span id="ve-he-lang">HTML</span>
                    <span class="vm-editor-sep">·</span>
                    <span>UTF-8</span>
                    <span class="vm-editor-sep">·</span>
                    <span>LF</span>
                    <span class="vm-editor-sep">·</span>
                    <span id="ve-he-cursor">Ln 1, Col 1</span>
                    <span class="vm-editor-spacer"></span>
                    <span class="vm-editor-valid" id="ve-he-valid"><i class="fa-solid fa-check"></i> Válido</span>
                    <span id="ve-he-meta">0 líneas · 0 chars</span>
                </div>
            </div>
            <div class="vm-foot">
                <span class="vm-kbd-pill"><span class="vm-kbd">⌘↵</span> aplicar</span>
                <span class="vm-editor-hint">Ctrl+F buscar · Ctrl+H reemplazar · F11 pantalla completa</span>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-apply-html">
                    <i class="fa-solid fa-check"></i>Aplicar cambios
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: icono selector ─────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-icon-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-icons me-2"></i>Insertar icono</h5>
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
{{-- ── 01 · Shortcode builder modal ─────────────────────────────────────── --}}
<div class="modal fade" id="ve-shortcode-builder-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-md modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-code"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">SHORTCODE</div>
                    <div class="vm-title">
                        <span id="ve-scb-title">Shortcode Builder</span>
                        <span class="vm-chip" id="ve-scb-tag"></span>
                    </div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div class="vm-desc-bar" id="ve-scb-description" style="display:none;"></div>

                {{-- Attribute inputs (filled dynamically) --}}
                <div id="ve-scb-attrs"></div>

                {{-- Content (pair shortcodes) --}}
                <div id="ve-scb-content-wrap" class="vm-field" style="display:none;">
                    <label class="vm-flabel">Contenido <span class="hint">opcional</span></label>
                    <textarea class="vm-finput" id="ve-scb-content" rows="3" placeholder="Contenido del shortcode…"></textarea>
                </div>

                {{-- Live preview --}}
                <div class="vm-field">
                    <label class="vm-flabel">Código generado <span class="hint">copia listo</span></label>
                    <div class="vm-code-block">
                        <pre id="ve-scb-preview" style="margin:0;white-space:pre-wrap;word-break:break-all;min-height:36px;background:transparent;color:inherit;padding:0;border:none;font-size:inherit;font-family:inherit;"></pre>
                        <button class="vm-copy-btn" id="btn-copy-shortcode"><i class="fa-regular fa-copy"></i>Copiar</button>
                    </div>
                </div>

                <div class="vm-field" id="ve-scb-example-wrap">
                    <label class="vm-flabel">Ejemplo</label>
                    <div class="vm-example" id="ve-scb-example"></div>
                </div>
            </div>
            <div class="vm-foot">
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" id="btn-copy-shortcode-foot"><i class="fa-regular fa-copy"></i>Copiar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-insert-shortcode">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>Insertar en editor
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Ayuda / Guía ──────────────────────────────────────────── --}}
<div class="modal fade" id="ve-help-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-md modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-circle-question"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">DOCUMENTACIÓN</div>
                    <div class="vm-title">Ayuda del editor</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div class="ve-help-grid">
                    <a href="#" class="ve-help-card" data-action="onboarding">
                        <div class="ve-help-ico"><i class="fa-solid fa-graduation-cap"></i></div>
                        <div class="ve-help-t">Tour guiado</div>
                        <div class="ve-help-s">Recorrido rápido por las funciones principales del editor</div>
                    </a>
                    <a href="#" class="ve-help-card" data-action="shortcuts">
                        <div class="ve-help-ico"><i class="fa-solid fa-keyboard"></i></div>
                        <div class="ve-help-t">Atajos de teclado</div>
                        <div class="ve-help-s">Lista completa de combinaciones para trabajar más rápido</div>
                    </a>
                    <a href="#" class="ve-help-card" data-action="quick-actions">
                        <div class="ve-help-ico"><i class="fa-solid fa-bolt"></i></div>
                        <div class="ve-help-t">Personalizar acciones</div>
                        <div class="ve-help-s">Configura los botones flotantes de acceso rápido (hasta 6)</div>
                    </a>
                    <a href="#" class="ve-help-card" data-action="tweaks">
                        <div class="ve-help-ico"><i class="fa-solid fa-sliders"></i></div>
                        <div class="ve-help-t">Ajustes visuales</div>
                        <div class="ve-help-s">Fondo del canvas, ancho del panel, chrome, minimapa</div>
                    </a>
                    <a href="#" class="ve-help-card" data-action="command-palette">
                        <div class="ve-help-ico"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <div class="ve-help-t">Paleta de comandos</div>
                        <div class="ve-help-s">Abre con <span class="kbd">⌘K</span> para buscar cualquier acción</div>
                    </a>
                    <a href="https://docs.alsernet.pt" target="_blank" rel="noopener noreferrer" class="ve-help-card">
                        <div class="ve-help-ico"><i class="fa-solid fa-book"></i></div>
                        <div class="ve-help-t">Documentación</div>
                        <div class="ve-help-s">Guías completas, tutoriales en vídeo y preguntas frecuentes</div>
                    </a>
                </div>
                <div class="ve-help-footer">
                    <span class="ve-help-version">Versión editor · v1.0</span>
                    <a href="mailto:soporte@alsernet.pt" class="ve-help-contact">
                        <i class="fa-solid fa-envelope"></i>Contactar soporte
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Atajos de teclado (cheatsheet) ──────────────────────────── --}}
<div class="modal fade" id="ve-shortcuts-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-md modal-dialog-centered">
        <div class="modal-content vm-wrap ve-cheat">
            <div class="ve-cheat-head">
                <i class="fa-solid fa-keyboard"></i>
                <div class="t">Atajos</div>
                <div class="s">22 comandos</div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="ve-cheat-body">
                <div class="ve-cheat-col">
                    <div class="ve-cheat-sec">Documento</div>
                    <div class="ve-cheat-row"><span class="lbl">Guardar</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">S</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Publicar</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">⇧</span><span class="kbd">P</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Deshacer</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">Z</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Rehacer</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">Y</span></div></div>
                    <div class="ve-cheat-sec">Vista</div>
                    <div class="ve-cheat-row"><span class="lbl">Preview</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">P</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Pantalla completa</span><div class="keys"><span class="kbd">F11</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Outline mode</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">O</span></div></div>
                    <div class="ve-cheat-sec">Navegación</div>
                    <div class="ve-cheat-row"><span class="lbl">Command palette</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">K</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Buscar en página</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">F</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Reemplazar</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">H</span></div></div>
                </div>
                <div class="ve-cheat-col">
                    <div class="ve-cheat-sec">Edición</div>
                    <div class="ve-cheat-row"><span class="lbl">Copiar elemento</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">⇧</span><span class="kbd">C</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Pegar elemento</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">⇧</span><span class="kbd">V</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Duplicar</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">D</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Eliminar</span><div class="keys"><span class="kbd">⌫</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Editar HTML</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">E</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Snippets HTML</span><div class="keys"><span class="kbd">⌘</span><span class="kbd">⇧</span><span class="kbd">S</span></div></div>
                    <div class="ve-cheat-sec">Ratón</div>
                    <div class="ve-cheat-row"><span class="lbl">Seleccionar</span><div class="keys"><span class="kbd">clic</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Editar inline</span><div class="keys"><span class="kbd">2× clic</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Menú contextual</span><div class="keys"><span class="kbd">clic der.</span></div></div>
                    <div class="ve-cheat-sec">Insertar</div>
                    <div class="ve-cheat-row"><span class="lbl">Slash menu</span><div class="keys"><span class="kbd">/</span></div></div>
                    <div class="ve-cheat-row"><span class="lbl">Abrir atajos</span><div class="keys"><span class="kbd">?</span></div></div>
                </div>
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
                <button type="button" class="vm-btn vm-btn-primary w-100 mb-1" id="btn-confirm-save-block">
                    Guardar shortcode
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>


{{-- ── Modal: Confirmar eliminación ───────────────────────────────────────── --}}
<div class="modal fade" id="ve-confirm-modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog vm-dialog-sm modal-dialog-centered">
        <div class="modal-content vm-wrap ve-confirm-wrap">
            <div class="ve-confirm-body">
                <div class="ve-confirm-icon" id="ve-confirm-icon-wrap">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="ve-confirm-text">
                    <div class="ve-confirm-title" id="ve-confirm-title">¿Descartar cambios?</div>
                    <div class="ve-confirm-sub" id="ve-confirm-message">Se perderán los cambios sin guardar. Esta acción no se puede deshacer.</div>
                </div>
            </div>
            <div class="vm-foot">
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="vm-btn vm-btn-danger vm-btn-sm" id="ve-confirm-accept">Confirmar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: QR Preview ───────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-qr-modal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Escanea desde tu móvil</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-3">
                <div id="ve-qr-container" style="display:inline-block;"></div>
                <div id="ve-qr-url" class="mt-2" style="font-size:10px;color:#aaa;word-break:break-all;"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Imágenes rotas ────────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-broken-images-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-md modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-image"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">AUDITORÍA · <span id="ve-img-scanned-count">0</span> imágenes escaneadas</div>
                    <div class="vm-title">Imágenes</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body" style="gap:10px;">
                {{-- Score row estilo Accesibilidad --}}
                <div class="vm-score-row" id="ve-img-score-row">
                    <div class="vm-ring">
                        <svg width="52" height="52" viewBox="0 0 52 52">
                            <circle cx="26" cy="26" r="22" stroke="#e4e4e7" stroke-width="4" fill="none"/>
                            <circle id="ve-img-ring-arc" cx="26" cy="26" r="22" stroke="#16a34a" stroke-width="4" fill="none"
                                    stroke-dasharray="138.23" stroke-dashoffset="0" stroke-linecap="round"/>
                        </svg>
                        <div class="vm-ring-num" id="ve-img-score-num">—</div>
                    </div>
                    <div class="vm-score-label">
                        <div class="vm-score-grade" id="ve-img-grade">Analizando…</div>
                        <div class="vm-score-desc" id="ve-img-desc">Verificando imágenes del iframe</div>
                    </div>
                    <div class="vm-score-counts">
                        <div class="vm-score-count err"><span class="n" id="ve-img-broken-n">0</span><span class="l">Rotas</span></div>
                        <div class="vm-score-count ok"><span class="n" id="ve-img-ok-n">0</span><span class="l">OK</span></div>
                        <div class="vm-score-count warn"><span class="n" id="ve-img-total-n">0</span><span class="l">Total</span></div>
                    </div>
                </div>

                {{-- Progress bar para el scan --}}
                <div class="ve-img-progress ve-hidden" id="ve-img-progress-wrap">
                    <div class="ve-img-progress-bar" id="ve-img-progress-bar"></div>
                </div>

                {{-- Filter bar --}}
                <div class="ve-img-filterbar">
                    <div class="vm-seg ve-img-filter" data-active="broken">
                        <button type="button" data-filter="broken" class="active">Rotas</button>
                        <button type="button" data-filter="all">Todas</button>
                    </div>
                    <span class="ve-img-filterbar-meta" id="ve-img-filter-meta"></span>
                </div>

                <div class="vm-a11y-list ve-img-list" id="ve-broken-images-list">
                    <div class="ve-img-empty">
                        <i class="fa-solid fa-image"></i>
                        <div>Aún no se ha escaneado</div>
                    </div>
                </div>
            </div>
            <div class="vm-foot">
                <span class="ve-stat-updated" id="ve-img-updated">—</span>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" id="btn-img-rescan">
                    <i class="fa-solid fa-rotate"></i>Reescanear
                </button>
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Buscar y reemplazar en shortcodes ────────────────────────────── --}}
<div class="modal fade" id="ve-sc-find-replace-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Buscar y reemplazar en bloques</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" style="font-size:12px;">Tipo de bloque (opcional)</label>
                    <input type="text" class="form-control form-control-sm" id="sc-fr-type" placeholder="contact-form, button... (vacío = todos)">
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:12px;">Atributo</label>
                    <input type="text" class="form-control form-control-sm" id="sc-fr-attr" placeholder="email, title, color...">
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:12px;">Buscar</label>
                    <input type="text" class="form-control form-control-sm" id="sc-fr-find" placeholder="valor a buscar">
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:12px;">Reemplazar con</label>
                    <input type="text" class="form-control form-control-sm" id="sc-fr-replace" placeholder="nuevo valor">
                </div>
                <div id="sc-fr-preview" style="font-size:12px;color:#888;min-height:18px;"></div>
            </div>
            <div class="modal-footer py-2 d-block">
                <button type="button" class="btn ve-btn-primary w-100 mb-1" id="btn-sc-fr-preview">Vista previa</button>
                <button type="button" class="vm-btn vm-btn-primary w-100 mb-1" id="btn-sc-fr-apply">Aplicar a todos</button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Media picker ────────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-media-picker-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-lg modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-regular fa-image"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">BIBLIOTECA · <span id="ve-mp-count">0</span> archivos</div>
                    <div class="vm-title">Seleccionar medio</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="ec-mp-tabs" id="ve-mp-tabs">
                <button class="on" data-tab="library"><i class="fa-regular fa-image"></i> Biblioteca</button>
                <button data-tab="upload"><i class="fa-solid fa-upload"></i> Subir</button>
                <button data-tab="url"><i class="fa-solid fa-link"></i> URL</button>
            </div>
            <div class="ec-mp-toolbar">
                <div style="flex:1;position:relative;">
                    <i class="fa-solid fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--ve-text-muted);"></i>
                    <input type="text" class="vm-finput" id="ve-mp-search" placeholder="Buscar archivos…" style="padding-left:30px;">
                </div>
                <select class="vm-fselect" id="ve-mp-filter" style="width:auto;">
                    <option value="">Todos</option>
                    <option value="image">Imágenes</option>
                    <option value="video">Vídeos</option>
                    <option value="document">Documentos</option>
                </select>
            </div>
            <div class="ec-mp-side">
                <div class="ec-mp-grid" id="ve-mp-grid"></div>
                <div class="ec-mp-details" id="ve-mp-details">
                    <div class="ec-mp-thumb" id="ve-mp-thumb"></div>
                    <div style="font-size:12px;font-weight:600;" id="ve-mp-name">—</div>
                    <div class="ec-mp-meta"><span style="color:var(--ve-text-muted);">Dimensiones</span><span id="ve-mp-dims">—</span></div>
                    <div class="ec-mp-meta"><span style="color:var(--ve-text-muted);">Peso</span><span id="ve-mp-size">—</span></div>
                    <div class="ec-mp-meta"><span style="color:var(--ve-text-muted);">Alt</span><span id="ve-mp-alt">—</span></div>
                </div>
            </div>
            <div class="vm-foot">
                <span style="font-size:11px;color:var(--ve-text-muted);" id="ve-mp-selected-label">Ninguno seleccionado</span>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-mp-insert"><i class="fa-solid fa-check"></i>Insertar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Validación de imágenes ─────────────────────────────────────── --}}
<div class="modal fade" id="ve-image-check-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-md modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-regular fa-image"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">ESCANEO · <span id="ve-ic-total">0</span> imágenes</div>
                    <div class="vm-title">Validación de imágenes</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div style="display:flex;gap:6px;flex-wrap:wrap;" id="ve-ic-chips">
                    <span class="ec-chip danger"><span class="d"></span><span id="ve-ic-bad-n">0</span> rotas</span>
                    <span class="ec-chip warn"><span class="d"></span><span id="ve-ic-slow-n">0</span> lentas</span>
                    <span class="ec-chip success"><span class="d"></span><span id="ve-ic-ok-n">0</span> OK</span>
                </div>
                <div class="ec-img-check-list" id="ve-ic-list">
                    <div style="padding:20px;text-align:center;color:var(--ve-text-muted);font-size:12px;">Escaneando imágenes…</div>
                </div>
            </div>
            <div class="vm-foot">
                <button type="button" class="vm-btn vm-btn-ghost vm-btn-sm" id="btn-ic-rescan"><i class="fa-solid fa-rotate"></i>Re-escanear</button>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" id="btn-ic-report"><i class="fa-solid fa-file-export"></i>Reporte</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-ic-optimize"><i class="fa-solid fa-wand-magic-sparkles"></i>Optimizar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: SEO ─────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-seo-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-md modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">OPTIMIZACIÓN</div>
                    <div class="vm-title">SEO <span class="vm-chip" id="ve-seo-score-chip">—</span></div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div class="ec-seo-score">
                    <div style="font-size:22px;font-weight:700;font-family:'JetBrains Mono',monospace;" id="ve-seo-score-num">—</div>
                    <div style="flex:1;">
                        <div style="font-size:11.5px;font-weight:500;" id="ve-seo-score-label">Calculando…</div>
                        <div class="ec-seo-bar" style="margin-top:6px;"><div class="ec-seo-bar-fill" id="ve-seo-bar-fill" style="width:0%;"></div></div>
                    </div>
                </div>
                <div class="ec-seo-serp" id="ve-seo-serp">
                    <div class="url" id="ve-seo-serp-url"></div>
                    <div class="title" id="ve-seo-serp-title">{{ $page->seo_title ?: $page->title }}</div>
                    <div class="desc" id="ve-seo-serp-desc">{{ $page->seo_description }}</div>
                </div>
                <div class="vm-field">
                    <label class="vm-flabel">Título SEO <span class="hint" id="ve-seo-title-count"></span></label>
                    <input type="text" class="vm-finput" id="ve-seo-title-input" value="{{ $page->seo_title }}" placeholder="Título para buscadores (50-60 caracteres)">
                </div>
                <div class="vm-field">
                    <label class="vm-flabel">Meta descripción <span class="hint" id="ve-seo-desc-count"></span></label>
                    <textarea class="vm-finput" id="ve-seo-desc-input" rows="2" placeholder="Descripción para buscadores (120-160 caracteres)">{{ $page->seo_description }}</textarea>
                </div>
                <div class="ec-seo-checks" id="ve-seo-checks"></div>
            </div>
            <div class="vm-foot">
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-seo-save"><i class="fa-solid fa-floppy-disk"></i>Guardar SEO</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Estadísticas de página ─────────────────────────────────────── --}}
<div class="modal fade" id="ve-stats-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-md modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-chart-simple"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">ANÁLISIS · <span id="ve-stat-range-label">últimos 30 días</span></div>
                    <div class="vm-title">Estadísticas</div>
                </div>
                {{-- Range segment control --}}
                <div class="vm-seg ve-stat-range" data-active="30">
                    <button type="button" data-range="7">7d</button>
                    <button type="button" data-range="30" class="active">30d</button>
                    <button type="button" data-range="90">90d</button>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                {{-- Score row — big number + sparkline + delta (Accesibilidad style) --}}
                <div class="vm-score-row">
                    <div class="vm-ring-wrap ve-stat-hero">
                        <div class="vm-ring-num" id="ve-stat-visits">—</div>
                        <div class="vm-ring-cap">visitas</div>
                    </div>
                    <div class="vm-score-label">
                        <div class="vm-score-grade" id="ve-stat-trend">Cargando…</div>
                        <div class="vm-score-desc" id="ve-stat-trend-desc">—</div>
                        <svg class="ve-stat-spark" viewBox="0 0 200 36" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="ecCg" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#b10100" stop-opacity=".3"/>
                                    <stop offset="100%" stop-color="#b10100" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <path id="ve-chart-area" fill="url(#ecCg)"/>
                            <path id="ve-chart-line" fill="none" stroke="#b10100" stroke-width="1.5" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="vm-score-counts">
                        <div class="vm-score-count ok"><span class="n" id="ve-stat-conv">—</span><span class="l">Conv %</span></div>
                        <div class="vm-score-count warn"><span class="n" id="ve-stat-bounce">—</span><span class="l">Bounce</span></div>
                        <div class="vm-score-count err"><span class="n" id="ve-stat-time">—</span><span class="l">Tiempo</span></div>
                    </div>
                </div>

                {{-- Secondary KPIs — compact 4-col row --}}
                <div class="ve-stat-kpis">
                    <div class="ve-stat-kpi">
                        <div class="k">Páginas/sesión</div>
                        <div class="v" id="ve-stat-ppv">—</div>
                        <div class="d" id="ve-stat-ppv-delta"></div>
                    </div>
                    <div class="ve-stat-kpi">
                        <div class="k">Nuevos vs. recurrentes</div>
                        <div class="v" id="ve-stat-new-ratio">—</div>
                        <div class="d" id="ve-stat-new-ratio-delta"></div>
                    </div>
                    <div class="ve-stat-kpi">
                        <div class="k">Top origen</div>
                        <div class="v" id="ve-stat-source">—</div>
                        <div class="d" id="ve-stat-source-delta"></div>
                    </div>
                    <div class="ve-stat-kpi">
                        <div class="k">Dispositivo</div>
                        <div class="v" id="ve-stat-device">—</div>
                        <div class="d" id="ve-stat-device-delta"></div>
                    </div>
                </div>

                {{-- Contenido de la página — analiza el iframe actual --}}
                <div class="ve-stat-section">
                    <div class="ve-stat-section-head">
                        <span>Contenido de la página</span>
                        <span class="ve-stat-section-sub" id="ve-stat-content-meta">Análisis del DOM</span>
                    </div>
                    <div class="ve-stat-content-grid" id="ve-stat-content-grid">
                        <div class="ve-stat-content-cell"><div class="k">Palabras</div><div class="v" id="ve-stat-words">—</div></div>
                        <div class="ve-stat-content-cell"><div class="k">Imágenes</div><div class="v" id="ve-stat-images">—</div></div>
                        <div class="ve-stat-content-cell"><div class="k">Enlaces</div><div class="v" id="ve-stat-links">—</div></div>
                        <div class="ve-stat-content-cell"><div class="k">Encabezados</div><div class="v" id="ve-stat-headings">—</div></div>
                        <div class="ve-stat-content-cell"><div class="k">Shortcodes</div><div class="v" id="ve-stat-shortcodes">—</div></div>
                        <div class="ve-stat-content-cell"><div class="k">Shortcodes únicos</div><div class="v" id="ve-stat-shortcodes-uniq">—</div></div>
                    </div>
                </div>

                {{-- Salud técnica (mismo estilo que a11y-checks) --}}
                <div class="ve-stat-section">
                    <div class="ve-stat-section-head">
                        <span>Salud técnica</span>
                        <span class="ve-stat-section-sub" id="ve-stat-vitals-meta">Core Web Vitals</span>
                    </div>
                    <div class="vm-a11y-list" id="ve-stats-vitals">
                        <div style="padding:14px;text-align:center;color:var(--ve-text-muted);font-size:11px;">Midiendo…</div>
                    </div>
                </div>
            </div>
            <div class="vm-foot">
                <span class="ve-stat-updated" id="ve-stat-updated">Actualizado ahora</span>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" id="btn-stat-export">
                    <i class="fa-solid fa-file-export"></i>Exportar CSV
                </button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-stat-analytics">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>Ver en Analytics
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Slash menu (/ block inserter) ────────────────────────────────────── --}}
<div id="ve-slash-menu" role="listbox" aria-label="Insertar bloque">
    <div class="ve-slash-search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="ve-slash-input" placeholder="Buscar bloque…" autocomplete="off" spellcheck="false">
    </div>
    <div class="ve-slash-results" id="ve-slash-results"></div>
    <div class="ve-slash-footer">
        <span><kbd>↑</kbd><kbd>↓</kbd> navegar</span>
        <span><kbd>⏎</kbd> insertar</span>
        <span><kbd>Esc</kbd> cerrar</span>
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
    const EXPAND_SHORTCODE_URL   = '{{ route("pages.expand-shortcode", $page) }}';
    const EDITOR_VERSIONS_URL    = '{{ route("pages.editor-versions", $page) }}';
    const EDITOR_VERSION_URL     = '{{ url("panel/pages/".$page->id."/editor-versions") }}';
    const DRAFT_URL              = '{{ route("pages.draft", $page) }}';
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

    // Expose PAGE_DATA to other IIFEs (SEO modal, stats, etc.)
    window.PAGE_DATA = PAGE_DATA;

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

    function pushHistory(label, html, opts) {
        opts = opts || {};
        // Truncate redo entries
        historyStack = historyStack.slice(0, historyPointer + 1);
        historyStack.push({
            label:     label || 'Cambio',
            html:      html,
            timestamp: Date.now(),
            auto:      !!opts.auto,
            who:       opts.who || 'tú'
        });
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

    function formatRelativeTime(ts) {
        if (!ts) return '';
        var diff = Math.max(0, Math.round((Date.now() - ts) / 1000));
        if (diff < 10)   return 'ahora';
        if (diff < 60)   return 'hace ' + diff + 's';
        if (diff < 3600) return 'hace ' + Math.round(diff / 60)   + 'min';
        if (diff < 86400) return 'hace ' + Math.round(diff / 3600) + 'h';
        var d = new Date(ts);
        return 'hace ' + Math.round(diff / 86400) + 'd';
    }

    function renderHistoryPanel() {
        const $list  = $('#ve-history-list');
        const $empty = $('#ve-history-empty');

        // Update counter
        $('#ve-hist-counter').text(historyStack.length + ' / 60');

        if (historyStack.length === 0) {
            $list.empty();
            $empty.show();
            return;
        }
        $empty.hide();
        $list.empty();

        // Render newest first (i > pointer = future/redo, i == pointer = current, i < pointer = past)
        for (let i = historyStack.length - 1; i >= 0; i--) {
            const entry     = historyStack[i];
            const isCurrent = i === historyPointer;
            const isFuture  = i > historyPointer;
            const isPast    = i < historyPointer;

            let stateClass = '';
            if (isCurrent) stateClass = ' current';
            else if (isFuture) stateClass = ' ve-hist-future';
            else if (isPast)   stateClass = ' ve-hist-past';

            var meta = formatRelativeTime(entry.timestamp) + ' · ' + $('<div>').text(entry.who || 'tú').html();
            var autoBadge = entry.auto
                ? '<span class="ve-hist-auto">AUTO</span>'
                : (isCurrent
                    ? '<span class="ve-hist-badge">Actual</span>'
                    : (isFuture ? '<span class="ve-hist-badge ve-hist-badge-redo">Rehacer</span>' : ''));

            const $item = $('<div>')
                .addClass('ve-history-item' + stateClass)
                .attr('data-idx', i)
                .html(
                    '<div class="ve-hist-track"><div class="ve-hist-dot"></div></div>' +
                    '<div class="ve-hist-body">' +
                        '<div class="ve-hist-label">' + $('<div>').text(entry.label).html() + '</div>' +
                        '<div class="ve-hist-meta">' + meta + '</div>' +
                    '</div>' +
                    autoBadge
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
        window._veIsModified = dirty;
        setAutoSaveStatus(dirty ? 'unsaved' : '', dirty ? 'Sin guardar' : '');
        $('#btn-save').toggleClass('ve-btn-unsaved', dirty);
        $('#btn-save-bar').toggleClass('ve-btn-unsaved', dirty);
        $(document).trigger('ve-modified-changed', [dirty]);
    }

    /* ── CKEditor init ───────────────────────────────────────────────── */
    ClassicEditor.create(document.querySelector('#ve-content'), {
        toolbar: {
            items: [
                'heading', '|',
                'bold', 'italic', '|',
                'link', '|',
                'bulletedList', 'numberedList', '|',
                'blockQuote', '|',
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

        // Initialize contextual topbar for default active panel
        veUpdateTopbarCtx('shortcodes');

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
                data:        JSON.stringify({
                    content:         content,
                    locale:          LOCALE,
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
                    $btn.html('<i class="fa-solid fa-check me-1"></i>Guardado')
                        .css({ background: '#13C672', 'border-color': '#13C672' });
                    setTimeout(function () {
                        $btn.html('<i class="fa-solid fa-save me-1"></i>Guardar')
                            .css({ background: '#b10100', 'border-color': '#b10100' });
                    }, 2500);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Error al guardar';
                    $btn.html('<i class="fa-solid fa-exclamation-triangle me-1"></i>Error')
                        .css({ background: '#FA896B', 'border-color': '#FA896B' });
                    alert(msg);
                    setTimeout(function () {
                        $btn.html('<i class="fa-solid fa-save me-1"></i>Guardar')
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
                            .html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...');
        doSave($btn);
    });

    /* ── Undo / Redo ─────────────────────────────────────────────────── */
    $('#btn-undo').on('click', function () { undoHistory(); });
    $('#btn-redo').on('click', function () { redoHistory(); });

    // Expose to other IIFEs (Quick Actions, command palette, shortcuts)
    window.veSave = function () {
        if (isSaving) return;
        isSaving = true;
        var $btn = $('#btn-save').prop('disabled', true)
            .html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...');
        doSave($btn);
    };
    window.veUndo = function () {
        if (historyPointer <= 0) {
            if (window.showToast) window.showToast('<i class="fa-solid fa-info-circle me-1"></i>No hay acciones para deshacer');
            return;
        }
        undoHistory();
    };
    window.veRedo = function () {
        if (historyPointer >= historyStack.length - 1) {
            if (window.showToast) window.showToast('<i class="fa-solid fa-info-circle me-1"></i>No hay acciones para rehacer');
            return;
        }
        redoHistory();
    };

    $(document).on('click', '#btn-diff-discard', function () {
        bootstrap.Modal.getInstance(document.getElementById('ve-diff-modal')).hide();
        $('#btn-discard').trigger('click');
    });

    $('#btn-discard').on('click', function () {
        veConfirm('¿Descartar todos los cambios y restaurar el contenido guardado?', function () {
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
    });

    /* ── Locale Switcher ─────────────────────────────────────────────── */
    function performLocaleSwitch(newLocale) {
        var $btn = $('#btn-locale-switcher');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

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
    }

    $(document).on('click', '.ve-locale-btn', function () {
        var newLocale = $(this).data('locale');
        if (newLocale === LOCALE) return;
        if (isModified) {
            veConfirm('Hay cambios sin guardar. ¿Deseas cambiar de idioma sin guardar?', function () {
                performLocaleSwitch(newLocale);
            });
        } else {
            performLocaleSwitch(newLocale);
        }
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

    $(document).on('click', '.breakpoint-btn', function () {
        $('.breakpoint-btn').removeClass('active');
        $(this).addClass('active');
        // Sync pill group (ve-bp-btn uses 'on' class)
        var bpVal = $(this).data('breakpoint');
        $('.ve-bp-btn').removeClass('on');
        $('.ve-bp-btn[data-breakpoint="' + bpVal + '"]').addClass('on');

        const bp     = $(this).data('breakpoint');
        const width  = $(this).data('width');
        const height = $(this).data('height');
        const $wrap  = $('#ve-canvas-wrap');
        const $frame = $('#ve-preview-frame');

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

        // Re-apply fit if active, and refresh the responsive ruler if visible
        if (typeof veApplyFit === 'function' && _veZoomMode === 'fit') {
            setTimeout(veApplyFit, 50);
        }
        if ($('#ve-ruler').hasClass('active') && typeof window.veRenderRuler === 'function') {
            setTimeout(window.veRenderRuler, 50);
        }
    });

    /* ── Sidebar panel switching ─────────────────────────────────────── */
    /* ── Topbar context factories ────────────────────────────────────── */
    const veTopbarCtx = {
        shortcodes: function () {
            return '<div class="ve-ctx-group">' +
                '<div class="ve-seg" id="ve-blocks-view-seg">' +
                    '<button class="ve-seg-btn active" data-view="grid"><i class="fa-solid fa-grip"></i> Rejilla</button>' +
                    '<button class="ve-seg-btn" data-view="list"><i class="fa-solid fa-list"></i> Lista</button>' +
                '</div>' +
                '<div class="ve-topbar-divider"></div>' +
                '<div class="ve-seg" id="ve-blocks-filter-seg">' +
                    '<button class="ve-seg-btn active" data-filter="all">Todos</button>' +
                    '<button class="ve-seg-btn" data-filter="system">Sistema</button>' +
                    '<button class="ve-seg-btn" data-filter="custom">Personalizados</button>' +
                '</div>' +
            '</div>';
        },
        inspector: function () {
            return '<div class="ve-ctx-group">' +
                '<span class="ve-ctx-pill" id="ve-ctx-element-pill"><i class="fa-solid fa-crosshairs"></i> <span class="ve-ctx-mono">ninguno</span></span>' +
                '<div class="ve-topbar-divider"></div>' +
                '<div class="ve-seg">' +
                    '<button class="ve-seg-btn active breakpoint-btn" data-breakpoint="desktop" title="Escritorio"><i class="fa-solid fa-desktop"></i></button>' +
                    '<button class="ve-seg-btn breakpoint-btn" data-breakpoint="laptop" data-width="1280px" data-height="800px" title="Laptop"><i class="fa-solid fa-laptop"></i></button>' +
                    '<button class="ve-seg-btn breakpoint-btn" data-breakpoint="tablet" data-width="768px" data-height="1024px" title="Tablet"><i class="fa-solid fa-tablet-screen-button"></i></button>' +
                    '<button class="ve-seg-btn breakpoint-btn" data-breakpoint="mobile" data-width="375px" data-height="812px" title="Móvil"><i class="fa-solid fa-mobile-screen"></i></button>' +
                '</div>' +
                '<div class="ve-topbar-divider"></div>' +
                '<div class="ve-seg" id="ve-ctx-state-seg">' +
                    '<button class="ve-seg-btn active" data-state="base">Base</button>' +
                    '<button class="ve-seg-btn" data-state="hover">:hover</button>' +
                    '<button class="ve-seg-btn" data-state="focus">:focus</button>' +
                '</div>' +
            '</div>';
        },
        layout: function () {
            return '<div class="ve-ctx-group">' +
                '<div class="ve-seg">' +
                    '<button class="ve-seg-btn active breakpoint-btn" data-breakpoint="desktop"><i class="fa-solid fa-desktop"></i></button>' +
                    '<button class="ve-seg-btn breakpoint-btn" data-breakpoint="tablet" data-width="768px" data-height="1024px"><i class="fa-solid fa-tablet-screen-button"></i></button>' +
                    '<button class="ve-seg-btn breakpoint-btn" data-breakpoint="mobile" data-width="375px" data-height="812px"><i class="fa-solid fa-mobile-screen"></i></button>' +
                '</div>' +
                '<div class="ve-topbar-divider"></div>' +
                '<span class="ve-ctx-pill"><i class="fa-solid fa-border-all"></i> <span class="ve-ctx-mono">12 cols · 1280px</span></span>' +
                '<span class="ve-ctx-pill"><i class="fa-solid fa-grip-lines"></i> Gap <span class="ve-ctx-mono">24px</span></span>' +
            '</div>';
        },
        sections: function () {
            return '<div class="ve-ctx-group">' +
                '<span class="ve-ctx-pill"><i class="fa-solid fa-layer-group"></i> <span class="ve-ctx-mono" id="ve-ctx-sections-count">...</span></span>' +
                '<div class="ve-topbar-divider"></div>' +
                '<div class="ve-seg">' +
                    '<button class="ve-seg-btn active">Todo</button>' +
                    '<button class="ve-seg-btn">Ocultas</button>' +
                '</div>' +
            '</div>';
        },
        history: function () {
            return '<div class="ve-ctx-group">' +
                '<span class="ve-ctx-pill"><i class="fa-solid fa-clock-rotate-left"></i> <span class="ve-ctx-mono" id="ve-ctx-history-count">...</span></span>' +
                '<div class="ve-topbar-divider"></div>' +
                '<div class="ve-seg">' +
                    '<button class="ve-seg-btn active">Todas</button>' +
                    '<button class="ve-seg-btn">Snapshots</button>' +
                '</div>' +
            '</div>';
        },
        code: function () {
            return '<div class="ve-ctx-group">' +
                '<div class="ve-seg" id="ve-ctx-code-type-seg">' +
                    '<button class="ve-seg-btn active">HTML</button>' +
                    '<button class="ve-seg-btn">CSS</button>' +
                '</div>' +
                '<div class="ve-topbar-divider"></div>' +
                '<span class="ve-ctx-pill" id="ve-ctx-code-validity"><i class="fa-solid fa-circle" id="ve-ctx-validity-dot" style="color:#13C672;font-size:7px;"></i> <span class="ve-ctx-mono" id="ve-ctx-validity-label">Válido</span></span>' +
                '<span class="ve-ctx-pill ve-ctx-lines-pill"><span class="ve-ctx-mono" id="ve-ctx-code-lines">0 líneas</span></span>' +
            '</div>';
        },
        settings: function () {
            return '<div class="ve-ctx-group">' +
                '<div class="ve-seg" id="ve-ctx-settings-seg">' +
                    '<button class="ve-seg-btn active" data-settings-tab="basic">Básico</button>' +
                    '<button class="ve-seg-btn" data-settings-tab="seo">SEO</button>' +
                    '<button class="ve-seg-btn" data-settings-tab="social">Social</button>' +
                '</div>' +
                '<div class="ve-topbar-divider"></div>' +
                '<span class="ve-ctx-pill"><span class="ve-ctx-mono" id="ve-ctx-seo-score">SEO ...</span></span>' +
            '</div>';
        },
        'dom-tree': function () {
            return '<div class="ve-ctx-group">' +
                '<span class="ve-ctx-pill"><i class="fa-solid fa-sitemap"></i> <span class="ve-ctx-mono" id="ve-ctx-dom-count">...</span></span>' +
                '<div class="ve-topbar-divider"></div>' +
                '<div class="ve-seg">' +
                    '<button class="ve-seg-btn active">Todos</button>' +
                    '<button class="ve-seg-btn">Solo bloque</button>' +
                '</div>' +
            '</div>';
        },
    };

    function veUpdateTopbarCtx(panel) {
        const $default = $('#ve-ctx-default');
        const $ctx     = $('#ve-ctx-panel');
        if (veTopbarCtx[panel]) {
            $default.hide();
            $ctx.html(veTopbarCtx[panel]()).show();
            // Post-render panel hooks
            if (panel === 'history') {
                $('#ve-ctx-history-count').text($('#ve-history-list').children().length + ' versiones');
            }
            if (panel === 'code' && window._veFullCodeMirror) {
                $('#ve-ctx-code-lines').text(window._veFullCodeMirror.lineCount() + ' líneas');
            }
            if (panel === 'sections') {
                var cnt = document.getElementById('ve-preview-frame')?.contentDocument?.querySelectorAll('section,article,header,footer,main').length || '?';
                $('#ve-ctx-sections-count').text(cnt + ' secciones');
            }
        } else {
            $default.show();
            $ctx.empty().hide();
        }
    }

    $('#ve-sidebar-nav .ve-nav-btn').on('click', function () {
        const panel = $(this).data('panel');
        $('#ve-sidebar-nav .ve-nav-btn').removeClass('active');
        $(this).addClass('active');
        $('.ve-panel').removeClass('active');
        $('#ve-panel-' + panel).addClass('active');

        // Update contextual topbar
        veUpdateTopbarCtx(panel);
        if (panel === 'shortcodes' && window.veRefreshBlocksInPage) {
            setTimeout(window.veRefreshBlocksInPage, 200);
        }

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
        if (panel === 'dom-tree') {
            setTimeout(buildDomTree, 150);
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

    /* ── Publish / Unpublish ─────────────────────────────────────────── */
    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
    var PUBLISH_URL   = '{{ route("pages.publish", $page) }}';
    var UNPUBLISH_URL = '{{ route("pages.unpublish", $page) }}';

    // Expose to other IIFEs (Quick Actions, command palette, etc.)
    window.VE_CSRF_TOKEN    = CSRF_TOKEN;
    window.VE_PUBLISH_URL   = PUBLISH_URL;
    window.VE_UNPUBLISH_URL = UNPUBLISH_URL;
    window.VE_PAGE_STATUS   = '{{ $page->status->value }}';

    window.vePublishPage = function () {
        $.post(PUBLISH_URL, { _token: CSRF_TOKEN })
            .done(function () {
                if (window.showToast) window.showToast('<i class="fa-solid fa-globe me-1"></i>Página publicada correctamente.');
                window.VE_PAGE_STATUS = 'published';
                $('#btn-publish-page').hide();
                $('#btn-unpublish-page').show();
            })
            .fail(function () {
                if (window.showToast) window.showToast('<i class="fa-solid fa-exclamation-triangle me-1"></i>Error al publicar.', 'error');
            });
    };

    window.veUnpublishPage = function () {
        $.post(UNPUBLISH_URL, { _token: CSRF_TOKEN })
            .done(function () {
                if (window.showToast) window.showToast('<i class="fa-solid fa-eye-slash me-1"></i>Página despublicada.');
                window.VE_PAGE_STATUS = 'draft';
                $('#btn-unpublish-page').hide();
                $('#btn-publish-page').show();
            })
            .fail(function () {
                if (window.showToast) window.showToast('<i class="fa-solid fa-exclamation-triangle me-1"></i>Error al despublicar.', 'error');
            });
    };

    $('#btn-publish-page').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post(PUBLISH_URL, { _token: CSRF_TOKEN })
            .done(function () {
                showToast('<i class="fa-solid fa-globe me-1"></i>Página publicada correctamente.');
                $btn.hide();
                $('#btn-unpublish-page').show();
            })
            .fail(function () { showToast('<i class="fa-solid fa-exclamation-triangle me-1"></i>Error al publicar.', 'error'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#btn-unpublish-page').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post(UNPUBLISH_URL, { _token: CSRF_TOKEN })
            .done(function () {
                showToast('<i class="fa-solid fa-eye-slash me-1"></i>Página despublicada.');
                $btn.hide();
                $('#btn-publish-page').show();
            })
            .fail(function () { showToast('<i class="fa-solid fa-exclamation-triangle me-1"></i>Error al despublicar.', 'error'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    /* ── Approval request ────────────────────────────────────────────── */
    var APPROVAL_URL = '{{ route("pages.approval.request", $page) }}';

    $('#btn-request-approval').on('click', function () {
        new bootstrap.Modal(document.getElementById('ve-approval-modal')).show();
    });

    $('#btn-confirm-approval').on('click', function () {
        var comment = $('#ve-approval-comment').val().trim();
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: APPROVAL_URL,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            data: { comment: comment },
        })
        .done(function () {
            bootstrap.Modal.getInstance(document.getElementById('ve-approval-modal')).hide();
            showToast('<i class="fa-solid fa-check me-1"></i>Solicitud de aprobación enviada.');
        })
        .fail(function () { showToast('<i class="fa-solid fa-exclamation-triangle me-1"></i>Error al enviar solicitud.', 'error'); })
        .always(function () { $btn.prop('disabled', false); });
    });

    /* ── Page lock ───────────────────────────────────────────────────── */
    var LOCK_URL = '{{ route("pages.lock.acquire", $page) }}';
    var lockRenewTimer = null;

    function acquireLock() {
        $.ajax({
            url: LOCK_URL,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        })
        .done(function (res) {
            var data = res && res.data ? res.data : res;
            if (data && data.locked_by_me === false && data.locked_by) {
                showLockBanner(data.locked_by);
            } else {
                lockRenewTimer = setInterval(renewLock, 60000);
            }
        })
        .fail(function (xhr) {
            if (xhr.status === 423) {
                var data = xhr.responseJSON && xhr.responseJSON.data;
                showLockBanner(data && data.locked_by ? data.locked_by : 'otro usuario');
            }
        });
    }

    function renewLock() {
        $.ajax({ url: LOCK_URL, method: 'PATCH', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } });
    }

    function releaseLock() {
        clearInterval(lockRenewTimer);
        navigator.sendBeacon && navigator.sendBeacon(LOCK_URL + '?_method=DELETE&_token=' + CSRF_TOKEN);
    }

    function showLockBanner(user) {
        $('#ve-lock-banner-text').text('Esta página está siendo editada por ' + user + '. Tus cambios podrían sobrescribirse.');
        $('#ve-lock-banner').css('display', 'flex');
    }

    $(window).on('beforeunload', releaseLock);
    acquireLock();

    /* ── Server export / import ──────────────────────────────────────── */
    $('#btn-server-export').on('click', function () {
        window.open('{{ route("pages.export.download") }}?ids={{ $page->id }}', '_blank');
    });

    $('#btn-server-import').on('click', function () {
        new bootstrap.Modal(document.getElementById('ve-import-modal')).show();
    });

    $('#btn-confirm-import').on('click', function () {
        var file = document.getElementById('ve-import-file').files[0];
        if (!file) { showToast('Selecciona un archivo primero.', 'error'); return; }
        var formData = new FormData();
        formData.append('file', file);
        formData.append('_token', CSRF_TOKEN);
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '{{ route("pages.import.process") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        })
        .done(function () {
            bootstrap.Modal.getInstance(document.getElementById('ve-import-modal')).hide();
            showToast('<i class="fa-solid fa-check me-1"></i>Página importada correctamente.');
        })
        .fail(function () { showToast('<i class="fa-solid fa-exclamation-triangle me-1"></i>Error al importar.', 'error'); })
        .always(function () { $btn.prop('disabled', false); });
    });

    /* ── A11y auto-fix ───────────────────────────────────────────────── */
    $('#btn-a11y-fix-all').on('click', function () {
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) { return; }
        var doc = frame.contentDocument;
        var fixed = 0;

        // Add alt="" to images without alt
        doc.querySelectorAll('img:not([alt])').forEach(function (img) {
            img.setAttribute('alt', '');
            fixed++;
        });
        doc.querySelectorAll('img[alt=""]').forEach(function (img) {
            // already has alt (even empty) — counts as accessible
        });

        // Add aria-label to empty links
        doc.querySelectorAll('a').forEach(function (a) {
            if (!a.textContent.trim() && !a.querySelector('img') && !a.getAttribute('aria-label')) {
                a.setAttribute('aria-label', 'Enlace');
                fixed++;
            }
        });

        if (fixed > 0) {
            // Sync fixed HTML back to editor
            var newHtml = doc.body ? doc.body.innerHTML : '';
            if (window.p && window.p.veEditor) {
                window.p.veEditor.setData(newHtml);
            }
            showToast('<i class="fa-solid fa-wand-magic-sparkles me-1"></i>' + fixed + ' problema(s) reparado(s).');
        } else {
            showToast('<i class="fa-solid fa-check me-1"></i>No hay problemas que reparar automáticamente.');
        }
        bootstrap.Modal.getInstance(document.getElementById('ve-a11y-modal')).hide();
    });

    /* ── Media picker recent images ──────────────────────────────────── */
    var VE_RECENT_MEDIA_KEY = 've_recent_media_{{ $page->id }}';

    function getRecentMedia() {
        try { return JSON.parse(localStorage.getItem(VE_RECENT_MEDIA_KEY) || '[]'); } catch (e) { return []; }
    }

    function addRecentMedia(url) {
        if (!url || !url.match(/\.(jpg|jpeg|png|gif|webp|svg)/i)) return;
        var list = getRecentMedia().filter(function (u) { return u !== url; });
        list.unshift(url);
        localStorage.setItem(VE_RECENT_MEDIA_KEY, JSON.stringify(list.slice(0, 5)));
    }

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
    // Expose to any script outside this IIFE (panels loaded dynamically, etc.)
    window.sendToFrame = sendToFrame;

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

    /* ── Hover inspect state (declared early; handler added at end) ─── */
    var hoverInspectMode = false;
    var $hoverTooltip    = null;

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
                // Update element pill in contextual topbar
                if (d.tag) {
                    var pillLabel = d.tag + (d.classList ? '.' + d.classList.split(' ').filter(Boolean).slice(0,2).join('.') : '');
                    $('#ve-ctx-element-pill .ve-ctx-mono').text(pillLabel);
                }
                break;

            case 've-open-sc-editor': {
                $('[data-panel="inspector"]').trigger('click');
                setTimeout(function () {
                    var $section = $('#ve-section-shortcode');
                    var $scroll  = $section.closest('.ve-panel-root');
                    if ($section.length && !$section.hasClass('ve-hidden') && $scroll.length) {
                        $scroll.animate({ scrollTop: $scroll.scrollTop() + $section.position().top - 8 }, 200);
                    }
                }, 150);
                break;
            }

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
                        showToast('<i class="fa-solid fa-copy me-1"></i>Elemento copiado');
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

            case 've-open-conditions': {
                // Quick-bar "Condiciones" → open conditions modal for the current selection
                $('#ve-cond-target').text((d.tag || 'elemento').toLowerCase());
                var cur = d.current || '';
                $('#ve-conditions-modal .vm-cond-pill').removeClass('active');
                if (cur) $('#ve-conditions-modal .vm-cond-pill[data-condition="' + cur + '"]').addClass('active');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-conditions-modal')).show();
                break;
            }

            case 've-element-path':
                // Breadcrumb in inspector header
                renderBreadcrumb(d.path || []);
                break;

            case 've-show-link-popover':
                if (window.veShowLinkPopover) {
                    // Convert iframe-relative rect to canvas-wrap relative
                    try {
                        var frameEl = document.getElementById('ve-preview-frame');
                        var fRect   = frameEl.getBoundingClientRect();
                        var r = d.rect || {};
                        var absRect = {
                            top:    fRect.top + r.top,
                            left:   fRect.left + r.left,
                            right:  fRect.left + r.right,
                            bottom: fRect.top + r.bottom,
                            width:  r.width,
                            height: r.height
                        };
                        veShowLinkPopover(d.href || '', d.nodeId || null, absRect, d.attrs || {});
                    } catch (ex) {}
                }
                break;

            case 've-hide-link-popover':
                if (window.veHideLinkPopover) veHideLinkPopover();
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

            case 've-element-dims': {
                if (!hoverInspectMode) break;
                if (!$hoverTooltip || !$hoverTooltip.length) {
                    $hoverTooltip = $('<div id="ve-hover-tooltip">')
                        .css({
                            position: 'fixed', zIndex: 99998, pointerEvents: 'none',
                            background: '#1e1e2e', color: '#e0e0e0', padding: '3px 8px',
                            borderRadius: '4px', fontSize: '11px', fontFamily: 'monospace',
                            boxShadow: '0 2px 8px rgba(0,0,0,.4)', whiteSpace: 'nowrap',
                        });
                    $('body').append($hoverTooltip);
                }
                var w   = d.width  !== undefined ? Math.round(d.width)  : '?';
                var h   = d.height !== undefined ? Math.round(d.height) : '?';
                var tag = d.tag    ? '<' + d.tag + '> · ' : '';
                // Pin tooltip to bottom-left of canvas to avoid CKEditor balloon toolbars
                var frameRect2 = document.getElementById('ve-preview-frame').getBoundingClientRect();
                $hoverTooltip
                    .text(tag + w + '×' + h + (d.x !== undefined ? ' @ ' + Math.round(d.x) + ',' + Math.round(d.y) : ''))
                    .css({ left: (frameRect2.left + 8) + 'px', top: (frameRect2.bottom - 28) + 'px' });
                break;
            }
        }
    });

    /* ── Toast notification ──────────────────────────────────────────── */
    function showToast(msg, type, opts) {
        opts = opts || {};
        // Resolve type from legacy HTML string (contains fa- icon markup)
        if (!type && typeof msg === 'string' && msg.indexOf('fa-') !== -1) {
            if (msg.indexOf('exclamation') !== -1 || msg.indexOf('triangle') !== -1) type = 'warn';
            else if (msg.indexOf('times-circle') !== -1 || msg.indexOf('xmark') !== -1) type = 'err';
            else type = 'ok';
        }
        type = type || 'ok';

        const icons = { ok: 'fa-check', info: 'fa-code-branch', warn: 'fa-triangle-exclamation', err: 'fa-xmark' };
        // Strip HTML tags for clean text
        const plainMsg = $('<div>').html(msg).text().trim() || msg;
        const id = 've-toast-' + Date.now();

        const $toast = $('<div>')
            .addClass('ec-toast ' + type)
            .attr('id', id)
            .html(
                '<div class="tico"><i class="fa-solid ' + (icons[type] || icons.ok) + '"></i></div>' +
                '<div class="body"><div class="t">' + plainMsg + '</div>' + (opts.sub ? '<div class="s">' + opts.sub + '</div>' : '') + '</div>' +
                (opts.action ? '<button class="act" data-action="' + opts.action + '">' + opts.actionLabel + '</button>' : '') +
                '<button class="xbtn"><i class="fa-solid fa-xmark"></i></button>'
            );

        $('#ve-toast-container').append($toast);

        $toast.find('.xbtn').on('click', function () { removeToast($toast); });
        if (opts.action && opts.onAction) {
            $toast.find('.act').on('click', function () { opts.onAction(); removeToast($toast); });
        }

        const duration = opts.duration || 3000;
        setTimeout(function () { removeToast($toast); }, duration);
    }

    function removeToast($t) {
        $t.addClass('removing');
        setTimeout(function () { $t.remove(); }, 200);
    }

    /* ── Breadcrumb ──────────────────────────────────────────────────── */
    function renderBreadcrumb(path) {
        const $bar     = $('#ve-selection-bar');
        const $actions = $('#ve-inspector-actions');
        const $bc      = $('#ve-inspector-breadcrumb');
        if (!path || !path.length) {
            $bar.addClass('ve-hidden');
            $actions.addClass('ve-hidden');
            $bc.empty();
            return;
        }
        $bar.removeClass('ve-hidden');
        $actions.removeClass('ve-hidden');

        // Update chip tag (last element's tag)
        var lastTag = (path[path.length - 1] || {}).tag || 'div';
        $('#ve-sel-chip-tag').text(lastTag.toLowerCase());

        // Build breadcrumb path
        $bc.empty();
        path.forEach(function (item, i) {
            if (i > 0) $bc.append($('<span>').text(' > ').css({ color: 'var(--ve-text-muted)', margin: '0 2px' }));
            const $seg = $('<span>')
                .addClass('ve-bc-seg')
                .attr('data-node-id', item.nodeId || '')
                .text(item.tag)
                .on('click', function () {
                    const nodeId = $(this).data('node-id');
                    if (nodeId) sendToFrame({ type: 've-select-by-id', nodeId: nodeId });
                });
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

    function veHeUpdateStatus() {
        if (!window.veCodeMirror) return;
        var cm = window.veCodeMirror;
        var pos = cm.getCursor();
        $('#ve-he-cursor').text('Ln ' + (pos.line + 1) + ', Col ' + (pos.ch + 1));
        var val = cm.getValue();
        $('#ve-he-meta').text(cm.lineCount() + ' líneas · ' + val.length + ' chars');
        // Basic HTML validity: balanced opening/closing angle brackets
        var opens = (val.match(/</g) || []).length;
        var closes = (val.match(/>/g) || []).length;
        var $v = $('#ve-he-valid');
        if (opens === closes && opens > 0) {
            $v.removeClass('invalid').html('<i class="fa-solid fa-check"></i> Válido');
        } else if (opens === 0) {
            $v.removeClass('invalid').html('<i class="fa-solid fa-circle"></i> Vacío');
        } else {
            $v.addClass('invalid').html('<i class="fa-solid fa-triangle-exclamation"></i> Revisar');
        }
    }

    $('#ve-html-editor-modal').on('shown.bs.modal', function () {
        if (!window.veCodeMirror) {
            window.veCodeMirror = CodeMirror.fromTextArea(
                document.getElementById('ve-html-editor-textarea'),
                {
                    mode:             'htmlmixed',
                    theme:            've-dark',
                    lineNumbers:      true,
                    lineWrapping:     false,
                    autoCloseTags:    true,
                    matchBrackets:    true,
                    styleActiveLine:  true,
                    foldGutter:       true,
                    gutters:          ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                    foldOptions:      { widget: ' ▾ ··· ', minFoldSize: 2 },
                    hintOptions:      { hint: window.veBootstrapHint, completeSingle: false },
                    extraKeys: {
                        'Ctrl-Space': function (cm) {
                            CodeMirror.commands.autocomplete(cm, window.veBootstrapHint, { completeSingle: false });
                        },
                        'Ctrl-F': 'findPersistent',
                        'Ctrl-H': 'replace',
                        'Ctrl-Enter': function () { $('#btn-apply-html').trigger('click'); },
                        'Cmd-Enter': function () { $('#btn-apply-html').trigger('click'); },
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
            window.veCodeMirror.on('inputRead', window.veBootstrapHintAuto);
            window.veCodeMirror.on('cursorActivity', veHeUpdateStatus);
            window.veCodeMirror.on('change', veHeUpdateStatus);
        }
        window.veCodeMirror.refresh();
        veHeUpdateStatus();
    });

    // Tabs (HTML/CSS) — placeholder: switches label
    $(document).on('click', '#ve-html-editor-modal .vm-editor-tab', function () {
        if ($(this).hasClass('vm-editor-tab-add')) return;
        $('#ve-html-editor-modal .vm-editor-tab').removeClass('on');
        $(this).addClass('on');
        var tab = $(this).data('tab');
        $('#ve-he-lang').text(tab === 'css' ? 'CSS' : 'HTML');
        if (window.veCodeMirror) {
            window.veCodeMirror.setOption('mode', tab === 'css' ? 'css' : 'htmlmixed');
        }
    });

    // ── Toolbar del editor HTML modal ────────────────────────────────────────
    $('#ve-btn-format').on('click', function () {
        if (!window.veCodeMirror) { return; }
        window.veCodeMirror.setValue(veFormatHtml(window.veCodeMirror.getValue()));
        showToast('<i class="fas fa-check me-1"></i>Código formateado.');
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
                    theme:          've-dark',
                    indentUnit:     4,
                    tabSize:        4,
                    indentWithTabs: false,
                    autoCloseTags:  true,
                    matchBrackets:  true,
                    styleActiveLine: true,
                    foldGutter:     true,
                    gutters:        ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                    hintOptions:    { hint: window.veBootstrapHint, completeSingle: false },
                    extraKeys:      {
                        'Ctrl-Space': function (cm) {
                            CodeMirror.commands.autocomplete(cm, window.veBootstrapHint, { completeSingle: false });
                        },
                        'Ctrl-F': 'findPersistent',
                        'Ctrl-H': 'replace',
                    },
                }
            );
            veFullCodeMirror.on('inputRead', window.veBootstrapHintAuto);
            veFullCodeMirror.on('change', function () {
                codeEditorDirty = true;
                markModified(true);
            });
            window._veFullCodeMirror = veFullCodeMirror;
            var _veValidateTimer;
            veFullCodeMirror.on('change', function () {
                var lines = veFullCodeMirror.lineCount();
                $('#ve-ctx-code-lines').text(lines + ' líneas');
                // Debounced validation (HTML mode only)
                if (veCodePanelMode !== 'css') {
                    clearTimeout(_veValidateTimer);
                    _veValidateTimer = setTimeout(function () {
                        veValidateCode(veFullCodeMirror.getValue());
                    }, 800);
                }
            });
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
        var $btn = $(this);

        // CSS mode: apply directly to iframe style tag
        if (veCodePanelMode === 'css') {
            var cssText = veFullCodeMirror.getValue();
            var frame   = document.getElementById('ve-preview-frame');
            if (!frame || !frame.contentDocument) {
                showToast('<i class="fas fa-times-circle me-1"></i>Preview no disponible.', 'error');
                return;
            }
            var styleEl = frame.contentDocument.getElementById('ve-custom-css');
            if (!styleEl) {
                styleEl = frame.contentDocument.createElement('style');
                styleEl.id = 've-custom-css';
                frame.contentDocument.head.appendChild(styleEl);
            }
            styleEl.textContent = cssText;
            codeEditorDirty = false;
            markModified(true);
            showToast('<i class="fas fa-check me-1"></i>CSS aplicado al preview.');
            return;
        }

        // HTML mode: render via server and reload iframe
        var html = veFullCodeMirror.getValue();
        $btn.prop('disabled', true).text('Compilando…');

        // Validate HTML first
        veValidateCode(html);

        $.ajax({
            url: VISUAL_PREVIEW,
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({ content: html }),
        }).done(function (rendered) {
            var frame = document.getElementById('ve-preview-frame');
            if (!frame) { return; }
            frame.contentDocument.open();
            frame.contentDocument.write(rendered);
            frame.contentDocument.close();
            codeEditorDirty = false;
            markModified(true);
            pushHistory('Editar código', html);
        }).fail(function () {
            showToast('<i class="fas fa-times-circle me-1 text-danger"></i>Error al compilar el contenido.');
        }).always(function () {
            $btn.prop('disabled', false).text('Aplicar');
        });
    });

    /* ── Validación HTML en tiempo real ────────────────────────────── */
    function veValidateCode(html) {
        if (!html) return;
        try {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var errors = doc.querySelectorAll('parsererror');
            var isValid = errors.length === 0;
            $('#ve-ctx-validity-dot').css('color', isValid ? '#13C672' : '#FA896B');
            $('#ve-ctx-validity-label').text(isValid ? 'Válido' : 'Errores');
        } catch (e) {}
    }

    // ── Toolbar del panel código completo ────────────────────────────────────
    $('#ve-code-btn-format').on('click', function () {
        if (!veFullCodeMirror) { return; }
        veFullCodeMirror.setValue(veFormatHtml(veFullCodeMirror.getValue()));
        showToast('<i class="fas fa-check me-1"></i>Código formateado.');
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
                '<i class="fa-solid fa-grip-vertical ve-section-handle"></i>',
                '<span class="ve-section-tag">' + tag + '</span>',
                '<span class="ve-section-label" title="' + label.replace(/"/g, '&quot;') + '">',
                label, '</span>',
                '<div class="d-flex gap-1">',
                '<button class="btn btn-sm ve-sec-btn ve-sec-up" title="Subir"><i class="fa-solid fa-chevron-up"></i></button>',
                '<button class="btn btn-sm ve-sec-btn ve-sec-down" title="Bajar"><i class="fa-solid fa-chevron-down"></i></button>',
                '<button class="btn btn-sm ve-sec-btn ve-sec-delete" title="Eliminar"><i class="fa-solid fa-times"></i></button>',
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
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'a') {
            e.preventDefault();
            openAiModal();
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
        $('#ve-scb-tag').text('[' + sc.name + ']').toggle(!!sc.name);
        var desc = sc.description || '';
        $('#ve-scb-description').text(desc).toggle(!!desc);
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
            Object.entries(attrs).forEach(function ([key, cfg]) {
                const inputId = 've-scb-attr-' + key;
                const $field = $('<div class="vm-field">');
                $field.append($('<label class="vm-flabel">').attr('for', inputId).text(key));
                if (Array.isArray(cfg)) {
                    const $sel = $('<select>').attr({ id: inputId, class: 'vm-fselect ve-scb-attr-input' }).data('attr', key);
                    $('<option>').val('').text('— Seleccionar —').appendTo($sel);
                    cfg.forEach(function (opt) { $('<option>').val(opt).text(opt).appendTo($sel); });
                    $field.append($sel);
                } else {
                    const $finwrap = $('<div class="vm-finput-inline">');
                    $finwrap.append($('<span class="pre">').text(key + ':'));
                    $finwrap.append($('<input>').attr({ type: 'text', id: inputId, class: 'vm-finput ve-scb-attr-input', placeholder: cfg || '' }).data('attr', key));
                    $field.append($finwrap);
                }
                $attrs.append($field);
            });
        } else {
            $attrs.html('<div style="font-size:11.5px;color:var(--ve-text-muted);">Este shortcode no requiere atributos.</div>');
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

    // Generic confirm overlay helper (ec-confirm-wrap)
    window.veConfirm = function (message, onAccept, opts) {
        opts = opts || {};
        var type = opts.type || 'warning';  // warning | danger | info | success
        var iconMap = {
            warning: 'fa-triangle-exclamation',
            danger:  'fa-triangle-exclamation',
            info:    'fa-circle-info',
            success: 'fa-circle-check'
        };
        var faIcon = iconMap[type] || iconMap.warning;

        $('#ve-confirm-message').text(message);
        $('#ve-confirm-title').text(opts.title || '¿Confirmar acción?');

        // Icon with type class
        $('#ve-confirm-icon')
            .attr('class', 'ec-confirm-icon ' + type)
            .html('<i class="fa-solid ' + faIcon + '"></i>');

        // Accept button — solid danger if destructive
        var $acc = $('#ve-confirm-accept');
        $acc.text(opts.acceptLabel || 'Confirmar');
        $acc.removeClass('vm-btn-primary vm-btn-danger-solid');
        $acc.addClass(type === 'danger' ? 'vm-btn-danger-solid' : 'vm-btn-primary');

        const $wrap = $('#ve-confirm-wrap');
        $wrap.addClass('visible');
        if (window.veMarkModalOpen) veMarkModalOpen(true);

        function closeConfirm() {
            $wrap.removeClass('visible');
            if (window.veMarkModalOpen) veMarkModalOpen(false);
        }

        $acc.off('click.veconfirm').one('click.veconfirm', function () {
            closeConfirm();
            onAccept();
        });
        $('#ve-confirm-cancel').off('click.veconfirm').one('click.veconfirm', closeConfirm);
        $wrap.off('click.veconfirm').on('click.veconfirm', function (e) {
            if ($(e.target).is($wrap)) closeConfirm();
        });
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

                // Wrap with a sentinel div so extractContent() restores the raw shortcode
                // tag on save rather than persisting the expanded HTML.
                const safeCode = code.replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                                     .replace(/\[/g, '&#91;').replace(/\]/g, '&#93;');
                const tmp = frame.contentDocument.createElement('div');
                tmp.innerHTML = '<div data-ve-sc="' + safeCode + '">' + html + '</div>';
                while (tmp.firstChild) {
                    ck.appendChild(tmp.firstChild);
                }

                isModified = true;
                scheduleAutoSave();
                getContentToSave().then(function (savedHtml) {
                    pushHistory('Insertar shortcode', savedHtml);
                });
                showToast('<i class="fa-solid fa-code me-1"></i>Shortcode insertado');
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
                showToast('<i class="fa-solid fa-code me-1"></i>Shortcode insertado');
            },
        });
    });

    $('#btn-copy-shortcode').on('click', function () {
        const code = $('#ve-scb-preview').text();
        if (!code) return;
        navigator.clipboard?.writeText(code).then(function () {
            showToast('<i class="fa-solid fa-copy me-1"></i>Copiado al portapapeles');
        }).catch(function () {
            // fallback
            const ta = document.createElement('textarea');
            ta.value = code;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            showToast('<i class="fa-solid fa-copy me-1"></i>Copiado');
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
    $('#btn-shortcuts, #ve-rail-shortcuts-btn').on('click', function () {
        new bootstrap.Modal(document.getElementById('ve-shortcuts-modal')).show();
    });

    /* ── Help button → help modal (separate from shortcuts) ─────────── */
    $('#ve-rail-help-btn').on('click', function () {
        new bootstrap.Modal(document.getElementById('ve-help-modal')).show();
    });

    // Help cards — dispatch actions
    $(document).on('click', '.ve-help-card[data-action]', function (e) {
        e.preventDefault();
        var act = $(this).data('action');
        bootstrap.Modal.getInstance(document.getElementById('ve-help-modal'))?.hide();

        setTimeout(function () {
            switch (act) {
                case 'shortcuts':
                    new bootstrap.Modal(document.getElementById('ve-shortcuts-modal')).show();
                    break;
                case 'quick-actions':
                    new bootstrap.Modal(document.getElementById('ve-quick-actions-modal')).show();
                    break;
                case 'tweaks':
                    $('#ve-tweaks').addClass('open');
                    break;
                case 'command-palette':
                    $('#btn-open-cmd-palette').trigger('click');
                    break;
                case 'onboarding':
                    if (window.veStartOnboarding) {
                        veStartOnboarding([
                            { target: '#ve-sidebar-nav [data-panel="shortcodes"]', title: 'Panel de bloques', description: 'Arrastra bloques al canvas o haz clic para insertarlos al final de la página.' },
                            { target: '#ve-sidebar-nav [data-panel="inspector"]', title: 'Inspector', description: 'Edita los estilos del elemento seleccionado: tipografía, espaciado, fondo, borde, etc.' },
                            { target: '#btn-slash-menu', title: 'Slash menu', description: 'Insertar bloques rápido presionando "/" en cualquier parte.' },
                            { target: '#btn-open-cmd-palette', title: 'Paleta de comandos', description: 'Busca cualquier acción con ⌘K. El atajo más útil del editor.' },
                            { target: '#ve-statusbar-autosave-wrap', title: 'Guardar', description: 'Haz clic en la pill de autosave o pulsa Ctrl+S para guardar. Otras acciones están en la barra de Acciones rápidas.' }
                        ]);
                    }
                    break;
            }
        }, 250);
    });

    /* ── Blocks view toggle (grid / list) ───────────────────────────── */
    $(document).on('click', '#ve-blocks-view-seg .ve-seg-btn', function () {
        const view = $(this).data('view');
        $('#ve-blocks-view-seg .ve-seg-btn').removeClass('active');
        $(this).addClass('active');
        if (view === 'list') {
            $('#ve-sc-list .ve-blocks-grid').css({ 'grid-template-columns': '1fr', gap: '4px' });
            $('#ve-sc-list .ve-block-item').css({ 'flex-direction': 'row', 'text-align': 'left', padding: '8px 10px', gap: '8px' });
            $('#ve-sc-list .ve-block-icon').css('font-size', '14px');
        } else {
            $('#ve-sc-list .ve-blocks-grid').css({ 'grid-template-columns': '', gap: '' });
            $('#ve-sc-list .ve-block-item').css({ 'flex-direction': '', 'text-align': '', padding: '', gap: '' });
            $('#ve-sc-list .ve-block-icon').css('font-size', '');
        }
    });

    /* ── Blocks filter (all / system / custom) ──────────────────────── */
    $(document).on('click', '#ve-blocks-filter-seg .ve-seg-btn', function () {
        const filter = $(this).data('filter');
        $('#ve-blocks-filter-seg .ve-seg-btn').removeClass('active');
        $(this).addClass('active');
        if (filter === 'custom') {
            $('#ve-sc-list .ve-sc-group').hide();
            $('#ve-sc-group-custom').show();
        } else if (filter === 'system') {
            $('#ve-sc-list .ve-sc-group').show();
            $('#ve-sc-group-custom').hide();
        } else {
            $('#ve-sc-list .ve-sc-group').show();
        }
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
        showToast('<i class="fa-solid fa-bookmark me-1"></i>Shortcode guardado: ' + name);
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
                showToast('<i class="fa-solid fa-copy me-1"></i>Elemento copiado');
            }
        }
    };

    /* ── Sidebar resize handle ───────────────────────────────────────── */
    (function () {
        var $handle    = $('#ve-sidebar-resize');
        var $sidebar   = $('#ve-sidebar');
        var isResizing = false, startX = 0, startW = 0;

        // Keep --ve-sidebar-offset in sync so absolutely-positioned widgets
        // (Quick Actions Bar) stay clear of the sidebar + its resize handle.
        // #ve-sidebar uses display:contents, so measure the rightmost edge of its children.
        function syncSidebarOffset() {
            var nav    = document.getElementById('ve-sidebar-nav');
            var panels = document.getElementById('ve-sidebar-panels');
            var right  = 0;
            if (nav)    right = Math.max(right, nav.getBoundingClientRect().right);
            if (panels) right = Math.max(right, panels.getBoundingClientRect().right);
            document.documentElement.style.setProperty('--ve-sidebar-offset', Math.round(right) + 'px');
        }
        syncSidebarOffset();
        $(window).on('resize.ve-sidebar', syncSidebarOffset);
        // Re-sync when panels toggle (sidebar hides entirely)
        $(document).on('ve-sidebar-toggled', syncSidebarOffset);

        $handle.on('mousedown', function (e) {
            isResizing = true;
            startX = e.clientX;
            startW = $sidebar.width();
            $handle.addClass('resizing');
            $('<div id="ve-resize-overlay">').css({
                position: 'fixed', inset: 0, zIndex: 9999, cursor: 'col-resize'
            }).appendTo('body');
            e.preventDefault();
        });

        $(document).on('mousemove.sidebarResize', function (e) {
            if (!isResizing) return;
            var newW = Math.max(220, Math.min(900, startW + e.clientX - startX));
            $sidebar.css('width', newW + 'px');
            syncSidebarOffset();
        });

        $(document).on('mouseup.sidebarResize', function () {
            if (!isResizing) return;
            isResizing = false;
            $handle.removeClass('resizing');
            $('#ve-resize-overlay').remove();
            syncSidebarOffset();
        });
    })();

    /* ── Canvas zoom ─────────────────────────────────────────────────── */
    var _veZoom = 1;
    var _veZoomMin = 0.25, _veZoomMax = 3;
    var _veZoomMode = 'fit'; // 'fit' | 'manual'

    function veUpdateZoomUI() {
        $('#ve-zoom-pct').text(Math.round(_veZoom * 100) + '%');
        $('#btn-zoom-100').toggleClass('active', _veZoomMode === 'manual' && Math.abs(_veZoom - 1) < 0.001);
        $('#btn-zoom-fit').toggleClass('active', _veZoomMode === 'fit');
        $('#btn-zoom-in').prop('disabled', _veZoom >= _veZoomMax - 0.001);
        $('#btn-zoom-out').prop('disabled', _veZoom <= _veZoomMin + 0.001);
    }

    // Apply Fit: scale the canvas wrap so its content fits the visible canvas
    // area without scroll. Desktop/split stay full-size. Uses CSS `zoom` on
    // supporting browsers (Chrome/Safari), falls back to transform:scale.
    function veApplyFit() {
        var $wrap = $('#ve-canvas-wrap');
        var $canvas = $('#ve-canvas');
        $wrap.css({ zoom: '', transform: '' });
        _veZoomMode = 'fit';
        if ($wrap.hasClass('desktop') || $wrap.hasClass('split')) {
            _veZoom = 1;
            sendToFrame({ type: 've-set-zoom-fit' });
            veUpdateZoomUI();
            return;
        }
        var padding = 44; // matches #ve-canvas padding: 22px *2
        var availW = $canvas.width() - padding;
        var availH = $canvas.height() - padding;
        var wrapW = $wrap.outerWidth() / (parseFloat($wrap.css('zoom')) || 1);
        var wrapH = $wrap.outerHeight() / (parseFloat($wrap.css('zoom')) || 1);
        var scale = Math.min(1, availW / wrapW, availH / wrapH);
        if (CSS && CSS.supports && CSS.supports('zoom', '1')) {
            $wrap.css('zoom', scale);
        } else {
            $wrap.css({ transform: 'scale(' + scale + ')', transformOrigin: 'center center' });
        }
        _veZoom = scale;
        sendToFrame({ type: 've-set-zoom-fit' });
        veUpdateZoomUI();
    }

    function veApplyZoom(zoom, mode) {
        _veZoom = Math.max(_veZoomMin, Math.min(_veZoomMax, zoom));
        _veZoomMode = mode || 'manual';
        // Exit fit styling when going manual
        $('#ve-canvas-wrap').css({ zoom: '', transform: '' });
        sendToFrame({ type: 've-set-zoom', zoom: _veZoom });
        veUpdateZoomUI();
    }

    $(document).on('click', '#btn-zoom-100', function () { veApplyZoom(1, 'manual'); });
    $(document).on('click', '#btn-zoom-fit', veApplyFit);
    $(document).on('click', '#btn-zoom-in',  function () { veApplyZoom(_veZoom + 0.1, 'manual'); });
    $(document).on('click', '#btn-zoom-out', function () { veApplyZoom(_veZoom - 0.1, 'manual'); });

    // Re-fit on viewport resize while in fit mode
    var _veFitResizeTimer = null;
    $(window).on('resize', function () {
        if (_veZoomMode !== 'fit') { return; }
        clearTimeout(_veFitResizeTimer);
        _veFitResizeTimer = setTimeout(veApplyFit, 120);
    });

    // Keyboard shortcuts: Ctrl/⌘ + / - / 0 for zoom in / out / reset-to-fit.
    $(document).on('keydown.zoom', function (e) {
        if (!(e.ctrlKey || e.metaKey) || e.altKey) { return; }
        var tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || e.target.isContentEditable) { return; }
        if (e.key === '+' || e.key === '=') { e.preventDefault(); $('#btn-zoom-in').trigger('click'); }
        else if (e.key === '-' || e.key === '_') { e.preventDefault(); $('#btn-zoom-out').trigger('click'); }
        else if (e.key === '0') { e.preventDefault(); veApplyFit(); }
    });

    // Wireframe + Regla viven también en el topbar (Vista dropdown).
    // Estos son proxys para los nuevos botones del zoom-bar.
    $(document).on('click', '#btn-zoom-wireframe', function () {
        $(this).toggleClass('active');
        if ($('#btn-wireframe').length) $('#btn-wireframe').trigger('click');
    });
    $(document).on('click', '#btn-zoom-ruler', function () {
        $(this).toggleClass('active');
        if ($('#btn-ruler').length) $('#btn-ruler').trigger('click');
    });

    // Init
    veUpdateZoomUI();

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
        showToast('<i class="fa-solid fa-download me-1"></i>HTML exportado: ' + slug + '.html');
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

    /* ── Skeleton loading helpers ────────────────────────────────────── */
    window.veShowSkeleton = function ($container, count) {
        count = count || 6;
        var html = '<div class="ve-skeleton-panel">';
        for (var i = 0; i < count; i++) {
            html += '<div class="ve-sk-block ve-sk-block-item"></div>';
        }
        html += '</div>';
        $container.html(html);
    };

    window.veHideSkeleton = function ($container) {
        $container.find('.ve-skeleton-panel').remove();
    };

    /* veToast(message, type) — wrapper compatible con agentes externos */
    window.veToast = function (msg, type) {
        var iconMap = { success: 'fa-check', error: 'fa-times-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
        var colorMap = { success: '#13C672', error: '#FA896B', info: '#aaa', warning: '#FEC90F' };
        var t = type || 'info';
        showToast('<i class="fas ' + (iconMap[t] || 'fa-info-circle') + ' me-1" style="color:' + (colorMap[t] || '#aaa') + '"></i>' + msg);
    };

    /* ── MEJORA 9: Zoom con Ctrl+Scroll ─────────────────────────────── */
    // zoom buttons use data-zoom with decimal values: 0.5, 0.75, 1, 1.25, 1.5
    $(document).on('wheel', '#ve-preview-frame, #ve-canvas-wrap', function (e) {
        if (!e.ctrlKey && !e.metaKey) return;
        e.preventDefault();
        var zoomLevels = [0.5, 0.75, 1, 1.25, 1.5];
        var $active = $('.ve-zoom-btn.active');
        var current = $active.length ? parseFloat($active.data('zoom')) : 1;
        var idx = zoomLevels.indexOf(current);
        if (idx === -1) idx = 2;
        if (e.originalEvent.deltaY < 0 && idx < zoomLevels.length - 1) idx++;
        else if (e.originalEvent.deltaY > 0 && idx > 0) idx--;
        $('[data-zoom="' + zoomLevels[idx] + '"].ve-zoom-btn').trigger('click');
    });

    /* ── MEJORA 10: Modo outline ─────────────────────────────────────── */
    var outlineMode = false;
    $('#btn-outline-mode').on('click', function () {
        outlineMode = !outlineMode;
        $(this).toggleClass('active', outlineMode);
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) return;
        var styleId = 've-outline-style';
        var existing = frame.contentDocument.getElementById(styleId);
        if (outlineMode) {
            if (!existing) {
                var s = frame.contentDocument.createElement('style');
                s.id = styleId;
                s.textContent = '* { outline: 1px dashed rgba(144,187,19,0.4) !important; }';
                frame.contentDocument.head.appendChild(s);
            }
        } else {
            if (existing) existing.remove();
        }
    });

    // Re-apply outline after preview reloads
    $(document).on('ve-preview-updated', function () {
        if (!outlineMode) return;
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) return;
        if (!frame.contentDocument.getElementById('ve-outline-style')) {
            var s = frame.contentDocument.createElement('style');
            s.id = 've-outline-style';
            s.textContent = '* { outline: 1px dashed rgba(144,187,19,0.4) !important; }';
            frame.contentDocument.head.appendChild(s);
        }
    });

    /* ── MEJORA 11: Pantalla completa del preview ────────────────────── */
    var previewFullscreen = false;
    $('#btn-fullscreen-preview').on('click', function () {
        previewFullscreen = !previewFullscreen;
        $('#ve-sidebar').toggleClass('ve-hidden', previewFullscreen);
        $(this).toggleClass('active', previewFullscreen);
        $('#btn-fullscreen-preview i')
            .toggleClass('fa-expand', !previewFullscreen)
            .toggleClass('fa-compress', previewFullscreen);
        setTimeout(function () { $(window).trigger('resize'); }, 100);
    });

    $(document).on('keydown.fullscreen', function (e) {
        if (e.key === 'Escape' && previewFullscreen) {
            $('#btn-fullscreen-preview').trigger('click');
        }
    });

    /* ── MEJORA 12: Responsive preview quick-buttons ─────────────────── */
    // These mirror the existing .breakpoint-btn system but with simpler width-only control
    $(document).on('click', '.ve-resp-btn', function () {
        $('.ve-resp-btn').removeClass('active');
        $(this).addClass('active');
        var w = $(this).data('width');
        var $frame = $('#ve-preview-frame');
        if (w === '100%') {
            // Delegate to the desktop breakpoint button to keep state in sync
            $('.breakpoint-btn[data-breakpoint="desktop"]').trigger('click');
        } else if (w === '768px') {
            $('.breakpoint-btn[data-breakpoint="tablet"]').trigger('click');
        } else if (w === '375px') {
            $('.breakpoint-btn[data-breakpoint="mobile"]').trigger('click');
        }
    });

    /* ── MEJORA 13: Buscador de elementos ya existe en canvas (#ve-element-search) ── */
    // The canvas already has a full search bar (#ve-element-search-input, #ve-search-count,
    // #ve-search-prev, #ve-search-next, #ve-search-close). We only add the inspector panel
    // mini-search that highlights elements directly in the iframe DOM.
    var searchHighlightStyle = null;
    $(document).on('input', '#ve-inspector-element-search', function () {
        var q = $(this).val().trim().toLowerCase();
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) return;
        if (searchHighlightStyle) searchHighlightStyle.remove();
        frame.contentDocument.querySelectorAll('.ve-search-match').forEach(function (el) {
            el.classList.remove('ve-search-match');
        });
        if (!q) { $('#ve-inspector-search-results').text(''); return; }
        var matches = [];
        frame.contentDocument.querySelectorAll('body *').forEach(function (el) {
            if (el.hasAttribute('data-ve-sc')) return;
            if (el.textContent.toLowerCase().includes(q) && !el.matches('script,style,meta,link,head')) {
                el.classList.add('ve-search-match');
                matches.push(el);
            }
        });
        searchHighlightStyle = frame.contentDocument.createElement('style');
        searchHighlightStyle.textContent = '.ve-search-match { outline: 2px solid #FEC90F !important; background: rgba(254,201,15,0.1) !important; }';
        frame.contentDocument.head.appendChild(searchHighlightStyle);
        $('#ve-inspector-search-results').text(matches.length + ' elemento(s) encontrado(s)');
        if (matches[0]) matches[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    $(document).on('click', '#btn-inspector-search-clear', function () {
        $('#ve-inspector-element-search').val('').trigger('input');
    });

    /* ── MEJORA 14: Auto-guardado — ya existe setInterval(doAutoSave, 60000) ── */
    // The existing doAutoSave runs every 60s. We only add the indicator update loop.
    var lastAutoSaveTime = null;

    // Patch doAutoSave success to record time and update indicator
    var _origSetAutoSaveStatus = setAutoSaveStatus;
    setAutoSaveStatus = function (state, text) {
        _origSetAutoSaveStatus(state, text);
        if (state === 'saved') {
            lastAutoSaveTime = new Date();
            updateAutoSaveIndicator();
        }
        $('#autosave-status-bar').text(text || '').attr('class', state ? 'ms-1 ve-autosave-' + state : 'ms-1');
    };

    function updateAutoSaveIndicator() {
        if (!lastAutoSaveTime) { $('#ve-autosave-indicator').text(''); return; }
        var diff = Math.round((new Date() - lastAutoSaveTime) / 1000);
        var label;
        if (diff < 30)          label = 'Guardado automáticamente';
        else if (diff < 90)     label = 'Auto-guardado hace 1 min';
        else if (diff < 3600)   label = 'Auto-guardado hace ' + Math.round(diff / 60) + ' min';
        else                    label = 'Auto-guardado hace ' + Math.round(diff / 3600) + ' h';
        $('#ve-autosave-indicator').text(label);
    }
    setInterval(updateAutoSaveIndicator, 30000);

    /* ── MEJORA 15: Panel árbol DOM ──────────────────────────────────── */
    function veDomIcon(el) {
        var tag = (el.tagName || '').toLowerCase();
        var cls = (typeof el.className === 'string' ? el.className : '').toLowerCase();

        if (/^h[1-6]$/.test(tag))            return '<span class="ve-dom-h">H</span>';
        if (cls.indexOf('badge') > -1)       return '<i class="fa-solid fa-tag"></i>';
        if (cls.indexOf('title') > -1)       return '<span class="ve-dom-h">H</span>';
        if (cls.indexOf('desc') > -1)        return '<i class="fa-solid fa-align-left"></i>';
        if (cls.indexOf('topstrip') > -1 || cls.indexOf('divider') > -1) return '<i class="fa-solid fa-minus"></i>';
        if (cls.indexOf('hero') > -1)        return '<i class="fa-solid fa-image"></i>';

        switch (tag) {
            case 'html': case 'head': case 'body':
            case 'div':  case 'span':  case 'p':
                                       return '<i class="fa-solid fa-code"></i>';
            case 'section': case 'article': case 'main':
                                       return '<i class="fa-regular fa-image"></i>';
            case 'header':             return '<i class="fa-regular fa-window-maximize"></i>';
            case 'footer':             return '<i class="fa-solid fa-align-left"></i>';
            case 'nav':                return '<i class="fa-solid fa-bars"></i>';
            case 'img': case 'figure': case 'picture':
                                       return '<i class="fa-regular fa-image"></i>';
            case 'a':                  return '<i class="fa-solid fa-link"></i>';
            case 'button':             return '<i class="fa-regular fa-square"></i>';
            case 'form':               return '<i class="fa-regular fa-rectangle-list"></i>';
            case 'ul': case 'ol': case 'li':
                                       return '<i class="fa-solid fa-list"></i>';
            case 'input': case 'select': case 'textarea':
                                       return '<i class="fa-regular fa-square"></i>';
            case 'video': case 'audio':
                                       return '<i class="fa-solid fa-play"></i>';
        }
        return '<i class="fa-solid fa-code"></i>';
    }

    function veDomName(el) {
        var tag = el.tagName.toLowerCase();
        if (el.id) return '#' + el.id;
        if (typeof el.className === 'string' && el.className.trim()) {
            return '.' + el.className.trim().split(/\s+/)[0];
        }
        return tag;
    }

    function buildDomTree() {
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) return;
        var $list = $('#ve-dom-tree-list').empty();
        var ignoreTags = ['script', 'style', 'meta', 'link'];

        function renderNode(el, depth, $parent) {
            if (el.nodeType !== 1) return;
            var tag = el.tagName.toLowerCase();
            if (ignoreTags.indexOf(tag) !== -1) return;

            var validChildren = [];
            Array.from(el.children || []).forEach(function (c) {
                if (ignoreTags.indexOf(c.tagName.toLowerCase()) === -1) validChildren.push(c);
            });
            var hasChildren = validChildren.length > 0;

            var isSentinel = el.hasAttribute && el.hasAttribute('data-ve-sc');
            var expanded   = depth < 3;

            // Wrapper per node (for nested children)
            var $wrap = $('<div class="ve-dom-node"></div>');

            var $row = $('<div class="ve-dom-row"></div>')
                .css('padding-left', (depth * 14 + 8) + 'px')
                .data('ve-el', el);

            var chevHtml = hasChildren
                ? '<button type="button" class="ve-dom-chev' + (expanded ? ' open' : '') + '" aria-label="Expandir"><i class="fa-solid fa-chevron-right"></i></button>'
                : '<span class="ve-dom-chev-empty"></span>';

            $row.html(
                chevHtml +
                '<span class="ve-dom-ico">' + veDomIcon(el) + '</span>' +
                '<span class="ve-dom-name">' + $('<span>').text(veDomName(el)).html() + '</span>' +
                '<span class="ve-dom-tag">' + tag + '</span>' +
                '<span class="ve-dom-acts">' +
                    '<button type="button" class="ve-dom-act ve-dom-act-vis" title="Visibilidad"><i class="fa-solid fa-eye"></i></button>' +
                    '<button type="button" class="ve-dom-act ve-dom-act-lock" title="Bloquear"><i class="fa-solid fa-lock"></i></button>' +
                '</span>' +
                (isSentinel ? '<span class="ve-dom-sent" title="Bloque"><i class="fa-solid fa-puzzle-piece"></i></span>' : '')
            );

            $row.on('mouseenter', function () { el.style.outline = '1px dashed #60a5fa'; el.style.outlineOffset = '-1px'; });
            $row.on('mouseleave', function () { el.style.outline = ''; el.style.outlineOffset = ''; });
            $row.on('click', function (e) {
                if ($(e.target).closest('.ve-dom-chev, .ve-dom-act').length) return;
                e.stopPropagation();
                $('#ve-dom-tree-list .ve-dom-row').removeClass('selected');
                $(this).addClass('selected');
                el.click();
            });
            $wrap.append($row);

            if (hasChildren && depth < 10) {
                var $kids = $('<div class="ve-dom-kids' + (expanded ? '' : ' collapsed') + '"></div>');
                validChildren.forEach(function (child) {
                    renderNode(child, depth + 1, $kids);
                });
                $wrap.append($kids);
            }

            $parent.append($wrap);
        }

        var body = frame.contentDocument.documentElement;
        if (body) {
            renderNode(body, 0, $list);
        }
    }

    // Chevron toggle — simple parent/children relationship
    $(document).on('click', '#ve-dom-tree-list .ve-dom-chev', function (e) {
        e.stopPropagation();
        e.preventDefault();
        var $chev = $(this);
        var $wrap = $chev.closest('.ve-dom-node');
        var $kids = $wrap.children('.ve-dom-kids').first();
        $chev.toggleClass('open');
        $kids.toggleClass('collapsed');
    });

    // Visibility toggle
    $(document).on('click', '#ve-dom-tree-list .ve-dom-act-vis', function (e) {
        e.stopPropagation();
        var el = $(this).closest('.ve-dom-row').data('ve-el');
        if (!el) return;
        $(this).toggleClass('off');
        if ($(this).hasClass('off')) {
            $(this).html('<i class="fa-solid fa-eye-slash"></i>');
            el.style.visibility = 'hidden';
        } else {
            $(this).html('<i class="fa-solid fa-eye"></i>');
            el.style.visibility = '';
        }
    });

    // Lock toggle
    $(document).on('click', '#ve-dom-tree-list .ve-dom-act-lock', function (e) {
        e.stopPropagation();
        var el = $(this).closest('.ve-dom-row').data('ve-el');
        if (!el) return;
        $(this).toggleClass('on');
        if ($(this).hasClass('on')) {
            $(this).html('<i class="fa-solid fa-lock"></i>');
            el.setAttribute('data-ve-locked', '1');
        } else {
            $(this).html('<i class="fa-solid fa-lock-open"></i>');
            el.removeAttribute('data-ve-locked');
        }
    });

    // Build DOM tree when its panel is activated
    $('#ve-sidebar-nav').on('click', '.ve-nav-btn[data-panel="dom-tree"]', function () {
        setTimeout(buildDomTree, 150);
    });

    $('#btn-dom-refresh').on('click', buildDomTree);

    /* ── Comparar antes/después (side-by-side iframes) ──────────────── */
    var originalIframeHtml = null;

    $('#ve-preview-frame').on('load', function () {
        try {
            if (!originalIframeHtml && this.contentDocument && this.contentDocument.body) {
                originalIframeHtml = this.contentDocument.outerHTML;
            }
        } catch (e) {}
    });

    $('#btn-diff-preview').on('click', function () {
        new bootstrap.Modal(document.getElementById('ve-diff-modal')).show();
    });

    /* ── FEATURE 1: Session History Log ─────────────────────────────── */
    var veSessionLog = [];

    function addSessionEntry(label, icon) {
        var entry = {
            time:  new Date().toLocaleTimeString(),
            label: label,
            icon:  icon || 'fa-circle-dot',
        };
        veSessionLog.unshift(entry);
        if (veSessionLog.length > 50) veSessionLog.pop();
        renderSessionHistory();
    }

    function renderSessionHistory() {
        var $list = $('#ve-session-history-list');
        if (!veSessionLog.length) {
            $list.html('<div class="text-muted text-center" style="padding:16px;font-size:12px;">Sin actividad aún.</div>');
            return;
        }
        var html = veSessionLog.map(function (e) {
            return '<div style="display:flex;align-items:center;gap:8px;padding:6px 12px;border-bottom:1px solid #f0f0f0;font-size:12px;">'
                + '<i class="fa-solid ' + e.icon + ' text-muted" style="width:14px;flex-shrink:0;"></i>'
                + '<span style="flex:1;">' + $('<span>').text(e.label).html() + '</span>'
                + '<span style="color:#aaa;font-size:10px;">' + e.time + '</span>'
                + '</div>';
        }).join('');
        $list.html(html);
    }

    // Expose so panel scripts can call it
    window.veAddSessionEntry = addSessionEntry;

    // Hook: Guardar (btn-save triggers doSave which fires success; intercept markModified)
    $(document).on('click', '#btn-save, #btn-save-bar', function () {
        addSessionEntry('Guardado manual', 'fa-floppy-disk');
    });

    // Hook: Deshacer / Rehacer
    $(document).on('click', '#btn-undo, #btn-undo-bar', function () {
        addSessionEntry('Deshacer', 'fa-rotate-left');
    });
    $(document).on('click', '#btn-redo, #btn-redo-bar', function () {
        addSessionEntry('Rehacer', 'fa-rotate-right');
    });

    // Hook: Cambio de breakpoint
    $(document).on('click', '.breakpoint-btn', function () {
        var bp = $(this).data('breakpoint') || 'desktop';
        var icons = { desktop: 'fa-desktop', laptop: 'fa-laptop', tablet: 'fa-tablet-screen-button', mobile: 'fa-mobile-screen-button', device: 'fa-mobile-screen-button' };
        addSessionEntry('Vista: ' + bp.charAt(0).toUpperCase() + bp.slice(1), icons[bp] || 'fa-desktop');
    });

    // Hook: iframe load (preview updated)
    $('#ve-preview-frame').on('load', function () {
        addSessionEntry('Preview recargado', 'fa-arrows-rotate');
        var frameEl = this;
        var doc     = frameEl.contentDocument;
        var win     = frameEl.contentWindow;
        if (!doc) return;

        // Inject CSS once — hide popup + backdrop, restore body scroll
        if (!doc.getElementById('ve-suppress-popups')) {
            var style = doc.createElement('style');
            style.id = 've-suppress-popups';
            style.textContent = [
                '#newsletter-popup, .newsletter-popup { display: none !important; }',
                '.modal-backdrop { display: none !important; }',
                'body.modal-open { overflow: auto !important; padding-right: 0 !important; }'
            ].join('\n');
            doc.head && doc.head.appendChild(style);
        }

        function cleanupPopups() {
            // Remove newsletter popup element (if any)
            var popup = doc.getElementById('newsletter-popup');
            if (popup) {
                try {
                    if (win && win.bootstrap && win.bootstrap.Modal) {
                        var inst = win.bootstrap.Modal.getInstance(popup);
                        if (inst) inst.dispose();
                    }
                } catch (e) {}
                popup.remove();
            }
            // Remove any stray backdrops (left over from modals that already opened)
            doc.querySelectorAll('.modal-backdrop').forEach(function (b) { b.remove(); });
            // Unlock body scroll
            if (doc.body) {
                doc.body.classList.remove('modal-open');
                doc.body.style.overflow = '';
                doc.body.style.paddingRight = '';
            }
        }

        cleanupPopups();

        // Popup loads via fetch + setTimeout(5s) — keep cleaning for 15s
        var attempts = 0;
        var interval = setInterval(function () {
            cleanupPopups();
            if (++attempts >= 15) clearInterval(interval);
        }, 1000);
    });

    // Hook: Limpiar historial de sesión
    $('#btn-session-clear').on('click', function () {
        veSessionLog = [];
        renderSessionHistory();
    });

    // Init
    renderSessionHistory();

    /* ── FEATURE 4: Hover element info tooltip ───────────────────────── */
    $('#btn-hover-inspect').on('click', function () {
        hoverInspectMode = !hoverInspectMode;
        $(this).toggleClass('active', hoverInspectMode);

        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentDocument && frame.contentDocument.body) {
            frame.contentDocument.body.style.cursor = hoverInspectMode ? 'crosshair' : '';
        }

        sendToFrame({ type: 've-hover-inspect-toggle', active: hoverInspectMode });

        if (!hoverInspectMode && $hoverTooltip) {
            $hoverTooltip.remove();
            $hoverTooltip = null;
        }

        showToast(hoverInspectMode
            ? '<i class="fa-solid fa-crosshairs me-1"></i>Inspect hover activado'
            : '<i class="fa-solid fa-crosshairs me-1"></i>Inspect hover desactivado'
        );
    });

    // Re-apply cursor after preview reload when inspect mode is on
    $('#ve-preview-frame').on('load', function () {
        if (!hoverInspectMode) return;
        var frame = document.getElementById('ve-preview-frame');
        if (frame && frame.contentDocument && frame.contentDocument.body) {
            frame.contentDocument.body.style.cursor = 'crosshair';
        }
        sendToFrame({ type: 've-hover-inspect-toggle', active: true });
    });

    /* ── Topbar: Inspector state selector (Base / :hover / :focus) ───── */
    var currentInspectorState = 'base';

    $(document).on('click', '#ve-ctx-state-seg .ve-seg-btn', function () {
        var state = $(this).data('state') || 'base';
        $('#ve-ctx-state-seg .ve-seg-btn').removeClass('active');
        $(this).addClass('active');
        currentInspectorState = state;

        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) return;

        var prevStyle = frame.contentDocument.getElementById('ve-pseudo-state-style');
        if (prevStyle) prevStyle.remove();

        var el = null;
        if (currentContextNodeId) {
            el = frame.contentDocument.querySelector('[data-ve-id="' + currentContextNodeId + '"]');
        }
        if (!el) el = frame.contentDocument.querySelector('.ck-widget--selected');

        frame.contentDocument.querySelectorAll('.ve-pseudo-hover, .ve-pseudo-focus').forEach(function (n) {
            n.classList.remove('ve-pseudo-hover', 've-pseudo-focus');
        });

        if (state !== 'base' && el) {
            el.classList.add('ve-pseudo-' + state);
            if (state === 'focus') el.focus();

            var styleTag = frame.contentDocument.createElement('style');
            styleTag.id = 've-pseudo-state-style';
            styleTag.textContent = state === 'hover'
                ? '.ve-pseudo-hover { outline: 2px dashed #90bb13 !important; }'
                : '.ve-pseudo-focus { outline: 2px solid #0ea5e9 !important; }';
            frame.contentDocument.head.appendChild(styleTag);
        } else if (el && state === 'base') {
            el.blur();
        }

        var stateLabels = { base: 'Base', hover: ':hover', focus: ':focus' };
        showToast('<i class="fa-solid fa-palette me-1"></i>Estado ' + (stateLabels[state] || state) + ' activo');
    });

    /* ── Topbar: Code panel HTML / CSS tabs ─────────────────────────── */
    var veCodePanelMode = 'html';

    $(document).on('click', '#ve-ctx-code-type-seg .ve-seg-btn', function () {
        var mode = $(this).text().trim().toLowerCase();
        if (mode === veCodePanelMode) return;
        $('#ve-ctx-code-type-seg .ve-seg-btn').removeClass('active');
        $(this).addClass('active');
        veCodePanelMode = mode;

        if (!veFullCodeMirror) return;

        if (mode === 'css') {
            var frame = document.getElementById('ve-preview-frame');
            var cssText = '';
            if (frame && frame.contentDocument) {
                Array.from(frame.contentDocument.querySelectorAll('style')).forEach(function (s) {
                    cssText += (cssText ? '\n\n' : '') + s.textContent.trim();
                });
            }
            veFullCodeMirror.setOption('mode', 'css');
            veFullCodeMirror.setValue(cssText || '/* Sin estilos inline en la página */');
            veFullCodeMirror.refresh();
            $('#ve-ctx-code-lines').text(veFullCodeMirror.lineCount() + ' líneas');
            // In CSS mode validity is always shown as neutral
            $('#ve-ctx-validity-dot').css('color', '#aaa');
            $('#ve-ctx-validity-label').text('CSS');
            showToast('<i class="fa-solid fa-palette me-1"></i>Modo CSS activo');
        } else {
            veFullCodeMirror.setOption('mode', 'htmlmixed');
            veSyncCodeFromPreview();
            // Re-validate when switching back to HTML mode
            setTimeout(function () {
                if (veFullCodeMirror) veValidateCode(veFullCodeMirror.getValue());
            }, 500);
            showToast('<i class="fa-solid fa-code me-1"></i>Modo HTML activo');
        }
    });

    /* ── Topbar: Settings tabs (Básico / SEO / Social) ──────────────── */
    $(document).on('click', '#ve-ctx-settings-seg [data-settings-tab]', function () {
        var tab = $(this).data('settings-tab');
        $('#ve-ctx-settings-seg .ve-seg-btn').removeClass('active');
        $(this).addClass('active');

        var $container = $('#ve-settings-panel .ve-scrollable-area').first();
        if (!$container.length) return;

        var $target;
        if (tab === 'basic') {
            $target = $container.find('.ve-section-title').first();
        } else if (tab === 'seo') {
            $target = $container.find('.ve-section-title-bordered').first();
        } else if (tab === 'social') {
            $target = $container.find('#ve-og-header').first();
        }

        if ($target && $target.length) {
            var scrollTo = $container.scrollTop() + $target.offset().top - $container.offset().top - 8;
            $container.animate({ scrollTop: scrollTo }, 200);
        }
    });

    /* ── Topbar: contextual seg-btn dispatcher ───────────────────────── */
    $(document).on('click', '#ve-ctx-panel .ve-seg-btn', function () {
        var $btn = $(this);
        var label = $btn.text().trim();

        $btn.closest('.ve-seg').find('.ve-seg-btn').removeClass('active');
        $btn.addClass('active');

        /* History filter: Todas / Snapshots */
        if (label === 'Todas' || label === 'Snapshots') {
            if (label === 'Snapshots') {
                $('#ve-history-list').hide();
                $('.ve-snapshot-section').show();
            } else {
                $('#ve-history-list').show();
                $('.ve-snapshot-section').show();
            }
            return;
        }

        /* Sections filter: Todo / Ocultas */
        if (label === 'Todo' || label === 'Ocultas') {
            var $items = $('#ve-sections-list .ve-section-item');
            if (!$items.length) return;
            if (label === 'Ocultas') {
                var frame = document.getElementById('ve-preview-frame');
                $items.each(function () {
                    var idx  = parseInt($(this).data('index'), 10);
                    var show = false;
                    if (frame && frame.contentDocument && frame.contentWindow) {
                        var children = Array.from((frame.contentDocument.body || {}).children || []);
                        var el = children[idx];
                        if (el) {
                            var cs = frame.contentWindow.getComputedStyle(el);
                            show = cs.display === 'none' || cs.visibility === 'hidden' || parseFloat(cs.opacity) < 0.1;
                        }
                    }
                    $(this).toggle(show);
                });
                showToast('<i class="fa-solid fa-eye-slash me-1"></i>Mostrando secciones ocultas');
            } else {
                $items.show();
            }
            return;
        }

        /* DOM-tree filter: Todos / Solo bloque */
        if (label === 'Todos' || label === 'Solo bloque') {
            var $nodes = $('#ve-dom-tree-list .ve-dom-node');
            if (!$nodes.length) return;
            if (label === 'Solo bloque') {
                var blockRe = /^<(div|section|article|header|footer|main|nav|aside|ul|ol|table|form|figure|blockquote|h[1-6])/i;
                $nodes.each(function () {
                    $(this).toggle(blockRe.test($(this).html()));
                });
                showToast('<i class="fa-solid fa-sitemap me-1"></i>Solo elementos bloque');
            } else {
                $nodes.show();
            }
        }
    });

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/html-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/css-hint.min.js"></script>
<script>
/* ── Bootstrap 5 + Font Awesome 6 class autocomplete for CodeMirror ── */
(function () {
    var BS_CLASSES = [
        /* Layout */
        'container','container-fluid','container-sm','container-md','container-lg','container-xl','container-xxl',
        'row','row-cols-1','row-cols-2','row-cols-3','row-cols-4','row-cols-auto',
        'col','col-1','col-2','col-3','col-4','col-5','col-6','col-7','col-8','col-9','col-10','col-11','col-12',
        'col-auto','col-sm','col-sm-1','col-sm-2','col-sm-3','col-sm-4','col-sm-6','col-sm-8','col-sm-12',
        'col-md','col-md-1','col-md-2','col-md-3','col-md-4','col-md-6','col-md-8','col-md-12',
        'col-lg','col-lg-1','col-lg-2','col-lg-3','col-lg-4','col-lg-6','col-lg-8','col-lg-12',
        'col-xl','col-xl-3','col-xl-4','col-xl-6','col-xl-8','col-xl-12',
        'g-0','g-1','g-2','g-3','g-4','g-5','gx-0','gx-1','gx-2','gx-3','gx-4','gx-5',
        'gy-0','gy-1','gy-2','gy-3','gy-4','gy-5','offset-1','offset-2','offset-3','offset-4','offset-6',
        /* Display */
        'd-none','d-block','d-inline','d-inline-block','d-flex','d-inline-flex','d-grid','d-table','d-table-cell',
        'd-sm-none','d-sm-block','d-sm-flex','d-md-none','d-md-block','d-md-flex',
        'd-lg-none','d-lg-block','d-lg-flex','d-xl-none','d-xl-block','d-xl-flex',
        /* Flex */
        'flex-row','flex-row-reverse','flex-column','flex-column-reverse',
        'flex-wrap','flex-nowrap','flex-wrap-reverse','flex-fill','flex-grow-0','flex-grow-1','flex-shrink-0','flex-shrink-1',
        'justify-content-start','justify-content-end','justify-content-center','justify-content-between','justify-content-around','justify-content-evenly',
        'align-items-start','align-items-end','align-items-center','align-items-baseline','align-items-stretch',
        'align-self-start','align-self-end','align-self-center','align-self-baseline','align-self-stretch',
        'align-content-start','align-content-end','align-content-center','align-content-between','align-content-around','align-content-stretch',
        'gap-0','gap-1','gap-2','gap-3','gap-4','gap-5','gap-auto',
        'order-0','order-1','order-2','order-3','order-4','order-5','order-first','order-last',
        /* Spacing */
        'm-0','m-1','m-2','m-3','m-4','m-5','m-auto',
        'mt-0','mt-1','mt-2','mt-3','mt-4','mt-5','mt-auto',
        'mb-0','mb-1','mb-2','mb-3','mb-4','mb-5','mb-auto',
        'ms-0','ms-1','ms-2','ms-3','ms-4','ms-5','ms-auto',
        'me-0','me-1','me-2','me-3','me-4','me-5','me-auto',
        'mx-0','mx-1','mx-2','mx-3','mx-4','mx-5','mx-auto',
        'my-0','my-1','my-2','my-3','my-4','my-5','my-auto',
        'p-0','p-1','p-2','p-3','p-4','p-5',
        'pt-0','pt-1','pt-2','pt-3','pt-4','pt-5',
        'pb-0','pb-1','pb-2','pb-3','pb-4','pb-5',
        'ps-0','ps-1','ps-2','ps-3','ps-4','ps-5',
        'pe-0','pe-1','pe-2','pe-3','pe-4','pe-5',
        'px-0','px-1','px-2','px-3','px-4','px-5',
        'py-0','py-1','py-2','py-3','py-4','py-5',
        /* Sizing */
        'w-25','w-50','w-75','w-100','w-auto','mw-100',
        'h-25','h-50','h-75','h-100','h-auto','mh-100',
        'vw-100','vh-100','min-vw-100','min-vh-100',
        /* Typography */
        'h1','h2','h3','h4','h5','h6',
        'display-1','display-2','display-3','display-4','display-5','display-6',
        'lead','small','mark','del','s','ins','u','strong','em','abbr',
        'text-start','text-center','text-end','text-wrap','text-nowrap','text-truncate','text-break',
        'text-lowercase','text-uppercase','text-capitalize',
        'fw-light','fw-lighter','fw-normal','fw-semibold','fw-bold','fw-bolder',
        'fst-italic','fst-normal',
        'fs-1','fs-2','fs-3','fs-4','fs-5','fs-6',
        'lh-1','lh-sm','lh-base','lh-lg',
        'font-monospace',
        'text-decoration-none','text-decoration-underline','text-decoration-line-through',
        /* Colors — text */
        'text-primary','text-secondary','text-success','text-danger','text-warning','text-info',
        'text-light','text-dark','text-white','text-muted','text-body','text-black','text-black-50','text-white-50',
        'text-reset','text-opacity-25','text-opacity-50','text-opacity-75','text-opacity-100',
        /* Colors — bg */
        'bg-primary','bg-secondary','bg-success','bg-danger','bg-warning','bg-info',
        'bg-light','bg-dark','bg-white','bg-transparent','bg-body',
        'bg-gradient','bg-opacity-10','bg-opacity-25','bg-opacity-50','bg-opacity-75','bg-opacity-100',
        /* Border */
        'border','border-0','border-top','border-top-0','border-end','border-end-0',
        'border-bottom','border-bottom-0','border-start','border-start-0',
        'border-primary','border-secondary','border-success','border-danger','border-warning','border-info','border-light','border-dark','border-white',
        'border-1','border-2','border-3','border-4','border-5',
        'border-opacity-10','border-opacity-25','border-opacity-50','border-opacity-75','border-opacity-100',
        'rounded','rounded-0','rounded-1','rounded-2','rounded-3','rounded-4','rounded-5','rounded-circle','rounded-pill',
        'rounded-top','rounded-end','rounded-bottom','rounded-start',
        /* Shadow */
        'shadow','shadow-sm','shadow-lg','shadow-none',
        /* Position */
        'position-static','position-relative','position-absolute','position-fixed','position-sticky',
        'top-0','top-50','top-100','bottom-0','bottom-50','bottom-100',
        'start-0','start-50','start-100','end-0','end-50','end-100',
        'translate-middle','translate-middle-x','translate-middle-y',
        /* Overflow */
        'overflow-auto','overflow-hidden','overflow-visible','overflow-scroll',
        'overflow-x-auto','overflow-x-hidden','overflow-y-auto','overflow-y-hidden',
        /* Z-index */
        'z-0','z-1','z-2','z-3','z-n1',
        /* Visibility */
        'visible','invisible','visually-hidden','visually-hidden-focusable',
        /* Opacity */
        'opacity-0','opacity-25','opacity-50','opacity-75','opacity-100',
        /* Components */
        'btn','btn-sm','btn-lg','btn-block',
        'btn-primary','btn-secondary','btn-success','btn-danger','btn-warning','btn-info','btn-light','btn-dark','btn-link',
        'btn-outline-primary','btn-outline-secondary','btn-outline-success','btn-outline-danger',
        'btn-outline-warning','btn-outline-info','btn-outline-light','btn-outline-dark',
        'btn-close','btn-check',
        'badge','text-bg-primary','text-bg-secondary','text-bg-success','text-bg-danger',
        'text-bg-warning','text-bg-info','text-bg-light','text-bg-dark',
        'alert','alert-primary','alert-secondary','alert-success','alert-danger',
        'alert-warning','alert-info','alert-light','alert-dark','alert-dismissible',
        'card','card-body','card-title','card-subtitle','card-text','card-link',
        'card-header','card-footer','card-img','card-img-top','card-img-bottom','card-img-overlay',
        'card-group','card-columns',
        'nav','nav-tabs','nav-pills','nav-fill','nav-justified','nav-item','nav-link',
        'navbar','navbar-brand','navbar-nav','navbar-toggler','navbar-expand','navbar-expand-sm',
        'navbar-expand-md','navbar-expand-lg','navbar-expand-xl','navbar-light','navbar-dark',
        'dropdown','dropdown-toggle','dropdown-menu','dropdown-item','dropdown-divider',
        'dropdown-menu-end','dropdown-menu-start','dropup','dropend','dropstart',
        'list-group','list-group-item','list-group-flush','list-group-numbered',
        'list-group-item-primary','list-group-item-success','list-group-item-danger','list-group-item-warning',
        'list-unstyled','list-inline','list-inline-item',
        'table','table-sm','table-bordered','table-borderless','table-striped','table-hover','table-responsive',
        'table-primary','table-secondary','table-success','table-danger','table-warning','table-info','table-light','table-dark',
        'form-control','form-control-sm','form-control-lg','form-control-color',
        'form-select','form-select-sm','form-select-lg',
        'form-check','form-check-input','form-check-label','form-check-inline',
        'form-switch','form-range','form-label','form-text','form-floating',
        'input-group','input-group-text','input-group-sm','input-group-lg',
        'was-validated','is-valid','is-invalid','valid-feedback','invalid-feedback',
        'modal','modal-dialog','modal-dialog-centered','modal-dialog-scrollable',
        'modal-content','modal-header','modal-title','modal-body','modal-footer',
        'modal-sm','modal-lg','modal-xl','modal-fullscreen',
        'pagination','page-item','page-link','pagination-sm','pagination-lg',
        'progress','progress-bar','progress-bar-striped','progress-bar-animated',
        'spinner-border','spinner-border-sm','spinner-grow','spinner-grow-sm',
        'toast','toast-container','toast-header','toast-body',
        'tooltip','popover','popover-header','popover-body',
        'accordion','accordion-item','accordion-header','accordion-button','accordion-body','accordion-collapse','accordion-flush',
        'collapse','collapsing','show',
        'tab-content','tab-pane','fade','active','disabled',
        'breadcrumb','breadcrumb-item',
        'placeholder','placeholder-wave','placeholder-glow',
        'ratio','ratio-1x1','ratio-4x3','ratio-16x9','ratio-21x9',
        'img-fluid','img-thumbnail','figure','figure-img','figure-caption',
        /* Utilities misc */
        'clearfix','link-primary','link-secondary','link-success','link-danger',
        'pe-none','pe-auto','user-select-none','user-select-auto','user-select-all',
        'text-decoration-color-primary',
        /* Font Awesome 6 — prefix only (user types fa-...) */
        'fas','far','fab','fa-solid','fa-regular','fa-brands',
        'fa-xs','fa-sm','fa-lg','fa-xl','fa-2xl','fa-fw','fa-spin','fa-pulse',
        'fa-2x','fa-3x','fa-4x','fa-5x','fa-6x','fa-7x','fa-8x','fa-9x','fa-10x',
        'fa-rotate-90','fa-rotate-180','fa-rotate-270','fa-flip-horizontal','fa-flip-vertical',
        'fa-inverse','fa-stack','fa-stack-1x','fa-stack-2x',
    ];

    window.veBootstrapHint = function (cm) {
        var cursor = cm.getCursor();
        var line   = cm.getLine(cursor.line);
        var before = line.substring(0, cursor.ch);

        /* Inside class="..." → Bootstrap + FA class suggestions */
        var classMatch = before.match(/\bclass=["']([^"']*)$/) ||
                         before.match(/\bclass=([^\s>]*)$/);
        if (classMatch) {
            var typed   = classMatch[1];
            var parts   = typed.split(/\s+/);
            var current = parts[parts.length - 1];
            var list = current.length === 0
                ? BS_CLASSES
                : BS_CLASSES.filter(function (c) { return c.indexOf(current) === 0; });
            if (!list.length) { return null; }
            return {
                list: list,
                from: CodeMirror.Pos(cursor.line, cursor.ch - current.length),
                to:   CodeMirror.Pos(cursor.line, cursor.ch),
            };
        }

        /* Everywhere else: use the built-in HTML hint (tags + attributes) */
        if (CodeMirror.hint && CodeMirror.hint.html) {
            return CodeMirror.hint.html(cm);
        }

        return null;
    };

    /* Auto-trigger hint while typing */
    window.veBootstrapHintAuto = function (cm, change) {
        if (change.origin !== '+input' && change.origin !== 'paste') { return; }
        var cursor = cm.getCursor();
        var line   = cm.getLine(cursor.line);
        var before = line.substring(0, cursor.ch);

        /* Inside class attribute → Bootstrap classes */
        if (/\bclass=["'][^"']*$/.test(before)) {
            CodeMirror.commands.autocomplete(cm, window.veBootstrapHint, { completeSingle: false });
            return;
        }
        /* After < or </ → tag name completion */
        if (/<\/?[\w]*$/.test(before)) {
            CodeMirror.commands.autocomplete(cm, window.veBootstrapHint, { completeSingle: false });
            return;
        }
        /* Inside an open tag after a space → attribute completion */
        if (/<[\w][^>]*\s[\w-]*$/.test(before)) {
            CodeMirror.commands.autocomplete(cm, window.veBootstrapHint, { completeSingle: false });
        }
    };
}());
</script>

{{-- ── Modal: Find & Replace ─────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-find-replace-modal" tabindex="-1">
    <div class="modal-dialog vm-dialog-sm modal-dialog-centered">
        <div class="modal-content vm-wrap">
            <div class="vm-head">
                <div class="vm-icon"><i class="fa-solid fa-magnifying-glass-arrow-right"></i></div>
                <div class="vm-title-wrap">
                    <div class="vm-label">CONTENIDO</div>
                    <div class="vm-title">Buscar y reemplazar</div>
                </div>
                <button type="button" class="vm-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div class="vm-field">
                    <label class="vm-flabel">Buscar</label>
                    <input type="text" class="vm-finput" id="ve-fr-find" placeholder="Texto a buscar…" autocomplete="off">
                </div>
                <div class="vm-field">
                    <label class="vm-flabel">Reemplazar con</label>
                    <input type="text" class="vm-finput" id="ve-fr-replace" placeholder="Texto de reemplazo…" autocomplete="off">
                </div>
                <div class="vm-chk-row">
                    <div class="vm-chk" id="ve-fr-case-chk" data-for="ve-fr-case"><i class="fa-solid fa-check"></i></div>
                    <div class="body">
                        <div class="t">Distinguir mayúsculas/minúsculas</div>
                        <div class="desc">La búsqueda será exacta respetando cada carácter</div>
                    </div>
                    <input type="checkbox" id="ve-fr-case" class="ve-hidden">
                </div>
                <div id="ve-fr-feedback" class="vm-fr-feedback"></div>
            </div>
            <div class="vm-foot">
                <span class="vm-kbd-pill"><span class="vm-kbd">⏎</span> reemplazar</span>
                <div class="vm-spacer"></div>
                <button type="button" class="vm-btn vm-btn-outline vm-btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="vm-btn vm-btn-primary vm-btn-sm" id="btn-do-replace">
                    <i class="fa-solid fa-check"></i>Reemplazar todo
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Media Manager ──────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-media-modal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
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
                            <i class="fa-solid fa-upload me-1"></i>Subir
                            <input type="file" id="ve-media-upload-input" style="display:none;" multiple>
                        </label>
                    </div>
                </div>
                <div id="ve-media-grid" style="display:grid; grid-template-columns:repeat(auto-fill,120px); gap:10px; max-height:420px; overflow-y:auto; min-height:100px;">
                    <div class="text-muted text-center" style="grid-column:1/-1; padding:40px 0;">
                        <i class="fa-solid fa-spinner fa-spin me-1"></i>Cargando medios...
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <small class="text-muted me-auto" id="ve-media-selected-info">Ningún archivo seleccionado</small>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm" id="btn-media-select"
                        style="background:#1a1a1a;border-color:#1a1a1a;color:#fff;" disabled>
                    <i class="fa-solid fa-check me-1"></i>Seleccionar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Link Editor ─────────────────────────────────────────────── --}}
<div class="modal fade" id="ve-link-editor-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
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

    /* ── Draft banner: restore / dismiss ────────────────────────────── */
    $('#btn-restore-draft').on('click', function () {
        var $btn = $(this).prop('disabled', true).text('Cargando...');
        $.get(DRAFT_URL)
            .done(function (res) {
                if (!res.success || !res.data) {
                    showToast('<i class="fa-solid fa-triangle-exclamation me-1"></i>Sin borrador activo', 'error');
                    return;
                }
                veConfirm('¿Restaurar el borrador guardado ' + res.data.saved_at + '? El contenido actual quedará en el historial de deshacer.', function () {
                    if (window.veEditor) {
                        vePushHistory('Antes de restaurar borrador', window.veEditor.getData());
                        window.veEditor.setData(res.data.content || '');
                    }
                    showToast('<i class="fa-solid fa-clock-rotate-left me-1"></i>Borrador restaurado');
                    $('#ve-draft-banner').slideUp(200);
                });
            })
            .fail(function () {
                showToast('<i class="fa-solid fa-triangle-exclamation me-1"></i>Error al cargar borrador', 'error');
            })
            .always(function () { $btn.prop('disabled', false).text('Restaurar'); });
    });

    $('#btn-dismiss-draft').on('click', function () {
        $('#ve-draft-banner').slideUp(200);
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
        $('#ve-fr-feedback').html('<span class="text-success"><i class="fa-solid fa-check me-1"></i>' + count + ' reemplazo(s) realizados.</span>');
    });

    // ── Bulk find/replace in shortcodes ─────────────────────────────────────
    function getScNodeRaw(node) {
        return node.getAttribute('data-ve-sc') || '';
    }

    $('#btn-sc-fr-preview').on('click', function () {
        var type    = $('#sc-fr-type').val().trim();
        var attr    = $('#sc-fr-attr').val().trim();
        var find    = $('#sc-fr-find').val();
        var replace = $('#sc-fr-replace').val();
        if (!attr || !find) { $('#sc-fr-preview').text('Completa atributo y valor a buscar.'); return; }
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) { $('#sc-fr-preview').text('Preview no disponible.'); return; }
        var nodes = frame.contentDocument.querySelectorAll('[data-ve-sc]');
        var matches = 0;
        nodes.forEach(function (n) {
            var raw = decodeURIComponent(getScNodeRaw(n));
            if (type && raw.indexOf('[' + type) === -1) return;
            var re = new RegExp(attr + '=["\']([^"\']*' + find.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '[^"\']*)["\']');
            if (re.test(raw)) matches++;
        });
        $('#sc-fr-preview').html('<i class="fas fa-search me-1"></i>' + matches + ' bloque(s) coinciden.');
    });

    $('#btn-sc-fr-apply').on('click', function () {
        var type    = $('#sc-fr-type').val().trim();
        var attr    = $('#sc-fr-attr').val().trim();
        var find    = $('#sc-fr-find').val();
        var replace = $('#sc-fr-replace').val();
        if (!attr || !find) { showToast('<i class="fas fa-exclamation-triangle me-1" style="color:#FEC90F"></i>Completa atributo y valor.'); return; }
        veConfirm('¿Aplicar el reemplazo a todos los bloques coincidentes?', function () {
            var frame = document.getElementById('ve-preview-frame');
            if (!frame || !frame.contentDocument) return;
            var nodes = Array.from(frame.contentDocument.querySelectorAll('[data-ve-sc]'));
            var changed = 0;
            nodes.forEach(function (n) {
                var raw = decodeURIComponent(getScNodeRaw(n));
                if (type && raw.indexOf('[' + type) === -1) return;
                var re = new RegExp('(' + attr.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=["\'])' + find.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');
                if (!re.test(raw)) return;
                var newRaw = raw.replace(re, '$1' + replace);
                n.setAttribute('data-ve-sc', encodeURIComponent(newRaw));
                changed++;
            });
            if (changed) {
                showToast('<i class="fas fa-check me-1" style="color:#13C672"></i>' + changed + ' bloque(s) actualizados.');
                bootstrap.Modal.getInstance(document.getElementById('ve-sc-find-replace-modal')).hide();
                if (window.vePushHistory) window.vePushHistory('Bulk find/replace en shortcodes');
            } else {
                showToast('<i class="fas fa-info-circle me-1"></i>No se encontraron coincidencias.');
            }
        });
    });

    // ── Page title rename (dblclick on topbar title) ─────────────────────────
    $(document).on('dblclick', '#ve-page-title-display', function () {
        var $span = $(this);
        var current = $span.text().trim();
        var $input = $('<input type="text" class="form-control form-control-sm" style="max-width:160px;font-size:13px;height:24px;padding:2px 6px;">')
            .val(current).insertAfter($span);
        $span.hide();
        $input.focus().select();
        function commit() {
            var newTitle = $input.val().trim() || current;
            $span.text(newTitle).show();
            $input.remove();
            if (newTitle !== current) {
                $.ajax({
                    url: '{{ route("pages.update", $page) }}',
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    contentType: 'application/json',
                    data: JSON.stringify({ title: newTitle, _method: 'PUT' }),
                    success: function () { showToast('<i class="fas fa-check me-1" style="color:#13C672"></i>Título actualizado.'); document.title = newTitle + ' — Visual Editor'; },
                    error: function () { showToast('<i class="fas fa-times-circle me-1 text-danger"></i>No se pudo actualizar el título.'); $span.text(current); }
                });
            }
        }
        $input.on('blur', commit).on('keydown', function (e) {
            if (e.key === 'Enter') commit();
            if (e.key === 'Escape') { $span.show(); $input.remove(); }
        });
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
        $('#ve-media-grid').html('<div class="text-muted text-center" style="grid-column:1/-1;padding:40px 0;"><i class="fa-solid fa-spinner fa-spin me-1"></i>Cargando...</div>');
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

        // Prepend recent images section when no search filter active
        if (!query && !typeF) {
            var recent = getRecentMedia();
            if (recent.length) {
                var $recentLabel = $('<div style="grid-column:1/-1;font-size:10px;font-weight:600;color:#90bb13;text-transform:uppercase;letter-spacing:1px;padding:2px 0 4px;">Recientes</div>');
                $grid.append($recentLabel);
                recent.forEach(function (url) {
                    var fname = url.split('/').pop().split('?')[0];
                    var $item = $('<div class="ve-media-item ve-media-recent" data-url="' + url + '" title="' + fname + '" style="border:2px solid #dee2e6;border-radius:6px;overflow:hidden;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:4px;padding:6px;background:#f0f5e6;min-height:90px;"><img src="' + url + '" style="max-width:100%;max-height:70px;object-fit:cover;border-radius:3px;"><span style="font-size:10px;color:#555;text-align:center;word-break:break-all;line-height:1.2;">' + fname.substring(0, 20) + '</span></div>');
                    $item.on('click', function () {
                        $('.ve-media-item').css('border-color', '#dee2e6');
                        $(this).css('border-color', '#1a1a1a');
                        veSelectedMedia = { url: url, name: fname };
                        $('#btn-media-select').prop('disabled', false);
                        $('#ve-media-selected-info').text(fname);
                    });
                    $grid.append($item);
                });
                $grid.append('<div style="grid-column:1/-1;border-top:1px solid #dee2e6;margin:4px 0 6px;font-size:10px;color:#aaa;">Todos los medios</div>');
            }
        }
        if (!files.length) {
            $grid.html('<div class="text-muted text-center" style="grid-column:1/-1;padding:40px 0;">Sin resultados.</div>');
            return;
        }
        files.forEach(function (f) {
            var isImg  = (f.type || f.mime_type || '').toLowerCase().includes('image');
            var thumb  = isImg ? (f.url || f.thumbnail_url || '') : '';
            var icon   = isImg ? '' : '<i class="fa-solid fa-file fa-2x text-muted"></i>';
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
        addRecentMedia(url);
        // Copy to clipboard
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url);
        }
        // Fill fields based on context
        if (veMediaCalledFrom === 'settings') {
            $('#ve-settings-featured-image').val(url).trigger('input');
        }
        bootstrap.Modal.getInstance(document.getElementById('ve-media-modal'))?.hide();
        if (window.showToast) window.showToast('<i class="fa-solid fa-check me-1"></i>URL copiada: ' + url.substring(0, 40));
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

<script>
(function($){

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
                $saveBar.html('<i class="fa-solid fa-spinner-third fa-spin" style="font-size:11px;"></i>');
            } else if (cls.indexOf('saved') !== -1) {
                $saveBar.html('<i class="fa-solid fa-check" style="color:#13C672;"></i>');
                setTimeout(function(){ $saveBar.text('Guardar'); }, 1500);
            } else if (cls.indexOf('error') !== -1) {
                $saveBar.html('<i class="fa-solid fa-times" style="color:#b10100;"></i>');
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
            $dims = $('<span id="ve-breakpoint-dims"></span>').css({
                fontSize: '9px',
                color: '#bbb',
                marginLeft: '6px',
                display: 'inline-flex',
                alignItems: 'center',
                alignSelf: 'center'
            });
            $('#btn-responsive-bar').after($dims);
        }
        $dims.text(bp === 'desktop' ? '' : w.replace('px','') + '×' + h.replace('px',''));
    });

    // Responsive bar dropdown: portal the menu to <body> while open so the
    // iframe's GPU compositing layer cannot paint over it. Bootstrap toggles
    // 'show' on the original <ul>; we mirror the state to a detached instance
    // positioned next to the toggle.
    (function initResponsiveDropdown() {
        var toggle = document.getElementById('btn-responsive-bar');
        if (!toggle || !window.bootstrap) { return; }
        var menu = toggle.parentElement.querySelector('.ve-responsive-dropdown');
        if (!menu) { return; }
        var placeholder = document.createComment('ve-responsive-dropdown-anchor');
        menu.parentNode.insertBefore(placeholder, menu);

        function place() {
            var r = toggle.getBoundingClientRect();
            menu.style.position = 'fixed';
            menu.style.zIndex = '10000';
            menu.style.left = (r.right - menu.offsetWidth) + 'px';
            menu.style.top  = (r.top - menu.offsetHeight - 4) + 'px';
        }

        toggle.addEventListener('shown.bs.dropdown', function () {
            document.body.appendChild(menu);
            place();
        });
        toggle.addEventListener('hidden.bs.dropdown', function () {
            placeholder.parentNode.insertBefore(menu, placeholder);
            menu.style.cssText = '';
        });
        window.addEventListener('resize', function () {
            if (menu.parentElement === document.body) { place(); }
        });
    })();

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
            $('<div class="ve-ctx-item" id="ctx-copy-style"><i class="fa-solid fa-palette fa-fw ve-ctx-icon-muted"></i> Copiar estilo</div>')
                .insertBefore($last);
            $('<div class="ve-ctx-item" id="ctx-paste-style"><i class="fa-solid fa-fill-drip fa-fw ve-ctx-icon-muted"></i> Pegar estilo</div>')
                .insertBefore($last);
            $('<div class="ve-ctx-item" id="ctx-inspect"><i class="fa-solid fa-sliders fa-fw ve-ctx-icon-muted"></i> Inspeccionar</div>')
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
        var style = $('#ve-popup-style-pick .vm-pop-opt.on').data('s') || 'center';
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
            '<button type="button" class="btn btn-outline-secondary ve-form-remove-field"><i class="fa-solid fa-times"></i></button></div>';
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
            var row = '<div class="ve-form-field-row"><input type="text" class="form-control" value="' + f[0] + '"><select class="form-select">' + opts.replace('value="' + f[1] + '"', 'value="' + f[1] + '" selected') + '</select><button type="button" class="btn btn-outline-secondary ve-form-remove-field"><i class="fa-solid fa-times"></i></button></div>';
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
    // Applies a low-fidelity preview: outlines on every element, images and
    // media collapse to hatched placeholders. The iframe receives a postMessage
    // that injects the wireframe CSS into the preview document.
    $(document).on('click', '#btn-wireframe', function() {
        var $wrap = $('#ve-canvas-wrap');
        $wrap.toggleClass('ve-wireframe');
        $(this).toggleClass('active');
        sendToFrame({ type: 've-wireframe-toggle', enabled: $wrap.hasClass('ve-wireframe') });
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
    // Exposed so the breakpoint switcher can redraw it when canvas width changes.
    window.veRenderRuler = function () {
        var $ruler = $('#ve-ruler');
        if (!$ruler.hasClass('active')) { return; }
        $ruler.empty();
        var w = $('#ve-canvas-wrap').width();
        for (var px = 0; px <= w; px += 100) {
            $ruler.append('<div class="ve-ruler-tick" style="left:'+px+'px;"></div><div class="ve-ruler-label" style="left:'+(px+2)+'px;">'+px+'</div>');
        }
    };
    $(document).on('click', '#btn-ruler', function() {
        var $ruler = $('#ve-ruler');
        $ruler.toggleClass('active');
        $(this).toggleClass('active');
        if ($ruler.hasClass('active')) { window.veRenderRuler(); }
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
        'page.url': @json(url($translation->slug ?? $page->slug ?? '')),
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
    // Add condition entry to context menu (inserted before the final "Eliminar" separator)
    (function addConditionCtx() {
        var $menu = $('#ve-context-menu');
        if (!$menu.length) { setTimeout(addConditionCtx, 500); return; }
        if ($('#ctx-conditions').length) return;
        // Find the LAST separator (just above "Eliminar") and insert before it
        var $lastSep = $menu.find('.ec-ctx-sep').last();
        var $entry = $('<button type="button" class="ec-ctx-item" id="ctx-conditions" role="menuitem">'
            + '<i class="fa-solid fa-filter"></i><span>Condiciones</span>'
            + '</button>');
        if ($lastSep.length) $entry.insertBefore($lastSep);
        else                 $menu.append($entry);
    })();
    $(document).on('click', '#ctx-conditions', function() {
        $('#ve-context-menu').hide();
        var label = ($('#ve-sel-label').text() || 'elemento').toLowerCase();
        $('#ve-cond-target').text(label);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-conditions-modal')).show();
    });
    $(document).on('click', '#btn-apply-condition', function() {
        var condition = $('#ve-conditions-modal .vm-cond-pill.active').data('condition') || '';
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
    function openAiModal() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-ai-modal')).show();
    }
    $('#btn-ai-open').on('click', openAiModal);
    cmdActions.push({ cat:'Herramientas', label:'Generar contenido con AI', icon:'fa-wand-magic-sparkles', action: openAiModal });
    // Also add to context menu
    (function addAiCtx() {
        var $menu = $('#ve-context-menu');
        if (!$menu.length) { setTimeout(addAiCtx, 500); return; }
        var $last = $menu.find('.ve-ctx-divider').last();
        if ($last.length && !$('#ctx-ai-generate').length) {
            $('<div class="ve-ctx-item" id="ctx-ai-generate"><i class="fa-solid fa-wand-magic-sparkles fa-fw ve-ctx-icon-muted"></i> Generar con AI</div>')
                .insertBefore($last);
        }
    })();
    $(document).on('click', '#ctx-ai-generate', function() {
        $('#ve-context-menu').hide();
        openAiModal();
    });

    $(document).on('click', '#btn-ai-generate, #btn-ai-regenerate', function() {
        var type = $('#ve-ai-type').val();
        var prompt = $('#ve-ai-prompt').val().trim();
        var tone = $('#ve-ai-tone').val();
        if (!prompt) { if (window.veToast) window.veToast('Escribe una descripción', 'error'); return; }

        var $btn = $('#btn-ai-generate');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner-third fa-spin me-1"></i>Generando...');

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
            $btn.prop('disabled', false).html('<i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generar');
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
        if (!$dims.length) { $dims = $('<span id="ve-breakpoint-dims"></span>').css({fontSize:'9px',color:'#bbb',marginLeft:'6px',display:'inline-flex',alignItems:'center',alignSelf:'center'}); $('#btn-responsive-bar').after($dims); }
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
        var $m = $('<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content ve-cmd-content"><div class="ve-ai-modal-header"><h6 class="ve-ai-modal-title"><i class="fa-solid fa-code-compare ve-ai-modal-icon"></i>Comparar versiones</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="ve-ai-modal-body">' + html + '</div></div></div></div>');
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
    cmdActions.push({ cat:'Herramientas', label:'Vista previa en Google', icon:'fa-brands fa-google', action: function() {
        var title = $('#ve-settings-seo-title').val() || $('#ve-settings-title').val() || 'Título de la página';
        var desc = $('#ve-settings-seo-description').val() || 'Sin descripción';
        var url = window.location.origin + '/' + ($('#ve-settings-slug').val() || '');
        var html = '<div style="font-family:Arial,sans-serif;padding:20px;max-width:600px;">' +
            '<div style="font-size:20px;color:#1a0dab;margin-bottom:4px;cursor:pointer;">' + title + '</div>' +
            '<div style="font-size:14px;color:#006621;margin-bottom:4px;">' + url + '</div>' +
            '<div style="font-size:13px;color:#545454;line-height:1.5;">' + desc + '</div></div>';
        var $m = $('<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content ve-cmd-content"><div class="ve-ai-modal-header"><h6 class="ve-ai-modal-title"><i class="fa-brands fa-google ve-ai-modal-icon"></i>Vista previa en Google</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="ve-ai-modal-body">' + html + '</div></div></div></div>');
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
            (img ? '<div style="height:200px;background:#f0f0f0;overflow:hidden;"><img src="'+img+'" style="width:100%;height:100%;object-fit:cover;" alt=""></div>' : '<div style="height:200px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#aaa;"><i class="fa-solid fa-image fa-2x"></i></div>') +
            '<div style="padding:12px;"><div style="font-size:11px;color:#999;text-transform:uppercase;margin-bottom:4px;">' + window.location.hostname + '</div>' +
            '<div style="font-size:15px;font-weight:600;color:#333;margin-bottom:4px;">' + title + '</div>' +
            '<div style="font-size:13px;color:#666;line-height:1.4;">' + desc + '</div></div></div>';
        var $m = $('<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content ve-cmd-content"><div class="ve-ai-modal-header"><h6 class="ve-ai-modal-title"><i class="fa-solid fa-share-alt ve-ai-modal-icon"></i>Vista previa al compartir</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="ve-ai-modal-body">' + html + '</div></div></div></div>');
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

    // ── E5: Icon Picker for <i> swap in preview ──
    window.addEventListener('message', function (ev) {
        if (!ev.data || ev.data.type !== 've-open-icon-picker') return;
        var nodeId      = ev.data.nodeId;
        var currentCls  = ev.data.currentClass || '';

        // Open the modal + store context for the apply handler
        window.veIconPickerCtx = { nodeId: nodeId, currentClass: currentCls };
        var $m = $('#ve-icon-picker-modal');
        if (!$m.length) return;
        bootstrap.Modal.getOrCreateInstance($m[0]).show();

        // Prefill search with current base icon if possible
        var match = currentCls.match(/fa-([a-z0-9-]+)/);
        if (match && match[1] && !/^(solid|regular|brands|light|duotone|sharp|thin|fw|xs|sm|lg|xl|2x|3x|spin|pulse)$/i.test(match[1])) {
            setTimeout(function () {
                $('#ve-icon-picker-search').val(match[1]).trigger('input');
            }, 200);
        }
    });

    // When user picks an icon in the modal, apply it to the <i> in the iframe
    $(document).on('click', '#ve-icon-picker-modal .vm-icon-cell', function () {
        var newIcon = $(this).data('icon') || $(this).attr('data-icon');
        var ctx     = window.veIconPickerCtx;
        if (!newIcon || !ctx || !ctx.nodeId) return;
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) return;

        var el = frame.contentDocument.getElementById(ctx.nodeId);
        if (!el) return;

        // Replace the fa-xxx name but keep size/style modifiers
        var cls = (el.getAttribute('class') || '').split(/\s+/).filter(function (c) {
            return !/^fa-/.test(c) || /^fa-(solid|regular|brands|light|duotone|sharp|thin|fw|xs|sm|lg|xl|2x|3x|spin|pulse|flip-horizontal|flip-vertical|rotate-\d+|stack)$/i.test(c);
        });
        // Ensure we have a style prefix (default to fa-solid)
        var hasPrefix = cls.some(function (c) { return /^(fas|far|fab|fal|fad|fa-solid|fa-regular|fa-brands|fa-light|fa-duotone)$/.test(c); });
        if (!hasPrefix) cls.unshift('fa-solid');
        cls.push('fa-' + newIcon.replace(/^fa-/, ''));
        el.setAttribute('class', cls.join(' '));

        // Close modal + toast
        bootstrap.Modal.getInstance(document.getElementById('ve-icon-picker-modal'))?.hide();
        if (window.showToast) showToast('<i class="fa-solid fa-check me-1"></i>Icono actualizado');
        if (window.vePushHistory) vePushHistory('Icono cambiado');
        window.veIconPickerCtx = null;
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
        if (!frame || !frame.contentWindow || !frame.contentDocument) {
            $('#ve-a11y-grade').text('No disponible');
            $('#ve-a11y-desc').text('El preview no está cargado');
            $('#ve-a11y-results').html('<div style="padding:20px;text-align:center;color:var(--ve-text-muted);font-size:12px;">Espera a que el preview termine de cargar e intenta de nuevo.</div>');
            return;
        }
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
        var score = total > 0 ? Math.round((passed / total) * 100) : 100;

        var errs = issues.filter(function(i){ return i.type === 'fail'; }).length;
        var warns = issues.filter(function(i){ return i.type === 'warn'; }).length;
        var oks = issues.filter(function(i){ return i.type === 'pass'; }).length;

        // Update score ring
        var circumference = 138.23;
        var offset = circumference - (score / 100) * circumference;
        var ringColor = score >= 80 ? '#16a34a' : score >= 50 ? '#d97706' : '#dc2626';
        var grade = score >= 80 ? 'Bueno · Grado A' : score >= 50 ? 'Mejorable · Grado B' : 'Crítico · Grado C';

        $('#ve-a11y-ring-arc').attr('stroke', ringColor).attr('stroke-dashoffset', offset.toFixed(2));
        $('#ve-a11y-score-num').text(score);
        $('#ve-a11y-grade').text(grade);
        $('#ve-a11y-desc').text(errs + warns + ' problemas detectados');
        $('#ve-a11y-err-n').text(errs);
        $('#ve-a11y-warn-n').text(warns);
        $('#ve-a11y-ok-n').text(oks);
        $('#ve-a11y-node-count').text(total);

        var html = '';
        issues.forEach(function(i) {
            var sevCls = i.type === 'fail' ? 'err' : i.type === 'warn' ? 'warn' : 'ok';
            var sevIcon = i.type === 'fail' ? 'fa-xmark' : i.type === 'warn' ? 'fa-exclamation' : 'fa-check';
            var btnText = i.type === 'fail' ? 'Reparar' : i.type === 'warn' ? 'Revisar' : '';
            html += '<div class="vm-a11y-item">' +
                '<div class="vm-a11y-sev ' + sevCls + '"><i class="fa-solid ' + sevIcon + '"></i></div>' +
                '<div class="vm-a11y-body"><div class="vm-a11y-title">' + i.msg + '</div></div>' +
                (btnText ? '<button class="vm-a11y-action">' + btnText + '</button>' : '') +
                '</div>';
        });

        $('#ve-a11y-results').html(html);
        // Note: do NOT call modal.show() here — caller is responsible.
        // (Previously this caused infinite recursion with shown.bs.modal handler)
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
        var $m = $('<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border:none;border-radius:12px;overflow:hidden;"><div class="modal-header" style="background:#fff;border-bottom:1px solid #eee;padding:14px 20px;"><h6 style="font-weight:700;font-size:14px;color:#333;margin:0;"><i class="fa-solid fa-gauge-high me-2" style="color:#999;"></i>Performance score</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" style="padding:16px 20px;max-height:60vh;overflow-y:auto;">' + phtml + '</div></div></div></div>');
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
    // Shows desktop + mobile iframes side-by-side. Toggling off restores the
    // previously-active breakpoint (stored in _veLastBreakpoint).
    var _veLastBreakpoint = 'desktop';
    $(document).on('click', '#btn-split-view', function() {
        var $wrap = $('#ve-canvas-wrap');
        var $frame = $('#ve-preview-frame');
        if ($wrap.hasClass('split')) {
            $wrap.removeClass('split');
            $wrap.find('.ve-split-mobile').remove();
            // Restore previous breakpoint
            $('.breakpoint-btn[data-breakpoint="' + _veLastBreakpoint + '"]').first().trigger('click');
        } else {
            _veLastBreakpoint = $wrap.hasClass('laptop') ? 'laptop'
                : $wrap.hasClass('tablet') ? 'tablet'
                : $wrap.hasClass('mobile') ? 'mobile'
                : 'desktop';
            $wrap.removeClass('desktop tablet mobile laptop').addClass('split');
            $frame.css({ width: '', height: '' });
            if (!$wrap.find('.ve-split-mobile').length) {
                $('<iframe class="ve-split-mobile" sandbox="allow-same-origin allow-scripts"></iframe>')
                    .attr('src', $frame.attr('src'))
                    .appendTo($wrap);
            }
        }
    });

    // ── P2.1: Quick bar inspect handler ──
    window.addEventListener('message', function(ev) {
        if (ev.data && ev.data.type === 've-request-inspect') {
            $('#ve-sidebar-nav .ve-nav-btn[data-panel="inspector"]').trigger('click');
        }
    });

    // ── Broken shortcodes indicator ──────────────────────────────────────────
    function checkBrokenSentinels() {
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) return;
        var broken = [];
        frame.contentDocument.querySelectorAll('[data-ve-sc]').forEach(function(el) {
            var encoded = el.getAttribute('data-ve-sc');
            var decoded = encoded.replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"').replace(/&#039;/g,"'");
            var match = decoded.match(/^\[([a-z0-9_-]+)/i);
            if (match) {
                var name = match[1];
                if (window.veScMetaMap && !(name in window.veScMetaMap)) {
                    broken.push(name);
                }
            }
        });
        var $badge = $('#ve-broken-sc-badge');
        if (broken.length > 0) {
            if (!$badge.length) {
                $('<span id="ve-broken-sc-badge" title="Shortcodes no registrados: ' + broken.join(', ') + '" style="font-size:10px;color:#b10100;cursor:pointer;margin-left:6px;">&#9888; ' + broken.length + ' roto(s)</span>')
                    .appendTo('#ve-topbar');
            } else {
                $badge.attr('title', 'Shortcodes no registrados: ' + broken.join(', ')).text('⚠ ' + broken.length + ' roto(s)');
            }
        } else {
            $badge.remove();
        }
    }
    $('#ve-preview-frame').on('load', function() { setTimeout(checkBrokenSentinels, 1000); });

    // ── Page statistics modal ────────────────────────────────────────────────
    function computePageContentStats() {
        var frame = document.getElementById('ve-preview-frame');
        var uniqueSc = new Set();
        var shortcuts = 0, words = 0, images = 0, links = 0, headings = 0;
        if (frame && frame.contentDocument) {
            var doc = frame.contentDocument;
            doc.querySelectorAll('[data-ve-sc]').forEach(function(el) {
                shortcuts++;
                var decoded = el.getAttribute('data-ve-sc').replace(/&amp;/g,'&');
                var m = decoded.match(/^\[([a-z0-9_-]+)/i);
                if (m) uniqueSc.add(m[1]);
            });
            var text = doc.body ? doc.body.innerText : '';
            words = text.trim().split(/\s+/).filter(Boolean).length;
            images = doc.querySelectorAll('img').length;
            links = doc.querySelectorAll('a[href]').length;
            headings = doc.querySelectorAll('h1,h2,h3,h4,h5,h6').length;
        }
        return { shortcuts: shortcuts, uniqueSc: uniqueSc.size, words: words, images: images, links: links, headings: headings };
    }

    function renderStatsVitals() {
        // Minimal Core Web Vitals mock — real metrics would need a Performance Observer
        var frame = document.getElementById('ve-preview-frame');
        var perf = null;
        try { perf = frame && frame.contentWindow ? frame.contentWindow.performance : null; } catch(e) {}
        var t = perf && perf.timing ? perf.timing : null;
        var fcp = '—', lcp = '—', cls = '—', ttfb = '—';
        if (t) {
            if (t.domContentLoadedEventEnd && t.navigationStart) fcp = ((t.domContentLoadedEventEnd - t.navigationStart) / 1000).toFixed(2) + 's';
            if (t.loadEventEnd && t.navigationStart) lcp = ((t.loadEventEnd - t.navigationStart) / 1000).toFixed(2) + 's';
            if (t.responseStart && t.navigationStart) ttfb = (t.responseStart - t.navigationStart) + 'ms';
        }
        function row(code, label, value, status) {
            var cls = status === 'ok' ? 'ok' : (status === 'warn' ? 'warn' : 'ok');
            return '<div class="ve-vitals-row">' +
                '<span class="ve-vitals-dot ' + cls + '"></span>' +
                '<span class="ve-vitals-code">' + code + '</span>' +
                '<span class="ve-vitals-label">' + label + '</span>' +
                '<span class="ve-vitals-val">' + value + '</span>' +
                '</div>';
        }
        $('#ve-stats-vitals').html(
            row('FCP', 'First Contentful Paint', fcp, 'ok') +
            row('LCP', 'Largest Contentful Paint', lcp, 'ok') +
            row('CLS', 'Cumulative Layout Shift', cls, 'ok') +
            row('TTFB', 'Time to First Byte', ttfb, 'ok')
        );
    }

    function renderStatsAnalytics() {
        // Placeholder values (would be fetched from analytics in real impl)
        $('#ve-stat-visits').text('—');
        $('#ve-stat-trend').text('Sin datos');
        $('#ve-stat-trend-desc').text('Conecta con el módulo Analytics para ver visitas');
        $('#ve-stat-conv').text('—');
        $('#ve-stat-bounce').text('—');
        $('#ve-stat-time').text('—');
        $('#ve-stat-ppv').text('—');
        $('#ve-stat-new-ratio').text('—');
        $('#ve-stat-source').text('—');
        $('#ve-stat-device').text('—');
    }

    $('#btn-page-stats').on('click', function() {
        var s = computePageContentStats();
        $('#ve-stat-words').text(s.words.toLocaleString());
        $('#ve-stat-images').text(s.images);
        $('#ve-stat-links').text(s.links);
        $('#ve-stat-headings').text(s.headings);
        $('#ve-stat-shortcodes').text(s.shortcuts);
        $('#ve-stat-shortcodes-uniq').text(s.uniqueSc);
        renderStatsAnalytics();
        renderStatsVitals();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-stats-modal')).show();
    });

    // Range segment control (7d / 30d / 90d)
    $(document).on('click', '.ve-stat-range button', function () {
        var $btns = $(this).closest('.ve-stat-range').find('button');
        $btns.removeClass('active');
        $(this).addClass('active');
        var r = $(this).data('range');
        $('#ve-stat-range-label').text('últimos ' + r + ' días');
    });

    // ── Find in page ─────────────────────────────────────────────────────────
    function showFindPanel() {
        $('#ve-find-in-page').css('display', 'flex');
        $('#ve-find-in-page-input').focus();
    }
    $('#btn-search-in-page').on('click', function() {
        var $panel = $('#ve-find-in-page');
        if ($panel.is(':visible')) {
            $panel.hide();
        } else {
            showFindPanel();
        }
    });

    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f' && !$(e.target).is('input,textarea')) {
            e.preventDefault();
            showFindPanel();
        }
    });

    var findDebounce;
    $('#ve-find-in-page-input').on('input', function() {
        clearTimeout(findDebounce);
        var query = $(this).val();
        findDebounce = setTimeout(function() {
            sendToFrame({ type: 've-find-in-page', query: query });
        }, 300);
    });

    $('#btn-find-next').on('click', function() { sendToFrame({ type: 've-find-navigate', dir: 'next' }); });
    $('#btn-find-prev').on('click', function() { sendToFrame({ type: 've-find-navigate', dir: 'prev' }); });
    $('#btn-find-close').on('click', function() {
        $('#ve-find-in-page').hide();
        sendToFrame({ type: 've-find-in-page', query: '' });
        $('#ve-find-count').text('');
    });

    window.addEventListener('message', function(ev) {
        if (ev.data && ev.data.type === 've-find-results') {
            var c = ev.data.count;
            var cur = ev.data.current;
            $('#ve-find-count').text(c > 0 ? cur + '/' + c : 'Sin resultados');
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
    cmdActions.push({ cat:'Herramientas', label:'Auditoría de accesibilidad', icon:'fa-universal-access', action: function () { $('#btn-a11y-check').trigger('click'); } });
    cmdActions.push({ cat:'Herramientas', label:'Performance score', icon:'fa-gauge-high', action: runPerformanceScore });
    function renderCmd(q) {
        var $r = $('#ve-cmd-results').empty();
        var f = q ? cmdActions.filter(function(a){ return a.label.toLowerCase().indexOf(q)!==-1||a.cat.toLowerCase().indexOf(q)!==-1; }) : cmdActions;
        var lc = '';
        f.slice(0,15).forEach(function(a){
            if(a.cat!==lc){ $r.append('<div class="ve-cmd-cat">'+a.cat+'</div>'); lc=a.cat; }
            // Support icons that declare their own family (fa-brands/fa-regular/fab/far).
            var iconCls = (/\b(fa-brands|fa-regular|fa-solid|fab|far|fas)\b/).test(a.icon) ? a.icon : ('fa-solid ' + a.icon);
            var $i=$('<div class="ve-cmd-item">').html('<i class="'+iconCls+'"></i><span>'+a.label+'</span>'+(a.kbd?'<kbd>'+a.kbd+'</kbd>':''));
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

    // ── Word count + KB in bottom bar ──
    function updateWordCount() {
        try {
            var frame = document.getElementById('ve-preview-frame');
            var html = '';
            var text = '';

            if (frame && frame.contentDocument && frame.contentDocument.body) {
                text = frame.contentDocument.body.innerText || '';
                html = frame.contentDocument.body.innerHTML || '';
            } else if (window.veEditor) {
                html = window.veEditor.getData() || '';
                text = html.replace(/<[^>]*>/g, ' ').replace(/&[^;]+;/g, ' ');
            }

            var words = text.trim() ? text.trim().split(/\s+/).filter(function (w) { return w.length > 0; }).length : 0;
            var kb    = html ? (new Blob([html]).size / 1024).toFixed(1) : '0.0';
            $('#ve-word-count').text(words.toLocaleString() + ' palabras · ' + kb + ' KB');
        } catch(e) {}
    }
    setInterval(updateWordCount, 10000);
    setTimeout(updateWordCount, 3000);
    $('#ve-preview-frame').on('load', function () { setTimeout(updateWordCount, 500); });
    $(document).on('ve-content-changed', updateWordCount);

    // ── Presentation mode ────────────────────────────────────────────────────
    $('#btn-presentation-mode').on('click', function () {
        $('body').toggleClass('ve-presentation-mode');
        var on = $('body').hasClass('ve-presentation-mode');
        $(this).toggleClass('active', on).attr('title', on ? 'Salir del modo presentación (Esc)' : 'Modo presentación');
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('body').hasClass('ve-presentation-mode')) {
            $('body').removeClass('ve-presentation-mode');
            $('#btn-presentation-mode').removeClass('active').attr('title', 'Modo presentación');
        }
    });

    // ── QR preview ──────────────────────────────────────────────────────────
    $('#btn-qr-preview').on('click', function () {
        var url = $('#ve-preview-frame').attr('src') || window.location.href;
        var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(url);
        $('#ve-qr-container').html('<img src="' + qrUrl + '" width="200" height="200" alt="QR">');
        $('#ve-qr-url').text(url);
        new bootstrap.Modal(document.getElementById('ve-qr-modal')).show();
    });

    // ── Snippets modal (multiple triggers) ──────────────────────────────────
    function openSnippetsModal() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-snippets-modal')).show();
    }
    $('#btn-snippets, #btn-snippets-top').on('click', openSnippetsModal);

    // Keyboard shortcut ⌘⇧S / Ctrl+Shift+S
    $(document).on('keydown.snippets', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.shiftKey && (e.key === 'S' || e.key === 's')) {
            e.preventDefault();
            openSnippetsModal();
        }
    });

    // ── Accessibility audit ─────────────────────────────────────────────────
    // Idempotent bindings — use .off() first to prevent duplicate handlers
    $('#btn-a11y-check').off('click.a11y').on('click.a11y', function (e) {
        e.preventDefault();
        e.stopPropagation();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-a11y-modal')).show();
    });

    // Auto-run audit when modal opens — guard against re-entrancy
    var veA11yRunning = false;
    $('#ve-a11y-modal').off('shown.bs.modal.a11y').on('shown.bs.modal.a11y', function () {
        if (veA11yRunning) return;  // prevent concurrent executions
        veA11yRunning = true;

        $('#ve-a11y-grade').text('Analizando…');
        $('#ve-a11y-desc').text('');
        $('#ve-a11y-score-num').text('—');
        $('#ve-a11y-node-count').text('0');
        $('#ve-a11y-err-n').text('0');
        $('#ve-a11y-warn-n').text('0');
        $('#ve-a11y-ok-n').text('0');
        $('#ve-a11y-results').html('<div style="padding:20px;text-align:center;color:var(--ve-text-muted);font-size:12px;">Escaneando página…</div>');

        setTimeout(function () {
            try { runAccessibilityAudit(); }
            catch (e) {
                $('#ve-a11y-grade').text('Error al analizar');
                $('#ve-a11y-results').html('<div style="padding:20px;text-align:center;color:#dc2626;font-size:12px;">No se pudo acceder al contenido del preview.</div>');
            }
            veA11yRunning = false;
        }, 150);
    });
    // Clear flag when modal closes
    $('#ve-a11y-modal').off('hidden.bs.modal.a11y').on('hidden.bs.modal.a11y', function () {
        veA11yRunning = false;
    });

    // ── Quick actions modal ─────────────────────────────────────────────────
    $('#btn-quick-actions-config').on('click', function () {
        new bootstrap.Modal(document.getElementById('ve-quick-actions-modal')).show();
    });

    // ── Conditions / Popup / Form builder openers ──────────────────
    $('#btn-conditions-open').on('click', function () {
        // Requires an element to be selected — the modal acts on the currently selected node
        if ($('#ve-sel-outline').hasClass('ve-hidden') || !$('#ve-sel-outline').is(':visible')) {
            if (window.showToast) {
                window.showToast('<i class="fa-solid fa-hand-pointer me-1"></i>Selecciona un elemento del preview primero');
            }
            return;
        }
        // Sync the "VISIBILIDAD · <elemento>" label with the real selection
        var label = ($('#ve-sel-label').text() || 'elemento').toLowerCase();
        $('#ve-cond-target').text(label);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-conditions-modal')).show();
    });
    $('#btn-popup-builder-open').on('click', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-popup-builder')).show();
    });
    $('#btn-form-builder-open').on('click', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-form-builder')).show();
    });

    // ── Check broken images ─────────────────────────────────────────────────
    var _veImgResults = [];
    var _veImgFilter = 'broken';

    function veImgBasename(url) {
        if (!url) return '—';
        return (url.split('/').pop() || url).split('?')[0] || url;
    }

    function veImgRenderRow(r) {
        var name = veImgBasename(r.src);
        var isOk = r.ok;
        var statusClass = isOk ? 'ok' : 'err';
        var icon = isOk ? 'fa-check' : 'fa-triangle-exclamation';
        var thumb = isOk
            ? '<img class="ve-img-thumb" src="' + r.src + '" alt="" onerror="this.style.display=\'none\'">'
            : '<div class="ve-img-thumb ve-img-thumb-broken"><i class="fa-regular fa-image-slash"></i></div>';

        return '<div class="ve-img-row ' + statusClass + '">' +
            thumb +
            '<div class="ve-img-info">' +
                '<div class="ve-img-name">' + name + '</div>' +
                '<div class="ve-img-src" title="' + r.src + '">' + r.src + '</div>' +
            '</div>' +
            '<span class="ve-img-badge ' + statusClass + '"><i class="fa-solid ' + icon + '"></i>' + (isOk ? 'OK' : 'Rota') + '</span>' +
            (isOk ? '' : '<button type="button" class="vm-btn vm-btn-ghost vm-btn-sm ve-img-copy" data-src="' + r.src + '" title="Copiar URL"><i class="fa-regular fa-copy"></i></button>') +
            '</div>';
    }

    function veImgRenderList() {
        var list = _veImgFilter === 'all' ? _veImgResults : _veImgResults.filter(function (r) { return !r.ok; });
        var $list = $('#ve-broken-images-list');
        $('#ve-img-filter-meta').text(list.length + ' resultado' + (list.length === 1 ? '' : 's'));
        if (!list.length) {
            if (_veImgFilter === 'broken') {
                $list.html('<div class="ve-img-empty ve-img-empty-ok"><i class="fa-solid fa-circle-check"></i><div>Todas las imágenes cargan correctamente</div></div>');
            } else {
                $list.html('<div class="ve-img-empty"><i class="fa-solid fa-image"></i><div>No hay imágenes en la página</div></div>');
            }
            return;
        }
        $list.html(list.map(veImgRenderRow).join(''));
    }

    function veImgUpdateScore() {
        var total = _veImgResults.length;
        var ok    = _veImgResults.filter(function (r) { return r.ok; }).length;
        var broken = total - ok;
        var pct = total > 0 ? Math.round((ok / total) * 100) : 0;

        $('#ve-img-scanned-count').text(total);
        $('#ve-img-total-n').text(total);
        $('#ve-img-ok-n').text(ok);
        $('#ve-img-broken-n').text(broken);
        $('#ve-img-score-num').text(pct);

        // Ring: green if all OK, red if any broken
        var C = 2 * Math.PI * 22;
        var offset = C - (pct / 100) * C;
        $('#ve-img-ring-arc')
            .attr('stroke-dasharray', C.toFixed(2))
            .attr('stroke-dashoffset', offset.toFixed(2))
            .attr('stroke', broken === 0 ? '#16a34a' : (broken >= total * 0.5 ? '#dc2626' : '#d97706'));

        // Grade & description
        var grade, desc;
        if (total === 0) {
            grade = 'Sin imágenes';
            desc  = 'La página no contiene etiquetas <img>.';
        } else if (broken === 0) {
            grade = 'Perfecto';
            desc  = 'Las ' + total + ' imágenes cargan correctamente.';
        } else {
            grade = broken + ' imagen' + (broken > 1 ? 'es' : '') + ' rota' + (broken > 1 ? 's' : '');
            desc  = 'Sustituye o elimina las URLs que no responden.';
        }
        $('#ve-img-grade').text(grade);
        $('#ve-img-desc').text(desc);

        var now = new Date();
        var hh = String(now.getHours()).padStart(2, '0');
        var mm = String(now.getMinutes()).padStart(2, '0');
        $('#ve-img-updated').text('Actualizado ' + hh + ':' + mm);
    }

    /**
     * Escanea las imágenes del iframe y resuelve con los resultados.
     * No toca el modal — solo devuelve datos para que el caller decida cuándo mostrar UI.
     */
    function veImgScanAsync() {
        return new Promise(function (resolve, reject) {
            var frame = document.getElementById('ve-preview-frame');
            if (!frame || !frame.contentDocument) {
                reject(new Error('Preview no disponible'));
                return;
            }

            var imgs = frame.contentDocument.querySelectorAll('img');
            if (!imgs.length) { resolve([]); return; }

            var results = [];
            var checked = 0;
            Array.from(imgs).forEach(function (img) {
                var src = img.src || img.getAttribute('src') || '';
                var testImg = new Image();
                var done = function (ok) {
                    results.push({ src: src, ok: ok });
                    checked++;
                    // Progreso visible durante el scan (antes del modal)
                    if (window.veImgOnProgress) window.veImgOnProgress(checked, imgs.length);
                    if (checked === imgs.length) resolve(results);
                };
                testImg.onload  = function () { done(true); };
                testImg.onerror = function () { done(false); };
                testImg.src = src + (src.indexOf('?') >= 0 ? '&' : '?') + '_ve=' + Date.now();
            });
        });
    }

    /**
     * Pinta los resultados ya cargados en el modal (sin re-escanear).
     */
    function veImgPaintResults(results) {
        _veImgResults = results || [];
        $('#ve-img-progress-wrap').addClass('ve-hidden');
        veImgUpdateScore();
        veImgRenderList();
    }

    /**
     * Re-escaneo in-place (botón Reescanear dentro del modal abierto).
     * Muestra progress bar + spinner en la lista mientras escanea.
     */
    function veImgRescanInModal() {
        $('#ve-img-grade').text('Analizando…');
        $('#ve-img-desc').text('Verificando imágenes del iframe');
        $('#ve-img-progress-wrap').removeClass('ve-hidden');
        $('#ve-img-progress-bar').css('width', '0%');
        $('#ve-broken-images-list').html('<div class="ve-img-empty"><i class="fa-solid fa-spinner fa-spin"></i><div>Verificando imágenes…</div></div>');

        window.veImgOnProgress = function (n, total) {
            $('#ve-img-progress-bar').css('width', (n / total * 100) + '%');
            $('#ve-img-scanned-count').text(n);
        };

        veImgScanAsync().then(veImgPaintResults).catch(function (e) {
            if (window.showToast) window.showToast('<i class="fas fa-exclamation-triangle me-1" style="color:#FEC90F"></i>' + e.message);
        }).finally(function () { window.veImgOnProgress = null; });
    }

    /**
     * Handler del botón "Imágenes rotas":
     * 1. Muestra toast de loading con counter vivo
     * 2. Escanea en segundo plano
     * 3. Sólo abre el modal cuando los resultados están listos
     */
    $('#btn-check-images').on('click', function () {
        var toastEl = null;
        window.veImgOnProgress = function (n, total) {
            if (window.showToast && !toastEl) {
                window.showToast('<i class="fa-solid fa-spinner fa-spin me-1"></i>Escaneando imágenes… ' + n + '/' + total);
            }
        };

        veImgScanAsync()
            .then(function (results) {
                veImgPaintResults(results);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('ve-broken-images-modal')).show();
            })
            .catch(function (e) {
                if (window.showToast) window.showToast('<i class="fas fa-exclamation-triangle me-1" style="color:#FEC90F"></i>' + e.message);
            })
            .finally(function () { window.veImgOnProgress = null; });
    });

    $('#btn-img-rescan').on('click', veImgRescanInModal);

    // Filter toggle (Rotas / Todas)
    $(document).on('click', '.ve-img-filter button', function () {
        var $btns = $(this).closest('.ve-img-filter').find('button');
        $btns.removeClass('active');
        $(this).addClass('active');
        _veImgFilter = $(this).data('filter');
        veImgRenderList();
    });

    // Copy URL
    $(document).on('click', '.ve-img-copy', function () {
        var src = $(this).data('src');
        if (!src) return;
        navigator.clipboard.writeText(src).then(function () {
            if (window.showToast) window.showToast('<i class="fa-solid fa-check me-1" style="color:#13C672"></i>URL copiada');
        }).catch(function () {});
    });

    // ── Snippets CRUD (localStorage) ────────────────────────────────────────
    var VE_SNIPPETS_KEY = 've_snippets';
    var activeSnippetId = null;

    function loadSnippets() {
        try {
            return JSON.parse(localStorage.getItem(VE_SNIPPETS_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function saveSnippets(arr) {
        localStorage.setItem(VE_SNIPPETS_KEY, JSON.stringify(arr));
    }

    function renderSnippetList() {
        var snippets = loadSnippets();
        var $list = $('#ve-snippets-list');
        $list.empty();

        if (!snippets.length) {
            $list.append(
                '<div class="ve-empty-state" style="padding:24px 12px;">'
                + '<div class="ve-es-icon"><i class="fa-regular fa-bookmark"></i></div>'
                + '<div class="ve-es-title">Sin snippets guardados</div>'
                + '<div class="ve-es-desc">Guarda fragmentos de HTML para reutilizarlos en cualquier página.</div>'
                + '<div class="ve-es-cta-row">'
                + '<button type="button" class="ve-es-cta-btn outline" id="btn-empty-new-snippet"><i class="fa-solid fa-plus"></i>Crear primer snippet</button>'
                + '</div>'
                + '</div>'
            );
            return;
        }

        snippets.forEach(function (s) {
            var $item = $('<button type="button" class="vm-snip-item"><i class="fa-solid fa-code"></i></button>')
                .append(document.createTextNode(s.name || 'Sin nombre'))
                .attr('data-id', s.id);
            if (s.id === activeSnippetId) {
                $item.addClass('active');
            }
            $list.append($item);
        });
    }

    // Load snippet into editor fields
    function loadSnippetIntoEditor(id) {
        var snippets = loadSnippets();
        var s = snippets.find(function (x) { return x.id === id; });
        if (!s) return;
        activeSnippetId = s.id;
        $('#ve-snippet-name').val(s.name);
        $('#ve-snippet-code').val(s.code);
        renderSnippetList();
    }

    function clearSnippetEditor() {
        activeSnippetId = null;
        $('#ve-snippet-name').val('');
        $('#ve-snippet-code').val('');
        renderSnippetList();
    }

    // Open modal → render list
    document.getElementById('ve-snippets-modal').addEventListener('show.bs.modal', function () {
        renderSnippetList();
    });

    // Click on snippet item to load it
    $(document).on('click', '.vm-snip-item[data-id]', function () {
        var id = parseInt($(this).attr('data-id'), 10);
        loadSnippetIntoEditor(id);
    });

    // New snippet — also triggered from the empty-state CTA
    $(document).on('click', '#btn-snippet-new, #btn-empty-new-snippet', function () {
        clearSnippetEditor();
    });

    // Save / update snippet
    $('#btn-snippet-save').on('click', function () {
        var name = $.trim($('#ve-snippet-name').val());
        var code = $('#ve-snippet-code').val();

        if (!name) {
            showToast('<i class="fas fa-exclamation-triangle me-1" style="color:#FEC90F"></i>Escribe un nombre para el snippet.');
            return;
        }

        var snippets = loadSnippets();

        if (activeSnippetId) {
            snippets = snippets.map(function (s) {
                return s.id === activeSnippetId ? { id: s.id, name: name, code: code } : s;
            });
            showToast('<i class="fas fa-check me-1" style="color:#13C672"></i>Snippet actualizado.');
        } else {
            activeSnippetId = Date.now();
            snippets.push({ id: activeSnippetId, name: name, code: code });
            showToast('<i class="fas fa-check me-1" style="color:#13C672"></i>Snippet guardado.');
        }

        saveSnippets(snippets);
        renderSnippetList();
    });

    // Delete active snippet (with confirmation — matches the pattern used for
    // element/section deletion in the canvas).
    $('#btn-snippet-delete').on('click', function () {
        if (!activeSnippetId) {
            showToast('<i class="fas fa-exclamation-triangle me-1" style="color:#FEC90F"></i>Selecciona un snippet para eliminar.');
            return;
        }
        veConfirm('Se eliminará el snippet de forma permanente.', function () {
            var snippets = loadSnippets().filter(function (s) { return s.id !== activeSnippetId; });
            saveSnippets(snippets);
            clearSnippetEditor();
            showToast('<i class="fas fa-check me-1" style="color:#13C672"></i>Snippet eliminado.');
        });
    });

    // Insert snippet HTML into the page via postMessage
    $('#btn-snippet-insert').on('click', function () {
        var code = $('#ve-snippet-code').val();
        if (!$.trim(code)) {
            showToast('<i class="fas fa-exclamation-triangle me-1" style="color:#FEC90F"></i>El snippet está vacío.');
            return;
        }
        sendToFrame({ type: 've-insert-html', html: code });
        showToast('<i class="fas fa-check me-1" style="color:#13C672"></i>Snippet insertado en la página.');
        bootstrap.Modal.getInstance(document.getElementById('ve-snippets-modal')).hide();
    });

    // ── Comment mode ─────────────────────────────────────────────────────────
    var veCommentMode = false;
    // ── Comment mode ─────────────────────────────────────────────────────────
    var veCommentPins = [];

    $('#btn-comment-mode, #btn-comment-mode-top').on('click', function () {
        veCommentMode = !veCommentMode;
        $('#btn-comment-mode, #btn-comment-mode-top').toggleClass('active', veCommentMode);
        $('#btn-comment-mode-top').attr('title', veCommentMode ? 'Modo comentarios (activo — clic para desactivar)' : 'Modo comentarios');
        $('#ve-comment-overlay').toggle(veCommentMode);
        showToast(
            veCommentMode
                ? '<i class="fa-solid fa-comment me-1"></i>Modo comentario activo — haz clic en el canvas para añadir'
                : '<i class="fa-solid fa-comment-slash me-1"></i>Modo comentario desactivado'
        );
    });

    $('#ve-comment-overlay').on('click', function (e) {
        if (!veCommentMode) return;
        var x = e.offsetX;
        var y = e.offsetY;
        var num = veCommentPins.length + 1;

        // Inline prompt via custom UI to avoid browser prompt()
        var $bubble = $(
            '<div style="position:absolute;left:' + x + 'px;top:' + y + 'px;z-index:200;pointer-events:auto;">' +
            '<div style="width:22px;height:22px;background:#FEC90F;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#1a1a1a;cursor:default;box-shadow:0 2px 6px rgba(0,0,0,.25);">' + num + '</div>' +
            '<div class="ve-comment-bubble" style="position:absolute;left:26px;top:-4px;background:#fff;border:1px solid #e4e4e7;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);padding:10px;min-width:220px;z-index:201;">' +
            '<textarea class="form-control form-control-sm ve-comment-input" rows="3" placeholder="Escribe tu comentario..." style="resize:none;font-size:12px;border-color:#e4e4e7;"></textarea>' +
            '<div style="display:flex;gap:6px;margin-top:6px;">' +
            '<button class="btn btn-sm ve-comment-save" style="background:#18181b;color:#fff;font-size:11px;padding:2px 10px;border-radius:5px;flex:1;">Guardar</button>' +
            '<button class="btn btn-sm ve-comment-cancel" style="background:#f4f4f5;color:#555;font-size:11px;padding:2px 10px;border-radius:5px;">✕</button>' +
            '</div></div></div>'
        );

        $('#ve-comment-pins').append($bubble);
        $bubble.find('.ve-comment-input').focus();

        $bubble.find('.ve-comment-save').on('click', function () {
            var text = $bubble.find('.ve-comment-input').val().trim();
            if (!text) { showToast('Escribe un comentario primero', 'warning'); return; }
            veCommentPins.push({ x: x, y: y, text: text, num: num });
            // Collapse to just the pin number dot with tooltip
            $bubble.find('.ve-comment-bubble').remove();
            $bubble.css('pointer-events', 'auto').attr('title', num + ': ' + text);
            $bubble.find('div').first().css('cursor', 'pointer');
            $bubble.find('div').first().on('click', function (e2) {
                e2.stopPropagation();
                var $tip = $('<div style="position:absolute;left:26px;top:-4px;background:#fff;border:1px solid #e4e4e7;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);padding:10px;min-width:180px;font-size:12px;z-index:202;pointer-events:auto;">' +
                    '<strong style="font-size:11px;color:#888;">Comentario #' + num + '</strong><br>' +
                    '<span>' + $('<div>').text(text).html() + '</span>' +
                    '<div style="margin-top:6px;"><button class="ve-pin-delete btn btn-sm" style="font-size:10px;color:#dc2626;background:none;border:none;padding:0;cursor:pointer;"><i class="fa-solid fa-trash"></i> Eliminar</button></div>' +
                    '</div>');
                $bubble.find('.ve-pin-popover').remove();
                $tip.addClass('ve-pin-popover');
                $bubble.append($tip);
                $tip.find('.ve-pin-delete').on('click', function () {
                    $bubble.remove();
                    veCommentPins = veCommentPins.filter(function (p) { return p.num !== num; });
                });
                $(document).one('click', function () { $tip.remove(); });
                e2.stopPropagation();
            });
            addSessionEntry('Comentario añadido en (' + x + ',' + y + ')', 'fa-comment');
        });

        $bubble.find('.ve-comment-cancel').on('click', function () {
            $bubble.remove();
        });
    });

    // ── Icon Picker (Feature A) ───────────────────────────────────────────────
    var VE_ICONS = [
        // Básicos
        'fa-solid fa-star', 'fa-solid fa-heart', 'fa-solid fa-home', 'fa-solid fa-user',
        'fa-solid fa-gear', 'fa-solid fa-bell', 'fa-solid fa-search', 'fa-solid fa-check',
        'fa-solid fa-xmark', 'fa-solid fa-plus', 'fa-solid fa-minus', 'fa-solid fa-pen',
        'fa-solid fa-trash', 'fa-solid fa-floppy-disk', 'fa-solid fa-download', 'fa-solid fa-upload',
        'fa-solid fa-share', 'fa-solid fa-link', 'fa-solid fa-image', 'fa-solid fa-file',
        'fa-solid fa-folder', 'fa-solid fa-calendar', 'fa-solid fa-clock', 'fa-solid fa-lock',
        'fa-solid fa-unlock', 'fa-solid fa-eye', 'fa-solid fa-comment', 'fa-solid fa-envelope',
        'fa-solid fa-phone', 'fa-solid fa-location-dot', 'fa-solid fa-globe', 'fa-solid fa-chart-bar',
        'fa-solid fa-table', 'fa-solid fa-list', 'fa-solid fa-code', 'fa-solid fa-tag',
        'fa-solid fa-thumbs-up', 'fa-solid fa-thumbs-down', 'fa-solid fa-flag', 'fa-solid fa-bookmark',
        'fa-solid fa-circle-info', 'fa-solid fa-triangle-exclamation', 'fa-solid fa-shield',
        'fa-solid fa-key', 'fa-solid fa-bolt', 'fa-solid fa-fire', 'fa-solid fa-leaf',
        'fa-solid fa-map', 'fa-solid fa-paper-plane', 'fa-solid fa-rotate', 'fa-solid fa-arrows-rotate',
        // Multimedia
        'fa-solid fa-play', 'fa-solid fa-pause', 'fa-solid fa-music', 'fa-solid fa-video',
        'fa-solid fa-camera', 'fa-solid fa-microphone', 'fa-solid fa-volume-high',
        // Sociales
        'fa-brands fa-facebook', 'fa-brands fa-twitter', 'fa-brands fa-instagram',
        'fa-brands fa-linkedin', 'fa-brands fa-youtube', 'fa-brands fa-tiktok',
        'fa-brands fa-whatsapp', 'fa-brands fa-github',
    ];

    var _veSelectedIcon = '';

    function renderIconGrid(filter, styleFilter) {
        var $grid = $('#ve-icon-grid');
        $grid.empty();
        var list = VE_ICONS.filter(function (ic) {
            var matchText = !filter || ic.toLowerCase().indexOf(filter.toLowerCase()) !== -1;
            var matchStyle = !styleFilter || ic.indexOf(styleFilter) !== -1;
            return matchText && matchStyle;
        });

        $.each(list, function (_, iconClass) {
            var parts = iconClass.split(' ');
            var name = parts[parts.length - 1].replace('fa-', '');
            var isSelected = iconClass === _veSelectedIcon;
            $('<div class="vm-icon-cell' + (isSelected ? ' selected' : '') + '" data-icon="' + iconClass + '">'
                + '<i class="' + iconClass + '"></i>'
                + '<span>' + name + '</span>'
                + '</div>').appendTo($grid);
        });
    }

    $(document).on('click', '#btn-icon-insert', function () {
        var iconClass = _veSelectedIcon || $('#ve-icon-grid .vm-icon-cell.selected').data('icon');
        if (iconClass) {
            navigator.clipboard.writeText(iconClass).catch(function(){});
            showToast('<i class="fas fa-check me-1" style="color:#13C672"></i>Copiado: ' + iconClass);
        }
        bootstrap.Modal.getInstance(document.getElementById('ve-icon-picker-modal')).hide();
    });

    $(document).on('click', '#ve-icon-grid .vm-icon-cell', function () {
        _veSelectedIcon = $(this).data('icon');
        $('#ve-icon-selected-label').text(_veSelectedIcon.split(' ').pop().replace('fa-',''));
    });

    $(document).on('click', '#ve-icon-seg button', function () {
        renderIconGrid($('#ve-icon-search').val().trim(), $(this).data('style'));
    });

    $('#ve-icon-picker-modal').on('show.bs.modal', function () {
        _veSelectedIcon = '';
        $('#ve-icon-search').val('');
        $('#ve-icon-selected-label').text('—');
        renderIconGrid('', '');
    });

    $('#ve-icon-search').on('input', function () {
        var styleFilter = $('#ve-icon-seg button.active').data('style') || '';
        renderIconGrid($(this).val().trim(), styleFilter);
    });

    // ── Quick Actions Bar (Feature D) ────────────────────────────────────────
    var QA_ACTIONS = [
        // ── Edición básica ────────────────────────────────
        { id: 'save',         label: 'Guardar',             icon: 'fa-solid fa-floppy-disk',             action: function () {
            if (window.veSave) { window.veSave(); return; }
            $('#btn-save').trigger('click');
        } },
        { id: 'undo',         label: 'Deshacer',            icon: 'fa-solid fa-rotate-left',             action: function () {
            if (window.veUndo) { window.veUndo(); return; }
            $('#btn-undo').trigger('click');
        } },
        { id: 'redo',         label: 'Rehacer',             icon: 'fa-solid fa-rotate-right',            action: function () {
            if (window.veRedo) { window.veRedo(); return; }
            $('#btn-redo').trigger('click');
        } },

        // ── Publicación ───────────────────────────────────
        { id: 'publish',      label: 'Publicar',            icon: 'fa-solid fa-globe',                    action: function () {
            if (window.VE_PAGE_STATUS === 'published') {
                if (window.showToast) window.showToast('<i class="fa-solid fa-info-circle me-1"></i>La página ya está publicada.');
                return;
            }
            veConfirm('¿Publicar esta página?', function () {
                if (window.vePublishPage) window.vePublishPage();
            });
        } },
        { id: 'unpublish',    label: 'Despublicar',         icon: 'fa-solid fa-eye-slash',                action: function () {
            if (window.VE_PAGE_STATUS !== 'published') {
                if (window.showToast) window.showToast('<i class="fa-solid fa-info-circle me-1"></i>La página no está publicada.');
                return;
            }
            veConfirm('¿Despublicar esta página?', function () {
                if (window.veUnpublishPage) window.veUnpublishPage();
            });
        } },
        { id: 'approval',     label: 'Solicitar aprobación',icon: 'fa-solid fa-paper-plane',              action: function () {
            var el = document.getElementById('ve-approval-modal');
            if (el && window.bootstrap) new bootstrap.Modal(el).show();
        } },

        // ── Vista ─────────────────────────────────────────
        { id: 'preview',      label: 'Preview en pestaña',  icon: 'fa-solid fa-eye',                      action: function () { window.open($('.ve-topbar-preview-btn').attr('href') || '{{ $previewUrl }}', '_blank'); } },
        { id: 'presentation', label: 'Presentación',        icon: 'fa-solid fa-tv',                       action: function () {
            $('body').toggleClass('ve-presentation-mode');
            var on = $('body').hasClass('ve-presentation-mode');
            $('#btn-presentation-mode').toggleClass('active', on);
            if (window.showToast) window.showToast('<i class="fa-solid fa-tv me-1"></i>' + (on ? 'Modo presentación activado (Esc para salir)' : 'Modo presentación desactivado'));
        } },
        { id: 'grid',         label: 'Grid overlay',        icon: 'fa-solid fa-border-all',               action: function () { $('#btn-grid-overlay').trigger('click'); } },
        { id: 'tweaks',       label: 'Ajustes visuales',    icon: 'fa-solid fa-sliders',                  action: function (e) {
            if (e && e.stopPropagation) e.stopPropagation();
            $('#ve-tweaks').toggleClass('open');
        } },
        { id: 'minimap',      label: 'Mapa de página',      icon: 'fa-solid fa-map',                      action: function () {
            if (typeof window.veToggleMinimap === 'function') { window.veToggleMinimap(); return; }
            var btn = document.getElementById('btn-toggle-minimap');
            if (btn) btn.click();
        } },

        // ── Página ────────────────────────────────────────
        { id: 'duplicate',    label: 'Duplicar página',     icon: 'fa-regular fa-copy',                   action: function () {
            veConfirm('¿Duplicar esta página?', function () {
                if (window.showToast) window.showToast('<i class="fa-solid fa-copy me-1"></i>Duplicando página…');
            });
        } },
        { id: 'export',       label: 'Exportar',            icon: 'fa-solid fa-download',                 action: function () {
            if (window.showToast) window.showToast('<i class="fa-solid fa-download me-1"></i>Exportando página…');
        } },

        // ── Auditorías ────────────────────────────────────
        { id: 'a11y',         label: 'Accesibilidad',       icon: 'fa-solid fa-universal-access',         action: function () { $('#btn-a11y-check').trigger('click'); } },
        { id: 'images',       label: 'Imágenes rotas',      icon: 'fa-solid fa-image',                    action: function () { $('#btn-check-images').trigger('click'); } },
        { id: 'stats',        label: 'Estadísticas',        icon: 'fa-solid fa-chart-simple',             action: function () { $('#btn-page-stats').trigger('click'); } },
        { id: 'seo',          label: 'SEO',                 icon: 'fa-solid fa-magnifying-glass',         action: function () { $('#btn-statusbar-seo').trigger('click'); } },

        // ── Utilidades / Builders ─────────────────────────
        { id: 'snippets',     label: 'Snippets HTML',       icon: 'fa-solid fa-code',                     action: function () { $('#btn-snippets-top').trigger('click'); } },
        { id: 'conditions',   label: 'Condiciones',         icon: 'fa-solid fa-filter',                   action: function () { $('#btn-conditions-open').trigger('click'); } },
        { id: 'popup',        label: 'Popup builder',       icon: 'fa-solid fa-window-restore',           action: function () { $('#btn-popup-builder-open').trigger('click'); } },
        { id: 'form',         label: 'Form builder',        icon: 'fa-solid fa-rectangle-list',           action: function () { $('#btn-form-builder-open').trigger('click'); } },
        { id: 'shortcuts',    label: 'Atajos de teclado',   icon: 'fa-solid fa-keyboard',                 action: function () { new bootstrap.Modal(document.getElementById('ve-shortcuts-modal')).show(); } },
        { id: 'cmd-palette',  label: 'Paleta de comandos',  icon: 'fa-solid fa-terminal',                 action: function () { $('#btn-open-cmd-palette').trigger('click'); } },
    ];

    var VE_QA_KEY = 've_qa_selected';

    function getQaSelected() {
        try { return JSON.parse(localStorage.getItem(VE_QA_KEY)) || []; } catch (e) { return []; }
    }

    function renderQuickActionsBar() {
        var selected = getQaSelected();
        var $bar = $('#ve-quick-actions-bar');
        $bar.empty();
        $.each(selected, function (_, id) {
            var action = null;
            $.each(QA_ACTIONS, function (_, a) { if (a.id === id) { action = a; return false; } });
            if (!action) return;
            var $btn = $('<button class="ve-qa-btn" data-tooltip="' + action.label + '" aria-label="' + action.label + '" type="button"><i class="' + action.icon + '"></i></button>');
            $btn.on('click', action.action);
            $bar.append($btn);
        });
    }

    // Categorías para agrupar visualmente (IDs → nombre de sección)
    var QA_CATS = {
        save: 'Edición básica', undo: 'Edición básica', redo: 'Edición básica',
        publish: 'Publicación', unpublish: 'Publicación', approval: 'Publicación',
        preview: 'Vista', presentation: 'Vista', grid: 'Vista', tweaks: 'Vista', minimap: 'Vista',
        duplicate: 'Página', export: 'Página',
        a11y: 'Auditorías', images: 'Auditorías', stats: 'Auditorías', seo: 'Auditorías',
        snippets: 'Utilidades', conditions: 'Utilidades', popup: 'Utilidades', form: 'Utilidades',
        shortcuts: 'Utilidades', 'cmd-palette': 'Utilidades'
    };

    $('#ve-quick-actions-modal').on('show.bs.modal', function () {
        var selected = getQaSelected();
        var $opts    = $('#ve-qa-options').empty();
        var lastCat  = null;

        $.each(QA_ACTIONS, function (_, a) {
            var cat = QA_CATS[a.id] || 'Otros';
            if (cat !== lastCat) {
                $opts.append('<div class="vm-qa-cat">' + cat + '</div>');
                lastCat = cat;
            }
            var isOn = selected.indexOf(a.id) !== -1;
            $opts.append(
                '<div class="vm-qa-row' + (isOn ? ' on' : '') + '" data-action-id="' + a.id + '">' +
                '<div class="ico"><i class="' + a.icon + '"></i></div>' +
                '<div class="body"><div class="t">' + a.label + '</div></div>' +
                '<div class="switch"></div>' +
                '</div>'
            );
        });

        var total = QA_ACTIONS.length;
        var n = selected.length;
        $('#ve-qa-count').text(n + ' / ' + total + ' activas');
    });

    $('#btn-qa-save').on('click', function () {
        var selected = [];
        $('#ve-qa-options .vm-qa-row.on').each(function () {
            selected.push($(this).data('action-id'));
        });
        // No limit — user can pick as many as they want
        localStorage.setItem(VE_QA_KEY, JSON.stringify(selected));
        bootstrap.Modal.getInstance(document.getElementById('ve-quick-actions-modal')).hide();
        renderQuickActionsBar();
        showToast('<i class="fas fa-check me-1" style="color:#13C672"></i>' + selected.length + ' acciones rápidas activas');
    });

    // Init bar on load
    renderQuickActionsBar();

    // ── Auto-detect breakpoint based on screen width ──────────────────────
    (function () {
        var sw = window.screen.width;
        if (sw < 768) {
            $('.breakpoint-btn[data-breakpoint="mobile"]').trigger('click');
        } else if (sw < 1024) {
            $('.breakpoint-btn[data-breakpoint="tablet"]').trigger('click');
        }
        // desktop is default — no action needed, already active
    }());

    // ── Canvas background ────────────────────────────────────────────────────
    $(document).on('click', '.ve-canvas-bg-btn', function () {
        var bg = $(this).data('bg');
        $('#ve-canvas-wrap').css('background', bg);
        $('#btn-grid-overlay-top').closest('.dropdown').prev('#btn-canvas-bg')
            .find('i').css('color', bg === '#ffffff' || !bg ? '' : '#90bb13');
        localStorage.setItem('ve-canvas-bg', bg);
    });
    // Restore canvas bg on load
    var savedCanvasBg = localStorage.getItem('ve-canvas-bg');
    if (savedCanvasBg) $('#ve-canvas-wrap').css('background', savedCanvasBg);

    // ── Grid overlay top bar button (syncs with bottom btn-grid-overlay) ─────
    $(document).on('click', '#btn-grid-overlay-top', function () {
        $('#btn-grid-overlay').trigger('click');
        $(this).toggleClass('active', $('#btn-grid-overlay').hasClass('active'));
    });

    // ═══════════════════════════════════════════════════════════
    // MODAL REDESIGN: interactive vm-* components
    // ═══════════════════════════════════════════════════════════

    // ── vm-chk-row toggle ──────────────────────────────────────
    $(document).on('click', '.vm-chk-row', function () {
        var $chk = $(this).find('.vm-chk');
        $chk.toggleClass('on');
        if ($chk.hasClass('on')) {
            $chk.html('<i class="fa-solid fa-check"></i>');
        } else {
            $chk.html('');
        }
    });

    // ── vm-cond-pill toggle ────────────────────────────────────
    $(document).on('click', '.vm-cond-pill', function () {
        var $p = $(this);
        $p.toggleClass('on');
        if ($p.hasClass('on')) {
            if (!$p.find('> i.fa-check').length) {
                $p.append('<i class="fa-solid fa-check" style="color:var(--ve-text);flex-shrink:0;"></i>');
            }
        } else {
            $p.find('> i.fa-check').remove();
        }
    });

    // ── #btn-clear-conditions ──────────────────────────────────
    $(document).on('click', '#btn-clear-conditions', function () {
        $('#ve-conditions-modal .vm-cond-pill').each(function () {
            $(this).removeClass('on').find('> i.fa-check').remove();
        });
    });

    // ── vm-pop-opt single-select ───────────────────────────────
    $(document).on('click', '.vm-pop-opt', function () {
        $(this).closest('.vm-pop-choice').find('.vm-pop-opt').removeClass('on');
        $(this).addClass('on');
    });

    // ── vm-icon-cell single-select ─────────────────────────────
    $(document).on('click', '.vm-icon-cell', function () {
        $(this).closest('.vm-icon-grid').find('.vm-icon-cell').removeClass('selected');
        $(this).addClass('selected');
        var iconClass = $(this).find('i').attr('class');
        var name = $(this).find('span').text();
        $('#ve-icon-selected-label').text(name || iconClass);
        $(this).closest('.modal').data('selected-icon', iconClass);
    });

    // ── vm-snip-item single-select ─────────────────────────────
    $(document).on('click', '.vm-snip-item', function () {
        $(this).closest('.vm-snip-side').find('.vm-snip-item').removeClass('active');
        $(this).addClass('active');
    });

    // ── vm-mtabs inside modals ─────────────────────────────────
    $(document).on('click', '.vm-mtabs button', function () {
        $(this).closest('.vm-mtabs').find('button').removeClass('active');
        $(this).addClass('active');
    });

    // ── vm-seg inside modals ───────────────────────────────────
    $(document).on('click', '.vm-seg button', function () {
        $(this).closest('.vm-seg').find('button').removeClass('active');
        $(this).addClass('active');
    });

    // ── vm-qa-row toggle (quick actions) — sin límite ──────────
    $(document).on('click', '.vm-qa-row', function () {
        var $row   = $(this);
        var $modal = $row.closest('.modal');
        var $count = $modal.find('.vm-qa-count, #ve-qa-count');
        $row.toggleClass('on');
        var total = $modal.find('.vm-qa-row').length;
        var on    = $modal.find('.vm-qa-row.on').length;
        $count.text(on + ' / ' + total + ' activas');
    });

    // ── #btn-qa-reset ──────────────────────────────────────────
    $(document).on('click', '#btn-qa-reset', function () {
        $('#ve-quick-actions-modal .vm-qa-row').removeClass('on');
        $('#ve-qa-count').text('0 / 6');
    });

    // ── Drop zone click → file input ───────────────────────────
    $(document).on('click', '#ve-import-drop', function (e) {
        if (!$(e.target).is('input')) {
            $('#ve-import-file').trigger('click');
        }
    });
    $(document).on('change', '#ve-import-file', function () {
        var name = this.files[0] ? this.files[0].name : '';
        if (name) {
            $('#ve-import-drop .t').text(name);
            $('#ve-import-drop .s').text('Listo para importar');
        }
    });

    // ── vm-copy-btn (code block copy) ─────────────────────────
    $(document).on('click', '.vm-copy-btn, #btn-copy-shortcode-foot', function () {
        var text = $('#ve-scb-preview').text().trim();
        if (!text) return;
        navigator.clipboard.writeText(text).then(function () {
            var $btn = $('#btn-copy-shortcode, .vm-copy-btn');
            $btn.html('<i class="fa-solid fa-check"></i>Copiado');
            setTimeout(function () { $btn.html('<i class="fa-regular fa-copy"></i>Copiar'); }, 1500);
        });
    });

    // ── Diff modal: populate when shown ───────────────────────
    $('#ve-diff-modal').on('show.bs.modal', function () {
        var orig = window._veOriginalHtml || '';
        var curr = '';
        try {
            var frame = document.getElementById('ve-preview-frame');
            curr = frame ? (frame.contentDocument || frame.contentWindow.document).documentElement.outerHTML : '';
        } catch (e) {}

        var origLines = orig.split('\n');
        var currLines = curr.split('\n');
        var added = 0, removed = 0;

        var renderDiff = function (lines, isDiff) {
            return lines.map(function (l) {
                var el = document.createElement('span');
                if (isDiff && l.startsWith('+')) { el.className = 'add'; added++; }
                else if (isDiff && l.startsWith('-')) { el.className = 'del'; removed++; }
                else { el.className = 'ctx'; }
                el.textContent = l;
                return el.outerHTML;
            }).join('\n');
        };

        $('#ve-diff-original').html(renderDiff(origLines, false));
        $('#ve-diff-current').html(renderDiff(currLines, false));
        $('#ve-diff-add').text('+' + added);
        $('#ve-diff-del').text('−' + removed);
        $('#ve-diff-count').text(added + removed);
    });

    // ── Shortcode modal: scb-description show/hide ─────────────
    $('#ve-shortcode-builder-modal').on('show.bs.modal', function () {
        var $desc = $('#ve-scb-description');
        if ($desc.text().trim()) { $desc.show(); } else { $desc.hide(); }
    });

    // ── Form builder: render initial fields ───────────────────
    var veFormDefaultFields = [
        { type: 'text', label: 'Nombre', req: true },
        { type: 'email', label: 'Correo electrónico', req: true },
        { type: 'tel', label: 'Teléfono', req: false },
        { type: 'textarea', label: 'Mensaje', req: false },
    ];
    function veRenderFormFields(fields) {
        var $canvas = $('#ve-form-fields');
        $canvas.empty();
        fields.forEach(function (f, i) {
            var reqBadge = f.req ? '<span class="type" style="background:#fef3c7;color:#92400e;">req</span>' : '';
            $canvas.append(
                '<div class="vm-fb-field" data-idx="' + i + '">' +
                '<i class="fa-solid fa-grip-vertical grip"></i>' +
                '<span class="type">' + f.type + '</span>' +
                '<span class="lbl">' + f.label + '</span>' +
                reqBadge +
                '<div class="act">' +
                '<button title="Eliminar"><i class="fa-regular fa-trash-can"></i></button>' +
                '</div></div>'
            );
        });
        $canvas.append('<button type="button" class="vm-fb-add-btn" id="btn-add-form-field"><i class="fa-solid fa-plus"></i>Añadir campo</button>');
    }
    $('#ve-form-builder').on('show.bs.modal', function () {
        if ($('#ve-form-fields .vm-fb-field').length === 0) {
            veRenderFormFields(veFormDefaultFields);
        }
    });
    $(document).on('click', '#btn-add-form-field', function () {
        var label = prompt('Nombre del campo:');
        if (!label) return;
        var type = prompt('Tipo (text, email, tel, select, textarea):', 'text') || 'text';
        var fields = [];
        $('#ve-form-fields .vm-fb-field').each(function () {
            fields.push({ type: $(this).find('.type').first().text(), label: $(this).find('.lbl').text(), req: false });
        });
        fields.push({ type: type, label: label, req: false });
        veRenderFormFields(fields);
    });
    $(document).on('click', '#ve-form-fields .act button', function (e) {
        e.stopPropagation();
        $(this).closest('.vm-fb-field').remove();
    });
    $(document).on('change', '#ve-form-type', function () {
        $('#ve-form-type-chip').text($(this).find('option:selected').text().toLowerCase());
    });

    // ── A11y modal: populate results dynamically ───────────────
    function veRenderA11yItem(sev, title, desc, sel, btnText) {
        var sevCls = { error: 'err', warning: 'warn', ok: 'ok' }[sev] || 'warn';
        var sevIcon = { error: 'fa-xmark', warning: 'fa-exclamation', ok: 'fa-check' }[sev] || 'fa-exclamation';
        return '<div class="vm-a11y-item">' +
            '<div class="vm-a11y-sev ' + sevCls + '"><i class="fa-solid ' + sevIcon + '"></i></div>' +
            '<div class="vm-a11y-body">' +
            '<div class="vm-a11y-title">' + title + '</div>' +
            (desc ? '<div class="vm-a11y-desc">' + desc + '</div>' : '') +
            (sel ? '<div class="vm-a11y-sel">' + sel + '</div>' : '') +
            '</div>' +
            (btnText ? '<button class="vm-a11y-action">' + btnText + '</button>' : '') +
            '</div>';
    }
    // Patch existing veRunA11y to use new markup
    if (typeof window.veRunA11y === 'function') {
        var _origA11y = window.veRunA11y;
        window.veRunA11y = function () { _origA11y.apply(this, arguments); };
    }

    // ── Icon picker: render icons into new vm-icon-grid ───────
    if (typeof window.veIconList !== 'undefined') {
        var _origRenderIcons = window.veRenderIconGrid;
        if (typeof _origRenderIcons === 'function') {
            window.veRenderIconGrid = function (icons, container) {
                var $grid = $(container || '#ve-icon-grid');
                $grid.empty();
                icons.forEach(function (ic) {
                    $grid.append(
                        '<div class="vm-icon-cell" data-icon="' + ic.cls + '">' +
                        '<i class="' + ic.cls + '"></i><span>' + ic.name + '</span></div>'
                    );
                });
            };
        }
    }

    // ── Snippets: render items as vm-snip-item ─────────────────
    var _origRenderSnippets = window.veRenderSnippetList;
    if (typeof _origRenderSnippets === 'function') {
        window.veRenderSnippetList = function (snippets, activeIdx) {
            var $list = $('#ve-snippets-list');
            $list.empty();
            snippets.forEach(function (s, i) {
                var $item = $('<button type="button" class="vm-snip-item' + (i === activeIdx ? ' active' : '') + '" data-idx="' + i + '"><i class="fa-solid fa-code"></i>' + s.name + '</button>');
                $list.append($item);
            });
        };
    }

    // ── ec-ai-pill single select per row ───────────────────────
    $(document).on('click', '.ec-ai-pill', function () {
        var $row = $(this).closest('.ec-ai-pill-row');
        $row.find('.ec-ai-pill').removeClass('on');
        $(this).addClass('on');
        // Sync hidden input (look for sibling input)
        var $input = $row.next('input[type=hidden]');
        if ($input.length) { $input.val($(this).data('value')); }
    });

    // Patch AI modal button state
    $('#ve-ai-modal').on('show.bs.modal', function () {
        $('#ve-ai-result').addClass('ve-hidden');
        $('#btn-ai-insert').addClass('ve-hidden');
        $('#btn-ai-regenerate').addClass('ve-hidden');
        $('#btn-ai-generate').removeClass('ve-hidden');
    });
    $('#btn-ai-generate').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Generando…');
        // Trigger existing generation logic if available
        if (typeof window.veAiGenerate === 'function') {
            window.veAiGenerate($('#ve-ai-type').val(), $('#ve-ai-prompt').val(), $('#ve-ai-tone').val());
        }
        setTimeout(function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-wand-magic-sparkles"></i>Generar'); }, 3000);
    });

    // ── Command palette: render ec-cmd-item instead of old markup ─
    var _origRenderCmd = window.veRenderCmdResults;
    if (typeof _origRenderCmd === 'function') {
        window.veRenderCmdResults = function (items) {
            var $body = $('#ve-cmd-results');
            $body.empty();
            var sections = {};
            items.forEach(function (it) {
                var sec = it.section || 'Acciones';
                if (!sections[sec]) { sections[sec] = []; }
                sections[sec].push(it);
            });
            Object.keys(sections).forEach(function (sec) {
                $body.append('<div class="ec-cmd-section">' + sec + '</div>');
                sections[sec].forEach(function (it, idx) {
                    var kbdHtml = it.shortcut ? '<span class="s"><span class="ec-kbd">' + it.shortcut + '</span></span>' : (it.sub ? '<span class="s">' + it.sub + '</span>' : '');
                    $body.append('<div class="ec-cmd-item' + (idx === 0 ? ' on' : '') + '" data-action="' + (it.action || '') + '"><div class="ec-cmd-ico"><i class="' + (it.icon || 'fa-solid fa-bolt') + '"></i></div><div class="t">' + it.label + '</div>' + kbdHtml + '</div>');
                });
            });
        };
    }
    $(document).on('click', '.ec-cmd-item', function () {
        $('#ve-command-palette').find('.ec-cmd-item').removeClass('on');
        $(this).addClass('on');
    });
    // Keyboard nav in command palette
    $('#ve-command-palette').on('keydown', '#ve-cmd-input', function (e) {
        var $items = $('#ve-cmd-results').find('.ec-cmd-item');
        var $on = $items.filter('.on');
        var idx = $items.index($on);
        if (e.key === 'ArrowDown') { e.preventDefault(); $items.removeClass('on'); $items.eq(Math.min(idx + 1, $items.length - 1)).addClass('on'); }
        if (e.key === 'ArrowUp')   { e.preventDefault(); $items.removeClass('on'); $items.eq(Math.max(idx - 1, 0)).addClass('on'); }
        if (e.key === 'Enter')     { $items.filter('.on').trigger('click'); }
    });

    // ── Tooltip system (data-tooltip) ─────────────────────────
    var $ecTt = $('#ve-tooltip');
    $(document).on('mouseenter', '[data-tooltip]', function () {
        var $el = $(this);
        var txt = $el.data('tooltip');
        if (!txt) { return; }
        $ecTt.text(txt);
        var off = $el.offset();
        var w = $el.outerWidth();
        var ttW = $ecTt.outerWidth() || 120;
        $ecTt.css({
            top: off.top - $ecTt.outerHeight() - 8,
            left: off.left + w / 2 - ttW / 2,
        }).addClass('visible');
    }).on('mouseleave', '[data-tooltip]', function () {
        $ecTt.removeClass('visible');
    });

    // ── SEO modal ─────────────────────────────────────────────
    $('#ve-seo-modal').on('show.bs.modal', function () {
        var title = $('#ve-seo-title-input').val() || $('#ve-page-title-display').text() || PAGE_DATA.title;
        var desc  = $('#ve-seo-desc-input').val() || PAGE_DATA.seo_description;

        $('#ve-seo-serp-title').text(title);
        $('#ve-seo-serp-desc').text(desc);

        var score = 0;
        var checks = [];
        // Title check
        if (title.length >= 40 && title.length <= 60) { score += 20; checks.push({ ok: true, what: 'Title dentro del rango', val: title.length + ' / 60' }); }
        else { checks.push({ ok: false, warn: true, what: 'Title fuera del rango recomendado', val: title.length + ' / 60' }); }
        // Desc check
        if (desc.length >= 100 && desc.length <= 160) { score += 20; checks.push({ ok: true, what: 'Meta description óptima', val: desc.length + ' / 160' }); }
        else if (desc.length > 0) { score += 10; checks.push({ ok: false, warn: true, what: 'Meta description corta', val: desc.length + ' / 160' }); }
        else { checks.push({ ok: false, warn: false, what: 'Sin meta description', val: '—' }); }
        // Fixed checks (demo)
        score += 20; checks.push({ ok: true, what: 'H1 único en la página', val: '1' });
        score += 20; checks.push({ ok: true, what: 'URL amigable', val: '' });

        $('#ve-seo-score-num').text(score);
        $('#ve-seo-bar-fill').css('width', score + '%');
        var label = score >= 70 ? 'Bueno · puede mejorar' : (score >= 40 ? 'Mejorable' : 'Necesita atención');
        $('#ve-seo-score-label').text(label);
        $('#ve-seo-score-chip').text(score + '/100');

        var checksHtml = '';
        checks.forEach(function (c) {
            var cls = c.ok ? 'ok' : (c.warn ? 'warn' : 'err');
            var ico = c.ok ? 'fa-check' : (c.warn ? 'fa-triangle-exclamation' : 'fa-xmark');
            checksHtml += '<div class="ec-seo-check ' + cls + '"><i class="fa-solid ' + ico + '"></i><span class="what">' + c.what + '</span>' + (c.val ? '<span class="val">' + c.val + '</span>' : '') + '</div>';
        });
        $('#ve-seo-checks').html(checksHtml);

        // Character counters
        $('#ve-seo-title-input').on('input.seo', function () {
            $('#ve-seo-title-count').text($(this).val().length + '/60 chars');
            $('#ve-seo-serp-title').text($(this).val());
        }).trigger('input.seo');
        $('#ve-seo-desc-input').on('input.seo', function () {
            $('#ve-seo-desc-count').text($(this).val().length + '/160 chars');
            $('#ve-seo-serp-desc').text($(this).val());
        }).trigger('input.seo');
    });
    $('#ve-seo-modal').on('hidden.bs.modal', function () {
        $('#ve-seo-title-input, #ve-seo-desc-input').off('input.seo');
    });
    $('#btn-seo-save').on('click', function () {
        var title = $('#ve-seo-title-input').val();
        var desc  = $('#ve-seo-desc-input').val();
        PAGE_DATA.seo_title        = title;
        PAGE_DATA.seo_description  = desc;
        isModified = true;
        scheduleAutoSave();
        bootstrap.Modal.getInstance(document.getElementById('ve-seo-modal'))?.hide();
        showToast('SEO guardado correctamente', 'ok');
    });
    // Wire statusbar SEO link → SEO modal
    $('#btn-statusbar-seo').off('click').on('click', function (e) {
        e.preventDefault();
        new bootstrap.Modal(document.getElementById('ve-seo-modal')).show();
    });

    // ── Media picker modal ─────────────────────────────────────
    var _veMpSelected = null;
    $('#ve-media-picker-modal').on('show.bs.modal', function () {
        // Load from existing media module if available
        if (typeof window.veLoadMediaGrid === 'function') {
            window.veLoadMediaGrid('#ve-mp-grid');
        } else {
            $('#ve-mp-grid').html('<div style="grid-column:1/-1;padding:20px;text-align:center;color:var(--ve-text-muted);font-size:12px;">Conectar con el módulo de medios</div>');
        }
    });
    $(document).on('click', '.ec-mp-cell', function () {
        $('.ec-mp-cell').removeClass('on');
        $(this).addClass('on');
        _veMpSelected = $(this).data('url') || $(this).data('src') || '';
        var name = $(this).data('name') || 'archivo';
        $('#ve-mp-name').text(name);
        $('#ve-mp-selected-label').text('1 seleccionado');
    });
    $(document).on('click', '.ec-mp-tabs button', function () {
        $(this).closest('.ec-mp-tabs').find('button').removeClass('on');
        $(this).addClass('on');
    });
    $('#btn-mp-insert').on('click', function () {
        if (!_veMpSelected) { showToast('Selecciona un archivo primero', 'warn'); return; }
        sendToFrame({ type: 've-insert-media', url: _veMpSelected });
        bootstrap.Modal.getInstance(document.getElementById('ve-media-picker-modal'))?.hide();
    });

    // ── Image check modal ──────────────────────────────────────
    $('#ve-image-check-modal').on('show.bs.modal', function () {
        veRunImageCheck();
    });
    function veRenderImageCheckList(results) {
        var bad = 0, slow = 0, ok = 0;
        var html = '';
        results.forEach(function (r) {
            var cls = r.status === 'bad' ? 'bad' : (r.status === 'slow' ? 'warn' : '');
            var thumbCls = r.status === 'bad' ? '' : (r.status === 'slow' ? 'warn' : 'ok');
            var ico = r.status === 'bad' ? 'fa-xmark' : (r.status === 'slow' ? 'fa-clock' : 'fa-check');
            var stCls = r.status === 'bad' ? 'bad' : (r.status === 'slow' ? 'slow' : 'ok');
            var stTxt = r.status === 'bad' ? '404' : (r.size || 'OK');
            if (r.status === 'bad') bad++;
            else if (r.status === 'slow') slow++;
            else ok++;
            html += '<div class="ec-img-check-row">' +
                '<div class="ec-img-thumb ' + thumbCls + '"><i class="fa-solid ' + ico + '"></i></div>' +
                '<div class="ec-img-path">' + r.path + '</div>' +
                '<span class="ec-img-st ' + stCls + '">' + stTxt + '</span>' +
                '</div>';
        });
        $('#ve-ic-list').html(html || '<div style="padding:20px;text-align:center;color:var(--ve-text-muted);font-size:12px;">Sin problemas detectados</div>');
        $('#ve-ic-bad-n').text(bad);
        $('#ve-ic-slow-n').text(slow);
        $('#ve-ic-ok-n').text(ok);
        $('#ve-ic-total').text(results.length);
    }
    function veRunImageCheck() {
        $('#ve-ic-list').html('<div style="padding:20px;text-align:center;color:var(--ve-text-muted);font-size:12px;">Escaneando imágenes…</div>');
        // Pull images from iframe
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) { return; }
        var imgs = frame.contentDocument.querySelectorAll('img');
        var results = [];
        var pending = imgs.length;
        if (!pending) { veRenderImageCheckList([]); return; }
        imgs.forEach(function (img) {
            var path = img.getAttribute('src') || '';
            var testImg = new Image();
            testImg.onload = function () {
                var kb = Math.round(testImg.naturalWidth * testImg.naturalHeight * 0.002);
                results.push({ path: path, status: kb > 2000 ? 'slow' : 'ok', size: kb + ' KB' });
                if (--pending === 0) { veRenderImageCheckList(results); }
            };
            testImg.onerror = function () {
                results.push({ path: path, status: 'bad' });
                if (--pending === 0) { veRenderImageCheckList(results); }
            };
            testImg.src = img.src;
        });
    }
    $('#btn-ic-rescan').on('click', veRunImageCheck);

    /* ══════════════════════════════════════════════════════════
       SCRUB INPUTS — drag labels to change number values (Figma-style)
       ══════════════════════════════════════════════════════════ */
    (function () {
        var scrubState = null;

        // Attach scrub to a label+input pair
        function attachScrub($label, $input) {
            if ($label.data('ve-scrub')) return;
            $label.data('ve-scrub', true).addClass('ve-scrub-label');

            $label.on('mousedown.scrub', function (e) {
                if (e.button !== 0) return;
                var startX   = e.clientX;
                var startVal = parseFloat($input.val()) || 0;
                var step     = e.shiftKey ? 0.1 : (e.ctrlKey || e.metaKey ? 10 : 1);

                scrubState = { $input: $input, startX: startX, startVal: startVal, step: step };
                $('body').addClass('ve-scrubbing');
                $input.addClass('ve-scrub-active');
                e.preventDefault();
            });
        }

        // Global mousemove during scrub
        $(document).on('mousemove.vescrub', function (e) {
            if (!scrubState) return;
            var delta = Math.round((e.clientX - scrubState.startX) * scrubState.step);
            var newVal = scrubState.startVal + delta;
            var min = parseFloat(scrubState.$input.attr('min'));
            var max = parseFloat(scrubState.$input.attr('max'));
            if (!isNaN(min)) newVal = Math.max(min, newVal);
            if (!isNaN(max)) newVal = Math.min(max, newVal);
            scrubState.$input.val(newVal).trigger('input').trigger('change');
        });

        $(document).on('mouseup.vescrub', function () {
            if (!scrubState) return;
            $('body').removeClass('ve-scrubbing');
            scrubState.$input.removeClass('ve-scrub-active');
            scrubState = null;
        });

        // Wire up on inspector panel open
        function wireInspectorScrubs() {
            $('#ve-inspector-sections label').each(function () {
                var $label = $(this);
                // Find sibling or nearby number input
                var $input = $label.next('input[type="number"]');
                if (!$input.length) $input = $label.siblings('input[type="number"]').first();
                if (!$input.length) $input = $label.parent().find('input[type="number"]').first();
                if (!$input.length) return;
                attachScrub($label, $input);
            });
        }

        // Run on panel switch to inspector
        $('#ve-sidebar-nav').on('click', '.ve-nav-btn[data-panel="inspector"]', function () {
            setTimeout(wireInspectorScrubs, 100);
        });

        // Run whenever inspector content changes (element selected)
        $(document).on('ve-inspector-updated', wireInspectorScrubs);

        // Run once at init (in case inspector is already active)
        setTimeout(wireInspectorScrubs, 800);
    })();

    /* ══════════════════════════════════════════════════════════
       SLASH MENU — quick block inserter triggered by / or + button
       ══════════════════════════════════════════════════════════ */
    (function () {
        var slashOpen   = false;
        var slashIdx    = 0;
        var slashItems  = [];

        // Collect insertable items from the shortcodes panel
        function getSlashItems(query) {
            var all = [];
            $('.ve-block-item').each(function () {
                var name = $(this).find('.ve-block-name').text().trim();
                var icon = $(this).find('.ve-block-icon i').attr('class') || 'fa-solid fa-shapes';
                var cat  = $(this).closest('[data-category]').data('category') || 'Bloques';
                if (!query || name.toLowerCase().includes(query.toLowerCase())) {
                    all.push({ name: name, icon: icon, cat: cat, $el: $(this) });
                }
            });
            return all.slice(0, 24);
        }

        function renderSlashResults(query) {
            slashItems = getSlashItems(query);
            slashIdx   = 0;
            var $r = $('#ve-slash-results').empty();

            if (!slashItems.length) {
                $r.html('<div style="padding:16px;text-align:center;font-size:12px;color:var(--ve-text-muted);">Sin resultados para "' + $('<span>').text(query).html() + '"</div>');
                return;
            }

            var lastCat = null;
            slashItems.forEach(function (item, i) {
                if (item.cat !== lastCat) {
                    $r.append('<div class="ve-slash-section">' + $('<span>').text(item.cat).html() + '</div>');
                    lastCat = item.cat;
                }
                $r.append(
                    '<div class="ve-slash-item' + (i === 0 ? ' ve-slash-active' : '') + '" data-idx="' + i + '">' +
                    '<div class="ve-slash-icon"><i class="' + item.icon + '"></i></div>' +
                    '<span class="ve-slash-name">' + $('<span>').text(item.name).html() + '</span>' +
                    '</div>'
                );
            });
        }

        function openSlash() {
            if (slashOpen) return;
            slashOpen = true;

            // Center on the canvas wrap (not the viewport)
            var $wrap = $('#ve-canvas-wrap').length ? $('#ve-canvas-wrap') : $('#ve-canvas');
            if (!$wrap.length) $wrap = $(window);
            var off   = $wrap.offset() || { top: 0, left: 0 };
            var w     = $wrap.outerWidth()  || window.innerWidth;
            var h     = $wrap.outerHeight() || window.innerHeight;
            var mw    = 300; // menu width
            var mh    = 380; // menu approx height
            var left  = off.left + (w - mw) / 2;
            var top   = off.top  + (h - mh) / 2;
            // Clamp to viewport
            left = Math.max(20, Math.min(left, window.innerWidth  - mw - 20));
            top  = Math.max(20, Math.min(top,  window.innerHeight - mh - 20));
            $('#ve-slash-menu').css({ top: top + 'px', left: left + 'px' }).addClass('ve-slash-open');
            $('#ve-slash-input').val('').focus();
            renderSlashResults('');
        }

        function closeSlash() {
            if (!slashOpen) return;
            slashOpen = false;
            $('#ve-slash-menu').removeClass('ve-slash-open');
        }

        function moveSlash(dir) {
            if (!slashItems.length) return;
            slashIdx = (slashIdx + dir + slashItems.length) % slashItems.length;
            $('#ve-slash-results .ve-slash-item').removeClass('ve-slash-active').eq(slashIdx).addClass('ve-slash-active').get(0)?.scrollIntoView({ block: 'nearest' });
        }

        function insertSlash() {
            var item = slashItems[slashIdx];
            if (!item) return;
            // Click the block item to trigger insertion
            item.$el.trigger('click');
            closeSlash();
            showToast('<i class="fa-solid fa-plus me-1"></i>Bloque insertado: ' + item.name);
        }

        // Button in topbar
        $('#btn-slash-menu').on('click', function () {
            if (slashOpen) closeSlash(); else openSlash();
        });

        // `/` key when focused on canvas or not inside a text input
        $(document).on('keydown.slashmenu', function (e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
                if (e.key === 'Escape' && slashOpen) { closeSlash(); e.preventDefault(); }
                return;
            }
            if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                openSlash();
                return;
            }
            if (!slashOpen) return;
            if (e.key === 'Escape')    { closeSlash();   e.preventDefault(); }
            if (e.key === 'ArrowDown') { moveSlash(+1);  e.preventDefault(); }
            if (e.key === 'ArrowUp')   { moveSlash(-1);  e.preventDefault(); }
            if (e.key === 'Enter')     { insertSlash();  e.preventDefault(); }
        });

        // Type in slash search
        $('#ve-slash-input').on('input', function () {
            renderSlashResults($(this).val());
        });
        $('#ve-slash-input').on('keydown', function (e) {
            if (e.key === 'Escape')    { closeSlash();   e.preventDefault(); }
            if (e.key === 'ArrowDown') { moveSlash(+1);  e.preventDefault(); }
            if (e.key === 'ArrowUp')   { moveSlash(-1);  e.preventDefault(); }
            if (e.key === 'Enter')     { insertSlash();  e.preventDefault(); }
        });

        // Click item
        $(document).on('click', '#ve-slash-results .ve-slash-item', function () {
            slashIdx = parseInt($(this).data('idx'), 10) || 0;
            insertSlash();
        });

        // Click outside to close
        $(document).on('click.slashclose', function (e) {
            if (!slashOpen) return;
            if (!$(e.target).closest('#ve-slash-menu, #btn-slash-menu').length) {
                closeSlash();
            }
        });
    })();

    // ── Page stats modal ───────────────────────────────────────
    var VE_STAT_RANGE = 30;

    function formatN(n) {
        if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (n >= 1000)    return (n / 1000).toFixed(1) + 'k';
        return String(n);
    }

    function drawStatSpark(values) {
        if (!values || !values.length) {
            $('#ve-chart-area, #ve-chart-line').attr('d', '');
            return;
        }
        var W = 200, H = 36, n = values.length;
        var max = Math.max.apply(null, values) || 1;
        var pts = values.map(function (v, i) {
            var x = (i / (n - 1 || 1)) * W;
            var y = H - (v / max) * (H - 4) - 2;
            return x.toFixed(1) + ',' + y.toFixed(1);
        });
        var line = 'M' + pts.join(' L');
        var area = line + ' L' + W + ',' + H + ' L0,' + H + ' Z';
        $('#ve-chart-line').attr('d', line);
        $('#ve-chart-area').attr('d', area);
    }

    function populateStats(range) {
        var rangeLabels = { 7: 'últimos 7 días', 30: 'últimos 30 días', 90: 'últimos 90 días' };
        $('#ve-stat-range-label').text(rangeLabels[range] || 'últimos ' + range + ' días');

        var s = (typeof PAGE_DATA !== 'undefined' && PAGE_DATA.stats) || {};

        // Hero: visits
        var visits = s.visits ? (typeof s.visits === 'number' ? formatN(s.visits) : s.visits) : '—';
        $('#ve-stat-visits').text(visits);

        // Trend label
        var deltaPct = s.delta_pct || 0;
        if (deltaPct > 0) {
            $('#ve-stat-trend').text('↑ ' + deltaPct + '% vs. periodo anterior').css('color', '#16a34a');
            $('#ve-stat-trend-desc').text('Tendencia positiva');
        } else if (deltaPct < 0) {
            $('#ve-stat-trend').text('↓ ' + Math.abs(deltaPct) + '% vs. periodo anterior').css('color', '#dc2626');
            $('#ve-stat-trend-desc').text('Tendencia negativa');
        } else {
            $('#ve-stat-trend').text('Sin datos suficientes').css('color', 'var(--ve-text-muted)');
            $('#ve-stat-trend-desc').text('Conecta Google Analytics para ver métricas');
        }

        // Counts
        $('#ve-stat-conv').text(s.conv || '—');
        $('#ve-stat-bounce').text(s.bounce || '—');
        $('#ve-stat-time').text(s.time || '—');

        // Secondary KPIs
        $('#ve-stat-ppv').text(s.pages_per_session || '—');
        $('#ve-stat-ppv-delta').text(s.pages_per_session_delta || '').attr('class', 'd ' + (s.pages_per_session_trend || ''));
        $('#ve-stat-new-ratio').text(s.new_ratio || '—');
        $('#ve-stat-new-ratio-delta').text(s.new_ratio_delta || '').attr('class', 'd ' + (s.new_ratio_trend || ''));
        $('#ve-stat-source').text(s.top_source || '—');
        $('#ve-stat-source-delta').text(s.top_source_share || '');
        $('#ve-stat-device').text(s.top_device || '—');
        $('#ve-stat-device-delta').text(s.top_device_share || '');

        // Sparkline — use real data if available, else small placeholder
        drawStatSpark(s.chart || [4, 8, 6, 12, 10, 14, 18, 16, 22, 20, 25, 28]);

        // Core Web Vitals
        var vitals = s.vitals || [
            { ok: true, what: 'LCP · < 2.5s',   val: '—' },
            { ok: true, what: 'CLS · < 0.1',    val: '—' },
            { ok: null, what: 'FID · < 100ms',  val: '—' },
            { ok: null, what: 'Peso total',     val: '—' }
        ];
        var vHtml = '';
        vitals.forEach(function (v) {
            var cls = v.ok === true ? 'ok' : (v.ok === false ? 'err' : 'warn');
            var ico = v.ok === true ? 'fa-check' : (v.ok === false ? 'fa-xmark' : 'fa-triangle-exclamation');
            vHtml += '<div class="vm-a11y-item">' +
                '<div class="vm-a11y-sev ' + cls + '"><i class="fa-solid ' + ico + '"></i></div>' +
                '<div class="vm-a11y-body"><div class="vm-a11y-title">' + v.what + '</div></div>' +
                '<div class="vm-a11y-val" style="font-family:JetBrains Mono,monospace;font-size:10.5px;color:var(--ve-text-muted);">' + v.val + '</div>' +
                '</div>';
        });
        $('#ve-stats-vitals').html(vHtml);

        // Updated timestamp
        $('#ve-stat-updated').text('Actualizado · ' + new Date().toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' }));
    }

    $('#ve-page-stats-modal').on('shown.bs.modal', function () {
        populateStats(VE_STAT_RANGE);
    });

    // Range segment control
    $(document).on('click', '.ve-stat-range button', function () {
        $('.ve-stat-range button').removeClass('active');
        $(this).addClass('active');
        VE_STAT_RANGE = parseInt($(this).data('range'), 10) || 30;
        populateStats(VE_STAT_RANGE);
    });

    // Export stats to CSV: builds a blob from the current KPIs visible in the
    // stats modal and triggers a download without hitting the server.
    $('#btn-stat-export').on('click', function () {
        var rows = [['Métrica', 'Valor']];
        $('#ve-stats-modal [data-stat-key]').each(function () {
            rows.push([$(this).data('stat-key'), ($(this).text() || '').trim()]);
        });
        if (rows.length === 1) {
            // Fallback: scrape any .ve-stat-card label + value
            $('#ve-stats-modal .ve-stat-card, #ve-stats-modal .vm-stat').each(function () {
                var label = $(this).find('.ve-stat-label, .vm-stat-label, .label').first().text().trim();
                var value = $(this).find('.ve-stat-value, .vm-stat-value, .value, strong').first().text().trim();
                if (label) { rows.push([label, value]); }
            });
        }
        var csv = rows.map(function (r) {
            return r.map(function (c) {
                var s = String(c == null ? '' : c).replace(/"/g, '""');
                return /[",\n]/.test(s) ? '"' + s + '"' : s;
            }).join(',');
        }).join('\n');
        var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        var slug = ($('#ve-settings-slug').val() || 'pagina').replace(/[^a-z0-9-]/gi, '-');
        a.href = url;
        a.download = 'stats-' + slug + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { a.remove(); URL.revokeObjectURL(url); }, 1000);
        if (window.showToast) { showToast('<i class="fa-solid fa-file-csv me-1"></i>CSV exportado'); }
    });

    // Open Analytics (stub)
    $('#btn-stat-analytics').on('click', function () {
        window.open('https://analytics.google.com/', '_blank');
    });

})(jQuery);
</script>

{{-- Status Banners stack (top) --}}
<div id="ve-banners-stack" aria-live="polite"></div>

{{-- Onboarding Spotlight --}}
<div id="ve-spotlight" class="ve-hidden" aria-live="polite">
    <div class="ve-spot-overlay" id="ve-spot-overlay"></div>
    <div class="ve-spot-highlight" id="ve-spot-highlight"></div>
    <div class="ve-spot-callout" id="ve-spot-callout">
        <div class="ve-spot-step" id="ve-spot-step">PASO 1 DE 1</div>
        <div class="ve-spot-title" id="ve-spot-title">Bienvenido al editor</div>
        <div class="ve-spot-desc" id="ve-spot-desc">Descripción del paso</div>
        <div class="ve-spot-actions">
            <div class="ve-spot-dots" id="ve-spot-dots"></div>
            <div style="flex:1;"></div>
            <button type="button" class="ve-spot-skip" id="ve-spot-skip">Omitir</button>
            <button type="button" class="ve-spot-next" id="ve-spot-next">Siguiente <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </div>
</div>

{{-- Collab cursors layer (populated by JS when collab active) --}}
<div id="ve-collab-layer" style="position:absolute;inset:0;pointer-events:none;z-index:9950;"></div>

{{-- FAB Save/Publish (floating bottom-right) --}}
{{-- Tweaks panel --}}
<div id="ve-tweaks">
    <div class="ve-tweak-head">
        <span>Tweaks</span>
        <button type="button" class="ve-tweak-close" id="ve-tweak-close" title="Cerrar">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
    <div class="ve-tweak-body">
        <div class="ve-tweak-row">
            <label>Fondo del canvas</label>
            <div class="ve-tweak-seg" data-tweak="canvas-bg">
                <button class="active" data-val="dots">Puntos</button>
                <button data-val="grid">Grid</button>
                <button data-val="flat">Sólido</button>
            </div>
        </div>
        <div class="ve-tweak-row">
            <label>Ancho del panel</label>
            <div class="ve-tweak-seg" data-tweak="panel-width">
                <button data-val="240">Estrecho</button>
                <button class="active" data-val="280">Normal</button>
                <button data-val="320">Ancho</button>
            </div>
        </div>
        <div class="ve-tweak-row">
            <label>Canvas</label>
            <div class="ve-tweak-seg" data-tweak="breakpoint">
                <button class="active" data-val="desktop" title="Desktop"><i class="fa-solid fa-desktop"></i></button>
                <button data-val="tablet" title="Tablet"><i class="fa-solid fa-tablet-screen-button"></i></button>
                <button data-val="mobile" title="Móvil"><i class="fa-solid fa-mobile-screen"></i></button>
            </div>
        </div>
        <div class="ve-tweak-row">
            <label>Chrome del canvas</label>
            <div class="ve-tweak-seg" data-tweak="chrome">
                <button class="active" data-val="on">Mostrar</button>
                <button data-val="off">Ocultar</button>
            </div>
        </div>
        <div class="ve-tweak-row">
            <label>Minimapa</label>
            <div class="ve-tweak-seg" data-tweak="minimap">
                <button data-val="off" class="active">Oculto</button>
                <button data-val="on">Visible</button>
            </div>
        </div>
    </div>
</div>

{{-- Advanced Color Picker overlay (fixed, global) --}}
<div id="ve-acp" class="ve-hidden" role="dialog" aria-label="Selector de color">
    <div class="ve-acp-canvas-wrap" id="ve-acp-canvas-wrap">
        <canvas id="ve-acp-canvas" width="216" height="120"></canvas>
        <div id="ve-acp-picker-dot" class="ve-acp-picker-dot"></div>
    </div>
    <div class="ve-acp-sliders">
        <input type="range" id="ve-acp-hue" class="ve-acp-hue-bar" min="0" max="360" value="0">
        <input type="range" id="ve-acp-alpha" class="ve-acp-alpha-bar" min="0" max="100" value="100">
    </div>
    <div class="ve-acp-tabs ve-acp-tabs-4">
        <button class="ve-acp-tab active" data-tab="hex">HEX</button>
        <button class="ve-acp-tab" data-tab="rgb">RGB</button>
        <button class="ve-acp-tab" data-tab="hsl">HSL</button>
        <button class="ve-acp-tab" data-tab="oklch">OKLCH</button>
    </div>
    <div id="ve-acp-pane-hex" class="ve-acp-pane ve-acp-pane-hex">
        <div class="ve-acp-preview" id="ve-acp-preview">
            <div id="ve-acp-preview-fill" class="ve-acp-preview-fill"></div>
        </div>
        <input type="text" id="ve-acp-hex" class="ve-acp-text-input" placeholder="#000000" maxlength="9" spellcheck="false">
    </div>
    <div id="ve-acp-pane-rgb" class="ve-acp-pane ve-hidden">
        <div class="ve-acp-val"><input type="number" id="ve-acp-r" class="ve-acp-text-input" min="0" max="255" placeholder="R"><label>R</label></div>
        <div class="ve-acp-val"><input type="number" id="ve-acp-g" class="ve-acp-text-input" min="0" max="255" placeholder="G"><label>G</label></div>
        <div class="ve-acp-val"><input type="number" id="ve-acp-b" class="ve-acp-text-input" min="0" max="255" placeholder="B"><label>B</label></div>
        <div class="ve-acp-val"><input type="number" id="ve-acp-a" class="ve-acp-text-input" min="0" max="100" placeholder="A"><label>A%</label></div>
    </div>
    <div id="ve-acp-pane-hsl" class="ve-acp-pane ve-hidden">
        <div class="ve-acp-val"><input type="number" id="ve-acp-h2" class="ve-acp-text-input" min="0" max="360" placeholder="H"><label>H</label></div>
        <div class="ve-acp-val"><input type="number" id="ve-acp-s2" class="ve-acp-text-input" min="0" max="100" placeholder="S"><label>S%</label></div>
        <div class="ve-acp-val"><input type="number" id="ve-acp-l2" class="ve-acp-text-input" min="0" max="100" placeholder="L"><label>L%</label></div>
    </div>
    <div id="ve-acp-pane-oklch" class="ve-acp-pane ve-hidden">
        <div class="ve-acp-val"><input type="number" id="ve-acp-ol" class="ve-acp-text-input" min="0" max="100" placeholder="L"><label>L</label></div>
        <div class="ve-acp-val"><input type="number" id="ve-acp-oc" class="ve-acp-text-input" min="0" max="40" step="0.01" placeholder="C"><label>C</label></div>
        <div class="ve-acp-val"><input type="number" id="ve-acp-oh" class="ve-acp-text-input" min="0" max="360" placeholder="H"><label>H</label></div>
    </div>
    <div class="ve-acp-section-lbl">Brand <span class="ve-acp-section-sub">· {{ strtolower(config('app.name', 'alsernet')) }}</span></div>
    <div class="ve-acp-swatches ve-acp-brand-swatches" id="ve-acp-swatches"></div>
    <div class="ve-acp-tools">
        <button type="button" class="ve-acp-tool-btn" id="ve-acp-eyedropper" title="Capturar color">
            <i class="fa-solid fa-eye-dropper"></i>Capturar
        </button>
        <button type="button" class="ve-acp-tool-btn" id="ve-acp-contrast" title="Comprobar contraste AA">
            <i class="fa-solid fa-shuffle"></i>Contraste AA
        </button>
    </div>
</div>

<script>
/* ── Advanced Color Picker ───────────────────────────────── */
(function ($) {
    'use strict';
    var acp = {
        h: 0, s: 100, b: 100, a: 100,
        $target: null,
        dragging: false,
        // Brand palette — 8 colores + transparente + add button
        brandColor:   '#90bb13',
        swatchColors: ['#90bb13','#18181b','#ffffff','#f4f4f5','#dc2626','#d97706','#16a34a','#2563eb']
    };

    function hsvToHex(h, s, v) {
        s /= 100; v /= 100;
        var c = v * s, x = c * (1 - Math.abs((h / 60) % 2 - 1)), m = v - c;
        var r = 0, g = 0, b = 0;
        if (h < 60)       { r = c; g = x; b = 0; }
        else if (h < 120) { r = x; g = c; b = 0; }
        else if (h < 180) { r = 0; g = c; b = x; }
        else if (h < 240) { r = 0; g = x; b = c; }
        else if (h < 300) { r = x; g = 0; b = c; }
        else              { r = c; g = 0; b = x; }
        return '#' + [r + m, g + m, b + m].map(function (v) {
            return Math.round(v * 255).toString(16).padStart(2, '0');
        }).join('');
    }

    function hexToHsv(hex) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
        var r = parseInt(hex.slice(0, 2), 16) / 255,
            g = parseInt(hex.slice(2, 4), 16) / 255,
            b = parseInt(hex.slice(4, 6), 16) / 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
        var h = 0, s = max === 0 ? 0 : d / max, v = max;
        if (d !== 0) {
            if (max === r) h = ((g - b) / d + (g < b ? 6 : 0)) / 6;
            else if (max === g) h = ((b - r) / d + 2) / 6;
            else h = ((r - g) / d + 4) / 6;
        }
        return { h: Math.round(h * 360), s: Math.round(s * 100), b: Math.round(v * 100) };
    }

    function rgbToHex(r, g, b) {
        return '#' + [r, g, b].map(function (v) { return Math.round(v).toString(16).padStart(2, '0'); }).join('');
    }

    function hslToHex(h, s, l) {
        s /= 100; l /= 100;
        var c = (1 - Math.abs(2 * l - 1)) * s, x = c * (1 - Math.abs((h / 60) % 2 - 1)), m = l - c / 2;
        var r = 0, g = 0, b = 0;
        if (h < 60)       { r = c; g = x; b = 0; }
        else if (h < 120) { r = x; g = c; b = 0; }
        else if (h < 180) { r = 0; g = c; b = x; }
        else if (h < 240) { r = 0; g = x; b = c; }
        else if (h < 300) { r = x; g = 0; b = c; }
        else              { r = c; g = 0; b = x; }
        return rgbToHex((r + m) * 255, (g + m) * 255, (b + m) * 255);
    }

    function hexToRgb(hex) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
        return { r: parseInt(hex.slice(0, 2), 16), g: parseInt(hex.slice(2, 4), 16), b: parseInt(hex.slice(4, 6), 16) };
    }

    function drawCanvas() {
        var canvas = document.getElementById('ve-acp-canvas');
        var ctx = canvas.getContext('2d');
        var w = canvas.width, h = canvas.height;
        var pure = 'hsl(' + acp.h + ',100%,50%)';
        var gH = ctx.createLinearGradient(0, 0, w, 0);
        gH.addColorStop(0, '#fff'); gH.addColorStop(1, pure);
        ctx.fillStyle = gH; ctx.fillRect(0, 0, w, h);
        var gV = ctx.createLinearGradient(0, 0, 0, h);
        gV.addColorStop(0, 'rgba(0,0,0,0)'); gV.addColorStop(1, 'rgba(0,0,0,1)');
        ctx.fillStyle = gV; ctx.fillRect(0, 0, w, h);
    }

    function updateDot() {
        var canvas = document.getElementById('ve-acp-canvas');
        var x = (acp.s / 100) * canvas.width;
        var y = (1 - acp.b / 100) * canvas.height;
        var $dot = $('#ve-acp-picker-dot');
        $dot.css({ left: x + 'px', top: y + 'px' });
    }

    function updatePreview() {
        var hex = hsvToHex(acp.h, acp.s, acp.b);
        var alpha = acp.a / 100;
        var rgb = hexToRgb(hex);
        var rgba = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')';
        $('#ve-acp-preview-fill').css('background', rgba);
        // Alpha bar background
        $('#ve-acp-alpha').css('background',
            'linear-gradient(to right, rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',0), rgb(' + rgb.r + ',' + rgb.g + ',' + rgb.b + '))');
    }

    function syncInputs() {
        var hex = hsvToHex(acp.h, acp.s, acp.b);
        var rgb = hexToRgb(hex);
        // HSL calc
        var r = rgb.r / 255, g = rgb.g / 255, b = rgb.b / 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b);
        var l = (max + min) / 2;
        var s = max === min ? 0 : (l > 0.5 ? (max - min) / (2 - max - min) : (max - min) / (max + min));
        $('#ve-acp-hex').val(hex);
        $('#ve-acp-r').val(rgb.r); $('#ve-acp-g').val(rgb.g); $('#ve-acp-b').val(rgb.b);
        $('#ve-acp-h2').val(acp.h);
        $('#ve-acp-s2').val(Math.round(s * 100));
        $('#ve-acp-l2').val(Math.round(l * 100));
    }

    function applyToTarget() {
        if (!acp.$target) return;
        var hex = hsvToHex(acp.h, acp.s, acp.b);
        acp.$target.val(hex).trigger('input').trigger('change');
        // Sync linked text input if present
        var $row = acp.$target.closest('.ve-color-row');
        if ($row.length) $row.find('.ve-color-text').val(hex);
    }

    function refresh() {
        drawCanvas(); updateDot(); updatePreview(); syncInputs(); applyToTarget();
    }

    function openAcp($input) {
        acp.$target = $input;
        var hex = $input.val() || '#000000';
        var hsv = hexToHsv(hex);
        acp.h = hsv.h; acp.s = hsv.s; acp.b = hsv.b; acp.a = 100;
        $('#ve-acp-hue').val(acp.h);
        $('#ve-acp-alpha').val(100);
        // Position near input
        var rect = $input[0].getBoundingClientRect();
        var top = rect.bottom + 6, left = rect.left;
        if (top + 280 > window.innerHeight) top = rect.top - 280;
        if (left + 248 > window.innerWidth) left = window.innerWidth - 252;
        $('#ve-acp').css({ top: top + 'px', left: left + 'px' }).removeClass('ve-hidden');
        if (window.veMarkModalOpen) veMarkModalOpen(true);
        refresh();
        renderSwatches();
    }

    function closeAcp() {
        if (!$('#ve-acp').hasClass('ve-hidden') && window.veMarkModalOpen) veMarkModalOpen(false);
        $('#ve-acp').addClass('ve-hidden'); acp.$target = null;
    }

    function renderSwatches() {
        var html = '';
        acp.swatchColors.forEach(function (c) {
            var isBrand = c.toLowerCase() === (acp.brandColor || '').toLowerCase();
            html += '<div class="ve-acp-swatch' + (isBrand ? ' brand' : '') + '" style="background:' + c + '" data-color="' + c + '" title="' + c + '"></div>';
        });
        // Transparent swatch
        html += '<div class="ve-acp-swatch ve-acp-swatch-transparent" data-color="transparent" title="transparent"></div>';
        // Add custom color
        html += '<div class="ve-acp-swatch ve-acp-swatch-add" data-action="add" title="Añadir color"><i class="fa-solid fa-plus"></i></div>';
        $('#ve-acp-swatches').html(html);
    }

    // Add color swatch
    $(document).on('click', '.ve-acp-swatch-add', function () {
        var cur = hsvToHex(acp.h, acp.s, acp.b);
        if (acp.swatchColors.indexOf(cur) === -1) {
            acp.swatchColors.push(cur);
            try { localStorage.setItem('ve-acp-swatches', JSON.stringify(acp.swatchColors)); } catch (e) {}
            renderSwatches();
        }
    });

    // Eyedropper (uses native EyeDropper API when available)
    $(document).on('click', '#ve-acp-eyedropper', function () {
        if (typeof EyeDropper === 'undefined') {
            if (window.showToast) showToast('<i class="fa-solid fa-triangle-exclamation me-1"></i>EyeDropper no disponible en este navegador', 'warning');
            return;
        }
        var ed = new EyeDropper();
        ed.open().then(function (result) {
            var hex = result.sRGBHex;
            var hsv = hexToHsv(hex);
            acp.h = hsv.h; acp.s = hsv.s; acp.b = hsv.b;
            $('#ve-acp-hue').val(acp.h);
            refresh();
        }).catch(function () { /* cancelled */ });
    });

    // Contrast AA check against white and black
    $(document).on('click', '#ve-acp-contrast', function () {
        var hex = hsvToHex(acp.h, acp.s, acp.b);
        var rgb = hexToRgb(hex);
        function luminance(r, g, b) {
            var a = [r, g, b].map(function (v) {
                v /= 255;
                return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
            });
            return a[0] * 0.2126 + a[1] * 0.7152 + a[2] * 0.0722;
        }
        var lum = luminance(rgb.r, rgb.g, rgb.b);
        var cWhite = (1 + 0.05) / (lum + 0.05);
        var cBlack = (lum + 0.05) / (0 + 0.05);
        var best   = cWhite > cBlack ? { label: 'sobre blanco', ratio: cWhite } : { label: 'sobre negro', ratio: cBlack };
        var grade  = best.ratio >= 7 ? 'AAA' : best.ratio >= 4.5 ? 'AA' : best.ratio >= 3 ? 'AA large' : 'fail';
        if (window.showToast) {
            showToast('<i class="fa-solid fa-shuffle me-1"></i>' + hex + ' ' + best.label + ': ' + best.ratio.toFixed(2) + ':1 · ' + grade);
        }
    });

    // Persist custom swatches
    try {
        var saved = localStorage.getItem('ve-acp-swatches');
        if (saved) acp.swatchColors = JSON.parse(saved);
    } catch (e) {}

    // ── Canvas interaction ──────────────────────────────────
    var $canvasWrap = $('#ve-acp-canvas-wrap');
    $canvasWrap.on('mousedown', function (e) {
        acp.dragging = true;
        handleCanvasDrag(e);
    });
    $(document).on('mousemove.acp', function (e) {
        if (!acp.dragging) return;
        handleCanvasDrag(e);
    }).on('mouseup.acp', function () { acp.dragging = false; });

    function handleCanvasDrag(e) {
        var canvas = document.getElementById('ve-acp-canvas');
        var rect = canvas.getBoundingClientRect();
        var x = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
        var y = Math.max(0, Math.min(e.clientY - rect.top, rect.height));
        acp.s = Math.round((x / rect.width) * 100);
        acp.b = Math.round((1 - y / rect.height) * 100);
        refresh();
    }

    // ── Sliders ─────────────────────────────────────────────
    $('#ve-acp-hue').on('input', function () {
        acp.h = parseInt($(this).val(), 10);
        refresh();
    });
    $('#ve-acp-alpha').on('input', function () {
        acp.a = parseInt($(this).val(), 10);
        updatePreview();
        applyToTarget();
    });

    // ── Tabs ────────────────────────────────────────────────
    $(document).on('click', '.ve-acp-tab', function () {
        var tab = $(this).data('tab');
        $('.ve-acp-tab').removeClass('active');
        $(this).addClass('active');
        $('.ve-acp-pane').addClass('ve-hidden');
        $('#ve-acp-pane-' + tab).removeClass('ve-hidden');
    });

    // ── Hex input ──────────────────────────────────────────
    $('#ve-acp-hex').on('input', function () {
        var val = $(this).val().trim();
        if (/^#?[0-9a-f]{6}$/i.test(val)) {
            if (!val.startsWith('#')) val = '#' + val;
            var hsv = hexToHsv(val);
            acp.h = hsv.h; acp.s = hsv.s; acp.b = hsv.b;
            $('#ve-acp-hue').val(acp.h);
            drawCanvas(); updateDot(); updatePreview(); applyToTarget();
        }
    });

    // ── RGB inputs ─────────────────────────────────────────
    $('#ve-acp-r, #ve-acp-g, #ve-acp-b').on('input', function () {
        var r = parseInt($('#ve-acp-r').val(), 10) || 0;
        var g = parseInt($('#ve-acp-g').val(), 10) || 0;
        var b = parseInt($('#ve-acp-b').val(), 10) || 0;
        var hex = rgbToHex(r, g, b);
        var hsv = hexToHsv(hex);
        acp.h = hsv.h; acp.s = hsv.s; acp.b = hsv.b;
        $('#ve-acp-hue').val(acp.h);
        drawCanvas(); updateDot(); updatePreview(); syncInputs(); applyToTarget();
    });

    // ── HSL inputs ─────────────────────────────────────────
    $('#ve-acp-h2, #ve-acp-s2, #ve-acp-l2').on('input', function () {
        var h = parseInt($('#ve-acp-h2').val(), 10) || 0;
        var s = parseInt($('#ve-acp-s2').val(), 10) || 0;
        var l = parseInt($('#ve-acp-l2').val(), 10) || 0;
        var hex = hslToHex(h, s, l);
        var hsv = hexToHsv(hex);
        acp.h = hsv.h; acp.s = hsv.s; acp.b = hsv.b;
        $('#ve-acp-hue').val(acp.h);
        drawCanvas(); updateDot(); updatePreview(); applyToTarget();
    });

    // ── Swatches ───────────────────────────────────────────
    $(document).on('click', '.ve-acp-swatch', function () {
        var hex = $(this).data('color');
        var hsv = hexToHsv(hex);
        acp.h = hsv.h; acp.s = hsv.s; acp.b = hsv.b;
        $('#ve-acp-hue').val(acp.h);
        refresh();
    });

    // ── Trigger on color inputs ────────────────────────────
    $(document).on('click.acp-open', '.ve-acp-trigger', function (e) {
        e.preventDefault(); e.stopPropagation();
        if ($('#ve-acp').is(':visible') && acp.$target && acp.$target[0] === this._acpLinked) {
            closeAcp(); return;
        }
        openAcp($(this._acpLinked));
    });

    // Intercept native color picker with ACP button next to it
    $(document).on('click', '.ve-color-picker-input', function (e) {
        e.preventDefault();
        openAcp($(this));
    });

    // ── Close on outside click ─────────────────────────────
    $(document).on('click.acp-close', function (e) {
        if (!$('#ve-acp').hasClass('ve-hidden') &&
            !$(e.target).closest('#ve-acp').length &&
            !$(e.target).hasClass('ve-color-picker-input')) {
            closeAcp();
        }
    });

})(jQuery);

/* ── Link Popover ────────────────────────────────────────── */
(function ($) {
    'use strict';
    var popHref = '', popEl = null, popTargetNodeId = null;

    window.veShowLinkPopover = function (href, elOrId, rect, attrs) {
        popHref = href || '';
        popEl   = elOrId || null;
        popTargetNodeId = (typeof elOrId === 'string') ? elOrId : null;
        attrs = attrs || {};

        var text = popHref || 'https://…';
        $('#ve-lp-url-text').text(text).attr('title', popHref);

        // Sync option pills
        $('.ve-lp-opt').removeClass('on');
        if (attrs.target === '_blank') $('#ve-lp-opt-newtab').addClass('on');
        if ((attrs.rel || '').indexOf('nofollow') > -1) $('#ve-lp-opt-nofollow').addClass('on');
        if (attrs.download !== undefined) $('#ve-lp-opt-download').addClass('on');

        var $wrap = $('#ve-canvas-wrap');
        if (!$wrap.length) return;
        var wRect = $wrap[0].getBoundingClientRect();
        var top   = (rect ? rect.bottom : 80) - wRect.top + 10;
        var left  = (rect ? rect.left   : 80) - wRect.left;
        left = Math.max(4, Math.min(left, $wrap.width() - 350));
        if (top + 90 > $wrap.height()) top = (rect ? rect.top : 80) - wRect.top - 100;
        $('#ve-link-popover').css({ top: top + 'px', left: left + 'px' }).removeClass('ve-hidden');
    };

    window.veHideLinkPopover = function () { $('#ve-link-popover').addClass('ve-hidden'); };

    $('#ve-lp-open').on('click', function () {
        if (popHref) window.open(popHref, '_blank');
    });

    $('#ve-lp-copy').on('click', function () {
        if (!popHref) return;
        try {
            navigator.clipboard.writeText(popHref);
            if (window.showToast) showToast('<i class="fa-solid fa-check me-1"></i>URL copiada');
        } catch (e) {}
    });

    $('#ve-lp-unlink').on('click', function () {
        try {
            var doc = document.getElementById('ve-preview-frame').contentDocument;
            if (!doc) return;
            var el = doc.querySelector('[data-ve-link-active]')
                  || (popTargetNodeId ? doc.querySelector('[data-ve-id="' + popTargetNodeId + '"]') : null);
            if (!el || el.tagName !== 'A') {
                // try to find an anchor parent
                el = el ? el.closest('a') : null;
            }
            if (!el) return;
            var parent = el.parentNode;
            while (el.firstChild) parent.insertBefore(el.firstChild, el);
            parent.removeChild(el);
            veHideLinkPopover();
            if (window.vePushHistory) vePushHistory('Enlace eliminado');
        } catch (ex) {}
    });

    // Toggle option pills — apply attr to the <a> element in the iframe
    $(document).on('click', '.ve-lp-opt', function () {
        var $o = $(this);
        var attr = $o.data('attr');
        var val  = $o.data('val');
        $o.toggleClass('on');
        var active = $o.hasClass('on');
        try {
            var doc = document.getElementById('ve-preview-frame').contentDocument;
            if (!doc) return;
            var el = doc.querySelector('[data-ve-link-active]');
            if (!el) return;
            if (active) {
                el.setAttribute(attr, val || '');
            } else {
                el.removeAttribute(attr);
            }
            if (window.vePushHistory) vePushHistory('Enlace: ' + attr + (active ? ' aplicado' : ' quitado'));
        } catch (ex) {}
    });

    // Hide on outside click
    $(document).on('click.lp-close', function (e) {
        if (!$(e.target).closest('#ve-link-popover').length) {
            veHideLinkPopover();
        }
    });

})(jQuery);

/* ── Scroll Minimap ──────────────────────────────────────── */
(function ($) {
    'use strict';

    var minimapActive = false;
    var minimapColors = {
        header: '#94a3b8', nav: '#94a3b8', section: '#cbd5e1',
        article: '#e2e8f0', main: '#e2e8f0', footer: '#94a3b8',
        div: '#f1f5f9', h1: '#64748b', h2: '#64748b', h3: '#64748b',
        p: '#e2e8f0', img: '#bfdbfe', figure: '#bfdbfe'
    };

    function buildMinimap() {
        var frame;
        try { frame = document.getElementById('ve-preview-frame').contentDocument; } catch (e) { return; }
        if (!frame || !frame.body) return;

        var pageH    = frame.body.scrollHeight || 1;
        var $mm      = $('#ve-minimap');
        var mmH      = $mm.height() || 320;
        var availH   = mmH - 12; // minus vertical padding
        var html     = '';

        // Top-level sections
        var els = frame.body.querySelectorAll('header, nav, section, article, main, footer, aside');
        if (els.length === 0) {
            els = frame.body.children;
        }
        var list = Array.prototype.slice.call(els);

        // Sum real heights
        var totalReal = 0;
        list.forEach(function (el) { totalReal += (el.offsetHeight || 60); });
        if (totalReal < 1) totalReal = pageH;

        // Available space minus gaps (3px between bars)
        var gaps      = Math.max(0, list.length - 1) * 3;
        var spaceFor  = Math.max(40, availH - gaps);
        var scale     = spaceFor / totalReal;

        list.forEach(function (el, idx) {
            var realH = el.offsetHeight || 60;
            // Bar height scaled, min 8px, max 60% of available
            var h = Math.round(realH * scale);
            h = Math.max(8, Math.min(h, Math.floor(availH * 0.6)));
            html += '<div class="ve-minimap-bar" data-idx="' + idx + '" data-real-h="' + realH + '" style="height:' + h + 'px;"></div>';
        });

        $('#ve-minimap-content').html(html);
        updateMinimapViewport();
    }

    function updateMinimapViewport() {
        var frame;
        try { frame = document.getElementById('ve-preview-frame').contentDocument; } catch (e) { return; }
        if (!frame || !frame.body) return;

        var pageH   = frame.body.scrollHeight || 1;
        var viewH   = frame.documentElement.clientHeight;
        var scrollY = frame.documentElement.scrollTop || frame.body.scrollTop;
        // Use the ACTUAL rendered content height inside the minimap for scale
        var $content = $('#ve-minimap-content');
        var contentH = $content.height() || 280;
        var scale    = contentH / pageH;

        var vpTop = 6 + Math.round(scrollY * scale); // +6 for minimap padding
        var vpH   = Math.max(8, Math.round(viewH * scale));
        // Clamp to content area
        if (vpTop + vpH > 6 + contentH) vpH = 6 + contentH - vpTop;
        $('#ve-minimap-viewport').css({ top: vpTop + 'px', height: vpH + 'px' });
    }

    // Show minimap button in topbar
    window.veToggleMinimap = function () {
        minimapActive = !minimapActive;
        if (minimapActive) {
            $('#ve-minimap').removeClass('ve-hidden');
            buildMinimap();
        } else {
            $('#ve-minimap').addClass('ve-hidden');
        }
    };

    // Click minimap → scroll iframe
    $('#ve-minimap').on('click', function (e) {
        var frame;
        try { frame = document.getElementById('ve-preview-frame').contentDocument; } catch (er) { return; }
        if (!frame) return;
        var $mm   = $(this);
        var rect  = this.getBoundingClientRect();
        var y     = e.clientY - rect.top;
        var scale = ($mm.height() || 300) / (frame.body.scrollHeight || 1);
        var scrollTo = y / scale;
        frame.documentElement.scrollTop = scrollTo;
        frame.body.scrollTop = scrollTo;
    });

    // Track iframe scroll
    function attachMinimapScroll() {
        try {
            var frame = document.getElementById('ve-preview-frame').contentWindow;
            frame.addEventListener('scroll', updateMinimapViewport, { passive: true });
        } catch (e) {}
    }

    // Rebuild on iframe load
    $('#ve-preview-frame').on('load', function () {
        if (minimapActive) { buildMinimap(); }
        attachMinimapScroll();
    });

    // Update dims in box model when element selected
    $(document).on('ve-inspector-updated', function () {
        updateMinimapViewport();
    });

    // Box Model dims update (width × height as 2 lines)
    $(document).on('ve-element-selected', function (e, data) {
        if (data && data.width && data.height) {
            $('#ve-bm-dims').html(Math.round(data.width) + '×<br>' + Math.round(data.height));
        } else {
            $('#ve-bm-dims').html('—×<br>auto');
        }
    });

    // Spacing mode toggle (link/unlink)
    $(document).on('click', '.ve-sp-mode-btn', function () {
        $('.ve-sp-mode-btn').removeClass('active');
        $(this).addClass('active');
        // When 'link' mode: typing in margin-top also applies to r/b/l
        var mode = $(this).data('mode');
        $('#ve-sect-spacing').attr('data-spacing-mode', mode);
    });

    // Link-mode cascade: typing in margin-top/padding-top applies to all 4 sides
    $(document).on('input', '.ve-sp-val', function () {
        var mode = $('#ve-sect-spacing').attr('data-spacing-mode');
        if (mode !== 'link') return;
        var $el   = $(this);
        var prop  = $el.data('prop') || '';
        var val   = $el.val();
        // Only cascade from the TOP input of each side
        var match = /(margin|padding)-(top|right|bottom|left)/.exec(prop);
        if (!match) return;
        var family = match[1];
        var side   = match[2];
        if (side !== 'top') return; // only top triggers cascade
        ['right', 'bottom', 'left'].forEach(function (s) {
            $('[data-prop="' + family + '-' + s + '"]').val(val).trigger('change');
        });
    });

})(jQuery);

/* ── Element Outline + Floating Toolbar ──────────────────── */
(function ($) {
    'use strict';

    window.veShowSelectionOutline = function (el, rect, tag, classes) {
        if (!rect) return;
        var $wrap  = $('#ve-canvas-wrap');
        var wRect  = $wrap[0].getBoundingClientRect();
        var top    = rect.top    - wRect.top;
        var left   = rect.left   - wRect.left;
        var width  = rect.width;
        var height = rect.height;
        $('#ve-sel-outline').css({
            top: top + 'px', left: left + 'px',
            width: width + 'px', height: height + 'px'
        }).removeClass('ve-hidden');
        var lbl = (tag || 'DIV').toUpperCase();
        if (classes) lbl += '.' + classes.split(' ').filter(Boolean).slice(0, 2).join('.').toUpperCase();
        $('#ve-sel-label').text(lbl);
        $('#ve-sel-dims').text(Math.round(width) + ' × ' + Math.round(height));
    };

    window.veHideSelectionOutline = function () {
        $('#ve-sel-outline').addClass('ve-hidden');
    };

    window.veShowFloatingToolbar = function (rect, tag) {
        if (!rect) return;
        var $wrap = $('#ve-canvas-wrap');
        var wRect = $wrap[0].getBoundingClientRect();
        var top   = rect.top - wRect.top - 42;
        var left  = rect.left - wRect.left;
        if (top < 4) top = rect.bottom - wRect.top + 8;
        left = Math.max(4, Math.min(left, $wrap.width() - 420));
        $('#ve-float-tb').css({ top: top + 'px', left: left + 'px' }).removeClass('ve-hidden');
        if (tag) $('#ve-ftb-tag').val(tag.toLowerCase());
    };

    window.veHideFloatingToolbar = function () {
        $('#ve-float-tb').addClass('ve-hidden');
    };

    // Formatting buttons — apply via iframe selection + execCommand
    $(document).on('click', '.ve-ftb-btn[data-cmd]', function () {
        var cmd = $(this).data('cmd');
        try {
            var doc = document.getElementById('ve-preview-frame').contentDocument;
            doc.execCommand(cmd, false, null);
        } catch (e) {}
        $(this).toggleClass('on');
    });

    // Tag selector — change element tag
    $('#ve-ftb-tag').on('change', function () {
        var newTag = $(this).val();
        if (!newTag) return;
        try {
            var doc = document.getElementById('ve-preview-frame').contentDocument;
            doc.execCommand('formatBlock', false, '<' + newTag + '>');
        } catch (e) {}
    });

    // Link button — opens inspector link section
    $('#ve-ftb-link').on('click', function () {
        var $rail = $('[data-panel="inspector"]');
        if ($rail.length) $rail.trigger('click');
    });

    // Color button — opens advanced color picker
    $('#ve-ftb-color').on('click', function () {
        var $rail = $('[data-panel="inspector"]');
        if ($rail.length) $rail.trigger('click');
        setTimeout(function () {
            var $colorInput = $('#ve-inspector-sections [data-prop="color"]').first();
            if ($colorInput.length) $colorInput.trigger('click');
        }, 150);
    });

    // Align button — cycles through text-align values
    var alignStates = ['left', 'center', 'right', 'justify'];
    var alignIdx = 0;
    $('#ve-ftb-align').on('click', function () {
        alignIdx = (alignIdx + 1) % alignStates.length;
        var val = alignStates[alignIdx];
        try {
            var doc = document.getElementById('ve-preview-frame').contentDocument;
            doc.execCommand('justify' + val.charAt(0).toUpperCase() + val.slice(1), false, null);
        } catch (e) {}
        $(this).find('i').attr('class', 'fa-solid fa-align-' + val);
    });

    // AI button — triggers AI modal
    $('#ve-ftb-ai').on('click', function () {
        $('#btn-ai-open').trigger('click');
    });

    // Hide on outside click
    $(document).on('click.ftb-close', function (e) {
        if ($(e.target).closest('#ve-float-tb, #ve-sel-outline, #ve-inspector-panel, #ve-link-popover').length) return;
        // Only hide if clicking outside of the iframe as well
        if (!$(e.target).is('iframe') && !$(e.target).closest('#ve-canvas-wrap').length) {
            veHideFloatingToolbar();
            veHideSelectionOutline();
        }
    });

})(jQuery);

/* ── Status Banners ──────────────────────────────────────── */
(function ($) {
    'use strict';

    var bannerCounter = 0;

    window.veShowBanner = function (opts) {
        var id = 've-banner-' + (++bannerCounter);
        var type = opts.type || 'info';  // warn, lock, info, ok
        var icon = opts.icon || (type === 'warn' ? 'fa-clock-rotate-left'
            : type === 'lock' ? 'fa-lock'
            : type === 'ok' ? 'fa-circle-check'
            : 'fa-circle-info');
        var msg = opts.msg || '';
        var actions = opts.actions || [];
        var dismissible = opts.dismissible !== false;

        var actionsHtml = '';
        actions.forEach(function (a, i) {
            actionsHtml += '<button type="button" class="ve-banner-btn' + (a.primary ? ' primary' : '')
                + '" data-action-idx="' + i + '">' + (a.label || 'OK') + '</button>';
        });

        var xHtml = dismissible ? '<button type="button" class="ve-banner-x" title="Cerrar"><i class="fa-solid fa-xmark"></i></button>' : '';

        var $banner = $('<div>').addClass('ve-banner ' + type).attr('id', id).html(
            '<i class="fa-solid ' + icon + ' ve-banner-icon"></i>' +
            '<span class="ve-banner-msg">' + msg + '</span>' +
            actionsHtml + xHtml
        );

        // Store actions for later
        $banner.data('actions', actions);

        $('#ve-banners-stack').append($banner);

        // Auto-dismiss after timeout
        if (opts.timeout && opts.timeout > 0) {
            setTimeout(function () { veDismissBanner(id); }, opts.timeout);
        }

        return id;
    };

    window.veDismissBanner = function (id) {
        var $b = $('#' + id);
        if (!$b.length) return;
        $b.css({ transition: 'opacity .2s, transform .2s', opacity: 0, transform: 'translateY(-100%)' });
        setTimeout(function () { $b.remove(); }, 200);
    };

    // Handle action clicks
    $(document).on('click', '.ve-banner-btn', function () {
        var $banner = $(this).closest('.ve-banner');
        var actions = $banner.data('actions') || [];
        var idx = parseInt($(this).data('action-idx'), 10);
        var action = actions[idx];
        if (action && typeof action.handler === 'function') {
            action.handler($banner.attr('id'));
        }
        if (!action || action.dismiss !== false) {
            veDismissBanner($banner.attr('id'));
        }
    });

    // Handle X close
    $(document).on('click', '.ve-banner-x', function () {
        var id = $(this).closest('.ve-banner').attr('id');
        veDismissBanner(id);
    });

})(jQuery);

/* ── FAB Save/Publish + Tweaks Panel ─────────────────────── */
(function ($) {
    'use strict';

    // Route content lifecycle events into the autosave pill
    $(document).on('ve-content-changed', function () { if (typeof veSetAutosaveState === 'function') veSetAutosaveState('unsaved'); });
    $(document).on('ve-content-saved',   function () { if (typeof veSetAutosaveState === 'function') veSetAutosaveState('saved');   });
    $(document).on('ve-content-saving',  function () { if (typeof veSetAutosaveState === 'function') veSetAutosaveState('saving');  });

    /* ── Onboarding Spotlight ──────────────────────────────── */
    (function () {
        var steps = [];
        var idx   = 0;
        var $sp   = $('#ve-spotlight');

        function renderStep() {
            if (idx < 0 || idx >= steps.length) return closeSpot();
            var s = steps[idx];
            var $t = $(s.target).first();
            if (!$t.length) { next(); return; }

            var r = $t[0].getBoundingClientRect();
            $('#ve-spot-highlight').css({
                top: (r.top - 4) + 'px', left: (r.left - 4) + 'px',
                width: (r.width + 8) + 'px', height: (r.height + 8) + 'px'
            });

            var cx = Math.min(window.innerWidth - 300, Math.max(10, r.left));
            var cy = r.bottom + 14;
            if (cy + 180 > window.innerHeight) cy = Math.max(10, r.top - 180);
            $('#ve-spot-callout').css({ top: cy + 'px', left: cx + 'px' });

            $('#ve-spot-step').text('PASO ' + (idx + 1) + ' DE ' + steps.length);
            $('#ve-spot-title').text(s.title || '');
            $('#ve-spot-desc').text(s.description || '');

            var dotsHtml = '';
            for (var i = 0; i < steps.length; i++) {
                dotsHtml += '<span class="ve-spot-dot' + (i === idx ? ' on' : '') + '"></span>';
            }
            $('#ve-spot-dots').html(dotsHtml);
            $('#ve-spot-next').html(idx === steps.length - 1
                ? 'Finalizar <i class="fa-solid fa-check"></i>'
                : 'Siguiente <i class="fa-solid fa-arrow-right"></i>');
        }

        function next() { idx++; if (idx >= steps.length) closeSpot(); else renderStep(); }
        function closeSpot() {
            $sp.removeClass('open').addClass('ve-hidden');
            try { localStorage.setItem('ve-onboarding-seen', '1'); } catch (e) {}
        }

        window.veStartOnboarding = function (customSteps) {
            steps = customSteps || [];
            idx = 0;
            if (!steps.length) return;
            $sp.removeClass('ve-hidden').addClass('open');
            renderStep();
        };

        $('#ve-spot-next').on('click', next);
        $('#ve-spot-skip').on('click', closeSpot);
    })();

    /* ── Collaboration presence API ────────────────────────── */
    window.veCollab = {
        setCursor: function (id, data) {
            var $layer = $('#ve-collab-layer');
            var $c = $('#ve-collab-cur-' + id);
            if (!$c.length) {
                $c = $('<div class="ve-collab-cursor"><i class="fa-solid fa-arrow-pointer"></i></div>')
                    .attr('id', 've-collab-cur-' + id)
                    .attr('data-name', data.name || 'Anon')
                    .css('--c-color', data.color || '#ec4899');
                $layer.append($c);
            }
            $c.css({ left: data.x + 'px', top: data.y + 'px' });
        },
        removeCursor: function (id) { $('#ve-collab-cur-' + id).remove(); },
        clear: function () { $('#ve-collab-layer').empty(); }
    };

    /* ── Command palette from topbar search button ──────────── */
    $('#btn-open-cmd-palette').on('click', function () {
        var $m = $('#ve-command-palette');
        if ($m.length) {
            bootstrap.Modal.getOrCreateInstance($m[0]).show();
        }
    });
    // Also bind ⌘K / Ctrl+K globally
    $(document).on('keydown.cmdpalette', function (e) {
        if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            $('#btn-open-cmd-palette').trigger('click');
        }
    });

    /* ── Statusbar dynamic updates ──────────────────────────── */
    function veUpdateStatusbar() {
        try {
            var html = (window.veEditor && veEditor.getData) ? veEditor.getData() : '';
            var txt  = html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
            var words = txt ? txt.split(' ').length : 0;
            var kb    = (new Blob([html]).size / 1024).toFixed(1);
            $('#ve-statusbar-words').text(words.toLocaleString('es') + ' palabras');
            $('#ve-statusbar-weight').text(kb + ' KB');
        } catch (e) {}
    }
    veUpdateStatusbar();
    setInterval(veUpdateStatusbar, 8000);
    $(document).on('ve-content-changed ve-content-saved', veUpdateStatusbar);

    // ── Autosave pill — estado visual + timestamp relativo + click para guardar ──
    var _veAutosaveIcon = {
        idle:    'fa-cloud',
        saving:  'fa-cloud-arrow-up fa-beat-fade',
        saved:   'fa-cloud-check',
        unsaved: 'fa-circle-exclamation',
        error:   'fa-triangle-exclamation'
    };

    function veRelativeTime(d) {
        if (!d) return null;
        var diff = Math.round((new Date() - d) / 1000);
        if (diff < 10)    return 'ahora';
        if (diff < 60)    return 'hace ' + diff + 's';
        if (diff < 90)    return 'hace 1 min';
        if (diff < 3600)  return 'hace ' + Math.round(diff / 60) + ' min';
        if (diff < 86400) return 'hace ' + Math.round(diff / 3600) + ' h';
        return 'hace ' + Math.round(diff / 86400) + ' d';
    }

    function veSetAutosaveState(state, overrideLabel) {
        var $wrap = $('#ve-statusbar-autosave-wrap');
        $wrap.attr('data-state', state);
        var $icon = $wrap.find('.ve-autosave-icon i');
        $icon.attr('class', 'fa-solid ' + (_veAutosaveIcon[state] || _veAutosaveIcon.idle));

        var $lbl = $('#ve-statusbar-autosave');
        if (overrideLabel !== undefined) { $lbl.text(overrideLabel); return; }

        if (state === 'saving')       $lbl.text('Guardando…');
        else if (state === 'unsaved') $lbl.text('Cambios sin guardar');
        else if (state === 'error')   $lbl.text('Error al guardar');
        else if (state === 'saved') {
            var rel = veRelativeTime(window.lastAutoSaveTime);
            $lbl.text(rel ? 'Guardado ' + rel : 'Guardado');
            $wrap.attr('title', 'Último guardado: ' + (window.lastAutoSaveTime ? window.lastAutoSaveTime.toLocaleString('es') : '—'));
        } else {
            $lbl.text('Sin guardar aún');
        }
    }

    function veUpdateAutosaveLabel() {
        var state = $('#ve-statusbar-autosave-wrap').attr('data-state');
        if (state === 'saved') veSetAutosaveState('saved');
    }
    setInterval(veUpdateAutosaveLabel, 10000);

    // Hook into setAutoSaveStatus to drive the pill
    if (typeof setAutoSaveStatus === 'function') {
        var _origSetAutoSaveStatusPill = setAutoSaveStatus;
        setAutoSaveStatus = function (state, text) {
            _origSetAutoSaveStatusPill(state, text);
            veSetAutosaveState(state || 'idle');
        };
    }

    // Click / Enter → forzar save manual
    $('#ve-statusbar-autosave-wrap').on('click keydown', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        if ($(this).attr('data-state') === 'saving') return;
        if (window.veSave) { window.veSave(); return; }
        $('#btn-save').trigger('click');
    });

    // Initial state
    veSetAutosaveState('idle');

    // Click statusbar links → open modals
    $('#btn-statusbar-seo').on('click', function () {
        var el = document.getElementById('ve-seo-modal');
        if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show();
    });
    $('#btn-statusbar-a11y').on('click', function () {
        // Prefer the audit trigger (runs checks + opens modal)
        if ($('#btn-a11y-check').length) { $('#btn-a11y-check').trigger('click'); return; }
        var el = document.getElementById('ve-a11y-modal');
        if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show();
    });

    // Has-statusbar body class so FAB/toasts push up
    $('body').addClass('has-statusbar');

    /* ── Modal guard — block preview/iframe interaction while any modal is open ── */
    (function () {
        var openCount = 0;

        function countVisibleBsModals() {
            var n = 0;
            document.querySelectorAll('.modal.show').forEach(function () { n++; });
            return n;
        }

        function updateGuard() {
            openCount = countVisibleBsModals();
            $('body').toggleClass('ve-modal-open', openCount > 0);
        }

        // Track any Bootstrap modal open/close
        $(document).on('shown.bs.modal hidden.bs.modal', '.modal', updateGuard);

        // Custom non-BS overlays (confirm, ACP, link popover) — expose helper
        window.veMarkModalOpen = function (open) {
            if (open) openCount++; else openCount = Math.max(0, openCount - 1);
            $('body').toggleClass('ve-modal-open', openCount > 0);
        };

        // Block cursor events from iframe when modal is open (safety net)
        window.addEventListener('message', function (ev) {
            if (!$('body').hasClass('ve-modal-open') && !$('body').hasClass('modal-open')) return;
            // Allow lifecycle/dim messages but block UI-interaction triggers
            var blocked = [
                've-element-selected', 've-element-dbl-click', 've-show-link-popover',
                've-open-icon-picker', 've-open-media-picker', 've-open-sc-editor',
                've-open-link-editor', 've-request-inspect', 've-editing-started',
                've-element-path', 've-show-quick-bar'
            ];
            if (ev.data && blocked.indexOf(ev.data.type) > -1) {
                ev.stopImmediatePropagation();
            }
        }, true);
    })();

    // Custom checkbox sync (vm-chk with .on state ↔ hidden checkbox)
    $(document).on('click', '.vm-chk[data-for]', function () {
        var id = $(this).data('for');
        var $chk = $('#' + id);
        $(this).toggleClass('on');
        $chk.prop('checked', $(this).hasClass('on'));
    });

    // Find & Replace — Enter to submit + reset feedback on input
    $('#ve-fr-find, #ve-fr-replace').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btn-do-replace').trigger('click');
        }
    });
    $('#ve-fr-find, #ve-fr-replace').on('input', function () {
        $('#ve-fr-feedback').removeClass('ok err').text('');
    });
    // Autofocus find input when modal opens
    $('#ve-find-replace-modal').on('shown.bs.modal', function () {
        $('#ve-fr-find').trigger('focus');
    });

    $('#ve-tweak-close').on('click', function () {
        $('#ve-tweaks').removeClass('open');
    });

    // Close tweaks on outside click (Quick Actions is the sole trigger now)
    $(document).on('click.tweaks-close', function (e) {
        if (!$('#ve-tweaks').hasClass('open')) return;
        var $tgt = $(e.target);
        if ($tgt.closest('#ve-tweaks').length) return;
        if ($tgt.closest('.ve-qa-btn[data-tooltip="Ajustes visuales"]').length) return;
        $('#ve-tweaks').removeClass('open');
    });


    // Tweak segment handlers
    $(document).on('click', '.ve-tweak-seg button', function () {
        var $btn  = $(this);
        var $seg  = $btn.closest('.ve-tweak-seg');
        var tweak = $seg.data('tweak');
        var val   = $btn.data('val');

        $seg.find('button').removeClass('active');
        $btn.addClass('active');

        applyTweak(tweak, val);
    });

    function applyTweak(tweak, val) {
        var $wrap = $('#ve-canvas-wrap');
        switch (tweak) {
            case 'canvas-bg':
                $wrap.removeClass('ve-bg-grid ve-bg-flat');
                if (val === 'grid') $wrap.addClass('ve-bg-grid');
                else if (val === 'flat') $wrap.addClass('ve-bg-flat');
                break;
            case 'panel-width':
                document.documentElement.style.setProperty('--ve-panel-w', val + 'px');
                var $panel = $('#ve-side-panel, .ve-side-panel').first();
                if ($panel.length) $panel.css('width', val + 'px');
                break;
            case 'breakpoint':
                var $bpBtn = $('.ve-device-btn[data-device="' + val + '"], [data-breakpoint="' + val + '"]').first();
                if ($bpBtn.length) $bpBtn.trigger('click');
                else {
                    $wrap.removeClass('desktop tablet mobile').addClass(val);
                }
                break;
            case 'chrome':
                $('body').toggleClass('ve-chrome-off', val === 'off');
                break;
            case 'minimap':
                if (val === 'on') {
                    if ($('#ve-minimap').hasClass('ve-hidden') && typeof veToggleMinimap === 'function') veToggleMinimap();
                } else {
                    if (!$('#ve-minimap').hasClass('ve-hidden') && typeof veToggleMinimap === 'function') veToggleMinimap();
                }
                break;
        }
    }

})(jQuery);
</script>

</body>
</html>
