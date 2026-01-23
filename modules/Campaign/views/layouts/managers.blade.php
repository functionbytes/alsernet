@php
    use Illuminate\Support\Facades\Session;
@endphp

    <!DOCTYPE html>

<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="green" data-layout="vertical" data-boxed-layout="boxed" data-card="shadow">

<head>

    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <meta charset="utf-8" />
    <title>INOQUALAB - E-Learning</title>
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no" />
    <link rel="apple-touch-icon" href="pages/ico/60.png">
    <link rel="apple-touch-icon" sizes="76x76" href="pages/ico/76.png">
    <link rel="apple-touch-icon" sizes="120x120" href="pages/ico/120.png">
    <link rel="apple-touch-icon" sizes="152x152" href="pages/ico/152.png">
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta content="Meet pages - The simplest and fastest way to build web UI for your dashboard or app." name="description" />
    <meta content="Ace" name="author" />


    <meta name="csrf-token" content="{{ csrf_token() }}">


    <!-- Library CSS -->
    <link rel="stylesheet" href="{{ url('theme/libs/taginput/bootstrap-tagsinput.css') }}">
    <link rel="stylesheet" href="{{ url('theme/libs/owl.carousel/dist/assets/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ url('theme/libs/select2/dist/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ url('theme/libs/quill/dist/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ url('theme/libs/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{ url('theme/libs/dropzone/dist/min/dropzone.min.css') }}">
    <link rel="stylesheet" href="{{ url('theme/libs/daterangepicker/daterangepicker.css') }}">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ url('theme/libs/fontawesome/fontawesome.css') }}"

    <!-- Theme CSS -->
    <link rel="stylesheet" href="{{ url('theme/css/style.css') }}">
    <link rel="stylesheet" href="{{ url('theme/css/extra.css') }}">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">

    <link rel="stylesheet" href="{{ url('core/tooltipster/css/tooltipster.bundle.min.css') }}">
    <link rel="stylesheet" href="{{ url('core/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-light.min.css') }}">
    <link rel="stylesheet" href="{{ url('core/css/google-font-icon.css') }}">


    @stack('css')
    @stack('scripts-head')


</head>

<body class="">

<div
    id="main-wrapper"
>

<div
    class="page-wrapper"
>


    @include ('theme.components.nav')

    <!-- Main wrapper -->

    <div class="body-wrapper">

        @include ('theme.components.header')

        <div class="container-fluid">
            @yield('content')
        </div>

        @include ('core::components.delete')

    </div>

</div>

</div>

<!-- Core Libraries -->
<script src="{{ url('theme/libs/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ url('theme/libs/simplebar/dist/simplebar.min.js') }}"></script>
<script src="{{ url('theme/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ url('core/tooltipster/js/tooltipster.bundle.min.js') }}"></script>

<!-- Form/Input Libraries -->
<script src="{{ url('theme/libs/taginput/bootstrap-tagsinput.js') }}"></script>
<script src="{{ url('theme/libs/bootstrap-material-datetimepicker/node_modules/moment/moment.js') }}"></script>
<script src="{{ url('theme/libs/select2/dist/js/select2.min.js') }}"></script>
<script src="{{ url('theme/libs/jquery-validation/dist/jquery.validate.min.js') }}"></script>
<script src="{{ url('theme/libs/dropzone/dist/dropzone.js') }}"></script>
<script src="{{ url('theme/libs/toastr/toastr.min.js') }}"></script>
<script src="{{ url('theme/libs/quill/dist/quill.min.js') }}"></script>

<!-- Theme & App Scripts -->
<script src="{{ url('theme/js/theme/theme.js') }}"></script>

<!-- Form Initializers -->
<script src="{{ url('theme/js/forms/select2.init.js') }}"></script>
<script src="{{ url('theme/js/forms/quill-init.js') }}"></script>

<!-- Core App Scripts -->
<script src="{{ url('core/js/functions.js') }}"></script>
<script src="{{ url('core/js/link.js') }}"></script>
<script src="{{ url('core/js/box.js') }}"></script>
<script src="{{ url('core/js/popup.js') }}"></script>
<script src="{{ url('core/js/sidebar.js') }}"></script>
<script src="{{ url('core/js/list.js') }}"></script>
<script src="{{ url('core/js/anotify.js') }}"></script>
<script src="{{ url('core/js/dialog.js') }}"></script>
<script src="{{ url('core/js/iframe_modal.js') }}"></script>
<script src="{{ url('core/js/search.js') }}"></script>
<script src="{{ url('core/js/image_popup.js') }}"></script>
<script src="{{ url('core/js/app.js') }}"></script>

<script>
    $(function() {
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

<!-- Sidebar Mini-Nav and Menu Interaction -->
<script>
    "use strict";
    $(function () {
        // Sidebar Mini-Nav functionality
        const miniNavItems = $('.mini-nav-item');
        const navMenus = $('.sidebarmenu nav');

        // Handle mini-nav-item click
        miniNavItems.on('click', 'a', function (e) {
            e.preventDefault();

            const miniNavItem = $(this).closest('.mini-nav-item');
            const miniId = miniNavItem.attr('id'); // e.g., "mini-1"
            const menuNumber = miniId.replace('mini-', ''); // e.g., "1"
            const targetMenu = '#menu-right-mini-' + menuNumber;

            // Remove selected class from all mini-nav-items
            miniNavItems.removeClass('selected');

            // Add selected class to clicked mini-nav-item
            miniNavItem.addClass('selected');

            // Hide all menus
            navMenus.removeClass('d-block').addClass('d-none');

            // Show target menu
            $(targetMenu).removeClass('d-none').addClass('d-block');

            // Initialize tooltips
            initializeTooltips();
        });

        // Handle sidebar-link has-arrow click
        $(document).on('click', '.sidebar-link.has-arrow', function (e) {
            e.preventDefault();

            // Find which menu contains this link
            const parentNav = $(this).closest('nav');
            const menuId = parentNav.attr('id'); // e.g., "menu-right-mini-1"
            const menuNumber = menuId.replace('menu-right-mini-', ''); // e.g., "1"
            const miniItem = '#mini-' + menuNumber;

            // Mark corresponding mini-nav-item as selected
            miniNavItems.removeClass('selected');
            $(miniItem).addClass('selected');

            // Make sure target menu is visible
            navMenus.removeClass('d-block').addClass('d-none');
            parentNav.removeClass('d-none').addClass('d-block');

            // Toggle submenu
            $(this).parent().toggleClass('open');
            $(this).next('ul').slideToggle();
        });

        // Initialize tooltips for mini-nav
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

        // Initialize on page load
        initializeTooltips();

        // Function to highlight active sidebar-item and its mini-nav
        function highlightActiveSidebarItem() {
            const currentUrl = window.location.href;
            const currentPathname = window.location.pathname;
            let activeLink = null;

            // Find sidebar-link that matches current URL
            const sidebarLinks = document.querySelectorAll('.sidebarmenu .sidebar-link');

            sidebarLinks.forEach(link => {
                const href = link.getAttribute('href');

                if (href && href !== 'javascript:void(0)' && href !== '#') {
                    // Try multiple matching strategies
                    const isMatch =
                        currentUrl.includes(href) ||  // Full URL components href
                        currentPathname === href ||    // Exact path match
                        href === currentPathname + '/' || // Path with trailing slash
                        currentPathname === href + '/';    // Current path with trailing slash

                    if (isMatch) {
                        activeLink = link;
                    }
                }
            });

            if (activeLink) {
                // Remove active class from all sidebar-links
                document.querySelectorAll('.sidebar-link').forEach(link => {
                    link.classList.remove('active');
                });
                // Add active class to current link
                activeLink.classList.add('active');

                // Find the parent nav menu
                const parentNav = activeLink.closest('nav[id^="menu-right-mini-"]');
                if (parentNav) {
                    const navId = parentNav.id;
                    const miniNumber = navId.replace('menu-right-mini-', '');
                    const miniItem = document.getElementById(`mini-${miniNumber}`);

                    if (miniItem) {
                        // Remove selected from all mini-nav-items
                        miniNavItems.removeClass('selected');
                        // Add selected to the active one
                        $(miniItem).addClass('selected');

                        // Hide all menus
                        navMenus.removeClass('d-block').addClass('d-none');
                        // Show the active menu
                        parentNav.classList.remove('d-none');
                        parentNav.classList.add('d-block');

                        // Reinitialize tooltips
                        initializeTooltips();
                    }
                }
            } else {
                // If no active link found, show first menu by default
                $('.mini-nav-item').first().addClass('selected');
                $('#menu-right-mini-1').removeClass('d-none').addClass('d-block');
                navMenus.not('#menu-right-mini-1').removeClass('d-block').addClass('d-none');
            }
        }

        // Check active sidebar item on page load
        highlightActiveSidebarItem();
        scrollToActiveSidebarLink();

        deleteConfirmation();

        // scroll to active sidebar link instantly
        function scrollToActiveSidebarLink() {
            const activeLink = document.querySelector('.sidebar-link.active');
            if (activeLink) {
                const sidebarNav = document.querySelector('.sidebar-nav');
                if (sidebarNav) {
                    // Scroll directly without animation
                    activeLink.scrollIntoView(false);
                }
            }
        }

        // delete confirmation
        function deleteConfirmation() {
            $(".confirm-delete").click(function (e) {
                e.preventDefault();
                const url = $(this).data("href");
                $("#delete-modal").modal("show");
                $("#delete-form").attr("action", url);
            });
        }
    });
</script>

@stack('scripts')

</body>

</html>
