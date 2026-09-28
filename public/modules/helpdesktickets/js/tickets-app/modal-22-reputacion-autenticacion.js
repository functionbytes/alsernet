'use strict';

    // ── Modal 22: Reputación y autenticación ──────────────────
    function openReputationModal() {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-shield-halved', kicker: TKA.t('kicker_deliverability_reputation', 'Entregabilidad · reputación'),
            title: TKA.t('modal_title_reputation_auth', 'Reputación y autenticación'), width: 'xl',
            body: '<div id="tkt-rep-body"><div class="tkt-skeleton"></div></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-rep-save">' + TKA.t('save', 'Guardar') + '</button>' +
                '<button type="button" class="tkt-btn" data-modal-close>' + TKA.t('close', 'Cerrar') + '</button>',
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
                tktNotify('success', resp.message || TKA.t('saved_generic', 'Guardado'));
            }).fail(function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo guardar.');
                tktNotify('error', msg);
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
                        (ok ? (warn ? TKA.t('modal_22_status_review', 'revisar') : TKA.t('modal_22_status_ok', 'correcto')) : TKA.t('modal_22_status_not_published', 'no publicado')) + '</span>' +
                        (value ? '<span class="mono d">' + escapeHtml(value) + '</span>' : '') + '</span></div>';
            }
            var dmarcWarn = auth.dmarc && auth.dmarc.policy === 'none';
            var html = '<div class="tkt-headline' + (auth.spf && auth.spf.found && auth.dkim && auth.dkim.found ? '' : ' bad') + '">' +
                    '<div class="t">' + escapeHtml(d.domain || '—') + '</div>' +
                    '<div class="s">' + TKA.t('modal_22_domain_source_note', 'Dominio desde el que sale el correo (:from)', { ':from': escapeHtml(d.from || '—') }) + '</div></div>' +
                (settings.suppressed ? '<div class="tkt-note warn"><i class="fa-solid fa-triangle-exclamation"></i> ' + TKA.t('modal_22_auto_paused_note', 'Envío saliente pausado automáticamente: la tasa de rebote superó el umbral crítico. Se reanuda solo al recuperarse.') + '</div>' : '') +
                '<div class="tkt-authrows">' +
                    authRow('SPF', !!(auth.spf && auth.spf.found), auth.spf && auth.spf.value, false) +
                    authRow('DKIM', !!(auth.dkim && auth.dkim.found),
                        auth.dkim && auth.dkim.selectors && auth.dkim.selectors.length
                            ? TKA.t('modal_22_selectors_count', ':n selector(es)', { ':n': auth.dkim.selectors.length }) + ': ' + auth.dkim.selectors.join(', ') : null, false) +
                    authRow('DMARC', !!(auth.dmarc && auth.dmarc.found),
                        auth.dmarc && auth.dmarc.policy ? 'p=' + auth.dmarc.policy : null, dmarcWarn) +
                '</div>' +
                (dmarcWarn ? '<div class="tkt-note"><i class="fa-solid fa-triangle-exclamation"></i> ' + TKA.t('modal_22_dmarc_none_note', 'DMARC está en :pnone: solo informa, no bloquea la suplantación. Se recomienda :quarantine.', { ':pnone': '<span class="mono">p=none</span>', ':quarantine': '<span class="mono">quarantine</span>' }) + '</div>' : '') +
                (auth.dkim && !auth.dkim.found ? '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> ' + TKA.t('modal_22_dkim_not_found_note', 'No se encontró DKIM entre los selectores habituales. Si usáis uno propio, la comprobación automática no lo detecta.') + '</div>' : '') +
                '<div class="tkt-cap">' + TKA.t('modal_22_last_n_days_label', 'Últimos :n días', { ':n': rates.window_days || 30 }) + '</div>' +
                '<div class="tkt-side-rows">' +
                    sideRow(TKA.t('modal_22_sent_label', 'enviados'), String(rates.sent != null ? rates.sent : '—'), { mono: true }) +
                    sideRow(TKA.t('modal_22_bounce_rate_label', 'tasa de rebote'), rates.bounce_rate != null ? rates.bounce_rate + ' %' : TKA.t('modal_22_no_sends', 'sin envíos'), { mono: true, strong: true }) +
                    sideRow(TKA.t('modal_22_suppressions_label', 'supresiones'), String(rates.suppressed != null ? rates.suppressed : '—'), { mono: true, last: true }) +
                '</div>' +
                '<div class="tkt-cap">' + TKA.t('modal_22_if_bounce_crosses_threshold', 'Si la tasa de rebote cruza el umbral crítico') + '</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-rep-notify"' + (settings.notify_managers ? ' checked' : '') + '> ' + TKA.t('modal_22_notify_managers_checkbox', 'Avisar a los managers por email') + '</label>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-rep-suppress"' + (settings.auto_suppress ? ' checked' : '') + '> ' + TKA.t('modal_22_auto_suppress_checkbox', 'Suprimir automáticamente el envío saliente hasta que se recupere') + '</label>' +
                '<div id="tkt-rep-kpi"></div>';
            $backdrop.find('#tkt-rep-body').html(html);

            // KPI de apertura/clic/latencia: ya las calcula
            // TicketMailsController::stats() para la bandeja de emails.
            $.getJSON(TKA.urls.emailsIndex).done(function (res) {
                var st = res && res.stats;
                if (!st) return;
                $backdrop.find('#tkt-rep-kpi').html(
                    '<div class="tkt-cap">' + TKA.t('modal_22_last_50_outbound_label', 'Últimos 50 correos salientes') + '</div><div class="tkt-kpi4">' +
                        '<div><div class="v">' + st.bounce_rate + '%</div><div class="l">' + TKA.t('modal_22_kpi_bounce', 'Rebote') + '</div></div>' +
                        '<div><div class="v">' + st.opened_rate + '%</div><div class="l">' + TKA.t('modal_22_kpi_opened', 'Apertura') + '</div></div>' +
                        '<div><div class="v">' + st.clicked_rate + '%</div><div class="l">' + TKA.t('modal_22_kpi_clicked', 'Clic') + '</div></div>' +
                        '<div><div class="v">' + (st.avg_latency != null ? st.avg_latency + 's' : '—') + '</div><div class="l">' + TKA.t('modal_22_kpi_latency', 'Latencia') + '</div></div>' +
                    '</div><a href="' + TKA.urls.emailsIndex + '" class="tkt-btn tkt-w-100">' + TKA.t('modal_22_open_mail_inbox_link', 'Abrir bandeja de correos') + '</a>'
                );
            });
        }).fail(function () {
            $backdrop.find('#tkt-rep-body').html('<div class="tkt-empty-box">' + TKA.t('modal_22_reputation_query_failed', 'No se pudo consultar la reputación del dominio.') + '</div>');
        });
    }


