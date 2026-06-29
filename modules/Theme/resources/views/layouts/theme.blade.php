<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="light"
      data-color-scheme="default">
<head>

    {{-- ─── FOUC: dark mode pre-paint ────────────────────────── --}}
    <script>
    (function(){
        var t = localStorage.getItem('mc-theme');
        if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
        if (t === 'dark' || t === 'light') document.documentElement.setAttribute('data-theme', t);
        // Bootstrap compatibility
        document.documentElement.setAttribute('data-bs-theme', (t === 'dark') ? 'dark' : 'light');
    })();
    </script>

    {{-- ─── FOUC: sidebar collapsed state ────────────────────── --}}
    <script>
    (function(){
        if (window.innerWidth > 1199 && localStorage.getItem('mc-sidebar-collapsed') === 'true')
            document.documentElement.setAttribute('data-sidebar-collapsed', 'true');
    })();
    </script>

    <meta charset="utf-8"/>
    <title>@hasSection('title')@yield('title') · {{ getSiteName() }}@else{{ getSiteTitle() }}@endif</title>
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
    <meta name="googlebot" content="noindex,nofollow,noarchive,nosnippet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no"/>
    <link rel="icon" type="image/x-icon" href="/favicon.ico"/>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() ?? '' }}">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#90bb13">

    @stack('meta')

    {{-- ─── Google Fonts: Inter ────────────────────────────────── --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Material Symbols — omit CDN in local, fallback via Font Awesome --}}

    {{-- ─── Library CSS ─────────────────────────────────────────── --}}
    <link rel="stylesheet" href="{{ themeAsset('libs/simplebar/dist/simplebar.min.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/taginput/bootstrap-tagsinput.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/owl.carousel/dist/assets/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/select2/dist/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/quill/dist/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/dropzone/dist/min/dropzone.min.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/daterangepicker/daterangepicker.css') }}">
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="{{ themeAsset('libs/fontawesome/fontawesome.css') }}">

    {{-- ─── Content area CSS (legacy, Bootstrap-based components) ─ --}}
    <link rel="stylesheet" href="{{ themeAsset('css/style.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('css/nav.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('css/extra.css') }}">

    {{-- ─── Acelle Shell CSS (overrides shell from style.css) ───── --}}
    <link rel="stylesheet" href="{{ themeAsset('acelle/css/variables.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('acelle/css/app.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('acelle/css/layouts.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('acelle/css/components.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('acelle/css/notifications.css') }}">

    {{-- ─── Brand overrides (primary color #90bb13) ───────────────── --}}
    <link rel="stylesheet" href="{{ themeAsset('acelle/css/theme-overrides.css') }}">

    {{-- ─── Tooltipster (keep for legacy tooltips) ───────────────── --}}
    <link rel="stylesheet" href="{{ url('core/tooltipster/css/tooltipster.bundle.min.css') }}">
    <link rel="stylesheet" href="{{ url('core/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-light.min.css') }}">

    @stack('css')
    @stack('scripts-head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {!! \Modules\Core\Models\Setting::get('theme.custom_header_html') !!}
    {!! \Modules\Core\Models\Setting::get('theme.custom_header_js') !!}

    @includeIf('analytics::partials._gtag')

</head>

<body>

{{-- ─── Global loading bar ─────────────────────────────────── --}}
<div id="global-loader" class="d-none position-fixed top-0 start-0 w-100" style="z-index:9999;height:3px;">
    <div id="loader-bar" class="h-100" style="width:0%;background:var(--color-teal);transition:width 0.3s ease;"></div>
</div>

{{-- ─── App Shell ───────────────────────────────────────────── --}}
<div class="mc-app" id="mc-app">

    @include('theme.includes.nav')

    <div class="mc-sidebar-overlay"></div>

    <div class="mc-app-body app-wrapper">

        @include('theme.includes.header')

        @hasSection('page_header')
            @yield('page_header')
        @endif

        <main class="mc-content">
            @php $__fullWidth = $__env->hasSection('content_full_width'); @endphp
            @if(!$__fullWidth)
                <div class="mc-content-inner">
            @endif
                @yield('content')
            @if(!$__fullWidth)
                </div>
            @endif
            @include('core::components.delete')
        </main>

    </div>

</div>

{{-- ─── Core Libraries ──────────────────────────────────────── --}}
<script data-pagespeed-no-defer src="{{ themeAsset('libs/jquery/dist/jquery.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/simplebar/dist/simplebar.min.js') }}"></script>

{{-- ─── Form / Input Libraries ─────────────────────────────── --}}
<script data-pagespeed-no-defer src="{{ themeAsset('libs/taginput/bootstrap-tagsinput.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/bootstrap-material-datetimepicker/node_modules/moment/moment.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/daterangepicker/daterangepicker.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/select2/dist/js/select2.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/jquery-validation/dist/jquery.validate.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/dropzone/dist/dropzone.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/toastr/toastr.min.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('libs/quill/dist/quill.min.js') }}"></script>

{{-- ─── Acelle Shell JS ─────────────────────────────────────── --}}
<script data-pagespeed-no-defer src="{{ themeAsset('acelle/js/Theme.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('acelle/js/ColorScheme.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('acelle/js/Sidebar.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('acelle/js/Notify.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('acelle/js/Dropdown.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('acelle/js/Tooltip.js') }}"></script>
<script data-pagespeed-no-defer src="{{ themeAsset('acelle/js/app.js') }}"></script>

{{-- ─── Legacy Core App Scripts ───────────────────────────────── --}}
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

{{-- ─── Bootstrap 5 + data-bs-theme sync ─────────────────────── --}}
<script>
(function () {
    // Keep Bootstrap's data-bs-theme in sync with Acelle's data-theme
    var root = document.documentElement;
    new MutationObserver(function (muts) {
        muts.forEach(function (m) {
            if (m.attributeName === 'data-theme') {
                root.setAttribute('data-bs-theme', root.getAttribute('data-theme') || 'light');
            }
        });
    }).observe(root, { attributes: true, attributeFilter: ['data-theme'] });
    root.setAttribute('data-bs-theme', root.getAttribute('data-theme') || 'light');
})();
</script>

{{-- ─── jQuery CSRF + Toastr config ───────────────────────────── --}}
<script>
$(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-bottom-right',
        timeOut: 5000,
        extendedTimeOut: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };

    // DateRangePicker global init
    if ($.fn.daterangepicker && $('.daterange').length) {
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Limpiar', applyLabel: 'Aplicar', format: 'DD/MM/YYYY',
                separator: ' - ',
                daysOfWeek: ['Do','Lu','Ma','Mi','Ju','Vi','Sa'],
                monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio',
                             'Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
                firstDay: 1,
            },
            ranges: {
                'Hoy':             [moment(), moment()],
                'Ayer':            [moment().subtract(1,'days'), moment().subtract(1,'days')],
                'Últimos 7 días':  [moment().subtract(6,'days'), moment()],
                'Últimos 30 días': [moment().subtract(29,'days'), moment()],
                'Este mes':        [moment().startOf('month'), moment().endOf('month')],
                'Mes anterior':    [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')],
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

    // Delete confirmation modal
    $(document).on('click', '.confirm-delete', function (e) {
        e.preventDefault();
        const url = $(this).data('href');
        $('#delete-modal').modal('show');
        $('#delete-form').attr('action', url);
    });

    $(document).on('submit', '#delete-form', function (e) {
        e.preventDefault();
        const form = $(this);
        const btn = form.find('button[type="submit"]');
        btn.prop('disabled', true);
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                $('#delete-modal').modal('hide');
                if (typeof toastr !== 'undefined') {
                    toastr.success(data.message || 'Eliminado exitosamente');
                }
                setTimeout(function () { window.location.reload(); }, 600);
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                $('#delete-modal').modal('hide');
                const msg = xhr.responseJSON?.message || 'No se pudo eliminar';
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                }
            },
        });
    });
});
</script>

{{-- ─── Global loading indicator ──────────────────────────────── --}}
<script>
(function () {
    var loader = document.getElementById('global-loader');
    var bar    = document.getElementById('loader-bar');

    function showLoader() {
        loader && loader.classList.remove('d-none');
        bar && (bar.style.width = '70%');
    }
    function hideLoader() {
        if (!bar) return;
        bar.style.width = '100%';
        setTimeout(function () {
            loader && loader.classList.add('d-none');
            bar.style.width = '0%';
        }, 300);
    }

    if (typeof $ !== 'undefined') {
        $(document).ajaxStart(showLoader).ajaxStop(hideLoader);
    }
    document.addEventListener('submit', showLoader);
})();
</script>

{{-- ─── Auto-submit selects ────────────────────────────────────── --}}
<script>
$(document).on('change', '.js-auto-submit', function () {
    $(this).closest('form').submit();
});
</script>

{{-- ─── Global confirm modal ───────────────────────────────────── --}}
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
    if (this.dataset.confirmed === '1') return true;
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

@stack('scripts')

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
    }).catch(function (err) { console.warn('[PWA] SW registration failed', err); });

    window.__requestPush = async function () {
        const reg  = await navigator.serviceWorker.ready;
        const perm = await Notification.requestPermission();
        if (perm !== 'granted') return;
        try {
            const vapidPublicKey = '{{ env("VAPID_PUBLIC_KEY", "") }}';
            if (!vapidPublicKey) { alert('VAPID key no configurada.'); return; }
            const subscription = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });
            await fetch('/panel/push/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                body: JSON.stringify(subscription),
            });
            toastr.success('Notificaciones activadas');
        } catch (e) { toastr.error('No se pudo activar las notificaciones'); }
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

{!! \Modules\Core\Models\Setting::get('theme.custom_footer_html') !!}
{!! \Modules\Core\Models\Setting::get('theme.custom_footer_js') !!}

</body>

</html>
