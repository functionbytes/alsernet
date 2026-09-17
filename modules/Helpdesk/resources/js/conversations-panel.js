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
        // ─── Tabs panel derecho ──────────────────────────────────────
        // Ocultar tabs que solo tienen estado vacío (sin contenido real)
        function syncRightTabVisibility() {
            document.querySelectorAll('.bv-right-tab-content[data-bv-tab-content]').forEach(function (content) {
                const tabName = content.getAttribute('data-bv-tab-content');
                const btn = document.querySelector(`.bv-right-tab[data-bv-tab="${tabName}"]`);
                if (!btn) return;
                const meaningful = Array.from(content.children).filter(function (el) {
                    return !el.classList.contains('bv-tab-loading');
                });
                const onlyEmpty = meaningful.length === 1 && meaningful[0].classList.contains('bv-tab-empty');
                const wasHidden = btn.style.display === 'none';
                btn.style.display = onlyEmpty ? 'none' : '';
                // Si este tab estaba activo y lo ocultamos, activar el primero visible
                if (onlyEmpty && btn.classList.contains('on')) {
                    const $first = $('.bv-right-tab:visible').first();
                    if ($first.length) { $first[0].click(); }
                }
            });
            // Ocultar separador si todo el grupo PS o ERP está oculto
            const $sep = $('.rsp-tabs-sep');
            const psHidden  = $('.bv-right-tab[data-bv-tab^="ps-"]').toArray().every(b => b.style.display === 'none');
            const erpHidden = $('.bv-right-tab[data-bv-tab^="erp-"]').toArray().every(b => b.style.display === 'none');
            $sep.toggle(!(psHidden && erpHidden));
        }
        // Expuesta en window: los módulos satélite (erp-inbox.js, que pinta las
        // pestañas ERP tras su fetch diferido) necesitan re-evaluar qué botones
        // mostrar una vez llega contenido real, y viven en un <script> aparte sin
        // acceso a este scope privado.
        window.bvSyncRightTabVisibility = syncRightTabVisibility;

        // Helpers rtab URL


        // Usar capture phase nativo para evitar que Bootstrap tooltip intercepte el click
        document.addEventListener('click', function (e) {
            const $tab = $(e.target).closest('.bv-right-tab');
            if (!$tab.length) { return; }
            const target = $tab.data('bv-tab');
            try { const tip = bootstrap.Tooltip.getInstance($tab[0]); if (tip) { tip.hide(); } } catch (_) {}
            $tab.siblings().removeClass('on');
            $tab.addClass('on');
            $('.bv-right-tab-content').addClass('bv-tab-hidden').hide();
            $(`[data-bv-tab-content="${target}"]`).removeClass('bv-tab-hidden').show();
            setRtabUrl(target);
        }, true);

        // Inicializa/re-inicializa los tabs del panel derecho: tooltips, visibilidad
        // y restauración del tab activo (rtab). Se ejecuta al cargar la página y de
        // nuevo tras cada swap de pane (SPA) porque ese markup se inyecta sin scripts.
        function initRightPanelTabs() {
            document.querySelectorAll('.bv-right-tab[data-bs-toggle="tooltip"], .bv-right .r-tag[data-bs-toggle="tooltip"]').forEach(function (el) {
                try {
                    if (!bootstrap.Tooltip.getInstance(el)) {
                        new bootstrap.Tooltip(el, { trigger: 'hover' });
                    }
                } catch (_) {}
            });
            syncRightTabVisibility();
            const rtab = getRtabFromUrl();
            if (rtab) {
                const $target = $(`[data-bv-tab="${rtab}"]`);
                if ($target.length) { $target[0].click(); }
            }
            // Si ningún tab quedó activo (p.ej. "General" deshabilitado en
            // Settings > Funcionalidades), activar el primero visible.
            if (!$('.bv-right-tab.on:visible').length) {
                const $firstTab = $('.bv-right-tab:visible').first();
                if ($firstTab.length) { $firstTab[0].click(); }
            }
        }
        window.bvInitRightPanelTabs = initRightPanelTabs;
        initRightPanelTabs();


        // ─── Panel cliente: menú "Más" ────────────────────────────────
        $(document).on('click', '.rsp-more-toggle', function (e) {
            e.stopPropagation();
            const $menu = $(this).siblings('.rsp-more-menu');
            const wasOpen = $menu.hasClass('on');
            closeAllMenus();
            if (!wasOpen) {
                $menu.addClass('on');
                $(this).attr('aria-expanded', 'true');
            }
        });


        // ─── Acciones del menú "Más" ──────────────────────────────────
        $(document).on('click', '#bv-btn-send-csat', function () {
            var url = $(this).data('csat-url');
            if (!url) { if (window.toastr) toastr.warning('No hay conversación activa'); return; }
            $.ajax({
                url: url, method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
            }).done(function () { if (window.toastr) toastr.success('Encuesta CSAT enviada.'); })
              .fail(function (xhr) { if (window.toastr) toastr.error(xhr?.responseJSON?.message || 'Error al enviar CSAT'); });
            closeAllMenus();
        });

        $(document).on('click', '#bv-btn-mark-spam', function () {
            var url = $(this).data('spam-url');
            if (!url) { if (window.toastr) toastr.warning('No hay conversación activa'); return; }
            closeAllMenus();
            openModal('mark-spam');
        });

        $(document).on('click', '#bv-btn-block-contact', function () {
            var url = $(this).data('block-url');
            if (!url) { if (window.toastr) toastr.warning('No hay conversación activa'); return; }
            closeAllMenus();
            openModal('block-contact');
        });

        $(document).on('click', '#bv-btn-delete-conv', function () {
            var url = $(this).data('delete-url');
            if (!url) { if (window.toastr) toastr.warning('No hay conversación activa'); return; }
            closeAllMenus();
            openModal('delete-conv');
        });


        // ─── Selección de modo en panel traducción ────────────────────
        $(document).on('click', '.bv-tp-mode', function () {
            $(this).siblings('.bv-tp-mode').removeClass('on');
            $(this).addClass('on');
        });


        // ─── Status / Priority modal: apply button ───────────────────
        // Nota: estado y prioridad tienen sus propios handlers específicos
        // ([data-bv-apply="status"|"priority"] más abajo); se excluyen aquí para
        // no dispararse dos veces sobre el mismo botón (el genérico busca .bv-opt.on,
        // que esos modales no usan → falso "Selecciona una opción primero").
        $(document).on('click', '[data-bv-apply]:not([data-bv-apply="status"]):not([data-bv-apply="priority"])', function () {
            const type = $(this).data('bv-apply');
            const $modal = $(this).closest('.bv-modal');
            const $selected = $modal.find('.bv-opt.on');

            if (!$selected.length) {
                if (window.toastr) {
                    toastr.warning('Selecciona una opción primero');
                }
                return;
            }

            const value = $selected.data('bv-value');
            const label = $selected.data('bv-label');
            const color = $selected.data('bv-color') || 'muted';
            const $composer = $('.bv-composer[data-bv-conversation-id]');
            const convId = $composer.data('bv-conversation-id');
            const updateUrl = $composer.data('bv-update-url');

            if (!convId || !updateUrl) {
                closeModal($modal);
                return;
            }

            const payload = type === 'status'
                ? { action: 'set_status', status_id: value }
                : { action: 'set_priority', priority: value };

            $.ajax({
                url: updateUrl,
                method: 'POST',
                dataType: 'json',
                data: payload,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'PUT',
                },
            })
                .done(function (resp) {
                    updateThreadPill(type, label, color, resp);
                    closeModal($modal);
                })
                .fail(function (xhr) {
                    const msg = xhr?.responseJSON?.message || 'No se pudo actualizar';
                    if (window.toastr) {
                        toastr.error(msg);
                    }
                });
        });

        function updateThreadPill(type, label, color) {
            const $pills = $('.bv-th-pill');
            const $target = type === 'status' ? $pills.eq(0) : $pills.eq(1);
            if (!$target.length) return;
            $target.find('.dot').attr('class', 'dot bv-dot-' + color);
            $target.contents().filter(function () {
                return this.nodeType === 3;
            }).each(function () {
                if ($(this).text().trim()) {
                    this.textContent = ' ' + label + ' ';
                }
            });
        }


        // ─── Helper: get current conversation update URL ──────────────
        function getConvUrls() {
            const $composer = $('.bv-composer[data-bv-conversation-id]');
            return {
                convId: $composer.data('bv-conversation-id'),
                updateUrl: $composer.data('bv-update-url'),
                sendUrl: $composer.data('bv-send-url'),
                closeUrl: $composer.data('bv-close-url'),
            };
        }

        // ─── Close conversation modal: submit ─────────────────────────
        $(document).on('click', '[data-bv-modal-name="close-conv"] #bv-close-apply', function () {
            const $modal = $(this).closest('.bv-modal');
            const $btn = $(this);
            const resolution = $modal.find('.bv-modal-ta').val().trim();
            const skipCsat = $modal.find('#close-csat').is(':checked') ? 0 : 1;
            const reason = $modal.find('input[name="close_reason"]:checked').val() || 'resolved';
            const urls = getConvUrls();

            if (!urls.updateUrl) {
                toastr && toastr.warning('No hay conversación activa');
                return;
            }

            // Derive the close URL from the update URL (same base path + /close)
            // update URL: /panel/helpdesk/conversations/{id}  →  close: /panel/helpdesk/conversations/{id}/close
            const closeUrl = urls.updateUrl.replace(/\/?$/, '/close');

            $btn.prop('disabled', true);

            $.ajax({
                url: closeUrl,
                method: 'POST',
                dataType: 'json',
                data: { resolution: resolution, reason: reason, skip_csat: skipCsat },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            })
                .done(function () {
                    // Full reload back to the inbox with no `selected` param: the
                    // conversation we just closed is no longer open, so we don't stay
                    // on its pane nor auto-pick another one — Blade's own empty state
                    // ("Selecciona una conversación", thread.blade.php/right-panel.blade.php)
                    // takes over, and the sidebar list re-renders server-side without it.
                    // There's no AJAX endpoint for that empty state (`.../pane` requires
                    // a real conversation id), so a full navigation is the correct way
                    // to reach it — same pattern already used when the last conversation
                    // in a filtered view gets closed.
                    const listFilters = readInboxFiltersFromUrl();
                    delete listFilters.selected;
                    const qs = $.param(listFilters);
                    window.location.href = '/panel/helpdesk/conversations' + (qs ? '?' + qs : '');
                })
                .fail(function (xhr) {
                    const msg = xhr?.responseJSON?.message || 'No se pudo cerrar la conversación';
                    toastr && toastr.error(msg);
                })
                .always(function () {
                    $btn.prop('disabled', false);
                });
        });

        // ─── Reopen conversation ──────────────────────────────────────
        $(document).on('click', '#bv-btn-reopen', function () {
            const $btn = $(this);
            const url = $btn.data('reopen-url') || getConvUrls().updateUrl.replace(/\/?$/, '/reopen');

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
                    // Swap reopen button → close button in the header
                    $btn.replaceWith(
                        '<button class="bv-th-action" data-bv-modal="close-conv" title="Cerrar conversación">' +
                        '<i class="fas fa-check"></i></button>'
                    );

                    // Solo el botón se actualizaba: el "Estado" del panel derecho
                    // seguía mostrando "Cerrado"/"Resuelto" y los contadores del
                    // sidebar (Cerradas, Todas...) no bajaban hasta recargar la
                    // página a mano. Reutiliza el mismo refresco que ya usan
                    // tags/status para mantener ambos al día sin full reload.
                    const convId = $('.bv-composer').data('bv-conversation-id');
                    if (convId && typeof window.bvLoadConversationPane === 'function') {
                        window.bvLoadConversationPane(convId, null, { push: false });
                    }
                    // Sin filtros, refreshInboxList() navega a "/conversations" a
                    // secas y pierde el filtro activo (ej. "?status=closed" donde
                    // se reabrió la conversación) — hay que pasarle los de la URL.
                    if (typeof window.refreshInboxList === 'function' && typeof window.readInboxFiltersFromUrl === 'function') {
                        window.refreshInboxList(window.readInboxFiltersFromUrl());
                    }
                })
                .fail(function (xhr) {
                    const msg = xhr?.responseJSON?.message || 'No se pudo reabrir la conversación';
                    toastr && toastr.error(msg);
                })
                .always(function () {
                    $btn.prop('disabled', false);
                });
        });

        // ─── Tags modal: save ─────────────────────────────────────────
        $(document).on('click', '[data-bv-modal-name="tags"] #bv-tags-apply', function () {
            const $modal = $(this).closest('.bv-modal');
            const $btn = $(this);
            const urls = getConvUrls();

            if (!urls.updateUrl) {
                toastr && toastr.warning('No hay conversación activa');
                return;
            }

            // Collect numeric tag IDs from selected opts (skip non-numeric like 'urgente', 'envio')
            const tagIds = [];
            $modal.find('.bv-opt.on').each(function () {
                const id = parseInt($(this).data('tag-id'), 10);
                if (!isNaN(id)) {
                    tagIds.push(id);
                }
            });

            $btn.prop('disabled', true);

            $.ajax({
                url: urls.updateUrl,
                method: 'POST',
                dataType: 'json',
                traditional: true,
                data: { tag_ids: tagIds },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'PUT',
                },
            })
                .done(function (resp) {
                    closeModal($modal);
                })
                .fail(function (xhr) {
                    const msg = xhr?.responseJSON?.message || 'No se pudieron guardar las etiquetas';
                    toastr && toastr.error(msg);
                })
                .always(function () {
                    $btn.prop('disabled', false);
                });
        });


        // ─── Filtro de archivos del right-panel ──────────────────────
        $(document).on('click', '.bv-files-filter', function () {
            const $btn = $(this);
            const filter = $btn.data('bv-files-filter');
            $btn.siblings('.bv-files-filter').removeClass('on');
            $btn.addClass('on');
            clearFilesSelection();
            const $cards = $('.bv-files-grid .bv-file-card');
            if (filter === 'all') {
                $cards.show();
            } else {
                $cards.each(function () {
                    $(this).toggle($(this).data('bv-file-type') === filter);
                });
            }
        });

        // ─── Ordenamiento de archivos ────────────────────────────────
        $(document).on('change', '#bv-files-sort', function () {
            const mode = $(this).val();
            const $grid = $('#bv-files-grid');
            const $cards = $grid.children('.bv-file-card').get();
            $cards.sort(function (a, b) {
                const $a = $(a), $b = $(b);
                if (mode === 'recent')    return ($b.data('bv-file-ts') || 0) - ($a.data('bv-file-ts') || 0);
                if (mode === 'oldest')    return ($a.data('bv-file-ts') || 0) - ($b.data('bv-file-ts') || 0);
                if (mode === 'size-desc') return ($b.data('bv-file-size') || 0) - ($a.data('bv-file-size') || 0);
                if (mode === 'size-asc')  return ($a.data('bv-file-size') || 0) - ($b.data('bv-file-size') || 0);
                if (mode === 'name')      return String($a.data('bv-file-name') || '').localeCompare(String($b.data('bv-file-name') || ''));
                return 0;
            });
            $grid.append($cards);
        });

        // ─── Toggle vista grid/lista ─────────────────────────────────
        $(document).on('click', '#bv-files-view-toggle, .bv-files-vt', function () {
            var $btn = $(this);
            var next;
            if ($btn.hasClass('bv-files-vt')) {
                next = $btn.data('bv-view');
                $('.bv-files-vt').removeClass('on');
                $btn.addClass('on');
            } else {
                var current = $('#bv-files-grid').attr('data-view') || 'grid';
                next = current === 'grid' ? 'list' : 'grid';
                $btn.find('i').attr('class', next === 'grid' ? 'fas fa-list' : 'fas fa-grip');
            }
            $('#bv-files-grid').attr('data-view', next);
        });

        // ─── Filtros de tickets ──────────────────────────────────────
        $(document).on('click', '[data-bv-tickets-filter]', function () {
            const $btn = $(this);
            const filter = $btn.data('bv-tickets-filter');
            $btn.siblings('[data-bv-tickets-filter]').removeClass('on');
            $btn.addClass('on');
            $('[data-bv-ticket-tags]').each(function () {
                const tags = String($(this).data('bv-ticket-tags') || '').split(/\s+/);
                $(this).toggle(tags.includes(filter));
            });
        });

        // ─── Click en tarjeta del panel de Archivos → lightbox ──────
        $(document).on('click', '.bv-file-card', function (e) {
            if ($(e.target).closest('.bv-file-select').length) return;
            e.preventDefault();
            openMediaPanelLightbox($(this));
        });

        // ─── Selección de archivos ────────────────────────────────────
        function updateFilesDownloadBtn() {
            const count = $('.bv-files-grid .bv-file-card.bv-selected').length;
            const $footer = $('#bv-files-footer');
            if (count > 0) {
                $('#bv-files-dl-btn').text('Descargar selección (' + count + ')');
                $footer.show();
            } else {
                $footer.hide();
            }
        }

        $(document).on('change', '.bv-file-cb', function () {
            $(this).closest('.bv-file-card').toggleClass('bv-selected', this.checked);
            updateFilesDownloadBtn();
        });

        function normalizeMediaUrl(url) {
            if (!url) return url;
            try {
                const u = new URL(url);
                if (u.origin !== window.location.origin && u.pathname.startsWith('/storage/')) {
                    return window.location.origin + u.pathname + u.search;
                }
            } catch (e) {}
            return url;
        }

        function collectMediaPanelFiles($clicked) {
            const list = [];
            const clickedRaw = $clicked.data('bv-file-url');
            const clickedUrl = normalizeMediaUrl(clickedRaw);
            let startIdx = 0;
            $('.bv-files-grid .bv-file-card:visible').each(function () {
                const $c = $(this);
                const rawSrc = $c.data('bv-file-url');
                if (!rawSrc) return;
                const src = normalizeMediaUrl(rawSrc);
                const name = $c.data('bv-file-name') || rawSrc.split('/').pop();
                const ext = (name.split('.').pop() || '').toLowerCase();
                if (src === clickedUrl) startIdx = list.length;
                list.push({
                    src,
                    name,
                    ext,
                    size: $c.data('bv-file-size') || 0,
                    author: $c.find('.author').text().trim(),
                    time: $c.find('.date').text().trim(),
                    type: $c.data('bv-file-type') || 'document',
                });
            });
            return { list, startIdx };
        }

        function openMediaPanelLightbox($card) {
            const { list, startIdx } = collectMediaPanelFiles($card);
            if (!list.length) return;
            lightbox.list = list;
            lightbox.idx = startIdx;
            renderLightbox();
            $('[data-bv-modal-name="file-preview"]').addClass('on');
            $('body').css('overflow', 'hidden');
        }

        // ─── Files tab footer: descarga y cierre ─────────────────────
        $(document).on('click', '#bv-files-dl-btn', function () {
            const $selected = $('.bv-files-grid .bv-file-card.bv-selected');
            const $toDownload = $selected.length
                ? $selected
                : $('.bv-files-grid .bv-file-card:visible');
            if (!$toDownload.length) return;
            $toDownload.each(function (i) {
                const rawUrl = $(this).data('bv-file-url') || $(this).attr('href');
                const name   = $(this).data('bv-file-name') || '';
                const url    = normalizeMediaUrl(rawUrl);
                if (!url) return;
                setTimeout(function () { downloadBlob(url, name); }, i * 300);
            });
            clearFilesSelection();
        });

        function clearFilesSelection() {
            $('.bv-files-grid .bv-file-card').removeClass('bv-selected');
            $('.bv-files-grid .bv-file-cb').prop('checked', false);
            updateFilesDownloadBtn();
        }

        $(document).on('click', '#bv-files-close-btn', function () {
            clearFilesSelection();
            $('.bv-files-filter').removeClass('on').filter('[data-bv-files-filter="all"]').addClass('on');
            $('.bv-files-grid .bv-file-card').show();
        });

        // ─── Acciones de la conversación (modales) ───────────────────
        function getCurrentConversationId() {
            return $('.bv-composer').data('bv-conversation-id') || null;
        }

        function getCurrentUserId() {
            return parseInt($('meta[name="user-id"]').attr('content') || '0', 10);
        }

        // Contadores "Todas"/"Mías"/"Urgentes"/"Sin leer" del sidebar solo
        // cuentan conversaciones abiertas y no archivadas (Conversation::
        // scopeDefaultViewVisible() en el backend). Cambiar estado/prioridad/
        // agente desde el panel derecho no dispara un evento de broadcast (es
        // el propio agente quien lo hace), así que se actualizan aquí mismo,
        // en vez de esperar al próximo refreshConversationList().
        function bumpSidebarCounter(key, delta) {
            if (!delta) { return; }
            const $counter = $('[data-counter="' + key + '"]');
            if (!$counter.length) { return; }
            const cur = parseInt($counter.text(), 10) || 0;
            $counter.text(Math.max(0, cur + delta));
        }

        function ajaxConversationUpdate(payload, successMsg) {
            const convId = getCurrentConversationId();
            if (!convId) {
                if (window.toastr) toastr.error('No hay conversación seleccionada');
                return Promise.reject('no-conv');
            }
            return $.ajax({
                url: '/panel/helpdesk/conversations/' + convId,
                method: 'POST',
                dataType: 'json',
                data: payload,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-HTTP-Method-Override': 'PUT',
                },
            }).done(function (resp) {
                if (successMsg && window.toastr) toastr.success(successMsg);
            }).fail(function (xhr) {
                const msg = xhr?.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors)[0]?.[0]
                    : (xhr?.responseJSON?.message || 'No se pudo actualizar');
                if (window.toastr) toastr.error(msg);
            });
        }

        function closeNamedModal(name) {
            const $modal = $(`[data-bv-modal-name="${name}"]`);
            $modal.removeClass('on');
            if ($('.bv-modal.on').length === 0) $('body').css('overflow', '');
        }

        // Selección visual genérica en modales con .bv-opt-list o .reason-list
        $(document).on('click', '.bv-opt-list .bv-opt[data-bv-value], .reason-list .reason[data-bv-value]', function () {
            $(this).closest('.bv-opt-list, .reason-list').find('.bv-opt, .reason').removeClass('on');
            $(this).addClass('on');
        });

        // Cambio de estado
        // Sincroniza el botón del panel derecho (.bv-right) tras cambiar estado/prioridad.
        function setRightPanelPill(type, label, color) {
            const $btn = $('.bv-right .r-tag-btn[data-bv-modal="' + type + '"]');
            if (!$btn.length) { return; }
            let replaced = false;
            $btn.contents().each(function () {
                if (this.nodeType === 3 && this.textContent.trim() && !replaced) {
                    this.textContent = label || '';
                    replaced = true;
                }
            });
            if (type === 'status') {
                $btn.find('.dot').css('background', color || '#6c757d');
            } else if (type === 'priority') {
                $btn.removeClass('r-tag-muted r-tag-info r-tag-warning r-tag-danger r-tag-success');
                if (color) { $btn.addClass('r-tag-' + color); }
            }
        }

        $(document).on('click', '[data-bv-apply="status"]', function () {
            const $sel = $('[data-bv-modal-name="status"] [data-bv-value].on').first();
            const id = $sel.data('bv-value');
            const label = $sel.data('bv-label');
            const color = $sel.data('bv-color');
            const isOpenNow = $sel.data('bv-is-open') == 1;
            if (!id) {
                if (window.toastr) toastr.warning('Selecciona un estado');
                return;
            }
            const $statusPill = $('.r-tag-btn[data-bv-modal="status"]').first();
            const wasOpen = $statusPill.attr('data-bv-is-open') == 1;
            ajaxConversationUpdate({ status_id: id }, 'Estado actualizado').done(() => {
                closeNamedModal('status');
                const $pill = $('.bv-th-pill').filter(function () { return $(this).attr('data-bv-modal') === 'status'; });
                $pill.find('.dot').attr('class', 'dot').css('background', color || '#6c757d');
                const txt = ' ' + (label || '') + ' ';
                $pill.contents().filter(function () { return this.nodeType === 3; }).first().replaceWith(txt);
                setRightPanelPill('status', label, color);
                $statusPill.attr('data-bv-is-open', isOpenNow ? '1' : '0');

                if (isOpenNow !== wasOpen) {
                    const delta = isOpenNow ? 1 : -1;
                    const isUrgent = $('.r-tag-btn[data-bv-modal="priority"]').first().attr('data-bv-value') === 'urgent';
                    const isMine = String($('.r-tag-btn[data-bv-modal="assign"]').first().attr('data-bv-assignee-id') || '') === String(getCurrentUserId());
                    bumpSidebarCounter('total', delta);
                    if (isUrgent) { bumpSidebarCounter('urgent', delta); }
                    if (isMine) { bumpSidebarCounter('mine', delta); }
                }
            });
        });

        // Cambio de prioridad
        $(document).on('click', '[data-bv-apply="priority"]', function () {
            const $sel = $('[data-bv-modal-name="priority"] .prio-opt.on').first();
            const value = $sel.data('bv-value');
            const label = $sel.data('bv-label');
            const color = $sel.data('bv-color');
            if (!value) {
                if (window.toastr) toastr.warning('Selecciona una prioridad');
                return;
            }
            const $priorityPill = $('.r-tag-btn[data-bv-modal="priority"]').first();
            const wasUrgent = $priorityPill.attr('data-bv-value') === 'urgent';
            ajaxConversationUpdate({ priority: value }, 'Prioridad actualizada').done(() => {
                closeNamedModal('priority');
                const $pill = $('.bv-th-pill').filter(function () { return $(this).attr('data-bv-modal') === 'priority'; }).first();
                $pill.find('.dot').attr('class', 'dot bv-dot-' + (color || 'muted'));
                const txt = ' ' + (label || '') + ' ';
                $pill.contents().filter(function () { return this.nodeType === 3; }).first().replaceWith(txt);
                $pill.attr('data-bv-value', value);
                setRightPanelPill('priority', label, color);
                $priorityPill.attr('data-bv-value', value);

                const isUrgentNow = value === 'urgent';
                if (isUrgentNow !== wasUrgent) {
                    const isOpen = $('.r-tag-btn[data-bv-modal="status"]').first().attr('data-bv-is-open') == 1;
                    if (isOpen) { bumpSidebarCounter('urgent', isUrgentNow ? 1 : -1); }
                }
            });
        });

        // Asignar agente / equipo
        $(document).on('click', '#assign-btn-apply', function () {
            const $modal = $('[data-bv-modal-name="assign"]');
            const $selected = $modal.find('.asgn-item.on');
            if (!$selected.length) {
                if (window.toastr) toastr.warning('Selecciona un agente o equipo');
                return;
            }
            let payload, msg;
            const teamId = $selected.data('team-id');
            const isTeamAssignment = teamId !== undefined && teamId !== '';
            if (isTeamAssignment) {
                payload = { group_id: teamId };
                msg = 'Conversación movida al equipo';
            } else {
                const agentId = $selected.data('agent-id');
                payload = { assignee_id: agentId === '' ? null : agentId };
                msg = agentId === '' ? 'Asignación eliminada' : 'Conversación asignada';
            }
            const $assignPill = $('.r-tag-btn[data-bv-modal="assign"]').first();
            const wasMine = String($assignPill.attr('data-bv-assignee-id') || '') === String(getCurrentUserId());
            ajaxConversationUpdate(payload, msg).done(() => {
                closeNamedModal('assign');

                if (!isTeamAssignment) {
                    const agentId = $selected.data('agent-id');
                    const label = agentId === '' ? 'Sin asignar' : $selected.find('.asgn-t').text().trim();
                    $assignPill
                        .attr('data-bv-assignee-id', agentId === '' ? '' : agentId)
                        .toggleClass('r-tag-muted', agentId === '')
                        .contents().filter(function () { return this.nodeType === 3; }).first().replaceWith(' ' + label + ' ');

                    const isMineNow = String(agentId || '') === String(getCurrentUserId());
                    if (isMineNow !== wasMine) {
                        const isOpen = $('.r-tag-btn[data-bv-modal="status"]').first().attr('data-bv-is-open') == 1;
                        if (isOpen) { bumpSidebarCounter('mine', isMineNow ? 1 : -1); }
                    }
                }
            });
        });

        // Mover a equipo
        $(document).on('click', '#move-team-btn', function () {
            const id = $('[data-bv-modal-name="move-to-team"] .bv-opt.on').data('group-id');
            if (!id) { if (window.toastr) toastr.warning('Selecciona un equipo destino'); return; }
            ajaxConversationUpdate({ group_id: id }, 'Movida al equipo').done(() => closeNamedModal('move-to-team'));
        });

        // Reenviar conversación (último mensaje)
        $(document).on('click', '#bv-btn-forward-conv', function () {
            const $last = $('.bv-th-inner .bv-bubble[data-bv-item-id]').last();
            if (!$last.length) { if (window.toastr) toastr.warning('No hay mensajes para reenviar'); return; }
            const itemId = $last.data('bv-item-id');
            const body = $last.find('.bv-bubble-body').text().trim();
            openMessageForwardModal($last, itemId, body, body.slice(0, 120));
        });

        // Etiquetas
        $(document).on('click', '#bv-tags-apply', function () {
            const ids = $('[data-bv-modal-name="tags"] .bv-tag-item.on, [data-bv-modal-name="tags"] [data-tag-id].on')
                .map(function () { return $(this).data('tag-id') || $(this).data('bv-value'); })
                .get()
                .filter(Boolean);
            ajaxConversationUpdate({ tag_ids: ids }, 'Etiquetas actualizadas').done(() => closeNamedModal('tags'));
        });

        // ═══ Panel derecho (right-panel.blade.php) — extraído de los <script>
        // inline del partial; conservado tal cual, ver notas de cada bloque. ═══

        // ─── Páginas visitadas: paginación "Mostrar más" + refresco pestaña Tecnología ───
        (function () {
            var btn = document.getElementById('bv-pages-show-more');
            if (!btn) return;

            btn.addEventListener('click', function () {
                var shown  = parseInt(btn.dataset.shown, 10);
                var total  = parseInt(btn.dataset.total, 10);
                var reveal = Math.min(100, total - shown);
                var items  = document.querySelectorAll('#bv-pages-timeline .bv-page-collapsed');
                var revealed = 0;

                for (var i = 0; i < items.length && revealed < reveal; i++) {
                    items[i].classList.remove('bv-page-collapsed');
                    revealed++;
                }

                shown += revealed;
                btn.dataset.shown = shown;
                var remaining = total - shown;

                if (remaining <= 0) {
                    btn.remove();
                } else {
                    var next = Math.min(100, remaining);
                    document.getElementById('bv-pages-show-more-count').textContent = next;
                    btn.querySelector('.bv-pages-show-more-total').textContent = '(' + remaining + ' restantes)';
                }

                // Hide day labels whose items are all still collapsed
                document.querySelectorAll('#bv-pages-timeline .bv-pages-day-label').forEach(function (label) {
                    var next = label.nextElementSibling;
                    var hasVisible = false;
                    while (next && !next.classList.contains('bv-pages-day-label') && !next.classList.contains('bv-pages-show-more')) {
                        if (!next.classList.contains('bv-page-collapsed')) { hasVisible = true; break; }
                        next = next.nextElementSibling;
                    }
                    label.style.display = hasVisible ? '' : 'none';
                });
            });

            // Initial pass: hide day labels that have no visible items (all collapsed)
            document.querySelectorAll('#bv-pages-timeline .bv-pages-day-label').forEach(function (label) {
                var next = label.nextElementSibling;
                var hasVisible = false;
                while (next && !next.classList.contains('bv-pages-day-label') && !next.classList.contains('bv-pages-show-more')) {
                    if (!next.classList.contains('bv-page-collapsed')) { hasVisible = true; break; }
                    next = next.nextElementSibling;
                }
                if (!hasVisible) label.style.display = 'none';
            });

        }());

        (function () {
            // Refresh button — re-fetches the full Technology tab content (device info,
            // current page and visited pages) without a full page reload.
            // Uses event delegation on the aside so the handler survives innerHTML replacement.
            var bvAside = document.querySelector('.bv-right');
            if (!bvAside) return;

            bvAside.addEventListener('click', async function (e) {
                var btn = e.target.closest('#bv-pages-refresh');
                if (!btn) return;

                var icon = btn.querySelector('i');
                btn.disabled = true;
                if (icon) icon.classList.add('fa-spin');
                try {
                    var res = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    var html = await res.text();
                    var doc = new DOMParser().parseFromString(html, 'text/html');

                    var freshTab = doc.querySelector('[data-bv-tab-content="technology"]');
                    var oldTab = document.querySelector('[data-bv-tab-content="technology"]');
                    if (freshTab && oldTab) {
                        oldTab.innerHTML = freshTab.innerHTML;
                    }

                    if (typeof window.toastr !== 'undefined') {
                        window.toastr.success('Datos de sesión actualizados');
                    }
                } catch (e) {
                    if (typeof window.toastr !== 'undefined') {
                        window.toastr.error('No se pudo refrescar');
                    }
                } finally {
                    btn.disabled = false;
                    if (icon) icon.classList.remove('fa-spin');
                }
            });
        }());

        // ─── Pestaña Tecnología: eco en vivo de widget.session.updated ───
        // Gate original: @if($rpShowTechnologyTab && helpdesk_feature_enabled('tab_technology') && $rpConvo)
        (function () {
            var techTabEl = document.querySelector('[data-bv-tab-content="technology"]');
            if (!techTabEl) { return; }
            var convId = techTabEl.dataset.convId;
            if (!convId) { return; }

            // Resolve Echo asynchronously (it may load after this script runs).
            function waitForEcho(cb) {
                if (typeof window.Echo !== 'undefined' && window.Echo) {
                    return cb();
                }
                var tries = 0;
                var iv = setInterval(function () {
                    tries++;
                    if (typeof window.Echo !== 'undefined' && window.Echo) {
                        clearInterval(iv);
                        cb();
                    } else if (tries > 60) {
                        clearInterval(iv);
                    }
                }, 250);
            }

            waitForEcho(function () {
                window.Echo.private('helpdesk.conversation.' + convId)
                    .listen('.widget.session.updated', function (data) {
                        // Update "Página actual" section in real time.
                        var section = document.querySelector('.bv-current-page-section');

                        var url = data.current_url;
                        if (!url) return;

                        // Parse host + path from the new URL.
                        var parsed;
                        try { parsed = new URL(url); } catch (e) { return; }
                        var host = parsed.hostname;
                        var path = parsed.pathname + (parsed.search || '');

                        if (section) {
                            // Update host label.
                            var hostEl = section.querySelector('.bv-current-page-host');
                            if (hostEl) { hostEl.lastChild.textContent = host; }

                            // Update link: href + visible text.
                            var linkEl = section.querySelector('.bv-current-page-path');
                            if (linkEl) {
                                linkEl.href = url;
                                linkEl.title = url;
                                var textNode = linkEl.firstChild;
                                var truncated = path.length > 80 ? path.slice(0, 77) + '...' : path;
                                if (textNode && textNode.nodeType === Node.TEXT_NODE) {
                                    textNode.textContent = truncated + ' ';
                                }
                            }

                            // Switch pulse indicator to "Viendo ahora".
                            var idle = section.querySelector('.bv-current-page-idle');
                            if (idle) {
                                idle.className = 'bv-current-page-pulse';
                                idle.title = 'Visitante activo ahora';
                                idle.innerHTML = '<span class="bv-pulse-dot"></span>Viendo ahora';
                            }
                        } else {
                            // Section doesn't exist yet (no current_url on initial load) — do a
                            // lightweight fetch-replace so the full section renders server-side.
                            var techTab = document.querySelector('[data-bv-tab-content="technology"]');
                            if (!techTab) return;

                            fetch(window.location.href, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                                credentials: 'same-origin',
                            }).then(function (res) {
                                return res.ok ? res.text() : Promise.reject(res.status);
                            }).then(function (html) {
                                var doc = new DOMParser().parseFromString(html, 'text/html');
                                var fresh = doc.querySelector('[data-bv-tab-content="technology"]');
                                if (fresh) { techTab.innerHTML = fresh.innerHTML; }
                            }).catch(function () {});
                        }
                    });
            });
        }());

        // ─── Pestaña Pantalla (assist): live view (rrweb) + WebRTC screen share ───
        // Gate original: @if($rpShowAssistTab && helpdesk_feature_enabled('tab_assist') && $rpConvo)
        (function () {
            var assistTabEl = document.querySelector('[data-bv-tab-content="assist"]');
            if (!assistTabEl) { return; }
            var conversationId = assistTabEl.dataset.conversationId;
            if (!conversationId) { return; }
            var liveViewEnabled = assistTabEl.dataset.enableLiveView === '1';
            var screenShareEnabled = assistTabEl.dataset.enableScreenShare === '1';

            // window.Echo can load asynchronously after this script runs.
            // Poll until it appears (cap at 15s) so we don't miss the bind window.
            function waitForEcho(cb) {
                if (typeof window.Echo !== 'undefined' && window.Echo) {
                    return cb();
                }
                var tries = 0;
                var iv = setInterval(function () {
                    tries++;
                    if (typeof window.Echo !== 'undefined' && window.Echo) {
                        clearInterval(iv);
                        cb();
                    } else if (tries > 60) {
                        clearInterval(iv);
                        console.warn('[hd-assist] Echo never initialized — live view disabled.');
                    }
                }, 250);
            }

            waitForEcho(function () {

            // ── Live view (rrweb player) ─────────────────────────────────
            if (liveViewEnabled) {
                var playerEl = document.getElementById('hd-liveview-player-' + conversationId);
                var statusEl = document.getElementById('hd-liveview-status-' + conversationId);
                var emptyEl = playerEl ? playerEl.querySelector('.hd-liveview-empty') : null;
                var player = null;
                var bufferedEvents = [];

                function setStatus(text, cls) {
                    if (statusEl) {
                        statusEl.textContent = text;
                        statusEl.className = 'bv-assist-status badge ' + cls;
                    }
                }

                function ensurePlayer() {
                    if (player || !playerEl) {
                        return Promise.resolve(player);
                    }
                    // Load rrweb-player from CDN (no bundler step required for the
                    // admin panel — the script is small enough to fetch on demand
                    // and only loads when an agent opens the Pantalla tab).
                    var cssUrl = 'https://cdn.jsdelivr.net/npm/rrweb-player@1.0.0-alpha.4/dist/style.css';
                    var jsUrl = 'https://cdn.jsdelivr.net/npm/rrweb-player@1.0.0-alpha.4/dist/index.mjs';
                    if (! document.querySelector('link[data-hd="rrweb-player"]')) {
                        var link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = cssUrl;
                        link.dataset.hd = 'rrweb-player';
                        document.head.appendChild(link);
                    }
                    return import(jsUrl).then(function (mod) {
                        if (emptyEl) emptyEl.remove();
                        var Player = mod.default || mod.Player || mod;
                        player = new Player({
                            target: playerEl,
                            props: {
                                events: bufferedEvents.slice(),
                                autoPlay: true,
                                showController: false,
                                liveMode: true,
                            },
                        });
                        bufferedEvents = [];
                        return player;
                    }).catch(function (e) {
                        console.warn('[hd-assist] rrweb-player load failed', e);
                        if (playerEl) {
                            playerEl.innerHTML = '<div class="text-warning small p-3 text-center">No se pudo cargar el reproductor (rrweb-player no disponible).</div>';
                        }
                    });
                }

                // Fetch backlog first — rrweb requires the initial Meta + FullSnapshot
                // events to render anything. Live mode alone shows a blank frame for
                // any agent that joins after the visitor started recording.
                var historyUrl = assistTabEl.dataset.historyUrl;
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                fetch(historyUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                })
                    .then(function (r) { return r.ok ? r.json() : { events: [] }; })
                    .then(function (data) {
                        var historyEvents = data.events || [];
                        if (historyEvents.length > 0) {
                            bufferedEvents = bufferedEvents.concat(historyEvents);
                            setStatus('Reproduciendo', 'bg-info');
                            ensurePlayer();
                        }
                    })
                    .catch(function () { /* silent — live mode still works without backlog */ });

                try {
                    window.Echo.private('livestream.conversation.' + conversationId)
                        .listen('.livestream.batch', function (data) {
                            setStatus('En vivo', 'bg-success');
                            if (player) {
                                (data.events || []).forEach(function (e) { player.addEvent(e); });
                            } else {
                                bufferedEvents = bufferedEvents.concat(data.events || []);
                                ensurePlayer();
                            }
                        });
                } catch (e) {
                    setStatus('Sin conexión', 'bg-warning');
                }
            }

            // ── WebRTC screen share (agent answers visitor offer) ─────────
            if (screenShareEnabled) {
                var videoEl = document.getElementById('hd-webrtc-video-' + conversationId);
                var emptyWebrtc = document.getElementById('hd-webrtc-empty-' + conversationId);
                var endBtn = document.getElementById('hd-webrtc-end-' + conversationId);
                var peer = null;

                var STUN = [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' },
                ];

                function postJson(url, data) {
                    var token = document.querySelector('meta[name="csrf-token"]');
                    return fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token ? token.content : '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(data),
                        credentials: 'same-origin',
                    });
                }

                function tearDown() {
                    try { peer && peer.close(); } catch (e) {}
                    peer = null;
                    if (videoEl) {
                        videoEl.srcObject = null;
                        videoEl.removeAttribute('data-streaming');
                    }
                    if (emptyWebrtc) emptyWebrtc.style.display = '';
                }

                try {
                    window.Echo.private('webrtc.conversation.' + conversationId)
                        .listen('.webrtc.offer', async function (data) {
                            if (!data || !data.payload || !data.payload.sdp) return;
                            if (peer) tearDown();

                            peer = new RTCPeerConnection({ iceServers: STUN });

                            peer.ontrack = function (event) {
                                if (videoEl && event.streams && event.streams[0]) {
                                    videoEl.srcObject = event.streams[0];
                                    videoEl.setAttribute('data-streaming', '1');
                                    if (emptyWebrtc) emptyWebrtc.style.display = 'none';
                                }
                            };

                            peer.onicecandidate = function (event) {
                                if (event.candidate) {
                                    postJson(
                                        assistTabEl.dataset.iceUrl,
                                        { candidate: event.candidate.toJSON() }
                                    );
                                }
                            };

                            await peer.setRemoteDescription({ type: 'offer', sdp: data.payload.sdp });
                            var answer = await peer.createAnswer();
                            await peer.setLocalDescription(answer);
                            postJson(
                                assistTabEl.dataset.answerUrl,
                                { sdp: answer.sdp || '', type: 'answer' }
                            );
                        })
                        .listen('.webrtc.ice', function (data) {
                            if (peer && data && data.payload && data.payload.candidate) {
                                try { peer.addIceCandidate(new RTCIceCandidate(data.payload.candidate)); } catch (e) {}
                            }
                        })
                        .listen('.webrtc.end', function () {
                            tearDown();
                        });
                } catch (e) {}

                if (endBtn) {
                    endBtn.addEventListener('click', function () {
                        postJson(endBtn.dataset.endUrl, {});
                        tearDown();
                    });
                }

                var requestBtn = document.getElementById('hd-webrtc-request-' + conversationId);
                if (requestBtn) {
                    requestBtn.addEventListener('click', function () {
                        requestBtn.disabled = true;
                        var label = requestBtn.innerHTML;
                        requestBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Solicitando…';
                        postJson(requestBtn.dataset.requestUrl, {})
                            .then(function () {
                                setTimeout(function () {
                                    requestBtn.disabled = false;
                                    requestBtn.innerHTML = label;
                                }, 5000);
                            })
                            .catch(function () {
                                requestBtn.disabled = false;
                                requestBtn.innerHTML = label;
                            });
                    });
                }
            }

            // ── Fullscreen modal: hosts the player or the WebRTC video ───
            var modalEl = document.getElementById('hd-liveview-modal-' + conversationId);
            var modalBody = document.getElementById('hd-liveview-modal-body-' + conversationId);
            var modalClose = document.getElementById('hd-liveview-modal-close-' + conversationId);
            var modalTitle = document.getElementById('hd-liveview-modal-title-' + conversationId);
            var modalStatus = document.getElementById('hd-liveview-modal-status-' + conversationId);
            var liveExpand = document.getElementById('hd-liveview-expand-' + conversationId);
            var webrtcExpand = document.getElementById('hd-webrtc-expand-' + conversationId);

            var modalOriginalParent = null;
            var modalMovedNode = null;

            function triggerPlayerResize() {
                // rrweb-player listens to window resize internally (Svelte component).
                // Dispatch the event AFTER the move so the canvas re-scales to the
                // new container dimensions.
                try {
                    window.dispatchEvent(new Event('resize'));
                } catch (e) { /* noop */ }
                if (player && typeof player.triggerResize === 'function') {
                    player.triggerResize();
                }
            }

            function openModal(node, title, statusEl) {
                if (! modalEl || ! modalBody || ! node) return;
                modalOriginalParent = node.parentElement;
                modalMovedNode = node;
                modalBody.innerHTML = '';
                modalBody.appendChild(node);
                if (modalTitle) modalTitle.textContent = title;
                if (modalStatus && statusEl) {
                    modalStatus.textContent = statusEl.textContent;
                    modalStatus.className = 'bv-assist-status badge ' + (statusEl.className.match(/bg-\w+/)?.[0] || 'bg-secondary');
                }
                modalEl.classList.add('is-open');
                // The player computes scale on mount; force a resize tick so the
                // visitor viewport rescales to the new (larger) container.
                setTimeout(triggerPlayerResize, 60);
                setTimeout(triggerPlayerResize, 250);
            }

            function closeModal() {
                if (! modalEl || ! modalMovedNode || ! modalOriginalParent) {
                    modalEl?.classList.remove('is-open');
                    return;
                }
                modalOriginalParent.appendChild(modalMovedNode);
                modalEl.classList.remove('is-open');
                modalMovedNode = null;
                modalOriginalParent = null;
                setTimeout(triggerPlayerResize, 60);
            }

            if (liveExpand) {
                liveExpand.addEventListener('click', function () {
                    var playerEl = document.getElementById('hd-liveview-player-' + conversationId);
                    var statusEl = document.getElementById('hd-liveview-status-' + conversationId);
                    openModal(playerEl, 'Live view del visitante', statusEl);
                });
            }
            if (webrtcExpand) {
                webrtcExpand.addEventListener('click', function () {
                    var wrap = document.getElementById('hd-webrtc-video-' + conversationId)?.parentElement;
                    openModal(wrap, 'Pantalla del visitante', null);
                });
            }
            if (modalClose) {
                modalClose.addEventListener('click', closeModal);
            }
            if (modalEl) {
                modalEl.addEventListener('click', function (e) {
                    if (e.target === modalEl) closeModal();
                });
            }
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modalEl && modalEl.classList.contains('is-open')) {
                    closeModal();
                }
            });
            });
        }());

        // ─── Badge de identidad verificada: abre modal de verificación ───
        // Badge/boton de identidad del panel: abre el modal reutilizable de
        // verificacion (definido en HelpdeskIntegration) y recarga el panel al
        // validar, para reflejar el badge "Verificada" sin duplicar el render.
        $(document).on('click', '.bv-identity-verify-trigger', function () {
            var customerId = $(this).data('customer-id');
            if (!customerId || typeof window.openCustomerIdentityVerification !== 'function') { return; }

            window.openCustomerIdentityVerification(customerId, function () {
                window.location.reload();
            });
        });

        // ─── Botón "re-sincronizar" comercio (PrestaShop/gestión) ───
        // Botón "re-sincronizar": redetecta el vínculo PrestaShop/gestión del cliente
        // y recarga el panel para reflejar integraciones y pedidos actualizados.
        $(document).on('click', '.bv-sync-commerce', function () {
            var convId = $(this).data('conv-id');
            if (!convId) { return; }
            var $btn = $(this).prop('disabled', true);
            $btn.find('i').addClass('fa-spin');
            $.ajax({
                url: '/panel/helpdesk/conversations/' + convId + '/sync-commerce',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (res) {
                if (window.toastr) { toastr.success((res && res.message) || 'Cliente sincronizado.'); }
                setTimeout(function () { window.location.reload(); }, 600);
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'No se pudo sincronizar.';
                if (window.toastr) { toastr.error(msg); } else { alert(msg); }
                $btn.prop('disabled', false).find('i').removeClass('fa-spin');
            });
        });

        // ─── Carga perezosa: Archivos / Anteriores / Actividad / Cliente 360 ───
        (function () {
            var RP_LAZY_TABS = {
                files: { url: 'right-panel/files' },
                previous: { url: 'right-panel/previous' },
                activity: { url: 'right-panel/activity' },
                'customer-360': { url: 'right-panel/customer-360' },
            };
            var rpLazyState = {};

            function rpLoadLazyTab(tabName, force) {
                var cfg = RP_LAZY_TABS[tabName];
                if (!cfg) { return; }
                var container = document.getElementById('bv-' + tabName + '-tab');
                if (!container) { return; }
                var convId = container.dataset.convId;
                if (!convId) { return; }

                var state = rpLazyState[tabName] || (rpLazyState[tabName] = {});
                if (!force && state.loaded && state.convId === convId) { return; }

                container.innerHTML = '<div class="bv-em-loading"><i class="fas fa-spinner fa-spin"></i></div>';
                $.ajax({
                    url: '/panel/helpdesk/conversations/' + convId + '/' + cfg.url + (force ? '?force=1' : ''),
                    method: 'GET',
                }).done(function (html) {
                    container.innerHTML = html;
                    state.loaded = true;
                    state.convId = convId;
                }).fail(function () {
                    container.innerHTML = '<div class="bv-tab-empty"><div class="bv-tab-empty-sub">No se pudo cargar el contenido.</div></div>';
                    state.loaded = false;
                });
            }

            $(document).on('click', '.bv-right-tab', function () {
                var tabName = $(this).data('bv-tab');
                if (RP_LAZY_TABS[tabName]) { rpLoadLazyTab(tabName); }
            });

            // Botón "Refrescar" dentro de la pestaña Cliente 360 — fuerza
            // bypass de caché en ErpContextService/PrestashopContextService.
            $(document).on('click', '#customer360Refresh', function (e) {
                e.stopPropagation();
                rpLoadLazyTab('customer-360', true);
            });

            // Recargar al cambiar de conversación aun si la pestaña ya estaba activa
            // (mismo mecanismo de MutationObserver que usa la pestaña "Emails").
            Object.keys(RP_LAZY_TABS).forEach(function (tabName) {
                var node = document.getElementById('bv-' + tabName + '-tab');
                if (!node) { return; }
                (new MutationObserver(function () {
                    var state = rpLazyState[tabName];
                    if (state) { state.loaded = false; }
                })).observe(node, { attributes: true, attributeFilter: ['data-conv-id'] });
            });
        })();

        // ─── Pestaña Emails: listado + filtros ───
        // Gate original: @if(helpdesk_feature_enabled('email'))
        (function () {
            // Gate: este bloque vivía en right-panel.blade.php dentro de
            // @if(helpdesk_feature_enabled('email')) — se preserva la misma condición.
            if (document.querySelector('.bv-right')?.dataset.emailFeature !== '1') { return; }
            var _rpEmAll    = [];
            var _rpEmFilter = 'all';
            var _rpEmLoaded = false;
            var _rpEmConvId = null;

            function listEl() { return document.getElementById('rpEmList'); }

            function emEsc(text) { return $('<span>').text(text || '').html(); }

            // failed/bounced/complained/suppressed son estados terminales negativos
            // y se agrupan como "danger" (rojo) — antes cualquiera de ellos que no
            // fuera literalmente 'failed' se pintaba gris, igual que "en cola".
            var EM_DANGER_STATUSES = ['failed', 'bounced', 'complained', 'suppressed'];
            var EM_STATUS_ICON = { sent: 'fa-check', danger: 'fa-xmark', queued: 'fa-clock' };

            function renderCards(filter) {
                _rpEmFilter = filter;
                document.querySelectorAll('.bv-em-tab-pill').forEach(function (p) {
                    p.classList.toggle('on', p.dataset.rpEmFilter === filter);
                });

                // El pill "failed" filtra el mismo grupo "danger" que pinta la
                // tarjeta en rojo (failed/bounced/complained/suppressed), no solo
                // el literal 'failed' — si no, un email bloqueado por la lista de
                // supresión se veía rojo en la tarjeta pero desaparecía al filtrar.
                var emails = _rpEmAll.filter(function (e) {
                    if (filter === 'all') { return true; }
                    if (filter === 'failed') { return EM_DANGER_STATUSES.indexOf(e.status) !== -1; }
                    return e.status === filter;
                });

                if (!emails.length) {
                    listEl().innerHTML = '<div class="bv-em-empty">' +
                        (filter !== 'all' ? 'Sin emails en este estado.' : 'Sin emails enviados.') +
                        '</div>';
                    return;
                }

                listEl().innerHTML = emails.map(function (e) {
                    var sc = e.status === 'sent' ? 'sent' : (EM_DANGER_STATUSES.indexOf(e.status) !== -1 ? 'danger' : 'queued');
                    var sl = e.status_label || e.status;
                    var att = e.attachments_count > 0
                        ? '<span class="bv-em-chip"><i class="fas fa-paperclip"></i> ' + e.attachments_count + (e.attachments_count === 1 ? ' adjunto' : ' adjuntos') + '</span>'
                        : '';
                    var preview = e.preview
                        ? '<div class="bv-em-preview">' + emEsc(e.preview) + '</div>'
                        : '';
                    return '<button class="bv-em-card" data-em-uid="' + e.uid + '">' +
                        '<div class="bv-em-head">' +
                        '<span class="bv-em-avatar ' + sc + '"><i class="fas ' + (EM_STATUS_ICON[sc] || 'fa-clock') + '"></i></span>' +
                        '<span class="bv-em-to">' + emEsc(e.to) + '</span>' +
                        '<span class="bv-em-status ' + sc + '">' + emEsc(sl) + '</span>' +
                        '</div>' +
                        '<div class="bv-em-subject">' + emEsc(e.subject) + '</div>' +
                        preview +
                        (att ? '<div class="bv-em-chips">' + att + '</div>' : '') +
                        '<div class="bv-em-foot">' +
                        '<span class="bv-em-who">' + emEsc(e.sent_by) + '</span>' +
                        '<span class="bv-em-date">' + (e.date_human || '') + '</span>' +
                        '</div>' +
                    '</button>';
                }).join('');
            }

            function loadEmails(convId) {
                _rpEmConvId = String(convId);
                _rpEmLoaded = false;
                listEl().innerHTML = '<div class="bv-em-loading"><i class="fas fa-spinner fa-spin"></i></div>';
                document.getElementById('rpEmCount').textContent = '—';
                document.getElementById('rpEmSub').textContent   = '—';
                document.getElementById('rpEmFilterRow').style.display = 'none';

                $.ajax({
                    url: '/panel/helpdesk/conversations/' + convId + '/emails',
                    method: 'GET', dataType: 'json',
                    headers: { 'Accept': 'application/json' },
                }).done(function (resp) {
                    _rpEmAll = resp.emails || [];
                    var counts = resp.counts || {};
                    var sent   = counts.sent   || 0;
                    var failed = counts.failed || 0;

                    document.getElementById('rpEmCount').textContent      = _rpEmAll.length;
                    document.getElementById('rpEmCountAll').textContent   = _rpEmAll.length;
                    document.getElementById('rpEmCountSent').textContent  = sent;
                    document.getElementById('rpEmCountFailed').textContent = failed;

                    var queued = counts.queued || 0;
                    // counts.failed ya agrupa failed/bounced/complained/suppressed
                    // (ver emailLogIndex) — sent+queued+failed suman el total exacto,
                    // así que ya no hace falta un cuarto cubo de "incidencias".
                    var parts = [];
                    if (sent > 0)   { parts.push(sent   + ' enviado'  + (sent   !== 1 ? 's' : '')); }
                    if (queued > 0) { parts.push(queued + ' en cola'); }
                    if (failed > 0) { parts.push(failed + ' fallido'  + (failed !== 1 ? 's' : '')); }
                    document.getElementById('rpEmSub').textContent = parts.length ? parts.join(' · ') : 'ninguno aún';

                    if (_rpEmAll.length) {
                        document.getElementById('rpEmFilterRow').style.display = '';
                    }
                    renderCards(_rpEmFilter);
                    _rpEmLoaded = true;
                }).fail(function () {
                    listEl().innerHTML = '<div class="bv-em-empty">No se pudieron cargar los emails.</div>';
                });
            }

            // Activar tab "emails" → cargar
            $(document).on('click', '[data-bv-tab="emails"]', function () {
                var convId = document.getElementById('bv-emails-tab')?.dataset.convId
                    || $('.bv-composer').data('bv-conversation-id');
                if (!convId) { return; }
                if (!_rpEmLoaded || _rpEmConvId !== String(convId)) {
                    loadEmails(convId);
                }
            });

            // Recargar al cambiar de conversación
            var tabNode = document.getElementById('bv-emails-tab');
            if (tabNode) {
                (new MutationObserver(function () { _rpEmLoaded = false; }))
                    .observe(tabNode, { attributes: true, attributeFilter: ['data-conv-id'] });
            }

            // Click en em-card → abrir viewer
            $(document).on('click', '#rpEmList .bv-em-card', function () {
                var uid = $(this).data('em-uid');
                if (!uid) { return; }
                if (typeof window.openEmailViewer === 'function') {
                    window.openEmailViewer(uid);
                }
            });

            // Pills de filtro
            $(document).on('click', '.bv-em-tab-pill', function () {
                renderCards($(this).data('rp-em-filter'));
            });

            // Recargar desde fuera (tras enviar email nuevo)
            window.rpEmReload = function () {
                _rpEmLoaded = false;
                var tab = document.getElementById('bv-emails-tab');
                if (tab && !tab.classList.contains('bv-tab-hidden')) {
                    var convId = tab.dataset.convId || $('.bv-composer').data('bv-conversation-id');
                    if (convId) { loadEmails(convId); }
                }
            };
        }());

        // ─── Pestaña Anteriores: búsqueda/filtro + visor de conversación ───
        (function () {
            // Este bloque solo se registraba en el Blade original dentro de
            // @if(helpdesk_feature_enabled('email')) (aunque su contenido, la
            // pestaña 'Anteriores' / visor de conversaciones, no es del feature de
            // email — se preserva el mismo gate tal cual estaba).
            if (document.querySelector('.bv-right')?.dataset.emailFeature !== '1') { return; }
            // ── Previous tab: search + filter ──────────────────────────────
            $(document).on('input', '.bv-prev-search-input', function () {
                var q = $(this).val().toLowerCase();
                $('#bvPrevList .bv-conv-card').each(function () {
                    var text = ($(this).data('bv-prev-text') || '') + ' ' + $(this).find('.bv-conv-nm').text().toLowerCase();
                    $(this).toggleClass('bv-hidden', q.length > 0 && !text.includes(q));
                });
            });

            $(document).on('click', '.bv-prev-pill', function () {
                var filter = $(this).data('bv-prev-filter');
                $('.bv-prev-pill').removeClass('on');
                $(this).addClass('on');
                $('#bvPrevList .bv-conv-card').each(function () {
                    var isOpen = $(this).data('bv-prev-open') === 1 || $(this).data('bv-prev-open') === '1';
                    var show = filter === 'all' || (filter === 'open' && isOpen) || (filter === 'closed' && !isOpen);
                    $(this).toggleClass('bv-hidden', !show);
                });
            });

            // ── Click conv-card → open conversation viewer ─────────────────
            $(document).on('click', '.bv-conv-card', function () {
                var convId = $(this).data('conv-id');
                if (!convId) { return; }
                window._cvConvId = convId;
                $('[data-bv-modal-name="conversation-viewer"]').addClass('on');
                $('body').css('overflow', 'hidden');
                if (typeof window.loadConversationViewer === 'function') {
                    window.loadConversationViewer($(this).data('viewer-url'));
                }
            });

            // ── History modal pills filter ─────────────────────────────────
            $(document).on('click', '.bv-hist-pill', function () {
                var filter = $(this).data('bv-hist-filter');
                $('.bv-hist-pill').removeClass('on');
                $(this).addClass('on');
                $('#histList .bv-conv-card').each(function () {
                    var isOpen = $(this).data('bv-prev-open') === 1 || $(this).data('bv-prev-open') === '1';
                    var show = filter === 'all' || (filter === 'open' && isOpen) || (filter === 'closed' && !isOpen);
                    $(this).toggleClass('bv-hidden', !show);
                });
            });

            $(document).on('input', '#histSearchInput', function () {
                var q = $(this).val().toLowerCase();
                $('#histList .bv-conv-card').each(function () {
                    var text = $(this).find('.bv-conv-nm, .bv-conv-preview').text().toLowerCase();
                    $(this).toggleClass('bv-hidden', q.length > 0 && !text.includes(q));
                });
            });

            // ── Conversation viewer loader ─────────────────────────────────
            window.loadConversationViewer = function (viewerUrl) {
                $('#cvLoading').removeClass('bv-hidden');
                $('#cvCtxBar, #cvMessages').addClass('bv-hidden');
                $('#cvMessages').empty();

                $.ajax({
                    url: viewerUrl,
                    method: 'GET',
                    dataType: 'json',
                    headers: { 'Accept': 'application/json' },
                }).done(function (data) {
                    var conv  = data.conversation || {};
                    var items = data.items || [];

                    var cvSubjectHtml = $('<span>').text(conv.subject || 'Conversación').html();
                    var cvChip = conv.id ? '<span class="bv-cv-id-chip">#' + conv.id + '</span>' : '';
                    $('#cvModalTitle').html(cvSubjectHtml + cvChip);
                    $('#cvCtxAv').text(conv.customer_initials || '?');
                    $('#cvCtxNm').text(conv.customer_name || '—');

                    var ch = conv.channel_icon ? '<i class="' + conv.channel_icon + '"></i> ' : '';
                    $('#cvCtxSub').html(ch + (conv.channel || 'web') + ' · ' + (conv.message_count || 0) + ' mensajes · iniciado el ' + (conv.started_at_formatted || ''));

                    var statusCls = conv.is_open ? 'open' : '';
                    $('#cvCtxStatus').attr('class', 'bv-cv-ctx-status ' + statusCls).text(conv.status_name || '—');
                    $('#cvCtxBar').removeClass('bv-hidden');

                    if (window._cvConvId) {
                        $('#cvBtnOpen').off('click.cv').on('click.cv', function () {
                            window.open('/panel/helpdesk/conversations?selected=' + window._cvConvId, '_self');
                        });
                    }

                    var html = '';
                    items.forEach(function (item) {
                        if (item.type === 'day_separator') {
                            html += '<div class="bv-cv-day">' + $('<span>').text(item.label).html() + '</div>';
                            return;
                        }
                        if (item.is_internal) {
                            html += '<div class="bv-cv-system">' + $('<span>').text(item.body || '').html() + '</div>';
                            return;
                        }
                        var dirClass = item.is_agent ? 'bv-out' : 'bv-in';
                        var avText   = $('<span>').text(item.author_initials || '?').html();
                        var bodyHtml = $('<span>').text(item.body || '').html().replace(/\n/g, '<br>');
                        html += '<div class="bv-cv-bubble-row ' + dirClass + '">' +
                            '<div class="bv-cv-av-sm">' + avText + '</div>' +
                            '<div class="bv-cv-bubble">' + bodyHtml +
                            '<span class="bv-cv-ts">' + $('<span>').text(item.time_formatted || '').html() + '</span>' +
                            '</div></div>';
                    });

                    if (!html) {
                        html = '<div class="bv-cv-loading-msg">Sin mensajes registrados.</div>';
                    }

                    $('#cvMessages').html(html).removeClass('bv-hidden');

                    var msgs = document.getElementById('cvMessages');
                    if (msgs) { msgs.scrollTop = msgs.scrollHeight; }
                }).fail(function () {
                    $('#cvLoading').html('<i class="fas fa-triangle-exclamation"></i> No se pudo cargar la conversación.');
                }).always(function () {
                    $('#cvLoading').addClass('bv-hidden');
                });
            };

            // ── MutationObserver: reset viewer when modal closes ──────────
            var cvModal = document.querySelector('[data-bv-modal-name="conversation-viewer"]');
            if (cvModal) {
                (new MutationObserver(function (mutations) {
                    mutations.forEach(function (m) {
                        if (m.attributeName !== 'class') { return; }
                        if (!$(m.target).hasClass('on')) {
                            window._cvConvId = null;
                            $('#cvMessages').empty();
                            $('#cvCtxBar, #cvMessages').addClass('bv-hidden');
                            $('#cvLoading').removeClass('bv-hidden').html('<i class="fas fa-spinner fa-spin"></i> Cargando…');
                        }
                    });
                })).observe(cvModal, { attributes: true });
            }
        }());


        // Exponer helpers compartidos con conversations-list.js / conversations-thread.js
        window.getConvUrls = getConvUrls;
        window.initRightPanelTabs = initRightPanelTabs;

    });
})(jQuery);
