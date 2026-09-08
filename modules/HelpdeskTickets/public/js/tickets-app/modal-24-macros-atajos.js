'use strict';

    // ── Modal 24: Macros y atajos ────────────────────────────
    // El desplegable del composer solo enseña el nombre. Una macro que cierra
    // el ticket y otra que solo responde se leen igual desde ahí, y aplicarla
    // es irreversible en la práctica (manda un correo, cambia el estado). Este
    // modal enseña qué hace cada una ANTES de ejecutarla.

    function openMacrosModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-bolt',
            kicker: 'Redactor · macros',
            titleChip: t.ticket_number,
            title: 'Macros y atajos',
            width: 'lg',
            body: '<div class="tkt-field"><input type="search" class="tkt-input" id="tkt-macro-search" placeholder="Buscar macro…" aria-label="Buscar macro"></div>' +
                  '<div id="tkt-macro-list"><div class="tkt-empty-box">Cargando…</div></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-macro-apply" disabled>Aplicar macro</button>' +
                  '<a href="' + TKA.urls.macrosIndex + '" class="tkt-btn tkt-link-plain">Editar macros</a>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        loadMacros(function (macros) {
            if (!macros.length) {
                $backdrop.find('#tkt-macro-list').html('<div class="tkt-empty-box">No hay macros disponibles todavía.</div>');

                return;
            }

            $backdrop.find('#tkt-macro-list').html(macros.map(function (m) {
                var effects = (m.effects || []).map(function (e) {
                    return '<li>' + escapeHtml(e) + '</li>';
                }).join('');

                return '<label class="tkt-option tkt-macro-row" data-macro-name="' + escapeHtml((m.name || '').toLowerCase()) + '">' +
                    '<input type="radio" name="tkt-macro-pick" value="' + m.id + '">' +
                    '<span class="tkt-option-body">' +
                        '<span class="tkt-option-title">' + escapeHtml(m.name) + '</span>' +
                        (m.description ? '<span class="tkt-option-sub">' + escapeHtml(m.description) + '</span>' : '') +
                        (effects ? '<ul class="tkt-macro-effects">' + effects + '</ul>' : '') +
                    '</span>' +
                '</label>';
            }).join(''));
        });

        $backdrop.on('change', '[name="tkt-macro-pick"]', function () {
            $backdrop.find('.tkt-option').removeClass('on');
            $(this).closest('.tkt-option').addClass('on');
            $backdrop.find('#tkt-macro-apply').prop('disabled', false);
        });

        $backdrop.on('input', '#tkt-macro-search', function () {
            var term = this.value.trim().toLowerCase();
            $backdrop.find('.tkt-macro-row').each(function () {
                $(this).toggle(!term || String($(this).data('macro-name')).indexOf(term) > -1);
            });
        });

        $backdrop.on('click', '#tkt-macro-apply', function () {
            var id = $backdrop.find('[name="tkt-macro-pick"]:checked').val();
            if (!id) return;
            closeModal();
            applyMacro(t, id);
        });
    }


