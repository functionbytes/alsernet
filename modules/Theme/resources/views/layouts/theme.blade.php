<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="green" data-layout="vertical" data-boxed-layout="boxed" data-card="shadow">

<head>

    <meta http-equiv="content-type" content="text/html;charset=UTF-8"/>
    <meta charset="utf-8"/>
    <title>{{ getSiteTitle() }}</title>
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no"/>
    <link rel="apple-touch-icon" href="pages/ico/60.png">
    <link rel="apple-touch-icon" sizes="76x76" href="pages/ico/76.png">
    <link rel="apple-touch-icon" sizes="120x120" href="pages/ico/120.png">
    <link rel="apple-touch-icon" sizes="152x152" href="pages/ico/152.png">
    <link rel="icon" type="image/x-icon" href="favicon.ico"/>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta content="Meet pages - The simplest and fastest way to build web UI for your dashboard or app."
          name="description"/>
    <meta content="Ace" name="author"/>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() ?? '' }}">

    <!-- Library CSS -->
    <link rel="stylesheet" href="{{ themeAsset('images/taginput/bootstrap-tagsinput.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/owl.carousel/dist/assets/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/select2/dist/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/quill/dist/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/dropzone/dist/min/dropzone.min.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/daterangepicker/daterangepicker.css') }}">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ themeAsset('libs/fontawesome/fontawesome.css') }}">

    <!-- Theme CSS -->
    <link rel="stylesheet" href="{{ themeAsset('css/style.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('css/extra.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ themeAsset('css/fontawesome.min.css') }}">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">

    <link rel="stylesheet" href="{{ url('core/tooltipster/css/tooltipster.bundle.min.css') }}">
    <link rel="stylesheet" href="{{ url('core/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-light.min.css') }}">
    <link rel="stylesheet" href="{{ url('core/css/google-font-icon.css') }}">

    @stack('css')
    @stack('scripts-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])


</head>

<body class="" data-sidebartype="full" >

<div id="main-wrapper">

    @include ('theme.includes.nav')

    <div class="page-wrapper">

        @include ('theme.includes.header')

        <div class="body-wrapper">

            <div class="container-fluid">
                @yield('content')
            </div>

            @include ('core::components.delete')

        </div>

    </div>

</div>

<!-- Core Libraries -->
<script src="{{ themeAsset('libs/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ themeAsset('libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ themeAsset('libs/simplebar/dist/simplebar.min.js') }}"></script>

<!-- Form/Input Libraries -->
<script src="{{ themeAsset('libs/taginput/bootstrap-tagsinput.js') }}"></script>
<script src="{{ themeAsset('libs/bootstrap-material-datetimepicker/node_modules/moment/moment.js') }}"></script>
<script src="{{ themeAsset('libs/select2/dist/js/select2.min.js') }}"></script>
<script src="{{ themeAsset('libs/jquery-validation/dist/jquery.validate.min.js') }}"></script>
<script src="{{ themeAsset('libs/dropzone/dist/dropzone.js') }}"></script>
<script src="{{ themeAsset('libs/toastr/toastr.min.js') }}"></script>
<script src="{{ themeAsset('libs/quill/dist/quill.min.js') }}"></script>

<!-- Theme & App Scripts (orden importante) -->
<script src="{{ themeAsset('js/theme/app.init.js') }}"></script>
<script src="{{ themeAsset('js/theme/theme.js') }}"></script>
<script src="{{ themeAsset('js/theme/app.min.js') }}"></script>
<script src="{{ themeAsset('js/theme/sidebarmenu.js') }}"></script>

<!-- Form Initializers -->
<script src="{{ themeAsset('js/forms/select2.init.js') }}"></script>
<script src="{{ themeAsset('js/forms/quill-init.js') }}"></script>

<!-- Core App Scripts -->
<script src="{{ url('core/tooltipster/js/tooltipster.bundle.min.js') }}"></script>
<script src="{{ url('core/js/functions.js') }}"></script>
<script src="{{ url('core/js/link.js') }}"></script>
<script src="{{ url('core/js/box.js') }}"></script>
<script src="{{ url('core/js/popup.js') }}"></script>
<script src="{{ url('core/js/list.js') }}"></script>
<script src="{{ url('core/js/anotify.js') }}"></script>
<script src="{{ url('core/js/dialog.js') }}"></script>
<script src="{{ url('core/js/iframe_modal.js') }}"></script>
<script src="{{ url('core/js/search.js') }}"></script>
<script src="{{ url('core/js/image_popup.js') }}"></script>
<script src="{{ url('core/js/app.js') }}"></script>


<script>
    $(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        // Toastr Global Configuration
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-bottom-right",
            timeOut: 5000,
            extendedTimeOut: 1000,
            showEasing: "swing",
            hideEasing: "linear",
            showMethod: "fadeIn",
            hideMethod: "fadeOut"
        };
    });
</script>

<!-- Sidebar Navigation & Utilities -->
<script>
    "use strict";
    $(function () {
        // Initialize Bootstrap tooltips
        function initializeTooltips() {
            if (typeof bootstrap !== 'undefined') {
                // Remove old tooltip elements from DOM
                document.querySelectorAll('.tooltip').forEach(el => el.remove());

                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                    // Dispose existing instance if it exists
                    const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                    if (existingTooltip) {
                        existingTooltip.dispose();
                    }
                    // Create new instance
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        }

        // Initialize tooltips on page load
        initializeTooltips();

        // Reinitialize tooltips when sidebar changes (dispatched from nav.blade.php)
        document.addEventListener('sidebarChanged', function() {
            initializeTooltips();
        });

        // Scroll to active sidebar link
        function scrollToActiveSidebarLink() {
            const activeLink = document.querySelector('.sidebar-link.active');
            if (activeLink) {
                setTimeout(() => {
                    activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
        }

        // Scroll to active link on page load
        scrollToActiveSidebarLink();

        // Fix: Remove 'close' class from sidebarmenu when in full mode on page load
        const sidebarType = document.body.getAttribute('data-sidebartype');
        if (sidebarType === 'full') {
            document.querySelectorAll('.sidebarmenu').forEach(menu => {
                menu.classList.remove('close');
            });
        }

        // Delete confirmation modal
        function deleteConfirmation() {
            $(".confirm-delete").click(function (e) {
                e.preventDefault();
                const url = $(this).data("href");
                $("#delete-modal").modal("show");
                $("#delete-form").attr("action", url);
            });
        }

        deleteConfirmation();

        // Fix navbar visibility on mobile
        const navbarNav = document.getElementById('navbarNav');
        if (navbarNav) {
            navbarNav.addEventListener('shown.bs.collapse', function () {
                this.style.visibility = 'visible';
            });
            navbarNav.addEventListener('show.bs.collapse', function () {
                this.style.visibility = 'visible';
            });
        }
    });
</script>

@stack('scripts')

</body>

</html>
