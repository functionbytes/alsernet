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
(function ($) {
    'use strict';

    var _mpAll      = [];
    var _mpFilter   = 'all';
    var _mpView     = 'grid';
    var _mpSelected = [];

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    function fileType(file) {
        var ext = (file.extension || file.type || '').toLowerCase();
        if (['jpg','jpeg','png','gif','webp','svg'].indexOf(ext) !== -1) { return 'image'; }
        if (['mp4','webm','mov','avi'].indexOf(ext) !== -1) { return 'video'; }
        return 'document';
    }

    function fileIcon(type) {
        if (type === 'image')    { return 'far fa-image'; }
        if (type === 'video')    { return 'fas fa-video'; }
        return 'far fa-file-lines';
    }

    function filtered() {
        return _mpAll.filter(function (f) {
            return _mpFilter === 'all' || fileType(f) === _mpFilter;
        });
    }

    function renderGrid() {
        var list = filtered();
        if (!list.length) {
            $('#mediaPanelGrid').html('<div class="bv-cv-loading-msg bv-x35"><i class="fas fa-inbox"></i></div>').show();
            return;
        }
        var isGrid = _mpView === 'grid';
        $('#mediaPanelGrid').toggleClass('bv-media-grid--list', !isGrid);

        var html = list.map(function (f, i) {
            var type  = fileType(f);
            var icon  = fileIcon(type);
            var selCl = _mpSelected.indexOf(i) !== -1 ? ' on' : '';
            var ext   = (f.extension || f.type || '').toUpperCase();
            if (isGrid) {
                return '<div class="bv-media-card' + selCl + '" data-mp-idx="' + i + '">' +
                    '<div class="bv-media-card__thumb bv-media-card__thumb--' + type + '">' +
                        '<i class="' + icon + '"></i>' +
                        '<span class="bv-media-card__ext">' + escHtml(ext) + '</span>' +
                    '</div>' +
                    '<div class="bv-media-card__info">' +
                        '<span class="bv-media-card__name">' + escHtml(f.name) + '</span>' +
                        '<span class="bv-media-card__author">' + escHtml(f.sender || '') + '</span>' +
                        '<div class="bv-media-card__row">' +
                            '<span>' + escHtml(f.size || '') + '</span>' +
                            '<span>' + escHtml(f.date || '') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '</div>';
            }
            return '<div class="bv-media-list-item' + selCl + '" data-mp-idx="' + i + '">' +
                '<i class="' + icon + ' bv-media-list-item__ic"></i>' +
                '<div class="bv-media-list-item__body">' +
                    '<span class="bv-media-card__name">' + escHtml(f.name) + '</span>' +
                    '<span class="bv-media-card__author">' + escHtml(f.sender || '') + ' · ' + escHtml(f.size || '') + ' · ' + escHtml(f.date || '') + '</span>' +
                '</div>' +
                '<span class="bv-media-card__ext">' + escHtml(ext) + '</span>' +
                '</div>';
        }).join('');
        $('#mediaPanelGrid').html(html).show();

        var counts = { all: _mpAll.length, image: 0, video: 0, document: 0 };
        _mpAll.forEach(function (f) { var t = fileType(f); if (counts[t] !== undefined) { counts[t]++; } });
        $('#mpCountAll').text(counts.all);
        $('#mpCountImage').text(counts.image);
        $('#mpCountVideo').text(counts.video);
        $('#mpCountDocument').text(counts.document);

        $('#bv-media-download').prop('disabled', _mpSelected.length === 0);
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'media-panel') { return; }
        _mpAll = []; _mpFilter = 'all'; _mpSelected = [];
        $('.bv-hist-pill[data-mpf]').removeClass('on');
        $('.bv-hist-pill[data-mpf="all"]').addClass('on');
        $('#mediaPanelGrid').hide().empty();
        $('#mediaPanelLoading').show().html('<i class="fas fa-spinner fa-spin"></i>');
        $('#bv-media-download').prop('disabled', true);

        var convId = $('.bv-composer').data('bv-conversation-id') || null;
        if (!convId) {
            $('#mediaPanelLoading').html('<i class="fas fa-triangle-exclamation"></i>');
            return;
        }

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/media',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _mpAll = resp.data || resp || [];
            $('#mediaPanelLoading').hide();
            renderGrid();
        }).fail(function () {
            $('#mediaPanelLoading').html('<i class="fas fa-triangle-exclamation"></i>');
        });
    });

    $(document).on('click', '.bv-hist-pill[data-mpf]', function () {
        $('.bv-hist-pill[data-mpf]').removeClass('on');
        $(this).addClass('on');
        _mpFilter   = $(this).data('mpf');
        _mpSelected = [];
        renderGrid();
    });

    $(document).on('click', '#mpViewToggle button', function () {
        $('#mpViewToggle button').removeClass('on');
        $(this).addClass('on');
        _mpView = $(this).data('mpv');
        renderGrid();
    });

    $(document).on('click', '.bv-media-card, .bv-media-list-item', function () {
        var idx = parseInt($(this).data('mp-idx'), 10);
        var pos = _mpSelected.indexOf(idx);
        if (pos === -1) {
            _mpSelected.push(idx);
            $(this).addClass('on');
        } else {
            _mpSelected.splice(pos, 1);
            $(this).removeClass('on');
        }
        $('#bv-media-download').prop('disabled', _mpSelected.length === 0);
    });

    $(document).on('click', '#bv-media-download', function () {
        var files = _mpSelected.map(function (i) { return _mpAll[i]; });
        var ids   = files.map(function (f) { return f.id; });
        var convId = $('.bv-composer').data('bv-conversation-id') || null;
        if (!convId || !ids.length) { return; }
        window.location.href = '/panel/helpdesk/conversations/' + convId + '/media/download?ids=' + ids.join(',');
    });

}(window.jQuery));
