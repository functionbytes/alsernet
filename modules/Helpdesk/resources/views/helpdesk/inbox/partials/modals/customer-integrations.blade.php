{{-- Modal: Integraciones del cliente --}}
<div class="bv-modal" data-bv-modal-name="customer-integrations">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-plug"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CLIENTE · INTEGRACIONES</span>
                <div class="bv-modal-title"><span id="ciModalTitle">Integraciones del cliente</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body">

            {{-- Vista principal: lista de integraciones --}}
            <div id="ciMainView">
                <div class="info-table" id="ciCustomer">
                    <div class="lbl">Cliente</div>
                    <div class="val" id="ciCustomerName">—</div>
                    <div class="lbl">Email</div>
                    <div class="val" id="ciCustomerEmail">—</div>
                </div>

                <div class="field">
                    <div class="flabel">Plataformas integradas</div>
                    <div class="bv-intg-list" id="ciList">
                        <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>
                    </div>
                </div>

                <div class="minfo">
                    <i class="fas fa-circle-info"></i>
                    <div>Los datos del cliente se sincronizan automáticamente desde las plataformas conectadas.</div>
                </div>
            </div>

            {{-- Vista de búsqueda/vinculación manual --}}
            <div id="ciLinkPanel" style="display:none">
                <div class="field">
                    <div class="flabel">Plataforma</div>
                    <select class="form-control form-control-sm" id="ciPlatform">
                        <option value="prestashop">PrestaShop</option>
                        <option value="erp">Gestión (ERP)</option>
                    </select>
                </div>
                <div class="field">
                    <div class="flabel">Buscar por</div>
                    <div class="d-flex gap-2">
                        <select class="form-control form-control-sm" id="ciSearchType">
                            <option value="email">Email</option>
                            <option value="name">Nombre</option>
                            <option value="id">ID / NIF</option>
                        </select>
                        <input type="text" class="form-control form-control-sm flex-grow-1" id="ciSearchQ"
                               placeholder="Escribe para buscar…">
                        <button type="button" class="btn btn-sm btn-primary" id="ciSearchBtn" style="white-space:nowrap">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="field">
                    <div class="flabel">Resultados</div>
                    <div id="ciSearchResults">
                        <div class="rsp-empty">Ingresa un término de búsqueda.</div>
                    </div>
                </div>
                <div class="minfo">
                    <i class="fas fa-circle-info"></i>
                    <div>Elige el cliente correcto y presiona <strong>Vincular</strong> para asociarlo.</div>
                </div>
            </div>

        </div>

        <div class="bv-modal-foot">
            {{-- Footer vista principal --}}
            <div id="ciFootMain">
                <button class="btn-primary w-100 mb-2" id="ciSync" type="button">Sincronizar ahora</button>
                <button class="btn-secondary w-100 mb-2" id="ciLink" type="button" disabled title="Próximamente">
                    Vincular plataforma <span class="badge bg-secondary ms-1" style="font-size:0.65rem">Próximamente</span>
                </button>
                <button class="btn-secondary w-100" data-bv-close>Cerrar</button>
            </div>
            {{-- Footer vista de búsqueda --}}
            <div id="ciFootSearch" style="display:none">
                <button class="btn-secondary w-100" id="ciBackBtn" type="button">
                    <i class="fas fa-arrow-left"></i> Volver a integraciones
                </button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var PLATFORM_ICONS = {
        prestashop: 'fas fa-cart-shopping',
        erp:        'fas fa-clipboard-list',
        shopify:    'fab fa-shopify',
        woocommerce:'fab fa-wordpress',
        magento:    'fas fa-store',
        bigcommerce:'fas fa-store',
    };

    var PLATFORM_COLORS = {
        prestashop: '#df1d1d',
        erp:        '#f59e0b',
    };

    function iconFor(platform) {
        return PLATFORM_ICONS[platform] || 'fas fa-plug';
    }

    function colorFor(platform) {
        return PLATFORM_COLORS[platform] || null;
    }

    function esc(s) {
        return HDCommerce.esc ? HDCommerce.esc(s) : $('<div>').text(s).html();
    }

    // ── Lista de integraciones ──────────────────────────────────────────────

    function renderList(integrations) {
        if (!integrations || !integrations.length) {
            $('#ciList').html('<div class="bv-oc-empty"><i class="fas fa-plug"></i><div class="title">Sin integraciones detectadas</div></div>');
            return;
        }
        var html = integrations.map(function (it) {
            var connected = !!it.connected;
            var sub = connected ? 'ID: ' + esc(it.external_id) : 'Sin vincular';
            var status = connected ? 'Conectado' : 'Desconectado';
            var unlinkBtn = '';
            var color = colorFor(it.platform);
            var icoStyle = color ? ' style="color:' + color + '"' : '';
            return '<div class="bv-intg-row' + (connected ? '' : ' is-off') + '">' +
                '<div class="ico"' + icoStyle + '><i class="' + iconFor(it.platform) + '"></i></div>' +
                '<div class="meta">' +
                    '<span class="name">' + esc(it.label) + '</span>' +
                    '<span class="sub">' + sub + '</span>' +
                '</div>' +
                '<div class="d-flex align-items-center gap-2">' +
                    '<span class="status">' + status + '</span>' +
                    unlinkBtn +
                '</div>' +
            '</div>';
        }).join('');
        $('#ciList').html(html);
    }

    // ── Carga inicial de integraciones ─────────────────────────────────────

    function load() {
        var base = HDCommerce.base();
        if (!base) { return; }
        $('#ciList').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');
        HDCommerce.ajax({ url: base + '/integrations', method: 'GET' })
            .done(function (resp) {
                var c = resp.customer || {};
                $('#ciCustomerName').text(c.name || '—');
                $('#ciCustomerEmail').text(c.email || '—');
                renderList(resp.integrations);
            })
            .fail(function () {
                $('#ciList').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">No se pudieron cargar las integraciones</div></div>');
            });
    }

    // ── Sincronización automática ──────────────────────────────────────────

    $(document).on('click', '#ciSync', function () {
        var base = HDCommerce.base();
        if (!base) { return; }
        var $btn = $(this).prop('disabled', true);
        HDCommerce.ajax({ url: base + '/integrations/sync', method: 'POST' })
            .done(function (resp) {
                toastr[resp.linked ? 'success' : 'info'](resp.message || 'Sincronización completada.');
                renderList(resp.integrations);
            })
            .fail(function (xhr) {
                toastr.error(HDCommerce.errorMessage(xhr, 'No se pudo sincronizar.'));
            })
            .always(function () { $btn.prop('disabled', false); });
    });

    // ── Navegación entre vistas ────────────────────────────────────────────

    function showMainView() {
        $('#ciLinkPanel').hide();
        $('#ciMainView').show();
        $('#ciFootSearch').hide();
        $('#ciFootMain').show();
        $('#ciModalTitle').text('Integraciones del cliente');
        $('#ciSearchQ').val('');
        $('#ciSearchResults').html('<div class="rsp-empty">Ingresa un término de búsqueda.</div>');
    }

    // ── Tipos de búsqueda por plataforma ──────────────────────────────────

    var SEARCH_TYPES = {
        prestashop: [
            { value: 'email', label: 'Email' },
            { value: 'name',  label: 'Nombre' },
            { value: 'id',    label: 'ID numérico' },
        ],
        erp: [
            { value: 'email', label: 'Email' },
            { value: 'phone', label: 'Teléfono' },
            { value: 'id',    label: 'NIF / DNI' },
        ],
    };

    function updateSearchTypes(platform) {
        var types = SEARCH_TYPES[platform] || SEARCH_TYPES.prestashop;
        var $sel = $('#ciSearchType');
        var current = $sel.val();
        $sel.empty();
        types.forEach(function (t) {
            $sel.append($('<option>').val(t.value).text(t.label));
        });
        if ($sel.find('option[value="' + current + '"]').length) {
            $sel.val(current);
        }
    }

    $(document).on('change', '#ciPlatform', function () {
        updateSearchTypes($(this).val());
        $('#ciSearchQ').val('');
        $('#ciSearchResults').html('<div class="rsp-empty">Ingresa un término de búsqueda.</div>');
    });

    function showLinkPanel() {
        $('#ciMainView').hide();
        $('#ciLinkPanel').show();
        $('#ciFootMain').hide();
        $('#ciFootSearch').show();
        $('#ciModalTitle').text('Vincular plataforma');
        updateSearchTypes($('#ciPlatform').val());
        $('#ciSearchQ').focus();
    }

    $(document).on('click', '#ciLink', showLinkPanel);
    $(document).on('click', '#ciBackBtn', showMainView);

    // ── Búsqueda de clientes ───────────────────────────────────────────────

    function renderSearchResults(results, platform) {
        if (!results || !results.length) {
            $('#ciSearchResults').html('<div class="rsp-empty">No se encontraron resultados.</div>');
            return;
        }
        var srColor = colorFor(platform);
        var srIcoStyle = srColor ? ' style="color:' + srColor + '"' : '';
        var html = results.map(function (r) {
            return '<div class="bv-intg-row">' +
                '<div class="ico"' + srIcoStyle + '><i class="' + iconFor(platform) + '"></i></div>' +
                '<div class="meta">' +
                    '<span class="name">' + esc(r.name || '—') + '</span>' +
                    '<span class="sub">' + esc(r.email || r.meta || '') + '</span>' +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-primary ci-link-result" ' +
                    'data-id="' + esc(r.id) + '" ' +
                    'data-platform="' + esc(platform) + '" ' +
                    'data-name="' + esc(r.name || '') + '">' +
                    'Vincular' +
                '</button>' +
            '</div>';
        }).join('');
        $('#ciSearchResults').html(html);
    }

    function doSearch() {
        var base = HDCommerce.base();
        if (!base) { return; }
        var q = $.trim($('#ciSearchQ').val());
        var platform = $('#ciPlatform').val();
        var type = $('#ciSearchType').val();

        if (q.length < 2) {
            toastr.warning('Escribe al menos 2 caracteres.');
            return;
        }

        $('#ciSearchResults').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Buscando…</div>');
        $('#ciSearchBtn').prop('disabled', true);

        HDCommerce.ajax({
            url: base + '/integrations/search',
            method: 'GET',
            data: { platform: platform, q: q, type: type },
        })
            .done(function (resp) {
                renderSearchResults(resp.results || [], platform);
            })
            .fail(function (xhr) {
                $('#ciSearchResults').html('<div class="rsp-empty"><i class="fas fa-triangle-exclamation"></i> Error al buscar.</div>');
                toastr.error(HDCommerce.errorMessage(xhr, 'Error al buscar.'));
            })
            .always(function () { $('#ciSearchBtn').prop('disabled', false); });
    }

    $(document).on('click', '#ciSearchBtn', doSearch);
    $(document).on('keydown', '#ciSearchQ', function (e) {
        if (e.key === 'Enter') { doSearch(); }
    });

    // ── Vincular resultado seleccionado ───────────────────────────────────

    $(document).on('click', '.ci-link-result', function () {
        var base = HDCommerce.base();
        if (!base) { return; }
        var $btn = $(this).prop('disabled', true);
        var externalId = $btn.data('id');
        var platform   = $btn.data('platform');
        var name       = $btn.data('name');

        HDCommerce.ajax({
            url: base + '/integrations/link',
            method: 'POST',
            data: JSON.stringify({ platform: platform, external_id: externalId }),
            contentType: 'application/json',
        })
            .done(function (resp) {
                toastr.success('Vinculado correctamente: ' + esc(name));
                renderList(resp.integrations);
                showMainView();
                setTimeout(function () { window.location.reload(); }, 900);
            })
            .fail(function (xhr) {
                toastr.error(HDCommerce.errorMessage(xhr, 'No se pudo vincular.'));
                $btn.prop('disabled', false);
            });
    });

    // ── Desvincular plataforma ─────────────────────────────────────────────

    $(document).on('click', '.ci-unlink-btn', function () {
        var base = HDCommerce.base();
        if (!base) { return; }
        var $btn = $(this);
        var platform = $btn.data('platform');
        var label    = $btn.data('label');

        window.__confirm('¿Desvincular ' + esc(label) + ' de este cliente?', function () {
            $btn.prop('disabled', true);

            HDCommerce.ajax({
                url: base + '/integrations/unlink',
                method: 'POST',
                data: JSON.stringify({ platform: platform }),
                contentType: 'application/json',
            })
                .done(function (resp) {
                    toastr.success(resp.message || 'Desvinculado.');
                    renderList(resp.integrations);
                    setTimeout(function () { window.location.reload(); }, 900);
                })
                .fail(function (xhr) {
                    toastr.error(HDCommerce.errorMessage(xhr, 'No se pudo desvincular.'));
                    $btn.prop('disabled', false);
                });
        });
    });

    // ── Inicialización al abrir el modal ──────────────────────────────────

    // Escucha el evento global del sistema bv-modal (dispara conversations.js al hacer click en data-bv-modal)
    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'customer-integrations') { return; }
        showMainView();
        load();
    });

    window.openCustomerIntegrations = function () {
        if (!HDCommerce.customerId()) {
            if (window.toastr) { toastr.warning('Selecciona una conversación con cliente.'); }
            return;
        }
        showMainView();
        HDCommerce.open('customer-integrations');
        load();
    };
})();
</script>
@endpush
@endonce
