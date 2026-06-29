{{-- Modal: Emails enviados de la conversación (#25 ve-emails-panel) --}}
<div class="bv-modal" data-bv-modal-name="emails-panel">
    <div class="bv-modal-dialog sm bv-em-dialog">
        <div class="bv-modal-head bv-em-head-bar">
            <div class="bv-tk-panel-head">
                <span class="bv-tk-num" id="emPanelCount">0</span>
                <div class="bv-tk-meta">
                    <span class="bv-tk-lbl">Emails</span>
                    <span class="bv-tk-sub" id="emPanelSub">—</span>
                </div>
                <button class="bv-tk-add-btn tt" title="Nuevo email" data-tt="Nuevo email"
                        data-bv-close data-bv-open="email">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body bv-em-body">

            {{-- Filter pills --}}
            <div class="bv-em-filter-row">
                <span class="bv-media-pill bv-em-pill on" data-em-filter="all">
                    Todos <span class="c" id="emCountAll">0</span>
                </span>
                <span class="bv-media-pill bv-em-pill" data-em-filter="sent">
                    Enviados <span class="c" id="emCountSent">0</span>
                </span>
                <span class="bv-media-pill bv-em-pill" data-em-filter="failed">
                    Fallidos <span class="c" id="emCountFailed">0</span>
                </span>
            </div>

            {{-- List --}}
            <div class="bv-em-list" id="emList">
                <div class="bv-em-loading">
                    <i class="fas fa-spinner fa-spin"></i> Cargando…
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" data-bv-close data-bv-open="email">Nuevo email</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var _allEmails = [];
    var _activeFilter = 'all';

    function modal() { return $('[data-bv-modal-name="emails-panel"]'); }

    (new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            if (m.attributeName !== 'class') { return; }
            if (!$(m.target).hasClass('on')) { return; }
            loadEmails();
        });
    })).observe(document.querySelector('[data-bv-modal-name="emails-panel"]'), { attributes: true });

    function loadEmails() {
        var convId = $('.bv-composer').data('bv-conversation-id');
        if (!convId) { return; }

        $('#emList').html('<div class="bv-em-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/emails',
            method: 'GET',
            dataType: 'json',
            headers: { 'Accept': 'application/json' },
        }).done(function (resp) {
            _allEmails = resp.emails || [];
            var counts = resp.counts || {};

            $('#emPanelCount').text(_allEmails.length);
            var sentCount = counts.sent || 0;
            var failedCount = counts.failed || 0;
            var sub = sentCount + ' enviado' + (sentCount !== 1 ? 's' : '');
            if (failedCount > 0) { sub += ' · ' + failedCount + ' fallido' + (failedCount !== 1 ? 's' : ''); }
            $('#emPanelSub').text(sub);

            $('#emCountAll').text(_allEmails.length);
            $('#emCountSent').text(sentCount);
            $('#emCountFailed').text(failedCount);

            renderList(_activeFilter);
        }).fail(function () {
            $('#emList').html('<div class="bv-em-empty">No se pudieron cargar los emails.</div>');
        });
    }

    function renderList(filter) {
        _activeFilter = filter;
        var emails = _allEmails.filter(function (e) {
            if (filter === 'all') { return true; }
            return e.status === filter;
        });

        if (!emails.length) {
            $('#emList').html('<div class="bv-em-empty">No hay emails' + (filter !== 'all' ? ' en este estado' : '') + '.</div>');
            return;
        }

        var html = emails.map(function (e) {
            var statusClass = e.status === 'sent' ? 'sent' : (e.status === 'failed' ? 'failed' : 'queued');
            var statusLabel = e.status_label || e.status;
            var attHtml = e.attachments_count > 0
                ? '<span class="bv-em-att"><i class="fas fa-paperclip"></i> ' + e.attachments_count + '</span>'
                : '';
            var toText = $('<span>').text(e.to).html();
            var subjectText = $('<span>').text(e.subject).html();
            var previewText = e.preview ? $('<span>').text(e.preview).html() : '';
            var previewHtml = previewText
                ? '<div class="bv-em-preview">' + previewText + '</div>'
                : '';

            return '<button class="bv-em-card" data-em-uid="' + e.uid + '">' +
                '<div class="bv-em-head">' +
                '<i class="far fa-envelope-open bv-em-icon"></i>' +
                '<span class="bv-em-to">' + toText + '</span>' +
                '<span class="bv-em-status ' + statusClass + '">' + statusLabel + '</span>' +
                '</div>' +
                '<div class="bv-em-subject">' + subjectText + '</div>' +
                previewHtml +
                '<div class="bv-em-foot">' +
                attHtml +
                '<span class="bv-em-date">' + (e.date_human || '') + '</span>' +
                '</div>' +
            '</button>';
        }).join('');

        $('#emList').html(html);
    }

    modal().on('click', '.bv-em-pill', function () {
        $('.bv-em-pill').removeClass('on');
        $(this).addClass('on');
        renderList($(this).data('em-filter'));
    });

    modal().on('click', '.bv-em-card', function () {
        var uid = $(this).data('em-uid');
        if (!uid) { return; }
        window._emViewerUid = uid;
        $('[data-bv-modal-name="emails-panel"]').removeClass('on');
        setTimeout(function () {
            $('[data-bv-modal-name="email-viewer"]').addClass('on');
            $('body').css('overflow', 'hidden');
            if (typeof window.loadEmailViewer === 'function') {
                window.loadEmailViewer(uid);
            }
        }, 80);
    });
}());
</script>
@endpush
@endonce
