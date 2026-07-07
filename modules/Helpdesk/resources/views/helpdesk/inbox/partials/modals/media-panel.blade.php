{{-- Modal: Archivos compartidos del chat (#20 ve-media-panel) --}}
<div class="bv-modal" data-bv-modal-name="media-panel">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-folder-open"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.media_panel_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.media_panel_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Filtros --}}
            <div class="bv-hist-pills bv-media-pills" id="mediaPanelFilter">
                <button class="bv-hist-pill on" data-mpf="all">{{ __('helpdesk::helpdesk.inbox.modals.all_label') }} <span class="bv-pill-count" id="mpCountAll">0</span></button>
                <button class="bv-hist-pill" data-mpf="image"><i class="far fa-image"></i> <span class="bv-pill-count" id="mpCountImage">0</span></button>
                <button class="bv-hist-pill" data-mpf="video"><i class="fas fa-video"></i> <span class="bv-pill-count" id="mpCountVideo">0</span></button>
                <button class="bv-hist-pill" data-mpf="document"><i class="far fa-file-lines"></i> <span class="bv-pill-count" id="mpCountDocument">0</span></button>
            </div>

            {{-- Vista toggle --}}
            <div class="bv-x32">
                <div class="bv-media-view-toggle" id="mpViewToggle">
                    <button type="button" class="on" data-mpv="grid" title="{{ __('helpdesk::helpdesk.inbox.right.view_grid_title') }}"><i class="fas fa-grip"></i></button>
                    <button type="button" data-mpv="list" title="{{ __('helpdesk::helpdesk.inbox.right.view_list_title') }}"><i class="fas fa-list"></i></button>
                </div>
                <div class="bv-x33"></div>
                <select id="mpSort" class="bv-form-input bv-x34">
                    <option value="recent">{{ __('helpdesk::helpdesk.inbox.right.sort_recent') }}</option>
                    <option value="oldest">{{ __('helpdesk::helpdesk.inbox.modals.media_panel_sort_oldest') }}</option>
                    <option value="size">{{ __('helpdesk::helpdesk.inbox.modals.media_panel_sort_size') }}</option>
                </select>
            </div>

            <div id="mediaPanelLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            <div id="mediaPanelGrid" class="bv-media-grid" style="display:none"></div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-media-download" disabled>{{ __('helpdesk::helpdesk.inbox.right.download_selection') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.media_panel_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce
