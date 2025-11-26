$(document).ready(function () {

    $('#order-status-filter').on('change', function () {
        const targetId = $(this).val();
        console.log(targetId);
        $('.nav-link').removeClass('active');
        $('button[data-bs-target="#' + targetId + '"]').addClass('active');
        $('.tab-pane').removeClass('show active');
        $('#' + targetId).addClass('show active');
    });


    $('.select-filter-box').find('select.select2').select2({
        width: '100%',
        placeholder: 'Selecciona una opción'
    });

    $(".orders-table .dropdown-toggle").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();

        const $currentDropdown = $(this).closest('.dropdown');
        const isOpen = $currentDropdown.hasClass('open');

        $(".orders-table .dropdown").removeClass("open");
        $(".orders-table .dropdown-menu").removeClass("show");
        $(".orders-table .dropdown-toggle").attr("aria-expanded", "false");

        if (!isOpen) {
            $currentDropdown.addClass("open");
            $currentDropdown.find(".dropdown-menu").addClass("show");
            $(this).attr("aria-expanded", "true");
        }
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest(".orders-table .dropdown").length) {
            $(".orders-table .dropdown").removeClass("open");
            $(".orders-table .dropdown-menu").removeClass("show");
            $(".orders-table .dropdown-toggle").attr("aria-expanded", "false");
        }
    });

    $("#search-order").on("keyup", function () {
        let value = $(this).val().toLowerCase();
        $(".download-table tbody tr").filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    $("#search-button").on("click", function () {
        let value = $("#search-order").val().toLowerCase();
        $(".download-table tbody tr").filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    $('#pills-tab button').on('click', function (e) {
        e.preventDefault();

        $('.nav-pills .nav-link').removeClass('active');

        $(this).addClass('active');

        $('.tab-pane').removeClass('show active');

        let target = $(this).attr("data-bs-target");
        if (!target) {
            target = $(this).attr("data-target");
        }
        $(target).addClass('show active');
    });

    $('.nav-pills .nav-link:first').trigger('click');

    $('.order-detail').find('select.select2').select2({
        width: '100%',
        placeholder: 'Selecciona una opción'
    });

    function setMobileTable() {
        var maxWidth = 815;
        var isMobile = $(window).width() <= maxWidth;

        $('.order-table-section .table-responsive table').each(function () {
            var $table = $(this);
            var tdLabels = [];

            $table.find('thead th').each(function () {
                tdLabels.push($(this).text().trim());
            });

            $table.find('tbody tr').each(function () {
                $(this).children('td').each(function (index) {
                    var $td = $(this);
                    var label = tdLabels[index] || '';
                    var content = $td.text().trim();

                    if (isMobile) {
                        if (!$td.find('p').length) {
                            $td.html('<p><span class="mobile-label" style="font-weight: bold;">' + label + ':</span> ' + content + '</p>');
                        }
                    } else {
                        if ($td.find('p').length) {
                            $td.html(content);
                        }
                    }
                });
            });
        });
    }

    function setMobileTableOrder() {
        const maxWidth = 815;
        const isMobile = $(window).width() <= maxWidth;

        $('.order-products-section .table-responsive table').each(function () {
            const $table = $(this);
            const tdLabels = [];

            $table.find('thead th').each(function () {
                tdLabels.push($(this).text().trim());
            });

            $table.find('tbody tr').each(function () {
                const $tr = $(this);

                $tr.children('td').each(function (index) {
                    const $td = $(this);
                    const label = tdLabels[index] || '';
                    const rawText = $td.text().replace(/\u00a0/g, '').trim();

                    if (rawText === '') {
                        $td.remove();
                        return;
                    }

                    if (!$td.data('original-html')) {
                        $td.data('original-html', $td.html());
                    }

                    if (isMobile) {
                        if (!$td.find('span.mobile-label').length) {
                            const newHtml = '<p><span class="mobile-label" style="font-weight: bold;">' +
                                label + ':</span> ' + rawText + '</p>';
                            $td.html(newHtml);
                        }
                    } else {
                        if ($td.data('original-html')) {
                            $td.html($td.data('original-html'));
                        }
                    }
                });

                if ($tr.children('td').length === 0) {
                    $tr.remove();
                }
            });
        });
    }

    $(document).ready(function () {
        setMobileTableOrder();
        setMobileTable();
    });

    $(window).on('resize', function () {
        setMobileTable();
        setMobileTableOrder();
    });


});
