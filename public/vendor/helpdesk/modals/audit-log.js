/*!
 * Helpdesk · modal "{name}" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/{name}.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function () {
    var alData = null, alCat = 'all';

    var tagLabels = {
        create:  'Creación',
        assign:  'Asignación',
        state:   'Estado',
        tag:     'Etiqueta',
        update:  'Cambio',
        message: 'Mensaje',
        view:    'Acceso',
        export:  'Exportación',
    };

    function alCatOfTag(tag) {
        if (tag === 'assign')  return 'assign';
        if (tag === 'state')   return 'state';
        if (tag === 'tag')     return 'tag';
        if (tag === 'message') return 'message';
        if (['view','export','security'].indexOf(tag) !== -1) return 'security';
        return 'update';
    }

    function alLoad(convId) {
        $('#alLoading').removeClass('bv-hidden').html('<i class="fas fa-spinner fa-spin"></i> Cargando actividad…');
        $('#alPills, #alList').addClass('bv-hidden');
        alData = null; alCat = 'all';

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/audit-log',
            method: 'GET',
            headers: { 'Accept': 'application/json' },
        }).done(function (d) {
            alData = d;
            $('#alConvChip').text('#' + d.id);
            $('#alCntAll').text(d.counts.all);
            $('#alCntAssign').text(d.counts.assign);
            $('#alCntState').text(d.counts.state);
            $('#alCntMessage').text(d.counts.message);
            $('#alCntTag').text(d.counts.tag);
            $('#alCntUpdate').text(d.counts.update);
            $('.bv-al-pills .media-pill').removeClass('on');
            $('.bv-al-pills .media-pill[data-al-cat="all"]').addClass('on');
            alRender('all');
            $('#alLoading').addClass('bv-hidden');
            $('#alPills, #alList').removeClass('bv-hidden');
        }).fail(function () {
            $('#alLoading').html('<i class="fas fa-triangle-exclamation"></i> No se pudo cargar el log.');
        });
    }

    function alRender(cat) {
        if (!alData) return;
        var rows = alData.entries.filter(function (e) {
            return cat === 'all' || alCatOfTag(e.tag) === cat;
        });
        if (!rows.length) {
            $('#alList').html('<div class="bv-al-empty">Sin registros para este filtro</div>');
            return;
        }
        $('#alList').html(rows.map(function (e) {
            return '<div class="bv-audit-row" data-tag="' + e.tag + '">'
                + '<span class="bv-al-ts">' + e.ts + '</span>'
                + '<div class="bv-al-body">'
                +   '<div class="bv-al-act">' + $('<span>').text(e.action).html() + '</div>'
                +   '<div class="bv-al-who">' + $('<span>').text(e.who).html() + '</div>'
                + '</div>'
                + '<span class="bv-al-tag bv-al-tag--' + e.tag + '">' + (tagLabels[e.tag] || e.tag) + '</span>'
                + '</div>';
        }).join(''));
    }

    $(document).on('click', '.bv-al-pills .media-pill', function () {
        $('.bv-al-pills .media-pill').removeClass('on');
        $(this).addClass('on');
        alCat = $(this).data('al-cat');
        alRender(alCat);
    });

    $(document).on('click', '#alBtnExport', function () {
        if (!alData) return;
        var lines = ['Timestamp\tAcción\tUsuario\tTipo'];
        alData.entries.forEach(function (e) {
            lines.push([e.ts, e.action, e.who, e.tag].join('\t'));
        });
        var blob = new Blob([lines.join('\n')], { type: 'text/tab-separated-values' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'audit-conv-' + (alData.id || 'log') + '.tsv';
        a.click();
    });

    (new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            if (m.attributeName !== 'class') return;
            var $el = $(m.target);
            if ($el.hasClass('on')) {
                var convId = $('.bv-composer').data('bv-conversation-id');
                if (convId) alLoad(convId);
            } else {
                alData = null;
            }
        });
    })).observe(document.querySelector('[data-bv-modal-name="audit-log"]'), { attributes: true });
}());
