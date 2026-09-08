'use strict';

    // ── Modal 26: Resumen IA del hilo ─────────────────────────
    function openAiSummaryModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-wand-magic-sparkles', iconClass: 'ok', kicker: 'IA · resumen',
            title: 'Resumen IA del hilo', titleChip: t.ticket_number, width: 'lg',
            body: '<div id="tkt-sum-body"><div class="tkt-skeleton"></div></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-sum-copy" disabled>Copiar resumen</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-sum-regen">Regenerar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));
        var current = null;

        function load() {
            $backdrop.find('#tkt-sum-body').html('<div class="tkt-skeleton"></div>');
            $backdrop.find('#tkt-sum-copy').prop('disabled', true);
            $.getJSON(t.url_summary).done(function (res) {
                if (!res || !res.summary) {
                    // El servicio devuelve null si no hay clave de API o no hay
                    // contexto suficiente: se dice, no se inventa un resumen.
                    $backdrop.find('#tkt-sum-body').html('<div class="tkt-empty-box">No hay resumen disponible para este hilo. El servicio de IA no está configurado o el hilo es demasiado corto.</div>');
                    return;
                }
                current = res.summary;
                $backdrop.find('#tkt-sum-body').html(
                    '<div class="tkt-tpl-preview">' + escapeHtml(res.summary) + '</div><div class="tkt-side-rows">' +
                        sideRow('mensajes resumidos', String(res.messages_count != null ? res.messages_count : '—'), { mono: true }) +
                        sideRow('idioma detectado', res.language || '—', { mono: true, last: true }) + '</div>');
                $backdrop.find('#tkt-sum-copy').prop('disabled', false);
            }).fail(function () {
                $backdrop.find('#tkt-sum-body').html('<div class="tkt-empty-box">No se pudo generar el resumen.</div>');
            });
        }

        $backdrop.on('click', '#tkt-sum-regen', load);
        $backdrop.on('click', '#tkt-sum-copy', function () {
            if (!current || !navigator.clipboard) return;
            navigator.clipboard.writeText(current).then(function () {
                if (window.toastr) toastr.success('Resumen copiado');
            });
        });
        load();
    }


