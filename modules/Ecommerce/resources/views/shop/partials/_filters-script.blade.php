<script>
$(function () {
    var $form = $('#filtersForm');
    if (!$form.length) { return; }

    var $container = $('#productsContainer');
    var filterTimeout;

    function applyFilters() {
        var formData = $form.serialize();
        var baseUrl = $form.data('action');
        var separator = baseUrl.indexOf('?') !== -1 ? '&' : '?';
        var url = baseUrl + separator + formData;

        $container.css({ opacity: 0.5, 'pointer-events': 'none' });
        history.pushState(null, '', url);

        $.get(url, { ajax: 1 })
            .done(function (html) {
                var $newContent = $(html).find('#productsContainer');
                if ($newContent.length) {
                    $container.html($newContent.html());
                } else {
                    $container.html(html);
                }
                $container.css({ opacity: 1, 'pointer-events': 'auto' });
                $('html, body').animate({ scrollTop: $container.offset().top - 100 }, 300);
            })
            .fail(function () {
                window.location.href = url;
            });
    }

    $form.on('change', '.js-filter', function () {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(applyFilters, 350);
    });

    $form.on('input', 'input[type="number"].js-filter', function () {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(applyFilters, 600);
    });

    // Sync price range slider with max price input
    $('#priceRangeSlider').on('input', function () {
        $('#maxPriceInput').val($(this).val());
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(applyFilters, 400);
    });

    $('#maxPriceInput').on('input', function () {
        var val = $(this).val();
        var max = parseInt($('#priceRangeSlider').attr('max'), 10);
        if (val >= 0 && val <= max) {
            $('#priceRangeSlider').val(val);
        }
    });

    $('.js-clear-filters').on('click', function () {
        $form[0].reset();
        $('#priceRangeSlider').val($('#priceRangeSlider').attr('max'));
        applyFilters();
    });

    // Mobile drawer
    $('#openMobileFilters').on('click', function () {
        $('#productFilters').addClass('open');
        $('#filterOverlay').addClass('open');
        $('body').addClass('filters-open');
    });

    $('#closeMobileFilters, #filterOverlay').on('click', function () {
        $('#productFilters').removeClass('open');
        $('#filterOverlay').removeClass('open');
        $('body').removeClass('filters-open');
    });
});
</script>
