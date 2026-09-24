/**
 * Contactos 360 · puente con el panel PrestaShop del chat
 * (contacts/partials/_ps-chat-bridge.blade.php).
 *
 * Aporta en la ficha lo que el inbox da por hecho a los JS de
 * HelpdeskPrestashop, sin modificarlos:
 *  - cierre de los .bv-modal (botón [data-bv-close], fondo y Escape);
 *  - el "composer": lo que se inserta en .bv-composer-input se copia al
 *    portapapeles, porque en la ficha no hay conversación abierta;
 *  - la carga del contexto de la tienda (PscStore.load), en diferido.
 */
(function ($) {
    'use strict';

    if (!$('.c360-ps-host').length) {
        return;
    }

    $('body').addClass('c360-has-ps-bridge');

    // "Enviar de la lista de deseos": el chat lo abre con HDCommerce (su JS
    // se carga en el evento bv:modal:open); aquí se expone para la ficha.
    if (typeof window.openPsWishlistSend !== 'function') {
        window.openPsWishlistSend = function () {
            if (window.HDCommerce) {
                window.HDCommerce.open('ps-wishlist-send');
            }
        };
    }

    function closeModal($m) {
        if (!$m || !$m.length || $m.is('#external-search-modal')) {
            return;
        }
        $m.removeClass('on');
        if (!$('.bv-modal.on').length) {
            $('body').css('overflow', '');
        }
    }

    $(document).on('click', '.bv-modal [data-bv-close]', function () {
        closeModal($(this).closest('.bv-modal'));
    });

    $(document).on('click', '.bv-modal', function (e) {
        if (e.target === this) {
            closeModal($(this));
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal($('.bv-modal.on').last());
        }
    });

    // "Insertar en el chat" → portapapeles. Se dispara desde el propio clic
    // del agente, así que el navegador permite escribir en el portapapeles.
    $(document).on('input', '.c360-composer-bridge', function () {
        var text = String($(this).val() || '').trim();
        if (!text) {
            return;
        }
        $(this).val('');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () {
                if (window.toastr) {
                    toastr.success('Texto copiado: pégalo en la conversación o el email del cliente.');
                }
            });
        }
    });

    // El texto del toast de sus JS habla del "composer": en la ficha se
    // sustituye por el aviso de arriba.
    if (window.toastr && window.toastr.success) {
        var original = window.toastr.success;
        window.toastr.success = function (msg) {
            if (typeof msg === 'string' && /composer/i.test(msg)) {
                return null;
            }
            return original.apply(this, arguments);
        };
    }

    // En la ficha, "insertar/enviar al chat" copia al portapapeles: se
    // reetiquetan esos botones para que digan lo que hacen aquí.
    var LABELS = [
        [/^Insertar en el chat$/i, 'Copiar para el cliente'],
        [/^Enviar seguimiento al chat$/i, 'Copiar seguimiento'],
        [/^Enviar por el chat$/i, 'Copiar para enviar'],
        [/^Avisar al cliente en el chat$/i, 'Copiar aviso para el cliente'],
        [/^Recomendar en (el )?chat$/i, 'Copiar recomendación'],
        [/^Enviar resumen al chat$/i, 'Copiar resumen'],
        [/^Enviar comparación al chat$/i, 'Copiar comparación'],
        [/^Enviar datos de pago al chat$/i, 'Copiar datos de pago'],
        [/^Enviar (.+) al chat$/i, 'Copiar $1']
    ];

    function relabel(root) {
        $(root).find('button, a').each(function () {
            if (this.children.length > 1) {
                return;
            }
            var text = $.trim($(this).text());
            if (!/chat/i.test(text)) {
                return;
            }
            for (var i = 0; i < LABELS.length; i++) {
                if (LABELS[i][0].test(text)) {
                    var next = text.replace(LABELS[i][0], LABELS[i][1]);
                    if (this.children.length === 1) {
                        // Botón con icono: se conserva el icono.
                        $(this).contents().filter(function () { return this.nodeType === 3 && $.trim(this.nodeValue); }).last().replaceWith(' ' + next);
                    } else {
                        $(this).text(next);
                    }
                    return;
                }
            }
        });
    }

    var relabelQueued = false;
    function queueRelabel() {
        if (relabelQueued) {
            return;
        }
        relabelQueued = true;
        setTimeout(function () {
            relabelQueued = false;
            relabel(document.body);
        }, 60);
    }

    $(function () {
        relabel(document.body);
        if (window.MutationObserver) {
            $('.bv-modal, .c360-ps-host').each(function () {
                new MutationObserver(queueRelabel).observe(this, { childList: true, subtree: true });
            });
        }

        // Contexto de la tienda para los espacios de trabajo (misma llamada
        // /ps/orders que hace el tab Tienda del chat), sin competir con la
        // carga inicial de la ficha.
        setTimeout(function () {
            if (window.PscStore && typeof window.PscStore.load === 'function') {
                // Sin refresh(): repinta el tab oculto pidiendo ?fresh=1 y se
                // saltaría la caché del contexto (otra llamada a la tienda).
                window.PscStore.load(false);
            }
        }, 1200);
    });
})(window.jQuery);
