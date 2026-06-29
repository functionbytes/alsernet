<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-color-scheme="default" data-scheme-context="admin">
<head>
    {{-- Blocking: apply dark mode BEFORE any paint to prevent flash --}}
    <script>
        (function(){var t=localStorage.getItem('mc-theme');if(!t&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)t='dark';if(t)document.documentElement.setAttribute('data-theme',t);})();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin')</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('refactor/images/favicon.svg') }}" type="image/svg+xml">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Material Symbols Rounded --}}
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    {{-- Refactor framework CSS --}}
    <link href="{{ asset('refactor/css/variables.css') }}" rel="stylesheet">
    <link href="{{ asset('refactor/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('refactor/css/components.css') }}" rel="stylesheet">
    <link href="{{ asset('refactor/css/layouts.css') }}" rel="stylesheet">
    <link href="{{ asset('refactor/css/power-search.css') }}" rel="stylesheet">
    <link href="{{ asset('refactor/css/notifications.css') }}" rel="stylesheet">

    @stack('styles')
    @yield('head')
</head>
<body>
    <div class="mc-app" id="mc-app">
        {{-- Main content area --}}
        <div class="mc-app-body">
            {{-- Minimal topbar: page title + back link --}}
            <header class="mc-topbar">
                <div class="mc-topbar-inner" style="display:flex;align-items:center;gap:var(--space-3);padding:0 var(--space-5);">
                    <a href="{{ route('manager.page_templates.index') }}"
                       class="mc-btn mc-btn-secondary mc-btn-sm"
                       title="{{ trans('campaign::page-templates.create.back_to_index') }}">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                    </a>
                    <span class="mc-topbar-title" style="font-weight:var(--font-semibold);font-size:var(--text-base);">@yield('title', 'Admin')</span>
                </div>
            </header>

            {{-- Page header (edge-to-edge white bar) --}}
            @hasSection('page-header')
                <div class="mc-content-header">
                    <div class="mc-content-header-inner">
                        @yield('page-header')
                    </div>
                </div>
            @endif

            {{-- Content --}}
            <main class="mc-content @yield('content-class')">
                <div class="mc-content-inner">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    {{-- Flash messages for Notify --}}
    <div data-flash-messages class="mc-hidden">
        @if (session('success'))
            <span data-flash="success">{{ session('success') }}</span>
        @endif
        @if (session('error'))
            <span data-flash="error">{{ session('error') }}</span>
        @endif
        @if (session('warning'))
            <span data-flash="warning">{{ session('warning') }}</span>
        @endif
        @foreach (['alert-success', 'alert-info', 'alert-warning', 'alert-danger', 'alert-error'] as $msg)
            @if (Session::has($msg))
                <span data-flash="{{ str_replace('alert-', '', $msg) }}">{{ Session::get($msg) }}</span>
            @endif
        @endforeach
    </div>

    {{-- JS --}}
    <script src="{{ asset('refactor/js/Theme.js') }}"></script>
    <script src="{{ asset('refactor/js/ColorScheme.js') }}"></script>
    <script src="{{ asset('refactor/js/Sidebar.js') }}"></script>
    <script src="{{ asset('refactor/js/Notify.js') }}"></script>
    <script src="{{ asset('refactor/js/Dropdown.js') }}"></script>
    <script src="{{ asset('refactor/js/List.js') }}"></script>
    <script src="{{ asset('refactor/js/ColumnPicker.js') }}"></script>
    <script src="{{ asset('refactor/js/MultiSelect.js') }}"></script>
    <script src="{{ asset('refactor/js/Form.js') }}"></script>
    <script src="{{ asset('refactor/js/Popup.js') }}"></script>
    <script src="{{ asset('refactor/js/PreviewModal.js') }}"></script>
    <script src="{{ asset('refactor/js/Dialog.js') }}"></script>
    <script src="{{ asset('refactor/js/RichSelect.js') }}"></script>
    <script src="{{ asset('refactor/js/Tabs.js') }}"></script>
    <script src="{{ asset('refactor/js/FileUpload.js') }}"></script>
    <script src="{{ asset('refactor/js/InlineEdit.js') }}"></script>
    <script src="{{ asset('refactor/js/Tooltip.js') }}"></script>
    <script src="{{ asset('refactor/js/PowerSearch.js') }}"></script>
    <script src="{{ asset('refactor/js/app.js') }}"></script>
    <script src="{{ asset('refactor/js/notifications.js') }}"></script>

    @stack('scripts')
    @yield('scripts')
</body>
</html>
