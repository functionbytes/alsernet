/*!
 * Helpdesk · modal "email-viewer" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/email-viewer.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox (el modal se
 * incluye siempre, sin @if, desde partials/modals.blade.php). Sin interpolacion
 * Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function () {
    var $modal = $('#emailViewerModal');
    var _bsModal = null;

    function getModal() {
        if (!_bsModal) { _bsModal = new bootstrap.Modal($modal.get(0)); }
        return _bsModal;
    }

    function evSetText(id, text) { $(id).text(text || '—'); }

    function evRowToggle(rowId, show) {
        if (show) { $(rowId).removeClass('d-none'); }
        else { $(rowId).addClass('d-none'); }
    }

    function evEsc(text) { return $('<span>').text(text || '').html(); }

    function evSwitchTab(tab) {
        $('.bv-ev-tab').removeClass('on');
        $('.bv-ev-tab[data-ev-tab="' + tab + '"]').addClass('on');
        $('.bv-ev-tabpanel').addClass('d-none');
        $('.bv-ev-tabpanel[data-ev-panel="' + tab + '"]').removeClass('d-none');
    }

    var EV_TRACE_ICONS = {
        ok: 'fa-solid fa-check',
        err: 'fa-solid fa-xmark',
        warn: 'fa-solid fa-clock',
        spam: 'fa-solid fa-flag',
        unknown: 'fa-regular fa-circle-question',
    };

    function evRenderTrace(steps) {
        if (!steps || !steps.length) {
            $('#evTraceList').html('<div class="bv-ev-empty">' + evEsc(window.__evTraceEmptyText || 'Sin datos.') + '</div>');
            return;
        }
        var html = steps.map(function (step) {
            return '<div class="bv-ev-trace-step">'
                + '<span class="bv-ev-trace-icon ' + (step.icon || '') + '"><i class="' + (EV_TRACE_ICONS[step.icon] || EV_TRACE_ICONS.unknown) + '" aria-hidden="true"></i></span>'
                + '<div class="bv-ev-trace-main">'
                    + '<div class="t">' + evEsc(step.title) + '</div>'
                    + '<div class="s">' + evEsc(step.meta) + '</div>'
                + '</div>'
                + '<div class="bv-ev-trace-time">' + evEsc(step.time) + '</div>'
                + '</div>';
        }).join('');
        $('#evTraceList').html(html);
    }

    function evRenderEvents(sel, items, emptyText, kind) {
        var $el = $(sel);
        if (!items || !items.length) {
            $el.html('<div class="bv-ev-empty">' + evEsc(emptyText) + '</div>');
            return;
        }
        var html = items.map(function (it) {
            if (kind === 'click') {
                var urlHtml = it.url
                    ? '<a href="' + evEsc(it.url) + '" target="_blank" rel="noopener" class="mono">' + evEsc(it.url) + '</a>'
                    : '<span class="mono">—</span>';
                var metaBits = [it.at, it.ip, it.user_agent].filter(Boolean).map(evEsc);
                return '<div class="bv-ev-event-row">'
                    + '<span class="bv-ev-event-icon"><i class="fa-solid fa-arrow-pointer" aria-hidden="true"></i></span>'
                    + '<div class="bv-ev-event-main">' + urlHtml + '<span class="s">' + metaBits.join(' · ') + '</span></div>'
                    + '</div>';
            }
            var openMetaBits = [it.source, it.ip, it.user_agent].filter(Boolean).map(evEsc);
            return '<div class="bv-ev-event-row">'
                + '<span class="bv-ev-event-icon"><i class="fa-regular fa-envelope-open" aria-hidden="true"></i></span>'
                + '<div class="bv-ev-event-main"><span class="t">' + evEsc(it.at) + '</span><span class="s">' + openMetaBits.join(' · ') + '</span></div>'
                + '</div>';
        }).join('');
        $el.html(html);
    }

    function evRenderTraceAndOpens(e) {
        window.__evTraceEmptyText = $('#evTabTrace').data('empty-text');

        var hasTrace = Array.isArray(e.trace) && e.trace.length > 0;
        evRowToggle('#evTabTrace', hasTrace);
        if (hasTrace) { evRenderTrace(e.trace); }

        var opens = e.opens, clicks = e.clicks;
        var hasInteractions = !!opens || !!clicks;
        evRowToggle('#evTabOpens', hasInteractions);
        evRowToggle('#evOpensSection', !!opens);
        evRowToggle('#evClicksSection', !!clicks);
        evRowToggle('#evOpensClicksSep', !!opens && !!clicks);
        if (opens) { evRenderEvents('#evOpensList', opens.items, $('#evOpensSection').data('empty-text'), 'open'); }
        if (clicks) { evRenderEvents('#evClicksList', clicks.items, $('#evClicksSection').data('empty-text'), 'click'); }

        evSwitchTab('detail');
    }

    $(document).on('click', '.bv-ev-tab', function () {
        if (!$(this).hasClass('d-none')) { evSwitchTab($(this).data('ev-tab')); }
    });

    window.loadEmailViewer = function (uid) {
        var convId = $('.bv-composer').data('bv-conversation-id');
        if (!convId || !uid) { return; }

        $('#evLoading').removeClass('d-none').html('<i class="fas fa-spinner fa-spin me-2"></i> Cargando…');
        $('#evPreview, #evDetail, #evBottomNote').addClass('d-none');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/emails/' + uid,
            method: 'GET',
            dataType: 'json',
            headers: { 'Accept': 'application/json' },
        }).done(function (e) {
            $('#evTitleText').text(e.subject || 'Visualizar email');
            if (e.id_label) {
                $('#evIdChip').text(e.id_label).removeClass('d-none');
            } else {
                $('#evIdChip').addClass('d-none');
            }

            evSetText('#evSubject', e.subject);
            evSetText('#evTo', Array.isArray(e.to) ? e.to.join(', ') : e.to);

            evSetText('#evMetaId', e.id_label);
            evSetText('#evMetaCreated', e.created_at_formatted);

            if (e.cc && e.cc.length) {
                $('#evCc').text(e.cc.join(', '));
                evRowToggle('#evCcRow', true);
            } else {
                evRowToggle('#evCcRow', false);
            }

            if (e.type_label) {
                $('#evType').text(e.type_label);
                evRowToggle('#evTypeRow', true);
            } else {
                evRowToggle('#evTypeRow', false);
            }

            // failed/bounced/complained/suppressed son estados terminales
            // negativos: se agrupan en "danger" (rojo) en vez de repartirse
            // entre el gris de "queued" y un indigo que no comunicaba nada.
            var EV_DANGER_STATUSES = ['failed', 'bounced', 'complained', 'suppressed'];
            var statusClass = e.status === 'sent' ? 'sent' : (EV_DANGER_STATUSES.indexOf(e.status) !== -1 ? 'danger' : 'queued');
            var statusIco = e.status === 'sent'
                ? '<i class="fas fa-check"></i> '
                : (statusClass === 'danger' ? '<i class="fas fa-xmark"></i> ' : '<i class="far fa-clock"></i> ');
            $('#evStatus').attr('class', 'bv-ev-status ' + statusClass).html(statusIco + (e.status_label || e.status || '—'));

            evSetText('#evMetaSentBy', e.sent_by);
            if (e.sent_by) {
                $('#evSentBy').text(e.sent_by);
                evRowToggle('#evSentByRow', true);
            } else {
                evRowToggle('#evSentByRow', false);
            }

            var sentText = e.sent_at_formatted || e.sent_at || '—';
            var sentHtml = $('<span>').text(sentText).html();
            if (e.sent_at && e.sent_at_human) {
                sentHtml += ' <span class="text-muted">· ' + $('<span>').text(e.sent_at_human).html() + '</span>';
            }
            $('#evSentAt').html(sentHtml);

            if (e.template_name) {
                $('#evTemplate').text(e.template_name);
                evRowToggle('#evTemplateRow', true);
            } else {
                evRowToggle('#evTemplateRow', false);
            }

            if (e.error_message) {
                $('#evError').text(e.error_message);
                evRowToggle('#evErrorRow', true);
            } else {
                evRowToggle('#evErrorRow', false);
            }

            var hasDoc = false;
            if (e.related_order_id) {
                $('#evOrder').text('#' + e.related_order_id);
                evRowToggle('#evOrderRow', true);
                hasDoc = true;
            } else { evRowToggle('#evOrderRow', false); }

            if (e.related_customer_name) {
                $('#evCustomer').text(e.related_customer_name);
                evRowToggle('#evCustomerRow', true);
                hasDoc = true;
            } else { evRowToggle('#evCustomerRow', false); }

            if (e.related_document_status) {
                var docStatusClass = e.related_document_status_code === 'approved' ? 'sent'
                    : (e.related_document_status_code === 'rejected' ? 'failed' : 'queued');
                var docStatusIco = e.related_document_status_code === 'approved'
                    ? '<i class="fas fa-check"></i> '
                    : (e.related_document_status_code === 'rejected' ? '<i class="fas fa-xmark"></i> ' : '');
                $('#evDocStatus').attr('class', 'bv-ev-status ' + docStatusClass)
                    .html(docStatusIco + e.related_document_status);
                evRowToggle('#evDocStatusRow', true);
                hasDoc = true;
            } else { evRowToggle('#evDocStatusRow', false); }

            evRowToggle('#evDocSection', hasDoc);
            evRowToggle('#evBtnDoc', hasDoc);

            if (e.activity_url) {
                $('#evBtnActivity').attr('href', e.activity_url).removeClass('d-none');
            } else {
                $('#evBtnActivity').addClass('d-none').attr('href', '#');
            }

            if (e.body_html) {
                var _style = '<style>body{padding:20px!important;box-sizing:border-box}</style>';
                var _closingHead = '</' + 'head>';
                var _html = e.body_html.indexOf(_closingHead) !== -1
                    ? e.body_html.replace(_closingHead, _style + _closingHead)
                    : _style + e.body_html;
                $('#evIframe').prop('srcdoc', _html);
                $('#evPreview').removeClass('d-none');
            } else {
                $('#evPreview').addClass('d-none');
            }

            evRenderTraceAndOpens(e);

            $('#evDetail').removeClass('d-none');
            evRowToggle('#evBottomNote', true);
            $('#evLoading').addClass('d-none');
        }).fail(function () {
            $('#evLoading').removeClass('d-none').html(
                '<i class="fas fa-triangle-exclamation text-muted me-2"></i> No se pudo cargar el email.'
            );
        });
    };

    // Device toggle (iconos se mantienen porque el botón ES el icono)
    $(document).on('click', '.bv-ev-dt-btn', function () {
        var device = $(this).data('ev-device');
        $('.bv-ev-dt-btn').removeClass('on');
        $(this).addClass('on');
        $('#evIframe').toggleClass('mobile', device === 'mobile');
    });

    $('#evBtnPrint').on('click', function () {
        var $iframe = $('#evIframe');
        if ($iframe.length && $iframe.get(0).contentWindow) {
            $iframe.get(0).contentWindow.print();
        }
    });

    $('#evBtnBack').on('click', function () {
        getModal().hide();
    });

    $modal.on('hidden.bs.modal', function () {
        window._emViewerUid = null;
    });

    // API global para abrir el modal
    window.openEmailViewer = function (uid) {
        window._emViewerUid = uid;
        getModal().show();
        window.loadEmailViewer(uid);
    };
}());
