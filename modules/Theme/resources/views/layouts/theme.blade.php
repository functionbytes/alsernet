<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" data-bs-theme="light" data-color-theme="green" data-layout="vertical" data-boxed-layout="boxed" data-card="shadow">

<head>

    <meta charset="utf-8"/>
    <title>@hasSection('title')@yield('title') · {{ getSiteName() }}@else{{ getSiteTitle() }}@endif</title>
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
    <meta name="googlebot" content="noindex,nofollow,noarchive,nosnippet">
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

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#90bb13">

    @stack('meta')

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
    <link rel="stylesheet" href="{{ themeAsset('css/extra.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('css/fontawesome.min.css') }}">

    <link rel="stylesheet" href="{{ url('core/tooltipster/css/tooltipster.bundle.min.css') }}">
    <link rel="stylesheet" href="{{ url('core/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-light.min.css') }}">
    <link rel="stylesheet" href="{{ url('core/css/google-font-icon.css') }}">

    @stack('css')
    @stack('scripts-head')
    <script>
        (function () {
            var saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {!! \Modules\Core\Models\Setting::get('theme.custom_header_html') !!}
    {!! \Modules\Core\Models\Setting::get('theme.custom_header_js') !!}

    @includeIf('analytics::partials._gtag')

</head>

<body class="" data-sidebartype="full" >

<!-- Global loading indicator -->
<div id="global-loader" class="d-none position-fixed top-0 start-0 w-100" style="z-index:9999; height:3px;">
    <div id="loader-bar" class="h-100" style="width:0%; background:#b10100; transition: width 0.3s ease;"></div>
</div>

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
<script data-pagespeed-no-defer src="{{ themeAsset('libs/jquery/dist/jquery.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/simplebar/dist/simplebar.min.js') }}"></script>

<!-- Form/Input Libraries -->
<script data-pagespeed-no-defer src="{{ themeAsset('libs/taginput/bootstrap-tagsinput.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/bootstrap-material-datetimepicker/node_modules/moment/moment.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/daterangepicker/daterangepicker.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/select2/dist/js/select2.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/jquery-validation/dist/jquery.validate.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/dropzone/dist/dropzone.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/toastr/toastr.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/quill/dist/quill.min.js') }}"></script>

<!-- Theme & App Scripts (orden importante) -->
<script data-pagespeed-no-defer src="{{ themeAsset('js/theme/app.init.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('js/theme/theme.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('js/theme/app.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('js/theme/sidebarmenu.js') }}"></script>

<!-- Form Initializers -->
<script data-pagespeed-no-defer src="{{ themeAsset('js/forms/select2.init.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('js/forms/quill-init.js') }}"></script>
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
<script data-pagespeed-no-defer src="{{ url('core/tooltipster/js/tooltipster.bundle.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/functions.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/link.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/box.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/popup.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/list.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/anotify.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/dialog.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/iframe_modal.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/search.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/image_popup.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/app.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('core/js/bulk.js') }}"></script>
<script data-pagespeed-no-defer src="{{ url('modules/Media/js/media-picker.js') }}"></script>


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

@stack('scripts')

{{-- Global confirm modal (replaces native confirm() on form.needs-confirm) --}}
<div class="modal fade" id="__globalConfirmModal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="__globalConfirmMsg"></div>
            <div class="modal-footer flex-column">
                <button type="button" class="btn btn-primary w-100 mb-2" id="__globalConfirmOk">Confirmar</button>
                <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
<script>
window.__confirm = function (msg, onConfirm) {
    $('#__globalConfirmMsg').text(msg || 'Confirmar accion');
    const modal = new bootstrap.Modal('#__globalConfirmModal');
    modal.show();
    $('#__globalConfirmOk').off('click').on('click', function () {
        modal.hide();
        onConfirm();
    });
};

$(document).on('submit', 'form.needs-confirm', function (e) {
    if (this.dataset.confirmed === '1') { return true; }
    e.preventDefault();
    const form = this;
    window.__confirm(form.dataset.confirmMsg, function () {
        form.dataset.confirmed = '1';
        form.submit();
    });
    $('#__globalConfirmModal').off('hidden.bs.modal.confirm').on('hidden.bs.modal.confirm', function () {
        form.dataset.confirmed = '0';
    });
});
</script>

<script>
$(document).on('change', '.js-auto-submit', function () {
    $(this).closest('form').submit();
});
</script>

@include('media::partials.picker-modal')

@auth
<script>
(function () {
    if (!('serviceWorker' in navigator)) return;

    navigator.serviceWorker.register('/sw.js').then(function (reg) {
        if (!('Notification' in window) || !('PushManager' in reg)) return;
        if (Notification.permission !== 'default') return;
        if (localStorage.getItem('__push_asked')) return;

        setTimeout(function () {
            localStorage.setItem('__push_asked', '1');
            if (typeof toastr !== 'undefined') {
                toastr.info(
                    '<button class="btn btn-sm btn-primary mt-2" onclick="window.__requestPush()">Activar notificaciones</button>',
                    '¿Quieres recibir notificaciones?',
                    { timeOut: 10000, extendedTimeOut: 0, closeButton: true, allowHtml: true }
                );
            }
        }, 30000);
    }).catch(function (err) {
        console.warn('[PWA] SW registration failed', err);
    });

    window.__requestPush = async function () {
        const reg = await navigator.serviceWorker.ready;
        const perm = await Notification.requestPermission();
        if (perm !== 'granted') return;

        try {
            const vapidPublicKey = '{{ env("VAPID_PUBLIC_KEY", "") }}';
            if (!vapidPublicKey) {
                alert('VAPID key no configurada. El administrador debe agregar VAPID_PUBLIC_KEY en .env');
                return;
            }
            const subscription = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });
            await fetch('/panel/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
                body: JSON.stringify(subscription),
            });
            toastr.success('Notificaciones activadas');
        } catch (e) {
            console.error('[PWA] Push subscribe failed', e);
            toastr.error('No se pudo activar las notificaciones');
        }
    };

    function urlBase64ToUint8Array(base64) {
        const padding = '='.repeat((4 - base64.length % 4) % 4);
        const b64 = (base64 + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = window.atob(b64);
        return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
    }
}());
</script>
@endauth

{{-- Cookie banner is for public pages only — loaded in template::partials.footer --}}

{!! \Modules\Core\Models\Setting::get('theme.custom_footer_html') !!}
{!! \Modules\Core\Models\Setting::get('theme.custom_footer_js') !!}

</body>

</html>
