/**
 * Consumo de WhatsApp — settings/whatsapp-usage/index.blade.php
 * Requiere: window.WhatsappUsageConfig = { dataUrl, exportUrl, pricingUrl }
 */
$(document).ready(function () {
    var cfg = window.WhatsappUsageConfig || {};

    var waCategoryLabels = {
        marketing: 'Marketing',
        utility: 'Utilidad',
        authentication: 'Autenticación',
        service: 'Servicio (gratis)',
        desconocida: 'Desconocida'
    };
    var waChart = null;

    function waRenderUsage(data) {
        $('#wa-usage-sent').text(Number(data.totals.sent).toLocaleString());
        $('#wa-usage-success').text(Number(data.totals.success_sent).toLocaleString());
        $('#wa-usage-failed').text(Number(data.totals.failed_sent).toLocaleString());
        $('#wa-usage-cost').text('€' + Number(data.totals.estimated_cost_eur).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 }));

        var $byCategory = $('#wa-usage-by-category').empty();
        (data.by_category || []).forEach(function (row) {
            var label = waCategoryLabels[row.category] || row.category;
            $byCategory.append(
                $('<tr>').append(
                    $('<td>').text(label),
                    $('<td class="text-end">').text(Number(row.sent).toLocaleString())
                )
            );
        });

        var $topTemplates = $('#wa-usage-top-templates').empty();
        (data.top_templates || []).forEach(function (row) {
            var label = waCategoryLabels[row.category] || row.category || '—';
            $topTemplates.append(
                $('<tr>').append(
                    $('<td>').text(row.template_name || '—'),
                    $('<td class="text-center">').text(label),
                    $('<td class="text-end">').text(Number(row.sent).toLocaleString())
                )
            );
        });

        var daily = data.daily || [];
        if (waChart) {
            waChart.destroy();
            waChart = null;
        }
        waChart = new Chart(document.getElementById('wa-usage-chart'), {
            type: 'line',
            data: {
                labels: daily.map(function (d) { return d.date; }),
                datasets: [
                    { label: 'Enviados', data: daily.map(function (d) { return d.sent; }), borderColor: '#90bb13', tension: 0.3 },
                    { label: 'Fallidos', data: daily.map(function (d) { return d.failed; }), borderColor: '#FA896B', tension: 0.3 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false },
        });

        var isEmpty = data.totals.sent === 0;
        $('#wa-usage-content').toggleClass('d-none', isEmpty);
        $('#wa-usage-empty').toggleClass('d-none', !isEmpty);
    }

    function waCurrentRange() {
        return { from: $('#wa-usage-from').val(), to: $('#wa-usage-to').val() };
    }

    function waLoadUsage() {
        $('#wa-usage-loading').removeClass('d-none');
        $('#wa-usage-content, #wa-usage-empty').addClass('d-none');

        $.ajax({
            url: cfg.dataUrl,
            method: 'GET',
            data: waCurrentRange(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (resp) {
                waRenderUsage(resp);
            },
            error: function () {
                if (window.toastr) {
                    toastr.error('No se pudo cargar el consumo de WhatsApp.');
                }
            },
            complete: function () {
                $('#wa-usage-loading').addClass('d-none');
            }
        });
    }

    function waUpdateExportLink() {
        var range = waCurrentRange();
        $('#wa-usage-export').attr('href', cfg.exportUrl + '?' + $.param(range));
    }

    $('#wa-usage-filter').on('click', function () {
        waUpdateExportLink();
        waLoadUsage();
    });

    $('#wa-pricing-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true);

        $.ajax({
            url: cfg.pricingUrl,
            method: 'POST',
            data: {
                marketing: $('#wa-price-marketing').val(),
                utility: $('#wa-price-utility').val(),
                authentication: $('#wa-price-authentication').val(),
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                if (window.toastr) {
                    toastr.success('Tarifas actualizadas.');
                }
                waLoadUsage();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudieron guardar las tarifas.';
                if (window.toastr) {
                    toastr.error(msg);
                }
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    (function () {
        var today = new Date();
        var from = new Date();
        from.setDate(today.getDate() - 29);
        $('#wa-usage-to').val(today.toISOString().slice(0, 10));
        $('#wa-usage-from').val(from.toISOString().slice(0, 10));
        waUpdateExportLink();
        waLoadUsage();
    })();
});
