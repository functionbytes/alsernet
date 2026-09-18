(function ($) {
    var bulk = null;

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

    // Data attached via jQuery .data() rather than string-concatenated HTML
    // attributes, so a gift message containing quotes can't break the markup.
    function emptyCell() {
        return '<span class="text-muted">&mdash;</span>';
    }

    function typeLabel(type) {
        return type === 'card' ? 'tarjeta' : 'sobre';
    }

    // "Ver sobre"/"Ver tarjeta": enlaces a PDFs ya generados antes para este
    // mismo pedido (ver GiftMessageOrderService::attachExistingGenerations),
    // para no obligar a regenerar algo que ya existe en el historico.
    function renderExistingGenerations(generations) {
        var config = window.GIFTMESSAGE_ORDERS;

        if (!generations || !generations.length) {
            return $(emptyCell());
        }

        var $wrap = $('<div>', { 'class': 'd-flex flex-column gap-1' });

        generations.forEach(function (generation) {
            $('<a>', {
                href: config.urls.historyView.replace('__ID__', generation.id),
                target: '_blank',
                rel: 'noopener',
                'class': 'badge bg-secondary-subtle text-secondary text-decoration-none',
                title: 'Generado el ' + new Date(generation.created_at).toLocaleString(),
                html: '<i class="fas fa-file-pdf me-1"></i>Ver ' + typeLabel(generation.type),
            }).appendTo($wrap);
        });

        return $wrap;
    }

    // Bajo el mensaje se adelanta a que tamano va a salir impreso: mejor saber
    // que una tarjeta va a salir al minimo ANTES de mandar el lote entero.
    function renderMessageCell(order) {
        var $cell = $('<div>');

        $('<div>').text(order.gift_message || '').appendTo($cell);

        var preview = order.print_preview;

        if (!preview) {
            return $cell;
        }

        var noCabe = !preview.envelope.fits || !preview.card.fits;
        var alMinimo = preview.envelope.font_size <= preview.min_font_size
            || preview.card.font_size <= preview.min_font_size;
        var texto = 'Se imprimira a ' + preview.envelope.font_size + ' pt (sobre) y '
            + preview.card.font_size + ' pt (tarjeta)';

        if (noCabe) {
            texto = 'Mensaje demasiado largo: no cabe ni al minimo y se recortara al imprimir';
        } else if (preview.too_long) {
            texto += ' — mensaje largo (' + preview.length + ' caracteres)';
        }

        $('<small>', {
            'class': 'd-block mt-1 gm-print-note' + (noCabe || alMinimo ? ' gm-print-note-alert' : ''),
            text: texto,
        }).appendTo($cell);

        return $cell;
    }

    function renderOrderRow(order) {
        var name = ((order.firstname || '') + ' ' + (order.lastname || '')).trim();
        // Tener PDF ya no bloquea la fila: se puede volver a seleccionar para
        // regenerarlo (el anterior del mismo tipo se sustituye). El dato viaja
        // en el checkbox para poder avisar en el modal.
        var hasPdf = !!(order.existing_generations && order.existing_generations.length);

        var $row = $('<tr>');
        var $checkboxCell = $('<td>').appendTo($row);

        $('<input>', {
            type: 'checkbox',
            'class': 'form-check-input order-checkbox',
            value: order.id_order,
            title: hasPdf ? 'Ya tiene PDF: al generar se reemplaza' : '',
        }).data({
            idOrder: order.id_order,
            giftMessage: order.gift_message || '',
            firstname: order.firstname || '',
            lastname: order.lastname || '',
            idGestion: order.id_gestion || '',
            npedidocli: order.npedidocli || '',
            hasPdf: hasPdf,
        }).appendTo($checkboxCell);

        $('<td>').text(order.id_order).appendTo($row);
        $('<td>').html(order.npedidocli ? escapeHtml(order.npedidocli) : emptyCell()).appendTo($row);
        $('<td>').html(order.id_gestion ? escapeHtml(order.id_gestion) : emptyCell()).appendTo($row);
        $('<td>').text(name).appendTo($row);
        $('<td>').append(renderMessageCell(order)).appendTo($row);
        $('<td>').append(renderExistingGenerations(order.existing_generations)).appendTo($row);

        return $row;
    }

    // Alterna entre el estado "todavia no hay pedidos" y la tabla de
    // resultados (Paso 2), segun si hay filas o no.
    function toggleResultsCard(hasRows) {
        $('#orders-results-card').toggleClass('d-none', !hasRows);
        $('#orders-empty-state').toggleClass('d-none', hasRows);
    }

    function renderOrders(rows) {
        var $tbody = $('#orders-table tbody').empty();

        rows.forEach(function (order) {
            $tbody.append(renderOrderRow(order));
        });

        $('#orders-count').text(rows.length);
        $('#select-all').prop('checked', false);
        toggleResultsCard(rows.length > 0);

        if (bulk) {
            bulk.reset();
        }
    }

    function selectedRows() {
        return $('.order-checkbox:checked').map(function () {
            var $checkbox = $(this);

            return {
                id_order: $checkbox.data('idOrder'),
                gift_message: $checkbox.data('giftMessage'),
                firstname: $checkbox.data('firstname'),
                lastname: $checkbox.data('lastname'),
                id_gestion: $checkbox.data('idGestion'),
                npedidocli: $checkbox.data('npedidocli'),
            };
        }).get();
    }

    function searchOrders() {
        var config = window.GIFTMESSAGE_ORDERS;
        var ids = $('#gestion-search').val().trim();

        if (!ids) {
            toastr.warning('Indica al menos un numero de gestion o de pedido.');

            return;
        }

        $.ajax({
            url: config.urls.ordersSearch,
            method: 'POST',
            data: { ids: ids },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                var rows = response.rows || [];
                renderOrders(rows);

                if (!rows.length) {
                    toastr.warning('Ningun pedido coincide con esos numeros.');

                    return;
                }

                // Un mismo pedido puede llegar por su numero de gestion y por su
                // id de PrestaShop a la vez, asi que el total encontrado puede ser
                // menor que la cantidad de numeros buscados.
                toastr.success(rows.length === 1 ? '1 pedido encontrado.' : rows.length + ' pedidos encontrados.');
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al buscar los pedidos.');
            },
        });
    }

    // La pantalla arranca sin listado (el controlador ya no precarga pedidos),
    // asi que limpiar es volver al estado vacio inicial.
    function resetOrders() {
        $('#orders-table tbody').empty();
        $('#gestion-search').val('');
        $('#orders-count').text(0);
        $('#select-all').prop('checked', false);
        toggleResultsCard(false);

        if (bulk) {
            bulk.reset();
        }

        toastr.success('Busqueda limpiada.');
    }

    // Una petición por tipo: sobre y tarjeta comparten el mismo endpoint,
    // que solo genera un tipo a la vez.
    function requestPdf(type, rows) {
        var config = window.GIFTMESSAGE_ORDERS;

        return $.ajax({
            url: config.urls.generate,
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({ type: type, rows: rows }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        });
    }

    // Tras generar, la columna PDF de la tabla apunta a generaciones que quiza
    // acaban de ser reemplazadas. Se re-consulta la busqueda en curso para dejar
    // los enlaces al dia, sin recargar la pagina ni cerrar el modal de
    // resultados (por eso no se toca la seleccion ni el foco).
    function refreshRowsAfterGenerate() {
        var config = window.GIFTMESSAGE_ORDERS;
        var ids = $('#gestion-search').val().trim();

        if (!ids) {
            return;
        }

        $.ajax({
            url: config.urls.ordersSearch,
            method: 'POST',
            data: { ids: ids },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                renderOrders(response.rows || []);
            },
        });
    }

    // Los mensajes que no caben se recortan por CSS al imprimir, asi que si el
    // servidor avisa hay que decirlo: antes se perdia en silencio.
    function warnAboutTightMessages(results) {
        var avisos = [];

        results.forEach(function (result) {
            (result.warnings || []).forEach(function (warning) { avisos.push(warning); });
        });

        if (!avisos.length) {
            return;
        }

        var recortados = avisos.filter(function (w) { return w.truncated; });
        var numeros = avisos.map(function (w) { return w.order_number; }).filter(function (v, i, a) {
            return v && a.indexOf(v) === i;
        });

        // Lo primero es lo que sale mal del todo: un caracter sin glifo se
        // imprime como cuadro vacio y no hay forma de saberlo mirando el PDF en
        // pantalla si no lo sabes buscar.
        var sinFuente = avisos.filter(function (w) { return w.unprintable; });

        if (sinFuente.length) {
            toastr.warning('Los pedidos ' + unprintableOrders(sinFuente).join(', ') + ' llevan caracteres que ninguna fuente instalada puede imprimir (' +
                unprintableChars(sinFuente).join(' ') + ') y saldran como cuadros vacios. Sube una fuente que los cubra en Configuracion.',
                'Caracteres sin fuente', { timeOut: 15000 });

            return;
        }

        if (recortados.length) {
            toastr.warning('Revisa los pedidos ' + numeros.join(', ') +
                ': el mensaje no cabe ni al tamano minimo y saldra recortado.', 'Mensajes demasiado largos', { timeOut: 12000 });

            return;
        }

        toastr.info('Los pedidos ' + numeros.join(', ') + ' se han impreso con la letra reducida para que cupiera el mensaje.',
            'Letra reducida', { timeOut: 8000 });
    }

    /**
     * Pedidos y caracteres afectados por los avisos de "sin fuente", sin
     * repetidos: con "Ambos" el mismo pedido llega dos veces (sobre y tarjeta).
     */
    function unprintableOrders(avisos) {
        return avisos.map(function (w) { return w.order_number; }).filter(function (v, i, a) {
            return v && a.indexOf(v) === i;
        });
    }

    function unprintableChars(avisos) {
        return avisos
            .map(function (w) { return (w.unprintable || '').split(''); })
            .reduce(function (all, chars) { return all.concat(chars); }, [])
            .filter(function (v, i, a) { return a.indexOf(v) === i; });
    }

    function renderUnprintableNotice(results) {
        var avisos = [];

        results.forEach(function (result) {
            (result.warnings || []).forEach(function (warning) {
                if (warning.unprintable) {
                    avisos.push(warning);
                }
            });
        });

        var $notice = $('#bulk-result-unprintable');

        if (!avisos.length) {
            $notice.addClass('d-none').empty();

            return;
        }

        $notice.removeClass('d-none').html(
            '<strong>Ojo antes de imprimir:</strong> los pedidos ' + escapeHtml(unprintableOrders(avisos).join(', ')) +
            ' llevan caracteres que ninguna fuente instalada puede imprimir (' + escapeHtml(unprintableChars(avisos).join(' ')) +
            '). En el PDF salen como cuadros vacios. Sube una fuente que los cubra en Configuracion &rsaquo; Fuentes.'
        );
    }

    function showFormStep() {
        $('#bulk-step-form, #bulk-step-form-footer').removeClass('d-none');
        $('#bulk-step-result, #bulk-step-result-footer').addClass('d-none');
        $('#bulk-modal-title').text('Generar PDF de mensaje regalo');
        $('#bulk-result-links').empty();
        $('#bulk-result-unprintable').addClass('d-none').empty();
    }

    // Segunda pantalla del modal: un boton por PDF generado. Se abren al
    // pulsarlos (gesto del usuario), no automaticamente, porque Chrome solo
    // deja abrir un popup por click y con "Ambos" el segundo quedaba
    // bloqueado. El listado de pedidos se deja tal cual, sin refrescar.
    function showResultStep(results) {
        var $links = $('#bulk-result-links').empty();

        results.forEach(function (result) {
            $('<a>', {
                href: result.url,
                target: '_blank',
                rel: 'noopener',
                'class': 'btn btn-primary w-100 mb-2',
                text: 'Abrir ' + typeLabel(result.type),
            }).appendTo($links);
        });

        $('#bulk-modal-title').text(results.length > 1 ? 'PDF generados' : 'PDF generado');
        $('#bulk-result-text').text(results.length > 1
            ? 'PDF generados correctamente. Abrelos desde los botones de abajo; cada uno se abre en una pestana nueva.'
            : 'PDF generado correctamente. Abrelo desde el boton de abajo; se abre en una pestana nueva.');
        renderUnprintableNotice(results);
        $('#bulk-step-form, #bulk-step-form-footer').addClass('d-none');
        $('#bulk-step-result, #bulk-step-result-footer').removeClass('d-none');
    }

    // Paso 3: genera el/los PDF via AJAX (Accept: json, ver
    // GiftMessageGenerationController::generate) y pasa el modal a la
    // pantalla con los enlaces.
    // Descarga encadenada: el navegador solo acepta varias descargas seguidas si
    // salen del mismo gesto y con un respiro entre ellas; con "Ambos" son dos.
    function downloadAll(results) {
        results.forEach(function (result, index) {
            setTimeout(function () {
                var link = document.createElement('a');

                link.href = result.downloadUrl;
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }, index * 700);
        });
    }

    function generatePdf(autoDownload, forcedType) {
        var rows = selectedRows();
        var type = forcedType || $('#bulk-type-select').val();

        if (!rows.length) {
            toastr.warning('Selecciona al menos un pedido.');

            return;
        }

        var types = type === 'both' ? ['envelope', 'card'] : [type];
        var $btn = forcedType
            ? $('#bulk-quick-download')
            : $(autoDownload ? '#bulk-download-btn' : '#bulk-apply-btn');

        $btn.prop('disabled', true);
        $('#bulk-apply-btn, #bulk-download-btn, #bulk-quick-download').prop('disabled', true);

        if (forcedType) {
            $btn.data('label', $btn.html()).text('Generando y descargando...');
        } else {
            $btn.text(autoDownload ? 'Generando y descargando...' : 'Generando...');
        }
        var results = [];

        var requests = types.map(function (t) {
            return requestPdf(t, rows).done(function (response) {
                results.push({
                    type: t,
                    url: response.view_url,
                    downloadUrl: response.download_url,
                    warnings: response.warnings || [],
                });
            });
        });

        $.when.apply($, requests)
            .done(function () {
                // Las peticiones son paralelas, asi que el orden de llegada no
                // tiene por que ser sobre-tarjeta: se reordena para que los
                // botones salgan siempre en el orden elegido.
                results.sort(function (a, b) {
                    return types.indexOf(a.type) - types.indexOf(b.type);
                });

                warnAboutTightMessages(results);

                if (autoDownload) {
                    downloadAll(results);
                    toastr.success(results.length > 1
                        ? 'PDF generados. La descarga empieza sola; si el navegador pide permiso para varias descargas, aceptalo.'
                        : 'PDF generado. La descarga empieza sola.');
                    $('#bulk-modal').modal('hide');
                    refreshRowsAfterGenerate();

                    return;
                }

                toastr.success(results.length > 1 ? 'PDF generados correctamente.' : 'PDF generado correctamente.');
                showResultStep(results);
                refreshRowsAfterGenerate();
            })
            .fail(function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al generar el PDF.');
            })
            .always(function () {
                $('#bulk-apply-btn, #bulk-download-btn, #bulk-quick-download').prop('disabled', false);
                $('#bulk-download-btn').text('Generar y descargar');

                if (forcedType && $btn.data('label')) {
                    $btn.html($btn.data('label'));
                }
            });
    }

    $(document).ready(function () {
        if (!window.GIFTMESSAGE_ORDERS) {
            return;
        }

        bulk = window.BulkActions.init({ checkbox: '.order-checkbox' });

        if ($.fn.select2) {
            $('#bulk-type-select').select2({
                dropdownParent: $('#bulk-modal'),
                width: '100%',
                minimumResultsForSearch: Infinity,
            });
        }

        $('#gestion-search-btn').on('click', searchOrders);
        $('#gestion-search').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                searchOrders();
            }
        });
        $('#gestion-reset-btn').on('click', resetOrders);

        // "Ambos" es el caso habitual, asi que el modal siempre se abre ahi y
        // en la primera pantalla, aunque la anterior apertura acabara en la
        // de resultados.
        $('#bulk-modal').on('show.bs.modal', function () {
            // El contador del modal se refresca aqui a mano: BulkActions solo
            // actualiza los [data-bulk-count] del toolbar y de un modal cuyo id
            // deriva del sufijo del toolbar (bulk-toolbar-X -> bulk-X-modal),
            // asi que con los ids "legacy" (#bulk-toolbar / #bulk-modal) el del
            // modal se quedaba siempre a 0.
            var total = selectedRows().length;
            var conPdf = $('.order-checkbox:checked').filter(function () {
                return $(this).data('hasPdf') === true;
            }).length;

            $('#bulk-modal [data-bulk-count]').text(total);
            $('#bulk-replace-warning')
                .toggleClass('d-none', conPdf === 0)
                .text(conPdf === 1
                    ? '1 de los pedidos seleccionados ya tiene PDF: el anterior se reemplazara por el nuevo.'
                    : conPdf + ' de los pedidos seleccionados ya tienen PDF: los anteriores se reemplazaran por los nuevos.');
            $('#bulk-type-select').val('both').trigger('change');
            $('#bulk-apply-btn').prop('disabled', false).text(conPdf === total && total > 0 ? 'Regenerar' : 'Generar');
            $('#bulk-download-btn').prop('disabled', false)
                .text(conPdf === total && total > 0 ? 'Regenerar y descargar' : 'Generar y descargar');
            showFormStep();
        });

        $('#bulk-apply-btn').on('click', function () { generatePdf(false); });
        $('#bulk-download-btn').on('click', function () { generatePdf(true); });
        // Desde la barra: sobre y tarjeta, generados y descargados de una.
        $('#bulk-quick-download').on('click', function () { generatePdf(true, 'both'); });
    });
})(jQuery);
