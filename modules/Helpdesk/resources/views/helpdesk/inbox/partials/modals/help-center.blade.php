{{-- Modal: Centro de ayuda (#84 ve-help-center) --}}
<div class="bv-modal" data-bv-modal-name="help-center">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="far fa-circle-question"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.help_center_label') }}</span>
                <div class="bv-modal-title"><span>{{ __('helpdesk::helpdesk.inbox.modals.help_center_title') }}</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body">
            <div class="search-field">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="hcSearch" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.help_center_search_placeholder') }}" autocomplete="off">
            </div>

            <div class="bv-hc-grid" id="hcGrid">
                <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> {{ __('helpdesk::helpdesk.inbox.modals.help_center_loading') }}</div>
            </div>
        </div>

        <div class="bv-modal-foot">
            <a href="{{ route('public.helpcenter.index') }}" target="_blank" rel="noopener" class="btn-primary">
                {{ __('helpdesk::helpdesk.inbox.modals.help_center_open_kb') }}
            </a>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.help_center_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
            url: '{{ route('manager.helpcenter.api.categories') }}',
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
        var url = '{{ route('public.helpcenter.index') }}' + (id ? ('?category=' + encodeURIComponent(id)) : '');
        window.open(url, '_blank', 'noopener');
    });

    window.openHelpCenter = function () {
        HDCommerce.open('help-center');
        load();
    };
})();
</script>
@endpush
@endonce
