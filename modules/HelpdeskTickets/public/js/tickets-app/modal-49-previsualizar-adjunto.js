'use strict';

    // ── Modal 49: Previsualizar adjunto ───────────────────────
    var PREVIEWABLE = /\.(png|jpe?g|gif|webp|svg|pdf)$/i;

    function openFilePreviewModal(t, file) {
        var isImage = /\.(png|jpe?g|gif|webp|svg)$/i.test(file.name || '');
        var isPdf = /\.pdf$/i.test(file.name || '');
        var body;

        if (isImage) {
            body = '<div class="tkt-preview-stage"><img src="' + escapeHtml(file.url_download) + '" alt="' + escapeHtml(file.name) + '"></div>';
        } else if (isPdf) {
            body = '<div class="tkt-preview-stage"><iframe src="' + escapeHtml(file.url_download) + '" title="' + escapeHtml(file.name) + '"></iframe></div>';
        } else {
            // Sin visor para este tipo: se dice claramente en vez de dejar un
            // marco en blanco que parece roto.
            body = '<div class="tkt-empty-box">Este tipo de archivo no se puede previsualizar en el navegador. Descárgalo para abrirlo.</div>';
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-file',
            kicker: 'Adjunto · previsualización',
            titleChip: file.name || '',
            title: 'Previsualizar adjunto',
            width: 'xl',
            body: body +
                '<div class="tkt-side-rows">' +
                    sideRow('tamaño', file.size ? formatFileSize(file.size) : '—', { mono: true }) +
                    sideRow('origen', file.source === 'customer' ? 'Enviado por el cliente' : 'Enviado por un agente') +
                    sideRow('fecha', file.created_at_human || '—') +
                    sideRow('ticket', t.ticket_number, { mono: true, last: true }) +
                '</div>',
            foot: '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(file.url_download) + '" download>Descargar</a>' +
                  '<a class="tkt-btn" href="' + escapeHtml(file.url_download) + '" target="_blank" rel="noopener">Abrir en pestaña nueva</a>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        return $backdrop;
    }


