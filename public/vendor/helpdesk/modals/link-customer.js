/*!
 * Helpdesk · modal "link-customer" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/link-customer.blade.php.
 * La config que antes se interpolaba con Blade (rutas, flags, datos de sesion,
 * textos traducidos) viaja ahora por atributos data-* del markup, que es lo que
 * permite servir este JS como fichero estatico cacheable.
 */
(function () {
    var lcTimer = null;
    var lcSelectedId = null;
    var lcSelectedName = '';

    function resetLinkCustomerModal() {
        clearTimeout(lcTimer);
        lcSelectedId = null;
        lcSelectedName = '';
        $('#link-customer-search').val('');
        $('#link-customer-list').html(emptyHintHtml());
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
            $('#link-customer-list').html(emptyHintHtml());
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
