{{-- Modal: Respuesta rápida / plantillas (#63 ve-quick-reply) --}}
<div class="bv-modal" data-bv-modal-name="quick-reply">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-bolt"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.snooze_eyebrow') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.quick_reply_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-modal-search bv-modal-search--hint">
                <i class="fas fa-magnifying-glass"></i>
                <input id="qrSearch" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.quick_reply_search_placeholder') }}" autocomplete="off">
                <span class="bv-kbd">↑↓</span>
            </div>

            {{-- Category pills --}}
            <div class="bv-media-pills bv-x47" id="qrCategoryPills">
                <span class="bv-media-pill on" data-qr-cat="">{{ __('helpdesk::helpdesk.inbox.thread.all_label') }}</span>
            </div>

            {{-- List --}}
            <div class="bv-x48" id="qrList">
                <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            </div>

            {{-- Preview --}}
            <div id="qrPreview" style="display:none;margin-top:10px;padding:10px 12px;background:var(--bv-bg-subtle,#f9fafb);border-radius:6px;font-size:12.5px;color:var(--bv-text,#111827);white-space:pre-wrap"></div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-qr-insert" disabled>{{ __('helpdesk::helpdesk.inbox.thread.insert_template') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.order_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _allReplies  = [];
    var _selId       = null;
    var _selContent  = '';
    var _activeCat   = '';
    var _searchTimer = null;

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function renderReplies(replies) {
        _selId = null; _selContent = '';
        $('#bv-qr-insert').prop('disabled', true);
        $('#qrPreview').hide();

        if (!replies.length) {
            $('#qrList').html('<div class="bv-cv-loading-msg bv-x49"><i class="fas fa-inbox"></i> Sin plantillas</div>');
            return;
        }
        var html = replies.map(function (r) {
            return '<button class="bv-qr-item bv-x50" data-id="' + r.id + '" data-content="' + encodeURIComponent(r.content || '') + '">' +
                '<div class="bv-x51">' +
                    '<div class="bv-x52">' + (r.name || r.title || '') + '</div>' +
                    '<div class="bv-x53">' + (r.content || '').slice(0, 90) + '</div>' +
                '</div>' +
                (r.shortcut ? '<span class="bv-kbd">' + r.shortcut + '</span>' : '') +
                '</button>';
        }).join('');
        $('#qrList').html(html);
    }

    function loadReplies(q) {
        $('#qrList').html('<div class="bv-cv-loading-msg bv-x49"><i class="fas fa-spinner fa-spin"></i></div>');
        $.ajax({
            url: '/panel/helpdesk/canned-replies/search',
            method: 'GET',
            data: { query: q || '', category: _activeCat },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _allReplies = resp.data || resp.replies || resp || [];
            renderReplies(_allReplies);

            // Build category pills
            var cats = {};
            _allReplies.forEach(function (r) { if (r.category) { cats[r.category] = (cats[r.category] || 0) + 1; } });
            var pillsHtml = '<span class="bv-media-pill on" data-qr-cat="">Todas <span class="c">' + _allReplies.length + '</span></span>';
            Object.keys(cats).forEach(function (cat) {
                pillsHtml += '<span class="bv-media-pill" data-qr-cat="' + cat + '">' + cat + ' <span class="c">' + cats[cat] + '</span></span>';
            });
            $('#qrCategoryPills').html(pillsHtml);
        }).fail(function () {
            $('#qrList').html('<div class="bv-cv-loading-msg bv-x49"><i class="fas fa-triangle-exclamation"></i> Error al cargar</div>');
        });
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'quick-reply') { return; }
        _activeCat = '';
        $('#qrSearch').val('').focus();
        loadReplies('');
    });

    $(document).on('input', '#qrSearch', function () {
        clearTimeout(_searchTimer);
        var q = $(this).val().trim();
        _searchTimer = setTimeout(function () { loadReplies(q); }, 200);
    });

    $(document).on('click', '#qrCategoryPills .bv-media-pill', function () {
        $('#qrCategoryPills .bv-media-pill').removeClass('on');
        $(this).addClass('on');
        _activeCat = $(this).data('qr-cat') || '';
        var filtered = _activeCat ? _allReplies.filter(function (r) { return r.category === _activeCat; }) : _allReplies;
        renderReplies(filtered);
    });

    $(document).on('click', '#qrList .bv-qr-item', function () {
        $('#qrList .bv-qr-item').css({ background: '' });
        $(this).css({ background: 'var(--bv-bg-active,#f0fdf4)' });
        _selId = $(this).data('id');
        _selContent = decodeURIComponent($(this).data('content') || '');
        $('#qrPreview').text(_selContent).show();
        $('#bv-qr-insert').prop('disabled', false);
    });

    $(document).on('click', '#bv-qr-insert', function () {
        if (!_selContent) { return; }
        // Insert into the active composer textarea
        var $ta = $('.bv-composer textarea, .bv-composer [contenteditable]').first();
        if ($ta.is('[contenteditable]')) {
            $ta.focus();
            document.execCommand('insertText', false, _selContent);
        } else {
            var cur = $ta.val();
            $ta.val(cur ? cur + '\n' + _selContent : _selContent);
            $ta.trigger('input');
        }
        closeBvModal('quick-reply');
        if (window.toastr) { toastr.success('Plantilla insertada'); }
    });

}(window.jQuery));
</script>
@endpush
@endonce
