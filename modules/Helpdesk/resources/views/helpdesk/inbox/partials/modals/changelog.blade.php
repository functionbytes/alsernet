{{-- Modal: Novedades del producto (#86 ve-changelog) --}}
<div class="bv-modal" data-bv-modal-name="changelog">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-bullhorn"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.changelog_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.changelog_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div id="changelogLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            <div id="changelogContent" style="display:none"></div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-changelog-full">Ver changelog completo</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _loaded = false;

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    function renderChangelog(items) {
        var html = items.map(function (item) {
            var liHtml = (item.changes || []).map(function (c) {
                var newBadge = c.is_new ? '<span class="bv-changelog-item__new">NUEVO</span> ' : '';
                return '<li>' + newBadge + escHtml(c.text) + '</li>';
            }).join('');
            return '<div class="bv-changelog-item">' +
                '<div class="bv-changelog-item__meta">' +
                    '<span class="bv-changelog-item__v">' + escHtml(item.version) + '</span>' +
                    '<span class="bv-changelog-item__date">' + escHtml(item.date) + '</span>' +
                '</div>' +
                '<h5 class="bv-changelog-item__title">' + escHtml(item.title) + '</h5>' +
                '<ul>' + liHtml + '</ul>' +
                '</div>';
        }).join('');
        $('#changelogContent').html(html);
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'changelog') { return; }
        if (_loaded) { return; }

        $('#changelogLoading').show();
        $('#changelogContent').hide();

        $.ajax({
            url: '/panel/helpdesk/changelog',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            var items = resp.data || resp || [];
            renderChangelog(items);
            _loaded = true;
            $('#changelogLoading').hide();
            $('#changelogContent').show();
        }).fail(function () {
            $('#changelogLoading').html('<i class="fas fa-triangle-exclamation"></i>');
        });
    });

    $(document).on('click', '#bv-changelog-full', function () {
        window.open('/panel/helpdesk/changelog', '_blank');
    });

}(window.jQuery));
</script>
@endpush
@endonce
