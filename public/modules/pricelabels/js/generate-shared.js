(function ($, window) {
    function renderExcelPreview($container, data, fieldLabels) {
        fieldLabels = fieldLabels || {};

        var html = '<span class="text-success">Se detectaron ' + data.rows_count + ' etiqueta(s).</span>';

        if (data.sample && data.sample.length) {
            var keys = Object.keys(data.sample[0]);
            html += '<table class="table table-sm table-bordered mt-2 mb-0">';
            html += '<thead><tr>' + keys.map(function (key) {
                return '<th class="p-1 small">' + (fieldLabels[key] || key) + '</th>';
            }).join('') + '</tr></thead>';
            html += '<tbody>';
            data.sample.forEach(function (row) {
                html += '<tr>' + keys.map(function (key) {
                    return '<td class="p-1 small text-muted">' + row[key] + '</td>';
                }).join('') + '</tr>';
            });
            html += '</tbody></table>';
        }

        $container.removeClass('d-none').html(html);
    }

    function pollGenerationStatus(options) {
        var attempt = options.attempt || 0;

        if (attempt > 60) {
            options.$status.html('<span class="text-danger">La generacion esta tardando demasiado. Revisa el historial en unos minutos.</span>');
            options.onDone();

            return;
        }

        $.get(options.statusUrl, function (res) {
            if (res.status === 'pending') {
                setTimeout(function () {
                    pollGenerationStatus($.extend({}, options, { attempt: attempt + 1 }));
                }, 1500);

                return;
            }

            options.onDone();

            if (res.status === 'completed') {
                options.$status.html('<span class="text-success">PDF generado correctamente. </span><a href="' + res.download_url + '" target="_blank" class="fw-semibold">Descargar PDF</a>');
                toastr.success('PDF generado correctamente.');
                window.open(res.download_url, '_blank');
            } else {
                options.$status.html('<span class="text-danger">' + (res.error_message || 'Ocurrio un error al generar el PDF.') + '</span>');
                toastr.error(res.error_message || 'Ocurrio un error al generar el PDF.');
            }
        }).fail(function () {
            options.$status.html('<span class="text-danger">No se pudo consultar el estado de la generacion.</span>');
            options.onDone();
        });
    }

    window.PriceLabelsShared = {
        renderExcelPreview: renderExcelPreview,
        pollGenerationStatus: pollGenerationStatus,
    };
})(jQuery, window);
