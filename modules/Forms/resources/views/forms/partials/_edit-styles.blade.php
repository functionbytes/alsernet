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
