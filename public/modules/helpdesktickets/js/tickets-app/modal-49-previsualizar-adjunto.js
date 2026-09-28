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
            body = '<div class="tkt-empty-box">' + TKA.t('modal_49_preview_unavailable', 'Este tipo de archivo no se puede previsualizar en el navegador. Descárgalo para abrirlo.') + '</div>';
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-file',
            kicker: TKA.t('kicker_attachment_preview', 'Adjunto · previsualización'),
            titleChip: file.name || '',
            title: TKA.t('modal_title_preview_attachment', 'Previsualizar adjunto'),
            width: 'xl',
            body: body +
                '<div class="tkt-side-rows">' +
                    sideRow(TKA.t('modal_49_size_label', 'tamaño'), file.size ? formatFileSize(file.size) : '—', { mono: true }) +
                    sideRow(TKA.t('modal_49_source_label', 'origen'), file.source === 'customer' ? TKA.t('modal_49_sent_by_customer', 'Enviado por el cliente') : TKA.t('modal_49_sent_by_agent', 'Enviado por un agente')) +
                    sideRow(TKA.t('modal_49_date_label', 'fecha'), file.created_at_human || '—') +
                    sideRow(TKA.t('modal_49_ticket_label', 'ticket'), t.ticket_number, { mono: true, last: true }) +
                '</div>',
            foot: '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(file.url_download) + '" download>' + TKA.t('modal_49_download_btn', 'Descargar') + '</a>' +
                  '<a class="tkt-btn" href="' + escapeHtml(file.url_download) + '" target="_blank" rel="noopener">' + TKA.t('modal_49_open_new_tab_btn', 'Abrir en pestaña nueva') + '</a>' +
                  '<button type="button" class="tkt-btn" data-modal-close>' + TKA.t('close', 'Cerrar') + '</button>',
        }));

        return $backdrop;
    }


