/**
 * Pantalla settings/inboxes/form.blade.php.
 * window.HdInboxFormConfig = { testConnectionUrl, showWidgetPreview,
 * previewToken } lo imprime el Blade.
 *
 * Requiere settings-common-batch3.js (window.HdSettingsCommon) cargado antes.
 */
$(function () {
    var cfg = window.HdInboxFormConfig || {};

    window.HdSettingsCommon.initFormSelect2('.form-select');

    // ── Persistencia de tab en URL hash ──────────────────────────────
    $('#inboxTabs button').on('shown.bs.tab', function (e) {
        window.location.hash = e.target.getAttribute('data-bs-target');
    });

    if (window.location.hash) {
        var tabBtn = document.querySelector('[data-bs-target="' + window.location.hash + '"]');
        if (tabBtn) {
            bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        }
    }

    // ── Autoexpandir tab del primer error de validación ───────────────
    (function () {
        var firstError = document.querySelector('.is-invalid');
        if (!firstError) { return; }
        var pane = firstError.closest('.tab-pane');
        if (!pane) { return; }
        var btn = document.querySelector('[data-bs-target="#' + pane.id + '"]');
        if (btn) { bootstrap.Tab.getOrCreateInstance(btn).show(); }
    })();

    // ── Horario laboral ───────────────────────────────────────────────
    var $whToggle = $('#working_hours_enabled');
    var $whBlock  = $('#inbox-schedule-block');

    function toggleSchedule() {
        $whBlock.toggle($whToggle.is(':checked'));
    }

    toggleSchedule();
    $whToggle.on('change', toggleSchedule);

    $(document).on('change', '.inbox-day-toggle', function () {
        $(this).closest('.row').find('.inbox-day-time').prop('disabled', !this.checked);
    });

    // ── Selector de proveedor WhatsApp ────────────────────────────────
    $('#wa_provider').on('change', function () {
        var val = this.value;
        $('.wa-provider-fields').hide();
        $('[data-provider="' + val + '"]').show();
    }).trigger('change');

    // ── Copiar código de instalación ──────────────────────────────────
    window.copyEmbedCode = function () {
        var code = document.getElementById('embed-code').textContent;
        navigator.clipboard.writeText(code);
    };

    // ── Desbloquear campo de credencial oculto ────────────────────────
    $(document).on('click', '.btn-change-cred', function () {
        var target = $(this).data('target');
        $('#' + target).prop('disabled', false).attr('type', 'password').focus();
        $(this).prop('disabled', true).text('Editando…');
    });

    // ── Probar conexión ───────────────────────────────────────────────
    $('#btn-test-connection').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Verificando...');

        $.ajax({
            url: cfg.testConnectionUrl || '',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                var cls  = res.ok ? 'text-success' : 'text-dark';
                var icon = res.ok ? 'fa-check-circle' : 'fa-times-circle';
                $('#test-result')
                    .html('<i class="fas ' + icon + ' me-1"></i>' + res.message)
                    .attr('class', 'small mt-2 ' + cls);
            },
            error: function () {
                $('#test-result')
                    .html('<i class="fas fa-times-circle me-1"></i>Error al conectar')
                    .attr('class', 'small mt-2 text-dark');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Probar conexión');
            },
        });
    });

    if (cfg.showWidgetPreview) {
        // ── Widget preview live sync ─────────────────────────────────────
        (function () {
            var iframe = document.getElementById('widgetPreviewIframe');
            if (!iframe) return;

            var iframeReady = false;
            var pendingSettings = null;

            window.addEventListener('message', function (event) {
                if (event.origin !== window.location.origin) return;
                if (event.data && event.data.source === 'be-backups-preview' && event.data.type === 'appLoaded') {
                    iframeReady = true;
                    if (pendingSettings) {
                        postSettings(pendingSettings);
                        pendingSettings = null;
                    }
                }
            });

            function getVal(name) {
                var el = document.querySelector('[name="widget['+name+']"]:not([type=hidden])');
                return el ? el.value : '';
            }
            function isChecked(name) {
                var el = document.querySelector('input[type=checkbox][name="widget['+name+']"]');
                return el ? el.checked : false;
            }

            function collectSettings() {
                return {
                    primary_color:        getVal('widget_color') || '#90bb13',
                    secondary_color:      getVal('secondary_color') || '#ffffff',
                    header_title:         getVal('header_title'),
                    welcome_message:      getVal('welcome_message'),
                    input_placeholder:    getVal('input_placeholder'),
                    show_avatars:         isChecked('show_avatars'),
                    show_help_center:     isChecked('show_help_center'),
                    typing_indicator:     isChecked('typing_indicator'),
                    sound_notifications:  isChecked('sound_notifications'),
                    show_timestamps:      isChecked('show_timestamps'),
                    enable_email_transcripts: isChecked('enable_email_transcripts'),
                    hide_launcher:        isChecked('hide_launcher'),
                    position:             getVal('widget_position') || 'bottom-right',
                    side_spacing:         parseInt(getVal('side_spacing')) || 16,
                    bottom_spacing:       parseInt(getVal('bottom_spacing')) || 16,
                };
            }

            function postSettings(s) {
                if (!iframe.contentWindow) return;
                iframe.contentWindow.postMessage({
                    source: 'be-backups-editor',
                    type:   'setValues',
                    values: s,
                }, window.location.origin);
            }

            function syncPreview() {
                var s = collectSettings();
                if (iframeReady) postSettings(s);
                else pendingSettings = s;
            }

            // Sync on any change inside #tab-widget
            $('#tab-widget').on('change input', 'input, select, textarea', syncPreview);

            // Switch preview screen tab
            var previewToken = cfg.previewToken;
            $('#previewTabs').on('click', 'a.nav-link', function (e) {
                e.preventDefault();
                $('#previewTabs a.nav-link').removeClass('active');
                $(this).addClass('active');
                iframeReady = false;
                try {
                    var url = iframe.src.split('?')[0] + '?preview=true&screen=' + $(this).data('screen');
                    if (previewToken) url += '&website_token=' + encodeURIComponent(previewToken);
                    iframe.src = url;
                } catch (err) {}
                pendingSettings = collectSettings();
            });

            // Initial sync
            pendingSettings = collectSettings();
        })();
    }
});
