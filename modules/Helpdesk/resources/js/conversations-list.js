/**
 * Bandeja v4 - Helpdesk conversations interactions
 * jQuery + Bootstrap 5.3 (sin React/Babel)
 *
 * Parte de un split de public/vendor/helpdesk/conversations.js (7.138 líneas)
 * en varios archivos por responsabilidad. Ver conversations-core.js para los
 * helpers compartidos (openModal/closeModal/escapeHtml/refreshInboxList/...)
 * expuestos en window para que el resto de archivos los usen tal cual.
 */

(function ($) {
    'use strict';

    $(function () {
        // ─── Filter apply via event (from filter modal) ───────────────
        $(document).on('bv:filter:apply', function (e, params) {
            applyInboxFilters(params);
        });

        // Cerrar menús al hacer clic fuera
        $(document).on('click', function () {
            closeAllMenus();
        });

        // Evitar que clicks dentro del menú lo cierren
        $(document).on('click', '#bv-more-menu, #bv-attach-menu', function (e) {
            e.stopPropagation();
        });


        // ─── Channel filter pills (AJAX) ─────────────────────────────
        $(document).on('click', '.bv-chpill', function () {
            const $pill = $(this);
            $pill.siblings().removeClass('on');
            $pill.addClass('on');
            const channel = $pill.data('bv-channel');
            applyInboxFilters({ channel: channel === 'all' ? null : channel });
        });

        // ─── Filter chips (AJAX) ─────────────────────────────────────
        $(document).on('click', '.bv-chip[data-bv-filter]', function () {
            const $chip = $(this);
            const filter = $chip.data('bv-filter');
            const isActive = $chip.hasClass('on');
            $chip.toggleClass('on', !isActive);
            applyInboxFilters({ [filter]: isActive ? null : '1' });
        });

        // ─── Nav items (AJAX) ────────────────────────────────────────
        $(document).on('click', '.bv-nav-item[href]', function (e) {
            const href = $(this).attr('href');
            if (!href || href === '#') return;
            const url = new URL(href, window.location.origin);
            if (url.pathname !== '/panel/helpdesk/conversations') return;
            e.preventDefault();
            const next = {};
            url.searchParams.forEach((v, k) => { next[k] = v; });
            $('.bv-nav-item').removeClass('on');
            $(this).addClass('on');
            refreshInboxList(next, { force: true });
        });

        // ─── Apertura SPA de la conversación (sin recargar la página) ────
        // Carga el pane (thread + right-panel) por AJAX y lo intercambia en su
        // sitio. Si algo falla cae al comportamiento anterior (full reload) para
        // no dejar nunca la bandeja en un estado roto.
        let bvPaneToken = 0;

        function loadConversationPane(convId, fallbackUrl, opts) {
            convId = parseInt(convId, 10);
            opts = opts || {};
            const pushHistory = opts.push !== false;

            if (!convId) {
                if (fallbackUrl) { window.location.href = fallbackUrl; }
                return;
            }

            const token = ++bvPaneToken;
            const paneUrl = '/panel/helpdesk/conversations/' + convId + '/pane';
            $('.bv-thread').addClass('bv-pane-loading');

            $.ajax({
                url: paneUrl,
                method: 'GET',
                dataType: 'html',
                timeout: 15000,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).done(function (html) {
                // Un click posterior ya disparó otra carga: descartar esta respuesta.
                if (token !== bvPaneToken) { return; }
                try {
                    // parseHTML elimina los <script>, así que la inyección nunca
                    // re-ejecuta handlers ni duplica listeners de document.
                    const $resp = $('<div>').append($.parseHTML(html, document, false));
                    const $newThread = $resp.find('.bv-thread').first();
                    const $newRight = $resp.find('.bv-right').first();
                    const $newOverlay = $resp.find('#hdCannedOverlay').first();

                    if (!$newThread.length || !$newRight.length) {
                        throw new Error('pane markup incompleto');
                    }

                    $('.bv-thread').replaceWith($newThread);

                    // El overlay de respuestas rápidas es hermano del thread.
                    $('#hdCannedOverlay').remove();
                    if ($newOverlay.length) { $newThread.after($newOverlay); }

                    $('.bv-right').replaceWith($newRight);

                    // Re-init de los tabs del panel derecho (tooltips/visibilidad/rtab).
                    if (typeof initRightPanelTabs === 'function') { initRightPanelTabs(); }

                    // Re-suscripción Reverb + typing + borrador + mark-read del hilo.
                    if (typeof window.bvBindConversation === 'function') {
                        window.bvBindConversation(convId);
                    }

                    if (pushHistory) {
                        try {
                            const u = new URL(window.location.href);
                            u.searchParams.set('selected', convId);
                            history.pushState({ bvSelected: convId }, '', u.toString());
                        } catch (_) {}
                    }

                    scrollThreadToBottom(false);

                    // Permite a otros scripts re-inicializar slots inyectados.
                    const ev = { conversationId: convId };
                    document.dispatchEvent(new CustomEvent('pane:loaded', { detail: ev }));
                    window.dispatchEvent(new CustomEvent('pane:loaded', { detail: ev }));
                } catch (err) {
                    console.error('[Inbox] pane swap failed, full reload fallback:', err);
                    if (fallbackUrl) { window.location.href = fallbackUrl; }
                }
            }).fail(function (xhr) {
                if (token !== bvPaneToken) { return; }
                console.warn('[Inbox] pane load failed, full reload fallback:', xhr && xhr.status);
                if (fallbackUrl) { window.location.href = fallbackUrl; }
                else { window.location.reload(); }
            }).always(function () {
                $('.bv-thread').removeClass('bv-pane-loading');
            });
        }
        window.bvLoadConversationPane = loadConversationPane;

        // ─── Click en item de la lista ───────────────────────────────
        $(document).on('click', '.bv-conv', function (e) {
            // Ignorar clicks en checkbox o botones de acciones rápidas
            if ($(e.target).closest('input[type="checkbox"], .bv-conv-hactions').length) return;

            const $conv = $(this);
            const url = $conv.data('bv-conv-url');
            const convId = $conv.data('bv-conv-id');

            // Solo uno activo en toda la lista (los items están agrupados).
            $('.bv-conv').removeClass('on');
            $conv.addClass('on').removeClass('unread');

            // Auto-switch to thread tab on mobile/tablet
            if (window.innerWidth < 1024) {
                $('.conversations').attr('data-bv-mobile-tab', 'thread');
                $('.bv-mobile-tab').removeClass('on');
                $('[data-bv-mobile-tab="thread"]').addClass('on');
            }

            if (convId) {
                loadConversationPane(convId, url);
            } else if (url) {
                window.location.href = url;
            }
        });

        // ─── Atrás / adelante del navegador ──────────────────────────
        window.addEventListener('popstate', function () {
            const sel = parseInt(new URLSearchParams(window.location.search).get('selected') || '0', 10);
            // When going back to the base state (no ?selected) fall back to the
            // first conversation, mirroring the server default, so URL and content stay in sync.
            const targetId = sel || parseInt($('.bv-conv').first().data('bv-conv-id') || '0', 10);
            if (!targetId) { return; }
            $('.bv-conv').removeClass('on');
            $('.bv-conv[data-bv-conv-id="' + targetId + '"]').addClass('on');
            const url = sel
                ? '/panel/helpdesk/conversations?selected=' + targetId
                : '/panel/helpdesk/conversations';
            loadConversationPane(targetId, url, { push: false });
        });

        // ─── Mobile bottom tabs ───────────────────────────────────────
        $(document).on('click', '.bv-mobile-tab', function () {
            const tab = $(this).data('bv-mobile-tab');
            $('.conversations').attr('data-bv-mobile-tab', tab);
            $('.bv-mobile-tab').removeClass('on');
            $(this).addClass('on');
        });

        // ─── Nav (vistas guardadas) ──────────────────────────────────
        // Active state is set server-side; let anchor navigate normally.
        // No preventDefault — real hrefs are generated by Blade.

        // ─── Búsqueda en lista (live) ────────────────────────────────
        let searchTimeout = null;
        $('#bv-search-input').on('input', function () {
            const term = $(this).val().toLowerCase().trim();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                $('.bv-conv').each(function () {
                    const name = $(this).find('.name').text().toLowerCase();
                    const preview = $(this).find('.preview').text().toLowerCase();
                    const match = !term || name.includes(term) || preview.includes(term);
                    $(this).toggle(match);
                });
            }, 200);
        });

        // ─── Status / Priority / Filter option clicks ───────────────
        $(document).on('click', '.bv-opt', function () {
            $(this).siblings().removeClass('on');
            $(this).addClass('on');
        });


        // ─── Bulk select (checkbox) ──────────────────────────────────
        $(document).on('change', '[data-bv-bulk-select]', function () {
            $(this).closest('.bv-conv').toggleClass('selected', this.checked);
        });

        // ─── Bulk bar: contador de seleccionadas, acciones en bloque y macros ───
        // Extraído de bulk-bar.blade.php (@push('scripts') @once ... @endpush @endonce).
        (function () {
            var bulkUrl = '/panel/helpdesk/conversations/bulk';
            var csrf    = $('meta[name="csrf-token"]').attr('content');

            function getSelectedIds() {
                var ids = [];
                $('[data-bv-bulk-select]:checked').each(function () {
                    var id = $(this).closest('.bv-conv').data('bv-conv-id');
                    if (id) { ids.push(id); }
                });
                return ids;
            }

            function updateBulkBar() {
                var count = $('[data-bv-bulk-select]:checked').length;
                $('#bv-bulk-count').text(count);
                if (count > 0) {
                    $('#bv-bulk-bar').removeClass('bv-hidden');
                } else {
                    $('#bv-bulk-bar').addClass('bv-hidden');
                }
            }

            function executeBulkAction(action, ids, payload) {
                $.ajax({
                    url: bulkUrl,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ action: action, ids: ids, payload: payload || {} }),
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                }).done(function (resp) {
                    ids.forEach(function (id) {
                        $('.bv-conv[data-bv-conv-id="' + id + '"]').fadeOut(200, function () { $(this).remove(); });
                    });
                    $('#bv-bulk-bar').addClass('bv-hidden');
                }).fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo ejecutar la acción';
                    toastr.error(msg);
                });
            }

            // Mostrar/ocultar barra al cambiar selección
            $(document).on('change', '[data-bv-bulk-select]', function () {
                updateBulkBar();
            });

            // También actualizar cuando se agrega/quita clase selected por JS externo
            $(document).on('bv:selection-changed', function () {
                updateBulkBar();
            });

            // Deseleccionar todo
            $(document).on('click', '#bv-bulk-deselect', function () {
                $('.bv-conv.selected').removeClass('selected');
                $('[data-bv-bulk-select]').prop('checked', false);
                $('#bv-bulk-bar').addClass('bv-hidden');
            });

            // Ejecutar acción bulk
            $(document).on('click', '[data-bv-bulk-action]', function () {
                var action = $(this).data('bv-bulk-action');
                var ids    = getSelectedIds();
                if (!ids.length) { return; }

                executeBulkAction(action, ids, {});
            });

            // ─── Asignar en bloque ──────────────────────────────────────────────
            var agentsForBulkLoaded = false;

            function renderAssignMenu(agents) {
                var $menu = $('#bv-bulk-assign-menu');
                $menu.empty();
                if (!agents || !agents.length) {
                    $menu.append('<div class="dropdown-item-text text-muted small px-3 py-2">No hay agentes disponibles.</div>');
                    return;
                }
                agents.forEach(function (a) {
                    var $item = $('<button type="button" class="dropdown-item d-flex align-items-center gap-2"></button>')
                        .attr('data-bv-agent-id', a.id);
                    $item.append($('<span></span>').text(a.name || a.email));
                    $menu.append($item);
                });
            }

            function loadAgentsForBulk() {
                if (agentsForBulkLoaded) { return; }
                $.get('/panel/helpdesk/api/agents-autocomplete', { include_self: 1 })
                    .done(function (resp) {
                        agentsForBulkLoaded = true;
                        renderAssignMenu((resp && resp.agents) || []);
                    })
                    .fail(function () {
                        $('#bv-bulk-assign-menu').html('<div class="dropdown-item-text text-dark small px-3 py-2">No se pudieron cargar los agentes.</div>');
                    });
            }

            // Cargar agentes la primera vez que se abre el dropdown
            $(document).on('click', '#bv-bulk-assign-toggle', loadAgentsForBulk);

            // Asignar el agente elegido a las conversaciones seleccionadas
            $(document).on('click', '#bv-bulk-assign-menu [data-bv-agent-id]', function () {
                var agentId = $(this).data('bv-agent-id');
                var ids     = getSelectedIds();
                if (!agentId || !ids.length) { return; }
                executeBulkAction('assign', ids, { assignee_id: agentId });
            });

            // ─── Aplicar macro en bloque ──────────────────────────────────────────
            var macroPickerUrl = '/panel/helpdesk/conversations/macros-picker';
            var bulkMacroUrl   = '/panel/helpdesk/conversations/bulk-macro';
            var macrosLoaded   = false;

            function renderMacroMenu(macros) {
                var $menu = $('#bv-bulk-macro-menu');
                $menu.empty();
                if (!macros || !macros.length) {
                    $menu.append('<div class="dropdown-item-text text-muted small px-3 py-2">No hay macros disponibles.</div>');
                    return;
                }
                $menu.append('<div class="dropdown-header">Más usados</div>');
                macros.forEach(function (m) {
                    var badge = m.usados
                        ? '<span class="badge bg-secondary ms-2">' + m.usageCount + '</span>'
                        : '';
                    var $item = $('<button type="button" class="dropdown-item d-flex align-items-center justify-content-between gap-2"></button>')
                        .attr('data-bv-macro-id', m.id);
                    $item.append($('<span></span>').text(m.name));
                    $item.append($(badge || '<span></span>'));
                    $menu.append($item);
                });
            }

            function loadMacros() {
                if (macrosLoaded) { return; }
                $.get(macroPickerUrl, { sort: 'used' })
                    .done(function (resp) {
                        macrosLoaded = true;
                        renderMacroMenu(resp && resp.macros ? resp.macros : []);
                    })
                    .fail(function () {
                        $('#bv-bulk-macro-menu').html('<div class="dropdown-item-text text-dark small px-3 py-2">No se pudieron cargar los macros.</div>');
                    });
            }

            // Cargar macros la primera vez que se abre el dropdown
            $(document).on('click', '#bv-bulk-macro-toggle', loadMacros);

            // Aplicar el macro elegido a las conversaciones seleccionadas
            $(document).on('click', '#bv-bulk-macro-menu [data-bv-macro-id]', function () {
                var macroId = $(this).data('bv-macro-id');
                var ids     = getSelectedIds();
                if (!macroId || !ids.length) { return; }

                $.ajax({
                    url: bulkMacroUrl,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ macro_id: macroId, conversation_ids: ids }),
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                }).done(function (resp) {
                    if (resp && resp.success) {
                        toastr.success(resp.message || 'Macro aplicado');
                    } else {
                        toastr.warning((resp && resp.message) || 'No se aplicó el macro a ninguna conversación.');
                    }
                    $('#bv-bulk-bar').addClass('bv-hidden');
                    $('.bv-conv.selected').removeClass('selected');
                    $('[data-bv-bulk-select]').prop('checked', false);
                    // Reuse the inbox list-refresh pipeline already wired in index.blade.php
                    window.dispatchEvent(new CustomEvent('inbox:incoming-message'));
                }).fail(function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function (field, msgs) {
                            toastr.error(msgs[0]);
                        });
                        return;
                    }
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo aplicar el macro';
                    toastr.error(msg);
                });
            });
        })();

        // ─── Quick action: pin conversation ──────────────────────────
        $(document).on('click', '[data-bv-action="pin"]', function (e) {
            e.stopPropagation();
            const $btn = $(this);
            const url = $btn.data('bv-url');
            const $conv = $btn.closest('.bv-conv');
            if (!url || $btn.prop('disabled')) return;
            $btn.prop('disabled', true);
            $.ajax({
                url: url, method: 'POST', dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
            }).done(function (resp) {
                const pinned = resp?.pinned ?? !$conv.hasClass('is-pinned');
                $conv.toggleClass('is-pinned', pinned);
                $btn.find('i').toggleClass('text-warning', pinned);
                if (window.toastr) toastr.success(pinned ? 'Conversación fijada.' : 'Conversación desfijada.');
            }).fail(function (xhr) {
                if (window.toastr) toastr.error(xhr?.responseJSON?.message || 'No se pudo fijar la conversación');
            }).always(function () { $btn.prop('disabled', false); });
        });

        // ─── Quick action: mute conversation ─────────────────────────
        $(document).on('click', '[data-bv-action="mute"]', function (e) {
            e.stopPropagation();
            const $btn = $(this);
            const url = $btn.data('bv-url');
            const $conv = $btn.closest('.bv-conv');
            if (!url || $btn.prop('disabled')) return;
            $btn.prop('disabled', true);
            $.ajax({
                url: url, method: 'POST', dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
            }).done(function (resp) {
                const muted = resp?.muted ?? !$conv.hasClass('is-muted');
                $conv.toggleClass('is-muted', muted);
                $btn.find('i').toggleClass('far', !muted).toggleClass('fas', muted);
                if (window.toastr) toastr.success(muted ? 'Notificaciones silenciadas.' : 'Notificaciones activadas.');
            }).fail(function (xhr) {
                if (window.toastr) toastr.error(xhr?.responseJSON?.message || 'No se pudo silenciar la conversación');
            }).always(function () { $btn.prop('disabled', false); });
        });

        // ─── Quick action: archive conversation ──────────────────────
        $(document).on('click', '[data-bv-action="archive"]', function (e) {
            e.stopPropagation();
            const $btn = $(this);
            const url = $btn.data('bv-url');
            const $conv = $btn.closest('.bv-conv');

            if (!url || $btn.prop('disabled')) return;

            $btn.prop('disabled', true);

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            })
                .done(function (resp) {
                    $conv.fadeOut(300, function () { $(this).remove(); });
                })
                .fail(function (xhr) {
                    const msg = xhr?.responseJSON?.message || 'No se pudo archivar la conversación';
                    if (window.toastr) {
                        toastr.error(msg);
                    }
                    $btn.prop('disabled', false);
                });
        });

        // Restaurar conversación desde la papelera (view=deleted)
        $(document).on('click', '[data-bv-action="restore"]', function (e) {
            e.stopPropagation();
            const $btn = $(this);
            const url = $btn.data('bv-url');
            const $conv = $btn.closest('.bv-conv');

            if (!url || $btn.prop('disabled')) return;

            $btn.prop('disabled', true);

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            })
                .done(function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Conversación restaurada');
                    $conv.fadeOut(300, function () { $(this).remove(); });
                })
                .fail(function (xhr) {
                    const msg = xhr?.responseJSON?.message || 'No se pudo restaurar la conversación';
                    if (window.toastr) toastr.error(msg);
                    $btn.prop('disabled', false);
                });
        });


        // ─── Drag & drop conversaciones a equipos ────────────────────
        $(document).on('dragstart', '.bv-conv', function (e) {
            const convId = $(this).data('bv-conv-id');
            if (!convId) return;
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/plain', String(convId));
            $(this).addClass('bv-dragging');
        });

        $(document).on('dragend', '.bv-conv', function () {
            $(this).removeClass('bv-dragging');
            $('.bv-droptarget-active').removeClass('bv-droptarget-active');
        });

        $(document).on('dragover', '[data-bv-droptarget="team"]', function (e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            $(this).addClass('bv-droptarget-active');
        });

        $(document).on('dragleave', '[data-bv-droptarget="team"]', function () {
            $(this).removeClass('bv-droptarget-active');
        });

        $(document).on('drop', '[data-bv-droptarget="team"]', function (e) {
            e.preventDefault();
            const convId = e.originalEvent.dataTransfer.getData('text/plain');
            const teamId = $(this).data('bv-team-id');
            const $target = $(this);
            $target.removeClass('bv-droptarget-active');
            if (!convId || !teamId) return;

            $.ajax({
                url: '/panel/helpdesk/conversations/' + convId,
                method: 'POST',
                dataType: 'json',
                data: { group_id: teamId },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'PUT',
                },
            })
                .done(function () {
                    $(`.bv-conv[data-bv-conv-id="${convId}"]`).fadeOut(300, function () { $(this).remove(); });
                })
                .fail(function () {
                    if (window.toastr) toastr.error('No se pudo mover la conversación');
                });
        });

        // Guarda el modo/idiomas de traducción en sesión (por pestaña, no
        // persiste en servidor — ver referencia en el panel "Traducir") y
        // traduce las burbujas ya visibles si el modo lo pide. Extraído del
        // handler del panel para que el modal "Detectar idioma" (sugerencia
        // automática, ver detect-lang.blade.php) pueda activar la traducción
        // sin duplicar esta lógica ni depender de un endpoint que no existe.

        // ═══════════════════════════════════════════════════════════════
        // FEATURE: Smart views — save/delete desde el inbox
        // ═══════════════════════════════════════════════════════════════

        // Guardar vista: abre modal con nombre, guarda filtros actuales
        $(document).on('click', '#bv-save-view-btn', function () {
            var $modal = $('#bv-save-view-modal');
            if ($modal.length) {
                $modal.find('#bv-save-view-name').val('');
                $modal.css('display', 'flex');
                setTimeout(function () { $modal.find('#bv-save-view-name').focus(); }, 50);
            }
        });

        $(document).on('click', '#bv-save-view-cancel', function () {
            $('#bv-save-view-modal').css('display', 'none');
        });

        $(document).on('click', '#bv-save-view-confirm', function () {
            var name = $('#bv-save-view-name').val().trim();
            if (!name) {
                if (window.toastr) toastr.warning('Ingresa un nombre para la vista');
                return;
            }
            var filters = Object.fromEntries(new URLSearchParams(window.location.search));
            $.ajax({
                url: '/panel/helpdesk/views',
                method: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
                data: JSON.stringify({ name: name, filters: filters }),
            }).done(function (resp) {
                $('#bv-save-view-modal').css('display', 'none');
                // Añadir dinamicamente al nav
                if (resp.view) {
                    var $sec = $('.bv-nav-section[data-section="saved-views"]');
                    if ($sec.length) {
                        var href = '/panel/helpdesk/conversations?' + new URLSearchParams(filters).toString();
                        $sec.append(
                            '<a href="' + href + '" class="bv-nav-item bv-nav-saved-view" data-view-id="' + resp.view.id + '">' +
                            '<i class="fas fa-star" style="font-size:9px;margin-right:2px;opacity:.7"></i>' +
                            '<span class="bv-nav-view-name">' + $('<span>').text(resp.view.name).html() + '</span>' +
                            '<button type="button" class="bv-nav-view-del ms-auto" data-view-id="' + resp.view.id + '" title="Eliminar vista" aria-label="Eliminar vista" style="background:none;border:none;cursor:pointer;color:var(--bv-text-muted);padding:0 2px;line-height:1">×</button>' +
                            '</a>'
                        );
                    }
                }
            }).fail(function (xhr) {
                var msg = xhr.responseJSON?.message || 'No se pudo guardar la vista';
                if (window.toastr) toastr.error(msg);
            });
        });

        // Eliminar vista guardada
        $(document).on('click', '.bv-nav-view-del', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var viewId = $(this).data('view-id');
            var $item = $(this).closest('.bv-nav-saved-view');
            if (!viewId) return;
            $.ajax({
                url: '/panel/helpdesk/views/' + viewId,
                method: 'DELETE',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            }).done(function () {
                $item.remove();
            }).fail(function () {
                if (window.toastr) toastr.error('No se pudo eliminar la vista');
            });
        });


    });
})(jQuery);

// ═══════════════════════════════════════════════════════════════════
// Listener global de la bandeja: nuevas conversaciones / nuevos mensajes.
// Extraído de inbox/index.blade.php. Mantiene la lista (.bv-list / .bv-conv)
// y los contadores del sidebar sincronizados en tiempo real vía Echo; vive
// aquí (no en conversations-core.js) porque su trabajo es, en esencia, la
// lista de conversaciones. window.BvSidebarInboxIds es el dato server-side
// (bandejas visibles del agente) que index.blade.php expone justo antes de
// cargar estos scripts — ver el <script> de datos en ese archivo.
// ═══════════════════════════════════════════════════════════════════
(function () {
    // UI-05: live-region oculta para anunciar eventos dinámicos a lectores de pantalla.
    window.bvAnnounce = window.bvAnnounce || function (message) {
        var $live = $('#bv-sr-live');
        if (!$live.length) {
            $live = $('<div id="bv-sr-live" class="visually-hidden" role="status" aria-live="polite" aria-atomic="true"></div>');
            $('body').append($live);
        }
        // Vaciar y re-escribir fuerza el re-anuncio aunque el texto se repita.
        $live.text('');
        window.setTimeout(function () { $live.text(message); }, 50);
    };

    function setupInboxListener() {
        if (typeof window.Echo === 'undefined') {
            console.warn('[Inbox] Echo not ready, retrying in 500ms');
            setTimeout(setupInboxListener, 500);
            return;
        }

        const myId = parseInt(document.querySelector('meta[name="user-id"]')?.content || '0', 10);

        // SEC-01: el canal global 'helpdesk.inbox' se retiró (transportaba el
        // payload completo de cada mensaje, incluidas notas internas, a
        // cualquier agente con el permiso grueso 'helpdesk.conversations.view',
        // sin aislar por bandeja). Ahora hay un canal privado por bandeja
        // ('helpdesk.inbox.{inboxId}'), autorizado contra AgentInboxCapacity
        // (ver routes/channels.php). Nos suscribimos a las mismas bandejas que
        // ya ve el agente en el sidebar — $sidebarInboxes viene del controller
        // ya filtrado por ConversationsController::getUserInboxIds(), el mismo
        // criterio que usa el canal para autorizar.
        const inboxIds = window.BvSidebarInboxIds || [];
        const inboxChannels = inboxIds.map(function (id) {
            return window.Echo.private('helpdesk.inbox.' + id);
        });
        console.log('[Inbox] Subscribing to', inboxChannels.length, 'private helpdesk.inbox.{id} channel(s)');

        function refreshConversationList(done) {
            const $list = $('.bv-list').first();
            if (!$list.length) {
                console.warn('[Inbox] .bv-list not found, cannot refresh');
                if (typeof done === 'function') done();
                return;
            }
            const url = '/panel/helpdesk/conversations/list';
            const currentParams = new URLSearchParams(window.location.search);
            $.get(url, Object.fromEntries(currentParams)).done(function (resp) {
                if (resp && typeof resp.html === 'string') {
                    $list.replaceWith(resp.html);
                }
                if (resp && resp.counts) {
                    ['total', 'unread', 'mine', 'urgent'].forEach(function (k) {
                        if (resp.counts[k] !== undefined) {
                            $('[data-counter="' + k + '"]').text(resp.counts[k]);
                        }
                    });
                }
            }).fail(function (xhr) {
                console.error('[Inbox] List refresh failed:', xhr.status);
            }).always(function () {
                if (typeof done === 'function') done();
            });
        }

        let refreshTimer = null;
        let refreshInflight = false;
        let pendingRefresh = false;
        function scheduleRefresh() {
            if (refreshInflight) {
                pendingRefresh = true;
                return;
            }
            clearTimeout(refreshTimer);
            refreshTimer = setTimeout(function () {
                refreshInflight = true;
                pendingRefresh = false;
                refreshConversationList(function () {
                    refreshInflight = false;
                    if (pendingRefresh) scheduleRefresh();
                });
            }, 50);
        }

        // Same listener wired on every inbox channel the agent can see. Sidebar
        // update only — thread bubble rendering is handled by the
        // per-conversation listener below.
        function handleInboxItemCreated(e) {
            const msg = e.message || {};
            // Los mensajes de actividad (etiqueta, asignación, estado…) no deben
            // pisar el preview del último mensaje real en la lista lateral.
            if (msg.type === 'activity') return;
            if (msg.user_id && parseInt(msg.user_id, 10) === myId) return;

            const conv = e.conversation || {};
            const isNew = !!e.is_new_conversation;
            const isViewing = parseInt(new URLSearchParams(window.location.search).get('selected') || '0', 10) === parseInt(conv.id, 10);

            const $existing = $('.bv-conv[data-bv-conv-id="' + conv.id + '"]');
            if (!isNew && $existing.length) {
                patchConvItem($existing, conv, msg, isViewing);
            } else {
                scheduleRefresh();
            }

            // Push notification when message is incoming and agent is not viewing it
            const isIncoming = !msg.user_id && (msg.author_id || msg.is_incoming);
            if (isIncoming && (document.visibilityState === 'hidden' || !isViewing)) {
                const customerName = conv.customer_name || conv.subject || 'Nuevo mensaje';
                const preview = (msg.body || '').slice(0, 100);
                const avatar = conv.customer_avatar || null;
                if (typeof window.showInboxPushNotif === 'function') {
                    window.showInboxPushNotif(conv.id, customerName, preview, avatar);
                }
            }
        }

        inboxChannels.forEach(function (channel) {
            channel.listen('.item.created', handleInboxItemCreated);
        });

        // Patch a conversation item in-place: update last message preview,
        // bump unread badge, and move to top — all without an AJAX call.
        function patchConvItem($item, conv, msg, isViewing) {
            const preview = (msg.body || '').slice(0, 100);
            if (preview) {
                $item.find('.row2 .preview').first().text(preview);
            }

            $item.find('.row1 .time').first().text('ahora');

            if (!isViewing && msg.is_incoming) {
                const wasUnread = $item.hasClass('unread');
                $item.addClass('unread');
                const $badge = $item.find('.bv-ucount');
                if ($badge.length) {
                    const n = parseInt($badge.text(), 10) || 0;
                    $badge.text(n >= 9 ? '9+' : n + 1);
                } else {
                    $item.find('.row2 .meta').first().append('<span class="bv-ucount">1</span>');
                }

                // patchConvItem existe justamente para no hacer un AJAX por
                // cada mensaje entrante (este canal recibe eventos de todas
                // las conversaciones de las bandejas del agente). El
                // contador "No leídos" del sidebar se actualiza aquí mismo,
                // en vez de esperar al próximo refreshConversationList.
                if (!wasUnread) {
                    const $counter = $('[data-counter="unread"]');
                    const cur = parseInt($counter.text(), 10) || 0;
                    $counter.text(cur + 1);
                }
            }

            // Move to top of its group
            const $group = $item.closest('.bv-conv-group, .bv-conv-stack, .bv-conv-list');
            if ($group.length && $item.prev().length) {
                $item.detach();
                $group.find('.bv-conv-group-head, .bv-conv-stack-head').first().after($item);
                if (!$item.parent().is($group)) {
                    $group.prepend($item);
                }
            }

            $item.addClass('bv-conv-flash');
            setTimeout(() => $item.removeClass('bv-conv-flash'), 1200);
        }

        // Also refresh when an item.created arrives on any open conversation channel,
        // because counters (last_message_at, unread badge) change.
        window.addEventListener('inbox:incoming-message', scheduleRefresh);

        window.__hdInboxListenerReady = true;
        console.log('[Inbox] Listener registered');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupInboxListener);
    } else {
        setupInboxListener();
    }
})();
