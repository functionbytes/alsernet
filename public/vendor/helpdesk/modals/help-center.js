/*!
 * Helpdesk · modal "help-center" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/help-center.blade.php.
 * La config que antes se interpolaba con Blade (rutas, flags, datos de sesion,
 * textos traducidos) viaja ahora por atributos data-* del markup, que es lo que
 * permite servir este JS como fichero estatico cacheable.
 */
(function () {
    var _categories = [];
    var _loaded = false;

    var ICONS = {
        rocket: 'fa-solid fa-rocket',
        comment: 'fa-regular fa-comment',
        ticket: 'fa-solid fa-ticket',
        megaphone: 'fa-solid fa-bullhorn',
        plug: 'fa-solid fa-plug',
        shield: 'fa-solid fa-shield-halved',
    };

    function iconClass(icon) {
        if (!icon) { return 'fas fa-folder-open'; }
        if (ICONS[icon]) { return ICONS[icon]; }
        if (/^(fa-solid|fa-regular|fa-brands|fas|far|fab)\s/.test(icon)) { return icon; }
        return 'fas fa-' + String(icon).replace(/^fa-/, '');
    }

    function articlesLabel(n) {
        n = n || 0;
        return n + ' artículo' + (n !== 1 ? 's' : '');
    }

    function render(list) {
        if (!list.length) {
            $('#hcGrid').html('<div class="bv-oc-empty"><i class="fas fa-magnifying-glass"></i>' +
                '<div class="title">Sin resultados</div></div>');
            return;
        }
        var html = list.map(function (c) {
            return '<button class="bv-hc-card" type="button" data-hc-id="' + c.id + '">' +
                '<i class="' + iconClass(c.icon) + '"></i>' +
                '<span class="t">' + HDCommerce.esc(c.name) + '</span>' +
                '<span class="s">' + HDCommerce.esc(articlesLabel(c.articles_count)) + '</span>' +
            '</button>';
        }).join('');
        $('#hcGrid').html(html);
    }

    function filter() {
        var q = $('#hcSearch').val().trim().toLowerCase();
        if (!q) { render(_categories); return; }
        render(_categories.filter(function (c) {
            return (c.name || '').toLowerCase().indexOf(q) !== -1;
        }));
    }

    function load() {
        if (_loaded) { filter(); return; }
        $('#hcGrid').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');
        HDCommerce.ajax({
            url: $('[data-bv-modal-name="help-center"]').data('url-categories'),
            method: 'GET',
        }).done(function (resp) {
            _categories = resp.categories || [];
            _loaded = true;
            filter();
        }).fail(function () {
            $('#hcGrid').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i>' +
                '<div class="title">No se pudieron cargar las categorías</div></div>');
        });
    }

    (new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            if (m.attributeName !== 'class') { return; }
            if (!$(m.target).hasClass('on')) { return; }
            load();
        });
    })).observe(document.querySelector('[data-bv-modal-name="help-center"]'), { attributes: true });

    $(document).on('input', '#hcSearch', filter);

    $(document).on('click', '.bv-hc-card', function () {
        var id = $(this).data('hc-id');
        var base = $('[data-bv-modal-name="help-center"]').data('url-public');
        var url = base + (id ? ('?category=' + encodeURIComponent(id)) : '');
        window.open(url, '_blank', 'noopener');
    });

    window.openHelpCenter = function () {
        HDCommerce.open('help-center');
        load();
    };
})();
