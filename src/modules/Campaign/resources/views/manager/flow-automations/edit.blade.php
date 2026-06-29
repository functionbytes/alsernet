@extends('campaign::refactor.layout')

@section('title', $automation->name . ' — Flow Editor')

@php $cssVer = $cssVer ?? '1'; @endphp

@section('head')
{{-- Pre-paint FOUC guard: read fullscreen + canvas-sidebar-collapsed flags
     from localStorage and apply them to <html> BEFORE first paint. <body>
     doesn't exist yet at head-script time so the class lives on <html>. --}}
<script>
    (function () {
        try {
            if (window.innerWidth > 1199 && localStorage.getItem('flow-edit-fullscreen') === 'true') {
                document.documentElement.classList.add('flow-edit-fullscreen');
            }
            if (localStorage.getItem('flow-edit-canvas-sidebar-collapsed') === 'true') {
                document.documentElement.classList.add('flow-edit-canvas-sidebar-collapsed');
            }
        } catch (_) { /* localStorage blocked — fail silent */ }
    })();
</script>
<link href="{{ asset('refactor/css/flow-lib.css') }}?v={{ $cssVer }}" rel="stylesheet">
<style>
    /* Full-bleed canvas — break out of .mc-content padding and .mc-content-inner max-width
       so the gray flow surface fills the entire body region (right edge → sidebar edge). */
    .mc-content:has(.flow-edit-page) { padding: 0; }
    .mc-content:has(.flow-edit-page) .mc-content-inner { max-width: none; margin: 0; }

    .flow-edit-page {
        position: relative;
        height: calc(100vh - var(--mc-topbar-h, 56px));
        background: #C4CCD6;
    }
    /* Topbar — identity row + automation-level actions (Pause/Activate,
       Subscribers). The zoom dock is the only thing that floats (clean
       canvas surface), since zoom is a viewport tool, not an automation
       action. */
    .flow-edit-topbar {
        position: absolute; top: 0; left: 0; right: 0; height: 56px;
        display: flex; align-items: center; gap: 12px; padding: 0 20px;
        background: white; border-bottom: 1px solid #E5E7EB; z-index: 10;
    }
    .flow-edit-topbar .back {
        display: inline-flex; align-items: center; gap: 4px;
        color: #475569; text-decoration: none; font-size: 13px;
    }
    .flow-edit-topbar .back:hover { color: #0A0A0A; }
    .flow-edit-topbar h1 { margin: 0; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .flow-edit-topbar .grow { flex: 1; }
    .flow-edit-topbar .badge {
        font-size: 10px; padding: 2px 8px; background: #FEE2E2; color: #991B1B;
        border-radius: 999px; font-weight: 600; letter-spacing: 0.5px;
    }
    .flow-edit-topbar .badge.badge-active { background: #D1FAE5; color: #065F46; }
    /* Topbar action button — same family as floating zoom tool but with a
       visible 1px border to read against the white topbar. */
    .flow-edit-topbar .action {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px;
        background: white;
        border: 1px solid #E5E7EB; border-radius: 8px;
        font: inherit; font-size: 13px; font-weight: 500;
        color: #334155; text-decoration: none;
        cursor: pointer;
        transition: border-color 120ms ease, color 120ms ease;
    }
    .flow-edit-topbar .action:hover { border-color: #0A0A0A; color: #0A0A0A; text-decoration: none; }
    .flow-edit-topbar .action .material-symbols-rounded {
        font-size: 16px;
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    .flow-edit-stage { position: absolute; top: 56px; left: 0; right: 0; bottom: 0; }

    /* Shared chrome for floating panels (legend + zoom dock) — same white pill
       so the two onboarding/viewport overlays read as one family. */
    .flow-edit-float {
        position: absolute; z-index: 5;
        background: white;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }
    .flow-edit-legend {
        bottom: 16px; left: 16px;
        padding: 10px 14px;
        font-size: 11px; color: #6B7280;
    }
    /* Bottom-right zoom dock — compact icon-only triplet. */
    .flow-edit-zoom {
        bottom: 16px; right: 16px;
        display: inline-flex; align-items: stretch;
        padding: 4px;
        gap: 2px;
    }
    .flow-edit-zoom .zoom-tool {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px;
        background: transparent;
        border: none; border-radius: 7px;
        color: #334155; cursor: pointer;
        transition: background 120ms ease, color 120ms ease;
    }
    .flow-edit-zoom .zoom-tool:hover { background: #F1F5F9; color: #0A0A0A; }
    .flow-edit-zoom .zoom-tool .material-symbols-rounded {
        font-size: 18px;
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    /* ── Fullscreen mode ─────────────────────────────────────────────────
       Toggled client-side by adding `.flow-edit-fullscreen` to <html>.
       Hides every chrome surface + the in-page topbar; reveals the
       new automation sidebar (.flow-edit-canvas-sidebar) at the left edge. */
    html.flow-edit-fullscreen .mc-sidebar,
    html.flow-edit-fullscreen .mc-topbar,
    html.flow-edit-fullscreen .login-as-bar,
    html.flow-edit-fullscreen .system-alert-bar,
    html.flow-edit-fullscreen .flow-edit-topbar { display: none !important; }
    html.flow-edit-fullscreen .mc-app-body { margin-left: 0 !important; }
    html.flow-edit-fullscreen .flow-edit-page { height: 100vh; padding-left: 240px; }
    html.flow-edit-fullscreen .flow-edit-stage { top: 0; }
    html.flow-edit-fullscreen.flow-edit-canvas-sidebar-collapsed .flow-edit-page { padding-left: 60px; }

    /* The new automation sidebar — always rendered in DOM but only shown in
       fullscreen mode. Width matches the main sidebar pattern
       (240 expanded / 60 collapsed) for visual continuity. */
    .flow-edit-canvas-sidebar { display: none; }
    html.flow-edit-fullscreen .flow-edit-canvas-sidebar {
        display: flex; flex-direction: column;
        position: fixed; top: 0; left: 0; bottom: 0;
        width: 240px;
        background: white;
        border-right: 1px solid #E5E7EB;
        z-index: 200;
        transition: width 200ms cubic-bezier(0.32, 0.72, 0, 1);
    }
    html.flow-edit-fullscreen.flow-edit-canvas-sidebar-collapsed .flow-edit-canvas-sidebar { width: 60px; }
    .flow-edit-canvas-sidebar .csb-header {
        padding: 14px 16px;
        border-bottom: 1px solid #E5E7EB;
        display: flex; flex-direction: column; gap: 4px;
        min-height: 56px;
    }
    .flow-edit-canvas-sidebar .csb-name {
        font-size: 14px; font-weight: 600; color: #0A0A0A;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .flow-edit-canvas-sidebar .csb-status {
        font-size: 9px; padding: 2px 8px; background: #FEE2E2; color: #991B1B;
        border-radius: 999px; font-weight: 600; letter-spacing: 0.5px;
        align-self: flex-start;
    }
    .flow-edit-canvas-sidebar .csb-status.csb-status-active { background: #D1FAE5; color: #065F46; }
    html.flow-edit-fullscreen.flow-edit-canvas-sidebar-collapsed .csb-name,
    html.flow-edit-fullscreen.flow-edit-canvas-sidebar-collapsed .csb-status { display: none; }
    /* Collapsed: keep a colored dot to convey status without taking space */
    html.flow-edit-fullscreen.flow-edit-canvas-sidebar-collapsed .csb-header {
        padding: 14px 0;
        align-items: center;
    }
    html.flow-edit-fullscreen.flow-edit-canvas-sidebar-collapsed .csb-header::after {
        content: ''; width: 8px; height: 8px; border-radius: 50%;
        background: var(--csb-status-dot, #B91C1C);
    }
    html.flow-edit-fullscreen .flow-edit-canvas-sidebar.is-active .csb-header { --csb-status-dot: #10B981; }

    .flow-edit-canvas-sidebar nav { padding: 8px; display: flex; flex-direction: column; gap: 2px; }
    .flow-edit-canvas-sidebar .csb-divider { height: 1px; background: #E5E7EB; margin: 6px 8px; }
    .flow-edit-canvas-sidebar .csb-item {
        display: flex; align-items: center; gap: 12px;
        padding: 9px 10px;
        background: transparent;
        border: none; border-radius: 8px;
        font: inherit; font-size: 13px; font-weight: 500;
        color: #334155; text-decoration: none;
        cursor: pointer;
        transition: background 120ms ease, color 120ms ease;
        text-align: left;
        width: 100%;
    }
    .flow-edit-canvas-sidebar .csb-item:hover { background: #F1F5F9; color: #0A0A0A; text-decoration: none; }
    /* mc-icon partial — thin SVG strokes, matches the main app sidebar nav. */
    .flow-edit-canvas-sidebar .csb-item-icon {
        width: 20px; height: 20px;
        flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .flow-edit-canvas-sidebar .csb-item-icon .mc-icon,
    .flow-edit-canvas-sidebar .csb-item-icon svg { width: 20px; height: 20px; }
    .flow-edit-canvas-sidebar .csb-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    html.flow-edit-fullscreen.flow-edit-canvas-sidebar-collapsed .csb-item { justify-content: center; padding: 9px 0; }
    html.flow-edit-fullscreen.flow-edit-canvas-sidebar-collapsed .csb-item .csb-label { display: none; }
    /* Subtle teal accent on the Exit button so the way back reads at a glance. */
    .flow-edit-canvas-sidebar .csb-item-exit { color: #0F766E; }
    .flow-edit-canvas-sidebar .csb-item-exit:hover { background: #D1FAE5; color: #134E4A; }
    .flow-edit-canvas-sidebar .csb-footer { margin-top: auto; border-top: 1px solid #E5E7EB; padding: 8px; }
</style>
@endsection

@section('content')
{{-- Always-rendered automation sidebar — only visible when <html> has the
     `flow-edit-fullscreen` class. Same actions as the topbar above; both
     sets of buttons share data-act attributes and a global click-delegation
     handler so they stay in sync. --}}
<aside class="flow-edit-canvas-sidebar {{ $automation->isActive() ? 'is-active' : '' }}" data-canvas-sidebar>
    <div class="csb-header">
        <span class="csb-name">{{ $automation->name }}</span>
        <span class="csb-status {{ $automation->isActive() ? 'csb-status-active' : '' }}" data-status-badge>
            {{ $automation->isActive() ? 'ACTIVE' : 'PAUSED' }}
        </span>
    </div>
    <nav>
        {{-- Top: prominent way back to normal view (Esc also exits). --}}
        <button type="button" class="csb-item csb-item-exit" data-act="exit-fullscreen"
                title="Exit fullscreen (Esc)">
            <span class="csb-item-icon">@include('campaign::refactor.components.icons.mc-icon', ['icon' => 'fullscreen_exit', 'size' => 20])</span>
            <span class="csb-label">Exit fullscreen</span>
        </button>
        <div class="csb-divider"></div>

        <a class="csb-item" href="{{ route('manager.flow_automations.index') }}"
           title="Back to list">
            <span class="csb-item-icon">@include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow_back', 'size' => 20])</span>
            <span class="csb-label">Back to list</span>
        </a>
        <button type="button" class="csb-item" data-act="toggle-status"
                data-status="{{ $automation->status }}"
                data-uid="{{ $automation->uid }}"
                title="{{ $automation->isActive() ? 'Pause this automation' : 'Activate this automation' }}">
            <span class="csb-item-icon" data-status-icon>@include('campaign::refactor.components.icons.mc-icon', ['icon' => $automation->isActive() ? 'pause' : 'play_arrow', 'size' => 20])</span>
            <span class="csb-label" data-status-text>{{ $automation->isActive() ? 'Pause' : 'Activate' }}</span>
        </button>
        <a class="csb-item" href="#"
           title="Statistics">
            <span class="csb-item-icon">@include('campaign::refactor.components.icons.mc-icon', ['icon' => 'bar_chart', 'size' => 20])</span>
            <span class="csb-label">Statistics</span>
        </a>
        <button type="button" class="csb-item" data-act="open-settings"
                data-url="#"
                title="Settings">
            <span class="csb-item-icon">@include('campaign::refactor.components.icons.mc-icon', ['icon' => 'settings', 'size' => 20])</span>
            <span class="csb-label">Settings</span>
        </button>
    </nav>
    <div class="csb-footer">
        <button type="button" class="csb-item" data-act="toggle-canvas-sidebar"
                title="Collapse sidebar">
            <span class="csb-item-icon">@include('campaign::refactor.components.icons.mc-icon', ['icon' => 'menu_open', 'size' => 20])</span>
            <span class="csb-label">Collapse sidebar</span>
        </button>
    </div>
</aside>

<div class="flow-edit-page">
    <div class="flow-edit-topbar">
        <a class="back" href="{{ route('manager.flow_automations.index') }}">
            <span class="material-symbols-rounded" style="font-size: 18px">arrow_back</span>
            Back
        </a>
        <h1>
            {{ $automation->name }}
            <span class="badge {{ $automation->isActive() ? 'badge-active' : '' }}" data-status-badge>
                {{ $automation->isActive() ? 'ACTIVE' : 'PAUSED' }}
            </span>
        </h1>
        <div class="grow"></div>
        <button type="button" class="action" data-act="toggle-status"
                data-status="{{ $automation->status }}"
                data-uid="{{ $automation->uid }}"
                title="{{ $automation->isActive() ? 'Pause this automation' : 'Activate this automation' }}">
            <span class="material-symbols-rounded">{{ $automation->isActive() ? 'pause' : 'play_arrow' }}</span>
            <span data-status-text>{{ $automation->isActive() ? 'Pause' : 'Activate' }}</span>
        </button>
        <a class="action" href="#"
           title="Statistics">
            <span class="material-symbols-rounded">bar_chart</span>
            Statistics
        </a>
        <button type="button" class="action" data-act="open-settings"
                data-url="#"
                title="Settings">
            <span class="material-symbols-rounded">settings</span>
            Settings
        </button>
        <button type="button" class="action" data-act="enter-fullscreen"
                title="Enter fullscreen (F)">
            <span class="material-symbols-rounded">fullscreen</span>
            Fullscreen
        </button>
    </div>

    <div id="flow-stage" class="flow-edit-stage"></div>

    {{-- Floating: bottom-right zoom dock — viewport tools, icon-only. --}}
    <div class="flow-edit-float flow-edit-zoom">
        <button type="button" class="zoom-tool" onclick="window.flowEdit.renderer.zoomOut()" title="Zoom out" aria-label="Zoom out">
            <span class="material-symbols-rounded">remove</span>
        </button>
        <button type="button" class="zoom-tool" onclick="window.flowEdit.renderer.fit()" title="Fit to screen" aria-label="Fit to screen">
            <span class="material-symbols-rounded" style="font-variation-settings:'FILL' 0,'wght' 250,'GRAD' 0,'opsz' 24">crop_free</span>
        </button>
        <button type="button" class="zoom-tool" onclick="window.flowEdit.renderer.zoomIn()" title="Zoom in" aria-label="Zoom in">
            <span class="material-symbols-rounded">add</span>
        </button>
    </div>

    {{-- Floating: bottom-left onboarding legend. --}}
    <div class="flow-edit-float flow-edit-legend">
        Click <strong>+</strong> to add node · drag to pan · scroll to zoom
    </div>
</div>
@endsection

@section('scripts')
@php $cssVer = $cssVer ?? '1'; @endphp
<script src="https://cdn.jsdelivr.net/npm/dagre@0.8.5/dist/dagre.min.js"></script>
<script src="{{ asset('refactor/js/flow-lib/tree.js') }}?v={{ $cssVer }}"></script>
<script src="{{ asset('refactor/js/flow-lib/renderer.js') }}?v={{ $cssVer }}"></script>
<script src="{{ asset('refactor/js/flow-lib/dynamic-dropdown-control.js') }}?v={{ $cssVer }}"></script>
<script src="{{ asset('refactor/js/flow-lib/automation-edit-app.js') }}?v={{ $cssVer }}"></script>
<script>
    const initialData = @json($flow, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    window.flowEdit = AutomationEditApp.init({
        container:   '#flow-stage',
        initialData,
        csrfToken:   @json(csrf_token()),
        ctx:         @json($ctx),
        urls: {
            create:      @json(route('manager.flow_automations.nodes.create',   [$automation->uid, '__TYPE__'])),
            update:      @json(route('manager.flow_automations.nodes.update',   [$automation->uid, '__ID__'])),
            delete:      @json(route('manager.flow_automations.nodes.delete',   [$automation->uid, '__ID__'])),
            trigger:     @json(route('manager.flow_automations.trigger.update', $automation->uid)),
            emailCreate:        '#',
            emailPreview:       '#',
            emailSettingsShow:  '#',
            emailSettingsSave:  '#',
            emailTest:          '#',
            emailTemplateGallery: '#',
            emailTemplateChoose: '#',
            emailCustomHtml:    '#',
            emailEdit:          '#',
            identityDropbox:    '#',
            enable:             @json(route('manager.flow_automations.enable',  $automation->uid)),
            disable:            @json(route('manager.flow_automations.disable', $automation->uid)),
            indexUrl:           @json(route('manager.flow_automations.index')),
        },
    });

    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => window.flowEdit.renderer.fit(), 120);
    });

    // ── Topbar / sidebar actions ────────────────────────────────────────
    // The topbar buttons AND the new automation-sidebar buttons share the
    // same data-act attributes, so all wiring is done via document-level
    // event delegation — clicks from either source end up in the same
    // handler and visual state updates query ALL [data-status-*] elements
    // so the topbar + sidebar stay in sync.
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Settings sidebar — fetches the partial (complete <aside> + <script>)
    // and injects into <body>. Idempotent: existing panel removed first.
    async function openSettingsPanel(url) {
        document.querySelector('[data-settings-panel]')?.remove();
        document.querySelector('[data-settings-backdrop]')?.remove();
        try {
            const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            const html = await r.text();
            const wrap = document.createElement('div');
            wrap.innerHTML = html;
            while (wrap.firstChild) {
                const node = wrap.firstChild;
                if (node.tagName === 'SCRIPT') {
                    const s = document.createElement('script');
                    s.textContent = node.textContent;
                    document.body.appendChild(s);
                    wrap.removeChild(node);
                } else {
                    document.body.appendChild(node);
                }
            }
        } catch (err) {
            window.flowEdit?.toast?.('Failed to open settings', 'error');
        }
    }

    async function toggleStatus(triggerBtn) {
        const enabling = triggerBtn.dataset.status !== 'active';
        const uid      = triggerBtn.dataset.uid;
        const url      = enabling ? window.flowEdit.urls.enable : window.flowEdit.urls.disable;
        try {
            const r = await fetch(url, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({ uids: uid }),
            });
            const data = await r.json();
            if (!r.ok) throw new Error(data.message || 'Failed');

            // Local UI update — fan out to BOTH topbar and sidebar copies.
            // Sidebar uses the mc-icon SVG partial (custom thin strokes); topbar
            // still uses material-symbols. Each surface has its own swap path.
            const SIDEBAR_PAUSE_ICON = @json(view('campaign::refactor.components.icons.mc-icon', ['icon' => 'pause', 'size' => 20])->render());
            const SIDEBAR_PLAY_ICON  = @json(view('campaign::refactor.components.icons.mc-icon', ['icon' => 'play_arrow', 'size' => 20])->render());
            document.querySelectorAll('[data-act="toggle-status"]').forEach(btn => {
                btn.dataset.status = enabling ? 'active' : 'inactive';
                const text = btn.querySelector('[data-status-text]');
                if (text) text.textContent = enabling ? 'Pause' : 'Activate';
                const sidebarIcon = btn.querySelector('[data-status-icon]');
                if (sidebarIcon) {
                    sidebarIcon.innerHTML = enabling ? SIDEBAR_PAUSE_ICON : SIDEBAR_PLAY_ICON;
                }
                const topbarIcon = btn.querySelector('.material-symbols-rounded');
                if (topbarIcon) topbarIcon.textContent = enabling ? 'pause' : 'play_arrow';
            });
            document.querySelectorAll('[data-status-badge]').forEach(badge => {
                badge.textContent = enabling ? 'ACTIVE' : 'PAUSED';
                // Topbar badge uses .badge-active; sidebar pill uses .csb-status-active.
                badge.classList.toggle('badge-active', enabling);
                badge.classList.toggle('csb-status-active', enabling);
            });
            // Sidebar wrapper carries .is-active to drive the collapsed status dot.
            document.querySelectorAll('[data-canvas-sidebar]').forEach(s => s.classList.toggle('is-active', enabling));

            window.flowEdit.toast(data.message || (enabling ? 'Activated' : 'Paused'), 'ok');
        } catch (err) { window.flowEdit.toast(err.message, 'error'); }
    }

    // Single delegated click handler for every action that lives in BOTH
    // the topbar and the new sidebar. Async wraps are fine — the handler
    // doesn't await.
    document.addEventListener('click', (e) => {
        const settingsBtn = e.target.closest('[data-act="open-settings"]');
        if (settingsBtn) { openSettingsPanel(settingsBtn.dataset.url); return; }

        const statusBtn = e.target.closest('[data-act="toggle-status"]');
        if (statusBtn) { toggleStatus(statusBtn); return; }

        if (e.target.closest('[data-act="enter-fullscreen"]')) { setFullscreen(true); return; }
        if (e.target.closest('[data-act="exit-fullscreen"]'))  { setFullscreen(false); return; }
        if (e.target.closest('[data-act="toggle-canvas-sidebar"]')) {
            const collapsed = document.documentElement.classList.contains('flow-edit-canvas-sidebar-collapsed');
            setCanvasSidebarCollapsed(!collapsed);
            return;
        }
    });

    // Reflect rename in BOTH the topbar <h1> and the sidebar header so the
    // user sees the new name without a reload. Page <title> stays stale until
    // next navigation — acceptable.
    document.addEventListener('automation:renamed', (e) => {
        const newName = e.detail?.name;
        if (!newName) return;
        const h1 = document.querySelector('.flow-edit-topbar h1');
        if (h1) {
            const badge = h1.querySelector('[data-status-badge]');
            if (h1.firstChild && h1.firstChild.nodeType === Node.TEXT_NODE) {
                h1.firstChild.nodeValue = newName + ' ';
            } else if (badge) {
                h1.insertBefore(document.createTextNode(newName + ' '), badge);
            }
        }
        const sbName = document.querySelector('.flow-edit-canvas-sidebar .csb-name');
        if (sbName) sbName.textContent = newName;
    });

    // ── Fullscreen mode ─────────────────────────────────────────────────
    function setFullscreen(on) {
        document.documentElement.classList.toggle('flow-edit-fullscreen', on);
        try { localStorage.setItem('flow-edit-fullscreen', on ? 'true' : 'false'); } catch (_) {}
        // Re-fit the canvas to the new viewport — entering hides chrome
        // (canvas grows ~240px wide + ~56px tall), exiting brings it back.
        // Two rAFs to let the browser apply the new layout BEFORE measuring.
        requestAnimationFrame(() => requestAnimationFrame(() =>
            window.flowEdit?.renderer?.fit?.()
        ));
    }
    function setCanvasSidebarCollapsed(on) {
        document.documentElement.classList.toggle('flow-edit-canvas-sidebar-collapsed', on);
        try { localStorage.setItem('flow-edit-canvas-sidebar-collapsed', on ? 'true' : 'false'); } catch (_) {}
        // Repaint the canvas after the sidebar finishes its width transition
        // so dagre's nodes reflow into the freshly-available space.
        setTimeout(() => window.flowEdit?.renderer?.fit?.(), 220);
    }

    // Keyboard: F to enter, Esc to exit. Skip when the user is typing or has
    // any sidebar/modal open (those have their own Esc handlers).
    document.addEventListener('keydown', (e) => {
        const tag = document.activeElement?.tagName;
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return;
        if (document.activeElement?.isContentEditable) return;
        if (document.querySelector('.fl-sidebar.fl-sidebar-open, [data-settings-panel], .mc-modal.active')) return;
        if (e.key === 'f' || e.key === 'F') {
            if (window.innerWidth <= 1199) return; // mobile fallback
            e.preventDefault();
            setFullscreen(true);
        } else if (e.key === 'Escape' && document.documentElement.classList.contains('flow-edit-fullscreen')) {
            e.preventDefault();
            setFullscreen(false);
        }
    });

    // Mobile auto-disable: actively strip fullscreen when viewport drops to
    // tablet/mobile so the user isn't trapped behind a 240px sidebar on a
    // 600px screen.
    window.addEventListener('resize', () => {
        if (window.innerWidth <= 1199 && document.documentElement.classList.contains('flow-edit-fullscreen')) {
            setFullscreen(false);
        }
    });
</script>
@endsection
