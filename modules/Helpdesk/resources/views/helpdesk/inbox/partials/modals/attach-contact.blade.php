{{-- Modal: Adjuntar contacto --}}
<div class="bv-modal" data-bv-modal-name="attach-contact">
    <div class="modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-regular fa-id-card"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">COMPOSER · CONTACTO</div>
                <div class="modal-title">Adjuntar contacto</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">

            <div class="minfo">
                <i class="fa-solid fa-circle-info"></i>
                <div>El contacto se enviará al cliente como tarjeta vCard. Podrá guardarlo en su agenda con un toque.</div>
            </div>

            <div class="search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="attach-contact-search" type="text" placeholder="Buscar contacto por nombre, email o teléfono…" autocomplete="off">
            </div>

            <div class="bv-modal-list-label">Contactos</div>
            <div id="attach-contact-list">
                <div class="nc-empty-hint">
                    <i class="fas fa-magnifying-glass"></i>
                    <span>Escribe para buscar contactos</span>
                </div>
            </div>

        </div>
        <div class="modal-foot">
            <button class="btn btn-primary" id="attach-contact-send" disabled>Enviar contacto</button>
            <button class="btn btn-outline" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var acTimer = null;
    var acSelectedId = null;

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'attach-contact') { return; }
        acSelectedId = null;
        clearTimeout(acTimer);
        $('#attach-contact-search').val('');
        $('#attach-contact-list').html('<div class="nc-empty-hint"><i class="fas fa-magnifying-glass"></i><span>Escribe para buscar contactos</span></div>');
        $('#attach-contact-send').prop('disabled', true);
    });

    $(document).on('input', '#attach-contact-search', function () {
        var q = $(this).val().trim();
        clearTimeout(acTimer);
        acSelectedId = null;
        $('#attach-contact-send').prop('disabled', true);
        if (!q) {
            $('#attach-contact-list').html('<div class="nc-empty-hint"><i class="fas fa-magnifying-glass"></i><span>Escribe para buscar contactos</span></div>');
            return;
        }
        $('#attach-contact-list').html('<div class="nc-empty-hint"><i class="fas fa-spinner fa-spin"></i><span>Buscando…</span></div>');
        acTimer = setTimeout(function () {
            $.ajax({
                url: '/panel/helpdesk/customers/search',
                method: 'GET',
                dataType: 'json',
                data: { q: q },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (data) {
                if (!data.length) {
                    $('#attach-contact-list').html('<div class="nc-empty-hint"><i class="fas fa-user-slash"></i><span>Sin resultados para "' + $('<span>').text(q).html() + '"</span></div>');
                    return;
                }
                var html = '';
                $.each(data, function (i, c) {
                    var sub = [c.email, c.phone].filter(Boolean).join(' · ');
                    var initials = (c.name || '?').split(' ').slice(0, 2).map(function (w) { return w[0] || ''; }).join('').toUpperCase() || '?';
                    html += '<button class="list-item" data-contact-id="' + c.id + '" data-contact-name="' + $('<span>').text(c.name).html() + '">' +
                        '<div class="bv-av c' + ((i % 8) + 1) + '">' + $('<span>').text(initials).html() + '</div>' +
                        '<div class="body"><span class="t">' + $('<span>').text(c.name || 'Sin nombre').html() + '</span>' +
                        (sub ? '<span class="s">' + $('<span>').text(sub).html() + '</span>' : '') + '</div>' +
                        '<div class="check"></div></button>';
                });
                $('#attach-contact-list').html(html);
            }).fail(function () {
                $('#attach-contact-list').html('<div class="nc-empty-hint"><i class="fas fa-circle-exclamation"></i><span>Error al buscar. Intenta de nuevo.</span></div>');
            });
        }, 300);
    });

    $(document).on('click', '[data-bv-modal-name="attach-contact"] .list-item', function () {
        $('[data-bv-modal-name="attach-contact"] .list-item').removeClass('on');
        $(this).addClass('on');
        acSelectedId = $(this).data('contact-id');
        $('#attach-contact-send').prop('disabled', false);
    });

    $(document).on('click', '#attach-contact-send', function () {
        if (!acSelectedId) { return; }
        var sendUrl = $('.bv-composer').data('bv-contact-url');
        if (!sendUrl) { toastr.error('No hay conversación activa'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: sendUrl,
            method: 'POST',
            dataType: 'json',
            data: { customer_id: acSelectedId },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
        }).done(function (resp) {
            $('[data-bv-modal-name="attach-contact"]').removeClass('on');
            if (!$('.bv-modal.on').length) { $('body').css('overflow', ''); }
            if (resp.item && typeof window.appendBubbleToThread === 'function') {
                window.appendBubbleToThread(resp.item, false);
            }
        }).fail(function (xhr) {
            toastr.error(xhr?.responseJSON?.message || 'No se pudo enviar el contacto');
            $btn.prop('disabled', false);
        });
    });
}());
</script>
@endpush
@endonce
