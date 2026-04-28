@extends('theme::layouts.manager')

@section('title', 'Builder · '.$template->name)

@push('styles')
<style>
    /* ── Reset & shell ────────────────────────────────────────────── */
    .builder-shell {
        --b-bg: #f7f8fa;
        --b-canvas-bg: #eef0f4;
        --b-border: #e5e7eb;
        --b-text: #111827;
        --b-muted: #6b7280;
        --b-primary: #4f46e5;
        --b-primary-light: #eef2ff;
        --b-primary-dark: #4338ca;
        --b-success: #10b981;
        --b-warning: #f59e0b;
        --b-danger: #ef4444;
        position: fixed;
        inset: 56px 0 0 0; /* deja header del sistema arriba */
        background: var(--b-bg);
        display: grid;
        grid-template-rows: 56px 1fr;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: var(--b-text);
        z-index: 100;
    }

    /* ── Topbar ──────────────────────────────────────────────────── */
    .b-topbar {
        background: white;
        border-bottom: 1px solid var(--b-border);
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 0 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
    }
    .b-back { color: var(--b-muted); text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 6px; }
    .b-back:hover { color: var(--b-text); }
    .b-title-input { border: 0; outline: 0; font-size: 14px; font-weight: 500; padding: 6px 8px; border-radius: 6px; background: transparent; min-width: 240px; }
    .b-title-input:focus, .b-title-input:hover { background: var(--b-bg); }
    .b-saved { color: var(--b-muted); font-size: 12px; display: flex; align-items: center; gap: 4px; }
    .b-saved.dirty { color: var(--b-warning); }
    .b-saved.saved { color: var(--b-success); }
    .b-actions { margin-left: auto; display: flex; gap: 6px; align-items: center; }

    .b-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px; border-radius: 6px; border: 1px solid var(--b-border); background: white; font-size: 13px; cursor: pointer; transition: all .12s; }
    .b-btn:hover { border-color: var(--b-primary); color: var(--b-primary); background: var(--b-primary-light); }
    .b-btn:disabled { opacity: .5; cursor: not-allowed; }
    .b-btn-primary { background: var(--b-primary); color: white; border-color: var(--b-primary); }
    .b-btn-primary:hover { background: var(--b-primary-dark); color: white; border-color: var(--b-primary-dark); }
    .b-btn-icon { padding: 7px; }

    .b-device-toggle { display: inline-flex; gap: 0; padding: 2px; background: var(--b-bg); border-radius: 6px; border: 1px solid var(--b-border); }
    .b-device-toggle button { padding: 4px 10px; border: 0; background: transparent; cursor: pointer; border-radius: 4px; font-size: 12px; color: var(--b-muted); }
    .b-device-toggle button.active { background: white; color: var(--b-text); box-shadow: 0 1px 2px rgba(0,0,0,.08); }

    /* ── Layout 3 columnas ───────────────────────────────────────── */
    .b-layout { display: grid; grid-template-columns: 280px 1fr 320px; height: 100%; overflow: hidden; }
    .b-panel { background: white; overflow-y: auto; height: 100%; }
    .b-panel.sidebar { border-right: 1px solid var(--b-border); }
    .b-panel.settings { border-left: 1px solid var(--b-border); }

    /* ── Sidebar tabs ─────────────────────────────────────────────── */
    .b-tabs { display: flex; border-bottom: 1px solid var(--b-border); background: white; position: sticky; top: 0; z-index: 1; }
    .b-tab { flex: 1; padding: 12px; border: 0; background: transparent; cursor: pointer; font-size: 12px; color: var(--b-muted); border-bottom: 2px solid transparent; font-weight: 500; }
    .b-tab.active { color: var(--b-primary); border-bottom-color: var(--b-primary); }
    .b-tab:hover { color: var(--b-text); }
    .b-section { padding: 16px; }
    .b-section-title { font-size: 11px; text-transform: uppercase; color: var(--b-muted); letter-spacing: .04em; font-weight: 600; margin: 0 0 10px; }

    /* ── Paleta de bloques ───────────────────────────────────────── */
    .b-blocks-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .b-block-card {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 6px; padding: 14px 8px;
        border: 1px solid var(--b-border); border-radius: 8px;
        background: white; cursor: grab; transition: all .12s;
        font-size: 11px; color: var(--b-text); text-align: center;
    }
    .b-block-card:hover { border-color: var(--b-primary); background: var(--b-primary-light); transform: translateY(-1px); box-shadow: 0 4px 8px rgba(79,70,229,.1); }
    .b-block-card:active { cursor: grabbing; }
    .b-block-card svg { width: 22px; height: 22px; color: var(--b-primary); }

    /* ── Variables ────────────────────────────────────────────────── */
    .b-var-pill { display: inline-block; background: #fef3c7; color: #92400e; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-family: ui-monospace, SFMono-Regular, monospace; cursor: pointer; margin: 0 4px 4px 0; transition: all .1s; }
    .b-var-pill:hover { background: #fde68a; }
    .b-var-group-title { font-size: 11px; font-weight: 600; color: var(--b-muted); margin: 12px 0 6px; }
    .b-var-warn { background: #fef3c7; color: #92400e; padding: 8px 10px; border-radius: 6px; font-size: 12px; margin-top: 12px; border: 1px solid #fde68a; }

    /* ── Canvas ───────────────────────────────────────────────────── */
    .b-canvas {
        background: var(--b-canvas-bg);
        overflow-y: auto; height: 100%;
        display: flex; justify-content: center;
        padding: 32px 24px;
    }
    .b-canvas-frame {
        background: white;
        width: 600px; max-width: 100%;
        min-height: calc(100% - 40px);
        box-shadow: 0 8px 32px rgba(0,0,0,.06);
        border-radius: 4px;
        display: flex; flex-direction: column;
    }
    .b-canvas-frame.mobile { width: 375px; }

    .b-subject-row { padding: 12px 16px; border-bottom: 1px dashed var(--b-border); }
    .b-subject-row input { border: 0; outline: 0; width: 100%; font-size: 13px; color: var(--b-muted); }
    .b-subject-row input:focus { color: var(--b-text); }

    .b-blocks-canvas { flex: 1; min-height: 200px; padding: 0; }
    .b-canvas-block {
        position: relative;
        border: 2px solid transparent;
        cursor: pointer;
        transition: border-color .1s;
    }
    .b-canvas-block:hover { border-color: rgba(79,70,229,.3); }
    .b-canvas-block.selected { border-color: var(--b-primary); }
    .b-canvas-block.selected::before {
        content: ''; position: absolute; inset: -2px; border: 2px solid var(--b-primary); pointer-events: none; border-radius: 2px;
    }

    .b-block-toolbar {
        position: absolute; top: -32px; right: -2px; z-index: 10;
        display: none; align-items: center; gap: 4px;
        background: var(--b-primary); color: white;
        padding: 4px; border-radius: 6px 6px 0 0;
        font-size: 11px; box-shadow: 0 2px 8px rgba(79,70,229,.3);
    }
    .b-canvas-block.selected .b-block-toolbar { display: flex; }
    .b-block-toolbar-tag { padding: 0 8px; font-weight: 500; text-transform: uppercase; font-size: 10px; letter-spacing: .04em; }
    .b-block-toolbar button { background: transparent; border: 0; color: white; padding: 4px 6px; border-radius: 3px; cursor: pointer; font-size: 13px; }
    .b-block-toolbar button:hover { background: rgba(255,255,255,.2); }
    .b-block-toolbar button:disabled { opacity: .4; cursor: not-allowed; }

    .b-canvas-empty {
        text-align: center; padding: 60px 20px; color: var(--b-muted); font-size: 14px;
        border: 2px dashed var(--b-border); margin: 20px;
    }
    .b-canvas-empty.dragover { border-color: var(--b-primary); background: var(--b-primary-light); }

    .b-drop-indicator { height: 3px; background: var(--b-primary); margin: 0; transition: all .1s; opacity: 0; }
    .b-drop-indicator.active { opacity: 1; box-shadow: 0 0 8px rgba(79,70,229,.5); }

    /* ── Panel settings ──────────────────────────────────────────── */
    .b-settings-tabs { display: flex; border-bottom: 1px solid var(--b-border); position: sticky; top: 0; background: white; z-index: 1; }
    .b-settings-tab { flex: 1; padding: 10px; border: 0; background: transparent; font-size: 11px; color: var(--b-muted); cursor: pointer; border-bottom: 2px solid transparent; font-weight: 500; text-transform: uppercase; letter-spacing: .04em; }
    .b-settings-tab.active { color: var(--b-primary); border-bottom-color: var(--b-primary); }

    .b-field { margin-bottom: 14px; }
    .b-field label { display: block; font-size: 11px; font-weight: 600; color: var(--b-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
    .b-field input[type=text], .b-field input[type=number], .b-field input[type=email], .b-field input[type=url],
    .b-field select, .b-field textarea {
        width: 100%; padding: 7px 10px; border: 1px solid var(--b-border); border-radius: 5px; font-size: 13px;
        background: white; color: var(--b-text);
    }
    .b-field input:focus, .b-field select:focus, .b-field textarea:focus { outline: 0; border-color: var(--b-primary); box-shadow: 0 0 0 2px rgba(79,70,229,.1); }
    .b-field textarea { font-family: ui-monospace, SFMono-Regular, monospace; font-size: 12px; }

    .b-color-picker { display: flex; gap: 6px; align-items: center; }
    .b-color-picker input[type=color] { width: 32px; height: 32px; padding: 2px; border: 1px solid var(--b-border); border-radius: 5px; cursor: pointer; flex-shrink: 0; }
    .b-color-picker input[type=text] { flex: 1; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 12px; }
    .b-color-presets { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 6px; }
    .b-color-preset { width: 18px; height: 18px; border-radius: 3px; border: 1px solid var(--b-border); cursor: pointer; }
    .b-color-preset:hover { transform: scale(1.15); }

    .b-spacing-box {
        display: grid; grid-template-columns: 40px 1fr 40px; grid-template-rows: 40px 1fr 40px;
        align-items: center; justify-items: center; gap: 4px;
        border: 1px dashed var(--b-border); border-radius: 6px; padding: 4px; background: var(--b-bg);
        margin-bottom: 6px;
    }
    .b-spacing-box input { width: 38px; padding: 4px; text-align: center; font-size: 11px; border: 1px solid var(--b-border); border-radius: 3px; background: white; }
    .b-spacing-box .center { background: white; border: 1px dashed var(--b-border); border-radius: 4px; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 10px; color: var(--b-muted); }

    .b-toggle { display: flex; align-items: center; gap: 8px; cursor: pointer; }
    .b-toggle input { width: 16px; height: 16px; cursor: pointer; }

    .b-image-uploader {
        display: flex; gap: 4px;
    }
    .b-image-uploader input { flex: 1; }
    .b-upload-btn { padding: 6px 10px; background: var(--b-primary-light); color: var(--b-primary); border: 1px solid transparent; border-radius: 5px; cursor: pointer; font-size: 13px; }
    .b-upload-btn:hover { background: var(--b-primary); color: white; }

    .b-empty-settings { padding: 40px 16px; text-align: center; color: var(--b-muted); font-size: 13px; }

    /* ── Toast ───────────────────────────────────────────────────── */
    .b-toast {
        position: fixed; top: 76px; right: 20px; z-index: 99999;
        padding: 10px 16px; border-radius: 6px; color: white; font-size: 13px;
        box-shadow: 0 4px 12px rgba(0,0,0,.15); animation: slideIn .2s ease-out;
    }
    @keyframes slideIn { from { transform: translateX(20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

    /* ── WYSIWYG modal ───────────────────────────────────────────── */
    .b-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 99999; align-items: center; justify-content: center; }
    .b-modal.show { display: flex; }
    .b-modal-card { background: white; width: 90%; max-width: 800px; max-height: 90vh; border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; }
    .b-modal-head { padding: 14px 18px; border-bottom: 1px solid var(--b-border); display: flex; justify-content: space-between; align-items: center; }
    .b-modal-body { flex: 1; overflow: auto; min-height: 400px; }
    .b-modal-foot { padding: 12px 18px; border-top: 1px solid var(--b-border); display: flex; justify-content: flex-end; gap: 8px; }

    /* Esconder layout normal del manager mientras estamos en builder */
    .builder-active .l-side, .builder-active .l-topbar, .builder-active footer { display: none !important; }
</style>
@endpush

@section('content')
<div class="builder-shell" x-data="builder()" x-init="init()" x-cloak>

    {{-- ═══════════════════════════════════════════════════════════
         TOPBAR
         ═══════════════════════════════════════════════════════════ --}}
    <div class="b-topbar">
        <a href="{{ route('manager.campaigns.templates.index') }}" class="b-back">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
            Plantillas
        </a>

        <input type="text" x-model="meta.name" class="b-title-input" placeholder="Nombre de la plantilla">

        <span class="b-saved" :class="{ dirty: dirty, saved: !dirty && lastSaved }" x-show="lastSaved || dirty">
            <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" x-show="!dirty"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
            <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" x-show="dirty"><circle cx="10" cy="10" r="4"/></svg>
            <span x-text="dirty ? 'Cambios sin guardar' : `Guardado · hace ${secondsAgo}s`"></span>
        </span>

        <div class="b-actions">
            <div class="b-device-toggle">
                <button :class="{ active: device === 'desktop' }" @click="device = 'desktop'" title="Desktop">
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" style="vertical-align:middle"><path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/></svg>
                </button>
                <button :class="{ active: device === 'mobile' }" @click="device = 'mobile'" title="Mobile">
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" style="vertical-align:middle"><path d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z"/></svg>
                </button>
            </div>

            <button class="b-btn" @click="promptSendTest()" title="Enviar test">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                Test
            </button>
            <button class="b-btn" @click="openPreviewWindow()" title="Preview">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Preview
            </button>
            <button class="b-btn" @click="openPreviewReal()" :disabled="!selectedListUid" title="Con datos reales">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                Con datos
            </button>
            <button class="b-btn b-btn-primary" @click="save()" :disabled="saving">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" x-show="!saving"><path d="M3 4a2 2 0 012-2h10a2 2 0 012 2v14l-5-2.5L7 18V4z"/></svg>
                <span x-text="saving ? 'Guardando…' : 'Guardar'"></span>
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         LAYOUT 3 COLUMNAS
         ═══════════════════════════════════════════════════════════ --}}
    <div class="b-layout">

        {{-- ── SIDEBAR: TABS BLOQUES / VARIABLES ───────────────── --}}
        <div class="b-panel sidebar">
            <div class="b-tabs">
                <button class="b-tab" :class="{ active: sidebarTab === 'blocks' }" @click="sidebarTab = 'blocks'">Bloques</button>
                <button class="b-tab" :class="{ active: sidebarTab === 'variables' }" @click="sidebarTab = 'variables'">Variables</button>
                <button class="b-tab" :class="{ active: sidebarTab === 'global' }" @click="sidebarTab = 'global'">Global</button>
            </div>

            {{-- Tab Bloques --}}
            <div class="b-section" x-show="sidebarTab === 'blocks'">
                <div class="b-section-title">Arrastra al canvas</div>
                <div class="b-blocks-grid" id="palette">
                    @foreach ($palette as $type => $meta)
                        <div class="b-block-card" data-block-type="{{ $type }}" draggable="true">
                            @switch($type)
                                @case('header')<svg viewBox="0 0 20 20" fill="currentColor"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5z"/><path fill-rule="evenodd" d="M5 11a2 2 0 00-2 2v2a2 2 0 002 2h10a2 2 0 002-2v-2a2 2 0 00-2-2H5z" clip-rule="evenodd"/></svg>@break
                                @case('hero')<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>@break
                                @case('text')<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zm0 4a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zm0 4a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zm0 4a1 1 0 011-1h6a1 1 0 110 2H5a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>@break
                                @case('image')<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>@break
                                @case('video')<svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/></svg>@break
                                @case('button')<svg viewBox="0 0 20 20" fill="currentColor"><rect x="2" y="6" width="16" height="8" rx="2"/></svg>@break
                                @case('columns')<svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 3h6v14H3zM11 3h6v14h-6z"/></svg>@break
                                @case('list')<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 9a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 13a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/><circle cx="2" cy="5" r="0.7"/><circle cx="2" cy="9" r="0.7"/><circle cx="2" cy="13" r="0.7"/></svg>@break
                                @case('quote')<svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 17h3l2-4V7H2v6h3zM12 17h3l2-4V7h-6v6h3z"/></svg>@break
                                @case('spacer')<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7h10v2H5zm0 6v-2h10v2H5z" clip-rule="evenodd"/></svg>@break
                                @case('divider')<svg viewBox="0 0 20 20" fill="currentColor"><rect x="2" y="9" width="16" height="2" rx="1"/></svg>@break
                                @case('social')<svg viewBox="0 0 20 20" fill="currentColor"><path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z"/></svg>@break
                                @case('html')<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>@break
                                @case('footer')<svg viewBox="0 0 20 20" fill="currentColor"><rect x="2" y="14" width="16" height="3" rx="1"/></svg>@break
                                @default<svg viewBox="0 0 20 20" fill="currentColor"><rect x="3" y="3" width="14" height="14" rx="2"/></svg>
                            @endswitch
                            <span>{{ $meta['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Tab Variables --}}
            <div class="b-section" x-show="sidebarTab === 'variables'">
                <div class="b-section-title">Lista de referencia</div>
                <select x-model="selectedListUid" @change="loadVariables()" class="b-field" style="margin-bottom:14px;width:100%;padding:7px 10px;border:1px solid var(--b-border);border-radius:5px;font-size:13px">
                    <option value="">Sin lista (sólo sistema)</option>
                    @foreach (\Modules\Campaign\Models\CampaignMaillist::orderBy('name')->get(['uid','name']) as $l)
                        <option value="{{ $l->uid }}">{{ $l->name }}</option>
                    @endforeach
                </select>

                <p style="font-size:11px;color:var(--b-muted);margin:0 0 10px">Click sobre una variable para insertar en el cursor.</p>

                <template x-for="group in variableGroups" :key="group.key">
                    <div>
                        <div class="b-var-group-title" x-text="group.label"></div>
                        <template x-for="v in group.variables" :key="v.tag">
                            <span class="b-var-pill" @click="insertVar(v.tag)" :title="v.description || v.label" x-text="'{{'+v.tag+'}}'"></span>
                        </template>
                    </div>
                </template>

                <div class="b-var-warn" x-show="undefinedVars.length > 0">
                    ⚠️ Variables sin definir: <strong x-text="undefinedVars.join(', ')"></strong>
                    <div style="margin-top:4px;font-size:10px">Quedarán vacías al enviar.</div>
                </div>
            </div>

            {{-- Tab Global --}}
            <div class="b-section" x-show="sidebarTab === 'global'">
                <div class="b-section-title">Aspecto global</div>

                <div class="b-field">
                    <label>Color de fondo (área)</label>
                    <div class="b-color-picker">
                        <input type="color" x-model="globals.background_color" @change="renderAll()">
                        <input type="text" x-model="globals.background_color" @change="renderAll()">
                    </div>
                </div>

                <div class="b-field">
                    <label>Fondo del email</label>
                    <div class="b-color-picker">
                        <input type="color" x-model="globals.content_background_color" @change="renderAll()">
                        <input type="text" x-model="globals.content_background_color" @change="renderAll()">
                    </div>
                </div>

                <div class="b-field">
                    <label>Color de texto</label>
                    <div class="b-color-picker">
                        <input type="color" x-model="globals.text_color" @change="renderAll()">
                        <input type="text" x-model="globals.text_color" @change="renderAll()">
                    </div>
                </div>

                <div class="b-field">
                    <label>Color de enlaces</label>
                    <div class="b-color-picker">
                        <input type="color" x-model="globals.link_color" @change="renderAll()">
                        <input type="text" x-model="globals.link_color" @change="renderAll()">
                    </div>
                </div>

                <div class="b-field">
                    <label>Ancho del contenido</label>
                    <input type="text" x-model="globals.content_width" @change="renderAll()" placeholder="600px">
                </div>

                <div class="b-field">
                    <label>Familia de fuente</label>
                    <select x-model="globals.font_family" @change="renderAll()">
                        <option value="-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif">System (recomendado)</option>
                        <option value="'Helvetica Neue', Helvetica, Arial, sans-serif">Helvetica</option>
                        <option value="Georgia, 'Times New Roman', serif">Georgia (serif)</option>
                        <option value="'Courier New', monospace">Courier (monospace)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- ── CANVAS CENTRAL ────────────────────────────────────── --}}
        <div class="b-canvas">
            <div class="b-canvas-frame" :class="{ mobile: device === 'mobile' }">
                <div class="b-subject-row">
                    <input type="text" x-model="meta.subject" placeholder="Asunto del email…">
                </div>

                <div class="b-blocks-canvas" id="canvas-blocks">
                    <template x-for="(block, idx) in blocks" :key="idx">
                        <div>
                            <div class="b-drop-indicator" :class="{ active: dropIndex === idx }"></div>
                            <div class="b-canvas-block" :class="{ selected: selectedIndex === idx }" @click.stop="selectBlock(idx)" :data-idx="idx">
                                <div class="b-block-toolbar">
                                    <span class="b-block-toolbar-tag" x-text="block.type"></span>
                                    <button @click.stop="moveBlock(idx, -1)" :disabled="idx === 0" title="Subir">↑</button>
                                    <button @click.stop="moveBlock(idx, 1)" :disabled="idx === blocks.length - 1" title="Bajar">↓</button>
                                    <button @click.stop="duplicateBlock(idx)" title="Duplicar">⧉</button>
                                    <button @click.stop="removeBlock(idx)" title="Eliminar">×</button>
                                </div>
                                <div x-html="renderedBlocks[idx] ?? '<div style=\'padding:20px;color:#999;text-align:center\'>...</div>'"></div>
                            </div>
                        </div>
                    </template>

                    <div class="b-canvas-empty" x-show="blocks.length === 0" :class="{ dragover: canvasDragging }">
                        Arrastra un bloque aquí desde el panel izquierdo<br>
                        <small style="opacity:.7">o elige uno de los <a href="{{ route('manager.campaigns.templates.gallery') }}">presets</a></small>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── PANEL SETTINGS ────────────────────────────────────── --}}
        <div class="b-panel settings">
            <template x-if="selectedIndex === null">
                <div class="b-empty-settings">
                    <svg width="48" height="48" viewBox="0 0 20 20" fill="#d1d5db" style="margin-bottom:12px"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <div>Click sobre un bloque para editarlo</div>
                </div>
            </template>

            <template x-if="selectedIndex !== null">
                <div>
                    <div class="b-settings-tabs">
                        <button class="b-settings-tab" :class="{ active: settingsTab === 'content' }" @click="settingsTab = 'content'">Contenido</button>
                        <button class="b-settings-tab" :class="{ active: settingsTab === 'design' }" @click="settingsTab = 'design'">Diseño</button>
                    </div>

                    {{-- ── Tab Contenido (específico por bloque) ─────── --}}
                    <div class="b-section" x-show="settingsTab === 'content'">
                        <template x-if="['header','hero'].includes(blocks[selectedIndex]?.type)">
                            <div>
                                <div class="b-field"><label>Título</label><input type="text" x-model="blocks[selectedIndex].content.title" @input.debounce.300ms="renderBlock(selectedIndex)"></div>
                                <div class="b-field"><label>Subtítulo</label><input type="text" x-model="blocks[selectedIndex].content.subtitle" @input.debounce.300ms="renderBlock(selectedIndex)"></div>
                            </div>
                        </template>

                        <template x-if="blocks[selectedIndex]?.type === 'hero'">
                            <div>
                                <div class="b-field"><label>Imagen</label>
                                    <div class="b-image-uploader">
                                        <input type="text" x-model="blocks[selectedIndex].content.image_url" @input.debounce.300ms="renderBlock(selectedIndex)">
                                        <button type="button" @click="uploadImage('image_url')" class="b-upload-btn">📤</button>
                                    </div>
                                </div>
                                <div class="b-field"><label>Texto botón</label><input type="text" x-model="blocks[selectedIndex].content.button_text" @input.debounce.300ms="renderBlock(selectedIndex)"></div>
                                <div class="b-field"><label>URL botón</label><input type="text" x-model="blocks[selectedIndex].content.button_url" @input.debounce.300ms="renderBlock(selectedIndex)"></div>
                            </div>
                        </template>

                        <template x-if="['text','footer'].includes(blocks[selectedIndex]?.type)">
                            <div class="b-field">
                                <label style="display:flex;justify-content:space-between;align-items:center">
                                    <span x-text="blocks[selectedIndex].type === 'footer' ? 'Texto' : 'Contenido'"></span>
                                    <button type="button" class="b-upload-btn" @click="openWysiwyg(blocks[selectedIndex].type === 'footer' ? 'text' : 'html')">✏️ Editor visual</button>
                                </label>
                                <textarea rows="10" x-model="blocks[selectedIndex].content[blocks[selectedIndex].type === 'footer' ? 'text' : 'html']" @input.debounce.500ms="renderBlock(selectedIndex)"></textarea>
                            </div>
                        </template>

                        <template x-if="blocks[selectedIndex]?.type === 'image'">
                            <div>
                                <div class="b-field"><label>Imagen</label>
                                    <div class="b-image-uploader">
                                        <input type="text" x-model="blocks[selectedIndex].content.url" @input.debounce.300ms="renderBlock(selectedIndex)">
                                        <button type="button" @click="uploadImage('url')" class="b-upload-btn">📤</button>
                                    </div>
                                </div>
                                <div class="b-field"><label>Alt text</label><input type="text" x-model="blocks[selectedIndex].content.alt" @input.debounce.300ms="renderBlock(selectedIndex)"></div>
                                <div class="b-field"><label>Link al click</label><input type="text" x-model="blocks[selectedIndex].content.link" @input.debounce.300ms="renderBlock(selectedIndex)"></div>
                            </div>
                        </template>

                        <template x-if="blocks[selectedIndex]?.type === 'button'">
                            <div>
                                <div class="b-field"><label>Texto</label><input type="text" x-model="blocks[selectedIndex].content.text" @input.debounce.300ms="renderBlock(selectedIndex)"></div>
                                <div class="b-field"><label>URL destino</label><input type="text" x-model="blocks[selectedIndex].content.url" @input.debounce.300ms="renderBlock(selectedIndex)"></div>
                            </div>
                        </template>

                        <template x-if="blocks[selectedIndex]?.type === 'columns'">
                            <div>
                                <template x-for="(col, ci) in blocks[selectedIndex].content.columns" :key="ci">
                                    <div class="b-field">
                                        <label>Columna <span x-text="ci+1"></span></label>
                                        <textarea rows="4" x-model="col.html" @input.debounce.500ms="renderBlock(selectedIndex)"></textarea>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="blocks[selectedIndex]?.type === 'video'">
                            <div>
                                <div class="b-field"><label>URL del video</label><input type="text" x-model="blocks[selectedIndex].content.video_url" @input.debounce.300ms="renderBlock(selectedIndex)" placeholder="https://youtube.com/watch?v=…"></div>
                                <div class="b-field"><label>Thumbnail</label>
                                    <div class="b-image-uploader">
                                        <input type="text" x-model="blocks[selectedIndex].content.thumbnail_url" @input.debounce.300ms="renderBlock(selectedIndex)">
                                        <button type="button" @click="uploadImage('thumbnail_url')" class="b-upload-btn">📤</button>
                                    </div>
                                </div>
                                <label class="b-toggle b-field"><input type="checkbox" x-model="blocks[selectedIndex].content.play_overlay" @change="renderBlock(selectedIndex)">Mostrar botón ▶ sobre thumbnail</label>
                            </div>
                        </template>

                        <template x-if="blocks[selectedIndex]?.type === 'quote'">
                            <div>
                                <div class="b-field"><label>Cita</label><textarea rows="4" x-model="blocks[selectedIndex].content.text" @input.debounce.500ms="renderBlock(selectedIndex)"></textarea></div>
                                <div class="b-field"><label>Autor</label><input type="text" x-model="blocks[selectedIndex].content.author" @input.debounce.300ms="renderBlock(selectedIndex)"></div>
                            </div>
                        </template>

                        <template x-if="blocks[selectedIndex]?.type === 'list'">
                            <div>
                                <div class="b-field"><label>Tipo</label>
                                    <select x-model="blocks[selectedIndex].settings.list_type" @change="renderBlock(selectedIndex)">
                                        <option value="ul">• Viñetas</option>
                                        <option value="ol">1. Numerada</option>
                                    </select>
                                </div>
                                <div class="b-field"><label>Ítems</label>
                                    <template x-for="(item, ii) in blocks[selectedIndex].content.items" :key="ii">
                                        <div style="display:flex;gap:4px;margin-bottom:4px">
                                            <input type="text" x-model="blocks[selectedIndex].content.items[ii]" @input.debounce.500ms="renderBlock(selectedIndex)">
                                            <button type="button" @click="blocks[selectedIndex].content.items.splice(ii, 1); renderBlock(selectedIndex)" style="border:0;background:transparent;color:var(--b-danger);cursor:pointer">×</button>
                                        </div>
                                    </template>
                                    <button type="button" @click="blocks[selectedIndex].content.items.push('Nuevo ítem'); renderBlock(selectedIndex)" class="b-upload-btn" style="margin-top:6px">+ Ítem</button>
                                </div>
                            </div>
                        </template>

                        <template x-if="blocks[selectedIndex]?.type === 'social'">
                            <template x-for="(net, ni) in blocks[selectedIndex].content.networks" :key="ni">
                                <div class="b-field">
                                    <label x-text="net.name"></label>
                                    <input type="text" x-model="net.url" @input.debounce.300ms="renderBlock(selectedIndex)" placeholder="https://">
                                </div>
                            </template>
                        </template>

                        <template x-if="blocks[selectedIndex]?.type === 'html'">
                            <div class="b-field">
                                <label>HTML personalizado <span style="color:var(--b-warning)">⚠ sin sanitizar</span></label>
                                <textarea rows="14" x-model="blocks[selectedIndex].content.html" @input.debounce.500ms="renderBlock(selectedIndex)"></textarea>
                            </div>
                        </template>

                        <template x-if="['spacer','divider'].includes(blocks[selectedIndex]?.type)">
                            <div class="b-empty-settings" style="padding:20px">
                                Este bloque no tiene contenido.<br>Usa la pestaña <strong>Diseño</strong>.
                            </div>
                        </template>
                    </div>

                    {{-- ── Tab Diseño (settings comunes) ───────────────── --}}
                    <div class="b-section" x-show="settingsTab === 'design'">
                        <div class="b-field" x-show="hasSettings('background_color')">
                            <label>Fondo</label>
                            <div class="b-color-picker">
                                <input type="color" x-model="blocks[selectedIndex].settings.background_color" @change="renderBlock(selectedIndex)">
                                <input type="text" x-model="blocks[selectedIndex].settings.background_color" @input.debounce.300ms="renderBlock(selectedIndex)">
                            </div>
                            <div class="b-color-presets">
                                <template x-for="c in ['#ffffff','#f9fafb','#0d6efd','#22c55e','#ef4444','#f59e0b','#8b5cf6','#0f172a']" :key="c">
                                    <div class="b-color-preset" :style="`background:${c}`" @click="blocks[selectedIndex].settings.background_color = c; renderBlock(selectedIndex)"></div>
                                </template>
                            </div>
                        </div>

                        <div class="b-field" x-show="hasSettings('text_color')">
                            <label>Texto</label>
                            <div class="b-color-picker">
                                <input type="color" x-model="blocks[selectedIndex].settings.text_color" @change="renderBlock(selectedIndex)">
                                <input type="text" x-model="blocks[selectedIndex].settings.text_color" @input.debounce.300ms="renderBlock(selectedIndex)">
                            </div>
                        </div>

                        <div class="b-field" x-show="hasSettings('align')">
                            <label>Alineación</label>
                            <select x-model="blocks[selectedIndex].settings.align" @change="renderBlock(selectedIndex)">
                                <option value="left">⊟ Izquierda</option>
                                <option value="center">⊟ Centro</option>
                                <option value="right">⊟ Derecha</option>
                            </select>
                        </div>

                        <div class="b-field" x-show="hasSettings('font_size')">
                            <label>Tamaño fuente</label>
                            <input type="text" x-model="blocks[selectedIndex].settings.font_size" @input.debounce.300ms="renderBlock(selectedIndex)" placeholder="16px">
                        </div>

                        <div class="b-field" x-show="hasSettings('border_radius')">
                            <label>Border radius</label>
                            <input type="text" x-model="blocks[selectedIndex].settings.border_radius" @input.debounce.300ms="renderBlock(selectedIndex)" placeholder="6px">
                        </div>

                        <div class="b-field" x-show="hasSettings('height')">
                            <label>Altura</label>
                            <input type="text" x-model="blocks[selectedIndex].settings.height" @input.debounce.300ms="renderBlock(selectedIndex)" placeholder="32px">
                        </div>

                        <div class="b-field" x-show="hasSettings('padding')">
                            <label style="display:flex;justify-content:space-between;align-items:center">
                                <span>Padding</span>
                                <label style="font-weight:400;text-transform:none;font-size:11px;color:var(--b-muted);display:flex;gap:4px;align-items:center;cursor:pointer">
                                    <input type="checkbox" x-model="paddingLock" style="width:12px;height:12px"> 🔗 Igualar
                                </label>
                            </label>
                            <div class="b-spacing-box">
                                <div></div>
                                <input type="text" :value="getPad(0)" @input="setPad(0, $event.target.value)" placeholder="20px">
                                <div></div>
                                <input type="text" :value="getPad(3)" @input="setPad(3, $event.target.value)" placeholder="30px">
                                <div class="center" x-text="(blocks[selectedIndex].settings.padding||'').split(' ').slice(0,2).join(' ') || '—'"></div>
                                <input type="text" :value="getPad(1)" @input="setPad(1, $event.target.value)" placeholder="30px">
                                <div></div>
                                <input type="text" :value="getPad(2)" @input="setPad(2, $event.target.value)" placeholder="20px">
                                <div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<input type="hidden" id="csrf-token" value="{{ csrf_token() }}">

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>

{{-- Modal WYSIWYG --}}
<div id="wysiwyg-modal" class="b-modal">
    <div class="b-modal-card">
        <div class="b-modal-head">
            <h5 style="margin:0;font-size:14px">Editor visual</h5>
            <button onclick="closeWysiwyg()" style="border:0;background:none;font-size:20px;cursor:pointer;color:var(--b-muted)">×</button>
        </div>
        <div class="b-modal-body" id="quill-container"></div>
        <div class="b-modal-foot">
            <button onclick="closeWysiwyg()" class="b-btn">Cancelar</button>
            <button onclick="saveWysiwyg()" class="b-btn b-btn-primary">Aplicar</button>
        </div>
    </div>
</div>

<script>
let quillInstance = null;
function openWysiwygModal(initialHtml, callback) {
    document.getElementById('wysiwyg-modal').classList.add('show');
    if (! quillInstance) {
        quillInstance = new Quill('#quill-container', {
            theme: 'snow',
            modules: { toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ color: [] }, { background: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ align: [] }],
                ['link', 'clean'],
            ] },
        });
    }
    quillInstance.root.innerHTML = initialHtml || '';
    window._quillCallback = callback;
}
function closeWysiwyg() { document.getElementById('wysiwyg-modal').classList.remove('show'); }
function saveWysiwyg() {
    if (window._quillCallback && quillInstance) window._quillCallback(quillInstance.root.innerHTML);
    closeWysiwyg();
}

const TEMPLATE_UID = @json($template->uid);
const ROUTES = {
    save: @json(route('manager.campaigns.templates.builder.save', $template->uid)),
    renderBlock: @json(route('manager.campaigns.templates.builder.render-block')),
    blockBlank: (type) => `/panel/campaign/templates/builder/blocks/blank/${type}`,
    preview: @json(route('manager.campaigns.templates.builder.preview', $template->uid)),
    sendTest: @json(route('manager.campaigns.templates.builder.send-test', $template->uid)),
    uploadImage: @json(route('manager.campaigns.templates.builder.upload-image')),
    variables: @json(route('manager.campaigns.templates.builder.variables')),
    previewReal: @json(route('manager.campaigns.templates.builder.preview-real', $template->uid)),
};
const INITIAL_BLOCKS = @json($blocks);
const INITIAL_GLOBALS = @json($globals);
const INITIAL_META = { name: @json($template->name), subject: @json($template->subject) };

function builder() {
    return {
        // State
        blocks: INITIAL_BLOCKS && INITIAL_BLOCKS.length ? INITIAL_BLOCKS : [],
        globals: Object.assign({
            content_width: '600px',
            background_color: '#f4f4f7',
            content_background_color: '#ffffff',
            text_color: '#333333',
            link_color: '#0d6efd',
            font_family: "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
        }, INITIAL_GLOBALS || {}),
        meta: INITIAL_META,
        renderedBlocks: {},
        selectedIndex: null,
        device: 'desktop',
        sidebarTab: 'blocks',
        settingsTab: 'content',
        saving: false,
        dirty: false,
        lastSaved: null,
        secondsAgo: 0,
        autosaveTimer: null,
        canvasDragging: false,
        dropIndex: null,
        paddingLock: false,
        // Variables
        selectedListUid: '',
        variableGroups: [],
        undefinedVars: [],
        lastFocusedField: null,
        lastFocusedCursorPos: 0,

        init() {
            document.body.classList.add('builder-active');
            this.renderAll();
            this.loadVariables();

            // Drag desde paleta al canvas
            document.querySelectorAll('.b-block-card').forEach(el => {
                el.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('block-type', el.dataset.blockType);
                    e.dataTransfer.effectAllowed = 'copy';
                });
            });

            const canvas = document.getElementById('canvas-blocks');
            canvas.addEventListener('dragenter', () => { this.canvasDragging = true; });
            canvas.addEventListener('dragleave', (e) => { if (e.target === canvas) this.canvasDragging = false; });
            canvas.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect = 'copy'; });
            canvas.addEventListener('drop', async (e) => {
                e.preventDefault();
                this.canvasDragging = false;
                const type = e.dataTransfer.getData('block-type');
                if (!type) return;
                const block = await this.fetchBlankBlock(type);
                this.blocks.push(block);
                await this.renderBlock(this.blocks.length - 1);
                this.selectBlock(this.blocks.length - 1);
            });

            Sortable.create(canvas, {
                animation: 150,
                handle: '.b-canvas-block',
                filter: '.b-canvas-empty, .b-block-toolbar, .b-block-toolbar *',
                ghostClass: 'b-sortable-ghost',
                onEnd: (evt) => {
                    if (evt.oldIndex === evt.newIndex) return;
                    const moved = this.blocks.splice(evt.oldIndex, 1)[0];
                    this.blocks.splice(evt.newIndex, 0, moved);
                    this.selectedIndex = evt.newIndex;
                    this.renderAll();
                },
            });

            document.addEventListener('click', () => { this.selectedIndex = null; });

            // Auto-save
            this.autosaveTimer = setInterval(() => {
                if (this.dirty && !this.saving) this.save(true);
                if (this.lastSaved) this.secondsAgo = Math.floor((Date.now() - this.lastSaved) / 1000);
            }, 1000);

            this.$watch('blocks', () => { this.dirty = true; this.detectUndefinedVars(); }, { deep: true });
            this.$watch('globals', () => { this.dirty = true; }, { deep: true });
            this.$watch('meta', () => { this.dirty = true; }, { deep: true });

            window.addEventListener('beforeunload', (e) => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } });

            // Tracking foco para insertar variables
            document.querySelector('.b-panel.settings').addEventListener('focusin', (e) => {
                if (e.target.matches('input[type=text], input[type=email], input[type=url], textarea')) {
                    this.lastFocusedField = e.target;
                    this.lastFocusedCursorPos = e.target.selectionStart ?? 0;
                }
            });
            document.querySelector('.b-panel.settings').addEventListener('keyup', (e) => {
                if (e.target === this.lastFocusedField) this.lastFocusedCursorPos = e.target.selectionStart ?? 0;
            });
        },

        async fetchBlankBlock(type) {
            const r = await fetch(ROUTES.blockBlank(type));
            return await r.json();
        },
        async renderBlock(idx) {
            const block = this.blocks[idx]; if (!block) return;
            const r = await fetch(ROUTES.renderBlock, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.getElementById('csrf-token').value },
                body: JSON.stringify(block),
            });
            this.renderedBlocks[idx] = await r.text();
        },
        async renderAll() { for (let i = 0; i < this.blocks.length; i++) await this.renderBlock(i); },
        selectBlock(idx) { this.selectedIndex = idx; this.settingsTab = 'content'; },
        removeBlock(idx) { if (!confirm('¿Eliminar este bloque?')) return; this.blocks.splice(idx, 1); this.selectedIndex = null; this.renderAll(); },
        duplicateBlock(idx) { const c = JSON.parse(JSON.stringify(this.blocks[idx])); this.blocks.splice(idx + 1, 0, c); this.renderAll(); },
        moveBlock(idx, dir) {
            const n = idx + dir; if (n < 0 || n >= this.blocks.length) return;
            const m = this.blocks.splice(idx, 1)[0]; this.blocks.splice(n, 0, m);
            this.selectedIndex = n; this.renderAll();
        },
        hasSettings(key) { return this.blocks[this.selectedIndex]?.settings?.[key] !== undefined; },

        // Padding box: parsea "20px 30px 20px 30px" → array de 4 valores.
        // Soporta CSS shorthand de 1, 2, 3 y 4 valores.
        getPad(side) {
            const padStr = this.blocks[this.selectedIndex]?.settings?.padding || '0';
            const parts = padStr.trim().split(/\s+/);
            // CSS shorthand: 1 valor = todos, 2 = vert/horiz, 3 = top/horiz/bottom, 4 = top/right/bottom/left
            const expand = {
                1: [parts[0], parts[0], parts[0], parts[0]],
                2: [parts[0], parts[1], parts[0], parts[1]],
                3: [parts[0], parts[1], parts[2], parts[1]],
                4: parts,
            };
            return (expand[parts.length] || ['0', '0', '0', '0'])[side] || '0';
        },
        setPad(side, value) {
            const sides = [this.getPad(0), this.getPad(1), this.getPad(2), this.getPad(3)];
            if (this.paddingLock) {
                sides[0] = sides[1] = sides[2] = sides[3] = value;
            } else {
                sides[side] = value;
            }
            // Si los 4 son iguales, escribe shorthand de 1 valor
            const allEqual = sides.every(s => s === sides[0]);
            const vertHorizEqual = sides[0] === sides[2] && sides[1] === sides[3];
            const value2 = allEqual ? sides[0] : (vertHorizEqual ? `${sides[0]} ${sides[1]}` : sides.join(' '));
            this.blocks[this.selectedIndex].settings.padding = value2;
            this.renderBlock(this.selectedIndex);
        },

        async loadVariables() {
            const url = ROUTES.variables + (this.selectedListUid ? '?list_uid=' + this.selectedListUid : '');
            try {
                const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await r.json();
                this.variableGroups = data.groups || [];
                this.detectUndefinedVars();
            } catch { this.variableGroups = []; }
        },
        insertVar(tag) {
            const placeholder = '{{' + tag + '}}';
            const field = this.lastFocusedField;
            if (!field) {
                navigator.clipboard.writeText(placeholder);
                this.toast('Variable copiada. Haz click en un campo para insertar directamente.', 'info'); return;
            }
            const pos = this.lastFocusedCursorPos, value = field.value;
            field.value = value.slice(0, pos) + placeholder + value.slice(pos);
            field.focus(); field.selectionStart = field.selectionEnd = pos + placeholder.length;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            this.lastFocusedCursorPos = pos + placeholder.length;
        },
        detectUndefinedVars() {
            const defined = new Set();
            this.variableGroups.forEach(g => g.variables.forEach(v => defined.add(v.tag)));
            const found = new Set();
            const scan = (s) => { if (typeof s !== 'string') return; const m = s.matchAll(/\{\{?\s*([A-Z][A-Z0-9_]*)\s*\}\}?/g); for (const x of m) found.add(x[1]); };
            this.blocks.forEach(b => Object.values(b.content || {}).forEach(v => {
                if (typeof v === 'string') scan(v);
                else if (Array.isArray(v)) v.forEach(item => typeof item === 'string' ? scan(item) : (item && Object.values(item).forEach(x => scan(x))));
            }));
            this.undefinedVars = [...found].filter(t => !defined.has(t));
        },

        async save(silent = false) {
            this.saving = true;
            try {
                const r = await fetch(ROUTES.save, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.getElementById('csrf-token').value },
                    body: JSON.stringify({ name: this.meta.name, subject: this.meta.subject, blocks: this.blocks, global_settings: this.globals }),
                });
                if (r.ok) { this.dirty = false; this.lastSaved = Date.now(); this.secondsAgo = 0; if (!silent) this.toast('Guardado'); }
                else this.toast('Error al guardar', 'error');
            } finally { this.saving = false; }
        },

        async promptSendTest() {
            const to = prompt('Email de prueba:'); if (!to) return;
            try {
                const r = await fetch(ROUTES.sendTest, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.getElementById('csrf-token').value },
                    body: JSON.stringify({ to, blocks: this.blocks, global_settings: this.globals }),
                });
                const d = await r.json();
                this.toast(d.message, d.ok ? 'success' : 'error');
            } catch (e) { this.toast('Error: ' + e.message, 'error'); }
        },

        uploadImage(field) {
            const i = document.createElement('input'); i.type = 'file'; i.accept = 'image/*';
            i.onchange = async () => {
                if (!i.files[0]) return;
                const fd = new FormData(); fd.append('image', i.files[0]);
                try {
                    const r = await fetch(ROUTES.uploadImage, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.getElementById('csrf-token').value },
                        body: fd,
                    });
                    const d = await r.json();
                    if (d.ok) {
                        this.blocks[this.selectedIndex].content[field] = d.url;
                        this.renderBlock(this.selectedIndex);
                        this.toast('Imagen subida');
                    } else this.toast('Error subiendo', 'error');
                } catch (e) { this.toast('Error: ' + e.message, 'error'); }
            };
            i.click();
        },

        openWysiwyg(field) {
            const idx = this.selectedIndex; if (idx === null) return;
            openWysiwygModal(this.blocks[idx].content[field] || '', (html) => {
                this.blocks[idx].content[field] = html; this.renderBlock(idx);
            });
        },

        async openPreviewWindow() {
            const r = await fetch(ROUTES.preview, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.getElementById('csrf-token').value },
                body: JSON.stringify({ blocks: this.blocks, global_settings: this.globals }),
            });
            const html = await r.text();
            const w = window.open('', '_blank', 'width=720,height=900');
            w.document.write(html); w.document.close();
        },

        async openPreviewReal() {
            if (!this.selectedListUid) { this.toast('Selecciona una lista en Variables.', 'info'); return; }
            const r = await fetch(ROUTES.previewReal, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.getElementById('csrf-token').value },
                body: JSON.stringify({ blocks: this.blocks, global_settings: this.globals, list_uid: this.selectedListUid }),
            });
            const html = await r.text();
            const w = window.open('', '_blank', 'width=720,height=900');
            w.document.write(html); w.document.close();
        },

        toast(message, type = 'success') {
            const colors = { success: '#10b981', error: '#ef4444', info: '#3b82f6' };
            const el = document.createElement('div');
            el.className = 'b-toast'; el.style.background = colors[type] || colors.success;
            el.textContent = message;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3000);
        },
    };
}
</script>
@endsection
