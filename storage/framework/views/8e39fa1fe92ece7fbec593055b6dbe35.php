<!DOCTYPE html>
<html lang="<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Core\Models\Setting;

echo e(str_replace('_', '-', app()->getLocale())); ?>"
      data-theme="light"
      data-color-scheme="default">
<head>

    
    <script>
    (function(){
        var t = localStorage.getItem('mc-theme');
        if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
        if (t === 'dark' || t === 'light') document.documentElement.setAttribute('data-theme', t);
        // Bootstrap compatibility
        document.documentElement.setAttribute('data-bs-theme', (t === 'dark') ? 'dark' : 'light');
    })();
    </script>

    
    <script>
    (function(){
        if (window.innerWidth > 1199 && localStorage.getItem('mc-sidebar-collapsed') === 'true')
            document.documentElement.setAttribute('data-sidebar-collapsed', 'true');
    })();
    </script>

    <meta charset="utf-8"/>
    <title><?php if (! empty(trim($__env->yieldContent('title')))) { ?><?php echo $__env->yieldContent('title'); ?> · <?php echo e(getSiteName()); ?><?php } else { ?><?php echo e(getSiteTitle()); ?><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></title>
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
    <meta name="googlebot" content="noindex,nofollow,noarchive,nosnippet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no"/>
    <link rel="icon" type="image/x-icon" href="/favicon.ico"/>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="user-id" content="<?php echo e(auth()->id() ?? ''); ?>">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#b10100">

    <?php echo $__env->yieldPushContent('meta'); ?>

    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    

    
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/taginput/bootstrap-tagsinput.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/owl.carousel/dist/assets/owl.carousel.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/select2/dist/css/select2.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/quill/dist/quill.snow.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/toastr/toastr.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/dropzone/dist/min/dropzone.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/daterangepicker/daterangepicker.css')); ?>">
    
    <link rel="stylesheet" href="<?php echo e(themeAsset('libs/fontawesome/fontawesome.css')); ?>">

    
    <link rel="stylesheet" href="<?php echo e(themeAsset('css/style.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('css/extra.css')); ?>">

    
    <link rel="stylesheet" href="<?php echo e(themeAsset('acelle/css/variables.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('acelle/css/app.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('acelle/css/layouts.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('acelle/css/components.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(themeAsset('acelle/css/notifications.css')); ?>">

    
    <link rel="stylesheet" href="<?php echo e(themeAsset('acelle/css/theme-overrides.css')); ?>">

    
    <link rel="stylesheet" href="<?php echo e(url('core/tooltipster/css/tooltipster.bundle.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(url('core/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-light.min.css')); ?>">

    <?php echo $__env->yieldPushContent('css'); ?>
    <?php echo $__env->yieldPushContent('scripts-head'); ?>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    <?php echo Setting::get('theme.custom_header_html'); ?>

    <?php echo Setting::get('theme.custom_header_js'); ?>


    <?php if ($__env->exists('analytics::partials._gtag')) {
        echo $__env->make('analytics::partials._gtag', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render();
    } ?>

</head>

<body>


<div id="global-loader" class="d-none position-fixed top-0 start-0 w-100" style="z-index:9999;height:3px;">
    <div id="loader-bar" class="h-100" style="width:0%;background:var(--color-teal);transition:width 0.3s ease;"></div>
</div>


<div class="mc-app" id="mc-app">

    <?php echo $__env->make('theme.includes.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="mc-sidebar-overlay"></div>

    <div class="mc-app-body">

        <?php echo $__env->make('theme.includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php if (! empty(trim($__env->yieldContent('page_header')))) { ?>
            <?php echo $__env->yieldContent('page_header'); ?>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        <main class="mc-content">
            <div class="mc-content-inner">
                <?php echo $__env->yieldContent('content'); ?>
            </div>
            <?php echo $__env->make('core::components.delete', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </main>

    </div>

</div>


<script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/jquery/dist/jquery.min.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/bootstrap/dist/js/bootstrap.bundle.min.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/simplebar/dist/simplebar.min.js')); ?>"></script>


<script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/taginput/bootstrap-tagsinput.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/bootstrap-material-datetimepicker/node_modules/moment/moment.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/daterangepicker/daterangepicker.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/select2/dist/js/select2.min.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/jquery-validation/dist/jquery.validate.min.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/dropzone/dist/dropzone.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/toastr/toastr.min.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('libs/quill/dist/quill.min.js')); ?>"></script>


<script data-pagespeed-no-defer src="<?php echo e(themeAsset('acelle/js/Theme.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('acelle/js/ColorScheme.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('acelle/js/Sidebar.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('acelle/js/Notify.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('acelle/js/Dropdown.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('acelle/js/Tooltip.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(themeAsset('acelle/js/app.js')); ?>"></script>


<script data-pagespeed-no-defer src="<?php echo e(url('core/tooltipster/js/tooltipster.bundle.min.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/functions.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/link.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/box.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/popup.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/list.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/anotify.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/dialog.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/iframe_modal.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/search.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/image_popup.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/app.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('core/js/bulk.js')); ?>"></script>
<script data-pagespeed-no-defer src="<?php echo e(url('modules/Media/js/media-picker.js')); ?>"></script>


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


<script>
$(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
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


<script>
$(document).on('change', '.js-auto-submit', function () {
    $(this).closest('form').submit();
});
</script>


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

<?php echo $__env->yieldPushContent('scripts'); ?>

<?php echo $__env->make('media::partials.picker-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (auth()->guard()->check()) { ?>
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
            const vapidPublicKey = '<?php echo e(env('VAPID_PUBLIC_KEY', '')); ?>';
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
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php echo Setting::get('theme.custom_footer_html'); ?>

<?php echo Setting::get('theme.custom_footer_js'); ?>


</body>

</html>
<?php /**PATH /Users/developerts/Herd/system/modules/Theme/resources/views/layouts/theme.blade.php ENDPATH**/ ?>