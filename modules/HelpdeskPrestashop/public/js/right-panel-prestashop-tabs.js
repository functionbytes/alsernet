/*!
 * HelpdeskPrestashop · tab "Tienda" del panel derecho del inbox.
 *
 * Extraido de resources/views/inbox-slots/right-panel-prestashop-tabs.blade.php,
 * donde vivia como <script> inline que se re-descargaba en CADA carga del
 * inbox (el slot se incluye siempre desde
 * helpdesk/inbox/partials/right-panel.blade.php cuando el módulo está
 * activo). No tiene interpolacion Blade: la config llega por atributos
 * data-* y por window.HDCommerce, que define el core en
 * modals/_commerce-js.blade.php.
 *
 * OJO 1: depende de window.HDCommerce en tiempo de uso (dentro de
 * openPsAddressesModal), asi que debe cargarse DESPUES de _commerce-js — lo
 * garantiza el orden de @push('scripts') (_commerce-js se encola en
 * modals.blade.php, antes de que right-panel.blade.php incluya este slot).
 * OJO 2: la fuente es este fichero; asset() sirve desde
 * public/modules/helpdeskprestashop/js/ — hay que copiarlo alli tras editar.
 */
(function () {
    window.openPsOrdersModal = function () {
        var modal = new bootstrap.Modal(document.getElementById('psOrdersModal'));
        modal.show();
    };

    // Abrir el workspace de pedido PrestaShop (detalle real vía el bridge) al
    // pulsar un pedido de la lista. Cierra el modal-lista de bootstrap antes.
    $(document).on('click', '.rp3-order[data-ps-order-open]', function () {
        var id = $(this).data('order-id');
        if (!id) { return; }
        var lm = bootstrap.Modal.getInstance(document.getElementById('psOrdersModal'));
        if (lm) { lm.hide(); }
        if (typeof window.openPsOrderWorkspace === 'function') {
            window.openPsOrderWorkspace(id);
        }
    });

    window.openPsAddressesModal = function () {
        var $body = $('#psAddressesBody');
        $body.html('<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

        var modal = new bootstrap.Modal(document.getElementById('psAddressesModal'));
        modal.show();

        var base = window.HDCommerce ? window.HDCommerce.base() : null;
        if (!base) {
            $body.html('<p class="text-center text-danger py-3">No hay cliente seleccionado.</p>');
            return;
        }

        $.ajax({
            url: base + '/ps/addresses',
            method: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        }).done(function (r) {
            var addresses = r.addresses || r.data || [];
            if (addresses.length) {
                var html = addresses.map(function (a) {
                    return '<div class="ps-addr-card">' +
                        '<div class="ps-addr-alias">' + esc(a.alias) + '</div>' +
                        '<div class="ps-addr-name">' + esc(a.full_name) + (a.company ? ' · ' + esc(a.company) : '') + '</div>' +
                        '<div class="ps-addr-line">' + esc(a.address1) + (a.address2 ? ', ' + esc(a.address2) : '') + '</div>' +
                        '<div class="ps-addr-city">' + esc(a.postcode) + ' ' + esc(a.city) + (a.country ? ', ' + esc(a.country) : '') + '</div>' +
                        (a.phone ? '<div class="ps-addr-phone"><i class="fas fa-phone"></i> ' + esc(a.phone) + '</div>' : '') +
                    '</div>';
                }).join('');
                $body.html(html);
            } else {
                $body.html('<p class="text-center text-muted py-3">No hay direcciones guardadas.</p>');
            }
        }).fail(function () {
            $body.html('<p class="text-center text-danger py-3">Error al cargar direcciones.</p>');
        });
    };

    function esc(s) {
        return $('<span>').text(String(s || '')).html();
    }
})();
