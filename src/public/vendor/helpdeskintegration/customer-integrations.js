/**
 * customer-integrations.js — HelpdeskIntegration module
 *
 * Modal "Integraciones del cliente": lista de plataformas conectadas,
 * busqueda/vinculacion unificada y sincronizacion (individual y masiva).
 *
 * User-facing strings come from window.HelpdeskIntegrationLang, emitted by
 * the customer-integrations and verify-customer-identity Blade partials
 * (`__('helpdeskintegration::messages.js')`). Hardcoded Spanish fallbacks
 * keep the file working if that script is absent.
 */
(function () {
    'use strict';

    var L = window.HelpdeskIntegrationLang || {};

    function t(key, fallback, repl) {
        var s = L[key] || fallback;
        if (repl) { Object.keys(repl).forEach(function (k) { s = s.replace(':' + k, repl[k]); }); }
        return s;
    }

    // Metadata (icono/color/tipos de busqueda) ya no se hardcodea: viene del
    // catalogo de proveedores (IntegrationProvider) via la respuesta de /integrations.
    var linkablePlatforms = [];
    var confirmPlatform = null;
    var lastIntegrations = [];
    // Plataformas con sync en vuelo: la fila muestra "Sincronizando…" con
    // spinner (mockup 50) en vez de solo deshabilitar el botón.
    var syncingPlatforms = {};

    function findLinkablePlatform(platform) {
        var matches = linkablePlatforms.filter(function (p) { return p.platform === platform; });
        return matches.length ? matches[0] : null;
    }

    function esc(s) {
        return HDCommerce.esc ? HDCommerce.esc(s) : $('<div>').text(s).html();
    }

    // ── Carga inicial ────────────────────────────────────────────────────────

    function load() {
        var base = HDCommerce.base();
        if (!base) { return; }

        HDCommerce.ajax({ url: base + '/integrations', method: 'GET' })
            .done(function (resp) {
                var c = resp.customer || {};
                $('#ciCustomerName').text(c.name || '—');
                $('#ciCustomerEmail').text(c.email || '—');
                $('#ciGateCustomerName').text(c.name || '—');
                $('#ciGateCustomerContact').text(c.email || c.phone || '—');

                if (!resp.identity || !resp.identity.verified) {
                    showGateView();
                    return;
                }

                linkablePlatforms = resp.linkable_platforms || [];
                lastIntegrations = resp.integrations || [];
                confirmPlatform = null;
                showMainView();
                renderList(lastIntegrations, resp.last_activity);

                // Cliente sin ninguna plataforma vinculada: buscar automaticamente
                // en todas las plataformas disponibles por email (o telefono si no
                // hay email) en vez de esperar a que el agente lo dispare a mano.
                var anyConnected = lastIntegrations.some(function (it) { return it.connected; });
                var autoQuery = c.email || c.phone || '';
                var autoType = c.email ? 'email' : 'phone';

                if (!anyConnected && autoQuery && linkablePlatforms.length) {
                    showLinkPanel({ auto: true, query: autoQuery, type: autoType });
                }
            })
            .fail(function () {
                $('#ciList').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">' + t('load_failed', 'No se pudieron cargar las integraciones') + '</div></div>');
            });
    }

    // ── Gate de verificacion de identidad (delega al modal reutilizable) ────

    function showGateView() {
        $('#ciGateView').show();
        $('#ciMainView').hide();
        $('#ciLinkPanel').hide();
        $('#ciFootGate').show();
        $('#ciFootMain').hide();
        $('#ciFootSearch').hide();
        $('#ciModalTitle').text(t('verify_identity_title', 'Verificar identidad'));
        setHeadIcon('fa-plug', false);
    }

    $(document).on('click', '#ciOpenVerify', function () {
        var customerId = HDCommerce.customerId();
        if (!customerId || typeof window.openCustomerIdentityVerification !== 'function') { return; }

        window.openCustomerIdentityVerification(customerId, function (resp) {
            linkablePlatforms = resp.linkable_platforms || [];
            lastIntegrations = resp.integrations || [];
            confirmPlatform = null;
            showMainView();
            renderList(lastIntegrations, resp.last_activity);
        });
    });

    // ── Lista de integraciones (secciones Conectadas / Disponibles) ─────────

    function statusMeta(it) {
        if (syncingPlatforms[it.platform]) {
            return { cls: 'is-syncing', label: t('syncing', 'Sincronizando…') };
        }

        // 'pending': el sync masivo agotó su presupuesto de tiempo y esta
        // plataforma quedó encolada en background — misma visual de "en curso".
        if (it.sync_status === 'pending') {
            return { cls: 'is-syncing', label: t('sync_pending', 'Sincronización en curso…') };
        }

        if (it.sync_status === 'not_found') {
            return { cls: 'is-error', label: t('id_not_found', 'ID no encontrado en la plataforma') };
        }

        if (it.sync_status === 'error') {
            return { cls: 'is-error', label: t('sync_error', 'Error de sincronización') };
        }

        return { cls: '', label: t('connected', 'Conectado') };
    }

    function connectedRowHtml(it) {
        if (confirmPlatform === it.platform) {
            return confirmRowHtml(it);
        }

        var s = statusMeta(it);
        var syncing = s.cls === 'is-syncing';
        var icoStyle = it.color ? ' style="color:' + esc(it.color) + '"' : '';
        var critBadge = it.is_critical
            ? ' <i class="fas fa-shield-halved intg-crit" title="' + t('critical_integration', 'Integración crítica') + '"></i>'
            : '';
        var det = (it.sync_status === 'not_found'
                ? t('id_no_longer_exists', 'El identificador ya no existe en la plataforma')
                : (it.sync_status === 'pending'
                    ? t('sync_pending_detail', 'Se verificará en segundo plano')
                    : (s.cls === 'is-error' ? t('sync_failed_detail', 'No se pudo sincronizar') : (t('synced_prefix', 'Sincronizado ') + esc(new Date(it.last_synced_at).toLocaleString())))))
            + (it.external_id ? (' · <span class="mono">' + esc(it.external_id) + '</span>') : '');

        var statusHtml = syncing
            ? '<span class="st"><span class="bv-intg-spin"></span>' + s.label + '</span>'
            : '<span class="st"><span class="dot"></span>' + s.label + '</span>';

        var actionBtn = s.cls === 'is-error'
            ? '<button type="button" class="bv-intg-mini-outline ci-sync-btn" data-platform="' + esc(it.platform) + '">' + t('retry', 'Reintentar') + '</button>'
            : '<button type="button" class="bv-intg-act-icon ci-sync-btn" data-platform="' + esc(it.platform) + '" title="' + t('sync_action', 'Sincronizar') + '" aria-label="' + t('sync_action', 'Sincronizar') + '"><i class="fas fa-rotate"></i></button>';

        // Mientras sincroniza no se ofrecen acciones (igual que el mockup).
        var actions = syncing ? '' :
            '<span class="bv-intg-actions">' +
                actionBtn +
                '<button type="button" class="bv-intg-act-icon danger ci-unlink-btn" data-platform="' + esc(it.platform) + '" ' +
                    'title="' + t('unlink_action', 'Desvincular') + '" aria-label="' + t('unlink_action', 'Desvincular') + '"><i class="fas fa-link-slash"></i></button>' +
            '</span>';

        return '<div class="bv-intg-row' + (s.cls ? ' ' + s.cls : '') + '">' +
            '<div class="ico"' + icoStyle + '><i class="' + esc(it.icon || 'fas fa-plug') + '"></i></div>' +
            '<div class="meta">' +
                '<span class="name">' + esc(it.label) + critBadge + '</span>' +
                statusHtml +
                '<span class="det">' + det + '</span>' +
            '</div>' +
            actions +
        '</div>';
    }

    function confirmRowHtml(it) {
        return '<div class="bv-intg-confirm">' +
            '<div style="flex:1">' +
                '<div class="ct">' + t('unlink_confirm_title', 'Desvincular «:label»', { label: esc(it.label) }) + (it.is_critical ? t('critical_suffix', ' · integración crítica') : '') + '</div>' +
                '<div class="cs">' + t('unlink_confirm_body', 'Se dejarán de sincronizar sus datos.') +
                    (it.is_critical ? ' ' + t('unlink_confirm_type_label', 'Escribe <strong>:label</strong> para confirmar.', { label: esc(it.label) }) : '') +
                '</div>' +
                (it.is_critical
                    ? '<input type="text" class="finput ci-confirm-input" style="margin-top:7px" data-platform="' + esc(it.platform) + '" placeholder="' + esc(it.label) + '" autocomplete="off">'
                    : '') +
            '</div>' +
            '<div class="bv-intg-confirm-actions">' +
                '<button type="button" class="bv-intg-mini-danger ci-confirm-unlink-btn" data-platform="' + esc(it.platform) + '" data-label="' + esc(it.label) + '"' +
                    (it.is_critical ? ' disabled' : '') + '>' + t('desync_button', 'Desincronizar') + '</button>' +
                '<button type="button" class="bv-intg-mini-outline ci-cancel-confirm-btn">' + t('cancel', 'Cancelar') + '</button>' +
            '</div>' +
        '</div>';
    }

    function availableRowHtml(it) {
        var icoStyle = it.color ? ' style="color:' + esc(it.color) + '"' : '';

        return '<div class="bv-intg-row is-off">' +
            '<div class="ico"' + icoStyle + '><i class="' + esc(it.icon || 'fas fa-plug') + '"></i></div>' +
            '<div class="meta">' +
                '<span class="name">' + esc(it.label) + '</span>' +
                '<span class="det">' + esc(it.description || t('not_linked', 'No vinculado')) + '</span>' +
            '</div>' +
            '<button type="button" class="bv-intg-mini ci-link-open-btn">' + t('link_button', 'Vincular') + '</button>' +
        '</div>';
    }

    function section(title, count, rowsHtml) {
        return '<div class="bv-intg-sec">' +
            '<div class="bv-intg-group">' + title + ' <span class="c">' + count + '</span></div>' +
            rowsHtml +
        '</div>';
    }

    // La línea "Última actividad" se conserva entre re-renders parciales
    // (confirmación inline, spinner de sync) que no traen last_activity fresco.
    var lastActivityLine = null;

    function renderList(integrations, lastActivity) {
        if (lastActivity !== undefined) { lastActivityLine = lastActivity; }
        lastActivity = lastActivityLine;

        var connected = (integrations || []).filter(function (it) { return it.connected; });
        var available = (integrations || []).filter(function (it) { return !it.connected; });

        var html = '';

        html += connected.length
            ? section(t('connected_section', 'Conectadas'), connected.length, connected.map(connectedRowHtml).join(''))
            : '<div class="bv-oc-empty"><i class="fas fa-plug-circle-xmark"></i><div class="title">' + t('no_integrations_title', 'Sin plataformas integradas') + '</div><div>' + t('no_integrations_body', 'Vincula una para sincronizar sus datos automáticamente.') + '</div></div>';

        if (available.length) {
            html += section(t('available_section', 'Disponibles'), available.length, available.map(availableRowHtml).join(''));
        }

        $('#ciList').html(html);

        $('#ciSyncAll').toggle(connected.length > 0);
        $('#ciLink').prop('disabled', linkablePlatforms.length === 0)
            .toggleClass('btn-primary', connected.length === 0)
            .toggleClass('btn-secondary', connected.length > 0);
        $('#ciLinkLabel').text(connected.length === 0 ? t('link_first_platform', 'Vincular primera plataforma') : t('link_platform', 'Vincular plataforma'));

        if (lastActivity) {
            $('#ciLastActivity').show().html(
                '<i class="fas fa-clock"></i> ' + t('last_activity_prefix', 'Última actividad: ') + esc(lastActivity) +
                ' <button type="button" class="bv-vi-linkbtn" id="ciOpenAuditLog">' + t('view_full_history', 'Ver historial completo') + '</button>'
            );
        } else {
            $('#ciLastActivity').hide();
        }
    }

    // ── Sincronizacion (individual y masiva) ────────────────────────────────

    $(document).on('click', '#ciSyncAll', function () {
        var base = HDCommerce.base();
        if (!base) { return; }
        var $btn = $(this).prop('disabled', true);

        lastIntegrations.forEach(function (it) {
            if (it.connected) { syncingPlatforms[it.platform] = true; }
        });
        renderList(lastIntegrations);

        HDCommerce.ajax({ url: base + '/integrations/sync', method: 'POST' })
            .done(function (resp) {
                toastr[resp.linked ? 'success' : 'info'](resp.message || t('sync_completed', 'Sincronización completada.'));
                lastIntegrations = resp.integrations || lastIntegrations;
                linkablePlatforms = resp.linkable_platforms || linkablePlatforms;
            })
            .fail(function (xhr) {
                toastr.error(HDCommerce.errorMessage(xhr, t('sync_failed', 'No se pudo sincronizar.')));
            })
            .always(function () {
                syncingPlatforms = {};
                $btn.prop('disabled', false);
                renderList(lastIntegrations);
            });
    });

    $(document).on('click', '.ci-sync-btn', function () {
        var base = HDCommerce.base();
        if (!base) { return; }
        var platform = $(this).data('platform');

        syncingPlatforms[platform] = true;
        renderList(lastIntegrations);

        HDCommerce.ajax({ url: base + '/integrations/' + platform + '/sync', method: 'POST' })
            .done(function (resp) {
                toastr.success(resp.message || t('synced_success', 'Sincronizado.'));
                lastIntegrations = resp.integrations || lastIntegrations;
            })
            .fail(function (xhr) {
                toastr.error(HDCommerce.errorMessage(xhr, t('sync_failed', 'No se pudo sincronizar.')));
            })
            .always(function () {
                delete syncingPlatforms[platform];
                renderList(lastIntegrations);
            });
    });

    // ── Navegación entre vistas ────────────────────────────────────────────

    function setHeadIcon(icon, clickable) {
        $('#ciHeadIcon').html('<i class="fas ' + icon + '"></i>')
            .css('cursor', clickable ? 'pointer' : '');
    }

    function showMainView() {
        $('#ciGateView').hide();
        $('#ciLinkPanel').hide();
        $('#ciAuditView').hide();
        $('#ciMainView').show();
        $('#ciFootGate').hide();
        $('#ciFootSearch').hide();
        $('#ciFootAudit').hide();
        $('#ciFootMain').show();
        $('#ciModalTitle').text(t('integrations_title', 'Integraciones del cliente'));
        $('#ciAutoNote').hide();
        $('#ciSearchQ').val('');
        $('#ciSearchResults').html('<div class="bv-oc-empty"><i class="fas fa-magnifying-glass"></i><div>' + t('search_prompt', 'Introduce un email, teléfono o identificador para buscar al cliente.') + '</div></div>');
        setHeadIcon('fa-plug', false);
    }

    function auditIconClass(icon) {
        var allowed = { 'fas fa-plug': 1, 'fas fa-shield-halved': 1, 'fas fa-lock': 1 };
        return allowed[icon] ? icon : 'fas fa-plug';
    }

    function renderAuditList(entries) {
        if (!entries || !entries.length) {
            $('#ciAuditList').html('<div class="bv-oc-empty"><i class="fas fa-clock-rotate-left"></i><div class="title">' + t('no_audit_activity', 'Sin actividad registrada') + '</div></div>');
            return;
        }
        var html = entries.map(function (entry) {
            return '<div class="bv-intg-row">' +
                '<div class="ico"><i class="' + esc(auditIconClass(entry.icon)) + '"></i></div>' +
                '<div class="meta">' +
                    '<span class="name">' + esc(entry.summary) + '</span>' +
                    '<span class="det">' + esc(entry.agent) + ' · ' + esc(new Date(entry.created_at).toLocaleString()) + '</span>' +
                '</div>' +
            '</div>';
        }).join('');
        $('#ciAuditList').html(html);
    }

    function showAuditView() {
        var base = HDCommerce.base();
        if (!base) { return; }

        $('#ciMainView').hide();
        $('#ciAuditView').show();
        $('#ciFootMain').hide();
        $('#ciFootAudit').show();
        $('#ciModalTitle').text(t('audit_history_title', 'Historial de auditoría'));
        setHeadIcon('fa-arrow-left', true);
        $('#ciAuditList').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> ' + t('loading', 'Cargando…') + '</div>');

        HDCommerce.ajax({ url: base + '/integrations/audit-log', method: 'GET' })
            .done(function (resp) { renderAuditList(resp.entries); })
            .fail(function (xhr) {
                $('#ciAuditList').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">' + t('audit_load_failed_title', 'No se pudo cargar el historial') + '</div></div>');
                toastr.error(HDCommerce.errorMessage(xhr, t('audit_load_failed', 'No se pudo cargar el historial.')));
            });
    }

    $(document).on('click', '#ciOpenAuditLog', showAuditView);
    $(document).on('click', '#ciAuditBackBtn', showMainView);

    function showLinkPanel(auto) {
        $('#ciMainView').hide();
        $('#ciLinkPanel').show();
        $('#ciFootMain').hide();
        $('#ciFootSearch').show();
        $('#ciModalTitle').text(t('link_platform', 'Vincular plataforma'));
        $('#ciAutoNote').toggle(!!(auto && auto.auto));
        setHeadIcon('fa-arrow-left', true);

        if (auto && auto.auto) {
            $('#ciSearchQ').val(auto.query);
            autoSearchAllPlatforms(auto.query, auto.type);
        } else {
            $('#ciSearchQ').val('').focus();
            $('#ciSearchResults').html('<div class="bv-oc-empty"><i class="fas fa-magnifying-glass"></i><div>' + t('search_prompt', 'Introduce un email, teléfono o identificador para buscar al cliente.') + '</div></div>');
        }
    }

    $(document).on('click', '#ciHeadIcon', function () {
        if ($('#ciLinkPanel').is(':visible') || $('#ciAuditView').is(':visible')) { showMainView(); }
    });

    $(document).on('click', '#ciLink', function () { showLinkPanel(); });
    $(document).on('click', '.ci-link-open-btn', function () { showLinkPanel(); });
    $(document).on('click', '#ciBackBtn', showMainView);

    // ── Búsqueda unificada (todas las plataformas vinculables a la vez) ────

    function renderSearchResults(results, query, failedPlatforms) {
        failedPlatforms = failedPlatforms || [];

        // Nota de plataformas que fallaron: los resultados pueden estar
        // incompletos, y "sin resultados" con todo caído no es un "no existe".
        var failNote = failedPlatforms.length
            ? '<div class="minfo danger"><i class="fas fa-triangle-exclamation"></i>' +
              '<div>' + t('platforms_no_response_prefix', 'No respondieron: ') + '<strong>' + failedPlatforms.map(esc).join(', ') + '</strong>' + t('platforms_no_response_suffix', '. Los resultados pueden estar incompletos; inténtalo de nuevo en unos minutos.') + '</div></div>'
            : '';

        if (!results || !results.length) {
            if (failedPlatforms.length) {
                $('#ciSearchResults').html(
                    failNote +
                    '<div class="bv-oc-empty"><i class="fas fa-plug-circle-exclamation"></i>' +
                    '<div class="title">' + t('search_incomplete_title', 'No se pudo completar la búsqueda') + '</div>' +
                    '<div>' + t('search_incomplete_body', 'Las plataformas indicadas no respondieron. No significa que el cliente no exista.') + '</div>' +
                    '</div>'
                );
                return;
            }

            $('#ciSearchResults').html(
                '<div class="bv-oc-empty"><i class="fas fa-user-slash"></i>' +
                '<div class="title">' + t('no_results_title', 'Sin resultados') + '</div>' +
                '<div>' + t('no_results_body', 'No se encontró ningún cliente con <strong>:query</strong>.<br>Revisa el email, teléfono o identificador e inténtalo de nuevo.', { query: esc(query || '') }) + '</div>' +
                '</div>'
            );
            return;
        }
        var html = results.map(function (r) {
            var p = findLinkablePlatform(r.platform);
            var icon = (p && p.icon) || 'fas fa-plug';
            var color = p && p.color;
            var icoStyle = color ? ' style="color:' + esc(color) + '"' : '';
            var badgeStyle = color ? ' style="color:' + esc(color) + ';border-color:' + esc(color) + '"' : '';
            var platformBadge = p ? '<span class="badge bg-white border ms-1"' + badgeStyle + '>' + esc(p.label) + '</span>' : '';
            return '<div class="bv-intg-row">' +
                '<div class="ico"' + icoStyle + '><i class="' + esc(icon) + '"></i></div>' +
                '<div class="meta">' +
                    '<span class="name">' + esc(r.name || '—') + platformBadge + '</span>' +
                    '<span class="det">' + esc(r.email || r.meta || '') + '</span>' +
                '</div>' +
                '<button type="button" class="bv-intg-mini ci-link-result" ' +
                    'data-id="' + esc(r.id) + '" ' +
                    'data-platform="' + esc(r.platform) + '" ' +
                    'data-name="' + esc(r.name || '') + '">' +
                    t('link_button', 'Vincular') +
                '</button>' +
            '</div>';
        }).join('');
        $('#ciSearchResults').html(html);
    }

    function autoSearchAllPlatforms(query, type) {
        var base = HDCommerce.base();
        if (!base || !linkablePlatforms.length) { return; }

        $('#ciSearchResults').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> ' + t('searching_all_platforms', 'Buscando en todas las plataformas…') + '</div>');

        var searches = linkablePlatforms.map(function (p) {
            return HDCommerce.ajax({
                url: base + '/integrations/search',
                method: 'GET',
                data: { platform: p.platform, q: query, type: type },
            }).then(function (resp) {
                if (resp.platform_error) {
                    return { failed: p.label, results: [] };
                }

                return {
                    results: (resp.results || []).map(function (r) {
                        r.platform = p.platform;
                        return r;
                    }),
                };
            }, function () {
                return { failed: p.label, results: [] };
            });
        });

        Promise.all(searches).then(function (outcomes) {
            var results = [];
            var failedPlatforms = [];

            outcomes.forEach(function (o) {
                results = results.concat(o.results);
                if (o.failed) { failedPlatforms.push(o.failed); }
            });

            renderSearchResults(results, query, failedPlatforms);
        });
    }

    // Sin selector de plataforma/tipo: se infiere un tipo razonable a partir
    // del propio texto para no perder precision en la busqueda unificada.
    function guessSearchType(q) {
        if (q.indexOf('@') !== -1) { return 'email'; }
        if (/^[0-9\s+()-]+$/.test(q)) { return 'phone'; }
        return 'name';
    }

    function doSearch() {
        var q = $.trim($('#ciSearchQ').val());

        if (q.length < 2) {
            $('#ciSearchResults').html('<div class="bv-oc-empty"><i class="fas fa-magnifying-glass"></i><div>' + t('search_prompt', 'Introduce un email, teléfono o identificador para buscar al cliente.') + '</div></div>');
            return;
        }

        $('#ciAutoNote').hide();
        autoSearchAllPlatforms(q, guessSearchType(q));
    }

    // Busqueda en vivo (debounce 650ms), igual que el mockup — sin boton "Buscar".
    var searchDebounce = null;
    $(document).on('input', '#ciSearchQ', function () {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(doSearch, 650);
    });
    $(document).on('keydown', '#ciSearchQ', function (e) {
        if (e.key === 'Enter') { clearTimeout(searchDebounce); doSearch(); }
    });

    // ── Vincular resultado seleccionado ───────────────────────────────────

    $(document).on('click', '.ci-link-result', function () {
        var base = HDCommerce.base();
        if (!base) { return; }
        var $btn = $(this).prop('disabled', true);
        // .attr() (no .data()) a propósito: jQuery castea data-id="10584" (IDs
        // numéricos de PrestaShop/ERP) a Number al leerlo con .data(), y ese
        // Number viaja como JSON numérico y rompe la regla 'string' del backend
        // ("El identificador externo debe ser un string").
        var externalId = $btn.attr('data-id');
        var platform   = $btn.attr('data-platform');
        var name       = $btn.attr('data-name');

        HDCommerce.ajax({
            url: base + '/integrations/link',
            method: 'POST',
            data: JSON.stringify({ platform: platform, external_id: externalId }),
            contentType: 'application/json',
        })
            .done(function (resp) {
                toastr.success(t('linked_success', 'Vinculado correctamente: :name', { name: esc(name) }));
                lastIntegrations = resp.integrations || lastIntegrations;
                linkablePlatforms = resp.linkable_platforms || linkablePlatforms;
                showMainView();
                renderList(lastIntegrations, resp.last_activity);
            })
            .fail(function (xhr) {
                toastr.error(HDCommerce.errorMessage(xhr, t('link_failed', 'No se pudo vincular.')));
                $btn.prop('disabled', false);
            });
    });

    // ── Desvincular plataforma (confirmacion inline) ────────────────────────

    $(document).on('click', '.ci-unlink-btn', function () {
        confirmPlatform = $(this).data('platform');
        renderList(lastIntegrations);
    });

    $(document).on('click', '.ci-cancel-confirm-btn', function () {
        confirmPlatform = null;
        renderList(lastIntegrations);
    });

    $(document).on('input', '.ci-confirm-input', function () {
        var platform = $(this).data('platform');
        var expected = (findLinkablePlatform(platform) || {}).label
            || (lastIntegrations.filter(function (it) { return it.platform === platform; })[0] || {}).label
            || '';
        var ok = $.trim($(this).val()).toLowerCase() === String(expected).toLowerCase();
        $('.ci-confirm-unlink-btn[data-platform="' + platform + '"]').prop('disabled', !ok);
    });

    $(document).on('click', '.ci-confirm-unlink-btn', function () {
        var base = HDCommerce.base();
        if (!base) { return; }
        var $btn = $(this).prop('disabled', true);
        var platform = $btn.data('platform');
        var label = $btn.data('label');

        HDCommerce.ajax({
            url: base + '/integrations/unlink',
            method: 'POST',
            data: JSON.stringify({ platform: platform }),
            contentType: 'application/json',
        })
            .done(function (resp) {
                toastr.success(resp.message || t('unlinked_success', '«:label» desvinculado.', { label: esc(label) }));
                confirmPlatform = null;
                lastIntegrations = resp.integrations || lastIntegrations;
                linkablePlatforms = resp.linkable_platforms || linkablePlatforms;
                renderList(lastIntegrations, resp.last_activity);
            })
            .fail(function (xhr) {
                toastr.error(HDCommerce.errorMessage(xhr, t('unlink_failed', 'No se pudo desvincular.')));
                $btn.prop('disabled', false);
            });
    });

    // ── Inicialización al abrir el modal ──────────────────────────────────

    // Escucha el evento global del sistema bv-modal (dispara conversations.js al hacer click en data-bv-modal)
    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'customer-integrations') { return; }
        confirmPlatform = null;
        load();
    });

    window.openCustomerIntegrations = function () {
        if (!HDCommerce.customerId()) {
            if (window.toastr) { toastr.warning(t('select_conversation_warning', 'Selecciona una conversación con cliente.')); }
            return;
        }
        confirmPlatform = null;
        HDCommerce.open('customer-integrations');
        load();
    };
})();
