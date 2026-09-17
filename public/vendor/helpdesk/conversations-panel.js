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


        // Exponer helpers compartidos con conversations-list.js / conversations-thread.js
        window.getConvUrls = getConvUrls;
        window.initRightPanelTabs = initRightPanelTabs;

    });
})(jQuery);
