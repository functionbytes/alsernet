'use strict';

    // ── Modal 04: Adjuntar archivos ───────────────────────────
    function openAttachModal(t) {
        var staged = composeDraft.files.slice();

        function stagedHtml() {
            if (!staged.length) return '';
            return '<div class="tkt-cap">' + TKA.t('modal_04_selected_label', 'Seleccionados') + ' · ' + staged.length + '</div>' +
                '<div class="tkt-file-rows">' + staged.map(function (f, i) {
                    return '<div class="tkt-file-row"><i class="' + fileIconClass(f.name) + '"></i>' +
                        '<span class="n">' + escapeHtml(f.name) + '</span>' +
                        '<span class="mono">' + formatFileSize(f.size) + '</span>' +
                        '<button type="button" data-staged-remove="' + i + '" title="' + TKA.t('modal_04_remove_title', 'Quitar') + '"><i class="fa-solid fa-xmark"></i></button></div>';
                }).join('') + '</div>';
        }

        function attachBtnLabel() {
            var suffix = staged.length === 1
                ? TKA.t('modal_04_file_singular_suffix', ' archivo')
                : TKA.t('modal_04_file_plural_suffix', ' archivos');

            return TKA.t('modal_04_attach_btn', 'Adjuntar') + ' ' + (staged.length || '') + suffix;
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-paperclip',
            kicker: TKA.t('kicker_ticket_attachments', 'Ticket · adjuntos'),
            title: TKA.t('modal_title_attach_files', 'Adjuntar archivos'),
            titleChip: t.ticket_number,
            width: 'md',
            body: '<label class="tkt-dropzone" id="tkt-dropzone">' +
                    '<i class="fa-solid fa-cloud-arrow-up"></i>' +
                    '<span class="t">' + TKA.t('modal_04_drag_files_here', 'Arrastra archivos aquí') + '</span>' +
                    '<span class="s">' + TKT_ATTACHMENT_EXTENSIONS.map(function (ext) { return ext.toUpperCase(); }).join(', ') + ' · ' + TKA.t('modal_04_max_per_file', 'máx. :size por archivo', { ':size': formatFileSize(TKT_ATTACHMENT_MAX_BYTES) }) + '</span>' +
                    '<input type="file" id="tkt-attach-input" multiple accept=".' + TKT_ATTACHMENT_EXTENSIONS.join(',.') + '" hidden>' +
                  '</label>' +
                  '<div id="tkt-attach-staged">' + stagedHtml() + '</div>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> ' + TKA.t('modal_04_attachments_note', 'Los adjuntos se guardan en el ticket junto al email enviado.') + '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-attach-confirm">' + attachBtnLabel() + '</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-attach-back">' + TKA.t('modal_04_back_btn', 'Volver') + '</button>',
        }));

        function refresh() {
            $backdrop.find('#tkt-attach-staged').html(stagedHtml());
            $backdrop.find('#tkt-attach-confirm').text(attachBtnLabel());
        }

        function add(fileList) {
            Array.prototype.forEach.call(fileList, function (f) {
                var extension = String(f.name || '').split('.').pop().toLowerCase();
                if (f.size > TKT_ATTACHMENT_MAX_BYTES || TKT_ATTACHMENT_EXTENSIONS.indexOf(extension) === -1) {
                    if (window.toastr) toastr.error(TKA.t('modal_04_file_not_allowed', ':name no cumple el límite o formato permitido', { ':name': f.name }));
                    return;
                }
                staged.push(f);
            });
            refresh();
        }

        $backdrop.on('change', '#tkt-attach-input', function () { add(this.files); this.value = ''; });
        $backdrop.on('click', '[data-staged-remove]', function () {
            staged.splice(parseInt($(this).data('staged-remove'), 10), 1);
            refresh();
        });
        // Arrastrar y soltar de verdad, que es lo que promete el copy.
        $backdrop.on('dragover', '#tkt-dropzone', function (e) { e.preventDefault(); $(this).addClass('over'); });
        $backdrop.on('dragleave drop', '#tkt-dropzone', function () { $(this).removeClass('over'); });
        $backdrop.on('drop', '#tkt-dropzone', function (e) {
            e.preventDefault();
            add(e.originalEvent.dataTransfer.files);
        });
        $backdrop.on('click', '#tkt-attach-confirm', function () {
            composeDraft.files = staged;
            closeModal();
            openComposeModal(t);
        });
        $backdrop.on('click', '#tkt-attach-back', function () { closeModal(); openComposeModal(t); });
    }

    // Picker simplificado por prompt() (el mockup abre un modal de
    // participantes con buscador) — backend real, tres preguntas mínimas.
