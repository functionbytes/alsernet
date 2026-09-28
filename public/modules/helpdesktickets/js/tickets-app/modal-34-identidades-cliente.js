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
                    ? '<button type="button" class="tkt-btn tkt-btn-sm" data-link-identity="' + escapeHtml(idn.value) + '">' + TKA.t('modal_34_link_btn', 'Vincular') + '</button>'
                    : '';
                var hitsText = idn.hits
                    ? ' · ' + (idn.hits === 1
                        ? TKA.t('modal_34_hits_singular', ':n correo', { ':n': idn.hits })
                        : TKA.t('modal_34_hits_plural', ':n correos', { ':n': idn.hits }))
                    : '';
                return '<div class="tkt-mailitem"><span class="av light"><i class="' + escapeHtml(idn.icon) + '"></i></span>' +
                    '<span class="who"><span class="n">' + escapeHtml(idn.value) + '</span>' +
                    '<span class="s">' + escapeHtml(idn.label + ' · ' + idn.source) + hitsText + '</span></span>' +
                    (idn.is_primary ? '<span class="tkt-rchip ok">' + TKA.t('modal_34_primary_chip', 'Principal') + '</span>' : '') + linkBtn + '</div>';
              }).join('')
            : '<div class="tkt-empty-box">' + TKA.t('modal_34_no_channels_registered', 'Este contacto no tiene ningún canal registrado todavía.') + '</div>';

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-fingerprint', kicker: TKA.t('kicker_customer_identity', 'Cliente · identidad'),
            title: TKA.t('modal_title_customer_identities', 'Identidades del cliente'), width: 'md',
            body: '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> ' + TKA.t('modal_34_multi_channel_note', 'Un mismo cliente puede escribir por formulario, email, WhatsApp o portal: todo se une bajo una sola ficha.') + '</div>' +
                '<div class="tkt-mailitems">' + rows + '</div>' +
                '<div class="tkt-note"><i class="fa-solid fa-triangle-exclamation"></i> ' + TKA.t('modal_34_thread_detected_note', 'Las direcciones marcadas como "detectado en el hilo" no están en la ficha: pulsa "Vincular" si son del mismo cliente, o "Fusionar duplicado" si en realidad pertenecen a otro contacto ya existente.') + '</div>',
            foot: (customer ? '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-identities-merge">' + TKA.t('modal_34_merge_duplicate', 'Fusionar duplicado') + '</button>' : '') +
                  '<button type="button" class="tkt-btn" data-modal-close>' + TKA.t('close', 'Cerrar') + '</button>',
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


