/*!
 * HelpdeskPrestashop · extensión "erpbridge".
 *
 * Tarjeta "En Gestión (ERP)" en la pestaña Pago del workspace de pedido: el
 * pedido de Gestión que corresponde a este pedido de PrestaShop (número,
 * estado real de almacén, fechas, líneas servidas, seguimiento) y la factura
 * fiscal de Gestión si Gestión la expone. Solo lectura.
 *
 * Los datos se piden al abrir la pestaña Pago (cada apertura son varias
 * lecturas contra Oracle), no con cada pedido que se pinta.
 *
 * Depende de window.PscOrderWorkspace (order-workspace.js) y window.PscStore
 * (right-panel-prestashop-tabs.js). Fuente en
 * modules/HelpdeskPrestashop/public/js/ext/; asset() sirve desde
 * public/modules/helpdeskprestashop/js/ext/ — copiar allí tras editar.
 */
(function () {
    'use strict';

    var W = function () { return window.PscOrderWorkspace; };
    var S = function () { return window.PscStore; };

    var _loadedFor = null;   // id de pedido ya pedido (o en curso)
    var _data = null;        // última respuesta del pedido abierto

    function esc(s) {
        var st = S();
        if (st && st.esc) { return st.esc(s == null ? '' : String(s)); }
        return W().esc(s == null ? '' : String(s));
    }
    function escAttr(s) {
        var st = S();
        if (st && st.escAttr) { return st.escAttr(s == null ? '' : String(s)); }
        return esc(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function money(n) {
        if (n === null || n === undefined || n === '') { return '—'; }
        var st = S();
        return st && st.money ? st.money(n) : W().money(n);
    }
    function day(iso) {
        if (!iso) { return '—'; }
        var st = S();
        return st && st.date ? esc(st.date(iso, true)) : W().fmtDate(iso);
    }
    function when(iso) {
        if (!iso) { return '—'; }
        return W().fmtDate(iso);
    }
    function units(n) {
        var v = parseFloat(n);
        if (isNaN(v)) { return '—'; }
        return (Math.round(v) === v ? String(v) : v.toFixed(2).replace('.', ',')) + ' ud.';
    }

    function cardShell(inner, sub) {
        return '<div class="bv-po-card psc-erpbridge" id="psErpbridgeCard">' +
            '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-warehouse"></i></span>' +
                '<div class="bv-po-card-ht"><span class="t">En Gestión (ERP)</span><span class="s">' + esc(sub || 'Pedido, albarán y factura en Gestión') + '</span></div></div>' +
            '<div class="psc-erpbridge-body" id="psErpbridgeBody">' + inner + '</div>' +
        '</div>';
    }

    function renderShell() {
        $('#psErpbridgeCard').remove();
        _data = null;
        var $panel = $('#powPanelPago');
        // Justo después de la tarjeta de pago (antes de Documentos/Devolución).
        var shell = cardShell('<div class="psc-loading"><i class="fas fa-spinner fa-spin"></i> Consultando Gestión…</div>');
        var $first = $panel.children('.bv-po-card').first();
        if ($first.length) { $first.after(shell); } else { $panel.append(shell); }
    }

    function load(force) {
        var id = W().orderId();
        if (!id || !$('#psErpbridgeCard').length) { return; }
        if (!force && _loadedFor === id) { return; }
        _loadedFor = id;
        $('#psErpbridgeBody').html('<div class="psc-loading"><i class="fas fa-spinner fa-spin"></i> Consultando Gestión…</div>');

        $.ajax({
            url: W().customerUrl('/ps/orders/' + id + '/erpbridge'),
            method: 'GET', dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': W().csrf() },
        }).done(function (r) {
            if (W().orderId() !== id) { return; }
            _data = (r && r.data) || null;
            render(_data);
        }).fail(function (xhr) {
            if (W().orderId() !== id) { return; }
            _loadedFor = null;
            if (xhr && xhr.status === 403) {
                $('#psErpbridgeBody').html(note('lock', 'No tienes permiso para ver los datos de Gestión de este pedido.'));
                return;
            }
            $('#psErpbridgeBody').html(
                note('warn', W().errorMessage(xhr, 'No se pudo consultar Gestión.')) +
                '<div class="psc-erpbridge-actions"><button type="button" class="psc-btn psc-btn--outline" id="psErpbridgeRetry">Reintentar</button></div>'
            );
        });
    }

    function note(kind, text) {
        return '<div class="psc-note psc-note--' + kind + '"><span class="psc-note-txt">' + esc(text) + '</span></div>';
    }

    function row(k, v) {
        return '<div class="psc-row"><span class="k">' + esc(k) + '</span><span class="v">' + v + '</span></div>';
    }

    function render(d) {
        if (!d) {
            $('#psErpbridgeBody').html(note('warn', 'Gestión no devolvió datos para este pedido.'));
            return;
        }

        if (d.status !== 'found' || !d.erp) {
            var kind = d.status === 'error' ? 'warn' : (d.status === 'not_found' ? 'info' : 'lock');
            var title = {
                not_found: 'Aún no está en Gestión',
                mismatch: 'No se puede mostrar',
                unverified: 'No se puede mostrar',
                disabled: 'Integración desactivada',
                error: 'Gestión no responde',
            }[d.status] || 'Sin datos de Gestión';
            $('#psErpbridgeBody').html(
                '<div class="psc-state psc-state--compact"><i class="fas fa-warehouse"></i>' +
                    '<span class="t">' + esc(title) + '</span></div>' +
                note(kind, d.message || 'Sin datos de Gestión para este pedido.') +
                (d.status === 'error' || d.status === 'not_found'
                    ? '<div class="psc-erpbridge-actions"><button type="button" class="psc-btn psc-btn--outline" id="psErpbridgeRetry">Volver a consultar</button></div>'
                    : '')
            );
            return;
        }

        var e = d.erp;
        var stateId = e.state && e.state.id !== null && e.state.id !== undefined ? parseInt(e.state.id, 10) : null;
        var stateCls = stateId === 7 ? 'psc-tag--done' : (stateId === 0 ? 'psc-tag--closed' : (stateId === 8 ? 'psc-tag--timeout' : 'psc-tag--progress'));
        var number = (e.series ? e.series + '/' : '') + (e.number || '—');

        var html =
            '<div class="psc-erpbridge-head">' +
                '<div class="psc-erpbridge-num"><span class="lbl">Pedido en Gestión</span><span class="n">' + esc(number) + '</span></div>' +
                '<span class="psc-tag ' + stateCls + '">' + esc((e.state && e.state.name) || 'Sin estado') + '</span>' +
            '</div>' +
            (e.has_incident ? note('warn', 'Gestión tiene una incidencia abierta en este pedido.') : '') +
            '<div class="psc-erpbridge-rows">' +
                row('Id interno', '<span class="mono">' + esc(e.id) + '</span>') +
                row('Fecha del pedido', when(e.date)) +
                (e.expected_date ? row('Fecha prevista', day(e.expected_date)) : '') +
                row('Servido', e.served_date ? day(e.served_date) : 'Todavía no') +
                (e.warehouse ? row('Almacén', esc(e.warehouse)) : '') +
                (e.origin ? row('Origen', esc(e.origin)) : '') +
                row('Total en Gestión', '<span class="mono">' + money(e.total) + '</span>') +
            '</div>';

        html += renderTracking(e.tracking || []);
        html += renderLines(d);
        html += renderHistory(e);
        html += renderInvoice(d.invoice || {});
        html += '<div class="psc-erpbridge-actions">' +
            '<button type="button" class="psc-btn psc-btn--outline" id="psErpbridgeInsert">Insertar estado en la respuesta</button>' +
            '<button type="button" class="psc-btn psc-btn--outline" id="psErpbridgeRetry">Actualizar</button>' +
        '</div>';

        $('#psErpbridgeBody').html(html);
    }

    function renderTracking(list) {
        if (!list.length) { return ''; }
        return '<div class="psc-erpbridge-sec"><span class="psc-erpbridge-sec-t">Seguimiento en Gestión</span>' +
            list.map(function (t) {
                var num = '<span class="mono">' + esc(t.number) + '</span>';
                if (t.url) {
                    num = '<a class="psc-erpbridge-link" href="' + escAttr(t.url) + '" target="_blank" rel="noopener noreferrer">' + num + '</a>';
                }
                return row('Envío ' + (t.date ? day(t.date) : ''), num);
            }).join('') +
        '</div>';
    }

    function lineHtml(l) {
        return '<div class="psc-erpbridge-line">' +
            '<div class="body"><div class="nm">' + esc(l.description || 'Artículo') + '</div>' +
                (l.code ? '<div class="sku">' + esc(l.code) + '</div>' : '') + '</div>' +
            '<div class="qty">' + esc(units(l.units)) + '</div>' +
            '<div class="price">' + money(l.total) + '</div>' +
        '</div>';
    }

    function renderLines(d) {
        var dn = d.delivery_note;
        var e = d.erp;
        var out = '<div class="psc-erpbridge-sec">';

        if (dn) {
            var how = dn.matched_by === 'served_time_and_articles'
                ? 'Albarán creado al servirse el pedido, con sus mismos artículos.'
                : 'Albarán creado al servirse el pedido (no se pudieron leer sus líneas para confirmarlo por artículos).';
            out += '<span class="psc-erpbridge-sec-t">Líneas servidas · albarán ' + esc(dn.number || dn.id) + ' (' + day(dn.date) + ')</span>' +
                '<div class="psc-erpbridge-hint">' + esc(how) + '</div>';
            if (dn.lines && dn.lines.length) {
                out += '<div class="psc-erpbridge-lines">' + dn.lines.map(lineHtml).join('') + '</div>';
            } else if (dn.lines_error) {
                out += note('warn', 'No se pudieron leer las líneas del albarán: ' + dn.lines_error);
            }
        } else {
            out += '<span class="psc-erpbridge-sec-t">Líneas del pedido en Gestión</span>';
            if (d.delivery_note_message) {
                out += '<div class="psc-erpbridge-hint">' + esc(d.delivery_note_message) + '</div>';
            }
            out += (e.lines && e.lines.length)
                ? '<div class="psc-erpbridge-lines">' + e.lines.map(lineHtml).join('') + '</div>'
                : '<div class="bv-po-empty">Gestión no devolvió líneas para este pedido.</div>';
        }

        if (e.shipping_cost !== null && e.shipping_cost !== undefined) {
            out += row('Portes en Gestión', '<span class="mono">' + money(e.shipping_cost) + '</span>');
        }

        return out + '</div>';
    }

    function renderHistory(e) {
        var hist = e.history || [];
        if (!hist.length) {
            return e.history_error ? '<div class="psc-erpbridge-sec">' + note('warn', 'Historial de Gestión no disponible: ' + e.history_error) + '</div>' : '';
        }
        return '<div class="psc-erpbridge-sec"><span class="psc-erpbridge-sec-t">Historial en Gestión</span>' +
            '<div class="psc-erpbridge-hist">' +
            hist.map(function (h, i) {
                var last = i === hist.length - 1;
                return '<div class="psc-erpbridge-hist-i' + (last ? ' is-last' : '') + '">' +
                    '<span class="dot"></span><span class="nm">' + esc(h.name) + '</span>' +
                    '<span class="dt">' + when(h.date) + '</span></div>';
            }).join('') +
            '</div></div>';
    }

    function renderInvoice(inv) {
        var out = '<div class="psc-erpbridge-sec"><span class="psc-erpbridge-sec-t">Factura fiscal de Gestión</span>';

        if (inv.status === 'found') {
            out += '<div class="psc-erpbridge-rows">' +
                row('Número', '<span class="mono">' + esc(inv.number) + '</span>') +
                row('Fecha', day(inv.date)) +
                row('Importe', '<span class="mono">' + money(inv.amount) + '</span>') +
                (inv.simplified ? row('Tipo', 'Factura simplificada') : '') +
            '</div>';
        } else {
            out += note(inv.status === 'denied' ? 'warn' : 'info', inv.message || 'Gestión no tiene factura asociada a este pedido.');
        }

        if (inv.marked_invoiced !== null && inv.marked_invoiced !== undefined) {
            out += row('Marcado como facturado en Gestión', inv.marked_invoiced ? 'Sí' : 'No');
        }

        // Descargar / Enviar por el chat solo existirían si Gestión expusiera
        // el PDF; hoy no lo hace y se explica en vez de ofrecer un botón.
        if (!inv.pdf_available && inv.pdf_message) {
            out += '<div class="psc-erpbridge-hint">' + esc(inv.pdf_message) + '</div>';
        }

        return out + '</div>';
    }

    function summaryText(d) {
        var e = d.erp;
        var ref = (d.ps && (d.ps.reference || d.ps.id)) || '';
        var st = (e.state && e.state.name) || '';
        var txt = 'Tu pedido ' + ref + ' figura en nuestro almacén con el estado «' + st + '»';
        if (e.served_date) {
            var sd = S() && S().date ? S().date(e.served_date, true) : e.served_date;
            txt += ' (servido el ' + sd + ')';
        }
        txt += '.';
        var t = (e.tracking || [])[0];
        if (t && t.number) {
            txt += ' Número de seguimiento del envío: ' + t.number + (t.url ? ' (' + t.url + ')' : '') + '.';
        }
        return txt;
    }

    // ── Eventos ──

    $(document).on('psc:order-rendered', function () {
        _loadedFor = null;
        renderShell();
        if ($('#powPanelPago').is(':visible')) { load(false); }
    });

    $(document).on('click', '#powTabs .bv-po-tab[data-po-tab="pago"]', function () {
        setTimeout(function () { load(false); }, 0);
    });

    $(document).on('click', '#psErpbridgeRetry', function () { load(true); });

    $(document).on('click', '#psErpbridgeInsert', function () {
        if (!_data || !_data.erp) { return; }
        W().insert(summaryText(_data));
    });
})();
