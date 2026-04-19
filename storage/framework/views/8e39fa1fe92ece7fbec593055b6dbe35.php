<!DOCTYPE html>
<html lang="<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Core\Models\Setting;

echo e(str_replace('_', '-', app()->getLocale())); ?>" dir="ltr" data-bs-theme="light" data-color-theme="green" data-layout="vertical" data-boxed-layout="boxed" data-card="shadow">

<head>

    <meta charset="utf-8"/>
    <title><?php echo e(getSiteTitle()); ?></title>
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

    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="user-id" content="<?php echo e(auth()->id() ?? ''); ?>">

    <?php echo $__env->yieldPushContent('meta'); ?>

    <!-- Library CSS -->
    <link rel="stylesheet" href="<?php echo e(themeAsset('images/taginput/bootstrap-tagsinput.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/owl.carousel/dist/assets/owl.carousel.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/select2/dist/css/select2.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/quill/dist/quill.snow.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/toastr/toastr.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/dropzone/dist/min/dropzone.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/daterangepicker/daterangepicker.css')); ?>">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/fontawesome/fontawesome.css')); ?>">

    <!-- Theme CSS -->
    <link rel="stylesheet" href="<?php echo e(themeAsset('css/style.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('css/extra.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('css/fontawesome.min.css')); ?>">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">

    <link rel="stylesheet" href="<?php echo e(url('core/tooltipster/css/tooltipster.bundle.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(url('core/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-light.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(url('core/css/google-font-icon.css')); ?>">

    <?php echo $__env->yieldPushContent('css'); ?>
    <?php echo $__env->yieldPushContent('scripts-head'); ?>
    <script>
        (function () {
            var saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    <?php echo Setting::get('theme.custom_header_html'); ?>

    <?php echo Setting::get('theme.custom_header_js'); ?>


    <?php if ($__env->exists('analytics::partials._gtag')) {
        echo $__env->make('analytics::partials._gtag', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render();
    } ?>

</head>

<body class="" data-sidebartype="full" >

<!-- Global loading indicator -->
<div id="global-loader" class="d-none position-fixed top-0 start-0 w-100" style="z-index:9999; height:3px;">
    <div id="loader-bar" class="h-100" style="width:0%; background:#b10100; transition: width 0.3s ease;"></div>
</div>

<div id="main-wrapper">

    <?php echo $__env->make('theme.includes.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="page-wrapper">

        <?php echo $__env->make('theme.includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="body-wrapper">

            <div class="container-fluid">
                <?php echo $__env->yieldContent('content'); ?>
            </div>

            <?php echo $__env->make('core::components.delete', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        </div>

    </div>

</div>

<!-- Core Libraries -->
<script src="<?php echo e(themeAsset('libs/jquery/dist/jquery.min.js')); ?>"></script>
<script src="<?php echo e(themeAsset('libs/bootstrap/dist/js/bootstrap.bundle.min.js')); ?>"></script>
<script src="<?php echo e(themeAsset('libs/simplebar/dist/simplebar.min.js')); ?>"></script>

<!-- Form/Input Libraries -->
<script src="<?php echo e(themeAsset('libs/taginput/bootstrap-tagsinput.js')); ?>"></script>
<script src="<?php echo e(themeAsset('libs/bootstrap-material-datetimepicker/node_modules/moment/moment.js')); ?>"></script>
<script src="<?php echo e(themeAsset('libs/daterangepicker/daterangepicker.js')); ?>"></script>
<script src="<?php echo e(themeAsset('libs/select2/dist/js/select2.min.js')); ?>"></script>
<script src="<?php echo e(themeAsset('libs/jquery-validation/dist/jquery.validate.min.js')); ?>"></script>
<script src="<?php echo e(themeAsset('libs/dropzone/dist/dropzone.js')); ?>"></script>
<script src="<?php echo e(themeAsset('libs/toastr/toastr.min.js')); ?>"></script>
<script src="<?php echo e(themeAsset('libs/quill/dist/quill.min.js')); ?>"></script>

<!-- Theme & App Scripts (orden importante) -->
<script src="<?php echo e(themeAsset('js/theme/app.init.js')); ?>"></script>
<script src="<?php echo e(themeAsset('js/theme/theme.js')); ?>"></script>
<script src="<?php echo e(themeAsset('js/theme/app.min.js')); ?>"></script>
<script src="<?php echo e(themeAsset('js/theme/sidebarmenu.js')); ?>"></script>

<!-- Form Initializers -->
<script src="<?php echo e(themeAsset('js/forms/select2.init.js')); ?>"></script>
<script src="<?php echo e(themeAsset('js/forms/quill-init.js')); ?>"></script>
<script>
$(document).ready(function () {
    if ($.fn.daterangepicker && $('.daterange').length) {
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Limpiar',
                applyLabel: 'Aplicar',
                format: 'DD/MM/YYYY',
                separator: ' - ',
                daysOfWeek: ['Do','Lu','Ma','Mi','Ju','Vi','Sa'],
                monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
                firstDay: 1,
            },
            ranges: {
                'Hoy':            [moment(), moment()],
                'Ayer':           [moment().subtract(1,'days'), moment().subtract(1,'days')],
                'Últimos 7 días': [moment().subtract(6,'days'), moment()],
                'Últimos 30 días':[moment().subtract(29,'days'), moment()],
                'Este mes':       [moment().startOf('month'), moment().endOf('month')],
                'Mes anterior':   [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')],
            },
        });

        $('.daterange').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            $(this).trigger('daterange:applied', [picker.startDate, picker.endDate]);
        });

        $('.daterange').on('cancel.daterangepicker', function () {
            $(this).val('').trigger('daterange:cleared');
        });
    }
});
</script>

<!-- Core App Scripts -->
<script src="<?php echo e(url('core/tooltipster/js/tooltipster.bundle.min.js')); ?>"></script>
<script src="<?php echo e(url('core/js/functions.js')); ?>"></script>
<script src="<?php echo e(url('core/js/link.js')); ?>"></script>
<script src="<?php echo e(url('core/js/box.js')); ?>"></script>
<script src="<?php echo e(url('core/js/popup.js')); ?>"></script>
<script src="<?php echo e(url('core/js/list.js')); ?>"></script>
<script src="<?php echo e(url('core/js/anotify.js')); ?>"></script>
<script src="<?php echo e(url('core/js/dialog.js')); ?>"></script>
<script src="<?php echo e(url('core/js/iframe_modal.js')); ?>"></script>
<script src="<?php echo e(url('core/js/search.js')); ?>"></script>
<script src="<?php echo e(url('core/js/image_popup.js')); ?>"></script>
<script src="<?php echo e(url('core/js/app.js')); ?>"></script>
<script src="<?php echo e(url('core/js/bulk.js')); ?>"></script>
<script src="<?php echo e(url('modules/Media/js/media-picker.js')); ?>"></script>


<script>
    $(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
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

<script>
(function () {
    // Dark mode toggle
    var saved = localStorage.getItem('theme') || 'light';
    var icon = document.getElementById('darkModeIcon');
    if (icon) {
        icon.className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    var btn = document.getElementById('darkModeToggle');
    if (btn) {
        btn.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            document.getElementById('darkModeIcon').className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
    }

    // Global loading indicator
    var loader = document.getElementById('global-loader');
    var bar = document.getElementById('loader-bar');

    function showLoader() {
        loader.classList.remove('d-none');
        bar.style.width = '70%';
    }

    function hideLoader() {
        bar.style.width = '100%';
        setTimeout(function () {
            loader.classList.add('d-none');
            bar.style.width = '0%';
        }, 300);
    }

    if (typeof $ !== 'undefined') {
        $(document).ajaxStart(showLoader).ajaxStop(hideLoader);
    }

    document.addEventListener('submit', showLoader);
})();
</script>

<?php echo $__env->yieldPushContent('scripts'); ?>

<script>
$(document).on('change', '.js-auto-submit', function () {
    $(this).closest('form').submit();
});
</script>

<?php echo $__env->make('media::partials.picker-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (Setting::get('cookie.enabled') === '1' && ! request()->is('setting/*', 'managers/*')) { ?>
    <?php echo $__env->make('cookie::index', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link rel="stylesheet" href="<?php echo e(url('modules/Cookie/css/cookie-consent.css')); ?>">
    <script src="<?php echo e(url('modules/Cookie/js/cookie-consent.js')); ?>"></script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php echo Setting::get('theme.custom_footer_html'); ?>

<?php echo Setting::get('theme.custom_footer_js'); ?>


</body>

</html>
<?php /**PATH /Users/developerts/Herd/system/modules/Theme/resources/views/layouts/theme.blade.php ENDPATH**/ ?>