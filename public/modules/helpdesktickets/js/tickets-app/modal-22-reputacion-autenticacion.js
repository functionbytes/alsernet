'use strict';

    // ── Modal 22: Reputación y autenticación ──────────────────
    function openReputationModal() {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-shield-halved', kicker: 'Entregabilidad · reputación',
            title: 'Reputación y autenticación', width: 'xl',
            body: '<div id="tkt-rep-body"><div class="tkt-skeleton"></div></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-rep-save">Guardar</button>' +
                '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));
        if (!TKA.urls.reputation) return;

        $backdrop.on('click', '#tkt-rep-save', function () {
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: TKA.urls.reputation,
                method: 'PATCH',
                data: {
                    notify_managers: $backdrop.find('#tkt-rep-notify').is(':checked') ? 1 : 0,
                    auto_suppress: $backdrop.find('#tkt-rep-suppress').is(':checked') ? 1 : 0,
                },
            }).done(function (resp) {
                if (window.toastr) toastr.success(resp.message || 'Guardado'); else window.alert(resp.message || 'Guardado');
            }).fail(function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo guardar.');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        $.getJSON(TKA.urls.reputation).done(function (d) {
            var auth = d.auth || {}, rates = d.rates || {}, settings = d.settings || {};
            // Un registro que falta no es un detalle estético: sin SPF/DKIM el
            // correo acaba en spam, y un DMARC en p=none no protege de nada.
            function authRow(label, ok, value, warn) {
                return '<div class="tkt-authrow"><span class="k">' + escapeHtml(label) + '</span>' +
                    '<span class="v"><span class="tkt-rchip' + (ok && !warn ? ' ok' : (ok ? '' : ' strong')) + '">' +
                        (ok ? (warn ? 'revisar' : 'correcto') : 'no publicado') + '</span>' +
                        (value ? '<span class="mono d">' + escapeHtml(value) + '</span>' : '') + '</span></div>';
            }
            var dmarcWarn = auth.dmarc && auth.dmarc.policy === 'none';
            var html = '<div class="tkt-headline' + (auth.spf && auth.spf.found && auth.dkim && auth.dkim.found ? '' : ' bad') + '">' +
                    '<div class="t">' + escapeHtml(d.domain || '—') + '</div>' +
                    '<div class="s">Dominio desde el que sale el correo (' + escapeHtml(d.from || '—') + ')</div></div>' +
                (settings.suppressed ? '<div class="tkt-note warn"><i class="fa-solid fa-triangle-exclamation"></i> Envío saliente pausado automáticamente: la tasa de rebote superó el umbral crítico. Se reanuda solo al recuperarse.</div>' : '') +
                '<div class="tkt-authrows">' +
                    authRow('SPF', !!(auth.spf && auth.spf.found), auth.spf && auth.spf.value, false) +
                    authRow('DKIM', !!(auth.dkim && auth.dkim.found),
                        auth.dkim && auth.dkim.selectors && auth.dkim.selectors.length
                            ? auth.dkim.selectors.length + ' selector(es): ' + auth.dkim.selectors.join(', ') : null, false) +
                    authRow('DMARC', !!(auth.dmarc && auth.dmarc.found),
                        auth.dmarc && auth.dmarc.policy ? 'p=' + auth.dmarc.policy : null, dmarcWarn) +
                '</div>' +
                (dmarcWarn ? '<div class="tkt-note"><i class="fa-solid fa-triangle-exclamation"></i> DMARC está en <span class="mono">p=none</span>: solo informa, no bloquea la suplantación. Se recomienda <span class="mono">quarantine</span>.</div>' : '') +
                (auth.dkim && !auth.dkim.found ? '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> No se encontró DKIM entre los selectores habituales. Si usáis uno propio, la comprobación automática no lo detecta.</div>' : '') +
                '<div class="tkt-cap">Últimos ' + (rates.window_days || 30) + ' días</div>' +
                '<div class="tkt-side-rows">' +
                    sideRow('enviados', String(rates.sent != null ? rates.sent : '—'), { mono: true }) +
                    sideRow('tasa de rebote', rates.bounce_rate != null ? rates.bounce_rate + ' %' : 'sin envíos', { mono: true, strong: true }) +
                    sideRow('supresiones', String(rates.suppressed != null ? rates.suppressed : '—'), { mono: true, last: true }) +
                '</div>' +
                '<div class="tkt-cap">Si la tasa de rebote cruza el umbral crítico</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-rep-notify"' + (settings.notify_managers ? ' checked' : '') + '> Avisar a los managers por email</label>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-rep-suppress"' + (settings.auto_suppress ? ' checked' : '') + '> Suprimir automáticamente el envío saliente hasta que se recupere</label>' +
                '<div id="tkt-rep-kpi"></div>';
            $backdrop.find('#tkt-rep-body').html(html);

            // KPI de apertura/clic/latencia: ya las calcula
            // TicketMailsController::stats() para la bandeja de emails.
            $.getJSON(TKA.urls.emailsIndex).done(function (res) {
                var st = res && res.stats;
                if (!st) return;
                $backdrop.find('#tkt-rep-kpi').html(
                    '<div class="tkt-cap">Últimos 50 correos salientes</div><div class="tkt-kpi4">' +
                        '<div><div class="v">' + st.bounce_rate + '%</div><div class="l">Rebote</div></div>' +
                        '<div><div class="v">' + st.opened_rate + '%</div><div class="l">Apertura</div></div>' +
                        '<div><div class="v">' + st.clicked_rate + '%</div><div class="l">Clic</div></div>' +
                        '<div><div class="v">' + (st.avg_latency != null ? st.avg_latency + 's' : '—') + '</div><div class="l">Latencia</div></div>' +
                    '</div><a href="' + TKA.urls.emailsIndex + '" class="tkt-btn tkt-w-100">Abrir bandeja de correos</a>'
                );
            });
        }).fail(function () {
            $backdrop.find('#tkt-rep-body').html('<div class="tkt-empty-box">No se pudo consultar la reputación del dominio.</div>');
        });
    }


