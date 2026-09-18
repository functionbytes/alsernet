'use strict';

    // ── Modal 04: Adjuntar archivos ───────────────────────────
    function openAttachModal(t) {
        var staged = composeDraft.files.slice();

        function stagedHtml() {
            if (!staged.length) return '';
            return '<div class="tkt-cap">Seleccionados · ' + staged.length + '</div>' +
                '<div class="tkt-file-rows">' + staged.map(function (f, i) {
                    return '<div class="tkt-file-row"><i class="' + fileIconClass(f.name) + '"></i>' +
                        '<span class="n">' + escapeHtml(f.name) + '</span>' +
                        '<span class="mono">' + formatFileSize(f.size) + '</span>' +
                        '<button type="button" data-staged-remove="' + i + '" title="Quitar"><i class="fa-solid fa-xmark"></i></button></div>';
                }).join('') + '</div>';
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-paperclip',
            kicker: 'Ticket · adjuntos',
            title: 'Adjuntar archivos',
            titleChip: t.ticket_number,
            width: 'md',
            body: '<label class="tkt-dropzone" id="tkt-dropzone">' +
                    '<i class="fa-solid fa-cloud-arrow-up"></i>' +
                    '<span class="t">Arrastra archivos aquí</span>' +
                    '<span class="s">' + TKT_ATTACHMENT_EXTENSIONS.map(function (ext) { return ext.toUpperCase(); }).join(', ') + ' · máx. ' + formatFileSize(TKT_ATTACHMENT_MAX_BYTES) + ' por archivo</span>' +
                    '<input type="file" id="tkt-attach-input" multiple accept=".' + TKT_ATTACHMENT_EXTENSIONS.join(',.') + '" hidden>' +
                  '</label>' +
                  '<div id="tkt-attach-staged">' + stagedHtml() + '</div>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Los adjuntos se guardan en el ticket junto al email enviado.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-attach-confirm">Adjuntar ' + (staged.length || '') + (staged.length === 1 ? ' archivo' : ' archivos') + '</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-attach-back">Volver</button>',
        }));

        function refresh() {
            $backdrop.find('#tkt-attach-staged').html(stagedHtml());
            $backdrop.find('#tkt-attach-confirm').text('Adjuntar ' + (staged.length || '') + (staged.length === 1 ? ' archivo' : ' archivos'));
        }

        function add(fileList) {
            Array.prototype.forEach.call(fileList, function (f) {
                var extension = String(f.name || '').split('.').pop().toLowerCase();
                if (f.size > TKT_ATTACHMENT_MAX_BYTES || TKT_ATTACHMENT_EXTENSIONS.indexOf(extension) === -1) {
                    if (window.toastr) toastr.error(f.name + ' no cumple el límite o formato permitido');
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
