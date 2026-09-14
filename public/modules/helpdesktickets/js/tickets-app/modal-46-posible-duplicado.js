'use strict';

    // ── Modal 46: Posible duplicado ──────────────────────────
    // El mockup lo dispara al crear un ticket. Aquí se dispara al abrirlo,
    // que es donde esta pantalla puede actuar: al crear todavía no hay nada
    // que fusionar ni que enlazar. Las dos salidas son las del mockup:
    // unificar (fusionar en el existente) o tratarlos como independientes.

    function openDuplicateModal(t, candidates, windowDays) {
        var rows = candidates.map(function (c) {
            return '<label class="tkt-option">' +
                '<input type="radio" name="tkt-dupe-pick" value="' + c.id + '">' +
                '<span class="tkt-option-body">' +
                    '<span class="tkt-option-title">' + escapeHtml(c.ticket_number) + ' · ' + escapeHtml(c.subject || '(sin asunto)') + '</span>' +
                    '<span class="tkt-option-sub">' + escapeHtml(c.status || '—') +
                        ' · coincidencia de asunto ' + Math.round(c.similarity * 100) + ' %' +
                        (c.same_customer ? ' · mismo cliente' : '') +
                    '</span>' +
                '</span>' +
            '</label>';
        }).join('');

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-clone',
            kicker: 'Creación · duplicados',
            titleChip: t.ticket_number,
            title: 'Posible duplicado al crear',
            width: 'lg',
            body: '<div class="tkt-cap">Ya existe un ticket similar</div>' +
                '<div class="tkt-note">Este cliente tiene ' + (candidates.length === 1 ? 'otro ticket abierto' : 'otros tickets abiertos') +
                    ' con un asunto muy parecido dentro de la ventana de detección.</div>' +
                rows +
                '<div class="tkt-kv-grid">' +
                    '<span>ventana</span><span>' + (windowDays ? 'últimos ' + windowDays + ' días' : '—') + '</span>' +
                    '<span>criterio</span><span>asunto y cliente</span>' +
                '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-dupe-merge" disabled>Fusionar con el seleccionado</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-dupe-keep" disabled>Son independientes</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('change', '[name="tkt-dupe-pick"]', function () {
            $backdrop.find('.tkt-option').removeClass('on');
            $(this).closest('.tkt-option').addClass('on');
            $backdrop.find('#tkt-dupe-merge, #tkt-dupe-keep').prop('disabled', false);
        });

        // Fusionar es destructivo: se delega en el modal 20, que ya pide
        // confirmación explícita y explica qué se mueve y qué se cierra.
        $backdrop.on('click', '#tkt-dupe-merge', function () {
            var target = $backdrop.find('[name="tkt-dupe-pick"]:checked').val();
            closeModal();
            mergeTicketPrompt(t, target);
        });

        // "Son independientes": se deja constancia con un enlace 'related'
        // para que el aviso no vuelva a salir con los mismos dos tickets.
        $backdrop.on('click', '#tkt-dupe-keep', function () {
            var target = $backdrop.find('[name="tkt-dupe-pick"]:checked').val();
            $.ajax({
                url: t.url_link,
                method: 'POST',
                data: { linked_ticket_id: target, link_type: 'related' },
                headers: { Accept: 'application/json' },
            }).done(function () {
                if (window.toastr) toastr.success('Marcados como relacionados, no duplicados.');
                closeModal();
                dismissDuplicateBanner();
            }).fail(function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo guardar la relación.');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            });
        });
    }


