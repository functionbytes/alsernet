/*!
 * HelpdeskPrestashop · modal "ps-wishlist-send" del inbox.
 *
 * Busca en la wishlist de PrestaShop del cliente y prepara un producto en el
 * composer del chat (nunca lo envía). Mismo criterio que product-recommend.js:
 * fichero propio, sin interpolación Blade, depende de window.HDCommerce
 * (definido en modals/_commerce-js.js, que se carga antes por orden de
 * @include en modals.blade.php).
 *
 * OJO: la fuente es este fichero; asset() sirve desde
 * public/modules/helpdeskprestashop/js/ — copiarlo ahí tras editar.
 *
 * OJO 2 (sin validar): el shape de cada item de /ps/wishlist (name, image,
 * reference, quantity, url, price) no tiene ningún consumidor JS existente
 * en el repo. El render de abajo es defensivo (valores por defecto en todos
 * los campos), pero falta confirmarlo contra datos reales de desarrollo.
 */
(function () {
    // ── Helpers ──────────────────────────────────────────────────────────────

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = String(s || '');
        return d.innerHTML;
    }

    function money(v) {
        var n = parseFloat(v) || 0;
        return n.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
    }

    function safeImg(url, alt) {
        return '<img src="' + esc(url) + '" alt="' + esc(alt || '') + '" loading="lazy" class="ps-img-safe">';
    }

    function stockBadge(p) {
        var qty = parseInt(p.quantity, 10);
        if (qty > 5) { return { cls: 'in', text: 'En stock' }; }
        if (qty > 0) { return { cls: 'low', text: qty + ' uds' }; }
        return { cls: 'out', text: 'Sin stock' };
    }

    function emptyState(icon, title, sub) {
        return '<div class="ps-empty"><i class="fas ' + icon + '"></i>' +
            '<span>' + esc(title) + '</span>' +
            (sub ? '<small>' + esc(sub) + '</small>' : '') +
            '</div>';
    }

    // ── Composer: insertar sin enviar (portado tal cual, DOM puro) ────────────

    var Composer = {
        $input: function () { return $('.bv-composer-input').first(); },
        insert: function (text) {
            var $i = Composer.$input();
            if (!$i.length) { return false; }
            var current = ($i.is('textarea, input') ? $i.val() : $i.text()) || '';
            var next = current.trim() ? current.replace(/\s+$/, '') + '\n\n' + text : text;
            if ($i.is('textarea, input')) {
                $i.val(next).trigger('input').trigger('change');
                var el = $i.get(0);
                el.focus();
                if (el.setSelectionRange) { el.setSelectionRange(next.length, next.length); }
            } else {
                $i.text(next).trigger('input');
                $i.get(0).focus();
            }
            return true;
        },
        product: function (p) {
            return [p.name || 'Producto', p.price ? '— ' + money(p.price) : '', '\n' + (p.url || '')]
                .filter(Boolean).join(' ').trim();
        }
    };

    // ── Lista de deseos ─────────────────────────────────────────────────────

    var Wish = {
        items: [],
        selected: null,

        load: function () {
            var $list = $('#psWishList');
            if (!$list.length) { return; }
            Wish.items = [];
            Wish.select(null);
            $list.html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');

            var base = window.HDCommerce.base();
            if (!base) {
                $list.html(emptyState('fa-triangle-exclamation', 'Selecciona una conversación con cliente'));
                return;
            }

            window.HDCommerce.ajax({ url: base + '/ps/wishlist', method: 'GET' }).done(function (r) {
                Wish.items = (r && r.items) || [];
                if (!Wish.items.length) {
                    $list.html(emptyState('fa-heart', 'Lista de deseos vacía', 'Este cliente no tiene productos guardados'));
                    return;
                }
                Wish.paint(Wish.items);
            }).fail(function () {
                $list.html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i>' +
                    '<div class="title">No se ha podido consultar PrestaShop</div></div>');
            });
        },

        paint: function (list) {
            var $list = $('#psWishList');
            if (!list.length) {
                $list.html(emptyState('fa-magnifying-glass', 'Sin resultados', 'Prueba con otro nombre o referencia'));
                return;
            }
            var count = '<div class="ps-sec-label"><span>' + list.length + (list.length === 1 ? ' producto' : ' productos') + '</span><span class="ln"></span></div>';
            $list.html(count + list.map(function (p) {
                var idx = Wish.items.indexOf(p);
                var sk = stockBadge(p);
                var thumb = p.image ? safeImg(p.image, p.name) : '<i class="fas fa-image"></i>';
                var on = p === Wish.selected ? ' on' : '';
                var price = parseFloat(p.price) > 0 ? '<span class="ps-prc-price">' + money(p.price) + '</span>' : '';
                return '<button type="button" class="ps-prc-item' + on + '" data-idx="' + idx + '">' +
                    '<span class="ps-prc-thumb">' + thumb + '</span>' +
                    '<span class="nm">' + esc(p.name || 'Producto') + '<small>' + esc(p.reference || '') + '</small></span>' +
                    '<span class="ps-prc-meta">' + price + '<span class="ps-prc-stock ' + sk.cls + '">' + sk.text + '</span></span>' +
                '</button>';
            }).join(''));
        },

        select: function (idx) {
            Wish.selected = Wish.items[idx] != null ? Wish.items[idx] : null;
            $('#psWishList .ps-prc-item').removeClass('on').filter('[data-idx="' + idx + '"]').addClass('on');
            $('#psWishInsert').prop('disabled', !Wish.selected).toggleClass('is-disabled', !Wish.selected);
            if (!Wish.selected) { $('#psWishPreview').addClass('bv-hidden'); return; }
            var url = (Wish.selected.url || '').trim();
            $('#psWishPreview').toggleClass('bv-hidden', !url);
            $('#psWishUrl').text(url);
        },

        insert: function () {
            if (!Wish.selected) { return; }
            if (Composer.insert(Composer.product(Wish.selected))) {
                toastr.success('Preparado en el chat, revísalo antes de enviar', '', { timeOut: 2000 });
                window.HDCommerce.close('ps-wishlist-send');
            }
        }
    };

    // ── Listeners ────────────────────────────────────────────────────────────

    $(document).on('click', '#psWishList .ps-prc-item', function () {
        Wish.select(parseInt($(this).attr('data-idx'), 10));
    });

    $(document).on('dblclick', '#psWishList .ps-prc-item', function () {
        Wish.select(parseInt($(this).attr('data-idx'), 10));
        Wish.insert();
    });

    $(document).on('click', '#psWishInsert', function () { Wish.insert(); });

    $(document).on('input', '#psWishSearch', function () {
        var q = String($(this).val() || '').trim().toLowerCase();
        Wish.paint(!q ? Wish.items : Wish.items.filter(function (p) {
            return String(p.name || '').toLowerCase().indexOf(q) > -1 ||
                String(p.reference || '').toLowerCase().indexOf(q) > -1;
        }));
    });

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'ps-wishlist-send') { return; }
        $('#psWishSearch').val('');
        Wish.load();
    });

    // Se expone para pruebas manuales en consola.
    window.PscWish = Wish;
})();
