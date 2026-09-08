'use strict';

    // ── Modal 25: Cliente 360 ─────────────────────────────────
    function openCustomer360Modal(t, customer) {
        if (!customer) { if (window.toastr) toastr.info('Este ticket no tiene un cliente asociado'); return; }
        var integraciones = (customer.integrations || []).map(function (i) {
            return '<span class="tkt-rchip">' + escapeHtml(i.label || i.name || i) + '</span>';
        }).join('');

        // "45 min" por debajo de la hora, "2h 15m" a partir de ahí — mismo
        // criterio de legibilidad que el resto de duraciones del panel.
        var firstResponse = '—';
        if (customer.avg_first_response_minutes != null) {
            var mins = Math.round(customer.avg_first_response_minutes);
            firstResponse = mins < 60 ? (mins + ' min') : (Math.floor(mins / 60) + 'h ' + (mins % 60) + 'm');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-address-card', kicker: 'Cliente · 360',
            titleChip: customer.name || '',
            title: 'Cliente 360', width: 'xl',
            body: '<div class="tkt-headline"><div class="t">' + escapeHtml(customer.name || '—') + '</div>' +
                    '<div class="s">' + escapeHtml(customer.company || 'Sin empresa asociada') + '</div></div>' +
                  '<div class="tkt-side-rows">' +
                    sideRow('email', customer.email || '—', { mono: true }) +
                    sideRow('teléfono', customer.phone || '—', { mono: true }) +
                    sideRow('cliente desde', customer.customer_since_year || '—', { mono: true }) +
                    sideRow('idioma', customer.language || '—', { mono: true }) +
                    sideRow('id externo', customer.external_id || '—', { mono: true, last: true }) +
                  '</div>' +
                  (integraciones ? '<div class="tkt-cap">Integraciones</div><div class="tkt-chiprow">' + integraciones + '</div>' : '') +
                  '<div class="tkt-cap">Historial de soporte</div>' +
                  '<div class="tkt-stats three">' +
                    '<div class="tkt-stat"><span class="n">' + (customer.tickets_count != null ? customer.tickets_count : '—') + '</span><span class="l">tickets</span></div>' +
                    '<div class="tkt-stat"><span class="n">' + (customer.avg_csat != null ? customer.avg_csat : '—') + '</span><span class="l">CSAT medio</span></div>' +
                    '<div class="tkt-stat"><span class="n">' + escapeHtml(firstResponse) + '</span><span class="l">1ª respuesta</span></div>' +
                  '</div>' +
                  (customer.is_banned ? '<div class="tkt-note"><i class="fa-solid fa-ban"></i> Este contacto está bloqueado.</div>' : '') +
                  '<div id="tkt-c360-orders"></div>',
            foot: (customer.url_c360 ? '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(customer.url_c360) + '">Abrir ficha completa</a>' : '') +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // Pedidos PrestaShop: bajo demanda (llama al bridge en vivo), no
        // viaja con los datos ya cargados del ticket.
        if (t.url_customer_orders) {
            $.getJSON(t.url_customer_orders).done(function (resp) {
                if (!resp || !resp.available || !resp.orders || !resp.orders.length) return;
                var rows = resp.orders.map(function (o) {
                    return '<div class="tkt-mailitem"><span class="av light"><i class="fa-solid fa-bag-shopping"></i></span>' +
                        '<span class="who"><span class="n">' + escapeHtml(o.reference || '—') + '</span>' +
                        '<span class="s">' + escapeHtml(o.state || '') + (o.placed_at ? ' · ' + escapeHtml(formatDateShort(o.placed_at)) : '') + '</span></span>' +
                        (o.total != null ? '<span class="mono">' + escapeHtml(String(o.total)) + ' ' + escapeHtml(o.currency_sign || '€') + '</span>' : '') +
                        '</div>';
                }).join('');
                $backdrop.find('#tkt-c360-orders').html('<div class="tkt-cap">Pedidos PrestaShop</div><div class="tkt-mailitems">' + rows + '</div>');
            });
        }
    }


