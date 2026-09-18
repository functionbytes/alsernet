/**
 * Configuración de LiveChat (settings/livechat/index.blade.php). Extraido
 * del <script> inline de esa vista: sincroniza el formulario con el
 * iframe de vista previa, tabs de tiempo de espera, formularios pre/post
 * chat y los botones de copiar.
 *
 * Depende de window.LivechatSettingsFlash (sembrado por un bootstrap
 * inline minimo en la propia vista con la sesion flash — lo unico que este
 * fichero no puede resolver por su cuenta). La URL del iframe de preview NO
 * hace falta pasarla aparte: se relee de su propio atributo src (ya
 * renderizado por Blade) y se le quita el query string para reconstruirla.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        $('.form-select').select2({ width: '100%' });

        var iframe = document.getElementById('widgetPreviewIframe');
        var iframeBaseSrc = iframe.src.split('?')[0];
        var iframeReady = false;
        var pendingSettings = null;

        // ── Widget ready signal ────────────────────────────────────────────────
        window.addEventListener('message', function (event) {
            if (event.origin !== window.location.origin) return;
            if (event.data?.source === 'be-backups-preview' && event.data?.type === 'appLoaded') {
                iframeReady = true;
                if (pendingSettings) {
                    postSettings(pendingSettings);
                    pendingSettings = null;
                }
            }
        });

        // ── Collect and post settings ─────────────────────────────────────────
        function collectSettings() {
            return {
                primary_color:          $('#primary_color').val(),
                secondary_color:        $('#secondary_color').val(),
                header_title:           $('#header_title').val(),
                welcome_message:        $('#welcome_message').val(),
                input_placeholder:      $('#input_placeholder').val(),
                show_avatars:           $('#show_avatars').is(':checked'),
                show_help_center:       $('#show_help_center').is(':checked'),
                typing_indicator:       $('#typing_indicator').is(':checked'),
                sound_notifications:    $('#sound_notifications').is(':checked'),
                show_timestamps:        $('#show_timestamps').is(':checked'),
                enable_email_transcripts: $('#enable_email_transcripts').is(':checked'),
                hide_launcher:          $('#hide_launcher').is(':checked'),
                position:               $('#position').val(),
                side_spacing:           parseInt($('#side_spacing').val()) || 16,
                bottom_spacing:         parseInt($('#bottom_spacing').val()) || 16,
            };
        }

        function postSettings(settings) {
            if (!iframe.contentWindow) return;
            iframe.contentWindow.postMessage({
                source: 'be-backups-editor',
                type:   'setValues',
                values: settings
            }, window.location.origin);
        }

        function syncPreview() {
            var s = collectSettings();
            if (iframeReady) {
                postSettings(s);
            } else {
                pendingSettings = s;
            }
        }

        // ── Sync color pickers ─────────────────────────────────────────────────
        // El componente de color ya mantiene muestra y hex en sintonia; aqui solo
        // hace falta repintar la vista previa del widget.
        $(document).on('input', '.ts-color .ts-color__hex', function () {
            syncPreview();
        });

        // ── Sync all other form inputs ─────────────────────────────────────────
        $('#livechatForm').on('change input', '.lc-sync', function () {
            syncPreview();
        });

        // ── Screen tabs ────────────────────────────────────────────────────────
        $('#previewTabs').on('click', 'a.nav-link', function (e) {
            e.preventDefault();
            $('#previewTabs a.nav-link').removeClass('active');
            $(this).addClass('active');
            iframeReady = false;
            iframe.src = iframeBaseSrc + '?preview=true';
            pendingSettings = collectSettings();
        });

        // ── Send initial settings once iframe is ready ─────────────────────────
        pendingSettings = collectSettings();

        var flash = window.LivechatSettingsFlash || {};
        if (flash.success) { toastr.success(flash.success, 'Configuración guardada'); }

        // File types section visibility
        (function () {
            var toggle  = document.getElementById('enable_file_upload');
            var section = document.getElementById('file-types-section');
            if (!toggle || !section) { return; }
            function sync() { section.style.display = toggle.checked ? '' : 'none'; }
            toggle.addEventListener('change', sync);
            sync();
        }());
    });

    // ── Timeout toggles ────────────────────────────────────────────────────────
    window.toggleTimeout = function (type) {
        var checkbox = document.getElementById('enable_auto_' + type);
        var body     = document.getElementById('body-' + type);
        var row      = document.getElementById('row-' + type);
        checkbox.checked = !checkbox.checked;
        body.classList.toggle('opacity-50', !checkbox.checked);
        row.classList.toggle('active', checkbox.checked);
    };

    // ── Forms switcher ─────────────────────────────────────────────────────────
    window.switchForm = function (type) {
        document.getElementById('formPreChat').classList.toggle('d-none', type !== 'pre');
        document.getElementById('formPostChat').classList.toggle('d-none', type !== 'post');
        document.getElementById('btnPreChat').classList.toggle('active', type === 'pre');
        document.getElementById('btnPostChat').classList.toggle('active', type === 'post');
    };

    // ── Copy helpers ───────────────────────────────────────────────────────────
    window.copyInstallCode = function () {
        var block = document.getElementById('installCodeBlock').innerText;
        navigator.clipboard.writeText(block).then(function () {
            var el = document.getElementById('copyCodeLabel');
            el.textContent = '¡Copiado!';
            setTimeout(function () { el.textContent = 'Copiar'; }, 2000);
        });
    };

    window.copyChatLink = function () {
        var url = document.getElementById('chatPageUrl').value;
        navigator.clipboard.writeText(url).then(function () {
            var el = document.getElementById('copyLinkLabel');
            el.textContent = '¡Enlace copiado!';
            setTimeout(function () { el.textContent = 'Copiar enlace'; }, 2000);
        });
    };

    window.copySecretKey = function () {
        var key = document.getElementById('secretKeyDisplay').value;
        navigator.clipboard.writeText(key).then(function () {
            var el = document.getElementById('copyKeyLabel');
            el.textContent = '¡Copiada!';
            setTimeout(function () { el.textContent = 'Copiar clave'; }, 2000);
        });
    };
})();
