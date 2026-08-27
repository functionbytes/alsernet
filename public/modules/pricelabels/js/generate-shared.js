(function ($, window) {
    /**
     * options.compact omite la linea "Se detectaron N" (el contador va fuera)
     * y anade al pie cuantas filas de la muestra se estan viendo.
     */
    function renderExcelPreview($container, data, fieldLabels, options) {
        fieldLabels = fieldLabels || {};
        options = options || {};

        var sample = data.sample || [];
        var html = options.compact ? '' : '<span class="text-success">Se detectaron ' + data.rows_count + ' etiqueta(s).</span>';

        if (sample.length) {
            var keys = Object.keys(sample[0]);
            html += '<div class="table-responsive"><table class="table table-sm mb-0' + (options.compact ? '' : ' table-bordered mt-2') + '">';
            html += '<thead><tr>' + keys.map(function (key) {
                return '<th class="p-1 small">' + (fieldLabels[key] || key) + '</th>';
            }).join('') + '</tr></thead>';
            html += '<tbody>';
            sample.forEach(function (row) {
                html += '<tr>' + keys.map(function (key) {
                    return '<td class="p-1 small text-muted">' + row[key] + '</td>';
                }).join('') + '</tr>';
            });
            html += '</tbody></table></div>';

            if (options.compact && data.rows_count > sample.length) {
                html += '<p class="small text-muted mt-2 mb-0">Mostrando las primeras ' + sample.length + ' filas de ' + data.rows_count + '.</p>';
            }
        }

        $container.removeClass('d-none').html(html);
    }

    function pollGenerationStatus(options) {
        var attempt = options.attempt || 0;

        if (attempt > 60) {
            if (options.$status) {
                options.$status.html('<span class="text-danger">La generacion esta tardando demasiado. Revisa el historial en unos minutos.</span>');
            }
            if (options.onTimeout) {
                options.onTimeout();
            }
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
                if (options.$status) {
                    options.$status.html('<span class="text-success">PDF generado correctamente. </span><a href="' + res.download_url + '" target="_blank" class="fw-semibold">Descargar PDF</a>');
                }
                toastr.success('PDF generado correctamente.');

                if (options.onSuccess) {
                    options.onSuccess(res);
                } else {
                    window.open(res.download_url, '_blank');
                }
            } else {
                if (options.$status) {
                    options.$status.html('<span class="text-danger">' + (res.error_message || 'Ocurrio un error al generar el PDF.') + '</span>');
                }
                toastr.error(res.error_message || 'Ocurrio un error al generar el PDF.');

                if (options.onError) {
                    options.onError(res);
                }
            }
        }).fail(function () {
            if (options.$status) {
                options.$status.html('<span class="text-danger">No se pudo consultar el estado de la generacion.</span>');
            }
            if (options.onFail) {
                options.onFail();
            }
            options.onDone();
        });
    }

    window.PriceLabelsShared = {
        renderExcelPreview: renderExcelPreview,
        pollGenerationStatus: pollGenerationStatus,
    };
})(jQuery, window);
