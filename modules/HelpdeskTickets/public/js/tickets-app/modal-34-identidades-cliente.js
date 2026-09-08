'use strict';

    // ── Modal 34: Identidades del cliente ─────────────────────
    function openIdentitiesModal(t, identities, customer) {
        var list = identities || [];
        var rows = list.length
            ? list.map(function (idn) {
                // "Vincular" solo tiene sentido para lo que NO está ya en la
                // ficha (source 'detectado en el hilo'): las demás filas ya
                // son columnas propias de Customer o helpdesk_customer_external_ids.
                var linkBtn = (idn.source === 'detectado en el hilo' && customer)
                    ? '<button type="button" class="tkt-btn tkt-btn-sm" data-link-identity="' + escapeHtml(idn.value) + '">Vincular</button>'
                    : '';
                return '<div class="tkt-mailitem"><span class="av light"><i class="' + escapeHtml(idn.icon) + '"></i></span>' +
                    '<span class="who"><span class="n">' + escapeHtml(idn.value) + '</span>' +
                    '<span class="s">' + escapeHtml(idn.label + ' · ' + idn.source +
                        (idn.hits ? ' · ' + idn.hits + (idn.hits === 1 ? ' correo' : ' correos') : '')) + '</span></span>' +
                    (idn.is_primary ? '<span class="tkt-rchip ok">Principal</span>' : '') + linkBtn + '</div>';
              }).join('')
            : '<div class="tkt-empty-box">Este contacto no tiene ningún canal registrado todavía.</div>';

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-fingerprint', kicker: 'Cliente · identidad',
            title: 'Identidades del cliente', width: 'md',
            body: '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Un mismo cliente puede escribir por formulario, email, WhatsApp o portal: todo se une bajo una sola ficha.</div>' +
                '<div class="tkt-mailitems">' + rows + '</div>' +
                '<div class="tkt-note"><i class="fa-solid fa-triangle-exclamation"></i> Las direcciones marcadas como "detectado en el hilo" no están en la ficha: pulsa "Vincular" si son del mismo cliente, o "Fusionar duplicado" si en realidad pertenecen a otro contacto ya existente.</div>',
            foot: (customer ? '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-identities-merge">Fusionar duplicado</button>' : '') +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // Ambos botones abren el mismo flujo de fusión ya existente
        // (ContactsMergeController vía openContactMergeModal) — "Vincular"
        // solo le adelanta la búsqueda con el email detectado, en vez de que
        // el agente lo copie y pegue a mano.
        $backdrop.on('click', '#tkt-identities-merge', function () {
            closeModal();
            openContactMergeModal(customer);
        });

        $backdrop.on('click', '[data-link-identity]', function () {
            var email = $(this).data('link-identity');
            closeModal();
            var $mergeBackdrop = openContactMergeModal(customer);
            $mergeBackdrop.find('#tkt-merge-search-input').val(email).trigger('input');
        });
    }


