{{-- Modal: Vincular a otro cliente --}}
<div class="bv-modal" data-bv-modal-name="link-customer">
    <div class="modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-link"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">{{ __('helpdesk::helpdesk.inbox.modals.link_customer_label') }}</div>
                <div class="modal-title">{{ __('helpdesk::helpdesk.inbox.modals.link_customer_title') }}</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">

            {{-- Paso 1: búsqueda --}}
            <div id="link-customer-step-search">
                <div class="minfo">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>{{ __('helpdesk::helpdesk.inbox.modals.link_customer_info') }}</div>
                </div>

                <div class="search-field">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="link-customer-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.link_customer_search_placeholder') }}" autocomplete="off">
                </div>

                <div class="bv-modal-list-label">{{ __('helpdesk::helpdesk.inbox.modals.link_customer_list_label') }}</div>
                <div id="link-customer-list">
                    <div class="nc-empty-hint">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.link_customer_empty_hint') }}</span>
                    </div>
                </div>
            </div>

            {{-- Paso 2: confirmación --}}
            <div id="link-customer-step-confirm" class="bv-hidden">
                <div class="minfo">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        {{ __('helpdesk::helpdesk.inbox.modals.link_customer_confirm_prefix') }}
                        <b id="link-customer-confirm-name"></b>{{ __('helpdesk::helpdesk.inbox.modals.link_customer_confirm_suffix') }}
                    </div>
                </div>
            </div>

        </div>
        <div class="modal-foot">
            <div id="link-customer-foot-search">
                <button class="btn btn-primary" id="link-customer-continue" disabled>{{ __('helpdesk::helpdesk.inbox.modals.link_customer_continue') }}</button>
                <button class="btn btn-outline" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
            </div>
            <div id="link-customer-foot-confirm" class="bv-hidden">
                <button class="btn btn-primary" id="link-customer-submit">{{ __('helpdesk::helpdesk.inbox.modals.link_customer_confirm_button') }}</button>
                <button class="btn btn-outline" id="link-customer-back">{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var lcTimer = null;
    var lcSelectedId = null;
    var lcSelectedName = '';

    function resetLinkCustomerModal() {
        clearTimeout(lcTimer);
        lcSelectedId = null;
        lcSelectedName = '';
        $('#link-customer-search').val('');
        $('#link-customer-list').html('<div class="nc-empty-hint"><i class="fas fa-magnifying-glass"></i><span>{{ __('helpdesk::helpdesk.inbox.modals.link_customer_empty_hint') }}</span></div>');
        $('#link-customer-continue').prop('disabled', true);
        showLinkCustomerStep('search');
    }

    function showLinkCustomerStep(step) {
        $('#link-customer-step-search, #link-customer-foot-search').toggleClass('bv-hidden', step !== 'search');
        $('#link-customer-step-confirm, #link-customer-foot-confirm').toggleClass('bv-hidden', step !== 'confirm');
    }

    window.openLinkCustomerModal = function () {
        resetLinkCustomerModal();
        HDCommerce.open('link-customer');
    };

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'link-customer') { return; }
        resetLinkCustomerModal();
    });

    $(document).on('input', '#link-customer-search', function () {
        var q = $(this).val().trim();
        clearTimeout(lcTimer);
        lcSelectedId = null;
        lcSelectedName = '';
        $('#link-customer-continue').prop('disabled', true);
        if (!q) {
            $('#link-customer-list').html('<div class="nc-empty-hint"><i class="fas fa-magnifying-glass"></i><span>{{ __('helpdesk::helpdesk.inbox.modals.link_customer_empty_hint') }}</span></div>');
            return;
        }
        $('#link-customer-list').html('<div class="nc-empty-hint"><i class="fas fa-spinner fa-spin"></i><span>Buscando…</span></div>');
        lcTimer = setTimeout(function () {
            $.ajax({
                url: '/panel/helpdesk/customers/search',
                method: 'GET',
                dataType: 'json',
                data: { q: q },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (data) {
                if (!data.length) {
                    $('#link-customer-list').html('<div class="nc-empty-hint"><i class="fas fa-user-slash"></i><span>Sin resultados para "' + $('<span>').text(q).html() + '"</span></div>');
                    return;
                }
                var html = '';
                $.each(data, function (i, c) {
                    var sub = [c.email, c.phone].filter(Boolean).join(' · ');
                    var initials = (c.name || '?').split(' ').slice(0, 2).map(function (w) { return w[0] || ''; }).join('').toUpperCase() || '?';
                    html += '<button class="list-item" data-customer-id="' + c.id + '" data-customer-name="' + $('<span>').text(c.name).html() + '">' +
                        '<div class="bv-av c' + ((i % 8) + 1) + '">' + $('<span>').text(initials).html() + '</div>' +
                        '<div class="body"><span class="t">' + $('<span>').text(c.name || 'Sin nombre').html() + '</span>' +
                        (sub ? '<span class="s">' + $('<span>').text(sub).html() + '</span>' : '') + '</div>' +
                        '<div class="check"></div></button>';
                });
                $('#link-customer-list').html(html);
            }).fail(function () {
                $('#link-customer-list').html('<div class="nc-empty-hint"><i class="fas fa-circle-exclamation"></i><span>Error al buscar. Intenta de nuevo.</span></div>');
            });
        }, 300);
    });

    $(document).on('click', '[data-bv-modal-name="link-customer"] .list-item', function () {
        $('[data-bv-modal-name="link-customer"] .list-item').removeClass('on');
        $(this).addClass('on');
        lcSelectedId = $(this).data('customer-id');
        lcSelectedName = $(this).data('customer-name');
        $('#link-customer-continue').prop('disabled', false);
    });

    $(document).on('click', '#link-customer-continue', function () {
        if (!lcSelectedId) { return; }
        $('#link-customer-confirm-name').text(lcSelectedName);
        showLinkCustomerStep('confirm');
    });

    $(document).on('click', '#link-customer-back', function () {
        showLinkCustomerStep('search');
    });

    $(document).on('click', '#link-customer-submit', function () {
        if (!lcSelectedId) { return; }
        var url = $('.bv-composer').data('bv-link-customer-url');
        if (!url) { toastr.error('No hay conversación activa'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data: { customer_id: lcSelectedId },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
        }).done(function (resp) {
            toastr.success(resp.message);
            HDCommerce.close('link-customer');
            window.location.reload();
        }).fail(function (xhr) {
            toastr.error(xhr?.responseJSON?.message || 'No se pudo vincular el cliente');
            $btn.prop('disabled', false);
        });
    });
}());
</script>
@endpush
@endonce
