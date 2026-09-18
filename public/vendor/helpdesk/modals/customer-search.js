/*!
 * Helpdesk · modal "customer-search" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/customer-search.blade.php.
 * La config que antes se interpolaba con Blade (rutas, flags, datos de sesion,
 * textos traducidos) viaja ahora por atributos data-* del markup, que es lo que
 * permite servir este JS como fichero estatico cacheable.
 */
(function () {
    var searchTimer = null;
    var globalSearchUrl = $('[data-bv-modal-name="search-customer"]').data('url-search');

    var channelIconMap = {
        whatsapp: 'fab fa-whatsapp',
        facebook: 'fab fa-facebook-messenger',
        instagram: 'fab fa-instagram',
        email: 'far fa-envelope',
        widget: 'far fa-comment-dots'
    };

    function escapeHtml(str) {
        return $('<div>').text(str || '').html();
    }

    function renderCustomers(items) {
        var $list = $('#gs-list-customers').empty();
        items.forEach(function (c) {
            var initials = (c.name || '?').trim().split(/\s+/).slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase();
            $list.append(
                '<a href="' + escapeHtml(c.url) + '" class="mv4-search-row bv-gs-result">' +
                    '<div class="av c1">' + escapeHtml(initials) + '</div>' +
                    '<div class="body">' +
                        '<div class="row"><b>' + escapeHtml(c.name) + '</b></div>' +
                        '<div class="meta">' + escapeHtml([c.email, c.phone].filter(Boolean).join(' · ')) + '</div>' +
                    '</div>' +
                    '<i class="fas fa-chevron-right chev"></i>' +
                '</a>'
            );
        });
    }

    function renderConversations(items) {
        var $list = $('#gs-list-conversations').empty();
        items.forEach(function (c) {
            var icon = channelIconMap[c.channel] || channelIconMap.widget;
            $list.append(
                '<a href="' + escapeHtml(c.url) + '" class="mv4-search-row bv-gs-result">' +
                    '<div class="av c2"><i class="' + icon + '"></i></div>' +
                    '<div class="body">' +
                        '<div class="row"><b>' + escapeHtml(c.subject || '#' + c.id) + '</b></div>' +
                        '<div class="meta">' + escapeHtml(c.customer_name || '') + '</div>' +
                    '</div>' +
                    '<i class="fas fa-chevron-right chev"></i>' +
                '</a>'
            );
        });
    }

    function renderTags(items) {
        var $list = $('#gs-list-tags').empty();
        items.forEach(function (t) {
            var color = escapeHtml(t.color || '#6c757d');
            $list.append(
                '<a href="' + escapeHtml(t.url) + '" class="mv4-search-row bv-gs-result">' +
                    '<div class="av bv-gs-tag-av" style="--bv-gs-tag-color:' + color + '"><i class="fas fa-tag bv-gs-tag-icon"></i></div>' +
                    '<div class="body">' +
                        '<div class="row"><b>' + escapeHtml(t.name) + '</b></div>' +
                    '</div>' +
                    '<i class="fas fa-chevron-right chev"></i>' +
                '</a>'
            );
        });
    }

    function doSearch(q) {
        if (q.length < 2) {
            $('#csPlaceholder').removeClass('bv-hidden');
            $('#csEmpty, #gs-sec-customers, #gs-sec-conversations, #gs-sec-tags').addClass('bv-hidden');
            $('#csCount').text('Escribe para buscar');
            return;
        }

        $('#csPlaceholder').addClass('bv-hidden');

        $.ajax({
            url: globalSearchUrl,
            method: 'GET',
            data: { q: q },
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(function (data) {
            var customers = data.customers || [];
            var conversations = data.conversations || [];
            var tags = data.tags || [];
            var total = customers.length + conversations.length + tags.length;

            $('#csEmpty').toggleClass('bv-hidden', total > 0);

            if (customers.length) {
                renderCustomers(customers);
                $('#gs-sec-customers').removeClass('bv-hidden');
            } else {
                $('#gs-sec-customers').addClass('bv-hidden');
            }

            if (conversations.length) {
                renderConversations(conversations);
                $('#gs-sec-conversations').removeClass('bv-hidden');
            } else {
                $('#gs-sec-conversations').addClass('bv-hidden');
            }

            if (tags.length) {
                renderTags(tags);
                $('#gs-sec-tags').removeClass('bv-hidden');
            } else {
                $('#gs-sec-tags').addClass('bv-hidden');
            }

            $('#csCount').text(total + ' resultado' + (total !== 1 ? 's' : ''));
        })
        .fail(function () {
            if (window.toastr) { toastr.error('Error al buscar. Intenta de nuevo.'); }
        });
    }

    // Debounced input handler
    $(document).on('input', '#csSearch', function () {
        var q = $(this).val().trim();
        $('#csClear').toggle(q.length > 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { doSearch(q); }, 300);
    });

    $(document).on('click', '#csClear', function () {
        $('#csSearch').val('').trigger('input');
        $(this).hide();
    });

    // Navigate to result URL and close modal on click
    $(document).on('click', '.bv-gs-result', function (e) {
        // Allow default anchor navigation — just close the modal
        var $modal = $(this).closest('.bv-modal');
        $modal.removeClass('on');
        if ($('.bv-modal.on').length === 0) {
            $('body').css('overflow', '');
        }
    });

    // Reset on modal open
    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'search-customer') { return; }
        $('#csSearch').val('').trigger('input');
        setTimeout(function () { $('#csSearch').focus(); }, 100);
    });
}());
