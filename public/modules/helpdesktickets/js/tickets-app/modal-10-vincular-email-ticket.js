'use strict';

    // ── Modal 10: Vincular email a ticket ─────────────────────
    function openLinkMailModal(t, mail) {
        var chosen = null;

        function resultsHtml(list, emptyText) {
            if (!list.length) return '<div class="tkt-empty-box">' + escapeHtml(emptyText) + '</div>';
            return list.map(function (o) {
                return '<button type="button" class="tkt-pick' + (chosen && chosen.id === o.id ? ' on' : '') + '" data-target="' + o.id + '">' +
                    '<span class="tkt-shortcode mono">' + escapeHtml(o.ticket_number) + '</span>' +
                    '<span class="who"><span class="n">' + escapeHtml(o.subject || '(sin asunto)') + '</span>' +
                    '<span class="s">' + escapeHtml([o.status_name || o.status_slug, o.customer ? o.customer.name : null].filter(Boolean).join(' · ')) + '</span></span>' +
                    (chosen && chosen.id === o.id ? '<i class="fa-solid fa-check"></i>' : '') +
                '</button>';
            }).join('');
        }

        // Candidatos por defecto: otros tickets del MISMO cliente, que es de
        // donde salen casi todos los correos mal clasificados.
        function sameCustomer() {
            if (!t.customer) return [];
            return TKA.state.tickets.filter(function (x) {
                return x.id !== t.id && x.customer && x.customer.id === t.customer.id;
            });
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-link',
            kicker: 'Tickets · vinculación',
            title: 'Vincular email a ticket',
            width: 'md',
            body: '<div class="tkt-headline">' +
                    '<div class="t">' + escapeHtml(mail.subject || '(sin asunto)') + '</div>' +
                    '<div class="s">' + escapeHtml((mail.direction === 'inbound' ? 'De ' : 'Para ') + (mail.direction === 'inbound' ? (mail.from || '—') : (mail.to || '—'))) + '</div>' +
                  '</div>' +
                  '<div class="tkt-field"><input type="search" class="tkt-input" id="tkt-link-search" placeholder="Buscar por número, cliente o asunto…" aria-label="Buscar ticket"></div>' +
                  '<div class="tkt-cap">Coincidencias del mismo cliente</div>' +
                  '<div class="tkt-pick-list" id="tkt-link-list">' + resultsHtml(sameCustomer(), 'Sin otros tickets de este cliente en la página actual.') + '</div>' +
                  '<label class="tkt-check"><input type="checkbox" id="tkt-link-thread"> Mover también el resto del hilo</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-link-confirm" disabled>Vincular</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('input', '#tkt-link-search', function () {
            var q = this.value.trim().toLowerCase();
            var list = q
                ? TKA.state.tickets.filter(function (x) {
                    return x.id !== t.id && (
                        String(x.ticket_number).toLowerCase().indexOf(q) !== -1 ||
                        String(x.subject || '').toLowerCase().indexOf(q) !== -1 ||
                        String(x.customer ? x.customer.name : '').toLowerCase().indexOf(q) !== -1);
                  })
                : sameCustomer();
            $backdrop.find('#tkt-link-list').html(resultsHtml(list, 'Ningún ticket coincide con la búsqueda.'));
        });

        $backdrop.on('click', '[data-target]', function () {
            var id = parseInt($(this).data('target'), 10);
            chosen = TKA.state.tickets.find(function (x) { return x.id === id; }) || null;
            $backdrop.find('[data-target]').removeClass('on').find('.fa-check').remove();
            $(this).addClass('on');
            $backdrop.find('#tkt-link-confirm').prop('disabled', !chosen);
        });

        $backdrop.on('click', '#tkt-link-confirm', function () {
            if (!chosen) return;
            var $btn = $(this).prop('disabled', true).text('Vinculando…');
            $.ajax({
                url: mail.url_link,
                method: 'POST',
                data: { ticket_id: chosen.id, move_thread: $('#tkt-link-thread').is(':checked') ? 1 : 0 },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Correo vinculado');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo vincular el correo';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Vincular');
                },
            });
        });
    }


