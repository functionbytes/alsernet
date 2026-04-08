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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        /* ── Toolbar ────────────────────────────────────────── */
        #ve-toolbar {
            height: 40px;
            background: #fff;
            color: #333;
            display: flex;
            align-items: center;
            padding: 0 10px;
            gap: 6px;
            flex-shrink: 0;
            border-bottom: 1px solid #e9ecef;
            position: relative;
            z-index: 200;
        }
        #ve-toolbar .vr { background: #dee2e6; width: 1px; height: 20px; margin: 0 2px; flex-shrink: 0; }
        #ve-toolbar .btn-outline-secondary {
            border-color: #dee2e6;
            color: #6c757d;
        }
        #ve-toolbar .btn-outline-secondary:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
            color: #333;
        }
        #ve-toolbar .btn-outline-secondary.active,
        #ve-toolbar .breakpoint-btn.active {
            background: #1a1a1a !important;
            border-color: #1a1a1a !important;
            color: #fff !important;
        }
        #ve-toolbar .text-white { color: #333 !important; }
        #autosave-status { font-size: 11px; }
        #autosave-status.saving  { color: #ffc107; }
        #autosave-status.saved   { color: #555; }
        #autosave-status.error   { color: #FA896B; }
        #autosave-status.unsaved { color: #888; }

        /* ── Layout ─────────────────────────────────────────── */
        #ve-body {
            display: flex;
            height: 100vh;
            overflow: hidden;
            min-height: 0;
        }

        /* ── Sidebar ─────────────────────────────────────────── */
        #ve-sidebar {
            width: 350px;
            min-width: 280px;
            display: flex;
            flex-direction: row;
            background: #fff;
            border-right: 1px solid #e9ecef;
            flex-shrink: 0;
            min-height: 0;
            position: relative;
        }

        /* Sidebar vertical icon nav */
        #ve-sidebar-nav {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding-top: 8px;
            width: 48px;
            flex-shrink: 0;
            border-right: 1px solid #e9ecef;
            background: #fff;
        }
        #ve-sidebar-nav .ve-nav-btn {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 16px;
            border-radius: 0;
            transition: all .15s;
            padding: 0;
        }
        #ve-sidebar-nav .ve-nav-btn span { display: none; }
        #ve-sidebar-nav .ve-nav-btn i { font-size: 18px; }
        #ve-sidebar-nav .ve-nav-btn:hover { color: #333; background: #f8f9fa; }
        #ve-sidebar-nav .ve-nav-btn.active {
            color: #1a1a1a;
            background: transparent;
            position: relative;
        }
        #ve-sidebar-nav .ve-nav-btn.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 3px;
            background: #1a1a1a;
            border-radius: 0 2px 2px 0;
        }
        #ve-sidebar-nav .ve-nav-back {
            width: 48px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            text-decoration: none;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 4px;
            flex-shrink: 0;
        }
        #ve-sidebar-nav .ve-nav-back:hover { color: #333; background: #f8f9fa; }

        /* Sidebar panels container */
        #ve-sidebar-panels {
            flex: 1;
            overflow: hidden;
            background: #fff;
            display: flex;
            flex-direction: column;
            min-height: 0;
            min-width: 0;
        }

        /* Bottom action toolbar */
        #ve-sidebar-actions {
            display: flex;
            align-items: center;
            height: 40px;
            flex-shrink: 0;
            background: #fff;
        }
        #ve-sidebar-actions .ve-action-btn {
            flex: none;
            width: 40px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            border-right: 1px solid #e9ecef;
            color: #6c757d;
            cursor: pointer;
            font-size: 14px;
            transition: all .15s;
            text-decoration: none;
        }
        #ve-sidebar-actions .ve-action-btn:hover { background: #f8f9fa; color: #333; }
        #ve-sidebar-actions .ve-action-btn:disabled { opacity: .35; cursor: default; }
        #ve-sidebar-actions .ve-action-btn:disabled:hover { background: transparent; color: #6c757d; }
        #ve-sidebar-actions .ve-save-btn {
            flex: none;
            width: auto;
            min-width: 70px;
            margin-left: auto;
            border-right: none;
            border-left: 1px solid #e9ecef;
            font-size: 13px;
            font-weight: 500;
            color: #6c757d;
        }
        #ve-sidebar-actions .ve-save-btn:hover { color: #1a1a1a; }
        #ve-sidebar-actions .ve-save-btn:disabled { opacity: .35; }
        /* Responsive dropdown button — hide Bootstrap caret */
        #btn-responsive-bar::after { display: none; }
        #btn-responsive-bar { font-size: 14px; }

        /* Sidebar collapse transition */
        #ve-sidebar { transition: margin-left .2s ease; }

        /* Sidebar toggle button */
        #ve-sidebar-toggle {
            position: absolute;
            top: 50%;
            right: -14px;
            transform: translateY(-50%);
            width: 14px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid #dee2e6;
            border-left: none;
            border-radius: 0 6px 6px 0;
            cursor: pointer;
            color: #adb5bd;
            font-size: 10px;
            z-index: 10;
            box-shadow: 2px 0 6px rgba(0,0,0,.08);
            transition: all .15s;
        }
        #ve-sidebar-toggle:hover {
            color: #333;
            background: #f8f9fa;
            box-shadow: 2px 0 8px rgba(0,0,0,.12);
        }
        .ve-panel { display: none; flex: 1; flex-direction: column; overflow: hidden; min-height: 0; }
        .ve-panel.active { display: flex; }

        /* Thin scrollbar for sidebar panels */
        #ve-sidebar-panels,
        #ve-sidebar-panels * {
            scrollbar-width: thin;
            scrollbar-color: rgb(193 193 193) transparent;
        }
        #ve-sidebar-panels ::-webkit-scrollbar { width: 6px; height: 6px; }
        #ve-sidebar-panels ::-webkit-scrollbar-thumb { background: rgb(193 193 193); border-radius: 10px; }
        #ve-sidebar-panels ::-webkit-scrollbar-track { background: transparent; }

        /* ── Canvas ──────────────────────────────────────────── */
        #ve-canvas {
            flex: 1;
            background: #404040;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }
        #ve-canvas-bar {
            height: 44px;
            background: #2a2a3a;
            display: flex;
            align-items: center;
            padding: 0 12px;
            gap: 6px;
            flex-shrink: 0;
            border-bottom: 1px solid #1e1e2e;
        }
        #ve-canvas-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;         /* allows scroll when iframe is smaller than canvas (tablet/mobile) */
            position: relative;
        }
        #ve-canvas-wrap.desktop  { align-items: stretch; overflow: hidden; }
        #ve-canvas-wrap.desktop #ve-preview-frame { width: 100%; height: 100%; box-shadow: none; border-radius: 0; }

        #ve-preview-frame {
            flex: none;
            border: none;
            background: #fff;
            transition: width .3s, height .3s;
        }

        /* Drag overlay (covers iframe during drag) */
        #ve-drag-overlay {
            display: none;
            position: absolute;
            inset: 0;
            z-index: 50;
            cursor: copy;
        }
        #ve-drag-overlay.active { display: block; }

        /* Preview loading spinner */
        #ve-preview-spinner {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(64,64,64,.5);
            align-items: center;
            justify-content: center;
            z-index: 40;
        }
        #ve-preview-spinner.visible { display: flex; }

        /* ── Context menu ─────────────────────────────────────── */
        #ve-context-menu {
            display: none;
            position: fixed;
            z-index: 9999;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,.15);
            min-width: 170px;
            padding: 4px 0;
        }
        .ve-ctx-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            font-size: 13px;
            cursor: pointer;
            color: #333;
            transition: background .1s;
        }
        .ve-ctx-item:hover { background: #f8f9fa; }
        .ve-ctx-item.text-danger { color: #dc3545 !important; }
        .ve-ctx-item.text-danger:hover { background: #fff5f5; }
        .ve-ctx-divider { height: 1px; background: #e9ecef; margin: 4px 0; }

        /* ── Blocks panel ─────────────────────────────────────── */
        .ve-block-item {
            cursor: grab;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            overflow: hidden;
            transition: border-color .15s, box-shadow .15s, transform .1s;
            user-select: none;
        }
        .ve-block-item:hover {
            border-color: #adb5bd;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }
        .ve-block-item.dragging {
            opacity: .5;
            transform: scale(.98);
        }
        .ve-block-item:active { cursor: grabbing; }

        /* Drop indicator in canvas */
        #ve-drop-line {
            display: none;
            position: absolute;
            height: 3px;
            background: #1a1a1a;
            border-radius: 2px;
            pointer-events: none;
            z-index: 200;
            transition: top .05s, left .05s, width .05s;
        }
        #ve-drop-line::before {
            content: '';
            display: block;
            width: 10px;
            height: 10px;
            background: #1a1a1a;
            border-radius: 50%;
            position: absolute;
            left: -4px;
            top: -4px;
        }

        /* ── Inspector sections ──────────────────────────────── */
        .ve-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 12px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            user-select: none;
            font-size: 12px;
            font-weight: 600;
            color: #444;
        }
        .ve-section-header:hover { background: #eef; }
        .ve-section-header .ve-section-chevron { font-size: 10px; color: #888; transition: transform .2s; }
        .ve-section-header.collapsed .ve-section-chevron { transform: rotate(-90deg); }

        /* ── History panel ───────────────────────────────────── */
        .ve-history-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            font-size: 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            color: #555;
            transition: background .1s;
        }
        .ve-history-item:hover { background: #f8f8f8; }
        .ve-history-item.current {
            background: #f0f0f0;
            color: #1a1a1a;
            font-weight: 600;
        }
        .ve-history-item .ve-hist-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #ccc;
            flex-shrink: 0;
        }
        .ve-history-item.current .ve-hist-dot { background: #1a1a1a; }

        /* ── Section items (Secciones panel) ─────────────────── */
        .ve-section-item {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 6px;
            cursor: move;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: box-shadow .15s, border-color .15s;
            user-select: none;
        }
        .ve-section-item:hover { border-color: #adb5bd; box-shadow: 0 2px 6px rgba(0,0,0,.1); }
        .ve-section-item.ui-sortable-helper { box-shadow: 0 6px 20px rgba(0,0,0,.15); border-color: #adb5bd; opacity: .9; }
        .ve-section-item.ui-sortable-placeholder { background: #f5f5f5; border: 2px dashed #adb5bd; visibility: visible !important; }
        .ve-section-tag { font-size: 10px; font-family: monospace; background: #e9ecef; color: #6c757d; padding: 1px 5px; border-radius: 3px; flex-shrink: 0; }
        .ve-section-label { font-size: 12px; color: #333; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ve-section-handle { color: #aaa; cursor: move; flex-shrink: 0; }

        /* ── Icon modal grid ─────────────────────────────────── */
        #ve-icon-grid { display: grid; grid-template-columns: repeat(auto-fill, 40px); gap: 6px; max-height: 360px; overflow-y: auto; }
        .ve-icon-item { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6; border-radius: 4px; cursor: pointer; font-size: 16px; transition: all .15s; }
        .ve-icon-item:hover { border-color: #adb5bd; background: #f5f5f5; color: #333; }

        /* ── Sidebar resize handle ──────────────────────────── */
        #ve-sidebar-resize {
            width: 5px;
            background: #e9ecef;
            cursor: col-resize;
            flex-shrink: 0;
            transition: background .15s;
            user-select: none;
        }
        #ve-sidebar-resize:hover,
        #ve-sidebar-resize.resizing { background: #adb5bd; }

        /* ── Zoom bar ───────────────────────────────────────── */
        #ve-zoom-bar {
            position: absolute;
            bottom: 14px;
            right: 14px;
            background: #000;
            border: 1px solid #3d3d50;
            border-radius: 4px;
            padding: 3px 6px;
            display: flex;
            align-items: center;
            gap: 1px;
            z-index: 200;
        }
        .ve-zoom-btn {
            background: transparent;
            border: none;
            color: #aaa;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: all .15s;
            line-height: 1.7;
        }
        .ve-zoom-btn:hover { color: #fff; background: rgba(255,255,255,.08); }
        .ve-zoom-btn.active { background: #fff; color: #1a1a1a; }

        /* ── Scroll sync active highlight ────────────────────── */
        .ve-section-item.ve-scroll-active {
            border-color: #1a1a1a !important;
            background: #f5f5f5 !important;
        }

        /* ── Misc ─────────────────────────────────────────────── */
        .CodeMirror { height: 500px; font-size: 13px; font-family: monospace; }
        .CodeMirror-fullscreen { z-index: 9999 !important; }
        .CodeMirror-dialog { background: #272822; color: #f8f8f2; border-top: 1px solid #555; padding: 5px 10px; font-size: 12px; }
        .CodeMirror-dialog input { background: #3d3d3d; color: #f8f8f2; border: 1px solid #555; border-radius: 3px; padding: 1px 6px; }
        .CodeMirror-foldmarker { color: #e6db74; cursor: pointer; font-size: 11px; padding: 0 4px; background: rgba(255,255,255,.1); border-radius: 3px; }
        #ve-html-editor-modal .modal-body { padding: 0; }
        .ve-editor-toolbar { display: flex; align-items: center; gap: 5px; padding: 5px 10px; background: #1e1e1e; border-bottom: 1px solid #444; flex-wrap: wrap; transition: background .2s, border-color .2s; }
        .ve-editor-toolbar .btn { font-size: 11px; padding: 2px 8px; border-color: #555; color: #ccc; background: transparent; transition: background .2s, color .2s, border-color .2s; }
        .ve-editor-toolbar .btn:hover { background: #333; color: #fff; }
        .ve-editor-toolbar .btn.active { background: #444; color: #fff; }
        .ve-editor-toolbar .ve-tb-sep { width: 1px; height: 16px; background: #555; margin: 0 2px; transition: background .2s; }
        .ve-editor-toolbar.light { background: #f5f5f5; border-bottom-color: #ddd; }
        .ve-editor-toolbar.light .btn { border-color: #ccc; color: #333; }
        .ve-editor-toolbar.light .btn:hover { background: #e0e0e0; color: #000; }
        .ve-editor-toolbar.light .btn.active { background: #d0d0d0; color: #000; }
        .ve-editor-toolbar.light .ve-tb-sep { background: #ccc; }
        .ve-editor-toolbar.light small { color: #666 !important; }

        /* ── Full-page code editor panel ──────────────────────── */
        #ve-panel-code .CodeMirror {
            height: 100%;
            font-size: 12px;
            font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
            border: none;
        }
        #ve-panel-code .CodeMirror-gutters {
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
        }
        /* Scrollbars always visible (horizontal + vertical) */
        #ve-panel-code .CodeMirror-hscrollbar,
        #ve-panel-code .CodeMirror-vscrollbar {
            -webkit-overflow-scrolling: touch;
        }
        #ve-panel-code .CodeMirror-scroll::-webkit-scrollbar { height: 8px; width: 8px; }
        #ve-panel-code .CodeMirror-hscrollbar::-webkit-scrollbar { height: 8px; }
        #ve-panel-code .CodeMirror-vscrollbar::-webkit-scrollbar { width: 8px; }
        #ve-panel-code .CodeMirror-scroll::-webkit-scrollbar-track,
        #ve-panel-code .CodeMirror-hscrollbar::-webkit-scrollbar-track,
        #ve-panel-code .CodeMirror-vscrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        #ve-panel-code .CodeMirror-scroll::-webkit-scrollbar-thumb,
        #ve-panel-code .CodeMirror-hscrollbar::-webkit-scrollbar-thumb,
        #ve-panel-code .CodeMirror-vscrollbar::-webkit-scrollbar-thumb { background: #adb5bd; border-radius: 4px; }
        #ve-panel-code .CodeMirror-scroll::-webkit-scrollbar-thumb:hover,
        #ve-panel-code .CodeMirror-hscrollbar::-webkit-scrollbar-thumb:hover,
        #ve-panel-code .CodeMirror-vscrollbar::-webkit-scrollbar-thumb:hover { background: #6c757d; }

        /* ── Link editor modal ───────────────────────────────── */
        .ve-link-type-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 14px;
            color: #333;
            transition: background .1s;
        }
        .ve-link-type-option:hover { background: #f8f9fa; }
        .ve-link-type-option.active { font-weight: 500; }
        .ve-link-radio {
            width: 18px;
            height: 18px;
            border: 2px solid #ccc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all .15s;
        }
        .ve-link-type-option.active .ve-link-radio {
            border-color: #1a1a1a;
            background: #1a1a1a;
            box-shadow: inset 0 0 0 3px #fff;
        }
    </style>
</head>
<body>

{{-- Hidden CKEditor textarea (must be in DOM before scripts init) --}}
<div style="display:none;" aria-hidden="true">
    <textarea id="ve-content">{{ $initialContent }}</textarea>
</div>

{{-- ── Body ────────────────────────────────────────────────────────────────── --}}
<div id="ve-body">

    {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
    <div id="ve-sidebar">

        {{-- Vertical icon nav --}}
        <div id="ve-sidebar-nav">
            <a href="{{ route('pages.edit', $page) }}" class="ve-nav-btn ve-nav-back" title="Volver">
                <i class="fa-duotone fa-solid fa-chevron-left"></i>
            </a>
            <button class="ve-nav-btn active" data-panel="shortcodes" title="Shortcodes">
                <i class="fa-duotone fa-solid fa-code"></i>
                <span>Shorts</span>
            </button>
            <button class="ve-nav-btn" data-panel="inspector" title="Inspector">
                <i class="fa-duotone fa-solid fa-sliders"></i>
                <span>Inspector</span>
            </button>
            <button class="ve-nav-btn" data-panel="layout" title="Layout">
                <i class="fa-duotone fa-solid fa-table-columns"></i>
                <span>Layout</span>
            </button>
            <button class="ve-nav-btn" data-panel="sections" title="Secciones">
                <i class="fa-duotone fa-solid fa-layer-group"></i>
                <span>Secciones</span>
            </button>
            <button class="ve-nav-btn" data-panel="history" title="Historial">
                <i class="fa-duotone fa-solid fa-clock-rotate-left"></i>
                <span>Historial</span>
            </button>
            <button class="ve-nav-btn" data-panel="code" title="Código HTML">
                <i class="fa-duotone fa-solid fa-file-code"></i>
                <span>HTML</span>
            </button>
            <button class="ve-nav-btn" data-panel="settings" title="Ajustes">
                <i class="fa-duotone fa-solid fa-gear"></i>
                <span>Ajustes</span>
            </button>
        </div>

        {{-- Content panels + bottom actions --}}
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
                    <div class="ve-editor-toolbar" id="ve-code-toolbar" style="flex-shrink:0;">
                        <button type="button" class="btn btn-sm" id="ve-code-btn-format" title="Formatear HTML">
                            <i class="fas fa-wand-magic-sparkles me-1"></i>Formatear
                        </button>
                        <div class="ve-tb-sep"></div>
                        <button type="button" class="btn btn-sm" id="ve-code-btn-fold" title="Colapsar todo">
                            <i class="fas fa-compress-alt me-1"></i>Colapsar
                        </button>
                        <button type="button" class="btn btn-sm" id="ve-code-btn-unfold" title="Expandir todo">
                            <i class="fas fa-expand-alt me-1"></i>Expandir
                        </button>
                        <div class="ve-tb-sep"></div>
                        <button type="button" class="btn btn-sm" id="ve-code-btn-wrap" title="Ajuste de línea">
                            <i class="fas fa-align-left me-1"></i>Ajuste
                        </button>
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm" id="ve-code-btn-theme" title="Tema claro / oscuro">
                                <i class="fas fa-circle-half-stroke"></i>
                            </button>
                            <button class="btn btn-sm" id="ve-code-refresh" title="Sincronizar desde preview">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button class="btn btn-sm active" id="ve-code-apply" title="Aplicar cambios al preview" style="background:#1a1a1a; border-color:#1a1a1a; color:#fff;">
                                <i class="fas fa-check me-1"></i>Aplicar
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

            {{-- Toolbar --}}
            <div id="ve-toolbar">
                <span class="fw-semibold text-truncate" style="max-width:160px;color:#000;font-size:13px;" title="{{ $page->title }}">
                    {{ $page->title }}
                </span>
                <span class="{{ $page->status->badgeClass() }}" style="font-size:10px;">{{ $page->status->label() }}</span>
                <span id="autosave-status" class="ms-1"></span>

                @if(count($supportedLocales) > 1)
                <div class="ms-auto">
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
                </div>
                @endif

                {{-- Hidden controls — still functional via keyboard shortcuts and programmatic triggers --}}
                <div style="display:none;" aria-hidden="true">
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
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-insert-icon" title="Insertar icono">
                        <i class="fa-duotone fa-solid fa-icons"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-export-html" title="Exportar HTML (.html)">
                        <i class="fa-duotone fa-solid fa-file-export"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-rotate-device" title="Rotar dispositivo">
                        <i class="fa-duotone fa-solid fa-mobile-alt"></i> <i class="fa-duotone fa-solid fa-sync-alt" style="font-size:9px;"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-shortcuts" title="Atajos de teclado">
                        <i class="fa-duotone fa-solid fa-keyboard"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-preview-draft" title="Preview borrador en nueva pestaña">
                        <i class="fa-duotone fa-solid fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-find-replace" title="Buscar y reemplazar (Ctrl+H)">
                        <i class="fa-duotone fa-solid fa-exchange-alt"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-grid-overlay" title="Grid overlay">
                        <i class="fa-duotone fa-solid fa-border-all"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-media-manager" title="Gestor de medios">
                        <i class="fa-duotone fa-solid fa-photo-video"></i>
                    </button>
                </div>
            </div>

            {{-- Bottom action toolbar --}}
            <div id="ve-sidebar-actions">
                <button class="ve-action-btn" id="btn-undo-bar" title="Deshacer" disabled>
                    <i class="fa-duotone fa-solid fa-rotate-left"></i>
                </button>
                <button class="ve-action-btn" id="btn-redo-bar" title="Rehacer" disabled>
                    <i class="fa-duotone fa-solid fa-rotate-right"></i>
                </button>
                {{-- Responsive breakpoints dropdown --}}
                <div class="dropdown" style="height:100%;">
                    <button class="ve-action-btn dropdown-toggle" id="btn-responsive-bar"
                            data-bs-toggle="dropdown" aria-expanded="false" title="Vista responsive"
                            style="width:40px;">
                        <i class="fa-duotone fa-solid fa-desktop" id="responsive-bar-icon"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-start" style="font-size:12px; min-width:150px;">
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2 breakpoint-btn active"
                                    data-breakpoint="desktop" title="Escritorio">
                                <i class="fa-duotone fa-solid fa-desktop fa-fw text-muted"></i> Escritorio
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2 breakpoint-btn"
                                    data-breakpoint="laptop" data-width="1280px" data-height="800px">
                                <i class="fa-duotone fa-solid fa-laptop fa-fw text-muted"></i> Laptop
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2 breakpoint-btn"
                                    data-breakpoint="tablet" data-width="768px" data-height="1024px">
                                <i class="fa-duotone fa-solid fa-tablet-screen-button fa-fw text-muted"></i> Tablet
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2 breakpoint-btn"
                                    data-breakpoint="mobile" data-width="375px" data-height="812px">
                                <i class="fa-duotone fa-solid fa-mobile-screen-button fa-fw text-muted"></i> Móvil
                            </button>
                        </li>
                    </ul>
                </div>
                <a href="{{ $previewUrl }}" target="_blank" class="ve-action-btn" title="Preview">
                    <i class="fa-duotone fa-solid fa-eye"></i>
                </a>
                <button class="ve-action-btn ve-save-btn" id="btn-save-bar">
                    Guardar
                </button>
            </div>

        </div>

        {{-- Sidebar toggle --}}
        <div id="ve-sidebar-toggle" title="Colapsar barra lateral">
            <i class="fa-duotone fa-solid fa-chevron-left"></i>
        </div>

    </div>

    {{-- ── Resize handle ──────────────────────────────────────────────── --}}
    <div id="ve-sidebar-resize" title="Arrastrar para redimensionar"></div>

    {{-- ── Canvas ───────────────────────────────────────────────────────── --}}
    <div id="ve-canvas">
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

        {{-- Zoom bar --}}
        <div id="ve-zoom-bar">
            <button class="ve-zoom-btn" data-zoom="0.5">50%</button>
            <button class="ve-zoom-btn" data-zoom="0.75">75%</button>
            <button class="ve-zoom-btn active" data-zoom="1">100%</button>
            <button class="ve-zoom-btn" data-zoom="1.25">125%</button>
            <button class="ve-zoom-btn" data-zoom="1.5">150%</button>
        </div>
    </div>

</div>

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
            // If the code panel is active, use its content directly — the user edited
            // the raw HTML and we must not lose their changes by reading from the iframe.
            if ($('#ve-panel-code').hasClass('active') && window._veFullCodeMirror) {
                resolve(window._veFullCodeMirror.getValue());
                return;
            }
            // Always try to extract from iframe first (gets clean .ck-content)
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
                        .css({ background: '#28a745', 'border-color': '#28a745' });
                    setTimeout(function () {
                        $btn.html('<i class="fa-duotone fa-solid fa-save me-1"></i>Guardar')
                            .css({ background: '#1a1a1a', 'border-color': '#1a1a1a' });
                    }, 2500);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Error al guardar';
                    $btn.html('<i class="fa-duotone fa-solid fa-exclamation-triangle me-1"></i>Error')
                        .css({ background: '#dc3545', 'border-color': '#dc3545' });
                    alert(msg);
                    setTimeout(function () {
                        $btn.html('<i class="fa-duotone fa-solid fa-save me-1"></i>Guardar')
                            .css({ background: '#1a1a1a', 'border-color': '#1a1a1a' });
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
    $('#ve-code-btn-theme').on('click', function () {
        if (!veFullCodeMirror) { return; }
        veCodeDark = !veCodeDark;
        veFullCodeMirror.setOption('theme', veCodeDark ? 'monokai' : 'default');
        $('#ve-code-toolbar').toggleClass('light', !veCodeDark);
        $(this).toggleClass('active', veCodeDark);
        // Sync apply button visibility in dark mode
        $('#ve-code-apply').css({ background: veCodeDark ? '#444' : '#1a1a1a', 'border-color': veCodeDark ? '#444' : '#1a1a1a' });
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

</body>
</html>
