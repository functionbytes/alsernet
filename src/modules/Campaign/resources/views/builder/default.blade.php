@php
    // Auto-compute template tags (var selector) if wrapper didn't pass them.
    // Wrappers may expose a $list directly, or a $campaign whose defaultMailList we can use.
    if (!isset($tags)) {
        $tagList = null;
        if (isset($list)) {
            $tagList = $list;
        } elseif (isset($campaign) && $campaign) {
            $tagList = $campaign->defaultMailList;
        }
        $tags = \Modules\Campaign\Models\Template\Template::tags($tagList);
    }

    // Generic template-kind discriminator passed to layout.head.assets /
    // layout.body.before_close hooks as context. Wrappers (email-template /
    // page-template / form-template / campaign / funnel / automation-email-step)
    // pass `$builderTemplateKind` ∈ {email, page, form} so plugin hook callbacks
    // can resolve surface-specific behaviour. Default `email` matches most
    // common path.
    $builderTemplateKind = $builderTemplateKind ?? 'email';
@endphp
<!DOCTYPE html>
<html lang="en" style="overflow: hidden;">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Builder</title>

    <link rel="icon" href="{{ asset('refactor/images/favicon.svg') }}" type="image/svg+xml">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

    <!-- jQuery -->
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <link href="{{ asset('builder/builder.css') }}?v={{ filemtime(public_path('builder/builder.css')) }}" rel="stylesheet" />
    <script src="{{ asset('builder/builder.js') }}?v={{ filemtime(public_path('builder/builder.js')) }}"></script>

    <link href="{{ asset('refactor/css/components.css') }}?v={{ filemtime(public_path('refactor/css/components.css')) }}" rel="stylesheet" />
    <link href="{{ asset('refactor/css/builder.css') }}?v={{ filemtime(public_path('refactor/css/builder.css')) }}" rel="stylesheet" />

    {{-- (Sin sistema de plugins/Hook en este módulo: bloque de assets de plugin omitido.) --}}

    <script>
    // UndoRedoHistory — Generic JSON state history with undo/redo
    (function () {
        'use strict';
        class UndoRedoHistory {
            constructor(opts = {}) {
                this.maxSize = opts.maxSize || 50;
                this.onUpdate = opts.onUpdate || (() => {});
                this.onStateChange = opts.onStateChange || (() => {});
                this._stack = [];
                this._pointer = -1;
                this._applying = false;
            }
            push(stateJson) {
                if (this._applying) return;
                const snapshot = JSON.stringify(stateJson);
                if (this._pointer >= 0 && JSON.stringify(this._stack[this._pointer]) === snapshot) return;
                this._stack.length = this._pointer + 1;
                this._stack.push(JSON.parse(snapshot));
                this._pointer = this._stack.length - 1;
                if (this._stack.length > this.maxSize) {
                    const excess = this._stack.length - this.maxSize;
                    this._stack.splice(0, excess);
                    this._pointer -= excess;
                }
                this._notify();
            }
            undo() { if (!this.canUndo) return false; this._pointer--; this._apply(); return true; }
            redo() { if (!this.canRedo) return false; this._pointer++; this._apply(); return true; }
            clear() { this._stack = []; this._pointer = -1; this._notify(); }
            get canUndo() { return this._pointer > 0; }
            get canRedo() { return this._pointer < this._stack.length - 1; }
            _apply() {
                this._applying = true;
                try { this.onUpdate(JSON.parse(JSON.stringify(this._stack[this._pointer]))); }
                finally { this._applying = false; }
                this._notify();
            }
            _notify() { this.onStateChange({ canUndo: this.canUndo, canRedo: this.canRedo }); }
        }
        window.UndoRedoHistory = UndoRedoHistory;
    })();
    </script>
</head>

<body>
    <!-- Loading Overlay -->
    <div id="page-loader-overlay">
        <div class="loader"></div>
    </div>

    <script>
        // Loader overlay logic
        window.addEventListener('load', () => {
            const loader = document.getElementById('page-loader-overlay');
            loader.classList.add('fade-out');
            document.body.classList.remove('loading');
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.body.classList.add('loading');
        });
    </script>

    <nav class="builder-topbar">
        <div class="builder-topbar-inner">
            {{-- LEFT: Logo + Title + Change Theme --}}
            <div class="builder-topbar-left">
                <a class="builder-logo" href="{{ url('/') }}" title="{{ trans('campaign::builder.back_to_dashboard') }}">
                    <img class="builder-logo-dark" src="{{ asset('refactor/images/favicon.svg') }}" alt="Logo" style="height:28px" />
                    <img class="builder-logo-light" src="{{ asset('refactor/images/favicon.svg') }}" alt="Logo" style="height:28px" />
                </a>
                <span class="builder-topbar-sep"></span>
                <span class="builder-title">
                    <span class="builder-title-text">{{ $title }}</span>
                </span>
                @if (count($templates))
                    <span class="builder-topbar-sep"></span>
                    <button id="selectTheme" onclick="changeThemePopup.show();" type="button" class="mc-btn mc-btn-ghost mc-btn-sm">
                        <span class="material-symbols-rounded">palette</span>
                        <span>{{ trans('campaign::builder.change_theme') }}</span>
                    </button>
                @endif
            </div>

            {{-- CENTER: Device modes + Undo/Redo --}}
            <div class="builder-topbar-center">
                <div class="builder-device-group">
                    <button id="desktopModeButton" onclick="switchToDesktopMode()" type="button" class="builder-device-btn active" title="{{ trans('campaign::builder.desktop') }}">
                        <span class="material-symbols-rounded">desktop_windows</span>
                    </button>
                    <button id="tabletModeButton" onclick="switchToTabletMode()" type="button" class="builder-device-btn" title="{{ trans('campaign::builder.tablet') }}">
                        <span class="material-symbols-rounded">tablet</span>
                    </button>
                    <button id="mobileModeButton" onclick="switchToMobileMode()" type="button" class="builder-device-btn" title="{{ trans('campaign::builder.mobile') }}">
                        <span class="material-symbols-rounded">smartphone</span>
                    </button>
                </div>
                <span class="builder-topbar-sep"></span>
                <button id="undoBtn" onclick="if(window.builderHistory)builderHistory.undo()" type="button" class="builder-device-btn" disabled title="{{ trans('campaign::builder.undo_shortcut') }}">
                    <span class="material-symbols-rounded">undo</span>
                </button>
                <button id="redoBtn" onclick="if(window.builderHistory)builderHistory.redo()" type="button" class="builder-device-btn" disabled title="{{ trans('campaign::builder.redo_shortcut') }}">
                    <span class="material-symbols-rounded">redo</span>
                </button>
            </div>

            {{-- RIGHT: Preview + Save/Close + Export --}}
            <div class="builder-topbar-right">
                <button id="toggleButton" onclick="toggleDesignMode()" type="button" class="mc-btn mc-btn-ghost mc-btn-sm" title="{{ trans('campaign::builder.toggle_preview') }}">
                    <span class="material-symbols-rounded">visibility</span>
                </button>
                <button id="themeToggleBtn" onclick="toggleBuilderTheme()" type="button" class="mc-btn mc-btn-ghost mc-btn-sm" title="{{ trans('campaign::builder.toggle_theme') }}">
                    <span class="material-symbols-rounded">dark_mode</span>
                </button>

                <span class="builder-topbar-sep"></span>

                <button id="saveToStoreButton" onclick="saveToStore()" type="button" class="mc-btn mc-btn-primary mc-btn-sm" title="{{ trans('campaign::builder.save_shortcut') }}">
                    <span class="material-symbols-rounded">save</span>
                    <span>{{ trans('campaign::builder.save') }}</span>
                </button>
                <button id="saveAndCloseButton" onclick="saveAndClose()" type="button" class="mc-btn mc-btn-ghost mc-btn-sm" title="{{ trans('campaign::builder.save_go_back') }}">
                    <span class="material-symbols-rounded">task_alt</span>
                    <span>{{ trans('campaign::builder.save_and_close') }}</span>
                </button>
                <a href="{{ $cancelUrl }}" class="mc-btn mc-btn-ghost mc-btn-sm" title="{{ trans('campaign::builder.close_without_saving') }}">
                    <span class="material-symbols-rounded">close</span>
                    <span>{{ trans('campaign::builder.close') }}</span>
                </a>

                <span class="builder-topbar-sep"></span>

                <div class="dropdown">
                    <button class="mc-btn mc-btn-ghost mc-btn-sm dropdown-toggle" type="button" id="exportDropdownButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="material-symbols-rounded">downloading</span>
                        <span>{{ trans('campaign::builder.export') }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end builder-export-menu" aria-labelledby="exportDropdownButton">
                        <a id="exportButtonHTML" class="mc-dropdown-item builder-export-item" href="#" onclick="exportThemeHTML(this); return false;">
                            <span class="fw-semibold">{{ trans('campaign::builder.export_html') }}</span>
                            <span class="builder-export-desc">{{ trans('campaign::builder.export_html_desc') }}</span>
                        </a>
                        <a id="exportButtonZip" class="mc-dropdown-item builder-export-item" href="#" onclick="exportThemeZip(this); return false;">
                            <span class="fw-semibold">{{ trans('campaign::builder.export_zip') }}</span>
                            <span class="builder-export-desc">{{ trans('campaign::builder.export_zip_desc') }}</span>
                        </a>
                    </div>
                </div>

                @if (Auth::check())
                    @php $displayName = Auth::user()->name ?? Auth::user()->email ?? 'U'; @endphp
                    <span class="builder-topbar-sep"></span>
                    <div class="dropdown">
                        <div class="builder-topbar-user" data-bs-toggle="dropdown" aria-expanded="false" tabindex="0">
                            <div class="mc-avatar mc-avatar-sm">{{ strtoupper(substr($displayName, 0, 1)) }}</div>
                        </div>
                        <div class="dropdown-menu dropdown-menu-end builder-user-menu">
                            <div class="builder-user-menu-header">
                                <div class="mc-avatar mc-avatar-md">{{ strtoupper(substr($displayName, 0, 1)) }}</div>
                                <div class="builder-user-menu-info">
                                    <div class="builder-user-menu-name">{{ $displayName }}</div>
                                    <div class="builder-user-menu-email">{{ Auth::user()->email ?? '' }}</div>
                                </div>
                            </div>
                            <div class="mc-dropdown-divider"></div>
                            <a class="mc-dropdown-item" href="{{ url('/') }}">
                                <span class="material-symbols-rounded">dashboard</span>{{ trans('campaign::builder.dashboard') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <div data-control="content-wrapper" class="content-wrapper">
            <div class="d-flex justify-content-center builder-canvas-area">
                <div class="spin-loader">
                </div>
                <div id="MainContainer" class="content-container">
                </div>
            </div>
        </div>
        <script>
            // Click on [data-control="content-wrapper"] but not its child #MainContainer to deselect element
            document.querySelector('[data-control="content-wrapper"]').addEventListener('click', function (e) {
                const mainContainer = document.getElementById('MainContainer');
                if (mainContainer.contains(e.target)) {
                    return;
                }
                builder.unselect();
            });
        </script>


        <div class="builder-sidebar">
            <div data-smenu-container="themes" style="display:none;">
                <div class="builder-sidebar-tabs">
                    <div class="tab-item active">
                        <span class="material-symbols-rounded">auto_awesome_mosaic</span>
                    </div>
                </div>
                <div class="themes-panel">
                    <div class="builder-panel-header">
                        <h6 class="fw-bold text-nowrap mb-0">{{ trans('campaign::builder.themes') }}</h6>
                    </div>
                    <div class="theme-box">
                        <div class="themes-header mx-3">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="fw-semibold small text-nowrap">{{ trans('campaign::builder.all_themes') }}</span>
                                <span class="builder-hr-line flex-grow-1"></span>
                            </div>
                            <div class="builder-panel-intro">{{ trans('campaign::builder.theme_intro') }}</div>
                            <div class="theme-items row">
                                @foreach ($templates as $t)
                                    <div class="col-6 mb-4">
                                        <a data-control="change-template" href="{{ $changeTemplateUrl }}" data-id="{{ $t->uid }}" class="theme-item shadow-sm select-template-layout" draggable="true">
                                            <div class="theme-thumb">
                                                <img width="100%" src="{{ $t->getThumbUrl() }}" alt="">
                                            </div>
                                            <div class="p-2">
                                                <span class="fw-semibold theme-label">{{ $t->name }}</span>
                                            </div>
                                        </a>
                                    </div>
                                @endforeach
                                <div class="col-6 mb-4">
                                    <div onclick="smenu.openTab(document.querySelector('[data-smenu=import]'));" class="theme-item shadow-sm" draggable="true">
                                        <div class="theme-thumb">
                                            <svg xmlns="http://www.w3.org/2000/svg" id="Layer_2" viewBox="0 0 596 769">
                                                <g id="Layer_1-2">
                                                    <rect width="596" height="769" style="fill:#f8f9fa;" />
                                                    <path d="M254.75,464.46v-105.79c0-4.85,1.71-8.97,5.14-12.35,3.43-3.38,7.56-5.07,12.41-5.07h105.66c4.81,0,8.92,1.71,12.35,5.14,3.42,3.42,5.14,7.54,5.14,12.35v68.08c0,2.39-.47,4.67-1.42,6.84-.94,2.17-2.2,4.04-3.77,5.6l-37.51,37.51c-1.57,1.57-3.44,2.82-5.6,3.77-2.17.94-4.45,1.42-6.84,1.42h-68.08c-4.81,0-8.92-1.71-12.35-5.14-3.42-3.42-5.14-7.54-5.14-12.35ZM200.91,326.06c-.94-4.84-.02-9.22,2.78-13.12,2.8-3.91,6.62-6.3,11.48-7.19l104.1-18.34c4.8-.94,9.13,0,12.99,2.82,3.86,2.82,6.3,6.64,7.33,11.44l1.48,9.01c.35,1.8.04,3.24-.91,4.32-.95,1.08-2.08,1.7-3.38,1.88-1.32.22-2.58,0-3.79-.67-1.2-.67-2.03-1.9-2.48-3.68l-1.89-9.99c-.28-1.53-1.11-2.74-2.5-3.64-1.39-.9-2.91-1.21-4.58-.94l-104.46,18.54c-1.94.28-3.4,1.18-4.37,2.71-.97,1.53-1.32,3.26-1.04,5.2l19.77,112.22c.26,1.4,0,2.73-.79,3.99-.79,1.26-1.92,2.08-3.39,2.49-1.47.4-2.86.13-4.16-.82-1.3-.95-2.09-2.2-2.35-3.74l-19.83-112.49ZM265.58,358.74v105.72c0,1.94.62,3.54,1.87,4.79,1.25,1.25,2.85,1.87,4.79,1.87h69.1l43.29-43.29v-69.1c0-1.94-.62-3.54-1.87-4.79-1.25-1.25-2.85-1.87-4.79-1.87h-105.72c-1.94,0-3.54.62-4.79,1.87-1.25,1.25-1.87,2.85-1.87,4.79ZM319.69,417.01v27.06c0,1.53.52,2.82,1.56,3.86,1.04,1.04,2.33,1.56,3.86,1.56s2.82-.52,3.85-1.56,1.55-2.32,1.55-3.86v-27.06h27.06c1.53,0,2.82-.52,3.86-1.56s1.56-2.33,1.56-3.86-.52-2.82-1.56-3.85c-1.04-1.03-2.32-1.55-3.86-1.55h-27.06v-27.06c0-1.53-.52-2.82-1.56-3.86-1.04-1.04-2.33-1.56-3.86-1.56s-2.82.52-3.85,1.56c-1.03,1.04-1.55,2.32-1.55,3.86v27.06h-27.06c-1.53,0-2.82.52-3.86,1.56s-1.56,2.33-1.56,3.86.52,2.82,1.56,3.85c1.04,1.03,2.32,1.55,3.86,1.55h27.06Z" style="fill:#666; opacity:.57;" />
                                                </g>
                                            </svg>
                                        </div>
                                        <div class="p-2">
                                            <span class="fw-semibold theme-label">{{ trans('campaign::builder.add_new_theme') }}</span>
                                            <p class="mb-0 theme-desc">{{ trans('campaign::builder.add_new_theme_desc') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div data-smenu-container="design">
                <div id="Sidebar">
                    <div class="builder-sidebar-tabs">
                        <div class="tab-item active" data-tab="widgets">
                            <span class="material-symbols-rounded">widgets</span>
                        </div>
                        <div class="tab-item" data-tab="controls">
                            <span class="material-symbols-rounded">instant_mix</span>
                        </div>
                        @if (isset($tags) && !empty($tags))
                            <div class="tab-item" data-tab="styles">
                                <span class="material-symbols-rounded">data_object</span>
                            </div>
                        @endif
                        <div onclick="builder.selectElement(builder.pageElement);" class="tab-item" data-tab="page-settings">
                            <span class="material-symbols-rounded">settings</span>
                        </div>
                    </div>
                    <div style="height: calc(100vh - 105px); overflow-y: auto;">
                        <div id="WidgetsContainer" data-tab-container="widgets" style="display: block;"></div>
                        <div id="SettingsContainer" data-tab-container="controls" style="display: none;"></div>
                        @if (isset($tags) && !empty($tags))
                            <div data-tab-container="styles" style="display: none;">
                                <div class="builder-tags-container">
                                    @foreach ($tags as $tag)
                                        <div class="tag-item d-flex align-items-center mb-2 px-2 py-1 border-bottom">
                                            <span class="tag-label fw-semibold me-2">{{ $tag['name'] }}</span>
                                            <button type="button" class="mc-btn mc-btn-ghost mc-btn-xs ms-auto copy-tag-btn" data-tag="{{ $tag['name'] }}" title="Copy tag">
                                                <span class="material-symbols-rounded">content_copy</span>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div data-smenu-container="import" style="display:none;">
                <div class="builder-sidebar-tabs">
                    <div class="tab-item active">
                        <span class="material-symbols-rounded">arrow_circle_down</span>
                    </div>
                </div>
                <div class="themes-panel">
                    <div class="builder-panel-header">
                        <h6 class="fw-bold text-nowrap mb-0">{{ trans('campaign::builder.import') }}</h6>
                    </div>
                    <div class="themes-header mx-3">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="fw-semibold small text-nowrap">{{ trans('campaign::builder.upload') }}</span>
                            <span class="builder-hr-line flex-grow-1"></span>
                        </div>
                        <div class="builder-panel-intro">{{ trans('campaign::builder.upload_intro') }}</div>
                        <div class="upload-container mt-3 p-3 border rounded text-center">
                            <div id="uploadPlaceholder" class="p-5 border-dashed rounded builder-upload-placeholder">
                                <span class="material-symbols-rounded builder-upload-icon">upload_file</span>
                                <p id="fileName" class="text-muted mt-2 mb-0">{!! trans('campaign::builder.upload_browse') !!}</p>
                            </div>
                            <input id="fileInput" type="file" accept=".zip" style="display: none;" />
                            <button id="uploadButton" class="mc-btn mc-btn-secondary mc-btn-sm mt-3" disabled>{{ trans('campaign::builder.upload_theme') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="global-sidebar">
            <div class="sidebar-toggle-button">
                <span class="material-symbols-rounded sidebar-toggle-icon">apps</span>
            </div>
            <div class="smenu-items p-1">
                <div data-smenu="themes" class="smenu-item p-2 my-3">
                    <div class="d-flex align-items-center justify-content-center w-100">
                        <span class="material-symbols-rounded smenu-item-icon rounded-2 p-1 mb-1">newspaper</span>
                    </div>
                    <div class="d-block text-center smenu-item-label">{{ trans('campaign::builder.themes') }}</div>
                </div>
                <div data-smenu="design" class="smenu-item p-2 my-3 active">
                    <div class="d-flex align-items-center justify-content-center w-100">
                        <span class="material-symbols-rounded smenu-item-icon rounded-2 p-1 mb-1">design_services</span>
                    </div>
                    <div class="d-block text-center smenu-item-label">{{ trans('campaign::builder.design') }}</div>
                </div>
                <div data-smenu="import" class="smenu-item p-2 my-3">
                    <div class="d-flex align-items-center justify-content-center w-100">
                        <span class="material-symbols-rounded smenu-item-icon rounded-2 p-1 mb-1">arrow_circle_down</span>
                    </div>
                    <div class="d-block text-center smenu-item-label">{{ trans('campaign::builder.import') }}</div>
                </div>
                <div data-smenu="branding" class="smenu-item p-2 my-3 position-relative"
                    data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-custom-class="custom-tooltip"
                    data-bs-title="Coming soon!">
                    <div class="d-flex align-items-center justify-content-center w-100">
                        <span class="material-symbols-rounded smenu-item-icon rounded-2 p-1 mb-1">corporate_fare</span>
                        <span class="material-symbols-rounded smenu-coming-soon-badge">info</span>
                    </div>
                    <div class="d-block text-center smenu-item-label">{{ trans('campaign::builder.branding') }}</div>
                </div>
                <div onclick="selectPageElement();" data-smenu="settings" class="smenu-item p-2 my-3">
                    <div class="d-flex align-items-center justify-content-center w-100">
                        <span class="material-symbols-rounded smenu-item-icon rounded-2 p-1 mb-1">settings</span>
                    </div>
                    <div class="d-block text-center smenu-item-label">{{ trans('campaign::builder.settings') }}</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Config
        const handlerAssetUploadHandler = '{{ $assetUploadHandler }}';
        const handlerExportHtml = '{{ route("manager.page_templates.export_html") }}';
        const handlerExportZip = '{{ route("manager.page_templates.export_zip") }}';
        const handlerSave = '{{ $saveUrl }}';
        const handlerUpload = '/upload.php';

        const themeTemplatesJson = {!! json_encode(\Modules\Campaign\Library\BuilderJSHelper::loadTemplates($template->theme ?: 'default')) !!};
        const themeConfigData = {!! json_encode($template->getThemeConfigData()) !!};
        const themeMediaUrl = '{{ rtrim($template->getThemeUrl(), "/") }}';
        const themeUrl = themeMediaUrl; // used by export helpers only
        const themeName = '{{ $template->name }}';
        const themeKey = '{{ $template->theme ?? "default" }}';
        const themeDir = themeKey;
        const themeJson = @json($template->getBuilderJson());
    </script>

    <script>
        // Main Builder initialization
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize the Builder instance
            window.builder = new Builder({
                mainContainer: '#MainContainer', // Content ID where page content will render
                settingsContainer: '#SettingsContainer', // Where Element Settings/Controls will be render
                widgetsContainer: '#WidgetsContainer', // Where Widgets will be rendered
                assetUploadHandler: handlerAssetUploadHandler,
                // BuilderJS inline rewrite library config — defaults to disabled.
                // The acelle/ai plugin patches `window.builder.ai` post-construction
                // when active (see plugin's body_assets injection).
                ai: {
                    inlineRewriteUrl: '',
                    enabled:          false,
                    csrfToken:        '{{ csrf_token() }}',
                },
                rssHandler: '{{ route('manager.page_templates.rss_proxy') }}',

                carousel: false, // Enable carousel features

                // filemanager
                // fileManager: filemanager,

                // i18n translations — pass current language translations to builder
                translations: @json(trans('campaign::builder_js')),
            });

            // Builder: load current theme data
            builder.load(themeJson, themeTemplatesJson, themeConfigData, themeMediaUrl, () => {
                // remove page loading effects
                document.querySelector('.spin-loader').remove();

                // register widgets
                builder.widgetsBox.addWidget(new ParagraphWidget(), { group: 'Basic' });
                builder.widgetsBox.addWidget(new HeadingWidget(), { group: 'Basic' });
                builder.widgetsBox.addWidget(new WelcomeWidget(), { group: 'Basic' });
                builder.widgetsBox.addWidget(new MenuWidget(), { group: 'Basic' });
                builder.widgetsBox.addWidget(new DividerWidget(), { group: 'Basic' });
                builder.widgetsBox.addWidget(new ImageWidget(), { group: 'Basic' });
                builder.widgetsBox.addWidget(new GridWidget(), { group: 'Basic' });
                builder.widgetsBox.addWidget(new ButtonWidget(), { group: 'Basic' });
                builder.widgetsBox.addWidget(new VideoWidget(), { group: 'Basic' });
                builder.widgetsBox.addWidget(new AlertWidget(), { group: 'Basic' });
                builder.widgetsBox.addWidget(new YoutubeWidget(), { group: 'Basic' });
                builder.widgetsBox.addWidget(new RSSWidget(), { group: 'Basic' });

                builder.widgetsBox.addWidget(new ImageTextLeftWidget(), { group: 'Image & Text', type: 'image' });
                builder.widgetsBox.addWidget(new ImageTextTopWidget(), { group: 'Image & Text', type: 'image' });
                builder.widgetsBox.addWidget(new ImageTextRightWidget(), { group: 'Image & Text', type: 'image' });
                builder.widgetsBox.addWidget(new ImageTextBottomWidget(), { group: 'Image & Text', type: 'image' });
                builder.widgetsBox.addWidget(new ImageTextDoubleWidget(), { group: 'Image & Text', type: 'image' });

                builder.widgetsBox.addWidget(new TextCenterWidget(), { group: 'Text', type: 'image' });
                builder.widgetsBox.addWidget(new TextLeftWidget(), { group: 'Text', type: 'image' });
                builder.widgetsBox.addWidget(new TextDoubleWidget(), { group: 'Text', type: 'image' });

                builder.widgetsBox.render();

                // Initialize undo/redo history
                window.builderHistory = new UndoRedoHistory({
                    maxSize: 50,
                    onUpdate: (state) => {
                        builder.pageElement.parse(state);
                        builder.pageElement.render();
                    },
                    onStateChange: ({ canUndo, canRedo }) => {
                        document.getElementById('undoBtn').disabled = !canUndo;
                        document.getElementById('redoBtn').disabled = !canRedo;
                    },
                });

                // Push initial state
                builderHistory.push(builder.getData());

                // Observe builder changes — push state on mutation
                const observer = new MutationObserver(() => {
                    if (window.builderHistory && !window.builderHistory._applying) {
                        builderHistory.push(builder.getData());
                    }
                });
                if (builder.iframe && builder.iframe.contentDocument) {
                    observer.observe(builder.iframe.contentDocument.body, {
                        childList: true, subtree: true, attributes: true, characterData: true
                    });
                }

                // F2.1 — Acelle-native AI chatbox mounts universally via the mc-ai-chatbox blade component.
                // Builder-page page_update behaviour is handled by AIChatboxBuilderAdapter
                // (reads window.builder.getData() + applies response.page to canvas + pushes to builderHistory).
                // Legacy AIChatBox init was removed here on 2026-04-24 — see AI.md D33.
            });


            // Sidebar toggle
            document.querySelector('.sidebar-toggle-button').addEventListener('click', () => {
                document.body.classList.toggle('hide-sidebar');
            });

            // Copy tag buttons
            document.querySelectorAll('.copy-tag-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const tag = btn.getAttribute('data-tag');
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(tag).then(
                            () => SNotify.show('Copied to clipboard!', 'success'),
                            () => SNotify.show('Failed to copy!', 'error')
                        );
                    } else {
                        const temp = document.createElement('input');
                        temp.value = tag;
                        document.body.appendChild(temp);
                        temp.select();
                        document.execCommand('copy');
                        document.body.removeChild(temp);
                        SNotify.show(tag + ' was copied to clipboard!', 'success');
                    }
                });
            });

            // UI tab: SMenu
            window.smenu = new TabsManager();
            smenu.addTab(document.querySelector('[data-smenu="themes"]'), document.querySelector('[data-smenu-container="themes"]'));
            smenu.addTab(document.querySelector('[data-smenu="design"]'), document.querySelector('[data-smenu-container="design"]'));
            smenu.addTab(document.querySelector('[data-smenu="import"]'), document.querySelector('[data-smenu-container="import"]'));

            // UI tab: Sidebar tabs
            window.sidebarTabManager = new TabsManager();
            sidebarTabManager.addTab(document.querySelector('[data-tab="widgets"]'), document.querySelector('[data-tab-container="widgets"]'));
            sidebarTabManager.addTab(document.querySelector('[data-tab="controls"]'), document.querySelector('[data-tab-container="controls"]'));
            
            @if (isset($tags) && !empty($tags))
                sidebarTabManager.addTab(document.querySelector('[data-tab="styles"]'), document.querySelector('[data-tab-container="styles"]'));
            @endif

            // Set default mode to desktop
            switchToDesktopMode();

            // Theme picker popup is included as a Blade partial at the end of
            // <body> — it self-registers `window.changeThemePopup` (see
            // _change_theme_popup.blade.php). No JS instantiation needed here.

            // Upload Manager
            window.uploadManager = new UploadManager(
                document.getElementById('uploadPlaceholder'),
                document.getElementById('fileInput'),
                document.getElementById('uploadButton'),
                document.getElementById('fileName')
            );
        });

        async function downloadPDF(button) {
            // Show loading effect
            originalText = button.innerHTML; // Save original text
            button.disabled = true;
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Generating PDF file...
            `;

            const elements = builder.iframe.contentWindow.document.querySelectorAll('[builder-element="CarouselBlockElement"]');

            // Render the first element to get its size
            const {
                imgData,
                width,
                height
            } = await elementToImage(elements[0]);
            const pdfWidth = (width * 25.4) / 96;
            const pdfHeight = (height * 25.4) / 96;

            const {
                jsPDF
            } = window.jspdf;
            const pdf = new jsPDF({
                unit: 'mm',
                format: [pdfWidth, pdfHeight]
            });

            // First page
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);

            // Rest of the pages
            for (let i = 1; i < elements.length; i++) {
                const {
                    imgData,
                    width,
                    height
                } = await elementToImage(elements[i]);
                const w = (width * 25.4) / 96;
                const h = (height * 25.4) / 96;

                pdf.addPage([w, h]);
                pdf.addImage(imgData, 'PNG', 0, 0, w, h);
            }

            pdf.save('carousel-images.pdf');

            // Remove loading effect
            button.disabled = false;
            button.innerHTML = originalText;
        }

        function elementToImage(element) {
            return html2canvas(element).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                return {
                    imgData,
                    width: canvas.width,
                    height: canvas.height
                };
            });
        }

        function exportThemeHTML(exportButton) {
            const originalText = exportButton.innerHTML;
            exportButton.disabled = true;
            exportButton.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Exporting...`;

            const payload = new URLSearchParams();
            payload.append('html', builder.getHtml());
            payload.append('baseUrl', window.location.origin);
            payload.append('_token', '{{ csrf_token() }}');

            fetch(handlerExportHtml, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: payload.toString()
            })
            .then(response => {
                if (!response.ok) throw new Error('Server error: ' + response.status);
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = themeName + '.html';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => SAlert.show(String(error), 'error'))
            .finally(() => {
                exportButton.disabled = false;
                exportButton.innerHTML = originalText;
            });
        }

        function exportThemeZip(exportButton) {
            const originalText = exportButton.innerHTML;
            exportButton.disabled = true;
            exportButton.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Exporting...`;

            const payload = new URLSearchParams();
            payload.append('html', builder.getHtml());
            payload.append('themeUrl', themeUrl);
            payload.append('theme', themeKey);
            payload.append('_token', '{{ csrf_token() }}');

            fetch(handlerExportZip, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: payload.toString()
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.error || 'Export failed'); });
                    }
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = themeKey + '.zip';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                })
                .catch(error => {
                    SAlert.show(error.message || 'Export failed', 'error');
                })
                .finally(() => {
                    exportButton.disabled = false;
                    exportButton.innerHTML = originalText;
                });
        }

        // Actions
        function runSampleCode() {
            // H1 Sample
            const h1 = new H1Element('H1', 'Welcome!');
            builder.appendElements([h1]);

            // Paragraph Sample
            const p = new PElement('P', 'Hello, world!');
            builder.appendElements([p]);

            // Add Menu to Page
            const menu = new MenuElement('Menu', [{
                    text: 'Home',
                    url: '#'
                },
                {
                    text: 'About',
                    url: '#'
                },
                {
                    text: 'Contact',
                    url: '#'
                }
            ]);
            builder.appendElements([menu]);

            // GRID + CELLs
            const grid = new GridElement('Grid');
            const cell_1 = new CellElement('Cell');
            const cell_2 = new CellElement('Cell');

            const cell_p1 = new PElement('P', 'Inside cell 1');
            const cell_p2 = new PElement('P', 'Inside cell 2');
            cell_1.appendElements([cell_p1]);
            cell_2.appendElements([cell_p2]);

            builder.appendElements([grid]);

            // // DROP WIDGET INSIDE PAGE
            // const widget = new MenuWidget();
            // builder.appendElements(widget.renderElements());

            // Image Element
            const image = new ImageElement('Image');
            builder.appendElements([image]);
        }

        function clearPage() {
            builder.clear();
        }

        // Save & Close: save then redirect back
        function saveAndClose() {
            const saveButton = document.getElementById('saveAndCloseButton');
            saveButton.disabled = true;
            const origHTML = saveButton.innerHTML;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

            const data = builder.getData();
            const payload = new URLSearchParams();
            payload.append('dir', themeDir);
            payload.append('json', JSON.stringify(data));
            payload.append('content', builder.getHtml());
            payload.append('_token', '{{ csrf_token() }}');

            fetch(handlerSave, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: payload.toString()
            })
            .then(async response => {
                const text = await response.text();
                let data;
                try { data = JSON.parse(text); }
                catch (e) { data = { status: 'error', message: response.status === 419 ? 'Session expired. Please reload the page.' : `Server returned ${response.status}` }; }
                return { status: response.status, data };
            })
            .then(({ status, data }) => {
                if (status === 403 || data.status === 'error') {
                    SAlert.show(data.message || 'Save failed', 'error');
                    saveButton.disabled = false;
                    saveButton.innerHTML = origHTML;
                } else if (data.status === 'success') {
                    window.location.href = '{{ $cancelUrl }}';
                } else {
                    SAlert.show(data.message || 'Save failed', 'error');
                    saveButton.disabled = false;
                    saveButton.innerHTML = origHTML;
                }
            })
            .catch(error => {
                SAlert.show(String(error), 'error');
                saveButton.disabled = false;
                saveButton.innerHTML = origHTML;
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+S / Cmd+S = Save
            if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key === 's') {
                e.preventDefault();
                saveToStore();
            }
            // Ctrl+Z / Cmd+Z = Undo
            if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key === 'z') {
                e.preventDefault();
                if (window.builderHistory) builderHistory.undo();
            }
            // Ctrl+Shift+Z / Cmd+Shift+Z = Redo
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'z') {
                e.preventDefault();
                if (window.builderHistory) builderHistory.redo();
            }
        });

        function toggleDesignMode() {
            if (builder.getMode() == 'design') {
                builder.setMode('preview');
                document.getElementById('toggleButton').innerHTML = `
                    <span class="d-flex align-items-center">
                        <span class="material-symbols-rounded">visibility_off</span>
                    </span>
                `;
            } else {
                builder.setMode('design');
                document.getElementById('toggleButton').innerHTML = `
                    <span class="d-flex align-items-center">
                        <span class="material-symbols-rounded">visibility</span>
                    </span>
                `;
            }
        }

        // Dark/Light theme toggle — uses app's data-theme system from variables.css
        function toggleBuilderTheme() {
            const html = document.documentElement;
            const isDark = html.getAttribute('data-theme') === 'dark';
            const newTheme = isDark ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('mc-theme', newTheme);
            const btn = document.getElementById('themeToggleBtn');
            btn.innerHTML = `<span class="material-symbols-rounded">${newTheme === 'dark' ? 'light_mode' : 'dark_mode'}</span>`;
            btn.title = newTheme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
        }

        // Restore theme from app preference
        (function() {
            const saved = localStorage.getItem('mc-theme');
            if (saved === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.getElementById('themeToggleBtn').innerHTML = '<span class="material-symbols-rounded">light_mode</span>';
                document.getElementById('themeToggleBtn').title = 'Switch to light mode';
            }
        })();

        function saveToStore() {
            // Show loading effect
            const saveButton = document.getElementById('saveToStoreButton');
            saveButton.disabled = true;
            saveButton.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Saving...
            `;

            const data = builder.getData(); // Get existing data
            const payload = new URLSearchParams();
            payload.append('dir', themeDir); // Add current theme
            payload.append('json', JSON.stringify(data)); // Include the rest of the data
            payload.append('content', builder.getHtml()); // Include the rest of the data
            payload.append('_token', '{{ csrf_token() }}');

            fetch(handlerSave, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: payload.toString()
                })
                .then(async response => {
                    const text = await response.text();
                    let data;
                    try { data = JSON.parse(text); }
                    catch (e) { data = { status: 'error', message: response.status === 419 ? 'Session expired. Please reload the page.' : `Server returned ${response.status}` }; }
                    return { status: response.status, data };
                })
                .then(({ status, data }) => {
                    if (status === 403 || data.status === 'error') {
                        SAlert.show(data.message || 'Save failed', 'error');
                    } else if (data.status === 'success') {
                        SAlert.show('Data saved successfully.');
                    } else {
                        SAlert.show(data.message || 'Save failed', 'error');
                    }
                })
                .catch(error => {
                    SAlert.show(String(error), 'error');
                })
                .finally(() => {
                    // Remove loading effect
                    saveButton.disabled = false;
                    saveButton.innerHTML = `
                        <span class="material-symbols-rounded">save</span>
                        <span>{{ trans('campaign::builder.save') }}</span>
                    `;
                });
        }

        function switchToMobileMode() {
            const contentContainer = document.querySelector('.content-container');
            const mobileButton = document.getElementById('mobileModeButton');
            const desktopButton = document.getElementById('desktopModeButton');
            const tabletButton = document.getElementById('tabletModeButton');

            contentContainer.classList.add('mobile-mode');
            contentContainer.classList.remove('desktop-mode');
            contentContainer.classList.remove('tablet-mode');

            mobileButton.classList.add('active');
            desktopButton.classList.remove('active');
            tabletButton.classList.remove('active');
        }

        function switchToTabletMode() {
            const contentContainer = document.querySelector('.content-container');
            const mobileButton = document.getElementById('mobileModeButton');
            const desktopButton = document.getElementById('desktopModeButton');
            const tabletButton = document.getElementById('tabletModeButton');

            contentContainer.classList.remove('mobile-mode');
            contentContainer.classList.remove('desktop-mode');
            contentContainer.classList.add('tablet-mode');

            mobileButton.classList.remove('active');
            desktopButton.classList.remove('active');
            tabletButton.classList.add('active');
        }

        function switchToDesktopMode() {
            const contentContainer = document.querySelector('.content-container');
            const mobileButton = document.getElementById('mobileModeButton');
            const desktopButton = document.getElementById('desktopModeButton');
            const tabletButton = document.getElementById('tabletModeButton');

            contentContainer.classList.add('desktop-mode');
            contentContainer.classList.remove('mobile-mode');
            contentContainer.classList.remove('tablet-mode');

            desktopButton.classList.add('active');
            mobileButton.classList.remove('active');
            tabletButton.classList.remove('active');
        }

        function selectPageElement() {
            const element = builder.pageElement;

            // Select the element
            builder.selectElement(element);
        }

        var SNotify = {
            show: function(message, type = 'default') {
                // Ensure the notifications wrapper exists
                let wrapper = document.getElementById('notificationsWrapper');
                if (!wrapper) {
                    wrapper = document.createElement('div');
                    wrapper.id = 'notificationsWrapper';
                    wrapper.style.position = 'fixed';
                    wrapper.style.bottom = '20px';
                    wrapper.style.right = '20px';
                    wrapper.style.zIndex = '1050';
                    wrapper.style.display = 'flex';
                    wrapper.style.flexDirection = 'column-reverse';
                    wrapper.style.gap = '10px';
                    document.body.appendChild(wrapper);
                }

                // Create a new notification element
                const notification = document.createElement('div');

                notification.style.minWidth = '250px';
                // notification.style.color = '#fff';
                notification.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                notification.style.opacity = '1';
                notification.style.transform = 'translateY(0)';

                // Set background color and icon based on type
                let icon = '';
                let colorClass = 'secondary';
                switch (type) {
                    case 'success':
                        colorClass = 'success';
                        icon = 'check_circle';
                        break;
                    case 'error':
                        colorClass = 'danger';
                        icon = 'error';
                        break;
                    default:
                        colorClass = 'secondary';
                        icon = 'info';
                        break;
                }

                // Set the notification class
                notification.className = `notification shadow rounded d-flex align-items-center p-3 bg-${colorClass}-subtle text-${colorClass}-emphasis`;

                // Add content to the notification
                notification.innerHTML = `
                    <span class="material-symbols-rounded me-2">${icon}</span>
                    <span>${message}</span>
                `;

                // Append the notification to the wrapper
                wrapper.appendChild(notification);

                // Remove the notification after 3 seconds
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateY(20px)';
                    setTimeout(() => wrapper.removeChild(notification), 300);
                }, 3000);
            }
        };

        // Theme picker popup — see resources/views/builder/_change_theme_popup.blade.php
        // (legacy Bootstrap modal + JS class removed; partial self-registers
        //  window.changeThemePopup with mc-modal + mc-tabs primitives).

        var UploadManager = class {
            constructor(uploadPlaceholder, fileInput, uploadButton, fileName) {
                this.uploadPlaceholder = uploadPlaceholder;
                this.fileInput = fileInput;
                this.uploadButton = uploadButton;
                this.fileName = fileName;

                this.events();
            }

            browserFile() {
                this.fileInput.click();
            }

            fileChangeHandler() {
                if (this.fileInput.files.length > 0) {
                    this.fileName.textContent = this.fileInput.files[0].name; // Show selected file name
                    this.uploadButton.disabled = false; // Enable upload button
                }
            }

            uploadFile() {
                const file = this.fileInput.files[0];
                if (!file) {
                    SAlert.show('Please select a file to upload.', 'info');
                    return;
                }

                const formData = new FormData();
                formData.append('file', file);

                fetch(handlerUpload, {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            SAlert.show('File uploaded and extracted successfully.', 'success');
                        } else {
                            SAlert.show(data.message || 'Upload failed', 'error');
                        }
                    })
                    .catch(error => {
                        SAlert.show(String(error), 'error');
                    });
            }

            events() {
                // Trigger file input click on placeholder click
                this.uploadPlaceholder.addEventListener('click', () => {
                    this.browserFile();
                });

                // Handle file selection
                this.fileInput.addEventListener('change', () => {
                    this.fileChangeHandler();
                });

                this.uploadButton.addEventListener('click', () => {
                    this.uploadFile();
                });
            }
        }

    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js" integrity="sha512-2rNj2KJ+D8s1ceNasTIex6z4HWyOnEYLVC3FigGOmyQCZc2eBXKgOxQmo3oKLHyfcj53uz4QMsRCWNbLd32Q1g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
    <script>
        var tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
        var tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
    </script>

    <script>
        $(() => {
            $(document).on('click', '[data-control="change-template"]', function(e) {
                e.preventDefault();        
                var url = $(this).attr('href');
                var template_uid = $(this).attr('data-id');

                // 
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        template_uid: template_uid,
                    },
                    statusCode: {
                        // validate error
                        400: function (res) {
                            SAlert.show('Something went wrong!', 'error');
                        }
                    },
                    success: function (response) {
                        window.location.reload();
                    }
                });
            });
        })
    </script>

    {{-- (Sin sistema de plugins/Hook en este módulo: bloque body.before_close omitido.) --}}

    @include('campaign::builder._change_theme_popup', [
        'templates' => $templates,
        'changeTemplateUrl' => $changeTemplateUrl,
    ])
</body>

</html>