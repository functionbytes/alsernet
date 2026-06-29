<style>
.search-suggestions-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    z-index: 1050;
    max-height: 400px;
    overflow-y: auto;
    display: none;
}
.search-suggestion-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-bottom: 1px solid #f1f3f5;
    text-decoration: none;
    color: inherit;
    transition: background 0.15s;
}
.search-suggestion-item:last-child { border-bottom: none; }
.search-suggestion-item:hover { background: #f8f9fa; color: inherit; }
.search-suggestion-item img { width: 48px; height: 48px; object-fit: cover; border-radius: 4px; flex-shrink: 0; }
.search-suggestion-price { color: #90bb13; font-weight: 600; margin-left: auto; white-space: nowrap; }
.search-style-2 { position: relative; }
</style>

<script>
$(function () {
    var searchTimeout;
    var $form  = $('.search-style-2 form').first();
    var $input = $form.find('input[name="q"]');
    var $dropdown = null;

    if (!$input.length) { return; }

    $input.attr('autocomplete', 'off');

    $input.on('input', function () {
        clearTimeout(searchTimeout);
        var q = $(this).val().trim();

        if (!$dropdown) {
            $dropdown = $('<div class="search-suggestions-dropdown"></div>');
            $form.append($dropdown);
        }

        if (q.length < 2) {
            $dropdown.hide();
            return;
        }

        searchTimeout = setTimeout(function () {
            $.get('/api/v1/ecommerce/products/suggestions', { q: q })
                .done(function (data) {
                    if (!data.products || !data.products.length) {
                        $dropdown.html(
                            '<div class="p-3 text-muted text-center small">Sin resultados para &ldquo;' +
                            $('<span>').text(q).html() + '&rdquo;</div>'
                        ).show();
                        return;
                    }

                    var items = data.products.map(function (p) {
                        return '<a href="' + p.url + '" class="search-suggestion-item">' +
                            '<img src="' + p.image + '" alt="" loading="lazy">' +
                            '<div class="flex-grow-1 small fw-semibold text-truncate">' + $('<span>').text(p.name).html() + '</div>' +
                            '<span class="search-suggestion-price">$' + parseFloat(p.price).toFixed(2) + '</span>' +
                            '</a>';
                    });

                    $dropdown.html(items.join('')).show();
                });
        }, 250);
    });

    $input.on('focus', function () {
        if ($dropdown && $dropdown.children().length) { $dropdown.show(); }
    });

    $(document).on('click', function (e) {
        if ($dropdown && !$(e.target).closest('.search-style-2').length) {
            $dropdown.hide();
        }
    });
});
</script>
