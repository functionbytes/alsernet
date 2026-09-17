/**
 * Pantalla settings/social-integrations/index.blade.php.
 * window.HdSocialIntegrationsConfig = { testRoutes: { whatsapp, facebook, instagram } }
 * lo imprime el Blade. Los botones usan data-platform/data-clipboard-target
 * en vez de onclick="" inline.
 */
$(document).ready(function () {
    var cfg = window.HdSocialIntegrationsConfig || {};
    var testRoutes = cfg.testRoutes || {};

    $(document).on('click', '.js-test-connection', function () {
        var platform = $(this).data('platform');
        var $btn = $(this);
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Probando...');

        fetch(testRoutes[platform], {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json',
            },
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) {
                    toastr.error(data.message);
                }
            })
            .catch(function () {
                toastr.error('Error de conexión');
            })
            .finally(function () {
                $btn.prop('disabled', false).html(originalHtml);
            });
    });

    $(document).on('click', '.js-copy-to-clipboard', function () {
        var inputId = $(this).data('clipboardTarget');
        var input = document.getElementById(inputId);

        navigator.clipboard.writeText(input.value).catch(function () {
            input.select();
            document.execCommand('copy');
        });
    });
});
