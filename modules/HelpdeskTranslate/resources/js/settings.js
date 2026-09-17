/**
 * Configuración de HelpdeskTranslate (settings/index.blade.php). Extraido
 * del <script> inline de esa vista: mostrar/ocultar API key, alternar
 * sección de proveedor, limpiar caché, probar conexión y el informe de
 * consumo (tabla + gráfico Chart.js).
 *
 * Depende de window.HelpdeskTranslateSettings = { i18n, routes }, sembrado
 * por un bootstrap inline mínimo en la propia vista (cadenas traducidas +
 * URLs de route() — lo único que este fichero no puede resolver por su
 * cuenta), siguiendo el mismo patrón que window.HelpdeskTranslateI18n en
 * partials/translate-panel.blade.php.
 */
(function () {
    'use strict';

    $(function () {
        var cfg = window.HelpdeskTranslateSettings || { i18n: {}, routes: {} };
        var i18n = cfg.i18n;
        var routes = cfg.routes;

        // Toggle visibility of API key
        $('#ht-toggle-key').on('click', function () {
            var input = $('#deepl_key');
            var isPassword = input.attr('type') === 'password';
            input.attr('type', isPassword ? 'text' : 'password');
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });

        // Show only the section matching the selected provider
        $('#provider').on('change', function () {
            var p = $(this).val();
            $('#ht-deepl-section').toggleClass('d-none', p !== 'deepl');
            $('#ht-libre-section').toggleClass('d-none', p !== 'libretranslate');
        });

        // Clear cache button
        $('#ht-clear-cache').on('click', function () {
            if (!confirm(i18n.confirmClear)) {
                return;
            }
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + i18n.clearing);
            $.ajax({
                url: routes.cacheClear,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (resp) {
                    if (window.toastr) {
                        toastr.success(resp.message || i18n.cacheCleared);
                    }
                    setTimeout(function () { window.location.reload(); }, 800);
                },
                error: function () {
                    if (window.toastr) {
                        toastr.error(i18n.cacheClearFailed);
                    }
                    $btn.prop('disabled', false).html('<i class="fas fa-broom me-1"></i> ' + i18n.btnClearCache);
                }
            });
        });

        // Test DeepL connection
        $('#ht-test-connection').on('click', function () {
            var $btn = $(this);
            var $result = $('#ht-test-result');

            $btn.prop('disabled', true);
            $result.removeClass('text-success text-danger').addClass('text-muted')
                .html('<i class="fas fa-spinner fa-spin"></i> ' + i18n.testing);

            $.ajax({
                url: routes.test,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (resp) {
                    var msg = resp.message || i18n.testOkDefault;
                    if (resp.usage && resp.usage.character_count !== null) {
                        msg += ' — ' + i18n.testUsage
                            .replace(':count', resp.usage.character_count.toLocaleString())
                            .replace(':limit', (resp.usage.character_limit || '∞').toLocaleString());
                    }
                    $result.removeClass('text-muted text-danger').addClass('text-success')
                        .html('<i class="fas fa-check-circle"></i> ' + msg);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || i18n.testErrorDefault;
                    $result.removeClass('text-muted text-success').addClass('text-danger')
                        .html('<i class="fas fa-circle-xmark"></i> ' + msg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Usage / consumption report
        var htFeatureLabels = {
            manual: i18n.usageFeatureManual,
            auto_incoming: i18n.usageFeatureAutoIncoming,
            auto_outgoing: i18n.usageFeatureAutoOutgoing,
            other: i18n.usageFeatureOther
        };
        var htOperationLabels = {
            translate: i18n.usageOperationTranslate,
            detect: i18n.usageOperationDetect
        };

        function htFillUsageTable(selector, rows, key, labels) {
            var $tbody = $(selector).empty();
            (rows || []).forEach(function (row) {
                var label = (labels && labels[row[key]]) || row[key];
                $tbody.append(
                    $('<tr>').append(
                        $('<td>').text(label),
                        $('<td class="text-end">').text(Number(row.characters).toLocaleString()),
                        $('<td class="text-end">').text(Number(row.calls).toLocaleString())
                    )
                );
            });
        }

        var htChart = null;

        function htRenderUsage(data) {
            $('#ht-usage-characters').text(Number(data.totals.characters).toLocaleString());
            $('#ht-usage-calls').text(Number(data.totals.calls).toLocaleString());
            $('#ht-usage-failed').text(Number(data.totals.failed_calls).toLocaleString());
            $('#ht-usage-cost').text('€' + Number(data.totals.estimated_cost_eur).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 }));

            if (data.quota && data.quota.character_count !== null && data.quota.character_count !== undefined) {
                var limit = data.quota.character_limit ? Number(data.quota.character_limit).toLocaleString() : '∞';
                $('#ht-usage-quota').text(Number(data.quota.character_count).toLocaleString() + ' / ' + limit);
            } else {
                $('#ht-usage-quota').text(i18n.usageQuotaUnavailable);
            }

            htFillUsageTable('#ht-usage-by-feature', data.by_feature, 'feature', htFeatureLabels);
            htFillUsageTable('#ht-usage-by-operation', data.by_operation, 'operation', htOperationLabels);
            htFillUsageTable('#ht-usage-by-provider', data.by_provider, 'provider', {});

            var daily = data.daily || [];
            if (htChart) {
                htChart.destroy();
                htChart = null;
            }
            // Barras y no linea: es un total cerrado por dia, no una magnitud
            // continua. La linea con tension dibujaba curvas entre dias sueltos y
            // sugeria consumos intermedios que nunca existieron.
            htChart = new Chart(document.getElementById('ht-usage-chart'), {
                type: 'bar',
                data: {
                    labels: daily.map(function (d) { return d.date; }),
                    datasets: [
                        {
                            label: i18n.usageStatCharacters,
                            data: daily.map(function (d) { return d.characters; }),
                            backgroundColor: '#90bb13',
                            borderRadius: 2,
                            maxBarThickness: 28,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    var d = daily[ctx.dataIndex] || {};
                                    return Number(ctx.parsed.y).toLocaleString() + ' caracteres · ' +
                                           Number(d.calls || 0).toLocaleString() + ' llamadas';
                                },
                            },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 16 } },
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                    },
                },
            });

            var isEmpty = data.totals.calls === 0;
            $('#ht-usage-content').toggleClass('d-none', isEmpty);
            $('#ht-usage-empty').toggleClass('d-none', !isEmpty);
        }

        function htCurrentRange() {
            return { from: $('#ht-usage-from').val(), to: $('#ht-usage-to').val() };
        }

        function htLoadUsage() {
            $('#ht-usage-loading').removeClass('d-none');
            $('#ht-usage-content, #ht-usage-empty').addClass('d-none');

            $.ajax({
                url: routes.usage,
                method: 'GET',
                data: htCurrentRange(),
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (resp) {
                    htRenderUsage(resp);
                },
                error: function () {
                    if (window.toastr) {
                        toastr.error(i18n.usageJsError);
                    }
                },
                complete: function () {
                    $('#ht-usage-loading').addClass('d-none');
                }
            });
        }

        function htUpdateExportLink() {
            $('#ht-usage-export').attr('href', routes.usageExport + '?' + $.param(htCurrentRange()));
        }

        $('#ht-usage-filter').on('click', function () {
            htUpdateExportLink();
            htLoadUsage();
        });

        (function () {
            var today = new Date();
            var from = new Date();
            from.setDate(today.getDate() - 29);
            $('#ht-usage-to').val(today.toISOString().slice(0, 10));
            $('#ht-usage-from').val(from.toISOString().slice(0, 10));
            htUpdateExportLink();
            htLoadUsage();
        })();
    });
})();
