{{-- Modal: Buscar globalmente (⌘K) --}}
<div class="bv-modal" data-bv-modal-name="search-customer">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-magnifying-glass bv-modal-title-icon"></i> Buscar</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="mv4-search bv-search-mb0">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="csSearch" placeholder="Busca clientes, conversaciones o etiquetas…" autocomplete="off">
                <button class="x d-none" id="csClear"><i class="fas fa-xmark"></i></button>
            </div>

            <div class="mv4-search-meta">
                <span id="csCount">Escribe para buscar</span>
                <span class="bv-spacer"></span>
            </div>

            <div class="mv4-search-list" id="csResults">
                {{-- Placeholder inicial --}}
                <div class="bv-gs-placeholder" id="csPlaceholder">
                    <i class="fas fa-magnifying-glass"></i>
                    <p>Escribe al menos 2 caracteres para buscar</p>
                </div>

                {{-- Sección: Clientes --}}
                <div class="bv-gs-section d-none" id="gs-sec-customers">
                    <div class="bv-gs-section-title">
                        <i class="fas fa-users"></i> Clientes
                    </div>
                    <div id="gs-list-customers"></div>
                </div>

                {{-- Sección: Conversaciones --}}
                <div class="bv-gs-section d-none" id="gs-sec-conversations">
                    <div class="bv-gs-section-title">
                        <i class="far fa-comments"></i> Conversaciones
                    </div>
                    <div id="gs-list-conversations"></div>
                </div>

                {{-- Sección: Etiquetas --}}
                <div class="bv-gs-section d-none" id="gs-sec-tags">
                    <div class="bv-gs-section-title">
                        <i class="fas fa-tag"></i> Etiquetas
                    </div>
                    <div id="gs-list-tags"></div>
                </div>

                <div class="mv4-empty d-none" id="csEmpty">
                    <i class="far fa-circle-question"></i>
                    <p>Sin resultados para tu búsqueda</p>
                    <a href="{{ route('manager.helpdesk.customers.create') }}" class="btn-primary bv-empty-create-btn">
                        <i class="fas fa-user-plus"></i> Crear cliente nuevo
                    </a>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <a href="{{ route('manager.helpdesk.customers.create') }}" class="btn-secondary">Crear nuevo cliente</a>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var searchTimer = null;
    var globalSearchUrl = '{{ route('manager.helpdesk.search.global') }}';

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
            $('#csPlaceholder').removeClass('d-none');
            $('#csEmpty, #gs-sec-customers, #gs-sec-conversations, #gs-sec-tags').addClass('d-none');
            $('#csCount').text('Escribe para buscar');
            return;
        }

        $('#csPlaceholder').addClass('d-none');

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

            $('#csEmpty').toggleClass('d-none', total > 0);

            if (customers.length) {
                renderCustomers(customers);
                $('#gs-sec-customers').removeClass('d-none');
            } else {
                $('#gs-sec-customers').addClass('d-none');
            }

            if (conversations.length) {
                renderConversations(conversations);
                $('#gs-sec-conversations').removeClass('d-none');
            } else {
                $('#gs-sec-conversations').addClass('d-none');
            }

            if (tags.length) {
                renderTags(tags);
                $('#gs-sec-tags').removeClass('d-none');
            } else {
                $('#gs-sec-tags').addClass('d-none');
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
</script>
@endpush
