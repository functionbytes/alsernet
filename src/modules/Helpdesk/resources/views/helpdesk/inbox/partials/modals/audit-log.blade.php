{{-- Modal: Actividad de la conversación (audit log) --}}
<div class="bv-modal" data-bv-modal-name="audit-log">
    <div class="bv-modal-dialog lg bv-modal-dialog--audit">
        <div class="bv-modal-head">
            <div class="bv-modal-icon-box"><i class="fas fa-clock-rotate-left"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">SEGURIDAD · AUDITORÍA</span>
                <div class="bv-modal-title">
                    Actividad de la conversación
                    <span class="bv-audit-chip" id="alConvChip"></span>
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body">
            {{-- Loading --}}
            <div class="bv-al-loading" id="alLoading">
                <i class="fas fa-spinner fa-spin"></i> Cargando actividad…
            </div>

            {{-- Filter pills --}}
            <div class="bv-al-pills bv-hidden" id="alPills">
                <span class="media-pill on" data-al-cat="all">Todo <span class="c" id="alCntAll">0</span></span>
                <span class="media-pill" data-al-cat="assign">Asignaciones <span class="c" id="alCntAssign">0</span></span>
                <span class="media-pill" data-al-cat="state">Estados <span class="c" id="alCntState">0</span></span>
                <span class="media-pill" data-al-cat="message">Mensajes <span class="c" id="alCntMessage">0</span></span>
                <span class="media-pill" data-al-cat="tag">Etiquetas <span class="c" id="alCntTag">0</span></span>
                <span class="media-pill" data-al-cat="update">Cambios <span class="c" id="alCntUpdate">0</span></span>
            </div>

            {{-- Audit rows --}}
            <div class="bv-al-list bv-hidden" id="alList"></div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn-primary" id="alBtnExport"><i class="fas fa-file-arrow-down"></i> Exportar log completo</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce
