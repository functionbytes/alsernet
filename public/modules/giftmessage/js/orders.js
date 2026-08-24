(function ($) {
    var originalOrdersHtml = '';
    var bulk = null;

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

    // Data attached via jQuery .data() rather than string-concatenated HTML
    // attributes, so a gift message containing quotes can't break the markup.
    function emptyCell() {
        return '<span class="text-muted">&mdash;</span>';
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
                html: '<i class="fas fa-file-pdf me-1"></i>Ver ' + (generation.type === 'card' ? 'tarjeta' : 'sobre'),
            }).appendTo($wrap);
        });

        return $wrap;
    }

    function renderOrderRow(order) {
        var name = ((order.firstname || '') + ' ' + (order.lastname || '')).trim();
        // Ya no tiene sentido seleccionar el pedido para generar si ya tiene
        // algun PDF; "Ver sobre"/"Ver tarjeta" son la via para volver a
        // verlos. Ni siquiera se pinta el checkbox. Mismo criterio que la
        // fila server-side.
        var hasPdf = !!(order.existing_generations && order.existing_generations.length);

        var $row = $('<tr>');
        var $checkboxCell = $('<td>').appendTo($row);

        if (!hasPdf) {
            $('<input>', {
                type: 'checkbox',
                'class': 'form-check-input order-checkbox',
                value: order.id_order,
            }).data({
                idOrder: order.id_order,
                giftMessage: order.gift_message || '',
                firstname: order.firstname || '',
                lastname: order.lastname || '',
                idGestion: order.id_gestion || '',
                npedidocli: order.npedidocli || '',
            }).appendTo($checkboxCell);
        }

        $('<td>').text(order.id_order).appendTo($row);
        $('<td>').html(order.npedidocli ? escapeHtml(order.npedidocli) : emptyCell()).appendTo($row);
        $('<td>').html(order.id_gestion ? escapeHtml(order.id_gestion) : emptyCell()).appendTo($row);
        $('<td>').text(name).appendTo($row);
        $('<td>').text(order.gift_message).appendTo($row);
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

    function resetOrders() {
        $('#orders-table tbody').html(originalOrdersHtml);
        $('#gestion-search').val('');
        $('#orders-count').text($('#orders-table tbody tr').length);
        toggleResultsCard($('#orders-table tbody .order-checkbox').length > 0);

        if (bulk) {
            bulk.reset();
        }

        toastr.success('Listado restaurado.');
    }

    // Una petición por tipo (sobre y/o tarjeta comparten el mismo endpoint,
    // que solo genera un tipo a la vez). El popup correspondiente se le pasa
    // ya abierto porque se creo de forma sincrona al click, no aqui dentro,
    // para que el navegador no lo bloquee.
    function requestPdf(type, rows, pdfWindow) {
        var config = window.GIFTMESSAGE_ORDERS;

        return $.ajax({
            url: config.urls.generate,
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({ type: type, rows: rows }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (response) {
            if (pdfWindow) {
                pdfWindow.location = response.view_url;
            }
        }).fail(function (xhr) {
            if (pdfWindow) {
                pdfWindow.close();
            }
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al generar el PDF.');
        });
    }

    // Tras generar, refresca el listado para que la fila muestre "Ver
    // sobre"/"Ver tarjeta" con los PDF recien creados (fusionados con los
    // que ya hubiera antes) y pierda su checkbox, igual que cualquier otro
    // pedido con PDF. Si habia un termino de busqueda activo se re-consulta
    // solo la tabla; si se genero desde el listado "recientes" (sin buscar)
    // no hay endpoint AJAX para refrescarlo solo, asi que se recarga la
    // pagina entera.
    function refreshOrdersAfterGenerate() {
        if ($('#gestion-search').val().trim()) {
            searchOrders();
        } else {
            setTimeout(function () { location.reload(); }, 600);
        }
    }

    // Paso 3: genera el/los PDF via AJAX (Accept: json, ver
    // GiftMessageGenerationController::generate). Solo se abre UNA pestana
    // automatica (la del primer tipo): Chrome permite un unico popup por
    // gesto de click aunque los dos window.open() se llamen de forma
    // sincrona antes de cualquier peticion (comprobado: el segundo devuelve
    // null siempre) — no es evitable desde JS. Con "Ambos", el resto de PDF
    // quedan accesibles como enlace en la tabla tras refrescar el listado.
    function generatePdf() {
        var rows = selectedRows();
        var type = $('#bulk-type-select').val();

        if (!rows.length) {
            toastr.warning('Selecciona al menos un pedido.');

            return;
        }

        var types = type === 'both' ? ['envelope', 'card'] : [type];
        var $btn = $('#bulk-apply-btn').prop('disabled', true).text('Generando...');
        var firstWindow = window.open('', '_blank');

        var requests = types.map(function (t, i) {
            return requestPdf(t, rows, i === 0 ? firstWindow : null);
        });

        $.when.apply($, requests)
            .done(function () {
                toastr.success(
                    types.length > 1
                        ? 'PDF generados correctamente. El sobre se abrio en una pestana nueva; la tarjeta ya esta lista en la tabla.'
                        : 'PDF generado correctamente.'
                );
                $('#bulk-modal').modal('hide');
                refreshOrdersAfterGenerate();
            })
            .always(function () {
                $btn.prop('disabled', false).text('Generar');
            });
    }

    $(document).ready(function () {
        if (!window.GIFTMESSAGE_ORDERS) {
            return;
        }

        originalOrdersHtml = $('#orders-table tbody').html();

        bulk = window.BulkActions.init({ checkbox: '.order-checkbox' });

        $('#gestion-search-btn').on('click', searchOrders);
        $('#gestion-search').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                searchOrders();
            }
        });
        $('#gestion-reset-btn').on('click', resetOrders);

        $('#bulk-modal').on('hide.bs.modal', function () {
            $('#bulk-type-select').val('envelope');
            $('#bulk-apply-btn').prop('disabled', false).text('Generar');
        });

        $('#bulk-apply-btn').on('click', generatePdf);
    });
})(jQuery);
