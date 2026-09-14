/**
 * Modal "CLIENTE · BÚSQUEDA EXTERNA" (.bv-modal, mismo framework visual que
 * customer-integrations.blade.php de HelpdeskIntegration) — apertura/cierre
 * propios, sin depender de conversations.js (acoplado al estado del inbox).
 *
 * Tres vistas: buscar (ERP+PrestaShop a la vez) → ficha completa del
 * resultado elegido (info-table + pedidos, vía external-preview) → enviar
 * plantilla WhatsApp inline (reusa contacts.hsm-templates / contacts.send-hsm).
 *
 * data-mode="index" (listado, sin contacto aún) / "link" (ficha 360, vincula
 * al contacto ya abierto), seteado por el trigger antes de abrir el modal.
 */
(function () {
    'use strict';

    var ALL_PLATFORMS_VALUE = '';

    var platforms = [];
    var platformsLoaded = false;
    var lastResults = [];

    var currentResult = null;
    var lastPreviewPhone = null;

    var hsmCustomerId = null;
    var hsmTemplates = [];
    var hsmTemplatesLoaded = false;

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function findPlatform(key) {
        return platforms.find(function (p) { return p.platform === key; });
    }

    function platformIcon(key) {
        var p = findPlatform(key);
        return p ? p.icon : 'fas fa-plug';
    }

    function platformLabel(key) {
        var p = findPlatform(key);
        return p ? p.label : key;
    }

    // ─── apertura / cierre .bv-modal (propio, sin conversations.js) ──────

    function showView(name) {
        ['extSearch', 'extPreview', 'extHsm'].forEach(function (v) {
            var on = v === name;
            // .toggleClass('d-none') en vez de jQuery .toggle(): estas vistas
            // usan display:flex vía la clase .ext-view-stack (no inline) para
            // que el gap entre sus campos internos sea confiable — .toggle()
            // podía restaurar "block" en vez de "flex" al mostrarlas.
            $('#' + v + 'View').toggleClass('d-none', !on);
            $('#' + v + 'Foot').toggle(on);
        });
    }

    function resetSearchView() {
        $('#ext-search-query').val('');
        $('#ext-search-results').html(
            '<div class="bv-oc-empty"><i class="fas fa-magnifying-glass"></i>' +
            '<div class="title">Buscar cliente</div>' +
            '<div>Escribe al menos 2 caracteres para buscar en ERP y PrestaShop a la vez.</div></div>'
        );
    }

    function openModal() {
        showView('extSearch');
        resetSearchView();
        currentResult = null;
        hsmCustomerId = null;
        $('#external-search-modal').addClass('on');
    }

    function closeModal() {
        $('#external-search-modal').removeClass('on');
    }

    $(document).on('click', '[data-bv-close]', function () {
        if ($(this).closest('#external-search-modal').length) {
            closeModal();
        }
    });

    $(document).on('click', '#external-search-modal', function (e) {
        if (e.target === this) {
            closeModal();
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#external-search-modal').hasClass('on')) {
            closeModal();
        }
    });

    // ─── vista búsqueda ────────────────────────────────────────────────

    // select2 no es un <select> nativo por dentro — reinicializar tras
    // reconstruir las <option> (en vez de dejar el widget viejo con la
    // lista anterior). dropdownParent lo mantiene dentro del .bv-modal
    // (si no, el dropdown se monta en <body> y queda detrás del overlay).
    function initSelect2(selector) {
        var $el = $(selector);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2({ dropdownParent: $('#external-search-modal'), width: '100%' });
    }

    function loadPlatforms() {
        if (platformsLoaded) {
            return;
        }

        var url = $('#external-search-modal').data('platforms-url');
        var $select = $('#ext-search-platform');

        $.get(url).done(function (resp) {
            platforms = (resp && resp.platforms) || [];
            platformsLoaded = true;

            $select.empty();
            $select.append($('<option></option>').val(ALL_PLATFORMS_VALUE).text('Todas (ERP + PrestaShop)'));
            platforms.forEach(function (p) {
                $select.append($('<option></option>').val(p.platform).text(p.label));
            });

            initSelect2('#ext-search-platform');
        }).fail(function () {
            $select.html('<option value="">Error al cargar plataformas</option>');
        });
    }

    function renderResults(results, failedPlatforms) {
        lastResults = results;
        var $box = $('#ext-search-results');
        var warning = '';

        if (failedPlatforms && failedPlatforms.length) {
            var labels = failedPlatforms.map(function (key) { return platformLabel(key); }).join(', ');
            warning = '<div class="minfo danger"><i class="fas fa-triangle-exclamation"></i>' +
                '<div>No respondió: ' + escapeHtml(labels) + '. Mostrando resultados del resto.</div></div>';
        }

        if (!results.length) {
            $box.html(warning + '<div class="bv-oc-empty"><i class="fas fa-face-frown"></i>' +
                '<div class="title">Sin resultados</div><div>Prueba con otro término de búsqueda.</div></div>');
            return;
        }

        // Agrupados por plataforma y con el mismo estilo neutro (sin color por
        // plataforma, ícono gris, id+email en el detalle) que usa el buscador
        // del modal CLIENTE · IDENTIDAD — mismo componente visual en toda la app.
        var order = [];
        var groups = {};
        results.forEach(function (r, idx) {
            r.__idx = idx;
            if (!groups[r.platform]) {
                groups[r.platform] = [];
                order.push(r.platform);
            }
            groups[r.platform].push(r);
        });

        var html = order.map(function (platform, groupIdx) {
            var items = groups[platform];
            var groupStyle = groupIdx > 0
                ? ' style="margin-top:22px;padding-top:14px;padding-bottom:2px;border-top:1px solid var(--bv-border, #e4e4e7)"'
                : '';

            var rows = items.map(function (r) {
                var detParts = [];
                if (r.id) { detParts.push(escapeHtml(r.id)); }
                if (r.email) { detParts.push(escapeHtml(r.email)); }
                // Algunas búsquedas (ej. tipo "Teléfono" en ERP) ya traen el
                // teléfono en el propio resultado — mostrarlo de una evita
                // depender de la ficha completa para saber si hay con qué
                // enviar WhatsApp más adelante.
                if (r.phone) { detParts.push(escapeHtml(r.phone)); }
                if (r.linked_customer_id) { detParts.push('ya vinculado'); }
                else if (r.matched_customer_id) { detParts.push('coincide con un contacto'); }

                return '<div class="bv-intg-row ext-result-row" data-idx="' + r.__idx + '">' +
                    '<div class="ico"><i class="' + escapeHtml(platformIcon(r.platform)) + '"></i></div>' +
                    '<div class="meta">' +
                        '<span class="name">' + escapeHtml(r.name || '—') + '</span>' +
                        '<span class="det">' + detParts.join(' · ') + '</span>' +
                    '</div>' +
                    '<button type="button" class="bv-intg-mini-outline" title="Ver ficha">' +
                        '<i class="fas fa-chevron-right"></i>' +
                    '</button>' +
                '</div>';
            }).join('');

            return '<div class="nc-platform-group"' + groupStyle + '>' +
                '<span class="bv-modal-label">' + escapeHtml(platformLabel(platform)) + ' (' + items.length + ')</span>' +
                '<div class="bv-intg-list">' + rows + '</div>' +
            '</div>';
        }).join('');

        $box.html(warning + html);
    }

    function runSearch() {
        var $modal = $('#external-search-modal');
        var platform = $('#ext-search-platform').val();
        var query = ($('#ext-search-query').val() || '').trim();

        if (query.length < 2) {
            resetSearchView();
            return;
        }

        var $box = $('#ext-search-results').html(
            '<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Buscando…</div>'
        );
        // Sin selector "Buscar por": el backend infiere el tipo por el
        // contenido de la búsqueda (email/id/nif/nombre) — ver
        // CustomerCommerceSyncService::searchCustomersOrFail() y el
        // comentario de ErpIntegrationDriver::search() sobre el manager Oracle.
        var params = { type: 'auto', query: query };
        if (platform !== ALL_PLATFORMS_VALUE) {
            params.platform = platform;
        }

        $.get($modal.data('search-url'), params)
            .done(function (resp) {
                if (!resp.ok) {
                    $box.html('<div class="minfo danger"><i class="fas fa-triangle-exclamation"></i><div>Ninguna plataforma respondió. Intenta de nuevo.</div></div>');
                    return;
                }
                renderResults(resp.results || [], resp.failed_platforms);
            })
            .fail(function () {
                $box.html('<div class="minfo danger"><i class="fas fa-triangle-exclamation"></i><div>Error al buscar.</div></div>');
            });
    }

    $(document).on('change', '#ext-search-platform', runSearch);

    // Busca al terminar de escribir (Enter o al salir del campo), no en cada
    // pausa mientras se sigue escribiendo — antes buscaba con cada pausa de
    // 400ms, disparando búsquedas a medio escribir.
    $(document).on('blur', '#ext-search-query', runSearch);

    $(document).on('keydown', '#ext-search-query', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            runSearch();
        }
    });

    // ─── vista ficha completa (preview) ───────────────────────────────

    $(document).on('click', '.ext-result-row', function () {
        var result = lastResults[$(this).data('idx')];
        if (result) {
            openPreview(result);
        }
    });

    function openPreview(result) {
        currentResult = result;
        // Si la búsqueda ya trajo teléfono (ej. tipo "Teléfono" en ERP), se
        // toma de una — la ficha completa lo puede sobrescribir si trae uno
        // más actualizado (ver renderPreviewProfile).
        lastPreviewPhone = result.phone || null;
        showView('extPreview');

        var phoneRow = result.phone
            ? '<div class="lbl">Teléfono</div><div class="val" id="ext-preview-phone-val">' + escapeHtml(result.phone) + '</div>'
            : '';
        $('#ext-preview-customer').html(
            '<div class="lbl">Cliente</div><div class="val">' + escapeHtml(result.name || '—') + '</div>' +
            '<div class="lbl">Email</div><div class="val mono">' + escapeHtml(result.email || '—') + '</div>' +
            phoneRow
        );
        renderPreviewActions(result);

        // La ficha completa se busca por email (ERP además acepta teléfono
        // como respaldo, PrestaShop no) — pero el resultado de búsqueda nunca
        // trae teléfono, solo id/nombre/email/meta. Sin email no hay con qué
        // pedirla: evita la llamada (que antes fallaba con 422) y lo dice
        // claro en vez de mostrar un error genérico.
        if (!result.email) {
            $('#ext-preview-orders').html(
                '<div class="bv-oc-empty"><i class="fas fa-circle-info"></i>' +
                '<div class="title">Sin ficha completa</div>' +
                '<div>Este resultado no tiene email registrado en la búsqueda, así que no se puede traer más detalle. Puedes continuar igual.</div></div>'
            );
            return;
        }

        $('#ext-preview-orders').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando ficha completa…</div>');

        var $modal = $('#external-search-modal');
        $.get($modal.data('preview-url'), { platform: result.platform, email: result.email, external_id: result.id })
            .done(renderPreviewProfile)
            .fail(function () {
                $('#ext-preview-orders').html('<div class="minfo danger"><i class="fas fa-triangle-exclamation"></i><div>No se pudo cargar la ficha completa.</div></div>');
            });
    }

    var PREVIEW_FIELD_LABELS = {
        nif: 'NIF/DNI', city: 'Ciudad', province: 'Provincia',
        address: 'Dirección', credit_limit: 'Crédito', balance_pending: 'Saldo pendiente',
        balance_invoiced: 'Facturado', loyalty_points: 'Puntos', payment_terms: 'Forma de pago',
    };

    function renderPreviewProfile(resp) {
        var customer = (resp && resp.customer) || {};

        if (!customer.found) {
            $('#ext-preview-orders').html(
                '<div class="bv-oc-empty"><i class="fas fa-circle-info"></i>' +
                '<div class="title">Sin ficha completa</div><div>La plataforma no devolvió más datos para este cliente.</div></div>'
            );
            return;
        }

        // El teléfono se maneja aparte del resto de campos (no vía
        // PREVIEW_FIELD_LABELS): puede haberse pintado ya desde el propio
        // resultado de búsqueda (ver openPreview) — acá solo se actualiza esa
        // misma fila en vez de duplicarla si la ficha completa trae uno.
        if (customer.phone) {
            lastPreviewPhone = customer.phone;
            var $phoneVal = $('#ext-preview-phone-val');
            if ($phoneVal.length) {
                $phoneVal.text(customer.phone);
            } else {
                $('#ext-preview-customer').append(
                    '<div class="lbl">Teléfono</div><div class="val" id="ext-preview-phone-val">' + escapeHtml(customer.phone) + '</div>'
                );
            }
        }

        var extraRows = '';
        Object.keys(PREVIEW_FIELD_LABELS).forEach(function (key) {
            var value = customer[key];
            if (value !== undefined && value !== null && value !== '') {
                extraRows += '<div class="lbl">' + PREVIEW_FIELD_LABELS[key] + '</div><div class="val">' + escapeHtml(value) + '</div>';
            }
        });
        if (extraRows) {
            $('#ext-preview-customer').append(extraRows);
        }

        var orders = (resp && resp.orders) || [];
        var html = '';

        if (orders.length) {
            html += '<div class="flabel" style="margin-bottom:6px">Pedidos (' + orders.length + ')</div>';
            html += '<div class="reason-list">' + orders.slice(0, 5).map(function (o) {
                var title = o.reference || o.number || ('#' + (o.id || ''));
                var subtitle = [o.status, (o.total || o.amount)].filter(Boolean).join(' · ');
                return '<div class="reason" style="cursor:default">' +
                    '<div class="ic"><i class="fas fa-box"></i></div>' +
                    '<div class="body"><div class="t">' + escapeHtml(title) + '</div><div class="s">' + escapeHtml(subtitle) + '</div></div>' +
                    '</div>';
            }).join('') + '</div>';
        } else {
            html += '<div class="bv-oc-empty"><i class="fas fa-box-open"></i><div class="title">Sin pedidos</div></div>';
        }

        $('#ext-preview-orders').html(html);
    }

    function renderPreviewActions(result) {
        var $modal = $('#external-search-modal');
        var mode = $modal.data('mode');
        var $actions = $('#ext-preview-actions').empty();

        if (result.linked_customer_id) {
            if (mode === 'link' && String(result.linked_customer_id) === String($modal.data('customer-id'))) {
                $actions.append('<div class="minfo"><i class="fas fa-circle-check"></i><div>Ya vinculado a este contacto.</div></div>');
            } else {
                $actions.append(
                    $('<a class="btn-secondary"></a>')
                        .attr('href', $modal.data('show-url-base') + '/' + result.linked_customer_id)
                        .text('Ver ficha')
                );
            }
            hsmCustomerId = result.linked_customer_id;
            $actions.append($('<button type="button" class="btn-primary" id="ext-preview-send-hsm"></button>').text('Enviar plantilla WhatsApp'));
            return;
        }

        if (mode === 'link') {
            $actions.append($('<button type="button" class="btn-primary ext-link-btn"></button>').text('Vincular a este contacto'));
        } else if (result.matched_customer_id) {
            $actions.append(
                $('<button type="button" class="btn-primary ext-link-btn"></button>')
                    .attr('data-target-customer-id', result.matched_customer_id)
                    .text('Vincular a contacto existente')
            );
        } else {
            $actions.append($('<button type="button" class="btn-primary ext-create-btn"></button>').text('Crear contacto nuevo'));
        }
    }

    $(document).on('click', '#ext-preview-back', function () { showView('extSearch'); });

    $(document).on('click', '#ext-preview-send-hsm', function () {
        openHsmStep();
    });

    // Tras crear/vincular, solo se ofrece el paso de WhatsApp si el contacto
    // tiene teléfono de verdad (dato que llega recién en la respuesta de
    // create/link) — antes se pasaba directo a la plantilla y el error
    // "no tiene número de WhatsApp" aparecía después de rellenarla entera.
    function proceedAfterCreateOrLink(customerId, hasPhone) {
        hsmCustomerId = customerId;

        if (hasPhone) {
            openHsmStep();
            return;
        }

        toastr.warning('El contacto no tiene teléfono registrado — no se puede enviar plantilla de WhatsApp por ahora.', 'Aviso');
        closeModal();
    }

    $(document).on('click', '.ext-create-btn', function () {
        var $modal = $('#external-search-modal');
        var $btn = $(this).prop('disabled', true);

        // El resultado de búsqueda a veces ya trae teléfono (ej. búsqueda por
        // tipo "Teléfono" en ERP), y la ficha completa (preview) puede traer
        // uno más actualizado — se prioriza la ficha completa si llegó a cargar.
        var phone = lastPreviewPhone || currentResult.phone || null;

        $.ajax({
            url: $modal.data('create-url'),
            method: 'POST',
            dataType: 'json',
            data: { platform: currentResult.platform, external_id: currentResult.id, phone: phone },
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        }).done(function (resp) {
            toastr.success('Contacto creado', 'Listo');
            proceedAfterCreateOrLink(resp.customer_id, resp.has_phone);
        }).fail(function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo crear el contacto', 'Error');
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.ext-link-btn', function () {
        var $modal = $('#external-search-modal');
        var targetCustomerId = $(this).data('target-customer-id') || $modal.data('customer-id');
        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url: $modal.data('link-url-base') + '/' + targetCustomerId + '/external-link',
            method: 'POST',
            dataType: 'json',
            data: { platform: currentResult.platform, external_id: currentResult.id },
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        }).done(function (resp) {
            toastr.success('Integración vinculada', 'Listo');
            proceedAfterCreateOrLink(targetCustomerId, resp.has_phone);
        }).fail(function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo vincular', 'Error');
            $btn.prop('disabled', false);
        });
    });

    // ─── vista enviar plantilla WhatsApp (inline) ─────────────────────

    function openHsmStep() {
        showView('extHsm');
        $('#ext-hsm-target').html(
            '<i class="fas fa-comment-sms"></i><div>Se enviará a <strong>' +
            escapeHtml(currentResult ? currentResult.name : 'este contacto') + '</strong>.</div>'
        );
        loadHsmTemplates();
    }

    function loadHsmTemplates() {
        if (hsmTemplatesLoaded) {
            renderHsmTemplateSelect();
            return;
        }

        $.get($('#external-search-modal').data('hsm-templates-url')).done(function (resp) {
            hsmTemplates = (resp && resp.templates) || [];
            hsmTemplatesLoaded = true;
            renderHsmTemplateSelect();
        });
    }

    function findHsmTemplate(id) {
        return hsmTemplates.find(function (t) { return String(t.id) === String(id); });
    }

    function renderHsmTemplateSelect() {
        var $select = $('#ext-hsm-template');

        if (!hsmTemplates.length) {
            $select.html('<option value="">No hay plantillas aprobadas</option>');
            $('#ext-hsm-vars').empty();
            $('#ext-hsm-preview').empty();
            return;
        }

        $select.empty();
        hsmTemplates.forEach(function (t) {
            $select.append($('<option></option>').val(t.id).text(t.name));
        });

        selectHsmTemplate(hsmTemplates[0].id);
    }

    function selectHsmTemplate(id) {
        var t = findHsmTemplate(id);
        var $vars = $('#ext-hsm-vars').empty();

        if (t && t.param_count) {
            for (var i = 1; i <= t.param_count; i++) {
                $vars.append(
                    $('<div class="field"></div>').append(
                        $('<div class="flabel"></div>').text('Variable {{' + i + '}}'),
                        $('<input type="text" class="finput ext-hsm-var-input">').attr('data-var-idx', i)
                    )
                );
            }
        }

        renderHsmPreview();
    }

    function renderHsmPreview() {
        var t = findHsmTemplate($('#ext-hsm-template').val());
        var $box = $('#ext-hsm-preview');

        if (!t) {
            $box.html('<div class="val">Elige una plantilla.</div>');
            return;
        }

        var body = t.body || '';
        $('.ext-hsm-var-input').each(function () {
            var idx = $(this).data('var-idx');
            var val = ($(this).val() || '').trim();
            body = body.split('{{' + idx + '}}').join(val || '{{' + idx + '}}');
        });

        $box.html('<div class="lbl">Mensaje</div><div class="val">' + escapeHtml(body).replace(/\n/g, '<br>') + '</div>');
    }

    $(document).on('change', '#ext-hsm-template', function () { selectHsmTemplate($(this).val()); });
    $(document).on('input', '.ext-hsm-var-input', renderHsmPreview);
    $(document).on('click', '#ext-hsm-back', function () { showView('extPreview'); });

    $(document).on('click', '#ext-hsm-send', function () {
        var t = findHsmTemplate($('#ext-hsm-template').val());

        if (!t || !hsmCustomerId) {
            toastr.warning('Elige una plantilla', 'Aviso');
            return;
        }

        var variables = [];
        var $firstInvalid = null;
        $('.ext-hsm-var-input').each(function () {
            var val = ($(this).val() || '').trim();
            $(this).toggleClass('is-invalid', !val);
            if (!val && !$firstInvalid) {
                $firstInvalid = $(this);
            }
            variables.push(val);
        });

        if ($firstInvalid) {
            toastr.warning('Completa todas las variables de la plantilla', 'Aviso');
            $firstInvalid.trigger('focus');
            return;
        }

        var $btn = $('#ext-hsm-send').prop('disabled', true);

        $.ajax({
            url: $('#external-search-modal').data('link-url-base') + '/' + hsmCustomerId + '/send-hsm',
            method: 'POST',
            dataType: 'json',
            data: { template_name: t.external_id, variables: variables },
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        }).done(function (resp) {
            toastr.success('Plantilla enviada', 'Listo');
            closeModal();

            if (resp && resp.conversation_id) {
                window.location.href = $('#external-search-modal').data('inbox-url') + '?selected=' + resp.conversation_id;
            }
        }).fail(function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo enviar la plantilla', 'Error');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    // ─── triggers ──────────────────────────────────────────────────────

    $(document).on('click', '#external-search-trigger', function () {
        var $modal = $('#external-search-modal');
        $modal.data('mode', 'index');
        $modal.removeData('customer-id');
        openModal();
        loadPlatforms();
    });

    $(document).on('click', '.external-link-trigger', function () {
        var $modal = $('#external-search-modal');
        $modal.data('mode', 'link');
        $modal.data('customer-id', $(this).data('customer-id'));
        openModal();
        loadPlatforms();
    });
})();
