/**
 * Bandeja v4 - Helpdesk conversations interactions
 * jQuery + Bootstrap 5.3 (sin React/Babel)
 *
 * Funcionalidad:
 * - Modales (open/close por data-bv-modal)
 * - Atajos de teclado (J/K navegación, ?/A/T/S/P/F/R/N/#)
 * - Click en items de lista → activar conversación
 * - Tabs del panel derecho
 * - Tabs del composer
 * - Channel filter pills
 * - Filter chips
 * - Búsqueda en lista
 */

(function ($) {
    'use strict';

    function escapeHtml(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    $(function () {
        // ─── Browser notification permission ─────────────────────────
        if (window.Notification && Notification.permission === 'default') {
            $(document).one('click', function () {
                Notification.requestPermission();
            });
        }

        // ─── Auto-scroll del thread al final ──────────────────────────
        function scrollThreadToBottom(smooth) {
            const $body = $('.bv-th-body');
            if (!$body.length) return;
            const target = $body[0].scrollHeight;
            if (smooth) {
                $body[0].scrollTo({ top: target, behavior: 'smooth' });
            } else {
                $body[0].scrollTop = target;
            }
        }
        window.scrollThreadToBottom = scrollThreadToBottom;

        // Al abrir el thread, ir al final inmediatamente (sin animación)
        scrollThreadToBottom(false);

        // Volver a hacer scroll cuando carguen imágenes/videos (cambian el alto del contenedor)
        $('.bv-th-inner img, .bv-th-inner video').each(function () {
            const el = this;
            if (el.tagName === 'IMG' && !el.complete) {
                $(el).one('load error', () => scrollThreadToBottom(false));
            } else if (el.tagName === 'VIDEO') {
                $(el).one('loadedmetadata', () => scrollThreadToBottom(false));
            }
        });

        // ─── Búsqueda en thread ──────────────────────────────────────
        let searchHits = [];
        let searchIndex = -1;

        function clearSearchHighlights() {
            $('.bv-th-inner mark.bv-search-hit').each(function () {
                const t = document.createTextNode(this.textContent);
                this.parentNode.replaceChild(t, this);
            });
            $('.bv-th-inner').each(function () { this.normalize(); });
            searchHits = [];
            searchIndex = -1;
            $('#bv-th-search-count').text('');
            $('#bv-th-search-prev, #bv-th-search-next').prop('disabled', true);
        }

        function highlightSearchInThread(query) {
            clearSearchHighlights();
            const q = (query || '').trim();
            if (q.length < 2) return;

            const re = new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
            const $bubbles = $('.bv-th-inner .bv-bubble');
            const hits = [];

            $bubbles.each(function () {
                // Sólo procesar nodos de texto (sin tocar HTML interno)
                const walker = document.createTreeWalker(this, NodeFilter.SHOW_TEXT, {
                    acceptNode(node) {
                        const p = node.parentElement;
                        if (!p) return NodeFilter.FILTER_REJECT;
                        if (p.closest('.meta, .note-badge, mark, script, style')) return NodeFilter.FILTER_REJECT;
                        return node.nodeValue && re.test(node.nodeValue) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
                    },
                });
                const texts = [];
                let n;
                while ((n = walker.nextNode())) texts.push(n);
                texts.forEach(t => {
                    re.lastIndex = 0;
                    const frag = document.createDocumentFragment();
                    let last = 0;
                    let m;
                    const v = t.nodeValue;
                    while ((m = re.exec(v))) {
                        if (m.index > last) frag.appendChild(document.createTextNode(v.slice(last, m.index)));
                        const mark = document.createElement('mark');
                        mark.className = 'bv-search-hit';
                        mark.textContent = m[0];
                        frag.appendChild(mark);
                        hits.push(mark);
                        last = m.index + m[0].length;
                        if (m.index === re.lastIndex) re.lastIndex++;
                    }
                    if (last < v.length) frag.appendChild(document.createTextNode(v.slice(last)));
                    t.parentNode.replaceChild(frag, t);
                });
            });

            searchHits = hits;
            searchIndex = hits.length ? 0 : -1;
            $('#bv-th-search-count').text(hits.length ? `${searchIndex + 1}/${hits.length}` : 'Sin resultados');
            $('#bv-th-search-prev, #bv-th-search-next').prop('disabled', hits.length < 2);
            if (hits.length) focusSearchHit(0);
        }

        function focusSearchHit(idx) {
            $('mark.bv-search-current').removeClass('bv-search-current');
            const hit = searchHits[idx];
            if (!hit) return;
            hit.classList.add('bv-search-current');
            hit.scrollIntoView({ behavior: 'smooth', block: 'center' });
            $('#bv-th-search-count').text(`${idx + 1}/${searchHits.length}`);
        }

        $(document).on('click', '#bv-th-search-btn', function () {
            $('#bv-th-search').toggleClass('bv-hidden');
            if (!$('#bv-th-search').hasClass('bv-hidden')) {
                $('#bv-th-search-input').trigger('focus');
            } else {
                clearSearchHighlights();
            }
        });

        $(document).on('click', '#bv-th-search-close', function () {
            $('#bv-th-search').addClass('bv-hidden');
            $('#bv-th-search-input').val('');
            clearSearchHighlights();
        });

        $(document).on('input', '#bv-th-search-input', function () {
            highlightSearchInThread($(this).val());
        });

        $(document).on('click', '#bv-th-search-next', function () {
            if (!searchHits.length) return;
            searchIndex = (searchIndex + 1) % searchHits.length;
            focusSearchHit(searchIndex);
        });

        $(document).on('click', '#bv-th-search-prev', function () {
            if (!searchHits.length) return;
            searchIndex = (searchIndex - 1 + searchHits.length) % searchHits.length;
            focusSearchHit(searchIndex);
        });

        $(document).on('keydown', '#bv-th-search-input', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(e.shiftKey ? '#bv-th-search-prev' : '#bv-th-search-next').click();
            } else if (e.key === 'Escape') {
                $('#bv-th-search-close').click();
            }
        });

        // ─── Dropzone overlay (drag de archivos sobre todo el thread) ─
        let dragCounter = 0;
        function showDropOverlay() {
            if (document.getElementById('bv-drop-overlay')) return;
            const $body = $('.bv-th-body');
            if (!$body.length) return;
            $body.css('position', 'relative').append(
                '<div id="bv-drop-overlay" class="bv-drop-overlay">' +
                    '<i class="fas fa-cloud-arrow-up bv-drop-overlay-icon"></i>' +
                    '<div class="bv-drop-overlay-text">Suelta los archivos aquí</div>' +
                    '<div class="bv-drop-overlay-sub">Hasta 16 MB por archivo · imágenes, video, audio o documentos</div>' +
                '</div>'
            );
        }
        function hideDropOverlay() {
            $('#bv-drop-overlay').remove();
        }

        $(window).on('dragenter', function (e) {
            if (e.originalEvent?.dataTransfer?.types?.includes?.('Files')) {
                dragCounter++;
                showDropOverlay();
            }
        });
        $(window).on('dragleave', function () {
            dragCounter = Math.max(0, dragCounter - 1);
            if (dragCounter === 0) hideDropOverlay();
        });
        $(window).on('dragover', function (e) { e.preventDefault(); });
        $(window).on('drop', function (e) {
            dragCounter = 0;
            hideDropOverlay();
            const files = e.originalEvent?.dataTransfer?.files;
            if (!files || !files.length) return;
            // Si el drop fue sobre el thread (no el composer), igualmente subir
            const $target = $(e.target);
            if (!$target.closest('.bv-composer-input, .bv-composer-box').length) {
                e.preventDefault();
                if (typeof uploadFiles === 'function') uploadFiles(files);
            }
        });

        // ─── Modales ─────────────────────────────────────────────────
        // UI-04: foco que se restaura al cerrar el modal.
        let lastFocusedBeforeModal = null;

        const MODAL_FOCUSABLE = 'a[href], button:not([disabled]), textarea:not([disabled]), '
            + 'input:not([disabled]):not([type=hidden]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

        // UI-04: añade semántica ARIA de diálogo sin alterar el flujo de apertura.
        function applyModalA11y($modal, name) {
            const $dialog = $modal.find('.bv-modal-dialog').first();
            const $d = $dialog.length ? $dialog : $modal;
            $modal.attr('role', 'presentation');
            $d.attr('role', 'dialog').attr('aria-modal', 'true');
            const $title = $d.find('.bv-modal-title').first();
            if ($title.length) {
                let titleId = $title.attr('id');
                if (!titleId) {
                    titleId = 'bv-modal-title-' + name;
                    $title.attr('id', titleId);
                }
                $d.attr('aria-labelledby', titleId);
            }
            return $d;
        }

        function openModal(name) {
            const $modal = $(`[data-bv-modal-name="${name}"]`);
            if ($modal.length) {
                lastFocusedBeforeModal = document.activeElement;
                const $dialog = applyModalA11y($modal, name);
                $modal.addClass('on');
                $('body').css('overflow', 'hidden');
                $(document).trigger('bv:modal:open', [name]);
                // UI-04: mover el foco dentro del diálogo (focus-trap).
                if (!$dialog.attr('tabindex')) { $dialog.attr('tabindex', '-1'); }
                $dialog.trigger('focus');
            }
        }

        function closeModal($modal) {
            $modal.removeClass('on');
            if ($modal.hasClass('bv-lightbox')) {
                const v = document.getElementById('bv-lightbox-video');
                if (v) { v.pause(); v.removeAttribute('src'); v.load(); }
                const a = document.getElementById('bv-lightbox-audio');
                if (a) { a.pause(); a.removeAttribute('src'); a.load(); }
                const img = document.getElementById('bv-lightbox-img');
                if (img) { img.removeAttribute('src'); img.classList.remove('bv-lb-error'); }
            }
            if ($('.bv-modal.on').length === 0) {
                $('body').css('overflow', '');
                // UI-04: restaurar el foco al elemento que abrió el modal.
                if (lastFocusedBeforeModal && document.contains(lastFocusedBeforeModal)) {
                    try { lastFocusedBeforeModal.focus(); } catch (err) { /* noop */ }
                }
                lastFocusedBeforeModal = null;
            }
        }

        // UI-04: focus-trap — Tab cicla dentro del modal abierto.
        $(document).on('keydown', function (e) {
            if (e.key !== 'Tab') { return; }
            const $open = $('.bv-modal.on').last();
            if (!$open.length) { return; }
            const $dialog = $open.find('.bv-modal-dialog').first();
            const $scope = $dialog.length ? $dialog : $open;
            const $focusable = $scope.find(MODAL_FOCUSABLE).filter(':visible');
            if (!$focusable.length) { return; }
            const first = $focusable.first()[0];
            const last = $focusable.last()[0];
            const active = document.activeElement;
            if (e.shiftKey && (active === first || active === $scope[0])) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && active === last) {
                e.preventDefault();
                first.focus();
            }
        });

        // Abrir modal por click en data-bv-modal
        $(document).on('click', '[data-bv-modal]', function (e) {
            const name = $(this).data('bv-modal');
            if (name) {
                e.preventDefault();
                closeAllMenus();
                openModal(name);
            }
        });

        // Cerrar modal por click en data-bv-close o backdrop
        $(document).on('click', '[data-bv-close]', function () {
            closeModal($(this).closest('.bv-modal'));
        });

        $(document).on('click', '.bv-modal', function (e) {
            if (e.target === this) {
                closeModal($(this));
            }
        });

        // ESC cierra el modal abierto
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                const $open = $('.bv-modal.on').last();
                if ($open.length) {
                    closeModal($open);
                }
            }
        });

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
        function setRtabUrl(tab) {
            try {
                const u = new URL(window.location.href);
                u.searchParams.set('rtab', tab);
                history.replaceState(history.state, '', u.toString());
            } catch (_) {}
        }
        function getRtabFromUrl() {
            try { return new URL(window.location.href).searchParams.get('rtab') || null; } catch (_) { return null; }
        }

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
            document.querySelectorAll('.bv-right-tab[data-bs-toggle="tooltip"]').forEach(function (el) {
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

        // ─── Tabs composer ───────────────────────────────────────────
        $(document).on('click', '.bv-composer-tab', function () {
            const $tab = $(this);
            const target = $tab.data('bv-tab');
            $tab.siblings().removeClass('on');
            $tab.addClass('on');
            $('#bv-composer-box').toggleClass('note', target === 'note');

            if (target === 'hsm') {
                $('#bv-translate-panel').removeClass('on');
                $('#bv-hsm-picker').addClass('on');
            } else if (target === 'translate') {
                $('#bv-hsm-picker').removeClass('on');
                $('#bv-translate-panel').addClass('on');
            } else {
                $('#bv-hsm-picker').removeClass('on');
                $('#bv-translate-panel').removeClass('on');
            }
        });

        // ─── Cerrar paneles HSM y Traducción ─────────────────────────
        function activateReplyTab() {
            const $reply = $('.bv-composer-tab[data-bv-tab="reply"]');
            $reply.siblings().removeClass('on');
            $reply.addClass('on');
            $('#bv-composer-box').removeClass('note');
        }

        $(document).on('click', '#bv-hsm-close, #bv-hsm-close-2', function () {
            $('#bv-hsm-picker').removeClass('on');
            activateReplyTab();
        });

        // ─── HSM Templates ────────────────────────────────────────────
        var hsmTemplates = [];
        var hsmSelectedId = null;

        function loadHsmTemplates() {
            if (hsmTemplates.length) { renderHsmList(hsmTemplates); return; }
            $.ajax({
                url: '/panel/helpdesk/hsm-templates',
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (resp) {
                hsmTemplates = resp.templates || [];
                renderHsmList(hsmTemplates);
            }).fail(function () {
                $('#bv-hsm-list').append('<div style="padding:12px;color:#9aa0ab;font-size:12px">Error al cargar plantillas</div>');
            });
        }

        function renderHsmList(list) {
            var $list = $('#bv-hsm-list');
            $list.find('.bv-hsm-row').remove();
            if (!list.length) {
                $list.append('<div style="padding:12px;color:#9aa0ab;font-size:12px">Sin plantillas aprobadas</div>');
                return;
            }
            list.forEach(function (t, i) {
                var badge = '<span class="bv-hsm-badge-approved">APPROVED</span>';
                var html = '<div class="bv-hsm-row' + (i === 0 ? ' on' : '') + '" data-hsm-id="' + t.id + '">' +
                    '<div class="nm">' + escapeHtml(t.name) + '</div>' +
                    '<div class="meta">' + badge + '</div></div>';
                $list.append(html);
            });
            if (list.length) selectHsmTemplate(list[0].id);
        }

        function selectHsmTemplate(id) {
            var t = hsmTemplates.find(function (x) { return x.id == id; });
            if (!t) return;
            hsmSelectedId = id;
            $('#bv-hsm-preview-text').text(t.body || 'Sin contenido');
            var varsHtml = '';
            for (var i = 1; i <= (t.param_count || 0); i++) {
                varsHtml += '<div class="bv-hsm-var-row">' +
                    '<span class="bv-hsm-var-lbl">{{' + i + '}}</span>' +
                    '<input type="text" class="bv-hsm-var-input" data-hsm-var-idx="' + i + '" placeholder="Variable ' + i + '">' +
                '</div>';
            }
            if (!varsHtml) varsHtml = '<div style="font-size:12px;color:#9aa0ab;padding:4px 0">Sin variables</div>';
            $('#bv-hsm-vars-list').html(varsHtml);
        }

        $(document).on('click', '.bv-composer-tab[data-bv-tab="hsm"]', function () {
            loadHsmTemplates();
        });

        $(document).on('click', '.bv-hsm-row', function () {
            $(this).siblings('.bv-hsm-row').removeClass('on');
            $(this).addClass('on');
            selectHsmTemplate($(this).data('hsm-id'));
        });

        $(document).on('input', '#bv-hsm-search', function () {
            var q = $(this).val().toLowerCase();
            var filtered = hsmTemplates.filter(function (t) {
                return !q || (t.name && t.name.toLowerCase().includes(q));
            });
            renderHsmList(filtered);
        });

        $(document).on('click', '#bv-hsm-insert', function () {
            var t = hsmTemplates.find(function (x) { return x.id == hsmSelectedId; });
            if (!t) { if (window.toastr) toastr.warning('Selecciona una plantilla'); return; }
            var convId = $('.bv-composer').data('bv-conversation-id');
            var sendUrl = $('.bv-composer').data('bv-send-hsm-url');
            if (!sendUrl) { if (window.toastr) toastr.error('No hay conversación activa'); return; }
            var variables = [];
            $('.bv-hsm-var-input').each(function () {
                variables.push($(this).val() || '');
            });
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: sendUrl,
                method: 'POST',
                dataType: 'json',
                data: { template_name: t.name, variables: variables },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            }).done(function (resp) {
                if (window.toastr) toastr.success('Plantilla enviada.');
                $('#bv-hsm-picker').removeClass('on');
                activateReplyTab();
            }).fail(function (xhr) {
                var msg = xhr?.responseJSON?.message || 'Error al enviar plantilla';
                if (window.toastr) toastr.error(msg);
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        $(document).on('click', '#bv-translate-close, #bv-translate-close-2', function () {
            $('#bv-translate-panel').removeClass('on');
            activateReplyTab();
        });

        // ─── More menu toggle ─────────────────────────────────────────
        $(document).on('click', '#bv-btn-more', function (e) {
            e.stopPropagation();
            const $menu = $('#bv-more-menu');
            const wasOpen = $menu.hasClass('on');
            closeAllMenus();
            if (!wasOpen) {
                $menu.addClass('on');
            }
        });

        // ─── Sort dropdown toggle ─────────────────────────────────────
        $(document).on('click', '#bv-btn-sort', function (e) {
            e.stopPropagation();
            const $menu = $('#bv-sort-menu');
            const wasOpen = $menu.hasClass('on');
            closeAllMenus();
            if (!wasOpen) {
                // Posicionar el menú (position:fixed) bajo el botón, alineado a la derecha
                const rect = this.getBoundingClientRect();
                const menuWidth = 220;
                let left = rect.right - menuWidth;
                if (left < 8) left = 8;
                if (left + menuWidth > window.innerWidth - 8) {
                    left = window.innerWidth - menuWidth - 8;
                }
                $menu.css({
                    top: (rect.bottom + 6) + 'px',
                    left: left + 'px',
                });
                $menu.addClass('on');
            }
        });

        $(document).on('click', '.bv-sort-opt', function (e) {
            e.stopPropagation();
            const sort = $(this).data('sort');
            $('.bv-sort-opt').removeClass('on');
            $(this).addClass('on');
            closeAllMenus();
            applyInboxFilters({ sort: sort === 'newest' ? null : sort });
        });

        // ─── Attach menu toggle ───────────────────────────────────────
        $(document).on('click', '#bv-btn-attach', function (e) {
            e.stopPropagation();
            const $menu = $('#bv-attach-menu');
            const wasOpen = $menu.hasClass('on');
            closeAllMenus();
            if (!wasOpen) {
                $menu.addClass('on');
            }
        });

        function closeAllMenus() {
            $('#bv-more-menu, #bv-attach-menu, #bv-sort-menu, .rsp-more-menu').removeClass('on');
        }

        // ─── Panel cliente: menú "Más" ────────────────────────────────
        $(document).on('click', '.rsp-more-toggle', function (e) {
            e.stopPropagation();
            const $menu = $(this).siblings('.rsp-more-menu');
            const wasOpen = $menu.hasClass('on');
            closeAllMenus();
            if (!wasOpen) {
                $menu.addClass('on');
            }
        });

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

        // ─── Selección de plantilla HSM ───────────────────────────────
        $(document).on('click', '.bv-hsm-row', function () {
            $(this).siblings('.bv-hsm-row').removeClass('on');
            $(this).addClass('on');
        });

        // ─── Helpers AJAX inbox ──────────────────────────────────────
        const inboxParams = ['channel', 'unread', 'mine', 'urgent', 'vip', 'archived',
            'priority', 'status', 'group', 'tag', 'viewId', 'search', 'selected', 'sort',
            'assignee', 'date', 'rtab'];

        function readInboxFiltersFromUrl() {
            const u = new URL(window.location.href);
            const out = {};
            inboxParams.forEach(p => {
                const v = u.searchParams.get(p);
                if (v !== null && v !== '') out[p] = v;
            });
            return out;
        }

        let lastInboxRefresh = 0;
        let pendingInboxRefresh = null;

        function refreshInboxList(params, opts = {}) {
            const now = Date.now();
            const since = now - lastInboxRefresh;
            if (!opts.force && since < 1500) {
                clearTimeout(pendingInboxRefresh);
                pendingInboxRefresh = setTimeout(() => refreshInboxList(params, { force: true }), 1500 - since);
                return;
            }
            lastInboxRefresh = now;

            // Preserve rtab across inbox navigation
            const rtab = getRtabFromUrl();
            if (rtab) { params = params || {}; if (!params.rtab) params.rtab = rtab; }
            const qs = $.param(params || {});
            const newUrl = '/panel/helpdesk/conversations' + (qs ? '?' + qs : '');
            history.pushState({}, '', newUrl);

            $.ajax({
                url: '/panel/helpdesk/conversations/list' + (qs ? '?' + qs : ''),
                method: 'GET',
                dataType: 'json',
                headers: { 'Accept': 'application/json' },
            })
                .done(resp => {
                    if (resp.html) {
                        const $temp = $('<div>').html(resp.html);
                        const $newConvList = $temp.find('.bv-conv-list');
                        if ($newConvList.length) {
                            $('.bv-conv-list').html($newConvList.html());
                        }
                    }
                    if (resp.counts) {
                        ['total', 'unread', 'mine', 'urgent'].forEach(k => {
                            if (resp.counts[k] !== undefined) {
                                $('[data-counter="' + k + '"]').text(resp.counts[k]);
                            }
                        });
                    }
                })
                .fail(() => { if (window.toastr) toastr.error('No se pudo refrescar la lista'); });
        }

        function applyInboxFilters(updates) {
            const next = { ...readInboxFiltersFromUrl(), ...updates };
            Object.keys(next).forEach(k => {
                if (next[k] === null || next[k] === '' || next[k] === false || next[k] === '0') {
                    delete next[k];
                }
            });
            refreshInboxList(next, { force: true });
            updateFilterBadge();
        }

        // Badge de filtros activos sobre el botón de filtro de la barra
        function updateFilterBadge() {
            const u = new URL(window.location.href);
            const keys = ['channel', 'status', 'priority', 'tag', 'mine', 'unread', 'urgent', 'vip', 'assignee', 'group', 'archived'];
            let n = 0;
            keys.forEach(k => {
                const v = u.searchParams.get(k);
                if (v !== null && v !== '' && v !== '0') {
                    n += String(v).split(',').filter(Boolean).length;
                }
            });
            const $b = $('#bvFilterBadge');
            if (n > 0) { $b.text(n).prop('hidden', false); }
            else { $b.text('').prop('hidden', true); }
        }
        updateFilterBadge();

        // Expose so inline scripts (e.g. filter modal) can call it
        window.applyInboxFilters = applyInboxFilters;

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

        // ─── Helpers de navegación de inbox ──────────────────────────
        function navigateInbox(params) {
            const url = new URL(window.location.href);
            // Limpiar params de navegación conocidos
            ['unread', 'mine', 'priority'].forEach(function (p) {
                url.searchParams.delete(p);
            });
            Object.keys(params).forEach(function (key) {
                url.searchParams.set(key, params[key]);
            });
            window.location.href = url.toString();
        }

        function archiveCurrentConversation() {
            const $btn = $('[data-bv-action="archive"][data-bv-url]').first();
            if ($btn.length) {
                $btn.click();
                return;
            }
            // Fallback: derive archive URL from update URL
            const urls = getConvUrls();
            if (!urls.updateUrl) {
                toastr && toastr.warning('No hay conversacion activa');
                return;
            }
            const archiveUrl = urls.updateUrl.replace(/\/?$/, '/archive');
            $.ajax({
                url: archiveUrl,
                method: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            })
                .done(function (resp) {
                    toastr && toastr.success((resp && resp.message) || 'Conversación archivada');
                    $('.bv-conv.on').fadeOut(180, function () { $(this).remove(); });
                })
                .fail(function (xhr) {
                    const msg = xhr?.responseJSON?.message || 'No se pudo archivar';
                    toastr && toastr.error(msg);
                });
        }

        // ─── Atajos de teclado ───────────────────────────────────────
        // State machine para secuencia G → X
        let gPressed = false;
        let gTimer = null;

        $(document).on('keydown', function (e) {
            // No interferir si el usuario está escribiendo
            if ($(e.target).is('input, textarea, [contenteditable]')) return;

            // ⌘/ o Ctrl+/ — toggle shortcuts modal
            if ((e.metaKey || e.ctrlKey) && e.key === '/') {
                e.preventDefault();
                const $shortcuts = $('[data-bv-modal-name="shortcuts"]');
                if ($shortcuts.hasClass('on')) {
                    closeModal($shortcuts);
                } else {
                    openModal('shortcuts');
                }
                return;
            }

            // ⌘E — archivar conversacion actual
            if ((e.metaKey || e.ctrlKey) && !e.shiftKey && e.key.toLowerCase() === 'e') {
                e.preventDefault();
                archiveCurrentConversation();
                return;
            }

            // ⌘+Shift+D — cerrar conversacion
            if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key.toLowerCase() === 'd') {
                e.preventDefault();
                openModal('close-conv');
                return;
            }


            // Ignorar otros modificadores
            if (e.metaKey || e.ctrlKey) return;

            // Tab — saltar al hilo cuando focus en lista
            if (e.key === 'Tab' && !e.shiftKey) {
                const $list = $('.bv-list');
                if ($list.length && ($list.is(':focus') || $list.find(':focus').length)) {
                    e.preventDefault();
                    $('.bv-th-inner').focus();
                }
                return;
            }

            // Shift+Tab — saltar al panel derecho cuando focus en hilo
            if (e.key === 'Tab' && e.shiftKey) {
                const $thread = $('.bv-thread');
                if ($thread.length && ($thread.is(':focus') || $thread.find(':focus').length)) {
                    e.preventDefault();
                    $('.bv-right').first().focus();
                }
                return;
            }

            // Secuencia G → X (state machine)
            if (e.key.toLowerCase() === 'g' && !gPressed) {
                gPressed = true;
                clearTimeout(gTimer);
                gTimer = setTimeout(function () { gPressed = false; }, 1500);
                return;
            }

            if (gPressed) {
                gPressed = false;
                clearTimeout(gTimer);
                switch (e.key.toLowerCase()) {
                    case 'u': navigateInbox({ unread: 1 }); break;
                    case 'm': navigateInbox({ mine: 1 }); break;
                    case 'a': navigateInbox({}); break;
                    case 'r': navigateInbox({ priority: 'urgent' }); break;
                }
                return;
            }

            const key = e.key.toLowerCase();
            switch (e.key) {
                case '?':
                    e.preventDefault();
                    openModal('shortcuts');
                    break;
                case '#':
                    e.preventDefault();
                    openModal('close-conv');
                    break;
                case 'ArrowDown':
                case 'j':
                    e.preventDefault();
                    navigateConv(1);
                    break;
                case 'ArrowUp':
                case 'k':
                    e.preventDefault();
                    navigateConv(-1);
                    break;
                default:
                    if (key === 'a') openModal('assign');
                    else if (key === 't') openModal('tags');
                    else if (key === 's') openModal('status');
                    else if (key === 'p') openModal('priority');
                    else if (key === 'f') openModal('filter');
                    else if (key === 'm') openModal('macro');
                    else if (key === 'n') $('.bv-composer-tab[data-bv-tab="note"]').click();
                    else if (key === 'r') $('.bv-composer-tab[data-bv-tab="reply"]').click();
            }
        });

        function navigateConv(direction) {
            const $items = $('.bv-conv:visible');
            if ($items.length === 0) return;
            const $current = $items.filter('.on');
            let nextIndex;
            if ($current.length === 0) {
                nextIndex = 0;
            } else {
                const currentIndex = $items.index($current);
                nextIndex = Math.max(0, Math.min($items.length - 1, currentIndex + direction));
            }
            $items.eq(nextIndex).click();
        }

        // ─── Composer: atajo de envío configurable ────────────────────
        // Preferencia persistente: 'ctrl-enter' (default) o 'enter'
        const SEND_SHORTCUT_KEY = 'bv:composer:send-shortcut';
        function getSendShortcut() {
            const v = localStorage.getItem(SEND_SHORTCUT_KEY);
            return v === 'enter' ? 'enter' : 'ctrl-enter';
        }
        function applySendShortcutUI() {
            const cur = getSendShortcut();
            const isMac = /Mac/.test(navigator.platform);
            $('#bv-kbd-send').text(cur === 'enter' ? '↵' : (isMac ? '⌘↵' : 'Ctrl+↵'));
            $('.bv-send-menu-opt').each(function () {
                $(this).toggleClass('on', $(this).data('bv-send-shortcut') === cur);
            });
        }
        applySendShortcutUI();

        $(document).on('keydown', '.bv-composer-input', function (e) {
            if (e.key !== 'Enter') return;
            // Si el menú de mención está abierto, dejar que su handler maneje Enter
            const $mentionMenu = $('#bv-mention-menu');
            if ($mentionMenu.length && !$mentionMenu.hasClass('bv-hidden')) return;
            // Si el slash-menu está abierto, dejar que su handler maneje Enter
            const $slashMenu = $('#bv-slash-menu');
            if ($slashMenu.length && $slashMenu.is(':visible')) return;
            const shortcut = getSendShortcut();
            const hasMod = e.metaKey || e.ctrlKey;
            if (shortcut === 'enter') {
                // Enter envía. Shift+Enter / Ctrl+Enter / Cmd+Enter = salto de línea
                if (e.shiftKey || hasMod) return;
                e.preventDefault();
                $(this).closest('.bv-composer').find('.btn-send').click();
            } else {
                // Ctrl/Cmd+Enter envía. Enter solo = salto de línea
                if (!hasMod) return;
                e.preventDefault();
                $(this).closest('.bv-composer').find('.btn-send').click();
            }
        });

        // Toggle del menú de atajo
        $(document).on('click', '#bv-send-config', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const $menu = $('#bv-send-menu');
            const willOpen = $menu.hasClass('bv-hidden');
            $menu.toggleClass('bv-hidden');
            $(this).attr('aria-expanded', willOpen ? 'true' : 'false');
        });

        // Selección de opción
        $(document).on('click', '.bv-send-menu-opt', function (e) {
            e.preventDefault();
            const value = $(this).data('bv-send-shortcut');
            localStorage.setItem(SEND_SHORTCUT_KEY, value);
            applySendShortcutUI();
            $('#bv-send-menu').addClass('bv-hidden');
            $('#bv-send-config').attr('aria-expanded', 'false');
            // Sin toast: el kbd del botón Enviar ya muestra el atajo activo
            $('.bv-composer-input').focus();
        });

        // Click fuera cierra el menú
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-send-menu, #bv-send-config').length) {
                $('#bv-send-menu').addClass('bv-hidden');
                $('#bv-send-config').attr('aria-expanded', 'false');
            }
        });

        // ─── Btn enviar (AJAX al endpoint storeMessage) ──────────────
        // UX-02: envío optimista. Pinta la burbuja al instante (bv-bubble--pending),
        // mantiene el textarea habilitado/enfocado, reconcilia en .done y ofrece
        // "Reintentar" en .fail. El eco por WebSocket de los mensajes propios ya se
        // descarta en index.blade.php (user_id === myId), por lo que la burbuja
        // optimista no se duplica; aun así reconcilePendingBubble protege por id.
        function sendMessage(opts) {
            const { text, isInternal, url, $btn, $pending } = opts;
            const $icon = $btn.find('i').first();
            const iconCls = $icon.attr('class');
            $btn.prop('disabled', true);
            if (iconCls) { $icon.attr('class', 'fas fa-spinner fa-spin'); }

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: {
                    body: text,
                    is_internal: isInternal ? 1 : 0,
                    action: 'send',
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            })
                .done(function (resp) {
                    reconcilePendingBubble($pending, resp?.item, isInternal);
                    $(document).trigger('bv:message:sent', resp?.item);
                    // Sin toast: el bubble que aparece en el thread es la confirmación visual
                })
                .fail(function (xhr) {
                    const msg = xhr?.responseJSON?.errors?.body?.[0]
                        || xhr?.responseJSON?.message
                        || 'No se pudo enviar el mensaje';
                    markPendingBubbleFailed($pending, { text, isInternal, url });
                    if (window.toastr) {
                        toastr.error(msg);
                    } else {
                        alert(msg);
                    }
                })
                .always(function () {
                    $btn.prop('disabled', false);
                    if (iconCls) { $icon.attr('class', iconCls); }
                });
        }

        $(document).on('click', '.btn-send', function () {
            const $btn = $(this);
            const $composer = $btn.closest('.bv-composer');
            const $textarea = $composer.find('.bv-composer-input');
            const text = $textarea.val().trim();
            const url = $composer.data('bv-send-url');
            if (!text || !url || $btn.prop('disabled')) return;

            const isInternal = $composer.find('.bv-composer-tab.on').data('bv-tab') === 'note';

            // UX-02: burbuja optimista + textarea vacío pero activo y enfocado.
            const $pending = appendPendingBubble(text, isInternal);
            $textarea.val('').css('height', 'auto').focus();

            sendMessage({ text, isInternal, url, $btn, $pending });
        });

        // UX-02: reintentar el envío de una burbuja fallida.
        $(document).on('click', '.bv-bubble-retry', function (e) {
            e.preventDefault();
            const $retry = $(this);
            const opts = $retry.data('retry');
            if (!opts) return;
            const $pending = $retry.closest('.bv-msg');
            const $bubble = $pending.find('.bv-bubble');
            $bubble.removeClass('bv-bubble--failed').addClass('bv-bubble--pending opacity-50');
            $retry.remove();
            $bubble.find('.meta span').first().text('Tú · Enviando…');
            if (!$bubble.find('.bv-pending-spin').length) {
                $bubble.find('.meta').append('<i class="fas fa-circle-notch fa-spin bv-pending-spin ms-1"></i>');
            }
            sendMessage({
                text: opts.text,
                isInternal: opts.isInternal,
                url: opts.url,
                $btn: $('.bv-composer .btn-send').first(),
                $pending,
            });
        });

        const escape = function (s) {
            return $('<div>').text(s ?? '').html();
        };

        function fileExtFromUrl(url) {
            const path = (url || '').split('?')[0].split('#')[0];
            const ext = path.split('.').pop() || '';
            return ext.toLowerCase();
        }

        function inferAttachType(meta, url) {
            const ext = fileExtFromUrl(url);
            const name = (meta?.name || url || '').split('/').pop().split('?')[0];
            // Facebook/Instagram widget voice recorder: voz-*.webm is always audio
            if (ext === 'webm' && name.startsWith('voz-')) return 'audio';
            if (meta?.type && meta.type !== 'video') return meta.type;
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'image';
            if (['mp4', 'mov'].includes(ext)) return 'video';
            if (['mp3', 'ogg', 'wav', 'oga', 'm4a', 'opus'].includes(ext)) return 'audio';
            // webm: prefer audio/webm mime over generic video classification
            if (ext === 'webm' && (meta?.mime || meta?.mime_type || '').startsWith('audio/')) return 'audio';
            if (ext === 'webm') return 'video';
            return 'document';
        }

        function buildWaveformHtml(url) {
            // Pseudo-aleatorio determinista para que las barras sean estables
            let seed = 0;
            for (let i = 0; i < (url || '').length; i++) seed = (seed * 31 + url.charCodeAt(i)) >>> 0;
            const rand = () => { seed = (seed * 1664525 + 1013904223) >>> 0; return seed / 0xFFFFFFFF; };
            let html = '';
            for (let b = 0; b < 32; b++) {
                const h = 25 + Math.round(rand() * 75);
                html += '<span class="bv-audio-bar" style="height:' + h + '%"></span>';
            }
            return html;
        }

        const docIconMap = {
            pdf: 'fa-file-pdf', doc: 'fa-file-word', docx: 'fa-file-word',
            xls: 'fa-file-excel', xlsx: 'fa-file-excel',
            ppt: 'fa-file-powerpoint', pptx: 'fa-file-powerpoint',
            zip: 'fa-file-zipper', csv: 'fa-file-csv', txt: 'fa-file-lines',
        };

        function buildAttachmentHtml(url, meta, ctx) {
            const type = inferAttachType(meta, url);
            const ext = fileExtFromUrl(url);
            const fileName = meta?.name || decodeURIComponent((url || '').split('/').pop() || 'archivo');
            const fileSize = meta?.size ? Math.round(meta.size / 1024) + ' KB' : '';
            // Use escapeHtml for URLs in HTML attributes (NOT escape() which mangles https: → https%3A)
            const safeUrl = escapeHtml(url);

            if (type === 'image') {
                return '<a href="' + safeUrl + '" class="bv-attach-thumb" data-bv-modal="file-preview" data-bv-preview-src="' + safeUrl + '" data-bv-preview-type="image">' +
                    '<img src="' + safeUrl + '" alt="' + escapeHtml(fileName) + '" loading="lazy" width="200" height="200">' +
                '</a>';
            }
            if (type === 'video') {
                return '<div class="bv-video-bubble">' +
                    '<video controls preload="metadata" class="bv-video-player">' +
                        '<source src="' + safeUrl + '">' +
                    '</video>' +
                '</div>';
            }
            if (type === 'audio') {
                const authorLabel = ctx?.authorLabel || 'Tú';
                const colorIdx = ctx?.colorIdx !== undefined ? ctx.colorIdx : 1;
                const initials = authorLabel.split(' ').slice(0, 2).map(w => (w || '').charAt(0).toUpperCase()).join('');
                return '<div class="bv-audio-msg" data-bv-audio-src="' + safeUrl + '">' +
                    '<div class="bv-audio-avatar bv-th-av-c' + colorIdx + '">' + escapeHtml(initials) + '<span class="bv-audio-mic"><i class="fas fa-microphone"></i></span></div>' +
                    '<button type="button" class="bv-audio-play" aria-label="Reproducir"><i class="fas fa-play"></i></button>' +
                    '<div class="bv-audio-wave" role="slider" tabindex="0" aria-label="Progreso del audio">' +
                        buildWaveformHtml(url) +
                        '<span class="bv-audio-progress-dot"></span>' +
                    '</div>' +
                    '<span class="bv-audio-time">0:00</span>' +
                    '<button type="button" class="bv-audio-speed" data-bv-speed="1" title="Velocidad">1x</button>' +
                    '<audio preload="metadata" class="bv-audio-el"><source src="' + safeUrl + '"></audio>' +
                '</div>';
            }
            const docIcon = docIconMap[ext] || 'fa-file';
            return '<a href="' + safeUrl + '" target="_blank" rel="noopener" class="bv-attach-file">' +
                '<i class="far ' + docIcon + '"></i>' +
                '<div class="bv-attach-file-info">' +
                    '<span class="bv-attach-file-name">' + escapeHtml(fileName) + '</span>' +
                    (fileSize ? '<span class="bv-attach-file-size">' + fileSize + '</span>' : '') +
                '</div>' +
            '</a>';
        }

        function renderBubbleEl(item, isInternal) {
            if (!item) return null;
            if (item.type === 'email_sent') return null;

            const isIncoming = !!item.is_incoming;
            const noteBadge = isInternal
                ? '<div class="note-badge"><i class="fas fa-lock"></i> Nota interna</div>'
                : '';
            const checkmark = !isInternal && !isIncoming
                ? '<span class="chk read bv-chk-read">✓✓</span>'
                : '';
            const bubbleClass = isInternal ? 'bv-bubble note' : 'bv-bubble';
            const msgClass = isInternal ? 'bv-msg in' : (isIncoming ? 'bv-msg in' : 'bv-msg out');

            let quotedHtml = '';
            if (item.reply_to) {
                quotedHtml = '<div class="bv-quoted-msg" data-bv-jump-to="' + (item.reply_to.id || '') + '">' +
                    '<div class="bv-quoted-author">' + escape(item.reply_to.author || '') + '</div>' +
                    '<div class="bv-quoted-body">' + escape(item.reply_to.body || '') + '</div>' +
                '</div>';
            }

            // Render attachments si los hay
            // attachment_urls puede ser:
            //   - array de strings (URL directos, formato legacy)
            //   - array de objetos {url, name, size, mime, mime_type, type, path}
            let attachmentsHtml = '';
            const urls = item.attachment_urls || [];
            const metas = item.attachments || (item.metadata?.attachments) || [];
            if (urls.length) {
                const authorStr = item.author || (isIncoming ? 'Cliente' : 'Tú');
                // Generate a stable colour index from author name (0–9) matching server-side logic
                let cIdx = 0;
                for (let ci = 0; ci < authorStr.length; ci++) cIdx += authorStr.charCodeAt(ci);
                cIdx = cIdx % 10;
                const attachCtx = { authorLabel: authorStr, colorIdx: cIdx };
                attachmentsHtml = '<div class="bv-attachment-gallery">';
                urls.forEach((u, i) => {
                    const url = (u && typeof u === 'object') ? (u.url || '') : u;
                    const inlineMeta = (u && typeof u === 'object') ? u : null;
                    attachmentsHtml += buildAttachmentHtml(url, inlineMeta || metas[i] || {}, attachCtx);
                });
                attachmentsHtml += '</div>';
            }

            // Link preview (OG metadata) — shown when the body contains a URL.
            const linkPreview = item.metadata?.link_preview || item.link_preview || null;
            let linkPreviewHtml = '';
            if (linkPreview && (linkPreview.title || linkPreview.description || linkPreview.image)) {
                const lpUrl = linkPreview.url || '';
                const lpImg = linkPreview.image
                    ? '<img src="' + escape(linkPreview.image) + '" alt="' + escape(linkPreview.title || '') + '" loading="lazy" class="bv-lp-img">'
                    : '';
                const lpTitle = linkPreview.title
                    ? '<p class="bv-lp-title">' + escape(linkPreview.title) + '</p>'
                    : '';
                const lpDesc = linkPreview.description
                    ? '<p class="bv-lp-desc">' + escape(linkPreview.description) + '</p>'
                    : '';
                const lpSite = linkPreview.site || (lpUrl ? (new URL(lpUrl).hostname || '') : '');
                linkPreviewHtml =
                    '<a href="' + escape(lpUrl) + '" target="_blank" rel="noopener noreferrer" class="bv-link-preview">' +
                        lpImg +
                        '<div class="bv-lp-body">' +
                            lpTitle +
                            lpDesc +
                            '<div class="bv-lp-meta">' +
                                '<i class="fas fa-link"></i>' +
                                '<span>' + escape(lpSite) + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</a>';
            }

            // Hide the body when it is *only* the URL that has already been
            // unfurled into a preview card — clicking the card already opens it.
            const trimmedBody = (item.body || '').trim();
            const previewUrl = (linkPreview?.url || '').trim();
            const isJustTheUrl = previewUrl && (
                trimmedBody === previewUrl ||
                trimmedBody.replace(/\/$/, '') === previewUrl.replace(/\/$/, '')
            );
            const bodyHtml = (item.body && !isJustTheUrl)
                ? escape(item.body)
                    .replace(/\n/g, '<br>')
                    // Auto-linkify URLs so visitor messages with raw URLs become clickable.
                    .replace(/(https?:\/\/[^\s<>"']+)/gi, (full) => {
                        let url = full;
                        let trail = '';
                        while (/[.,;:!?)\]]$/.test(url)) {
                            trail = url.slice(-1) + trail;
                            url = url.slice(0, -1);
                        }
                        return '<a href="' + url + '" target="_blank" rel="noopener noreferrer" class="bv-msg-link">' + url + '</a>' + trail;
                    })
                    .replace(/(^|\s|>)@([\p{L}0-9._-]+)/gu, (full, prefix, handle) => {
                        return prefix + '<span class="bv-mention-chip" data-bv-mention-handle="' + escape(handle) + '">@' + escape(handle) + '</span>';
                    })
                : '';

            // Data attributes mirror the server-rendered bubbles so the
            // context-menu (right-click) can discover the body, author,
            // internal/out flags etc. — without these, the "Traducir" entry
            // disappears for messages that arrived via WebSocket.
            const rawBody = (item.body || '').toString();
            const dataAttrs =
                ' data-bv-item-id="' + escape(item.id || '') + '"' +
                ' data-bv-author="' + escape(item.author || '') + '"' +
                ' data-bv-is-internal="' + (isInternal ? '1' : '0') + '"' +
                ' data-bv-is-out="' + (isIncoming ? '0' : '1') + '"' +
                ' data-bv-body="' + escape(rawBody) + '"' +
                ' data-bv-body-preview="' + escape(rawBody.slice(0, 80)) + '"';

            const outgoing = (item.outgoing_translated_body || '').toString();
            const outTrHtml = (!isInternal && !isIncoming && outgoing)
                ? '<div class="bv-bubble-translation bv-outgoing-translation"' +
                  ' data-bv-original="' + escape(rawBody) + '"' +
                  ' data-bv-translated="' + escape(outgoing) + '">' +
                  '<span class="bv-bubble-translation-lbl">&#8627; </span>' +
                  escape(outgoing) +
                  ' <button type="button" class="bv-bubble-translation-toggle" title="Ver original"><i class="fas fa-arrows-rotate"></i></button>' +
                  '</div>'
                : '';

            // WhatsApp: media received but not yet downloaded (media_id present, no attachment_urls)
            const meta = item.metadata || {};
            let pendingHtml = '';
            if (!attachmentsHtml && meta.media_id && meta.message_type) {
                const pendingIcons = { image: 'fa-image', audio: 'fa-microphone', video: 'fa-video', document: 'fa-file', sticker: 'fa-face-smile', voice: 'fa-microphone' };
                const pendingLabels = { image: 'Imagen', audio: 'Audio', video: 'Video', document: 'Documento', sticker: 'Sticker', voice: 'Audio' };
                pendingHtml = '<div class="bv-media-pending text-muted"><i class="fas ' + (pendingIcons[meta.message_type] || 'fa-paperclip') + ' me-1"></i>' +
                    escapeHtml(pendingLabels[meta.message_type] || meta.message_type) + '</div>';
            }

            // WhatsApp contact card
            let contactHtml = '';
            if (item.type === 'contact' && meta.name) {
                contactHtml = '<div class="bv-contact-card">' +
                    '<div class="bv-contact-card-icon"><i class="far fa-address-card"></i></div>' +
                    '<div class="bv-contact-card-info">' +
                    '<div class="bv-contact-card-name">' + escapeHtml(meta.name) + '</div>' +
                    (meta.phone ? '<div class="bv-contact-card-detail"><i class="fas fa-phone"></i> ' + escapeHtml(meta.phone) + '</div>' : '') +
                    (meta.email ? '<div class="bv-contact-card-detail"><i class="far fa-envelope"></i> ' + escapeHtml(meta.email) + '</div>' : '') +
                    '</div></div>';
            }

            // WhatsApp location
            let locationHtml = '';
            if (item.type === 'location' && meta.lat) {
                const lat = meta.lat, lng = meta.lng;
                const mapUrl = 'https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lng + '&zoom=15';
                const mapImg = 'https://staticmap.openstreetmap.de/staticmap.php?center=' + lat + ',' + lng + '&zoom=14&size=280x140&markers=' + lat + ',' + lng + ',red';
                locationHtml = '<div class="bv-location-bubble">' +
                    '<a href="' + escapeHtml(mapUrl) + '" target="_blank" rel="noopener" class="bv-location-map-link">' +
                    '<img src="' + escapeHtml(mapImg) + '" alt="Mapa" loading="lazy" class="bv-location-map-img" width="280" height="140"></a>' +
                    '<div class="bv-location-address"><i class="fas fa-location-dot"></i><span>' + escapeHtml(meta.address || lat + ', ' + lng) + '</span></div>' +
                    '</div>';
            }

            const $bubble = $(
                '<div class="' + msgClass + '">' +
                    '<div class="' + bubbleClass + '"' + dataAttrs + '>' +
                        noteBadge +
                        quotedHtml +
                        bodyHtml +
                        linkPreviewHtml +
                        attachmentsHtml +
                        pendingHtml +
                        contactHtml +
                        locationHtml +
                        outTrHtml +
                        '<div class="meta">' +
                            '<span>' + escape(item.author || 'Tú') + ' · ' + escape(item.time || '') + '</span>' +
                            checkmark +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            return $bubble;
        }

        function appendBubbleToThread(item, isInternal) {
            const $inner = $('.bv-th-inner');
            if (!$inner.length) return;
            const $bubble = renderBubbleEl(item, isInternal);
            if (!$bubble) return;
            // If bubble already exists (re-broadcast after media download), replace it
            const $existing = $inner.find('.bv-bubble[data-bv-item-id="' + escape(item.id || '') + '"]').closest('.bv-msg');
            if ($existing.length) {
                $existing.replaceWith($bubble);
            } else {
                $inner.append($bubble);
            }
            scrollThreadToBottom(true);
        }
        window.appendBubbleToThread = appendBubbleToThread;

        // ─── UX-02: burbujas optimistas (pending / reconciliación / fallo) ───
        function appendPendingBubble(text, isInternal) {
            const $inner = $('.bv-th-inner');
            if (!$inner.length) return $();
            const tempId = 'bv-pending-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
            const $msg = renderBubbleEl({
                id: tempId,
                body: text,
                author: 'Tú',
                time: 'Enviando…',
                is_incoming: false,
            }, isInternal);
            if (!$msg || !$msg.length) return $();
            $msg.find('.bv-bubble').addClass('bv-bubble--pending opacity-50').attr('data-bv-pending', '1');
            $msg.find('.bv-chk-read, .chk').remove();
            $msg.find('.meta').append('<i class="fas fa-circle-notch fa-spin bv-pending-spin ms-1"></i>');
            $inner.append($msg);
            scrollThreadToBottom(true);
            return $msg;
        }

        function reconcilePendingBubble($pending, item, isInternal) {
            if (!$pending || !$pending.length) {
                if (item) appendBubbleToThread(item, isInternal);
                return;
            }
            if (!item) { $pending.remove(); return; }
            const realId = (item.id == null ? '' : String(item.id));
            // Si el mensaje real ya llegó (p.ej. por WebSocket), descartar el placeholder.
            const $already = $('.bv-th-inner .bv-bubble[data-bv-item-id="' + escape(realId) + '"]').not('[data-bv-pending]');
            if (realId && $already.length) {
                $pending.remove();
                return;
            }
            const $real = renderBubbleEl(item, isInternal);
            if ($real && $real.length) { $pending.replaceWith($real); }
            else { $pending.remove(); }
        }

        function markPendingBubbleFailed($pending, retryOpts) {
            if (!$pending || !$pending.length) return;
            const $bubble = $pending.find('.bv-bubble');
            $bubble.removeClass('opacity-50 bv-bubble--pending').addClass('bv-bubble--failed');
            $bubble.find('.bv-pending-spin').remove();
            const $meta = $bubble.find('.meta');
            $meta.find('span').first().text('No se pudo enviar');
            if (!$meta.find('.bv-bubble-retry').length) {
                const $retry = $('<button type="button" class="bv-bubble-retry btn btn-sm btn-link p-0 ms-1 text-danger"><i class="fas fa-rotate-right me-1"></i>Reintentar</button>');
                $retry.data('retry', retryOpts);
                $meta.append($retry);
            }
        }

        // ─── perf-04: cargar mensajes anteriores (paginación hacia arriba) ───
        function loadOlderItems($btn) {
            if ($btn.data('loading')) { return; }
            const url = $btn.data('url');
            const before = $btn.data('oldest-id');
            if (!url || !before) { return; }

            $btn.data('loading', true);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Cargando…');

            const $body = $('.bv-th-body');
            const prevHeight = $body.length ? $body[0].scrollHeight : 0;
            const prevTop = $body.length ? $body[0].scrollTop : 0;

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                data: { before: before },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (resp) {
                const items = (resp && resp.items) || [];
                const $wrap = $btn.closest('.bv-load-older');

                if (!items.length) {
                    $wrap.remove();
                    return;
                }

                // Los items llegan en orden ascendente (el primero es el más antiguo).
                const els = [];
                items.forEach(function (it) {
                    const $b = renderBubbleEl(it, !!it.is_internal);
                    if ($b) { els.push($b[0]); }
                });
                if (items[0] && items[0].id) { $btn.data('oldest-id', items[0].id); }
                $wrap.after(els);

                // Mantener la posición de scroll tras el prepend.
                if ($body.length) {
                    $body[0].scrollTop = prevTop + ($body[0].scrollHeight - prevHeight);
                }

                if (!resp.has_more) { $wrap.remove(); }
            }).fail(function (xhr) {
                // Endpoint aún no disponible o sin acceso → degradar sin romper.
                if (xhr.status === 404 || xhr.status === 405) {
                    $btn.closest('.bv-load-older').remove();
                    return;
                }
                if (window.toastr) { toastr.error('No se pudieron cargar los mensajes anteriores'); }
            }).always(function () {
                $btn.data('loading', false).prop('disabled', false).html(originalHtml);
            });
        }

        $(document).on('click', '#bv-load-older .bv-load-older-btn', function () {
            loadOlderItems($(this));
        });

        // Click en bubble citado → scroll al original con flash highlight
        $(document).on('click', '.bv-quoted-msg', function () {
            const id = $(this).data('bv-jump-to');
            if (!id) return;
            const $target = $('.bv-bubble[data-bv-item-id="' + id + '"]');
            if (!$target.length) return;
            $target.closest('.bv-msg')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            $target.addClass('bv-bubble-flash');
            setTimeout(() => $target.removeClass('bv-bubble-flash'), 1500);
        });

        // ─── Auto-resize textarea ────────────────────────────────────
        $(document).on('input', '.bv-composer-input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(160, this.scrollHeight) + 'px';
        });

        // ─── Bulk select (checkbox) ──────────────────────────────────
        $(document).on('change', '[data-bv-bulk-select]', function () {
            $(this).closest('.bv-conv').toggleClass('selected', this.checked);
        });

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
                method: 'PUT',
                dataType: 'json',
                data: payload,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
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

        // ─── Event: message added from external modal (e.g. note modal) ─
        $(document).on('bv:message:added', function (e, item, isInternal) {
            appendBubbleToThread(item, isInternal);
        });

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
                .done(function (resp) {
                    updateThreadPill('status', 'Cerrada', 'muted', resp);
                    closeModal($modal);
                    // Remove closed conversation from list
                    $('.bv-conv.on').fadeOut(300, function () { $(this).remove(); });
                    // Swap Close → Reopen button in thread header
                    const reopenUrl = urls.updateUrl.replace(/\/?$/, '/reopen');
                    $('[data-bv-modal="close-conv"]').replaceWith(
                        '<button class="bv-th-action bv-th-action--reopen" id="bv-btn-reopen"' +
                        ' data-bv-tip="Reabrir conversación" data-reopen-url="' + reopenUrl + '">' +
                        '<i class="fas fa-rotate-left"></i></button>'
                    );
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
                method: 'PUT',
                dataType: 'json',
                traditional: true,
                data: { tag_ids: tagIds },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
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

        // ─── Slash menu: canned replies ───────────────────────────────
        const slashMenu = (function () {
            let $menu = null;
            let $textarea = null;
            let selectedIndex = -1;
            let items = [];
            let debounceTimer = null;
            const searchUrl = (window.bvCannedRepliesUrl || '/panel/helpdesk/canned-replies/search');

            function buildMenu() {
                if ($('#bv-slash-menu').length) {
                    $menu = $('#bv-slash-menu');
                    return;
                }
                $menu = $('<div id="bv-slash-menu" class="bv-slash-menu" style="display:none"></div>');
                $('body').append($menu);
            }

            function getSlashQuery(ta) {
                const val = ta.value;
                const pos = ta.selectionStart;
                const lineStart = val.lastIndexOf('\n', pos - 1) + 1;
                const lineText = val.substring(lineStart, pos);
                if (!lineText.startsWith('/')) return null;
                return lineText.substring(1);
            }

            function positionMenu(ta) {
                const rect = ta.getBoundingClientRect();
                $menu.css({
                    top: rect.top + window.scrollY - $menu.outerHeight() - 4,
                    left: rect.left + window.scrollX,
                    width: Math.min(420, rect.width),
                });
            }

            function renderItems() {
                $menu.empty();
                if (!items.length) {
                    $menu.hide();
                    return;
                }
                items.forEach(function (item, idx) {
                    const preview = (item.body || '').substring(0, 60).replace(/\n/g, ' ');
                    const $row = $(
                        '<div class="bv-slash-item" data-idx="' + idx + '">' +
                            '<div class="bv-slash-meta">' +
                                (item.shortcut ? '<span class="bv-slash-shortcut">/' + $('<div>').text(item.shortcut).html() + '</span>' : '') +
                                '<span class="bv-slash-name">' + $('<div>').text(item.name).html() + '</span>' +
                            '</div>' +
                            '<div class="bv-slash-preview">' + $('<div>').text(preview).html() + '</div>' +
                        '</div>'
                    );
                    $menu.append($row);
                });
                setSelected(0);
                positionMenu($textarea[0]);
                $menu.show();
            }

            function setSelected(idx) {
                selectedIndex = Math.max(0, Math.min(items.length - 1, idx));
                $menu.find('.bv-slash-item').removeClass('on').eq(selectedIndex).addClass('on');
            }

            function insertReply(item) {
                if (!$textarea || !$textarea.length) return;
                const ta = $textarea[0];
                const val = ta.value;
                const pos = ta.selectionStart;
                const lineStart = val.lastIndexOf('\n', pos - 1) + 1;
                const before = val.substring(0, lineStart);
                const after = val.substring(pos);
                const body = item.body || '';
                ta.value = before + body + after;
                const newPos = before.length + body.length;
                ta.setSelectionRange(newPos, newPos);
                $textarea.trigger('input');
                close();
                $textarea.focus();
            }

            function close() {
                if ($menu) $menu.hide();
                items = [];
                selectedIndex = -1;
            }

            function search(q) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    $.ajax({
                        url: searchUrl,
                        method: 'GET',
                        dataType: 'json',
                        data: { q: q },
                        headers: { 'Accept': 'application/json' },
                    })
                        .done(function (data) {
                            items = Array.isArray(data) ? data : [];
                            renderItems();
                        })
                        .fail(function () {
                            close();
                        });
                }, 200);
            }

            function init() {
                buildMenu();

                $(document).on('input', '.bv-composer-input', function () {
                    $textarea = $(this);
                    const q = getSlashQuery(this);
                    if (q === null) {
                        close();
                        return;
                    }
                    search(q);
                });

                $(document).on('keydown', '.bv-composer-input', function (e) {
                    if (!$menu || !$menu.is(':visible')) return;

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        setSelected(selectedIndex + 1);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        setSelected(selectedIndex - 1);
                    } else if (e.key === 'Enter') {
                        if (items[selectedIndex]) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            insertReply(items[selectedIndex]);
                        }
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        close();
                    }
                });

                $(document).on('click', '.bv-slash-item', function () {
                    const idx = parseInt($(this).data('idx'), 10);
                    if (items[idx]) {
                        insertReply(items[idx]);
                    }
                });

                $(document).on('click', function (e) {
                    if ($menu && !$(e.target).closest('.bv-slash-menu, .bv-composer-input').length) {
                        close();
                    }
                });
            }

            return { init: init };
        })();

        slashMenu.init();

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

        function ajaxConversationUpdate(payload, successMsg) {
            const convId = getCurrentConversationId();
            if (!convId) {
                if (window.toastr) toastr.error('No hay conversación seleccionada');
                return Promise.reject('no-conv');
            }
            return $.ajax({
                url: '/panel/helpdesk/conversations/' + convId,
                method: 'PUT',
                dataType: 'json',
                data: payload,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
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
            if (!id) {
                if (window.toastr) toastr.warning('Selecciona un estado');
                return;
            }
            ajaxConversationUpdate({ status_id: id }, 'Estado actualizado').done(() => {
                closeNamedModal('status');
                const $pill = $('.bv-th-pill').filter(function () { return $(this).attr('data-bv-modal') === 'status'; });
                $pill.find('.dot').attr('class', 'dot').css('background', color || '#6c757d');
                const txt = ' ' + (label || '') + ' ';
                $pill.contents().filter(function () { return this.nodeType === 3; }).first().replaceWith(txt);
                setRightPanelPill('status', label, color);
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
            ajaxConversationUpdate({ priority: value }, 'Prioridad actualizada').done(() => {
                closeNamedModal('priority');
                const $pill = $('.bv-th-pill').filter(function () { return $(this).attr('data-bv-modal') === 'priority'; }).first();
                $pill.find('.dot').attr('class', 'dot bv-dot-' + (color || 'muted'));
                const txt = ' ' + (label || '') + ' ';
                $pill.contents().filter(function () { return this.nodeType === 3; }).first().replaceWith(txt);
                $pill.attr('data-bv-value', value);
                setRightPanelPill('priority', label, color);
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
            if (teamId !== undefined && teamId !== '') {
                payload = { group_id: teamId };
                msg = 'Conversación movida al equipo';
            } else {
                const agentId = $selected.data('agent-id');
                payload = { assignee_id: agentId === '' ? null : agentId };
                msg = agentId === '' ? 'Asignación eliminada' : 'Conversación asignada';
            }
            ajaxConversationUpdate(payload, msg).done(() => closeNamedModal('assign'));
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
                method: 'PUT',
                dataType: 'json',
                data: { group_id: teamId },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            })
                .done(function () {
                    $(`.bv-conv[data-bv-conv-id="${convId}"]`).fadeOut(300, function () { $(this).remove(); });
                })
                .fail(function () {
                    if (window.toastr) toastr.error('No se pudo mover la conversación');
                });
        });

        // ─── Panel Traducir: "Activar traducción" ────────────────────
        $(document).on('click', '#bv-translate-panel .bv-panel-btn-confirm', function () {
            const mode = $('.bv-tp-mode.on').data('mode') || 'incoming';
            const from = $('#bv-tp-from').val() || 'auto';
            const to = $('#bv-tp-to').val() || 'es';

            sessionStorage.setItem('inbox_translation_settings', JSON.stringify({ mode, from, to }));

            $('#bv-translate-panel').removeClass('on');
            activateReplyTab();

            if (mode === 'incoming' || mode === 'both') {
                translateAllIncomingBubbles(to);
            }
        });

        // ─── Traducción de burbuja (reusable desde context menu) ────────
        function translateBubble($bubble, text, overrideTo) {
            if (!$bubble || !$bubble.length || !text) return;
            if ($bubble.find('.bv-bubble-translation').length) return; // ya traducida
            const settings = JSON.parse(sessionStorage.getItem('inbox_translation_settings') || '{}');
            const to = overrideTo || settings.to || 'es';
            const from = settings.from || 'auto';

            $bubble.append('<div class="bv-bubble-translation bv-bubble-translation--loading"><span>traduciendo…</span></div>');

            // Prefer the per-item endpoint when the bubble carries an item-id
            // — that endpoint persists translated_body on the conversation
            // item, so the translation survives reloads and any other agent
            // opening the conversation sees it.
            const itemId = $bubble.data('bv-item-id');
            const usePersistent = !!itemId;
            const url = usePersistent
                ? '/panel/helpdesk/conversations/items/' + itemId + '/translate'
                : '/panel/helpdesk/translate';
            const data = usePersistent ? { target: to } : { text: text, from: from, to: to };

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            })
                .done(function (resp) {
                    $bubble.find('.bv-bubble-translation--loading').remove();
                    const $tr = $('<div class="bv-bubble-translation"></div>');
                    $tr.attr('data-bv-original', text);
                    $tr.attr('data-bv-translated', resp.translated);
                    $tr.append('<span class="bv-bubble-translation-lbl">&#8627; </span>');
                    $tr.append(document.createTextNode(resp.translated));
                    $tr.append(' <button type="button" class="bv-bubble-translation-toggle" title="Ver original"><i class="fas fa-arrows-rotate"></i></button>');
                    $bubble.append($tr);
                })
                .fail(function () {
                    $bubble.find('.bv-bubble-translation--loading').remove();
                    if (window.toastr) {
                        toastr.error('No se pudo traducir');
                    }
                });
        }

        // Toggle "Ver original" en la traducción de un mensaje.
        $(document).on('click', '.bv-bubble-translation-toggle', function (e) {
            e.stopPropagation();
            const $tr = $(this).closest('.bv-bubble-translation');
            const original = $tr.data('bv-original') || '';
            const translated = $tr.data('bv-translated') || '';
            const showingOriginal = $tr.hasClass('showing-original');
            const $lbl = $tr.find('.bv-bubble-translation-lbl');
            // Rebuild the visible text node — keep label and toggle button.
            $tr.contents().filter(function () { return this.nodeType === 3; }).remove();
            $lbl.after(document.createTextNode(showingOriginal ? translated : original));
            $tr.toggleClass('showing-original');
            $(this).attr('title', showingOriginal ? 'Ver original' : 'Ver traducción');
        });

        // Traducir todas las burbujas entrantes (botón "Traducir todas" del panel)
        function translateAllIncomingBubbles(to) {
            $('.bv-msg.in .bv-bubble').each(function () {
                const $bubble = $(this);
                if ($bubble.find('.bv-bubble-translation').length) return;
                const text = ($bubble.data('bv-body') || '').toString();
                if (text) translateBubble($bubble, text, to);
            });
        }

        // ─── Context menu de burbujas (click derecho, estilo WhatsApp) ──
        function buildBubbleMenuItems($bubble) {
            const isInternal = String($bubble.data('bv-is-internal') || '0') === '1';
            const isOut = String($bubble.data('bv-is-out') || '0') === '1';
            const body = ($bubble.data('bv-body') || '').toString();
            const hasBody = body.length > 0;
            const alreadyTranslated = $bubble.find('.bv-bubble-translation').length > 0;

            const items = [];
            if (hasBody) {
                items.push({ icon: 'fas fa-reply', label: 'Responder', action: 'reply' });
            }
            items.push({ icon: 'far fa-face-smile', label: 'Reaccionar', action: 'react' });
            if (hasBody) {
                items.push({ icon: 'far fa-copy', label: 'Copiar texto', action: 'copy' });
            }
            if (hasBody && !isInternal && !alreadyTranslated) {
                items.push({ icon: 'fas fa-language', label: 'Traducir', action: 'translate' });
            }
            items.push({ icon: 'fas fa-share', label: 'Reenviar', action: 'forward' });
            items.push({ icon: 'fas fa-circle-info', label: 'Info del mensaje', action: 'info' });
            if (isOut) {
                items.push({ icon: 'far fa-trash-can', label: 'Eliminar', action: 'delete', danger: true });
            }
            return items;
        }

        function openBubbleMenu($bubble, x, y) {
            $('#bv-bubble-menu').remove();
            const items = buildBubbleMenuItems($bubble);
            if (!items.length) return;

            const itemsHtml = items.map((it, i) => (
                '<button type="button" class="bv-bubble-menu-item' + (it.danger ? ' is-danger' : '') + '" data-bv-action="' + it.action + '" data-bv-idx="' + i + '">' +
                    '<i class="' + it.icon + '"></i><span>' + it.label + '</span>' +
                '</button>'
            )).join('');

            const $menu = $('<div id="bv-bubble-menu" class="bv-bubble-menu" role="menu">' + itemsHtml + '</div>');
            $('body').append($menu);
            $menu.data('bubble', $bubble);

            // Posicionar con clamp al viewport
            const w = $menu.outerWidth();
            const h = $menu.outerHeight();
            let left = x;
            let top = y;
            if (left + w > window.innerWidth - 8) left = window.innerWidth - w - 8;
            if (top + h > window.innerHeight - 8) top = window.innerHeight - h - 8;
            if (left < 8) left = 8;
            if (top < 8) top = 8;
            $menu.css({ left: left + 'px', top: top + 'px' });
        }

        function closeBubbleMenu() {
            $('#bv-bubble-menu').remove();
        }

        // Abre con click derecho sobre el bubble
        $(document).on('contextmenu', '.bv-bubble', function (e) {
            // Si el click derecho fue sobre un link/botón interno, dejar el menú nativo
            if ($(e.target).closest('a, button, .bv-mention-chip').length) return;
            e.preventDefault();
            openBubbleMenu($(this), e.clientX, e.clientY);
        });

        // En desktop también accesible con long-press (no estorba)
        let bubbleLongPressTimer = null;
        $(document).on('touchstart', '.bv-bubble', function (e) {
            const $bubble = $(this);
            const t = e.originalEvent.touches[0];
            bubbleLongPressTimer = setTimeout(() => openBubbleMenu($bubble, t.clientX, t.clientY), 500);
        });
        $(document).on('touchend touchmove', '.bv-bubble', function () {
            clearTimeout(bubbleLongPressTimer);
        });

        // Click fuera o ESC cierra
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-bubble-menu').length) {
                closeBubbleMenu();
            }
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') closeBubbleMenu();
        });

        // Acción de un item
        $(document).on('click', '.bv-bubble-menu-item', function () {
            const action = $(this).data('bv-action');
            const $menu = $(this).closest('#bv-bubble-menu');
            const $bubble = $menu.data('bubble');
            closeBubbleMenu();
            if (!$bubble || !$bubble.length) return;

            const body = ($bubble.data('bv-body') || '').toString();
            const id = $bubble.data('bv-item-id');
            const author = $bubble.data('bv-author') || '';
            const preview = ($bubble.data('bv-body-preview') || body.slice(0, 80)).toString();

            switch (action) {
                case 'reply':
                    $(document).trigger('bv:set-reply', { id, author, body: preview });
                    break;
                case 'translate':
                    translateBubble($bubble, body);
                    break;
                case 'copy':
                    if (navigator.clipboard && body) {
                        navigator.clipboard.writeText(body).catch(() => {});
                        if (window.toastr) toastr.success('Texto copiado');
                    }
                    break;
                case 'react':
                    openReactionPicker($bubble, id);
                    break;
                case 'forward':
                    openMessageForwardModal($bubble, id, body, preview);
                    break;
                case 'info':
                    openMessageInfoModal($bubble, id);
                    break;
                case 'delete':
                    if (window.toastr) toastr.warning('La eliminación de mensajes no está habilitada');
                    break;
            }
        });

        // ─── Reaction picker ─────────────────────────────────────────────
        const REACTION_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '🙏', '🔥', '👏'];

        function openReactionPicker($bubble, itemId) {
            $('#bv-reaction-picker').remove();
            const buttons = REACTION_EMOJIS.map(e =>
                '<button type="button" class="bv-reaction-btn" data-bv-emoji="' + e + '">' + e + '</button>'
            ).join('');
            const $picker = $('<div id="bv-reaction-picker" class="bv-reaction-picker">' + buttons + '</div>');
            $('body').append($picker);

            const r = $bubble[0].getBoundingClientRect();
            const w = $picker.outerWidth();
            const h = $picker.outerHeight();
            let left = r.left + (r.width - w) / 2;
            let top = r.top - h - 8;
            if (top < 8) top = r.bottom + 8;
            if (left < 8) left = 8;
            if (left + w > window.innerWidth - 8) left = window.innerWidth - w - 8;
            $picker.css({ left: left + 'px', top: top + 'px' });

            const closer = (ev) => {
                if (!$(ev.target).closest('#bv-reaction-picker').length) {
                    $picker.remove();
                    document.removeEventListener('click', closer, true);
                }
            };
            setTimeout(() => document.addEventListener('click', closer, true), 0);

            $picker.on('click', '.bv-reaction-btn', function () {
                const emoji = $(this).data('bv-emoji');
                $picker.remove();
                document.removeEventListener('click', closer, true);

                $.ajax({
                    url: '/panel/helpdesk/messages/' + itemId + '/react',
                    method: 'POST',
                    dataType: 'json',
                    data: { emoji },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                }).fail(function (xhr) {
                    if (window.toastr) {
                        const msg = xhr?.responseJSON?.message || 'No se pudo registrar la reacción';
                        toastr.error(msg);
                    }
                });
            });
        }

        // ─── Forward modal (reenviar mensaje a otro cliente) ─────────────
        // Nota: existe otro `openForwardModal` más abajo para attachments —
        // este se llama distinto para no chocar con el otro en el closure.
        function openMessageForwardModal($bubble, itemId, body, preview) {
            $('#bv-msg-forward-modal').remove();
            $('#bv-forward-modal').remove();
            const previewText = preview || (body || '').slice(0, 120) || '(sin texto)';
            const $modal = $(
                '<div id="bv-msg-forward-modal" class="bv-modal on" role="dialog" aria-modal="true">' +
                    '<div class="bv-modal-dialog">' +
                        '<div class="bv-modal-head">' +
                            '<div class="bv-modal-title"><i class="fas fa-share"></i> Reenviar mensaje</div>' +
                            '<button type="button" class="bv-modal-close" aria-label="Cerrar">' +
                                '<i class="fas fa-xmark"></i>' +
                            '</button>' +
                        '</div>' +
                        '<div class="bv-modal-body">' +
                            '<div class="bv-fwd-preview">' + escape(previewText) + '</div>' +
                            '<label class="bv-modal-label">Buscar conversación o cliente</label>' +
                            '<input type="text" id="bv-fwd-search" class="bv-modal-input" placeholder="Nombre, email o asunto…" autocomplete="off">' +
                            '<div id="bv-fwd-results" class="bv-fwd-results"></div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $('body').append($modal);
            setTimeout(() => $modal.find('#bv-fwd-search').trigger('focus'), 50);

            const close = () => $modal.remove();
            $modal.on('click', '.bv-modal-close, .bv-modal', function (ev) {
                if (ev.target === this) close();
            });

            let searchTimer = null;
            $modal.on('input', '#bv-fwd-search', function () {
                const q = $(this).val();
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    if (!q || q.length < 2) {
                        $('#bv-fwd-results').html('<div class="bv-fwd-hint">Escribe al menos 2 caracteres…</div>');
                        return;
                    }
                    $('#bv-fwd-results').html('<div class="bv-fwd-hint">Buscando…</div>');
                    $.ajax({
                        url: '/panel/helpdesk/customers/search',
                        method: 'GET',
                        data: { q, limit: 8 },
                        dataType: 'json',
                        headers: { 'Accept': 'application/json' },
                    }).done(function (resp) {
                        const list = resp?.data || resp?.customers || resp || [];
                        if (!Array.isArray(list) || !list.length) {
                            $('#bv-fwd-results').html('<div class="bv-fwd-hint">Sin resultados</div>');
                            return;
                        }
                        const html = list.map(c => (
                            '<button type="button" class="bv-fwd-result" data-bv-customer-id="' + (c.id || '') + '">' +
                                '<div class="bv-fwd-name">' + escape(c.name || c.firstname || c.email || 'Sin nombre') + '</div>' +
                                '<div class="bv-fwd-email">' + escape(c.email || c.phone || '') + '</div>' +
                            '</button>'
                        )).join('');
                        $('#bv-fwd-results').html(html);
                    }).fail(function () {
                        $('#bv-fwd-results').html('<div class="bv-fwd-hint is-error">Error al buscar</div>');
                    });
                }, 250);
            });

            $modal.on('click', '.bv-fwd-result', function () {
                const customerId = $(this).data('bv-customer-id');
                if (!customerId) return;
                $.ajax({
                    url: '/panel/helpdesk/messages/' + itemId + '/forward',
                    method: 'POST',
                    dataType: 'json',
                    data: { customer_id: customerId },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                }).done(function (resp) {
                    close();
                    if (window.toastr) toastr.success(resp?.message || 'Mensaje reenviado');
                }).fail(function (xhr) {
                    const msg = xhr?.responseJSON?.message || 'No se pudo reenviar';
                    if (window.toastr) toastr.error(msg);
                });
            });
        }

        // ─── Message info modal ──────────────────────────────────────────
        function openMessageInfoModal($bubble, itemId) {
            $('#bv-msg-info-modal').remove();
            const $modal = $(
                '<div id="bv-msg-info-modal" class="bv-modal on" role="dialog" aria-modal="true">' +
                    '<div class="bv-modal-dialog">' +
                        '<div class="bv-modal-head">' +
                            '<div class="bv-modal-title"><i class="fas fa-circle-info"></i> Información del mensaje</div>' +
                            '<button type="button" class="bv-modal-close" aria-label="Cerrar">' +
                                '<i class="fas fa-xmark"></i>' +
                            '</button>' +
                        '</div>' +
                        '<div class="bv-modal-body" id="bv-msg-info-body">' +
                            '<div class="bv-fwd-hint">Cargando…</div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $('body').append($modal);
            $modal.on('click', '.bv-modal-close, .bv-modal', function (ev) {
                if (ev.target === this) $modal.remove();
            });

            $.ajax({
                url: '/panel/helpdesk/messages/' + itemId + '/info',
                method: 'GET',
                dataType: 'json',
                headers: { 'Accept': 'application/json' },
            }).done(function (resp) {
                const d = resp?.data || resp || {};
                const fmt = (s) => s ? new Date(s).toLocaleString() : '—';
                const rows = [
                    ['Enviado', fmt(d.sent_at || d.created_at)],
                    ['Entregado al cliente', fmt(d.delivered_at || d.customer_delivered_at)],
                    ['Leído por el cliente', fmt(d.read_at || d.customer_read_at)],
                    ['Autor', d.author_name || d.sender_name || '—'],
                    ['Canal', d.channel || '—'],
                    ['ID externo', d.external_id || '—'],
                ];
                const html = rows.map(([k, v]) => (
                    '<div class="bv-msg-info-row">' +
                        '<div class="bv-msg-info-label">' + escape(k) + '</div>' +
                        '<div class="bv-msg-info-value">' + escape(String(v)) + '</div>' +
                    '</div>'
                )).join('');
                $('#bv-msg-info-body').html(html);
            }).fail(function (xhr) {
                const msg = xhr?.responseJSON?.message || 'No se pudo cargar la información';
                $('#bv-msg-info-body').html('<div class="bv-fwd-hint is-error">' + escape(msg) + '</div>');
            });
        }

        // ─── Attach menu actions (document/image/audio/video/contact/location)
        const attachAcceptMap = {
            document: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip',
            image: 'image/*',
            audio: 'audio/*',
            video: 'video/*',
        };

        function ensureFilePicker() {
            if (!document.getElementById('bv-file-picker')) {
                $('body').append('<input type="file" id="bv-file-picker" hidden multiple>');
            }
            return document.getElementById('bv-file-picker');
        }

        function appendOptimisticUploadBubble(files) {
            const $inner = $('.bv-th-inner');
            if (!$inner.length) return null;
            const placeholders = Array.from(files).map(f => {
                const isImg = (f.type || '').startsWith('image/');
                const preview = isImg ? URL.createObjectURL(f) : null;
                const fname = f.name || 'archivo';
                const fsize = f.size ? Math.round(f.size / 1024) + ' KB' : '';
                if (preview) {
                    return '<div class="bv-attach-placeholder" style="background:transparent;padding:0;min-height:auto">' +
                        '<img src="' + preview + '" alt="' + escape(fname) + '" style="width:200px;border-radius:8px;opacity:0.6">' +
                        '<div class="bv-attach-placeholder-spinner"></div>' +
                    '</div>';
                }
                return '<div class="bv-attach-placeholder">' +
                    '<div class="bv-attach-placeholder-spinner"></div>' +
                    '<div>' + escape(fname) + (fsize ? ' · ' + fsize : '') + '</div>' +
                '</div>';
            }).join('');

            const $bubble = $(
                '<div class="bv-msg out" data-bv-optimistic="1">' +
                    '<div class="bv-bubble">' +
                        '<div class="bv-attachment-gallery">' + placeholders + '</div>' +
                        '<div class="meta">' +
                            '<span>Tú · subiendo…</span>' +
                            '<span class="chk read bv-chk-read" style="opacity:0.4">⏳</span>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $inner.append($bubble);
            scrollThreadToBottom(true);
            return $bubble;
        }

        async function uploadFiles(files) {
            const convId = $('.bv-composer').data('bv-conversation-id');
            if (!convId || !files || !files.length) return;

            const fd = new FormData();
            for (const f of files) fd.append('files[]', f);

            $('#bv-upload-progress').removeClass('bv-hidden');
            $('#bv-upload-bar').css('width', '20%');
            const $optimistic = appendOptimisticUploadBubble(files);

            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/conversations/' + convId + '/attachments',
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                    xhr: function () {
                        const x = new window.XMLHttpRequest();
                        x.upload.addEventListener('progress', function (e) {
                            if (e.lengthComputable) {
                                const pct = Math.round((e.loaded / e.total) * 90);
                                $('#bv-upload-bar').css('width', pct + '%');
                            }
                        }, false);
                        return x;
                    },
                });
                $('#bv-upload-bar').css('width', '100%');
                if ($optimistic) $optimistic.remove();
                if (resp?.item && typeof window.appendBubbleToThread === 'function') {
                    window.appendBubbleToThread(resp.item, false);
                }
                setTimeout(() => {
                    $('#bv-upload-progress').addClass('bv-hidden');
                    $('#bv-upload-bar').css('width', '0%');
                }, 600);
            } catch (xhr) {
                $('#bv-upload-progress').addClass('bv-hidden');
                if ($optimistic) $optimistic.remove();
                const msg = xhr?.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors)[0]?.[0]
                    : (xhr?.responseJSON?.message || 'No se pudo subir el archivo');
                if (window.toastr) toastr.error(msg);
            }
        }

        $(document).on('click', '[data-bv-attach-type]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const type = $(this).data('bv-attach-type');
            $('#bv-attach-menu').removeClass('on');

            if (type === 'store') {
                // El click ya abre el modal store-picker via data-bv-modal — no hacer nada extra
                return;
            }

            if (type === 'contact' || type === 'location') {
                // Legacy types — ahora ocultos por la opción Tienda
                return;
            }

            if (type === 'record') {
                startVoiceRecording();
                return;
            }

            const accept = attachAcceptMap[type] || '';
            const picker = ensureFilePicker();
            picker.setAttribute('accept', accept);
            picker.value = '';
            picker.click();
        });

        // ─── Voice recorder ───────────────────────────────────────────
        let mediaRecorder = null;
        let audioChunks = [];
        let recorderCancelled = false;
        let recordTimer = null;
        let recordSeconds = 0;

        function showRecorderUI() {
            if (document.getElementById('bv-recorder-bar')) return;
            const $bar = $(
                '<div id="bv-recorder-bar" class="bv-recorder-bar">' +
                    '<span class="bv-recorder-pulse"></span>' +
                    '<span class="bv-recorder-time" id="bv-recorder-time">0:00</span>' +
                    '<button class="bv-recorder-stop" id="bv-recorder-stop"><i class="fas fa-stop"></i> Enviar</button>' +
                    '<button class="bv-recorder-cancel" id="bv-recorder-cancel"><i class="fas fa-xmark"></i> Cancelar</button>' +
                '</div>'
            );
            $('.bv-composer').prepend($bar);
        }

        function hideRecorderUI() {
            $('#bv-recorder-bar').remove();
            clearInterval(recordTimer);
            recordTimer = null;
            recordSeconds = 0;
        }

        function tickRecorderTime() {
            recordSeconds++;
            const m = Math.floor(recordSeconds / 60);
            const s = String(recordSeconds % 60).padStart(2, '0');
            $('#bv-recorder-time').text(m + ':' + s);
        }

        async function startVoiceRecording() {
            if (!navigator.mediaDevices?.getUserMedia) {
                if (window.toastr) toastr.error('Tu navegador no soporta grabación de audio. Usa "Subir audio" para enviar un archivo.');
                return;
            }

            // No pre-check con permissions.query: en Chrome puede estar desincronizado.
            // getUserMedia es la fuente real de verdad: si hay permiso, abre el stream;
            // si no, lanza NotAllowedError y mostramos el helper.
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                audioChunks = [];
                recorderCancelled = false;
                mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
                mediaRecorder.ondataavailable = e => {
                    if (recorderCancelled) return;
                    audioChunks.push(e.data);
                };
                mediaRecorder.onstop = async () => {
                    stream.getTracks().forEach(t => t.stop());
                    if (!recorderCancelled && audioChunks.length) {
                        const blob = new Blob(audioChunks, { type: 'audio/webm' });
                        const file = new File([blob], 'voz-' + Date.now() + '.webm', { type: 'audio/webm' });
                        await uploadFiles([file]);
                    }
                    audioChunks = [];
                    hideRecorderUI();
                };
                showRecorderUI();
                recordSeconds = 0;
                $('#bv-recorder-time').text('0:00');
                recordTimer = setInterval(tickRecorderTime, 1000);
                mediaRecorder.start();
            } catch (err) {
                console.error('[bv-mic] getUserMedia error:', err.name, err.message, err);
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    showMicDeniedHelp(err);
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    if (window.toastr) toastr.error('No se detectó ningún micrófono conectado');
                } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                    if (window.toastr) toastr.error('El micrófono está siendo usado por otra aplicación. Cierra Zoom/Meet/etc. y reintenta.', '', { timeOut: 8000 });
                } else if (err.name === 'SecurityError') {
                    if (window.toastr) toastr.error('Error de seguridad: el micrófono solo funciona en HTTPS o localhost.', '', { timeOut: 8000 });
                } else {
                    if (window.toastr) toastr.error('Error al acceder al micrófono (' + err.name + '): ' + err.message, '', { timeOut: 8000 });
                }
            }
        }

        async function showMicDeniedHelp(err) {
            const origin = window.location.origin;
            const isSecure = window.isSecureContext;

            // Resolve real permission state up front so the modal renders the right guidance
            let permState = 'unknown';
            try {
                if (navigator.permissions) {
                    const p = await navigator.permissions.query({ name: 'microphone' });
                    permState = p.state; // 'granted' | 'denied' | 'prompt'
                }
            } catch (e) { /* unsupported */ }

            // Detect "labels empty" — confirms the OS/browser is hiding device identity
            let labelsHidden = false;
            try {
                const list = await navigator.mediaDevices.enumerateDevices();
                labelsHidden = list.some(d => d.kind === 'audioinput' && !d.label);
            } catch (e) {}

            const isMac = /Mac|iPhone|iPad/.test(navigator.platform);
            const browserName = (() => {
                const ua = navigator.userAgent;
                if (/Edg\//.test(ua)) return 'Microsoft Edge';
                if (/Chrome\//.test(ua)) return 'Google Chrome';
                if (/Firefox\//.test(ua)) return 'Firefox';
                if (/Safari\//.test(ua)) return 'Safari';
                return 'tu navegador';
            })();

            // When state==='denied' but the site UI shows permitted, this is the classic
            // sticky-deny + macOS override scenario. Make it explicit.
            const stickyDenyNotice = permState === 'denied' ? (
                '<div class=”perm-notice-denied”>' +
                    '<strong>⚠️ Activar el switch del sitio NO es suficiente</strong><br>' +
                    'El navegador reporta <code>permission: denied</code>. El switch per-site guarda preferencias ' +
                    'pero <strong>no resetea el estado runtime</strong> que el navegador cachea tras un bloqueo previo. ' +
                    'Sigue los pasos de abajo en orden.' +
                '</div>'
            ) : '';

            const macInstructions = isMac ? (
                '<div class=”perm-step”>' +
                    '<div class=”h”><span class=”num”>1</span> Permitir el micrófono en macOS</div>' +
                    '<ul>' +
                        '<li>Abre <b>Ajustes del sistema</b> → Privacidad y seguridad → Micrófono</li>' +
                        '<li>Activa el switch para <b>' + browserName + '</b></li>' +
                        '<li>Si ya estaba activo, <b>desactívalo y vuelve a activarlo</b></li>' +
                        '<li>Cierra completamente ' + browserName + ' (<b>Cmd+Q</b>) y reábrelo</li>' +
                    '</ul>' +
                    '<button type=”button” class=”btn btn-secondary btn-sm” id=”bv-mic-open-mac-prefs” style=”align-self:flex-start”>Abrir Ajustes del sistema</button>' +
                '</div>'
            ) : '';

            const browserResetSection =
                '<div class=”perm-step”>' +
                    '<div class=”h”><span class=”num”>' + (isMac ? '2' : '1') + '</span> Resetear el permiso del sitio</div>' +
                    '<div class=”hint”>Esta es la forma <b>rápida</b> que sí funciona: usa “Restablecer permisos”, no toques el switch del micrófono.</div>' +
                    '<ul>' +
                        '<li>Click en el icono <i class=”fas fa-lock” style=”font-size:9px”></i> a la izquierda de <code style=”background:#e5e7eb;padding:1px 4px;border-radius:3px;font-family:monospace;font-size:10.5px”>' + origin + '</code> en la barra de direcciones</li>' +
                        '<li>Click en <b>”Restablecer permisos”</b> (al final del popup)</li>' +
                        '<li><b>Recarga la página</b> (Cmd+R)</li>' +
                        '<li>Vuelve a hacer click en el botón del micrófono → ahora saldrá el prompt nativo</li>' +
                        '<li>Click en <b>Permitir</b></li>' +
                    '</ul>' +
                '</div>' +
                '<div class=”perm-step”>' +
                    '<div class=”h”><span class=”num”>' + (isMac ? '3' : '2') + '</span> Si Chrome tiene bloqueado el micrófono globalmente</div>' +
                    '<div style=”font-size:11.5px;color:#6b7280;line-height:1.55”>Pega en una pestaña nueva:</div>' +
                    '<div class=”perm-url”>' +
                        '<span>chrome://settings/content/microphone</span>' +
                        '<button class=”copy” data-bv-copy=”chrome://settings/content/microphone” title=”Copiar”><i class=”far fa-copy”></i></button>' +
                    '</div>' +
                    '<ul>' +
                        '<li>En “<b>Comportamiento predeterminado</b>” verifica que esté “Los sitios pueden pedirte usar tu micrófono”</li>' +
                        '<li>En “<b>No permitir</b>” busca <code style=”background:#e5e7eb;padding:1px 4px;border-radius:3px;font-family:monospace;font-size:10.5px”>' + origin + '</code> y elimínalo si aparece</li>' +
                    '</ul>' +
                '</div>';

            const diag = '<div class=”perm-diag”>' +
                '<div class=”h”>Diagnóstico</div>' +
                '<div>Origen: <code>' + origin + '</code></div>' +
                '<div>Contexto seguro: <code>' + (isSecure ? 'sí' : 'NO ❌') + '</code></div>' +
                '<div>Permiso (Permissions API): <code>' + permState + '</code></div>' +
                '<div>Etiquetas de dispositivo ocultas: <code>' + (labelsHidden ? 'sí (sin acceso real)' : 'no') + '</code></div>' +
                (err ? '<div>Error: <code>' + (err.name || 'Error') + ': ' + (err.message || '') + '</code></div>' : '') +
                '</div>';

            // El botón “Reintentar sin recargar” sólo funciona si el estado fue resetado a “prompt”.
            // Cuando es “denied” estable, NO produce re-prompt — lo ocultamos para no confundir.
            const retryBtn = permState !== 'denied'
                ? '<button class=”btn btn-secondary w-100” id=”bv-mic-denied-retry”>Reintentar sin recargar</button>'
                : '';

            const $modal = $(
                '<div id=”bv-mic-denied” class=”hd-overlay” style=”z-index:99999;padding-top:5vh”>' +
                    '<div class=”hd-modal w-md” style=”max-height:90vh;display:flex;flex-direction:column”>' +
                        '<div class=”modal-head”>' +
                            '<div class=”modal-icon” style=”background:#f3f4f6;color:#374151”><i class=”fas fa-microphone-slash”></i></div>' +
                            '<div class=”modal-title-wrap”>' +
                                '<span class=”modal-label”>PERMISO · NAVEGADOR</span>' +
                                '<span class=”modal-title”>No se pudo acceder al micrófono</span>' +
                            '</div>' +
                            '<button class=”modal-close” id=”bv-mic-denied-close”><i class=”fas fa-xmark”></i></button>' +
                        '</div>' +
                        '<div class=”modal-body” style=”overflow-y:auto;flex:1”>' +
                            '<div class=”perm-hero”>' +
                                '<div class=”ic-circle”><i class=”fas fa-microphone-slash”></i></div>' +
                                '<div class=”t”>No se pudo acceder al micrófono</div>' +
                            '</div>' +
                            stickyDenyNotice +
                            macInstructions +
                            browserResetSection +
                            diag +
                        '</div>' +
                        '<div class=”modal-foot modal-foot--stack”>' +
                            '<button class=”btn btn-primary w-100” id=”bv-mic-denied-reload”>Recargar página</button>' +
                            retryBtn +
                            '<button class=”btn btn-secondary w-100” id=”bv-mic-denied-close2”>Salir sin micrófono</button>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $('body').append($modal);
        }

        // Copy-to-clipboard helper para el chrome://settings/...
        $(document).on('click', '.bv-mic-denied-copy, .bv-mic-denied-url, .perm-url .copy, .perm-url span', async function () {
            const text = $(this).data('bv-copy');
            if (!text) return;
            try {
                await navigator.clipboard.writeText(text);
            } catch (e) {
            }
        });

        $(document).on('click', '#bv-mic-denied-reload', function () {
            window.location.reload();
        });

        $(document).on('click', '#bv-mic-open-mac-prefs', function () {
            // El esquema x-apple.systempreferences solo funciona desde Safari; igualmente lo intentamos.
            window.location.href = 'x-apple.systempreferences:com.apple.preference.security?Privacy_Microphone';
        });

        $(document).on('click', '#bv-mic-denied-close, #bv-mic-denied-close2, .hd-overlay#bv-mic-denied', function (e) {
            if (e.target.id === 'bv-mic-denied-close' || e.target.id === 'bv-mic-denied-close2' || e.target.id === 'bv-mic-denied') {
                $('#bv-mic-denied').remove();
            }
        });

        $(document).on('click', '#bv-mic-denied-upload', function () {
            $('#bv-mic-denied').remove();
            const picker = ensureFilePicker();
            picker.setAttribute('accept', 'audio/*');
            picker.value = '';
            picker.click();
        });

        // Retry mic permission — fuerza re-prompt del navegador (solo funciona si el user reseteó el block)
        $(document).on('click', '#bv-mic-denied-retry', async function () {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                stream.getTracks().forEach(t => t.stop());
                $('#bv-mic-denied').remove();
            } catch (e) {
                if (window.toastr) {
                    toastr.error('Sigue bloqueado. Usa el icono 🔒 en la URL para permitirlo manualmente.', '', { timeOut: 8000 });
                }
            }
        });

        // ─── Store picker: enviar tienda como mensaje ────────────────
        $(document).on('click', '.bv-store-item', async function () {
            const $btn = $(this);
            const data = {
                name: $btn.data('bv-store-name'),
                address: $btn.data('bv-store-address'),
                phone: $btn.data('bv-store-phone'),
                email: $btn.data('bv-store-email'),
            };
            const lines = ['🏪 *' + data.name + '*'];
            if (data.address) lines.push('📍 ' + data.address);
            if (data.phone) lines.push('📞 ' + data.phone);
            if (data.email) lines.push('✉️ ' + data.email);
            const body = lines.join('\n');

            const convId = $('.bv-composer').data('bv-conversation-id');
            if (!convId) return;

            $btn.prop('disabled', true);
            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/conversations/' + convId + '/messages',
                    method: 'POST',
                    dataType: 'json',
                    data: { body, action: 'send' },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                });
                // Sin toast: el bubble aparece en el thread como confirmación visual
                $('[data-bv-modal-name="store-picker"]').removeClass('on');
                $('body').css('overflow', '');
                if (resp?.item && typeof window.appendBubbleToThread === 'function') {
                    window.appendBubbleToThread(resp.item, false);
                }
            } catch (e) {
                if (window.toastr) toastr.error('No se pudo compartir la tienda');
            } finally {
                $btn.prop('disabled', false);
            }
        });

        // Search dentro del modal store-picker
        $(document).on('input', '#bv-store-search-input', function () {
            const q = $(this).val().toLowerCase().trim();
            $('.bv-store-item').each(function () {
                const text = ($(this).text() || '').toLowerCase();
                $(this).toggle(!q || text.includes(q));
            });
        });

        $(document).on('click', '#bv-recorder-stop', function () {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            }
        });

        $(document).on('click', '#bv-recorder-cancel', function () {
            recorderCancelled = true;
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            } else {
                hideRecorderUI();
            }
        });

        $(document).on('change', '#bv-file-picker', function (e) {
            const files = e.target.files;
            if (files && files.length) uploadFiles(files);
        });

        // Drag & drop files into composer
        $(document).on('dragover', '.bv-composer-input, .bv-composer-box', function (e) {
            e.preventDefault();
            $(this).addClass('bv-dragover');
        });
        $(document).on('dragleave drop', '.bv-composer-input, .bv-composer-box', function (e) {
            $(this).removeClass('bv-dragover');
        });
        $(document).on('drop', '.bv-composer-input, .bv-composer-box', function (e) {
            e.preventDefault();
            const files = e.originalEvent.dataTransfer?.files;
            if (files && files.length) uploadFiles(files);
        });

        // ─── Emoji picker ─────────────────────────────────────────────
        const emojiCategories = {
            smileys: ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😉','😊','😇','🥰','😍','🤩','😘','🤗','🤔','😎'],
            reactions: ['👍','👎','❤️','🔥','🎉','💯','👏','🙏','🤝','💪','✅','❌','⚠️','⭐','💡','📌','✨','💬','📞','🚀'],
            objects: ['📞','📱','💻','📧','📅','📎','📂','📦','🛍️','🏷️','💳','💰','🎁','📈','📊','🗓️','⏰','⏳','📍','🌍'],
        };

        function buildEmojiPicker() {
            if (document.getElementById('bv-emoji-picker')) return;
            const $p = $('<div id="bv-emoji-picker" class="bv-emoji-picker bv-hidden"></div>');
            const $tabs = $('<div class="bv-emoji-tabs"></div>');
            const $grid = $('<div class="bv-emoji-grid"></div>');
            ['smileys', 'reactions', 'objects'].forEach((cat, i) => {
                const labelMap = { smileys: '😀', reactions: '👍', objects: '📦' };
                $tabs.append(
                    $('<button class="bv-emoji-tab"></button>')
                        .text(labelMap[cat])
                        .attr('data-bv-emoji-cat', cat)
                        .toggleClass('on', i === 0)
                );
            });
            renderEmojiGrid($grid, 'smileys');
            $p.append($tabs).append($grid);
            $('body').append($p);
        }

        function renderEmojiGrid($grid, cat) {
            $grid.empty();
            (emojiCategories[cat] || []).forEach(em => {
                $grid.append($('<button class="bv-emoji-cell"></button>').text(em).attr('data-bv-emoji', em));
            });
        }

        function positionEmojiPicker($trigger) {
            const $p = $('#bv-emoji-picker');
            const rect = $trigger[0].getBoundingClientRect();
            const pickerW = 280;
            const pickerH = $p.outerHeight() || 280;
            // Render encima del botón; si no cabe arriba, debajo
            const fitsAbove = rect.top - 8 >= pickerH;
            const top = fitsAbove ? rect.top - pickerH - 6 : rect.bottom + 6;
            // Alinea por la derecha del botón pero clamp al viewport
            let left = rect.right - pickerW;
            if (left < 8) left = 8;
            if (left + pickerW > window.innerWidth - 8) left = window.innerWidth - pickerW - 8;
            $p.css({
                position: 'fixed',
                top: top + 'px',
                left: left + 'px',
                zIndex: 9999,
            });
        }

        $(document).on('click', '#bv-btn-emoji', function (e) {
            e.preventDefault();
            e.stopPropagation();
            buildEmojiPicker();
            const $p = $('#bv-emoji-picker');
            if ($p.hasClass('bv-hidden')) {
                positionEmojiPicker($(this));
                $p.removeClass('bv-hidden');
            } else {
                $p.addClass('bv-hidden');
            }
        });

        $(document).on('click', '.bv-emoji-tab', function () {
            const cat = $(this).data('bv-emoji-cat');
            $('.bv-emoji-tab').removeClass('on');
            $(this).addClass('on');
            renderEmojiGrid($('.bv-emoji-grid'), cat);
        });

        $(document).on('click', '.bv-emoji-cell', function () {
            const em = $(this).data('bv-emoji');
            const $ta = $('.bv-composer-input').first();
            const ta = $ta[0];
            const start = ta.selectionStart || 0;
            const end = ta.selectionEnd || 0;
            const v = $ta.val();
            $ta.val(v.slice(0, start) + em + v.slice(end));
            ta.selectionStart = ta.selectionEnd = start + em.length;
            ta.focus();
            $('#bv-emoji-picker').addClass('bv-hidden');
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-emoji-picker, #bv-btn-emoji').length) {
                $('#bv-emoji-picker').addClass('bv-hidden');
            }
        });

        // ─── Mention picker (@) ──────────────────────────────────────
        // Trigger: click en bv-btn-mention (inserta '@' y abre menú) o tipear '@' en el textarea.
        let mentionAnchor = null; // posición del '@' que disparó el menú (selectionStart al momento)
        let mentionItems = []; // unificado: [{type:'special'|'agent'|'team', handle, label, sub, ...}]
        let mentionSelectedIdx = 0;
        let mentionFetchTimer = null;
        let mentionFetchSeq = 0;

        const MENTION_SPECIALS = [
            { type: 'special', handle: 'all',  label: '@all',  sub: 'Notifica a todos los agentes',                  icon: 'fas fa-globe' },
            { type: 'special', handle: 'here', label: '@here', sub: 'Solo a quien está conectado ahora',             icon: 'fas fa-bolt' },
            { type: 'special', handle: 'team', label: '@team', sub: 'Al equipo asignado a esta conversación',        icon: 'fas fa-users-rectangle' },
        ];

        function buildMentionMenu() {
            if (document.getElementById('bv-mention-menu')) return;
            const $m = $(
                '<div id="bv-mention-menu" class="bv-mention-menu bv-hidden" role="listbox">' +
                    '<div class="bv-mention-list"></div>' +
                    '<div class="bv-mention-foot">' +
                        '<span><kbd>↑</kbd><kbd>↓</kbd> navegar</span>' +
                        '<span><kbd>↵</kbd> insertar</span>' +
                        '<span><kbd>Esc</kbd> cerrar</span>' +
                    '</div>' +
                '</div>'
            );
            $('body').append($m);
        }

        function positionMentionMenu($trigger) {
            const $m = $('#bv-mention-menu');
            const rect = $trigger[0].getBoundingClientRect();
            const menuH = $m.outerHeight() || 280;
            const menuW = 320;
            const fitsAbove = rect.top - 8 >= menuH;
            // Posiciona arriba o abajo del textarea, alineado al borde izquierdo
            const top = fitsAbove ? rect.top - menuH - 6 : rect.bottom + 6;
            let left = rect.left;
            if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
            if (left < 8) left = 8;
            $m.css({
                position: 'fixed',
                top: top + 'px',
                left: left + 'px',
                width: menuW + 'px',
                zIndex: 9999,
            });
        }

        function renderMentionItemHtml(item, idx, selected) {
            const esc = (s) => $('<i>').text(s == null ? '' : String(s)).html();
            const cls = 'bv-mention-row' + (selected ? ' on' : '');

            if (item.type === 'special') {
                return '<button type="button" class="' + cls + '" role="option" data-bv-mention-idx="' + idx + '">' +
                    '<span class="bv-mention-av is-special"><i class="' + item.icon + '"></i></span>' +
                    '<span class="bv-mention-body">' +
                        '<span class="bv-mention-name">' + esc(item.label) + '</span>' +
                        '<span class="bv-mention-meta">' + esc(item.sub) + '</span>' +
                    '</span>' +
                    '<span class="bv-mention-tag">Especial</span>' +
                '</button>';
            }

            if (item.type === 'team') {
                const t = item.data;
                const members = parseInt(t.members_count || 0, 10);
                return '<button type="button" class="' + cls + '" role="option" data-bv-mention-idx="' + idx + '">' +
                    '<span class="bv-mention-av is-team"><i class="fas fa-users"></i></span>' +
                    '<span class="bv-mention-body">' +
                        '<span class="bv-mention-name">' + esc(t.name) + '</span>' +
                        '<span class="bv-mention-meta">@' + esc(t.key) + ' · ' + members + (members === 1 ? ' miembro' : ' miembros') + '</span>' +
                    '</span>' +
                    '<span class="bv-mention-tag">Equipo</span>' +
                '</button>';
            }

            // Agent
            const a = item.data;
            const colorIdx = ((a.id || 0) % 6) + 1;
            const statusClass = a.status || (a.online ? 'online' : 'offline');
            return '<button type="button" class="' + cls + '" role="option" data-bv-mention-idx="' + idx + '">' +
                '<span class="bv-av c' + colorIdx + ' bv-mention-av-bv">' + esc(a.initials || '?') +
                    '<span class="bv-av-dot ' + statusClass + '"></span></span>' +
                '<span class="bv-mention-body">' +
                    '<span class="bv-mention-name">' + esc(a.name) +
                        (a.role ? '<span class="bv-mention-role-badge">' + esc(a.role) + '</span>' : '') +
                    '</span>' +
                    '<span class="bv-mention-meta">@' + esc(a.username) + ' · ' + esc(a.status_label || (a.online ? 'En línea' : 'Offline')) + '</span>' +
                '</span>' +
            '</button>';
        }

        function renderMentionList(payload, term) {
            const $list = $('#bv-mention-menu .bv-mention-list').empty();
            mentionItems = [];
            mentionSelectedIdx = 0;
            const t = (term || '').toLowerCase();

            // Especiales (filtrar por handle)
            const specials = MENTION_SPECIALS.filter(s => !t || s.handle.startsWith(t));

            // Agentes (vienen ya filtrados del server)
            const agents = (payload?.agents || []).map(a => ({ type: 'agent', handle: a.username, data: a }));

            // Equipos (vienen ya filtrados del server)
            const teams = (payload?.teams || []).map(g => ({ type: 'team', handle: g.key, data: g }));

            const sections = [
                { title: 'Especiales', items: specials.map(s => ({ ...s })) },
                { title: 'Agentes',    items: agents },
                { title: 'Equipos',    items: teams },
            ].filter(s => s.items.length > 0);

            if (sections.length === 0) {
                $list.append('<div class="bv-mention-empty">Sin resultados para "' + $('<i>').text(t).html() + '"</div>');
                return;
            }

            let idx = 0;
            sections.forEach(section => {
                $list.append('<div class="bv-mention-section">' + section.title + '</div>');
                section.items.forEach(item => {
                    mentionItems.push(item);
                    $list.append(renderMentionItemHtml(item, idx, idx === 0));
                    idx++;
                });
            });
        }

        async function fetchAgentsForMention(term) {
            const seq = ++mentionFetchSeq;
            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/api/agents-autocomplete',
                    method: 'GET',
                    data: { q: term || '' },
                    dataType: 'json',
                });
                if (seq !== mentionFetchSeq) return; // outdated response
                renderMentionList(resp || {}, term);
            } catch (e) {
                if (seq !== mentionFetchSeq) return;
                renderMentionList({}, term);
            }
        }

        function openMentionMenu($trigger, term) {
            buildMentionMenu();
            const $m = $('#bv-mention-menu');
            $m.removeClass('bv-hidden');
            positionMentionMenu($trigger);
            clearTimeout(mentionFetchTimer);
            mentionFetchTimer = setTimeout(() => fetchAgentsForMention(term), 120);
        }

        function closeMentionMenu() {
            $('#bv-mention-menu').addClass('bv-hidden');
            mentionAnchor = null;
            mentionItems = [];
        }

        function insertMentionAt($ta, anchorPos, item) {
            const ta = $ta[0];
            const v = $ta.val();
            // Reemplaza desde el '@' hasta la posición actual del cursor por '@handle '
            const cursor = ta.selectionStart || v.length;
            const before = v.slice(0, anchorPos);
            const after = v.slice(cursor);
            const handle = item.type === 'agent' ? item.data.username
                          : item.type === 'team' ? item.data.key
                          : item.handle; // special
            const replacement = '@' + handle + ' ';
            $ta.val(before + replacement + after);
            const newPos = before.length + replacement.length;
            ta.selectionStart = ta.selectionEnd = newPos;
            ta.focus();
            ta.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // ─── Popover info al click en .bv-mention-chip ─────────────────
        // Cachea la respuesta del agentes-autocomplete para resolver el handle
        // y muestra una mini-card con avatar, nombre, status y acciones rápidas.
        let mentionPopoverCache = null;
        let mentionPopoverFetchPromise = null;

        function ensureMentionPopoverData() {
            if (mentionPopoverCache !== null) return Promise.resolve(mentionPopoverCache);
            if (mentionPopoverFetchPromise) return mentionPopoverFetchPromise;
            mentionPopoverFetchPromise = $.ajax({
                url: '/panel/helpdesk/api/agents-autocomplete',
                method: 'GET',
                data: { q: '' },
                dataType: 'json',
            }).then(resp => {
                mentionPopoverCache = resp || { agents: [], teams: [] };
                mentionPopoverFetchPromise = null;
                return mentionPopoverCache;
            }).catch(() => {
                mentionPopoverFetchPromise = null;
                return { agents: [], teams: [] };
            });
            return mentionPopoverFetchPromise;
        }

        function buildMentionPopoverHtml(handle, data) {
            const esc = (s) => $('<i>').text(s == null ? '' : String(s)).html();
            const lower = handle.toLowerCase();

            // Especiales
            const specialMap = {
                all: { title: 'Todos los agentes', sub: 'Notifica a todos los miembros del workspace', icon: 'fas fa-globe' },
                here: { title: 'Agentes en línea', sub: 'Solo notifica a los que están conectados ahora', icon: 'fas fa-bolt' },
                team: { title: 'Equipo de la conversación', sub: 'Notifica al grupo asignado a esta conversación', icon: 'fas fa-users-rectangle' },
            };
            if (specialMap[lower]) {
                const s = specialMap[lower];
                return '<div class="bv-mention-pop-row">' +
                    '<div class="bv-mention-pop-av special"><i class="' + s.icon + '"></i></div>' +
                    '<div class="bv-mention-pop-body">' +
                        '<div class="bv-mention-pop-name">@' + esc(lower) + '</div>' +
                        '<div class="bv-mention-pop-sub">' + esc(s.title) + '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="bv-mention-pop-desc">' + esc(s.sub) + '</div>';
            }

            // Equipo (key)
            const team = (data?.teams || []).find(t => (t.key || '').toLowerCase() === lower);
            if (team) {
                return '<div class="bv-mention-pop-row">' +
                    '<div class="bv-mention-pop-av team"><i class="fas fa-users"></i></div>' +
                    '<div class="bv-mention-pop-body">' +
                        '<div class="bv-mention-pop-name">' + esc(team.name) + '</div>' +
                        '<div class="bv-mention-pop-sub">@' + esc(team.key) + ' · ' + (team.members_count || 0) + ' miembros</div>' +
                    '</div>' +
                '</div>' +
                (team.description ? '<div class="bv-mention-pop-desc">' + esc(team.description) + '</div>' : '');
            }

            // Agente (username)
            const agent = (data?.agents || []).find(a => (a.username || '').toLowerCase() === lower);
            if (agent) {
                const colorIdx = ((agent.id || 0) % 6) + 1;
                const statusClass = agent.status || (agent.online ? 'online' : 'offline');
                return '<div class="bv-mention-pop-row">' +
                    '<div class="bv-av c' + colorIdx + ' bv-mention-pop-av-bv">' + esc(agent.initials || '?') +
                        '<span class="bv-av-dot ' + statusClass + '"></span></div>' +
                    '<div class="bv-mention-pop-body">' +
                        '<div class="bv-mention-pop-name">' + esc(agent.name) +
                            (agent.role ? '<span class="bv-mention-role-badge">' + esc(agent.role) + '</span>' : '') +
                        '</div>' +
                        '<div class="bv-mention-pop-sub">' + esc(agent.status_label || (agent.online ? 'En línea' : 'Offline')) +
                            (agent.email ? ' · ' + esc(agent.email) : '') + '</div>' +
                    '</div>' +
                '</div>';
            }

            // Sin resolver
            return '<div class="bv-mention-pop-row">' +
                '<div class="bv-mention-pop-av special"><i class="fas fa-question"></i></div>' +
                '<div class="bv-mention-pop-body">' +
                    '<div class="bv-mention-pop-name">@' + esc(handle) + '</div>' +
                    '<div class="bv-mention-pop-sub">Mención sin resolver</div>' +
                '</div>' +
            '</div>';
        }

        function positionMentionPopover($trigger) {
            const $p = $('#bv-mention-popover');
            const rect = $trigger[0].getBoundingClientRect();
            const w = $p.outerWidth() || 280;
            const h = $p.outerHeight() || 140;
            const fitsAbove = rect.top - 8 >= h;
            const top = fitsAbove ? rect.top - h - 8 : rect.bottom + 8;
            let left = rect.left + (rect.width / 2) - (w / 2);
            if (left < 8) left = 8;
            if (left + w > window.innerWidth - 8) left = window.innerWidth - w - 8;
            $p.css({ position: 'fixed', top: top + 'px', left: left + 'px', zIndex: 9999 });
        }

        $(document).on('click', '.bv-mention-chip', async function (e) {
            e.preventDefault();
            e.stopPropagation();
            const handle = $(this).data('bv-mention-handle');
            if (!handle) return;
            const $trigger = $(this);

            $('#bv-mention-popover').remove();
            const $pop = $('<div id="bv-mention-popover" class="bv-mention-popover"><div class="bv-mention-pop-loading">Cargando…</div></div>');
            $('body').append($pop);
            positionMentionPopover($trigger);

            const data = await ensureMentionPopoverData();
            $pop.html(buildMentionPopoverHtml(String(handle), data));
            positionMentionPopover($trigger);
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-mention-popover, .bv-mention-chip').length) {
                $('#bv-mention-popover').remove();
            }
        });

        // ─── Modal de mención (abre por data-bv-modal="mention") ──────
        // Soporta dos tabs: Agentes y Equipos. El dropdown inline al tipear @
        // sigue usándose para flujo rápido (solo agentes).
        let mentionModalSelected = null; // { type: 'agent'|'team', data: {...} }
        let mentionModalAgents = [];
        let mentionModalTeams = [];
        let mentionModalFetchSeq = 0;
        let mentionActiveTab = 'agents';

        function renderMentionAgentRow(a, i) {
            const esc = (s) => $('<i>').text(s == null ? '' : String(s)).html();
            const colorIdx = ((a.id || 0) % 6) + 1;
            const cur = parseInt(a.workload_current || 0, 10);
            const max = parseInt(a.workload_max || 15, 10);
            const ratio = max > 0 ? Math.min(1, cur / max) : 0;
            const barColor = ratio >= 1 ? 'danger' : (ratio >= 0.7 ? 'warning' : 'success');
            const statusClass = a.status || (a.online ? 'online' : 'offline');
            const skills = (a.skills || []).filter(Boolean);
            const subParts = [esc(a.status_label || (a.online ? 'En línea' : 'Offline'))];
            if (skills.length) subParts.push(esc(skills.join(', ')));
            return $(
                '<button type="button" class="bv-opt bv-mention-opt" role="option" data-bv-mention-type="agent" data-bv-mention-idx="' + i + '">' +
                    '<div class="bv-av c' + colorIdx + '">' + esc(a.initials) +
                        '<span class="bv-av-dot ' + statusClass + '"></span>' +
                    '</div>' +
                    '<div class="body">' +
                        '<div class="bv-mention-row-title">' +
                            '<span class="name">' + esc(a.name) + '</span>' +
                            (a.role ? '<span class="bv-mention-role-badge">' + esc(a.role) + '</span>' : '') +
                        '</div>' +
                        '<div class="sub">' + subParts.join(' · ') + '</div>' +
                    '</div>' +
                    '<div class="bv-mention-load">' +
                        '<div class="bv-mention-load-num">' + cur + '/' + max + '</div>' +
                        '<div class="bv-mention-load-bar"><span class="bv-mention-load-fill ' + barColor + '" style="width:' + Math.round(ratio * 100) + '%"></span></div>' +
                    '</div>' +
                    '<i class="fas fa-check check"></i>' +
                '</button>'
            );
        }

        function renderMentionTeamRow(t, i) {
            const esc = (s) => $('<i>').text(s == null ? '' : String(s)).html();
            const colorIdx = ((t.id || 0) % 6) + 1;
            const cur = parseInt(t.workload_current || 0, 10);
            const max = parseInt(t.workload_max || 10, 10);
            const ratio = max > 0 ? Math.min(1, cur / max) : 0;
            const barColor = ratio >= 1 ? 'danger' : (ratio >= 0.7 ? 'warning' : 'success');
            const members = parseInt(t.members_count || 0, 10);
            const subParts = [members + ' ' + (members === 1 ? 'miembro' : 'miembros')];
            if (t.description) subParts.push(esc(t.description));
            return $(
                '<button type="button" class="bv-opt bv-mention-opt bv-mention-team-opt" role="option" data-bv-mention-type="team" data-bv-mention-idx="' + i + '">' +
                    '<div class="bv-av c' + colorIdx + '"><i class="fas fa-users bv-icon-sm"></i></div>' +
                    '<div class="body">' +
                        '<div class="bv-mention-row-title">' +
                            '<span class="name">' + esc(t.name) + '</span>' +
                            '<span class="bv-mention-team-badge">@' + esc(t.key) + '</span>' +
                        '</div>' +
                        '<div class="sub">' + subParts.join(' · ') + '</div>' +
                    '</div>' +
                    '<div class="bv-mention-load">' +
                        '<div class="bv-mention-load-num">' + cur + '/' + max + '</div>' +
                        '<div class="bv-mention-load-bar"><span class="bv-mention-load-fill ' + barColor + '" style="width:' + Math.round(ratio * 100) + '%"></span></div>' +
                    '</div>' +
                    '<i class="fas fa-check check"></i>' +
                '</button>'
            );
        }

        function renderMentionModalLists(payload) {
            mentionModalAgents = payload.agents || [];
            mentionModalTeams = payload.teams || [];
            mentionModalSelected = null;
            $('#bv-mention-modal-insert').prop('disabled', true);

            $('#bv-mention-tab-count-agents').text(mentionModalAgents.length);
            $('#bv-mention-tab-count-teams').text(mentionModalTeams.length);

            const $agents = $('#bv-mention-modal-list-agents').empty();
            if (!mentionModalAgents.length) {
                $agents.append('<div class="bv-mention-modal-empty">Sin agentes</div>');
            } else {
                mentionModalAgents.forEach((a, i) => $agents.append(renderMentionAgentRow(a, i)));
            }

            const $teams = $('#bv-mention-modal-list-teams').empty();
            if (!mentionModalTeams.length) {
                $teams.append('<div class="bv-mention-modal-empty">Sin equipos</div>');
            } else {
                mentionModalTeams.forEach((t, i) => $teams.append(renderMentionTeamRow(t, i)));
            }
        }

        async function fetchAgentsForModal(term) {
            const seq = ++mentionModalFetchSeq;
            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/api/agents-autocomplete',
                    method: 'GET',
                    data: { q: term || '' },
                    dataType: 'json',
                });
                if (seq !== mentionModalFetchSeq) return;
                renderMentionModalLists(resp || {});
            } catch (e) {
                if (seq !== mentionModalFetchSeq) return;
                renderMentionModalLists({});
            }
        }

        function switchMentionTab(tab) {
            mentionActiveTab = tab;
            $('[data-bv-modal-name="mention"] .bv-modal-tab').removeClass('on')
                .filter('[data-tab="' + tab + '"]').addClass('on');
            $('[data-bv-modal-name="mention"] .bv-mention-tab').addClass('bv-tab-hidden')
                .filter('[data-panel="' + tab + '"]').removeClass('bv-tab-hidden');
            // Reset selección al cambiar de tab
            $('[data-bv-modal-name="mention"] .bv-opt').removeClass('on');
            mentionModalSelected = null;
            $('#bv-mention-modal-insert').prop('disabled', true);
        }

        // Al abrir el modal de mención
        $(document).on('click', '[data-bv-modal="mention"]', function () {
            $('#bv-mention-search').val('');
            switchMentionTab('agents');
            $('#bv-mention-modal-list-agents').html('<div class="bv-mention-modal-empty">Cargando…</div>');
            $('#bv-mention-modal-list-teams').html('<div class="bv-mention-modal-empty">Cargando…</div>');
            fetchAgentsForModal('');
            setTimeout(() => $('#bv-mention-search').trigger('focus'), 80);
        });

        // Cambio de tab
        $(document).on('click', '[data-bv-modal-name="mention"] .bv-modal-tab', function () {
            switchMentionTab($(this).data('tab'));
        });

        // Búsqueda live con debounce
        let mentionModalSearchTimer = null;
        $(document).on('input', '#bv-mention-search', function () {
            const term = $(this).val();
            clearTimeout(mentionModalSearchTimer);
            mentionModalSearchTimer = setTimeout(() => fetchAgentsForModal(term), 180);
        });

        // Selección (agente o equipo)
        $(document).on('click', '[data-bv-modal-name="mention"] .bv-opt', function () {
            const idx = parseInt($(this).data('bv-mention-idx'), 10);
            const type = $(this).data('bv-mention-type');
            if (isNaN(idx) || !type) return;
            $('[data-bv-modal-name="mention"] .bv-opt').removeClass('on');
            $(this).addClass('on');
            const data = type === 'team' ? mentionModalTeams[idx] : mentionModalAgents[idx];
            mentionModalSelected = data ? { type, data } : null;
            $('#bv-mention-modal-insert').prop('disabled', !mentionModalSelected);
        });

        // Doble click inserta directo
        $(document).on('dblclick', '[data-bv-modal-name="mention"] .bv-opt', function () {
            $(this).trigger('click');
            $('#bv-mention-modal-insert').trigger('click');
        });

        // Insertar la mención
        $(document).on('click', '#bv-mention-modal-insert', function () {
            if (!mentionModalSelected) return;
            const $ta = $('.bv-composer-input').first();
            if (!$ta.length) return;
            const ta = $ta[0];
            const start = ta.selectionStart || ta.value.length;
            const end = ta.selectionEnd || start;
            const v = $ta.val();
            const handle = mentionModalSelected.type === 'team'
                ? mentionModalSelected.data.key
                : mentionModalSelected.data.username;
            const needsSpaceBefore = start > 0 && !/\s/.test(v[start - 1] || '');
            const insert = (needsSpaceBefore ? ' @' : '@') + handle + ' ';
            $ta.val(v.slice(0, start) + insert + v.slice(end));
            ta.selectionStart = ta.selectionEnd = start + insert.length;
            ta.dispatchEvent(new Event('input', { bubbles: true }));
            $('[data-bv-modal-name="mention"]').removeClass('on');
            if ($('.bv-modal.on').length === 0) $('body').css('overflow', '');
            ta.focus();
        });

        // Enter dentro del input de búsqueda inserta el primer resultado del tab activo
        $(document).on('keydown', '#bv-mention-search', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const $first = $('[data-bv-modal-name="mention"] [data-panel="' + mentionActiveTab + '"] .bv-opt').first();
                if ($first.length) {
                    $first.trigger('click');
                    $('#bv-mention-modal-insert').trigger('click');
                }
            }
        });

        // Detectar '@' tipeado / actualizar filtro
        $(document).on('input', '.bv-composer-input', function () {
            const ta = this;
            const cursor = ta.selectionStart || 0;
            const v = ta.value;
            // Si ya hay un anchor activo, comprueba si seguimos dentro del rango '@xxx'
            if (mentionAnchor !== null) {
                if (cursor < mentionAnchor + 1 || v[mentionAnchor] !== '@') {
                    closeMentionMenu();
                    return;
                }
                const fragment = v.slice(mentionAnchor + 1, cursor);
                if (/\s/.test(fragment)) {
                    closeMentionMenu();
                    return;
                }
                openMentionMenu($(this), fragment);
                return;
            }
            // Sin anchor: detecta '@' nuevo precedido por inicio o whitespace
            if (cursor > 0 && v[cursor - 1] === '@' && (cursor === 1 || /\s/.test(v[cursor - 2]))) {
                mentionAnchor = cursor - 1;
                openMentionMenu($(this), '');
            }
        });

        function highlightMentionRow(idx) {
            const $rows = $('.bv-mention-row');
            $rows.removeClass('on');
            const $row = $rows.eq(idx);
            $row.addClass('on');
            // Scroll into view dentro del menú
            const row = $row[0];
            if (row && row.scrollIntoView) {
                row.scrollIntoView({ block: 'nearest' });
            }
        }

        // Navegación con teclado dentro del menú
        $(document).on('keydown', '.bv-composer-input', function (e) {
            if (mentionAnchor === null) return;
            const total = mentionItems.length;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                mentionSelectedIdx = (mentionSelectedIdx + 1) % Math.max(total, 1);
                highlightMentionRow(mentionSelectedIdx);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                mentionSelectedIdx = (mentionSelectedIdx - 1 + total) % Math.max(total, 1);
                highlightMentionRow(mentionSelectedIdx);
            } else if (e.key === 'Enter' || e.key === 'Tab') {
                if (!total) {
                    closeMentionMenu();
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                const item = mentionItems[mentionSelectedIdx];
                insertMentionAt($(this), mentionAnchor, item);
                closeMentionMenu();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                closeMentionMenu();
            }
        });

        // Click en una fila del menú
        $(document).on('click', '.bv-mention-row', function (e) {
            e.preventDefault();
            const idx = parseInt($(this).data('bv-mention-idx'), 10) || 0;
            const item = mentionItems[idx];
            if (!item) return;
            const $ta = $('.bv-composer-input').first();
            insertMentionAt($ta, mentionAnchor, item);
            closeMentionMenu();
        });

        // Hover destaca la fila
        $(document).on('mouseenter', '.bv-mention-row', function () {
            const idx = parseInt($(this).data('bv-mention-idx'), 10);
            if (!isNaN(idx)) {
                mentionSelectedIdx = idx;
                $('.bv-mention-row').removeClass('on');
                $(this).addClass('on');
            }
        });

        // Click fuera cierra el menú
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-mention-menu, .bv-composer-input, #bv-btn-mention').length) {
                closeMentionMenu();
            }
        });

        // ─── Quick replies dropdown (botón rayo) ─────────────────────
        let cannedReplies = null;

        async function loadCannedReplies() {
            if (cannedReplies !== null) return cannedReplies;
            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/canned-replies/search',
                    method: 'GET',
                    dataType: 'json',
                });
                cannedReplies = Array.isArray(resp) ? resp : (resp.data || resp.items || []);
            } catch (e) {
                cannedReplies = [];
            }
            return cannedReplies;
        }

        function buildQuickRepliesDropdown(items) {
            if (document.getElementById('bv-quick-replies')) {
                $('#bv-quick-replies').remove();
            }
            const $d = $('<div id="bv-quick-replies" class="bv-quick-replies"></div>');
            if (!items.length) {
                $d.append('<div class="bv-quick-empty">Sin respuestas rápidas</div>');
            } else {
                items.forEach(it => {
                    const $row = $('<button class="bv-quick-item"></button>');
                    $row.append('<span class="bv-quick-shortcut">' + (it.shortcut || it.name) + '</span>');
                    $row.append('<span class="bv-quick-name">' + (it.name || '') + '</span>');
                    $row.append('<span class="bv-quick-body">' + (it.body || '').slice(0, 80) + '</span>');
                    $row.attr('data-bv-quick-body', it.body || '');
                    $d.append($row);
                });
            }
            $('body').append($d);
        }

        function positionQuickReplies($trigger) {
            const offset = $trigger.offset();
            $('#bv-quick-replies').css({
                position: 'absolute',
                top: (offset.top - 320) + 'px',
                left: offset.left + 'px',
                zIndex: 9999,
            });
        }

        $(document).on('click', '.bv-composer button[title="Respuesta rápida"]', async function (e) {
            e.stopPropagation();
            const items = await loadCannedReplies();
            buildQuickRepliesDropdown(items);
            positionQuickReplies($(this));
        });

        $(document).on('click', '.bv-quick-item', function () {
            const body = $(this).data('bv-quick-body');
            const $ta = $('.bv-composer-input').first();
            $ta.val(body).focus();
            $('#bv-quick-replies').remove();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-quick-replies, .bv-composer button[title="Respuesta rápida"]').length) {
                $('#bv-quick-replies').remove();
            }
        });

        // ─── AI suggestions (botón sparkles) ──────────────────────────
        function buildAiDropdown(items) {
            if (document.getElementById('bv-ai-suggestions')) {
                $('#bv-ai-suggestions').remove();
            }
            const $d = $('<div id="bv-ai-suggestions" class="bv-ai-suggestions"></div>');
            $d.append('<div class="bv-ai-head"><i class="fas fa-sparkles"></i> Sugerencias IA</div>');
            (items || []).forEach(it => {
                const $row = $('<button class="bv-ai-item"></button>')
                    .text(it.text || it)
                    .attr('data-bv-ai-text', it.text || it);
                $d.append($row);
            });
            $('body').append($d);
        }

        function positionAiDropdown($trigger) {
            const offset = $trigger.offset();
            $('#bv-ai-suggestions').css({
                position: 'absolute',
                top: (offset.top - 220) + 'px',
                left: Math.max(8, offset.left - 200) + 'px',
                zIndex: 9999,
            });
        }

        $(document).on('click', '.bv-composer button[data-bv-tip="Sugerencia IA"]', async function (e) {
            e.stopPropagation();
            const $btn = $(this);
            const convId = $('.bv-composer').data('bv-conversation-id');
            if (!convId) return;
            $btn.prop('disabled', true);
            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/conversations/' + convId + '/ai-suggestions',
                    method: 'POST',
                    dataType: 'json',
                    data: { context: $('.bv-composer-input').val() || '' },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                });
                buildAiDropdown(resp.suggestions || []);
                positionAiDropdown($btn);
            } catch (e) {
                if (window.toastr) toastr.error('No se pudo obtener sugerencias');
            } finally {
                $btn.prop('disabled', false);
            }
        });

        $(document).on('click', '.bv-ai-item', function () {
            const text = $(this).data('bv-ai-text');
            const $ta = $('.bv-composer-input').first();
            $ta.val(text).focus();
            $('#bv-ai-suggestions').remove();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-ai-suggestions, .bv-composer button[data-bv-tip="Sugerencia IA"]').length) {
                $('#bv-ai-suggestions').remove();
            }
        });

        // ─── Reply quoted (estilo WhatsApp) ──────────────────────────
        let activeReply = null;

        // Trigger desde context menu o cualquier UI que tenga los datos del bubble
        $(document).on('bv:set-reply', function (e, payload) {
            if (!payload || !payload.id) return;
            activeReply = {
                id: payload.id,
                author: payload.author || '',
                body: payload.body || '',
            };
            renderQuotePreview();
            $('.bv-composer-input').focus();
        });

        function renderQuotePreview() {
            $('#bv-quote-preview').remove();
            if (!activeReply) return;
            const escapeHtml = s => $('<div>').text(s == null ? '' : s).html();
            const $q = $(
                '<div class="bv-quote-preview" id="bv-quote-preview">' +
                    '<div class="bv-quote-line"></div>' +
                    '<div class="bv-quote-content">' +
                        '<div class="bv-quote-author"><i class="fas fa-reply"></i> ' + escapeHtml(activeReply.author) + '</div>' +
                        '<div class="bv-quote-body">' + escapeHtml(activeReply.body) + '</div>' +
                    '</div>' +
                    '<button class="bv-quote-cancel" id="bv-quote-cancel"><i class="fas fa-xmark"></i></button>' +
                '</div>'
            );
            $('.bv-composer-box').before($q);
        }

        $(document).on('click', '#bv-quote-cancel', function () {
            activeReply = null;
            $('#bv-quote-preview').remove();
        });

        // Override $.ajax para añadir reply_to_id en messages
        const _origAjax = $.ajax;
        $.ajax = function (opts) {
            if (opts && typeof opts.url === 'string'
                && /\/conversations\/\d+\/messages$/.test(opts.url)
                && (opts.method || '').toUpperCase() === 'POST'
                && activeReply) {
                if (opts.data && typeof opts.data === 'object' && !(opts.data instanceof FormData)) {
                    opts.data.reply_to_id = activeReply.id;
                }
                activeReply = null;
                $('#bv-quote-preview').remove();
            }
            return _origAjax.apply(this, arguments);
        };

        // ─── Audio message player (estilo WhatsApp) ──────────────────
        function fmtAudioTime(secs) {
            if (!isFinite(secs) || secs < 0) return '0:00';
            const m = Math.floor(secs / 60);
            const s = Math.floor(secs % 60);
            return m + ':' + String(s).padStart(2, '0');
        }

        function syncAudioUI($msg, audio) {
            const dur = isFinite(audio.duration) ? audio.duration : 0;
            const cur = audio.currentTime || 0;
            const pct = dur > 0 ? cur / dur : 0;
            const $bars = $msg.find('.bv-audio-bar');
            const total = $bars.length;
            const playedCount = Math.round(pct * total);
            $bars.each(function (i) {
                $(this).toggleClass('played', i < playedCount);
            });
            // Dot indicador que se mueve sobre la waveform
            $msg.find('.bv-audio-progress-dot').css('left', (pct * 100) + '%');
            const display = audio.paused && cur === 0 ? dur : cur;
            $msg.find('.bv-audio-time').text(fmtAudioTime(display));
        }

        $(document).on('click', '.bv-audio-play', function () {
            const $msg = $(this).closest('.bv-audio-msg');
            const audio = $msg.find('.bv-audio-el')[0];
            if (!audio) return;

            // Pausa cualquier otro audio que esté sonando
            $('.bv-audio-msg').not($msg).each(function () {
                const other = $(this).find('.bv-audio-el')[0];
                if (other && !other.paused) {
                    other.pause();
                    $(this).find('.bv-audio-play').removeClass('playing').html('<i class="fas fa-play"></i>');
                }
            });

            if (audio.paused) {
                audio.play().catch(err => {
                    console.error('[bv-audio] play error:', err);
                    if (window.toastr) toastr.error('No se puede reproducir el audio: ' + err.message);
                });
            } else {
                audio.pause();
            }
        });

        // Native capture phase: media events (timeupdate, play, pause, ended,
        // loadedmetadata, durationchange, error) don't bubble through jQuery
        // delegation reliably on <audio> elements. Capture works in all browsers.
        function onMediaEvent(eventName, handler) {
            document.addEventListener(eventName, function (ev) {
                const el = ev.target;
                if (el && el.classList && el.classList.contains('bv-audio-el')) {
                    handler(el, ev);
                }
            }, true);
        }

        onMediaEvent('play', function (el) {
            const $msg = $(el).closest('.bv-audio-msg');
            $msg.find('.bv-audio-play').addClass('playing').html('<i class="fas fa-pause"></i>');
        });

        onMediaEvent('pause', function (el) {
            const $msg = $(el).closest('.bv-audio-msg');
            $msg.find('.bv-audio-play').removeClass('playing').html('<i class="fas fa-play"></i>');
        });

        onMediaEvent('ended', function (el) {
            const $msg = $(el).closest('.bv-audio-msg');
            $msg.find('.bv-audio-play').removeClass('playing').html('<i class="fas fa-play"></i>');
            el.currentTime = 0;
            syncAudioUI($msg, el);
        });

        onMediaEvent('timeupdate', function (el) {
            syncAudioUI($(el).closest('.bv-audio-msg'), el);
        });

        onMediaEvent('loadedmetadata', function (el) {
            syncAudioUI($(el).closest('.bv-audio-msg'), el);
        });

        onMediaEvent('durationchange', function (el) {
            syncAudioUI($(el).closest('.bv-audio-msg'), el);
        });

        onMediaEvent('error', function (el) {
            const $msg = $(el).closest('.bv-audio-msg');
            $msg.find('.bv-audio-time').text('Error');
            console.error('[bv-audio] media error:', el.error);
        });

        // Seek por clic en el waveform
        $(document).on('click', '.bv-audio-wave', function (e) {
            const $msg = $(this).closest('.bv-audio-msg');
            const audio = $msg.find('.bv-audio-el')[0];
            if (!audio || !isFinite(audio.duration)) return;
            const rect = this.getBoundingClientRect();
            const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            audio.currentTime = pct * audio.duration;
        });

        // Cambio de velocidad: 1x → 1.5x → 2x → 1x
        $(document).on('click', '.bv-audio-speed', function (e) {
            e.stopPropagation();
            const $btn = $(this);
            const cur = parseFloat($btn.data('bv-speed')) || 1;
            const next = cur === 1 ? 1.5 : (cur === 1.5 ? 2 : 1);
            $btn.data('bv-speed', next).text(next + 'x');
            const audio = $btn.closest('.bv-audio-msg').find('.bv-audio-el')[0];
            if (audio) audio.playbackRate = next;
        });

        // ─── File actions popup (ver / descargar / reenviar) ─────────
        // Nota: las imágenes (.bv-attach-thumb) abren el lightbox directamente
        // vía openLightboxFromLink. Aquí solo se enganchan los documentos.
        $(document).on('click', '.bv-attach-file', function (e) {
            if ($(e.target).closest('.bv-file-actions').length) return;
            e.preventDefault();
            e.stopPropagation();

            const $a = $(this);
            const url = $a.attr('href');
            const isImage = $a.hasClass('bv-attach-thumb');
            const fileName = decodeURIComponent((url || '').split('/').pop() || 'archivo');

            $('.bv-file-actions').remove();

            const $popup = $(
                '<div class="bv-file-actions">' +
                    '<button class="bv-file-action" data-bv-fa="view"><i class="far fa-eye"></i> Ver</button>' +
                    '<button class="bv-file-action" data-bv-fa="download"><i class="fas fa-download"></i> Descargar</button>' +
                    '<button class="bv-file-action" data-bv-fa="forward"><i class="fas fa-share"></i> Reenviar</button>' +
                '</div>'
            );
            $popup.attr('data-bv-fa-url', url);
            $popup.attr('data-bv-fa-name', fileName);
            $popup.attr('data-bv-fa-type', isImage ? 'image' : 'file');

            const offset = $a.offset();
            $popup.css({
                position: 'absolute',
                top: (offset.top + $a.outerHeight() + 4) + 'px',
                left: offset.left + 'px',
                zIndex: 9999,
            });
            $('body').append($popup);
        });

        $(document).on('click', '.bv-file-action', function (e) {
            e.stopPropagation();
            const $btn = $(this);
            const $popup = $btn.closest('.bv-file-actions');
            const action = $btn.data('bv-fa');
            const url = $popup.attr('data-bv-fa-url');
            const name = $popup.attr('data-bv-fa-name');
            const type = $popup.attr('data-bv-fa-type');
            $popup.remove();

            if (action === 'view') {
                if (type === 'image') {
                    const $modal = $('[data-bv-modal-name="file-preview"]');
                    if ($modal.length) {
                        $modal.find('.bv-file-preview-content, #bv-file-preview-content')
                            .html('<img src="' + url + '" alt="" style="max-width:100%;max-height:80vh">');
                        $modal.addClass('on');
                        $('body').css('overflow', 'hidden');
                    } else {
                        window.open(url, '_blank');
                    }
                } else {
                    window.open(url, '_blank');
                }
            } else if (action === 'download') {
                downloadBlob(url, name);
            } else if (action === 'forward') {
                openForwardModal(url, name);
            }
        });

        $(document).on('click', function () {
            $('.bv-file-actions').remove();
        });

        async function openForwardModal(sourceUrl, originalName) {
            $('#bv-forward-modal').remove();
            const $modal = $(
                '<div class="bv-modal on" id="bv-forward-modal" data-bv-modal-name="forward-attachment">' +
                    '<div class="bv-modal-dialog">' +
                        '<div class="bv-modal-head">' +
                            '<div class="bv-modal-title"><i class="fas fa-share"></i> Reenviar archivo</div>' +
                            '<button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>' +
                        '</div>' +
                        '<div class="bv-modal-body">' +
                            '<div class="bv-store-search">' +
                                '<i class="fas fa-magnifying-glass"></i>' +
                                '<input type="text" id="bv-forward-search" placeholder="Buscar conversación...">' +
                            '</div>' +
                            '<div class="bv-store-list" id="bv-forward-targets">' +
                                '<div class="bv-tab-empty"><i class="fas fa-spinner fa-spin"></i><div class="bv-tab-empty-title">Cargando...</div></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $('body').append($modal);
            $('body').css('overflow', 'hidden');
            $modal.attr('data-bv-fa-url', sourceUrl);
            $modal.attr('data-bv-fa-name', originalName);

            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/search/global',
                    method: 'GET',
                    data: { q: '' },
                    dataType: 'json',
                });
                const convs = (resp && resp.conversations) || [];
                const $list = $('#bv-forward-targets').empty();
                const escapeHtml = s => $('<div>').text(s == null ? '' : s).html();
                if (!convs.length) {
                    $list.html('<div class="bv-tab-empty"><div class="bv-tab-empty-title">Sin conversaciones</div></div>');
                } else {
                    convs.forEach(c => {
                        const $row = $(
                            '<button class="bv-store-item bv-forward-target" data-bv-target-id="' + c.id + '">' +
                                '<div class="bv-store-icon" style="background:#6366f1"><i class="fas fa-comment-dots"></i></div>' +
                                '<div class="bv-store-body">' +
                                    '<div class="bv-store-name">' + escapeHtml(c.customer_name || c.subject || 'Conv #' + c.id) + '</div>' +
                                    '<div class="bv-store-meta">' + escapeHtml(c.subject || '') + '</div>' +
                                '</div>' +
                                '<i class="fas fa-paper-plane bv-store-send-icon"></i>' +
                            '</button>'
                        );
                        $list.append($row);
                    });
                }
            } catch (e) {
                $('#bv-forward-targets').html('<div class="bv-tab-empty">Error cargando</div>');
            }
        }

        $(document).on('click', '.bv-forward-target', async function () {
            const $btn = $(this);
            const targetId = $btn.data('bv-target-id');
            const $modal = $('#bv-forward-modal');
            const sourceUrl = $modal.attr('data-bv-fa-url');
            const originalName = $modal.attr('data-bv-fa-name');

            $btn.prop('disabled', true);
            try {
                await $.ajax({
                    url: '/panel/helpdesk/conversations/' + targetId + '/attachments/forward',
                    method: 'POST',
                    dataType: 'json',
                    data: { source_url: sourceUrl, original_name: originalName },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                });
                $modal.remove();
                $('body').css('overflow', '');
            } catch (e) {
                if (window.toastr) toastr.error('No se pudo reenviar');
                $btn.prop('disabled', false);
            }
        });

        $(document).on('click', '#bv-forward-modal [data-bv-close]', function () {
            $('#bv-forward-modal').remove();
            $('body').css('overflow', '');
        });

        $(document).on('input', '#bv-forward-search', function () {
            const q = $(this).val().toLowerCase().trim();
            $('.bv-forward-target').each(function () {
                const text = $(this).text().toLowerCase();
                $(this).toggle(!q || text.includes(q));
            });
        });

        // ─── Notification permission toggle button ───────────────────
        // Inject button if there's a topbar/header to host it
        function ensureNotifBtn() {
            if (document.getElementById('bv-toggle-notifications')) return;
            // Find topbar bell icon container
            const $bell = $('.bv-topbtn .fa-bell, .topbar .fa-bell').first().closest('button');
            if (!$bell.length) {
                // Fallback: prepend to thread head actions
                const $head = $('.bv-th-head .actions').first();
                if ($head.length) {
                    $head.prepend(
                        '<button class="bv-th-action" id="bv-toggle-notifications" title="Activar notificaciones">' +
                            '<i class="far fa-bell"></i>' +
                            '<span class="bad bv-hidden">!</span>' +
                        '</button>'
                    );
                }
                return;
            }
            $bell.attr('id', 'bv-toggle-notifications');
        }
        ensureNotifBtn();

        function updateNotifBtn() {
            const $btn = $('#bv-toggle-notifications');
            if (!$btn.length || typeof Notification === 'undefined') return;
            $btn.removeClass('granted denied default');
            $btn.addClass(Notification.permission || 'default');
            const titleMap = {
                granted: 'Notificaciones activadas',
                denied: 'Notificaciones bloqueadas — clic para ayuda',
                default: 'Activar notificaciones',
            };
            $btn.attr('title', titleMap[Notification.permission] || titleMap.default);
            // Switch icon to "bell" when granted, "bell-slash" when denied
            const $icon = $btn.find('i').first();
            if ($icon.length) {
                $icon.removeClass('fa-bell fa-bell-slash');
                $icon.addClass(Notification.permission === 'denied' ? 'fa-bell-slash' : 'fa-bell');
            }
        }
        updateNotifBtn();

        $(document).on('click', '#bv-toggle-notifications', async function (e) {
            if (typeof Notification === 'undefined') return;
            if (Notification.permission === 'granted') {
                return;
            }
            if (Notification.permission === 'denied') {
                e.preventDefault();
                e.stopPropagation();
                showNotifDeniedHelp();
                return;
            }
            const result = await Notification.requestPermission();
            updateNotifBtn();
            if (result === 'granted') {
                new Notification('🔔 Notificaciones activadas', { body: 'Recibirás alertas de nuevos mensajes' });
            } else if (result === 'denied') {
                showNotifDeniedHelp();
            }
        });

        function showNotifDeniedHelp() {
            $('#bv-notif-denied').remove();
            const $modal = $(
                '<div id="bv-notif-denied" class="bv-mic-denied-overlay">' +
                    '<div class="bv-mic-denied-card">' +
                        '<div class="bv-mic-denied-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-bell-slash"></i></div>' +
                        '<div class="bv-mic-denied-title">Notificaciones bloqueadas</div>' +
                        '<div class="bv-mic-denied-body">' +
                            'Para recibir alertas de nuevos mensajes en tiempo real:' +
                            '<ol class="bv-mic-denied-steps">' +
                                '<li>Haz clic en el icono <strong>🔒</strong> de la barra de direcciones</li>' +
                                '<li>Busca <strong>Notificaciones</strong></li>' +
                                '<li>Cambia a <strong>Permitir</strong></li>' +
                                '<li>Recarga la página</li>' +
                            '</ol>' +
                        '</div>' +
                        '<div class="bv-mic-denied-actions">' +
                            '<button class="bv-mic-denied-btn" id="bv-notif-retry"><i class="fas fa-bell"></i> Reintentar permiso</button>' +
                            '<button class="bv-mic-denied-btn-secondary" id="bv-notif-close">Entendido</button>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $('body').append($modal);
        }

        $(document).on('click', '#bv-notif-close', function () {
            $('#bv-notif-denied').remove();
        });

        $(document).on('click', '#bv-notif-retry', async function () {
            try {
                const result = await Notification.requestPermission();
                if (result === 'granted') {
                    $('#bv-notif-denied').remove();
                    updateNotifBtn();
                } else {
                    if (window.toastr) toastr.error('Sigue bloqueado. Usa el icono 🔒 de la URL para activarlo.', '', { timeOut: 8000 });
                }
            } catch (e) {
                if (window.toastr) toastr.error('Error al pedir permiso');
            }
        });

        // ─── Lightbox de imágenes (estilo WhatsApp) ─────────────────
        const lightbox = {
            list: [],   // [{src, name, author, time}]
            idx: 0,
            zoom: 1,
            rot: 0,
            tx: 0, ty: 0,         // pan offset
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,
            dragOriginTx: 0,
            dragOriginTy: 0,
        };

        function filenameFromUrl(url) {
            try {
                const path = new URL(url, window.location.origin).pathname;
                return decodeURIComponent(path.split('/').pop() || 'archivo');
            } catch (e) {
                return decodeURIComponent((url || '').split('?')[0].split('/').pop() || 'archivo');
            }
        }

        function collectThreadImages($currentLink) {
            const list = [];
            $('.bv-th-inner .bv-attach-thumb').each(function () {
                const $a = $(this);
                const src = $a.attr('href') || $a.data('bv-preview-src');
                if (!src) return;
                const $bubble = $a.closest('.bv-bubble');
                const author = $bubble.data('bv-author') || 'Mensaje';
                const time = $bubble.find('.meta span').first().text() || '';
                // Prefer the original filename stored in data-bv-name (set from
                // attachment metadata). Fall back to the URL path as last resort.
                const dataName = $a.data('bv-name') || $a.find('img').attr('alt');
                const name = (typeof dataName === 'string' && dataName.trim() !== '')
                    ? dataName
                    : filenameFromUrl(src);
                list.push({ src: src, name: name, author, time });
            });
            const startIdx = Math.max(0, list.findIndex(x => x.src === ($currentLink.attr('href') || $currentLink.data('bv-preview-src'))));
            return { list, startIdx };
        }

        function applyLightboxTransform() {
            const $img = $('#bv-lightbox-img');
            // Pan solo tiene efecto cuando hay zoom > 1; al zoom out se resetea automáticamente
            if (lightbox.zoom <= 1) {
                lightbox.tx = 0;
                lightbox.ty = 0;
            }
            $img.css('transform',
                'translate(' + lightbox.tx + 'px, ' + lightbox.ty + 'px) ' +
                'scale(' + lightbox.zoom + ') ' +
                'rotate(' + lightbox.rot + 'deg)'
            );
            // Cursor según el estado: zoom-in cuando 1x, grab cuando hay zoom (grabbing al arrastrar)
            const cursor = lightbox.isDragging
                ? 'grabbing'
                : (lightbox.zoom > 1 ? 'grab' : 'zoom-in');
            $img.css('cursor', cursor);
        }

        const LB_DOC_ICONS = {
            // PDF
            pdf:  { cls: 'fas fa-file-pdf',        color: '#dc2626' },
            // Word / text
            doc:  { cls: 'fas fa-file-word',        color: '#2563eb' },
            docx: { cls: 'fas fa-file-word',        color: '#2563eb' },
            rtf:  { cls: 'fas fa-file-word',        color: '#3b82f6' },
            odt:  { cls: 'fas fa-file-word',        color: '#3b82f6' },
            // Excel / data
            xls:  { cls: 'fas fa-file-excel',       color: '#059669' },
            xlsx: { cls: 'fas fa-file-excel',       color: '#059669' },
            ods:  { cls: 'fas fa-file-excel',       color: '#059669' },
            csv:  { cls: 'fas fa-file-csv',         color: '#059669' },
            // PowerPoint
            ppt:  { cls: 'fas fa-file-powerpoint',  color: '#e67000' },
            pptx: { cls: 'fas fa-file-powerpoint',  color: '#e67000' },
            odp:  { cls: 'fas fa-file-powerpoint',  color: '#e67000' },
            // Archives
            zip:  { cls: 'fas fa-file-zipper',      color: '#71717a' },
            rar:  { cls: 'fas fa-file-zipper',      color: '#71717a' },
            '7z': { cls: 'fas fa-file-zipper',      color: '#71717a' },
            gz:   { cls: 'fas fa-file-zipper',      color: '#71717a' },
            tar:  { cls: 'fas fa-file-zipper',      color: '#71717a' },
            bz2:  { cls: 'fas fa-file-zipper',      color: '#71717a' },
            // Text / plain
            txt:  { cls: 'fas fa-file-lines',       color: '#71717a' },
            md:   { cls: 'fas fa-file-lines',       color: '#71717a' },
            // Code / markup
            json: { cls: 'fas fa-file-code',        color: '#f59e0b' },
            xml:  { cls: 'fas fa-file-code',        color: '#f59e0b' },
            html: { cls: 'fas fa-file-code',        color: '#f97316' },
            htm:  { cls: 'fas fa-file-code',        color: '#f97316' },
            css:  { cls: 'fas fa-file-code',        color: '#06b6d4' },
            js:   { cls: 'fas fa-file-code',        color: '#eab308' },
            php:  { cls: 'fas fa-file-code',        color: '#8b5cf6' },
            // Image variants (when shown as doc)
            svg:  { cls: 'fas fa-file-image',       color: '#10b981' },
            bmp:  { cls: 'fas fa-file-image',       color: '#71717a' },
            tiff: { cls: 'fas fa-file-image',       color: '#71717a' },
            tif:  { cls: 'fas fa-file-image',       color: '#71717a' },
            // Audio (fallback)
            mp3:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            wav:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            ogg:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            oga:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            m4a:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            aac:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            flac: { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            opus: { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            wma:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            // Video (fallback)
            avi:  { cls: 'fas fa-file-video',       color: '#ef4444' },
            mkv:  { cls: 'fas fa-file-video',       color: '#ef4444' },
            flv:  { cls: 'fas fa-file-video',       color: '#ef4444' },
            wmv:  { cls: 'fas fa-file-video',       color: '#ef4444' },
            ogv:  { cls: 'fas fa-file-video',       color: '#ef4444' },
            '3gp':{ cls: 'fas fa-file-video',       color: '#ef4444' },
        };

        function renderLightbox() {
            const item = lightbox.list[lightbox.idx];
            if (!item) return;

            const $img   = $('#bv-lightbox-img');
            const $video = $('#bv-lightbox-video');
            const $audio = $('#bv-lightbox-audio');
            const $doc   = $('#bv-lightbox-doc');

            // Reset all elements
            $img.hide().removeClass('bv-lb-error');
            if ($video.length) { $video[0].pause(); $video.hide(); $video[0].removeAttribute('src'); }
            if ($audio.length) { $audio[0].pause(); $audio.hide(); $audio[0].removeAttribute('src'); }
            $doc.hide();

            // Toggle zoom/rotate controls (only for images)
            const isImage = item.type === 'image';
            $('#bv-lightbox-zoom-in, #bv-lightbox-zoom-out, #bv-lightbox-rotate').toggle(isImage);

            if (item.type === 'video') {
                $video.attr('src', item.src).show();
                $video[0].load();
            } else if (item.type === 'audio') {
                $audio.attr('src', item.src).show();
                $audio[0].load();
            } else if (isImage) {
                $img.attr('src', item.src).attr('alt', item.name || '').show();
                lightbox.zoom = 1; lightbox.rot = 0; lightbox.tx = 0; lightbox.ty = 0;
                applyLightboxTransform();
            } else {
                // document / unknown
                const meta = LB_DOC_ICONS[item.ext] || { cls: 'fas fa-file', color: '#71717a' };
                $doc.find('.bv-lb-doc-icon i').attr('class', meta.cls).css('color', meta.color);
                $doc.find('.bv-lb-doc-name').text(item.name);
                const sizeStr = item.size > 0
                    ? (item.size < 1048576 ? Math.round(item.size / 1024) + ' KB' : (item.size / 1048576).toFixed(1) + ' MB')
                    : '';
                $doc.find('.bv-lb-doc-size').text(sizeStr);
                $doc.show();
            }

            $('#bv-lightbox-author').text(item.author);
            $('#bv-lightbox-sub').text(item.time);
            $('#bv-lightbox-counter').text((lightbox.idx + 1) + ' / ' + lightbox.list.length);
            renderLightboxStrip();
            const showNav = lightbox.list.length > 1;
            $('#bv-lightbox-prev, #bv-lightbox-next').toggle(showNav);
        }

        function renderLightboxStrip() {
            const $strip = $('#bv-lightbox-strip').empty();
            if (lightbox.list.length <= 1) { $strip.hide(); return; }
            $strip.show();
            lightbox.list.forEach((item, i) => {
                const active = i === lightbox.idx ? ' on' : '';
                let inner;
                if (item.type === 'image') {
                    inner = '<img src="' + item.src + '" alt="">';
                } else if (item.type === 'video') {
                    inner = '<i class="fas fa-video"></i>';
                } else if (item.type === 'audio') {
                    inner = '<i class="fas fa-volume-high"></i>';
                } else {
                    const meta = LB_DOC_ICONS[item.ext] || { cls: 'fas fa-file' };
                    inner = '<i class="' + meta.cls + '"></i>';
                }
                const $thumb = $('<button type="button" class="bv-lightbox-thumb' + active + '" data-idx="' + i + '">' + inner + '</button>');
                $strip.append($thumb);
            });
        }

        function openLightboxFromLink($a) {
            const { list, startIdx } = collectThreadImages($a);
            if (!list.length) return;
            lightbox.list = list;
            lightbox.idx = startIdx;
            renderLightbox();
            const $modal = $('[data-bv-modal-name="file-preview"]');
            $modal.addClass('on');
            $('body').css('overflow', 'hidden');
        }

        // Intercepta el click sobre miniaturas — sustituye al modal genérico
        $(document).on('click', '.bv-attach-thumb', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openLightboxFromLink($(this));
        });

        $(document).on('click', '#bv-lightbox-prev', function () {
            lightbox.idx = (lightbox.idx - 1 + lightbox.list.length) % lightbox.list.length;
            renderLightbox();
        });
        $(document).on('click', '#bv-lightbox-next', function () {
            lightbox.idx = (lightbox.idx + 1) % lightbox.list.length;
            renderLightbox();
        });
        $(document).on('click', '.bv-lightbox-thumb', function () {
            lightbox.idx = parseInt($(this).data('idx'), 10) || 0;
            renderLightbox();
        });
        function zoomLightbox(delta, anchorX, anchorY) {
            const $img = $('#bv-lightbox-img');
            if (!$img.length) return;
            const prevZoom = lightbox.zoom;
            const newZoom = Math.max(0.5, Math.min(6, prevZoom + delta));
            if (newZoom === prevZoom) return;

            // Si nos pasaron coords, hacer zoom centrado en el cursor
            if (typeof anchorX === 'number' && typeof anchorY === 'number') {
                const rect = $img[0].getBoundingClientRect();
                const cx = rect.left + rect.width / 2;
                const cy = rect.top + rect.height / 2;
                // Vector cursor → centro del img actual
                const dx = anchorX - cx;
                const dy = anchorY - cy;
                const ratio = newZoom / prevZoom;
                // Mantén el punto bajo el cursor estable: ajusta tx/ty
                lightbox.tx = (lightbox.tx - dx) * ratio + dx;
                lightbox.ty = (lightbox.ty - dy) * ratio + dy;
            }
            lightbox.zoom = newZoom;
            applyLightboxTransform();
        }

        $(document).on('click', '#bv-lightbox-zoom-in', function () {
            zoomLightbox(0.25);
        });
        $(document).on('click', '#bv-lightbox-zoom-out', function () {
            zoomLightbox(-0.25);
        });
        $(document).on('click', '#bv-lightbox-rotate', function () {
            lightbox.rot = (lightbox.rot + 90) % 360;
            applyLightboxTransform();
        });

        // ─── Zoom con scroll del mouse ───────────────────────────────
        $(document).on('wheel', '.bv-lightbox-stage', function (e) {
            const $modal = $('[data-bv-modal-name="file-preview"]');
            if (!$modal.hasClass('on')) return;
            e.preventDefault();
            const native = e.originalEvent;
            const delta = native.deltaY < 0 ? 0.2 : -0.2;
            zoomLightbox(delta, native.clientX, native.clientY);
        });

        // ─── Pan con drag (cuando zoom > 1) ──────────────────────────
        $(document).on('mousedown', '#bv-lightbox-img, .bv-lightbox-stage', function (e) {
            if (e.button !== 0) return; // solo botón principal
            if (lightbox.zoom <= 1) return;
            e.preventDefault();
            lightbox.isDragging = true;
            lightbox.dragStartX = e.clientX;
            lightbox.dragStartY = e.clientY;
            lightbox.dragOriginTx = lightbox.tx;
            lightbox.dragOriginTy = lightbox.ty;
            applyLightboxTransform();
        });
        $(document).on('mousemove', function (e) {
            if (!lightbox.isDragging) return;
            const dx = e.clientX - lightbox.dragStartX;
            const dy = e.clientY - lightbox.dragStartY;
            lightbox.tx = lightbox.dragOriginTx + dx;
            lightbox.ty = lightbox.dragOriginTy + dy;
            applyLightboxTransform();
        });
        $(document).on('mouseup mouseleave', function () {
            if (!lightbox.isDragging) return;
            lightbox.isDragging = false;
            applyLightboxTransform();
        });

        // ─── Doble click toggle 1x ↔ 2x ─────────────────────────────
        $(document).on('dblclick', '#bv-lightbox-img, .bv-lightbox-stage', function (e) {
            const $modal = $('[data-bv-modal-name="file-preview"]');
            if (!$modal.hasClass('on')) return;
            e.preventDefault();
            if (lightbox.zoom > 1) {
                lightbox.zoom = 1;
                lightbox.tx = 0; lightbox.ty = 0;
                applyLightboxTransform();
            } else {
                zoomLightbox(1, e.clientX, e.clientY); // de 1 → 2
            }
        });
        $(document).on('click', '#bv-lightbox-open', function () {
            const item = lightbox.list[lightbox.idx];
            if (item) window.open(item.src, '_blank');
        });
        // Mapa MIME → extensión para fallback cuando el nombre no la tiene
        const MIME_EXT = {
            'image/png': 'png', 'image/jpeg': 'jpg', 'image/jpg': 'jpg',
            'image/gif': 'gif', 'image/webp': 'webp', 'image/svg+xml': 'svg',
            'image/heic': 'heic', 'image/avif': 'avif',
            'audio/webm': 'webm', 'audio/mpeg': 'mp3', 'audio/ogg': 'ogg',
            'audio/wav': 'wav', 'audio/x-m4a': 'm4a',
            'video/mp4': 'mp4', 'video/webm': 'webm', 'video/quicktime': 'mov',
            'application/pdf': 'pdf',
        };

        function ensureExtensionInName(name, blob) {
            if (!blob) return name || 'archivo';
            const hasExt = /\.[a-z0-9]{2,5}$/i.test(name || '');
            if (hasExt) return name;
            const ext = MIME_EXT[blob.type] || (blob.type.split('/')[1] || '').replace(/[^a-z0-9]/gi, '');
            const base = (name || 'archivo').replace(/\.+$/, '');
            return ext ? base + '.' + ext : base;
        }

        // Descarga forzada vía endpoint server con Content-Disposition.
        // Funciona en cualquier navegador (incluido Playwright/CDP).
        function downloadBlob(url, suggestedName) {
            const endpoint = '/panel/helpdesk/api/attachment-download?url=' + encodeURIComponent(url);
            const a = document.createElement('a');
            a.href = endpoint;
            a.download = suggestedName || filenameFromUrl(url);
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            a.remove();
            return true;
        }

        $(document).on('click', '#bv-lightbox-download, #bv-lb-doc-dl', function () {
            const item = lightbox.list[lightbox.idx];
            if (!item) return;
            downloadBlob(item.src, item.name || filenameFromUrl(item.src));
        });

        // Teclado: ←→ navegan, ESC cierra
        $(document).on('keydown', function (e) {
            const $modal = $('[data-bv-modal-name="file-preview"]');
            if (!$modal.hasClass('on')) return;
            if (e.key === 'ArrowLeft') { e.preventDefault(); $('#bv-lightbox-prev').click(); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); $('#bv-lightbox-next').click(); }
            else if (e.key === '+' || e.key === '=') $('#bv-lightbox-zoom-in').click();
            else if (e.key === '-') $('#bv-lightbox-zoom-out').click();
        });

        // Inbox initialized

        // ═══════════════════════════════════════════════════════════════
        // FEATURE 1: Atajos adicionales
        // - R sin modificador → enfocar compositor (ya activa tab reply)
        // - /  sin foco en input → enfocar #bv-search-input
        // ═══════════════════════════════════════════════════════════════
        // El handler de teclado existente (línea ~590) ya cubre J/K, ?, Esc,
        // R (reply tab) y la G-sequence. Extendemos el switch existente
        // para que R también enfoque el input del compositor, y agregamos /
        // como atajo de búsqueda de lista cuando no hay foco en un campo.
        //
        // NOTA: El handler existente ya guarda e.key / key = e.key.toLowerCase().
        // Añadimos un segundo handler secundario que SÓLO actúa cuando el
        // foco NO está en un campo de texto, complementando sin reemplazar.
        $(document).on('keydown.bv-extras', function (e) {
            if ($(e.target).is('input, textarea, [contenteditable]')) return;
            if (e.metaKey || e.ctrlKey || e.altKey) return;

            // / → enfocar búsqueda de conversaciones
            if (e.key === '/') {
                e.preventDefault();
                const $s = $('#bv-search-input');
                if ($s.length) {
                    $s.focus().select();
                }
                return;
            }

            // R → activar tab reply + enfocar compositor
            if (e.key.toLowerCase() === 'r') {
                const $tab = $('.bv-composer-tab[data-bv-tab="reply"]');
                if ($tab.length) {
                    $tab.click();
                    setTimeout(function () { $('.bv-composer-input').first().focus(); }, 50);
                }
            }
        });

        // ═══════════════════════════════════════════════════════════════
        // FEATURE 2: Sonido al llegar mensaje nuevo
        // ═══════════════════════════════════════════════════════════════
        var BvSound = (function () {
            var STORAGE_KEY = 'bv:sound:enabled';

            function isEnabled() {
                var v = localStorage.getItem(STORAGE_KEY);
                return v === null ? true : v === '1';
            }

            function setEnabled(on) {
                localStorage.setItem(STORAGE_KEY, on ? '1' : '0');
            }

            function playBeep() {
                if (!isEnabled()) return;
                try {
                    var ctx = new (window.AudioContext || window.webkitAudioContext)();
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, ctx.currentTime);
                    gain.gain.setValueAtTime(0.18, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.35);
                    osc.onended = function () { ctx.close(); };
                } catch (err) {
                    // AudioContext not available (e.g. server-side rendering)
                }
            }

            function applyUI() {
                var on = isEnabled();
                $('#bv-sound-toggle').find('i')
                    .toggleClass('fa-volume-up', on)
                    .toggleClass('fa-volume-mute', !on);
                $('#bv-sound-toggle').attr('title', on ? 'Silenciar notificaciones' : 'Activar notificaciones de sonido');
            }

            function init() {
                // Inyectar botón en la barra de estado (statusbar)
                var $sb = $('.bv-statusbar .spacer').first();
                if ($sb.length) {
                    $sb.before(
                        '<button id="bv-sound-toggle" class="sb-item sb-btn" style="background:none;border:none;cursor:pointer;padding:0 4px;color:inherit;">' +
                        '<i class="fas fa-volume-up"></i></button><span class="sep">│</span>'
                    );
                }
                applyUI();

                $(document).on('click', '#bv-sound-toggle', function () {
                    setEnabled(!isEnabled());
                    applyUI();
                });
            }

            return { init: init, playBeep: playBeep, isEnabled: isEnabled };
        })();
        BvSound.init();

        // Escuchar mensajes entrantes del cliente y reproducir beep
        window.addEventListener('inbox:incoming-message', function (ev) {
            var msg = ev.detail || {};
            var isCustomer = !msg.user_id && msg.author_id;
            if (!isCustomer) return;
            // ¿El agente está viendo ESTA conversación?
            var selectedId = parseInt(new URLSearchParams(window.location.search).get('selected') || '0', 10);
            var msgConvId  = parseInt(msg.conversation_id || '0', 10);
            var isViewing  = msgConvId && msgConvId === selectedId;
            if (!isViewing) {
                BvSound.playBeep();
            }
        });

        // ═══════════════════════════════════════════════════════════════
        // FEATURE 3: Badge favicon con conteo de no-leídos
        // ═══════════════════════════════════════════════════════════════
        var BvFavicon = (function () {
            var count = 0;
            var originalHref = null;
            var $link = null;

            function getLink() {
                if ($link && $link.length) return $link;
                $link = $('link[rel~="icon"]').first();
                if (!$link.length) {
                    $link = $('<link rel="icon" type="image/png">');
                    $('head').append($link);
                }
                return $link;
            }

            function drawBadge(src, n, cb) {
                var img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = function () {
                    var canvas = document.createElement('canvas');
                    canvas.width  = 32;
                    canvas.height = 32;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, 32, 32);
                    if (n > 0) {
                        var label = n > 99 ? '99+' : String(n);
                        var r = label.length > 1 ? 10 : 8;
                        var cx = 32 - r, cy = r;
                        ctx.beginPath();
                        ctx.arc(cx, cy, r, 0, 2 * Math.PI);
                        ctx.fillStyle = '#90bb13';
                        ctx.fill();
                        ctx.fillStyle = '#fff';
                        ctx.font = 'bold ' + (label.length > 1 ? '9' : '11') + 'px Arial';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(label, cx, cy);
                    }
                    cb(canvas.toDataURL('image/png'));
                };
                img.onerror = function () { cb(src); };
                img.src = src;
            }

            function update(n) {
                count = Math.max(0, n);
                var $l = getLink();
                if (!originalHref) {
                    originalHref = $l.attr('href') || '/favicon.ico';
                }
                drawBadge(originalHref, count, function (dataUrl) {
                    $l.attr('href', dataUrl);
                });
            }

            function increment() { update(count + 1); }
            function decrement() { update(count - 1); }
            function reset()     { update(0); }

            function initCount() {
                var n = $('.bv-conv.unread').length +
                        $('.bv-conv .bv-conv-unread:not(:empty)').length;
                if (n > 0) update(n);
            }

            return { init: initCount, increment: increment, decrement: decrement, reset: reset, update: update };
        })();
        BvFavicon.init();

        // Incrementar cuando llega mensaje entrante y no se está viendo
        window.addEventListener('inbox:incoming-message', function (ev) {
            var msg = ev.detail || {};
            var isCustomer = !msg.user_id && msg.author_id;
            if (!isCustomer) return;
            var selectedId = parseInt(new URLSearchParams(window.location.search).get('selected') || '0', 10);
            var msgConvId  = parseInt(msg.conversation_id || '0', 10);
            if (!msgConvId || msgConvId !== selectedId) {
                BvFavicon.increment();
            }
        });

        // Decrementar al abrir conversación (limpiar badge de esa conv)
        $(document).on('click', '.bv-conv', function () {
            var $item = $(this);
            var hadUnread = $item.hasClass('unread') || $item.find('.bv-conv-unread:not(:empty)').length > 0;
            if (hadUnread) {
                BvFavicon.decrement();
            }
        });

        // ═══════════════════════════════════════════════════════════════
        // FEATURE 4: Modo concentración
        // ═══════════════════════════════════════════════════════════════

        // Inyectar estilos del modo concentración
        (function injectFocusModeStyles() {
            if (document.getElementById('bv-focus-mode-styles')) return;
            var css = [
                'body.bv-focus-mode .bv-list { display: none !important; }',
                'body.bv-focus-mode .bv-right { display: none !important; }',
                // Detect actual grid: keep nav sidebar visible, expand thread, hide list + right.
                'body.bv-focus-mode .conversations { grid-template-columns: var(--bv-nav-w, 240px) 1fr !important; }',
                'body.bv-focus-mode .bv-thread { max-width: 820px; margin: 0 auto; width: 100%; }',
                '#bv-focus-timer {',
                '  display: none;',
                '  position: absolute;',
                '  top: 0; left: 0; right: 0;',
                '  background: rgba(177,1,0,0.07);',
                '  border-bottom: 2px solid rgba(177,1,0,0.18);',
                '  text-align: center;',
                '  font-size: 13px;',
                '  font-weight: 600;',
                '  color: #90bb13;',
                '  padding: 5px 12px;',
                '  letter-spacing: 0.5px;',
                '  z-index: 10;',
                '}',
                'body.bv-focus-mode #bv-focus-timer { display: block; }',
                'body.bv-focus-mode .bv-thread { position: relative; }',
            ].join('\n');
            var $style = $('<style id="bv-focus-mode-styles">').text(css);
            $('head').append($style);
        })();

        // Inyectar botón de modo concentración en la cabecera del hilo
        function injectFocusModeButton() {
            if ($('#bv-focus-btn').length) return;
            var $sep = $('.bv-th-head .actions .bv-th-sep').first();
            if ($sep.length) {
                $sep.before(
                    '<button class="bv-th-action" id="bv-focus-btn" title="Modo concentración" aria-label="Modo concentración">' +
                    '<i class="fas fa-expand"></i></button>'
                );
            }
        }
        injectFocusModeButton();

        // Timer SLA: calcula cuánto tiempo lleva abierta la conversación
        var focusTimerInterval = null;
        function startFocusTimer() {
            if ($('#bv-focus-timer').length === 0) {
                $('.bv-thread').prepend('<div id="bv-focus-timer"></div>');
            }
            var $thread = $('.bv-thread');
            var createdAtStr = $thread.find('[data-bv-conv-created]').first().data('bv-conv-created') ||
                               $thread.attr('data-bv-conv-created');
            var createdAt = createdAtStr ? new Date(createdAtStr) : null;

            function updateTimer() {
                var now = new Date();
                var diffMs = createdAt ? (now - createdAt) : 0;
                var mins = Math.floor(diffMs / 60000);
                var hrs  = Math.floor(mins / 60);
                var label;
                if (!createdAt) {
                    label = 'Modo concentración activo';
                } else if (hrs > 0) {
                    label = 'Conversación abierta hace ' + hrs + 'h ' + (mins % 60) + 'm';
                } else {
                    label = 'Conversación abierta hace ' + mins + ' min';
                }
                $('#bv-focus-timer').text(label);
            }

            updateTimer();
            focusTimerInterval = setInterval(updateTimer, 30000);
        }

        function stopFocusTimer() {
            clearInterval(focusTimerInterval);
            focusTimerInterval = null;
            $('#bv-focus-timer').remove();
        }

        function isFocusMode() { return $('body').hasClass('bv-focus-mode'); }

        function enableFocusMode() {
            $('body').addClass('bv-focus-mode');
            $('#bv-focus-btn i').removeClass('fa-expand').addClass('fa-compress');
            $('#bv-focus-btn').attr('title', 'Salir del modo concentración');
            startFocusTimer();
        }

        function disableFocusMode() {
            $('body').removeClass('bv-focus-mode');
            $('#bv-focus-btn i').removeClass('fa-compress').addClass('fa-expand');
            $('#bv-focus-btn').attr('title', 'Modo concentración');
            stopFocusTimer();
        }

        $(document).on('click', '#bv-focus-btn', function () {
            if (isFocusMode()) {
                disableFocusMode();
            } else {
                enableFocusMode();
            }
        });

        // Esc también desactiva el modo concentración (el handler de Esc existente
        // cierra modales; lo extendemos aquí sólo cuando no hay modal abierto)
        $(document).on('keydown.bv-focus', function (e) {
            if (e.key !== 'Escape') return;
            if ($('.bv-modal.on').length) return; // ya lo maneja el handler anterior
            if (isFocusMode()) {
                disableFocusMode();
            }
        });

        // Re-inyectar botón si el thread se recarga (AJAX)
        $(document).on('bv:thread:loaded', function () {
            injectFocusModeButton();
        });

        // ═══════════════════════════════════════════════════════════════
        // FEATURE: Acceso rápido (atajos) + Cambiar color blanco/negro
        // ═══════════════════════════════════════════════════════════════
        // Modo oscuro retirado: el inbox se muestra siempre en claro. Se conserva
        // el botón de acceso rápido a los atajos de teclado.
        var BvTheme = (function () {
            function init() {
                localStorage.removeItem('bv:theme:dark');
                document.documentElement.removeAttribute('data-bv-dark');
                $('.conversations').first().attr('data-theme', 'light');

                var $sb = $('.bv-statusbar .spacer').first();
                if ($sb.length) {
                    $sb.before(
                        '<button id="bv-quick-access" class="bv-sb-btn" title="Acceso rápido — Atajos de teclado" aria-label="Acceso rápido">' +
                        '<i class="fas fa-keyboard"></i></button><span class="sep">│</span>'
                    );
                }

                $(document).on('click', '#bv-quick-access', function () {
                    openModal('shortcuts');
                });
            }

            return { init: init };
        })();
        BvTheme.init();

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

    // Clase de pill de estado a partir del texto (compartida PS/ERP)
    window.bvOrderStatusClass = function (text) {
        var t = String(text != null ? text : '').toLowerCase();
        if (t.indexOf('entreg') >= 0 || t.indexOf('pago acept') >= 0 || t.indexOf('complet') >= 0 || t.indexOf('pagad') >= 0) { return 'is-completed'; }
        if (t.indexOf('envi') >= 0 || t.indexOf('ship') >= 0 || t.indexOf('tránsito') >= 0 || t.indexOf('transito') >= 0) { return 'is-shipped'; }
        if (t.indexOf('cancel') >= 0 || t.indexOf('reembols') >= 0 || t.indexOf('anulad') >= 0) { return 'is-cancelled'; }
        return 'is-pending';
    };

    // Helper: renderiza un bloque de dirección como HTML
    function renderAddrBlock(addr) {
        if (!addr || typeof addr !== 'object') { return null; }
        var e = function (s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        };
        var parts = [];
        var name = [addr.firstname, addr.lastname].filter(Boolean).join(' ');
        if (name) { parts.push('<strong>' + e(name) + '</strong>'); }
        if (addr.company) { parts.push(e(addr.company)); }
        if (addr.address1) { parts.push(e(addr.address1)); }
        if (addr.address2) { parts.push(e(addr.address2)); }
        var cityLine = [addr.postcode, addr.city].filter(Boolean).join(' ');
        if (cityLine) { parts.push(e(cityLine)); }
        if (addr.state && addr.state !== addr.city) { parts.push(e(addr.state)); }
        if (addr.country) { parts.push(e(addr.country)); }
        if (!parts.length) { return null; }
        var html = '<div class="bv-om-addr-lines">' + parts.join('<br>') + '</div>';
        var phone = addr.phone || addr.phone_mobile || null;
        if (phone) { html += '<span class="bv-addr-phone"><i class="fas fa-phone"></i> ' + e(phone) + '</span>'; }
        return html;
    }

    // Helper: rellena el panel Dirección del modal (shipping, billing opcional)
    function setAddressTab(shipping, billing) {
        var addr = shipping && typeof shipping === 'object' ? shipping : null;
        var hasData = !!(addr && (addr.address1 || addr.city || addr.country || addr.firstname));
        var $grid = $('#bv-order-modal-addr-grid');

        $grid.toggleClass('bv-hidden', !hasData);
        $('#bv-order-modal-addr-empty').toggleClass('bv-hidden', hasData);

        if (!hasData) { return; }

        // Set address type label
        var billAddr = billing && typeof billing === 'object' ? billing : null;
        var hasBill = !!(billAddr && (billAddr.address1 || billAddr.city));
        var addrLabel, addrIcon;
        if (!hasBill) {
            addrLabel = 'Dirección de envío';
            addrIcon  = 'fas fa-truck';
        } else {
            var sameAddr = billing.address1 === shipping.address1 && billing.city === shipping.city;
            if (sameAddr) {
                addrLabel = 'Dirección combinada (envío y facturación)';
                addrIcon  = 'fas fa-location-dot';
            } else {
                addrLabel = 'Dirección de envío';
                addrIcon  = 'fas fa-truck';
            }
        }
        $('#bv-om-addr-type-text').text(addrLabel);
        $('#bv-om-addr-type-icon').attr('class', addrIcon);

        // Reset all rows
        $grid.find('.lbl, .val').addClass('bv-hidden').removeClass('bv-last-row');

        function showRow(lblId, valId, val) {
            var has = !!(val && String(val).trim());
            $('#' + lblId).toggleClass('bv-hidden', !has);
            $('#' + valId).toggleClass('bv-hidden', !has);
            if (has) { $('#' + valId).text(String(val)); }
        }

        var name = [addr.firstname, addr.lastname].filter(Boolean).join(' ');
        showRow('bv-om-ship-name-lbl', 'bv-om-ship-name', name);
        showRow('bv-om-ship-company-lbl', 'bv-om-ship-company', addr.company);
        showRow('bv-om-ship-addr1-lbl', 'bv-om-ship-addr1', addr.address1);
        showRow('bv-om-ship-addr2-lbl', 'bv-om-ship-addr2', addr.address2);
        var city = [addr.postcode, addr.city].filter(Boolean).join(' ');
        showRow('bv-om-ship-city-lbl', 'bv-om-ship-city', city);
        var state = addr.state && addr.state !== addr.city ? addr.state : '';
        showRow('bv-om-ship-state-lbl', 'bv-om-ship-state', state);
        showRow('bv-om-ship-country-lbl', 'bv-om-ship-country', addr.country);
        var phone = addr.phone || addr.phone_mobile || '';
        showRow('bv-om-ship-phone-lbl', 'bv-om-ship-phone', phone);

        // Mark last visible row to remove its border-bottom
        $grid.find('.lbl:not(.bv-hidden)').last().addClass('bv-last-row');
        $grid.find('.val:not(.bv-hidden)').last().addClass('bv-last-row');
    }

    // ─── Order detail modal (#37 ve-order-view) — datos embebidos en la tarjeta ───
    $(document).on('click', '.rp3-order[data-bv-modal="order"]', function () {
        const $el = $(this).closest('.rp3-order[data-bv-modal="order"]');
        const ref = $el.data('order-ref') || '—';
        const status = $el.data('order-status') || '—';
        const date = $el.data('order-date') || '—';
        const total = $el.data('order-total') || '—';
        const productsJson = $el.attr('data-order-products');
        let products = [];
        try { products = JSON.parse(productsJson); } catch (e) { }
        const url = $el.data('order-url') || '';
        const platform = $el.data('order-platform') || '';
        const payment = $el.data('order-payment') || '—';

        $('#bv-order-modal-chip').text(ref);
        $('#bv-order-modal-date').text(date);
        $('#bv-order-modal-status')
            .text(status)
            .attr('class', 'bv-ov-st ' + window.bvOrderStatusClass(status));
        $('#bv-order-modal-payment').text(payment);
        $('#bv-order-modal-total').text(total + ' €');
        $('#bv-order-modal-products-count').text(products.length ? products.length + (products.length === 1 ? ' artículo' : ' artículos') : '');

        var subtotal = 0;
        products.forEach(function (p) {
            subtotal += (parseFloat(p.price) || 0) * (parseInt(p.qty) || 1);
        });
        $('#bv-order-modal-subtotal').text(subtotal.toFixed(2).replace('.', ',') + ' €');
        $('#bv-order-modal-shipping-val').text('—');
        $('#bv-order-modal-tax-row').addClass('bv-hidden');
        $('#bv-order-modal-discount-row').addClass('bv-hidden');

        var productsHtml = '';
        if (products.length === 0) {
            productsHtml = '<div class="bv-oc-empty"><i class="fas fa-box-open"></i><div class="title">Sin productos</div></div>';
        } else {
            products.forEach(function (p) {
                var unitPrice = parseFloat(p.price) || 0;
                var qty       = parseInt(p.qty) || 1;
                var lineTotal = unitPrice * qty;
                var meta = '\xd7' + qty + (unitPrice > 0 ? ' \xb7 €\xa0' + unitPrice.toFixed(2) : '');
                productsHtml +=
                    '<div class="bv-om-prod-card">' +
                        '<div class="bv-om-pc-thumb"><i class="fas fa-box"></i></div>' +
                        '<div class="bv-om-pc-info">' +
                            '<div class="bv-om-pc-name">' + escapeHtml(p.name || 'Producto') + '</div>' +
                            '<div class="bv-om-pc-meta">' + meta + '</div>' +
                        '</div>' +
                        '<div class="bv-om-pc-price">€\xa0' + lineTotal.toFixed(2) + '</div>' +
                    '</div>';
            });
        }
        $('#bv-order-modal-products').html(productsHtml);
        $('#bv-order-modal-tracking-field').addClass('bv-hidden');
        setAddressTab(null, null);

        var custName = ($('.bv-cp-name-btn').first().text() || $('.bv-right-name').first().text() || '—').trim();
        $('#bv-order-modal-cust-name').text(custName);

        var $link = $('#bv-order-modal-external-link');
        if (url) {
            $link.attr('href', url).removeClass('bv-hidden');
            if (platform === 'prestashop') {
                $link.html('<i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir en PrestaShop');
            } else if (platform === 'erp') {
                $link.html('<i class="fa-solid fa-arrow-up-right-from-square"></i> Ver en ERP');
            } else {
                $link.html('<i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir en tienda');
            }
        } else {
            $link.addClass('bv-hidden');
        }
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }


    // Pre-fill create-ticket modal when opened
    $(document).on('click', '[data-bv-modal="create-ticket"]', function () {
        var $selected = $('.bv-conv.on');
        var convId = $selected.data('bv-conv-id') || '';
        var $messages = $('.bv-msg');
        var msgCount = $messages.length;

        // Update context
        $('#bv-ticket-conv-id').text('#' + (convId || '—'));
        $('#bv-ticket-message-count').text(msgCount);

        // Build description from recent messages (last 5)
        var descLines = [];
        $messages.slice(-5).each(function () {
            var $bubble = $(this).find('.bv-bubble');
            var author = $bubble.data('bv-author') || '—';
            var body = $bubble.data('bv-body') || '';
            if (body) {
                descLines.push(author + ': ' + body);
            }
        });
        var description = descLines.join('\n');
        if (description) {
            $('#bv-ticket-description').val(description);
        }

        // Pre-fill subject from first customer message if empty
        var $subjectInput = $('#bv-ticket-subject');
        if (!$subjectInput.val().trim() && descLines.length > 0) {
            var firstMsg = descLines[0].replace(/^[^:]+:\s*/, '').substring(0, 80);
            $subjectInput.val(firstMsg);
        }
    });

    // ─── Create Ticket Modal ──────────────────────────────────────────────────
    $(document).on('click', '#bv-ticket-priority .prio-card', function () {
        $(this).siblings().removeClass('on');
        $(this).addClass('on');
    });

    $(document).on('click', '#bv-btn-create-ticket', function () {
        var $btn = $(this);
        var subject = $('#bv-ticket-subject').val().trim();
        if (!subject) {
            if (window.toastr) toastr.error('El asunto es obligatorio.');
            else alert('El asunto es obligatorio.');
            return;
        }
        var priority = $('#bv-ticket-priority .prio-card.on').data('priority') || 'medium';
        var categoryId = $('#bv-ticket-category').val() || null;
        var assigneeId = $('#bv-ticket-assignee').val() || null;
        var description = $('#bv-ticket-description').val().trim();

        var convId = $('.bv-composer').data('bv-conversation-id');
        if (!convId) {
            if (window.toastr) toastr.error('No hay conversación activa.');
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin"></i> Creando...');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/ticket',
            method: 'POST',
            dataType: 'json',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                subject: subject,
                description: description,
                priority: priority,
                category_id: categoryId,
                assignee_id: assigneeId,
            },
            success: function (res) {
                if (window.toastr) toastr.success(res.message || 'Ticket creado correctamente.');
                // closeModal() lives in a different IIFE in this bundle and isn't
                // reachable from here; replicate its non-lightbox behavior inline.
                $('[data-bv-modal-name="create-ticket"]').removeClass('on');
                if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
                if (res.ticket_url) {
                    window.open(res.ticket_url, '_blank');
                }
            },
            error: function (xhr) {
                var msg = 'Error al crear el ticket.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                if (window.toastr) toastr.error(msg);
                else alert(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-ticket"></i> Crear ticket');
            }
        });
    });

    // ─── Ticket detail modal (rp3-ticket click) ───────────────────────────────
    $(document).on('click', '.rp3-ticket[data-bv-modal="ticket"]', function () {
        var $el = $(this).closest('.rp3-ticket[data-bv-modal="ticket"]');
        var ticketId = $el.data('ticket-id');
        if (!ticketId) return;

        var convId = $('.bv-composer').data('bv-conversation-id');
        var fallbackTitle = $el.find('.t').text() || 'Sin título';
        var fallbackNum = $el.find('.id').text() || 'T-—';

        // Show loading state
        $('#bv-ticket-modal-num').text(fallbackNum);
        $('#bv-ticket-modal-title').text(fallbackTitle);
        $('#bv-ticket-modal-desc').text('Cargando…');

        if (convId) {
            $.ajax({
                url: '/panel/helpdesk/conversations/' + convId + '/ticket-detail/' + ticketId,
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function(resp) {
                if (!resp || !resp.ticket) return;
                var t = resp.ticket;
                var prioColor = 'var(--info)';
                if (t.priority === 'urgent') prioColor = 'var(--danger)';
                else if (t.priority === 'high') prioColor = 'var(--warning)';
                else if (t.priority === 'low') prioColor = 'var(--success)';

                $('#bv-ticket-modal-num').text(t.ticket_number);
                $('#bv-ticket-modal-title').text(t.subject || 'Sin título');
                $('#bv-ticket-modal-pills').html(
                    '<span class="mv4-pill"><span class="d" style="background:' + prioColor + ';"></span>' + escapeHtml(t.priority || 'Normal') + '</span>' +
                    '<span class="mv4-pill"><span class="d" style="background:' + (t.status.color || 'var(--info)') + ';"></span>' + escapeHtml(t.status.name) + '</span>' +
                    (t.category ? '<span class="mv4-pill">' + escapeHtml(t.category) + '</span>' : '')
                );
                $('#bv-ticket-modal-desc').text(t.description || 'Sin descripción.');
                $('#bv-ticket-modal-cust-name').text(t.customer.name);
                $('#bv-ticket-modal-side-name').text(t.customer.name);
                $('#bv-ticket-modal-side-meta').text(t.customer.email);
                $('#bv-ticket-modal-avatar').text(t.customer.initials || '??');
                $('#bv-ticket-modal-assignee').text(t.assignee);
                $('#bv-ticket-modal-group').text(t.group);
                $('#bv-ticket-modal-created').text(t.created_at);
                $('#bv-ticket-modal-updated').text(t.updated_at);
                $('#bv-ticket-modal-priority').html('<span style="color:' + prioColor + ';">● ' + escapeHtml(t.priority || 'Normal') + '</span>');
                $('#bv-ticket-modal-activity').html(
                    '<div class="mv4-tl-item"><span class="dot success"></span><div><b>Sistema</b> creó el ticket <span class="t">· ' + escapeHtml(t.created_at) + '</span></div></div>'
                );
            }).fail(function() {
                $('#bv-ticket-modal-desc').text('No se pudo cargar el detalle del ticket.');
            });
        } else {
            $('#bv-ticket-modal-desc').text('No se pudo cargar el detalle del ticket.');
        }
    });

    // ─── Cargar datos de Remarketing en pestaña Carritos ───────────────────────
    var remarketingOrdersLoaded = {};
    document.addEventListener('click', function (e) {
        if (!$(e.target).closest('.bv-right-tab[data-bv-tab="carts"]').length) { return; }
        var customerId = $('.bv-right').data('customer-id');
        if (!customerId || remarketingOrdersLoaded[customerId]) return;
        var $tab = $('[data-bv-tab-content="carts"]');
        var $container = $tab.find('.rp3-scroll');
        if (!$container.length) {
            $container = $('<div class="rp3-scroll"></div>').appendTo($tab);
        }
        $container.prepend('<div id="bv-remarketing-loading" style="padding:12px;text-align:center;color:#9aa0ab;font-size:12px"><i class="fas fa-circle-notch fa-spin"></i> Cargando pedidos…</div>');
        $.ajax({
            url: '/panel/helpdesk/customers/' + customerId + '/ecommerce',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (resp) {
            $('#bv-remarketing-loading').remove();
            if (!resp || !resp.success) return;
            var html = '';
            if (resp.orders && resp.orders.length) {
                html += '<div class="rp3-section"><div class="rp3-sec-head">Historial de pedidos <span class="count">· ' + resp.orders.length + '</span></div>';
                resp.orders.forEach(function (o) {
                    var statusColor = 'var(--warning)';
                    var s = (o.status || '').toLowerCase();
                    if (s === 'completed' || s === 'complete' || s === 'entregado') statusColor = 'var(--success)';
                    else if (s === 'shipped' || s === 'enviado') statusColor = 'var(--info)';
                    else if (s === 'cancelled' || s === 'cancelado') statusColor = 'var(--danger)';
                    var dateStr = o.placed_at_human || '—';
                    var itemsHtml = '';
                    if (o.items && o.items.length) {
                        itemsHtml = '<div style="margin-top:6px;font-size:12px;color:#6c757d;">' + o.items.map(function(i){ return i.title + ' ×' + i.quantity; }).join(', ') + '</div>';
                    }
                    html += '<div class="rp3-order" style="cursor:pointer">' +
                        '<div class="thumb"><i class="fa-solid fa-box"></i></div>' +
                        '<div class="body">' +
                            '<div class="id">#' + escapeHtml(o.order_number || o.id) + '</div>' +
                            '<div class="t">' + (o.items_count || 0) + ' producto' + (o.items_count === 1 ? '' : 's') + '</div>' +
                            '<div class="meta">' +
                                '<b>' + (o.total ? o.total.toFixed(2).replace('.', ',') : '0,00') + ' ' + (o.currency || '€') + '</b> ' +
                                '<span style="color:' + statusColor + ';font-weight:600;">●</span> <span>' + escapeHtml(o.status || 'Pendiente') + '</span> <span>·</span> <span>' + escapeHtml(dateStr) + '</span>' +
                            '</div>' + itemsHtml +
                        '</div>' +
                    '</div>';
                });
                html += '</div>';
            }
            if (resp.carts && resp.carts.length) {
                resp.carts.forEach(function (c) {
                    var itemCount = c.items_count || 0;
                    html += '<div class="rp3-section"><div class="rp3-sec-head">Carrito abandonado</div>' +
                        '<div class="rp3-cart"><div class="hd"><span class="dot"></span><i class="fa-solid fa-cart-shopping"></i> ' + itemCount + ' item' + (itemCount === 1 ? '' : 's') + '</div>' +
                        '<div class="rp3-cart-items">';
                    (c.items || []).forEach(function (it) {
                        html += '<div class="rp3-cart-item"><div class="th"></div><div class="n">' + escapeHtml(it.title || 'Producto') + '</div><div class="p">' + (it.price ? it.price.toFixed(2).replace('.', ',') : '0,00') + ' €</div></div>';
                    });
                    html += '</div></div></div>';
                });
            }
            if (html) {
                $container.prepend(html);
            }
            remarketingOrdersLoaded[customerId] = true;
        }).fail(function () {
            $('#bv-remarketing-loading').remove();
        });
    }, true);

    // ─── Service Worker (PWA) ─────────────────────────────────────────────────
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function (err) {
            console.warn('[BV] SW registration failed:', err);
        });
    }

    // ─── Customer Data Lookup (multi-source: PS + ERP) ───────────────────────
    (function () {
        var $aside = $('.bv-right');
        if (!$aside.length || (!$aside.data('has-ps') && !$aside.data('has-erp'))) return;

        var lookupUrl = $aside.data('lookup-url');
        var csrf      = $aside.data('csrf');
        var inboxId   = $aside.data('inbox-id');
        var lookupEmail  = $aside.data('lookup-email') || null;
        var lookupPsId   = $aside.data('lookup-ps-id') || null;
        var lookupErpId  = $aside.data('lookup-erp-id') || null;
        if (!lookupEmail && !lookupPsId && !lookupErpId) return;

        // Cache de tabs ya cargados — evita peticiones duplicadas
        var loaded = {};

        // Los tabs PrestaShop (ps-*) y ERP (erp-*) ahora los aportan los módulos
        // HelpdeskPrestashop / HelpdeskErp como inbox-slots (render server-side).
        // El lazy-load de Engagement queda desactivado para no sobreescribirlos.
        var tabConfig = {};

        function esc(str) {
            if (str == null) return '';
            return String(str).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function emptyState(icon, title, sub) {
            return '<div class="bv-tab-empty">' +
                '<i class="fas fa-' + icon + '"></i>' +
                '<div class="bv-tab-empty-title">' + esc(title) + '</div>' +
                '<div class="bv-tab-empty-sub">' + esc(sub) + '</div>' +
                '</div>';
        }

        function fetchAndRender(tabName, force) {
            var cfg = tabConfig[tabName];
            if (!cfg) return;
            if (!force && loaded[tabName]) return;

            var $target = $(cfg.target);
            if (!$target.length) return;

            $target.html('<div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');

            $.ajax({
                url: lookupUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                data: {
                    inbox_id: inboxId,
                    lookup: {
                        email: lookupEmail,
                        external_id: cfg.platform === 'erp' ? lookupErpId : lookupPsId,
                    },
                    actions: cfg.actions,
                    platform: cfg.platform,
                    force: force ? 1 : 0,
                },
                success: function (resp) {
                    if (!resp.success) {
                        $target.html(emptyState('triangle-exclamation', 'Error al cargar', ''));
                        return;
                    }
                    var platformData = ((resp.data || {})[cfg.platform]) || {};
                    $target.html(cfg.renderer(platformData));
                    loaded[tabName] = true;
                },
                error: function () {
                    if (window.toastr) toastr.error('No se pudieron cargar los datos');
                    $target.html(emptyState('triangle-exclamation', 'Error de red', ''));
                },
            });
        }

        function renderPsOrders(d) {
            var o = d.orders;
            if (!o || !o.ok || !o.data || !o.data.orders || !o.data.orders.length) {
                return emptyState('bag-shopping', 'Sin pedidos', 'No hay pedidos registrados en PrestaShop');
            }
            // PS state_id → CSS color var (2=Pago aceptado, 3=Preparando, 4=Enviado, 5=Entregado, 6=Cancelado, 7=Reembolsado)
            var stateColorMap = {
                2: 'var(--bv-info, #1d4ed8)',
                3: 'var(--bv-warning, #b45309)',
                4: 'var(--bv-info, #1d4ed8)',
                5: 'var(--bv-success, #0d7a3f)',
                6: 'var(--bv-danger, #b91c1c)',
                7: 'var(--bv-warning, #b45309)'
            };
            var html = '<div class="rp3-scroll"><div class="rp3-section">';
            html += '<div class="rp3-sec-head">Pedidos <span class="count">· ' + o.data.orders.length + '</span></div>';
            o.data.orders.forEach(function (ord) {
                var stLabel = ord.state_name || '—';
                var stColor = stateColorMap[ord.state_id] || 'var(--bv-text-muted, #8c929d)';
                var dateStr = (ord.created_at || ord.date_add || '').substring(0, 10);
                var ref     = ord.reference || ('#' + ord.id);
                html += '<div class="rp3-order"' +
                    ' data-order-id="' + esc(ord.id) + '"' +
                    ' data-order-platform="prestashop"' +
                    ' data-order-ref="' + esc(ref) + '">' +
                    '<div class="thumb"><i class="fas fa-box"></i></div>' +
                    '<div class="body">' +
                        '<div class="id">' + esc(ref) + '</div>' +
                        '<div class="meta">' +
                            '<b>' + esc(parseFloat(ord.total || 0).toFixed(2)) + ' €</b>' +
                            '<span style="color:' + stColor + ';font-weight:600;">●</span>' +
                            '<span>' + esc(stLabel) + '</span>' +
                            (dateStr ? '<span>·</span><span>' + esc(dateStr) + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                '</div>';
            });
            html += '</div></div>';
            return html;
        }

        function renderPsReturns(d) {
            var r = d.returns;
            if (!r || !r.ok || !r.data || !r.data.returns || !r.data.returns.length) {
                return emptyState('rotate-left', 'Sin devoluciones', 'No hay devoluciones registradas en PrestaShop');
            }
            var html = '<div class="rp3-scroll"><div class="rp3-section">';
            html += '<div class="rp3-sec-head">Devoluciones <span class="count">· ' + r.data.returns.length + '</span></div>';
            r.data.returns.forEach(function (ret) {
                html += '<div class="rp3-order">' +
                    '<div class="thumb"><i class="fas fa-rotate-left"></i></div>' +
                    '<div class="body">' +
                        '<div class="id">#' + esc(ret.order_reference || ret.id) + '</div>' +
                        '<div class="t">' + esc(ret.reason || 'Devolución') + '</div>' +
                        '<div class="meta"><b>' + (ret.items ? ret.items.length : 0) + ' item(s)</b> · <span>' + esc(ret.state_name || '') + '</span> · <span>' + esc((ret.created_at || '').substring(0, 10)) + '</span></div>' +
                    '</div>' +
                '</div>';
            });
            html += '</div></div>';
            return html;
        }

        function renderPsVouchers(d) {
            var v = d.vouchers;
            if (!v || !v.ok || !v.data || !v.data.vouchers || !v.data.vouchers.length) {
                return emptyState('tag', 'Sin cupones', 'Este cliente no tiene cupones asignados');
            }
            var html = '<div class="rp3-scroll"><div class="rp3-section">';
            html += '<div class="rp3-sec-head">Cupones <span class="count">· ' + v.data.vouchers.length + '</span></div>';
            v.data.vouchers.forEach(function (vo) {
                var reduction = vo.reduction_percent > 0 ? (vo.reduction_percent + '%') :
                    vo.reduction_amount > 0 ? (parseFloat(vo.reduction_amount).toFixed(2) + ' €') :
                    vo.free_shipping ? 'Envío gratis' : '—';
                html += '<div class="rp3-order' + (vo.expired ? ' is-expired' : '') + '">' +
                    '<div class="thumb"><i class="fas fa-tag"></i></div>' +
                    '<div class="body">' +
                        '<div class="id">' + esc(vo.code || '—') + '</div>' +
                        '<div class="t">' + esc(vo.description || '') + '</div>' +
                        '<div class="meta"><b>' + reduction + '</b>' +
                            (vo.date_to ? ' · hasta ' + esc(String(vo.date_to).substring(0, 10)) : '') +
                            (vo.expired ? ' · <span class="text-muted">expirado</span>' : '') +
                        '</div>' +
                    '</div>' +
                '</div>';
            });
            html += '</div></div>';
            return html;
        }

        function renderPsAddresses(d) {
            var a = d.addresses;
            if (!a || !a.ok || !a.data || !a.data.addresses || !a.data.addresses.length) {
                return emptyState('location-dot', 'Sin direcciones', 'No hay direcciones registradas');
            }
            var html = '<div class="rp3-scroll"><div class="rp3-section">';
            html += '<div class="rp3-sec-head">Direcciones <span class="count">· ' + a.data.addresses.length + '</span></div>';
            a.data.addresses.forEach(function (ad) {
                html += '<div class="rp3-order">' +
                    '<div class="thumb"><i class="fas fa-location-dot"></i></div>' +
                    '<div class="body">' +
                        '<div class="id">' + esc(ad.alias || ad.id) + '</div>' +
                        '<div class="t">' + esc((ad.firstname || '') + ' ' + (ad.lastname || '')) + '</div>' +
                        '<div class="meta">' + esc(ad.address || '') + ' · ' + esc(ad.postcode || '') + ' ' + esc(ad.city || '') + ' · ' + esc(ad.country || '') + '</div>' +
                        (ad.phone ? '<div class="meta"><i class="fas fa-phone"></i> ' + esc(ad.phone) + '</div>' : '') +
                    '</div>' +
                '</div>';
            });
            html += '</div></div>';
            return html;
        }

        function renderErpOrders(d) {
            var orders = (d.orders && d.orders.ok && d.orders.data) ? d.orders.data : [];
            var notes  = (d.deliveryNotes && d.deliveryNotes.ok && d.deliveryNotes.data) ? d.deliveryNotes.data : [];
            var html   = '<div class="rp3-scroll">';

            if (Array.isArray(orders) && orders.length) {
                html += '<div class="rp3-section"><div class="rp3-sec-head">Pedidos en gestión <span class="count">· ' + orders.length + '</span></div>';
                orders.slice(0, 20).forEach(function (o) {
                    var oRef    = '#' + (o.id || o.numpedido || '—');
                    var oStatus = o.estado || o.status || 'Pedido';
                    var oDate   = o.fpedido ? String(o.fpedido).substring(0, 10) : '';
                    var oTotal  = o.total != null ? parseFloat(o.total).toFixed(2).replace('.', ',') : '';
                    var oProducts = Array.isArray(o.lineas || o.products) ? (o.lineas || o.products).map(function (l) {
                        return { name: l.descripcion || l.name || 'Producto', qty: l.cantidad || l.qty || 1, price: l.precio || l.price || 0 };
                    }) : [];
                    html += '<div class="rp3-order" data-bv-modal="order" data-order-platform="erp"' +
                        ' data-order-ref="' + esc(oRef) + '"' +
                        ' data-order-status="' + esc(oStatus) + '"' +
                        ' data-order-date="' + esc(oDate) + '"' +
                        ' data-order-total="' + esc(oTotal) + '"' +
                        " data-order-products='" + esc(JSON.stringify(oProducts)) + "'>" +
                        '<div class="thumb"><i class="fas fa-clipboard-list"></i></div>' +
                        '<div class="body">' +
                            '<div class="head"><span class="id">' + esc(oRef) + '</span>' +
                                '<span class="st ' + window.bvOrderStatusClass(oStatus) + '">' + esc(oStatus) + '</span></div>' +
                            '<div class="meta"><b>' + (oTotal ? oTotal + ' €' : '') + '</b>' + (oDate ? '<span>· ' + esc(oDate) + '</span>' : '') + '</div>' +
                        '</div>' +
                    '</div>';
                });
                html += '</div>';
            }

            if (Array.isArray(notes) && notes.length) {
                html += '<div class="rp3-section"><div class="rp3-sec-head">Albaranes <span class="count">· ' + notes.length + '</span></div>';
                notes.slice(0, 10).forEach(function (n) {
                    html += '<div class="rp3-order">' +
                        '<div class="thumb"><i class="fas fa-truck"></i></div>' +
                        '<div class="body">' +
                            '<div class="id">#' + esc(n.id || n.numalbaran || '—') + '</div>' +
                            '<div class="meta">' + esc(String(n.falbaran || '').substring(0, 10)) + '</div>' +
                        '</div>' +
                    '</div>';
                });
                html += '</div>';
            }

            if (!orders.length && !notes.length) {
                html += emptyState('clipboard-list', 'Sin actividad en gestión', 'No hay pedidos ni albaranes registrados');
            }
            html += '</div>';
            return html;
        }

        function renderErpFinance(d) {
            var debts    = (d.debts && d.debts.ok && d.debts.data) ? d.debts.data : null;
            var balance  = (d.balance && d.balance.ok && d.balance.data) ? d.balance.data : null;
            var invoices = (d.invoices && d.invoices.ok && d.invoices.data) ? d.invoices.data : [];
            var html     = '<div class="rp3-scroll">';

            if (balance && typeof balance === 'object') {
                html += '<div class="rp3-section"><div class="rp3-sec-head">Balance</div><div class="rp3-stats">';
                Object.keys(balance).slice(0, 4).forEach(function (k) {
                    html += '<div class="rp3-stat"><div class="lbl">' + esc(k) + '</div><div class="val">' + esc(String(balance[k])) + '</div></div>';
                });
                html += '</div></div>';
            }

            if (debts) {
                var list = Array.isArray(debts) ? debts : [debts];
                if (list.length) {
                    html += '<div class="rp3-section"><div class="rp3-sec-head">Deudas pendientes</div>';
                    list.slice(0, 10).forEach(function (de) {
                        html += '<div class="rp3-order"><div class="thumb"><i class="fas fa-exclamation-circle"></i></div><div class="body">' +
                            '<div class="t">' + esc(de.concepto || de.descripcion || 'Deuda') + '</div>' +
                            '<div class="meta"><b>' + (de.importe != null ? parseFloat(de.importe).toFixed(2) + ' €' : '—') + '</b></div>' +
                        '</div></div>';
                    });
                    html += '</div>';
                }
            }

            if (Array.isArray(invoices) && invoices.length) {
                html += '<div class="rp3-section"><div class="rp3-sec-head">Facturas <span class="count">· ' + invoices.length + '</span></div>';
                invoices.slice(0, 10).forEach(function (inv) {
                    html += '<div class="rp3-order"><div class="thumb"><i class="fas fa-file-invoice"></i></div><div class="body">' +
                        '<div class="id">#' + esc(inv.id || inv.numfactura || '—') + '</div>' +
                        '<div class="meta"><b>' + (inv.total != null ? parseFloat(inv.total).toFixed(2) + ' €' : '') + '</b> · ' + esc(String(inv.ffactura || '').substring(0, 10)) + '</div>' +
                    '</div></div>';
                });
                html += '</div>';
            }

            if (!balance && !debts && !invoices.length) {
                html += emptyState('coins', 'Sin datos financieros', 'No hay información financiera disponible');
            }
            html += '</div>';
            return html;
        }

        function renderErpLoyalty(d) {
            var points = (d.loyaltyPoints && d.loyaltyPoints.ok && d.loyaltyPoints.data) ? d.loyaltyPoints.data : null;
            var bonuses = (d.bonuses && d.bonuses.ok && d.bonuses.data) ? d.bonuses.data : [];
            var html = '<div class="rp3-scroll">';

            if (points != null) {
                var total = (points && typeof points === 'object') ? (points.total || points.puntos || 0) : points;
                html += '<div class="rp3-section"><div class="rp3-sec-head">Puntos de fidelización</div>' +
                    '<div class="rp3-points-card">' +
                        '<div class="rp3-points-val">' + esc(String(total)) + '</div>' +
                        '<div class="rp3-points-lbl">puntos disponibles</div>' +
                    '</div>' +
                '</div>';
            }

            if (Array.isArray(bonuses) && bonuses.length) {
                html += '<div class="rp3-section"><div class="rp3-sec-head">Bonos <span class="count">· ' + bonuses.length + '</span></div>';
                bonuses.slice(0, 10).forEach(function (b) {
                    html += '<div class="rp3-order"><div class="thumb"><i class="fas fa-gift"></i></div><div class="body">' +
                        '<div class="t">' + esc(b.nombre || b.descripcion || 'Bono') + '</div>' +
                        '<div class="meta">' + esc(b.estado || '') + '</div>' +
                    '</div></div>';
                });
                html += '</div>';
            }

            if (points == null && !bonuses.length) {
                html += emptyState('star', 'Sin fidelización', 'No hay puntos ni bonos registrados');
            }
            html += '</div>';
            return html;
        }

        // Lazy-load al hacer clic en un tab de integración
        $(document).on('click', '[data-bv-tab]', function () {
            var tab = $(this).data('bv-tab');
            if (tabConfig[tab]) fetchAndRender(tab, false);
        });

        // Refresco manual desde el botón de la tab Pedidos
        $(document).on('click', '.bv-refresh-source', function () {
            var tab = $('.bv-right-tab.on').data('bv-tab');
            if (tabConfig[tab]) fetchAndRender(tab, true);
        });

        // ─── Order detail modal ───────────────────────────────────────
        var psStoreUrl = $aside.data('ps-store-url') || '';

        function resetOrderModal(ref) {
            $('#bv-order-modal-chip').text(ref || '#—');
            $('#bv-order-modal-date').text('—');
            $('#bv-order-modal-status').text('Cargando…').attr('class', 'bv-ov-st');
            $('#bv-order-modal-payment').text('—');
            $('#bv-order-modal-products-count').text('');
            $('#bv-order-modal-products').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i></div>');
            $('#bv-order-modal-subtotal, #bv-order-modal-shipping-val, #bv-order-modal-tax, #bv-order-modal-total').text('—');
            $('#bv-order-modal-tax-row').removeClass('bv-hidden');
            $('#bv-order-modal-discount-row').addClass('bv-hidden');
            $('#bv-order-modal-tracking-field').addClass('bv-hidden');
            $('#bv-order-modal-tracking-empty').removeClass('bv-hidden');
            $('#bv-order-modal-history-field').addClass('bv-hidden');
            $('#bv-order-modal-states-empty').removeClass('bv-hidden');
            setAddressTab(null, null);
            $('#bv-order-modal-order-id').text('—');
            $('#bv-order-modal-reference').text('—');
            $('#bv-order-modal-external-link').addClass('bv-hidden');
            // Reset to info tab
            $('[data-bv-om-tab]').removeClass('is-active');
            $('[data-bv-om-tab="info"]').addClass('is-active');
            $('[data-bv-om-panel]').removeClass('is-active');
            $('[data-bv-om-panel="info"]').addClass('is-active');
        }

        function populateOrderModal(order) {
            var cur   = order.currency || 'EUR';
            var ref   = order.reference || ('#' + order.id);
            var state = order.state_name || '—';
            var date  = (order.created_at || '').substring(0, 10);

            $('#bv-order-modal-chip').text(ref);
            $('#bv-order-modal-date').text(date || '—');
            $('#bv-order-modal-status')
                .text(state)
                .attr('class', 'bv-ov-st ' + window.bvOrderStatusClass(state));

            // Nº pedido y referencia
            $('#bv-order-modal-order-id').text(order.id ? '#' + order.id : '—');
            $('#bv-order-modal-reference').text(order.reference || '—');

            // Products
            var lines = order.lines || [];
            $('#bv-order-modal-products-count').text(lines.length ? lines.length + (lines.length === 1 ? ' artículo' : ' artículos') : '');
            var prodsHtml = '';
            lines.forEach(function (l) {
                var lineTotal = parseFloat(l.total || (l.unit_price || 0) * (l.quantity || 1)) || 0;
                var unitPrice = parseFloat(l.unit_price || 0);
                var metaParts = [];
                if (l.reference) { metaParts.push('Ref: ' + esc(l.reference)); }
                metaParts.push('\xd7' + (l.quantity || 1));
                if (unitPrice > 0) { metaParts.push(cur + '\xa0' + unitPrice.toFixed(2)); }
                var nameContent = esc(l.name);
                if (l.url) {
                    nameContent = '<a class="bv-om-pc-name-link" href="' + esc(l.url) + '" target="_blank" rel="noopener">' +
                        nameContent + ' <i class="fas fa-arrow-up-right-from-square"></i></a>';
                }
                prodsHtml +=
                    '<div class="bv-om-prod-card">' +
                        '<div class="bv-om-pc-thumb"><i class="fas fa-box"></i></div>' +
                        '<div class="bv-om-pc-info">' +
                            '<div class="bv-om-pc-name">' + nameContent + '</div>' +
                            '<div class="bv-om-pc-meta">' + metaParts.join(' \xb7 ') + '</div>' +
                        '</div>' +
                        '<div class="bv-om-pc-price">' + cur + '\xa0' + lineTotal.toFixed(2) + '</div>' +
                    '</div>';
            });
            $('#bv-order-modal-products').html(
                prodsHtml || '<div class="bv-oc-empty"><i class="fas fa-box-open"></i><div class="title">Sin productos</div></div>'
            );

            // Totals
            var tot = order.totals || {};
            var ship = parseFloat(tot.shipping || 0);
            var disc = parseFloat(tot.discount || 0);
            $('#bv-order-modal-subtotal').text(parseFloat(tot.subtotal || 0).toFixed(2) + ' ' + cur);
            $('#bv-order-modal-shipping-val').text(ship > 0 ? ship.toFixed(2) + ' ' + cur : 'Gratis');
            $('#bv-order-modal-tax').text(parseFloat(tot.tax || 0).toFixed(2) + ' ' + cur);
            $('#bv-order-modal-tax-row').removeClass('bv-hidden');
            if (disc > 0) {
                $('#bv-order-modal-discount').text('− ' + disc.toFixed(2) + ' ' + cur);
                $('#bv-order-modal-discount-row').removeClass('bv-hidden');
            } else {
                $('#bv-order-modal-discount-row').addClass('bv-hidden');
            }
            $('#bv-order-modal-total').text(parseFloat(tot.total || 0).toFixed(2) + ' ' + cur);

            // Payment
            var payments = order.payments || [];
            $('#bv-order-modal-payment').text(payments.length ? (payments[0].payment_method || 'Pago') : '—');

            // Customer + dirección
            var custName = ($('.bv-cp-name-btn').first().text() || $('.bv-right-name').first().text() || '—').trim();
            $('#bv-order-modal-cust-name').text(custName);

            var shippingAddr = order.shipping_address || order.address || null;
            var billingAddr  = order.billing_address  || order.invoice_address || null;
            setAddressTab(shippingAddr, billingAddr);

            // Panel Seguimiento
            var tracking = order.tracking || [];
            if (tracking.length) {
                var trackHtml = '';
                tracking.forEach(function (t) {
                    var tn = t.tracking_number || '';
                    if (!tn) { return; }
                    var meta = [];
                    if (t.carrier_name) { meta.push(esc(t.carrier_name)); }
                    if (t.weight && parseFloat(t.weight) > 0) { meta.push(parseFloat(t.weight).toFixed(2) + ' kg'); }
                    trackHtml +=
                        '<div class="bv-ov-track">' +
                            '<i class="fa-solid fa-truck"></i>' +
                            '<div class="bv-ov-track-body">' +
                                '<b>' + esc(tn) + '</b>' +
                                (meta.length ? '<span class="bv-ov-track-meta">' + meta.join(' · ') + '</span>' : '') +
                            '</div>' +
                            '<button type="button" class="bv-ov-copy bv-copy-tracking" data-val="' + esc(tn) + '" title="Copiar">' +
                                '<i class="fa-regular fa-copy"></i>' +
                            '</button>' +
                        '</div>';
                });
                if (trackHtml) {
                    $('#bv-order-modal-tracking-container').html(trackHtml);
                    $('#bv-order-modal-tracking-field').removeClass('bv-hidden');
                    $('#bv-order-modal-tracking-empty').addClass('bv-hidden');
                } else {
                    $('#bv-order-modal-tracking-field').addClass('bv-hidden');
                    $('#bv-order-modal-tracking-empty').removeClass('bv-hidden');
                }
            } else {
                $('#bv-order-modal-tracking-field').addClass('bv-hidden');
                $('#bv-order-modal-tracking-empty').removeClass('bv-hidden');
            }

            // Panel Estados
            var history = order.history || [];
            if (history.length) {
                var histHtml = '<div class="bv-order-timeline">';
                history.forEach(function (h, i) {
                    var isLast = i === history.length - 1;
                    var d = (h.date || '').substring(0, 16).replace('T', ' ');
                    histHtml +=
                        '<div class="bv-ot-step' + (isLast ? ' is-current' : '') + '">' +
                            '<div class="bv-ot-dot" style="background:' + esc(h.color || '#ccc') + '"></div>' +
                            '<div class="bv-ot-body">' +
                                '<span class="bv-ot-name">' + esc(h.state_name || '') + '</span>' +
                                '<span class="bv-ot-date">' + esc(d) + '</span>' +
                            '</div>' +
                        '</div>';
                });
                histHtml += '</div>';
                $('#bv-order-modal-history').html(histHtml);
                $('#bv-order-modal-history-field').removeClass('bv-hidden');
                $('#bv-order-modal-states-empty').addClass('bv-hidden');
            } else {
                $('#bv-order-modal-history-field').addClass('bv-hidden');
                $('#bv-order-modal-states-empty').removeClass('bv-hidden');
            }

            // External link
            if (psStoreUrl && order.id) {
                var adminUrl = psStoreUrl.replace(/\/+$/, '') + '/index.php?controller=AdminOrders&id_order=' + order.id + '&vieworder=1';
                $('#bv-order-modal-external-link')
                    .attr('href', adminUrl)
                    .html('<i class="fas fa-arrow-up-right-from-square"></i> Abrir en PrestaShop')
                    .removeClass('bv-hidden');
            }
        }

        function openOrderModal() {
            var $m = $('[data-bv-modal-name="order"]');
            if ($m.length) { $m.addClass('on'); $('body').css('overflow', 'hidden'); }
        }

        // Tab switching dentro del modal de pedido (3 tabs: info / tracking / states)
        $(document).on('click', '[data-bv-modal-name="order"] [data-bv-om-tab]', function () {
            var tab = $(this).data('bv-om-tab');
            var $modal = $('[data-bv-modal-name="order"]');
            $modal.find('[data-bv-om-tab]').removeClass('is-active');
            $(this).addClass('is-active');
            $modal.find('[data-bv-om-panel]').removeClass('is-active');
            $modal.find('[data-bv-om-panel="' + tab + '"]').addClass('is-active');
        });

        // Caché en memoria para detalles PS: la primera apertura hace AJAX,
        // las siguientes son instantáneas. Se invalida al cerrar el modal
        // de la conversación (SPA nav) o al recargar.
        var psDetailCache = {};

        function showPsOrderSkeleton(ref) {
            var skLine = '<div class="bv-om-prod-skeleton"><div class="bv-sk-thumb"></div><div class="bv-sk-body"><div class="bv-sk-line w70"></div><div class="bv-sk-line w40"></div></div></div>';
            $('#bv-order-modal-chip').text(ref || '#—');
            $('#bv-order-modal-cust-name').text('—');
            $('#bv-order-modal-order-id').text('—');
            $('#bv-order-modal-reference').text('—');
            $('#bv-order-modal-date').text('—');
            $('#bv-order-modal-status').text('—').attr('class', 'bv-ov-st');
            $('#bv-order-modal-payment').text('—');
            setAddressTab(null, null);
            $('#bv-order-modal-subtotal').text('—');
            $('#bv-order-modal-shipping-val').text('—');
            $('#bv-order-modal-total').text('—');
            $('#bv-order-modal-products-count').text('');
            $('#bv-order-modal-tracking-field').addClass('bv-hidden');
            $('#bv-order-modal-tracking-empty').removeClass('bv-hidden');
            $('#bv-order-modal-history-field').addClass('bv-hidden');
            $('#bv-order-modal-states-empty').removeClass('bv-hidden');
            $('#bv-order-modal-external-link').addClass('bv-hidden');
            // Reset to info tab
            $('[data-bv-modal-name="order"] [data-bv-om-tab]').removeClass('is-active');
            $('[data-bv-modal-name="order"] [data-bv-om-tab="info"]').addClass('is-active');
            $('[data-bv-modal-name="order"] [data-bv-om-panel]').removeClass('is-active');
            $('[data-bv-modal-name="order"] [data-bv-om-panel="info"]').addClass('is-active');
            $('#bv-order-modal-products').html(skLine + skLine);
        }

        // Deferred callbacks for in-flight pre-warm requests
        var psPrewarmDeferreds = {};

        function fetchPsOrderDetail(orderId, baseUrl, custEmail, onDone) {
            if (!orderId || !baseUrl) { return; }

            // Already cached — call onDone immediately
            if (psDetailCache[orderId]) {
                if (onDone) { onDone(psDetailCache[orderId]); }
                return;
            }

            // Already in flight — queue the callback
            if (psPrewarmDeferreds[orderId]) {
                if (onDone) { psPrewarmDeferreds[orderId].push(onDone); }
                return;
            }

            psPrewarmDeferreds[orderId] = onDone ? [onDone] : [];
            var detailUrl = baseUrl + orderId + '/detail' + (custEmail ? '?email=' + encodeURIComponent(custEmail) : '');

            $.ajax({
                url: detailUrl,
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                success: function (resp) {
                    if (resp.success && resp.data) {
                        psDetailCache[orderId] = resp.data;
                        var cbs = psPrewarmDeferreds[orderId] || [];
                        delete psPrewarmDeferreds[orderId];
                        cbs.forEach(function (cb) { cb(resp.data); });
                    } else {
                        delete psPrewarmDeferreds[orderId];
                    }
                },
                error: function () {
                    delete psPrewarmDeferreds[orderId];
                },
            });
        }

        // Pre-warm on hover so the modal is instant on click
        $(document).on('mouseenter', '.rp3-order[data-order-platform="prestashop"]', function () {
            var orderId = String($(this).data('order-id') || '');
            var baseUrl = $('#bv-ps-orders').data('ps-order-detail-url') || '';
            var custEmail = ($('[data-customer-email]').first().data('customer-email') || '').trim();
            fetchPsOrderDetail(orderId, baseUrl, custEmail, null);
        });

        $(document).on('click', '.rp3-order[data-order-platform="prestashop"]', function (e) {
            e.stopImmediatePropagation();
            var $el     = $(this);
            var orderId = String($el.data('order-id') || '');
            var ref     = $el.data('order-ref') || '#—';
            var baseUrl = $('#bv-ps-orders').data('ps-order-detail-url') || '';
            var custEmail = ($('[data-customer-email]').first().data('customer-email') || '').trim();

            // Always open modal immediately; populate with data or skeleton
            if (psDetailCache[orderId]) {
                openOrderModal();
                populateOrderModal(psDetailCache[orderId]);
                return;
            }

            showPsOrderSkeleton(ref);
            openOrderModal();

            fetchPsOrderDetail(orderId, baseUrl, custEmail, function (data) {
                populateOrderModal(data);
            });
        });

        // Copy tracking number
        $(document).on('click', '.bv-copy-tracking', function () {
            var val = $(this).data('val');
            if (val && navigator.clipboard) {
                navigator.clipboard.writeText(val).then(function () {
                    if (window.toastr) { toastr.success('Número de tracking copiado'); }
                });
            }
        });

        // ─── ERP tabs lazy-loading ──────────────────────────────────────────
        (function () {
            if (!$aside.data('has-erp')) { return; }

            var $erpTab    = $('#bv-erp-orders');
            var contextUrl = $erpTab.data('erp-context-url') || '';
            var detailBase = $erpTab.data('erp-order-detail-url-base') || '';
            if (!contextUrl) { return; }

            var erpCache         = null;
            var erpFetching      = false;
            var erpDeferreds     = [];
            var erpDetailCache   = {};
            var erpDetailDefers  = {};
            var erpDetailPrewarm = {};

            function showErpSkeleton(tabName) {
                var skRow = '<div class="bv-tab-sk-row"><div class="bv-sk-circle"></div><div class="bv-sk-body"><div class="bv-sk-line w60"></div><div class="bv-sk-line w40"></div></div></div>';
                $('[data-bv-tab-content="' + tabName + '"]').html(
                    '<div class="rp3-scroll"><div class="rp3-section">' + skRow + skRow + skRow + '</div></div>'
                );
            }

            function fetchErpContext(onDone) {
                if (erpCache) { if (onDone) { onDone(erpCache); } return; }
                if (erpFetching) { if (onDone) { erpDeferreds.push(onDone); } return; }
                erpFetching = true;
                if (onDone) { erpDeferreds.push(onDone); }

                var email      = $aside.data('lookup-email') || $aside.data('customer-email') || '';
                var custId     = $aside.data('customer-id') || '';
                var url        = contextUrl + '?email=' + encodeURIComponent(email) + (custId ? '&customer_id=' + custId : '');

                $.ajax({
                    url: url, method: 'GET',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    success: function (resp) {
                        erpFetching = false;
                        if (resp.success && resp.data) { erpCache = resp.data; }
                        var cbs = erpDeferreds.splice(0);
                        cbs.forEach(function (cb) { cb(erpCache); });
                    },
                    error: function () {
                        erpFetching = false;
                        var cbs = erpDeferreds.splice(0);
                        cbs.forEach(function (cb) { cb(null); });
                    },
                });
            }

            function renderErpOrdersTab(data) {
                var $t    = $('#bv-erp-orders');
                var cust  = (data && data.customer) || {};
                var custId = cust.id || $t.data('erp-customer-id') || '';
                var orders = (data && Array.isArray(data.orders)) ? data.orders : [];
                var html  = '<div class="rp3-scroll">';
                if (orders.length) {
                    html += '<div class="rp3-section"><div class="rp3-sec-head">Pedidos ERP <span class="count">· ' + orders.length + '</span></div>';
                    var erpStatusLabel = { 0:'Pendiente', 1:'Confirmado', 2:'En preparación', 3:'Enviado', 5:'Entregado', 7:'Servido', 9:'Cancelado' };
                    orders.slice(0, 20).forEach(function (o) {
                        var oRef    = o.number ? ('#' + o.number) : (o.id ? ('#' + o.id) : '—');
                        var oStatus = (typeof o.status === 'number') ? (erpStatusLabel[o.status] || ('Estado ' + o.status)) : (o.status || 'Pedido');
                        var oDate   = o.date ? String(o.date).substring(0, 10) : '';
                        html += '<div class="rp3-order rp3-erp-order" data-order-platform="erp"' +
                            ' data-order-id="' + esc(String(o.id || '')) + '"' +
                            ' data-erp-customer-id="' + esc(String(custId)) + '"' +
                            ' data-order-ref="' + esc(oRef) + '"' +
                            ' data-order-status="' + esc(oStatus) + '"' +
                            ' data-order-date="' + esc(oDate) + '">' +
                            '<div class="thumb"><i class="fas fa-clipboard-list"></i></div>' +
                            '<div class="body">' +
                                '<div class="head"><span class="id">' + esc(oRef) + '</span>' +
                                    '<span class="st ' + window.bvOrderStatusClass(oStatus) + '">' + esc(oStatus) + '</span></div>' +
                                '<div class="meta">' + esc(oDate) +
                                    (o.observations ? ' · <span>' + esc(String(o.observations).substring(0, 40)) + '</span>' : '') +
                                '</div>' +
                            '</div></div>';
                    });
                    html += '</div>';
                } else {
                    html += '<div class="bv-tab-empty"><i class="fas fa-clipboard-list"></i>' +
                        '<div class="bv-tab-empty-title">Sin pedidos ERP</div>' +
                        '<div class="bv-tab-empty-sub">No hay pedidos en gestión</div></div>';
                }
                $t.html(html + '</div>');
                syncRightTabVisibility();
            }

            function renderErpFinanceTab(data) {
                var cust     = (data && data.customer) || {};
                var invoices = (data && Array.isArray(data.invoices)) ? data.invoices : [];
                var html     = '<div class="rp3-scroll">';
                if (cust.found && (cust.credit_limit != null || cust.balance != null || cust.payment_terms)) {
                    html += '<div class="rp3-section"><div class="rp3-sec-head">Balance</div><div class="rp3-stats">';
                    if (cust.credit_limit != null) {
                        html += '<div class="rp3-stat"><div class="lbl">Límite crédito</div><div class="val">' + esc(parseFloat(cust.credit_limit).toFixed(2)) + ' €</div></div>';
                    }
                    if (cust.balance != null) {
                        html += '<div class="rp3-stat"><div class="lbl">Saldo pendiente</div><div class="val">' + esc(parseFloat(cust.balance).toFixed(2)) + ' €</div></div>';
                    }
                    if (cust.payment_terms) {
                        html += '<div class="rp3-stat"><div class="lbl">Forma de pago</div><div class="val">' + esc(cust.payment_terms) + '</div></div>';
                    }
                    html += '</div></div>';
                }
                if (invoices.length) {
                    html += '<div class="rp3-section"><div class="rp3-sec-head">Facturas <span class="count">· ' + invoices.length + '</span></div>';
                    invoices.slice(0, 15).forEach(function (inv) {
                        var invRef  = inv.number ? ('#' + inv.number) : (inv.id ? ('#' + inv.id) : '—');
                        var invDate = inv.date ? String(inv.date).substring(0, 10) : '';
                        html += '<div class="rp3-order"><div class="thumb"><i class="fas fa-file-invoice"></i></div><div class="body">' +
                            '<div class="head"><span class="id">' + esc(invRef) + '</span>' +
                                (inv.status ? '<span class="st ' + window.bvOrderStatusClass(inv.status) + '">' + esc(inv.status) + '</span>' : '') +
                            '</div>' +
                            '<div class="meta">' + esc(invDate) + (inv.payment_method ? ' · ' + esc(inv.payment_method) : '') + '</div>' +
                        '</div></div>';
                    });
                    html += '</div>';
                }
                if (html === '<div class="rp3-scroll">') {
                    html += '<div class="bv-tab-empty"><i class="fas fa-coins"></i>' +
                        '<div class="bv-tab-empty-title">Sin datos financieros</div>' +
                        '<div class="bv-tab-empty-sub">No hay información financiera disponible</div></div>';
                }
                $('#bv-erp-finance').html(html + '</div>');
                syncRightTabVisibility();
            }

            function renderErpLoyaltyTab(data) {
                var cust = (data && data.customer) || {};
                var html = '<div class="rp3-scroll">';
                if (cust.found && cust.loyalty_points != null) {
                    html += '<div class="rp3-section"><div class="rp3-sec-head">Fidelización</div>' +
                        '<div class="rp3-stats"><div class="rp3-stat">' +
                            '<div class="lbl">Puntos acumulados</div>' +
                            '<div class="val">' + esc(String(cust.loyalty_points)) + '</div>' +
                        '</div></div></div>';
                } else {
                    html += '<div class="bv-tab-empty"><i class="fas fa-star"></i>' +
                        '<div class="bv-tab-empty-title">Sin fidelización</div>' +
                        '<div class="bv-tab-empty-sub">No hay puntos registrados</div></div>';
                }
                $('#bv-erp-loyalty').html(html + '</div>');
                syncRightTabVisibility();
            }

            function renderForTab(tabName, data) {
                if (!data) {
                    $('[data-bv-tab-content="' + tabName + '"]').html(
                        '<div class="bv-tab-empty"><i class="fas fa-triangle-exclamation"></i>' +
                        '<div class="bv-tab-empty-title">Error al cargar</div>' +
                        '<div class="bv-tab-empty-sub">No se pudo obtener el contexto ERP</div></div>'
                    );
                    syncRightTabVisibility();
                    return;
                }
                if (tabName === 'erp-orders') { renderErpOrdersTab(data); }
                else if (tabName === 'erp-finance') { renderErpFinanceTab(data); }
                else if (tabName === 'erp-loyalty') { renderErpLoyaltyTab(data); }
            }

            // Pre-warm context when panel loads — avoids wait on first tab click
            fetchErpContext(null);

            // Lazy-load on tab click
            // Usar capture phase nativo: el click en erp-orders/finance/loyalty siempre dispara primero
            document.addEventListener('click', function (e) {
                var $btn = $(e.target).closest('.bv-right-tab[data-bv-tab^="erp-"]');
                if (!$btn.length) { return; }
                var tabName = $btn.data('bv-tab');
                if (erpCache) {
                    renderForTab(tabName, erpCache);
                    return;
                }
                showErpSkeleton(tabName);
                fetchErpContext(function (data) { renderForTab(tabName, data); });
            }, true);

            // Fetch ERP order detail (reutilizable para prefetch y click)
            function fetchErpOrderDetail(orderId, custId, onDone) {
                if (!orderId || !detailBase || !custId) { if (onDone) { onDone(null); } return; }
                if (erpDetailCache[orderId]) { if (onDone) { onDone(erpDetailCache[orderId]); } return; }
                if (erpDetailPrewarm[orderId]) { if (onDone) { erpDetailPrewarm[orderId].push(onDone); } return; }
                erpDetailPrewarm[orderId] = onDone ? [onDone] : [];
                $.ajax({
                    url: detailBase + custId + '/' + orderId,
                    method: 'GET',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    success: function (resp) {
                        if (resp.success && resp.data) { erpDetailCache[orderId] = resp.data; }
                        var cbs = (erpDetailPrewarm[orderId] || []).splice(0);
                        delete erpDetailPrewarm[orderId];
                        cbs.forEach(function (cb) { cb(resp.success ? resp.data : null); });
                    },
                    error: function () {
                        var cbs = (erpDetailPrewarm[orderId] || []).splice(0);
                        delete erpDetailPrewarm[orderId];
                        cbs.forEach(function (cb) { cb(null); });
                    },
                });
            }

            // Prefetch en hover — igual que PS
            $(document).on('mouseenter', '.rp3-erp-order', function () {
                var orderId = String($(this).data('order-id') || '');
                var custId  = String($(this).data('erp-customer-id') || $erpTab.data('erp-customer-id') || '');
                fetchErpOrderDetail(orderId, custId, null);
            });

            // ERP order card click → modal con datos ya cacheados o skeleton mientras carga
            document.addEventListener('click', function (e) {
                var $el = $(e.target).closest('.rp3-erp-order');
                if (!$el.length) { return; }

                var orderId = String($el.data('order-id') || '');
                var custId  = String($el.data('erp-customer-id') || $erpTab.data('erp-customer-id') || '');
                var ref     = $el.data('order-ref')    || '#—';
                var status  = $el.data('order-status') || '—';
                var date    = $el.data('order-date')   || '—';

                // Si ya está en caché: abrir con datos inmediatamente
                if (erpDetailCache[orderId]) {
                    showErpBasicModal(ref, status, date);
                    populateErpDetail(erpDetailCache[orderId]);
                    openOrderModal();
                    return;
                }

                // Si no: mostrar skeleton, abrir modal y cargar datos
                showErpBasicModal(ref, status, date);
                openOrderModal();
                fetchErpOrderDetail(orderId, custId, function (data) {
                    populateErpDetail(data);
                });
            }, true);

            function showErpBasicModal(ref, status, date) {
                var skLine = '<div class="bv-om-prod-skeleton"><div class="bv-sk-thumb"></div><div class="bv-sk-body"><div class="bv-sk-line w70"></div><div class="bv-sk-line w40"></div></div></div>';
                $('#bv-order-modal-chip').text(ref);
                $('#bv-order-modal-cust-name').text(($('.bv-cp-name-btn').first().text() || '—').trim());
                $('#bv-order-modal-order-id').text(ref);
                $('#bv-order-modal-reference').text(ref);
                $('#bv-order-modal-date').text(date || '—');
                $('#bv-order-modal-status').text(status || '—').attr('class', 'bv-ov-st ' + window.bvOrderStatusClass(status || ''));
                $('#bv-order-modal-payment').text('—');
                setAddressTab(null, null);
                $('#bv-order-modal-subtotal').text('—');
                $('#bv-order-modal-shipping-val').text('—');
                $('#bv-order-modal-total').text('—');
                $('#bv-order-modal-tax-row').addClass('bv-hidden');
                $('#bv-order-modal-discount-row').addClass('bv-hidden');
                $('#bv-order-modal-tracking-field').addClass('bv-hidden');
                $('#bv-order-modal-tracking-empty').removeClass('bv-hidden');
                $('#bv-order-modal-history-field').addClass('bv-hidden');
                $('#bv-order-modal-states-empty').removeClass('bv-hidden');
                $('#bv-order-modal-external-link').addClass('bv-hidden');
                $('[data-bv-modal-name="order"] [data-bv-om-tab]').removeClass('is-active');
                $('[data-bv-modal-name="order"] [data-bv-om-tab="info"]').addClass('is-active');
                $('[data-bv-modal-name="order"] [data-bv-om-panel]').removeClass('is-active');
                $('[data-bv-modal-name="order"] [data-bv-om-panel="info"]').addClass('is-active');
                $('#bv-order-modal-products').html(skLine + skLine);
                $('#bv-order-modal-products-count').text('');
            }

            function populateErpDetail(detail) {
                if (!detail) {
                    $('#bv-order-modal-products').html('<div class="bv-oc-empty"><i class="fas fa-box-open"></i><div class="title">Sin detalle</div></div>');
                    return;
                }
                var lines   = detail.lines || detail.lineas || detail.products || [];
                var total   = detail.total != null ? detail.total : (detail.importe || null);
                var payment = detail.payment_method || detail.formadepago || null;
                // Construir objetos de dirección desde los campos del ERP
                var rawAddr = detail.shipping_address || detail.address || detail.direccion || null;
                var erpShipping = rawAddr && typeof rawAddr === 'object'
                    ? rawAddr
                    : (rawAddr ? { address1: String(rawAddr), phone: detail.phone || detail.telefono || null } : null);
                var erpBilling = detail.billing_address || null;

                if (lines.length) {
                    var lHtml = '';
                    lines.forEach(function (l) {
                        var name  = l.name || l.descripcion || l.articulo || 'Artículo';
                        var qty   = parseInt(l.qty  || l.cantidad  || l.quantity || 1, 10);
                        var price = parseFloat(l.price || l.precio || l.importe  || 0);
                        var dto   = parseFloat(l.discount || l.dto || 0);
                        var linePrice = dto > 0 ? price * (1 - dto / 100) : price;
                        lHtml +=
                            '<div class="bv-om-prod-card">' +
                                '<div class="bv-om-pc-thumb"><i class="fas fa-box"></i></div>' +
                                '<div class="bv-om-pc-info">' +
                                    '<div class="bv-om-pc-name">' + esc(name) + '</div>' +
                                    '<div class="bv-om-pc-meta">\xd7' + qty +
                                        (price > 0 ? ' \xb7 ' + linePrice.toFixed(2) + ' €' : '') +
                                        (dto > 0 ? ' <span class="text-success">-' + dto + '%</span>' : '') +
                                    '</div>' +
                                '</div>' +
                                '<div class="bv-om-pc-price">' + (linePrice * qty).toFixed(2) + ' €</div>' +
                            '</div>';
                    });
                    $('#bv-order-modal-products').html(lHtml);
                    $('#bv-order-modal-products-count').text(lines.length + (lines.length === 1 ? ' artículo' : ' artículos'));
                } else {
                    $('#bv-order-modal-products').html('<div class="bv-oc-empty"><i class="fas fa-box-open"></i><div class="title">Sin productos registrados</div></div>');
                }
                if (total != null)   { $('#bv-order-modal-total').text(parseFloat(total).toFixed(2) + ' €'); }
                setAddressTab(erpShipping, erpBilling);
                if (payment)         { $('#bv-order-modal-payment').text(String(payment)); }
            }
        })();
    })();

    // ═══════════════════════════════════════════════════════════════
    // FEATURE: Preview conversación anterior (modal mv4-prev-thread)
    // ═══════════════════════════════════════════════════════════════
    (function () {
        function escHtml(s) {
            if (s == null) { return ''; }
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        var channelNames = {
            whatsapp: 'WhatsApp', facebook: 'Facebook', instagram: 'Instagram',
            email: 'Email', widget: 'Widget',
        };

        function buildPreviewBody(data) {
            var conv    = data.conversation;
            var msgs    = data.messages || [];
            var chName  = channelNames[conv.channel] || conv.channel;
            var chColor = {
                whatsapp: '#25d366', facebook: '#1877f2', instagram: '#e4405f',
                email: '#52525b', widget: '#6366f1',
            }[conv.channel] || '#8c929d';

            var isOpen  = conv.status && conv.status.is_open;
            var stColor = isOpen ? '#16a34a' : '#8c929d';
            var stName  = (conv.status && conv.status.name) || 'Abierta';

            // Meta pills
            var pills = '';
            pills += '<span class="mv4-pill"><span class="d" style="background:' + chColor + '"></span>' + escHtml(chName) + '</span>';
            pills += '<span class="mv4-pill"><span class="d" style="background:' + stColor + '"></span>' + escHtml(stName) + '</span>';
            if (conv.assignee) {
                pills += '<span class="mv4-pill">' + escHtml(conv.assignee.name) + '</span>';
            }
            pills += '<span class="mv4-pill mono">' + escHtml(conv.created_at) + '</span>';

            // Thread messages
            var thread = '';
            if (!msgs.length) {
                thread = '<div style="text-align:center;color:var(--bv-text-muted,#8c929d);font-size:12px;padding:20px 0">Sin mensajes</div>';
            } else {
                msgs.forEach(function (m) {
                    var dir = m.is_from_agent ? 'out' : 'in';
                    thread += '<div class="mv4-pmsg ' + dir + '">' +
                        '<div class="bubble">' +
                        (m.is_from_agent && m.sender_name ? '<div class="who">' + escHtml(m.sender_name) + '</div>' : '') +
                        '<div class="body">' + escHtml(m.body) + '</div>' +
                        '<div class="t">' + escHtml(m.created_at) + '</div>' +
                        '</div></div>';
                });
            }

            return '<div class="mv4-prev-head"><div class="mv4-prev-meta">' + pills + '</div></div>' +
                   '<div class="mv4-prev-thread">' + thread + '</div>';
        }

        function openPrevConvModal() {
            var $modal = $('[data-bv-modal-name="preview-conv"]');
            $modal.addClass('on');
            $('body').css('overflow', 'hidden');
        }

        $(document).on('click', '.rp3-prev', function () {
            var $el      = $(this);
            var url      = $el.data('preview-url');
            var openUrl  = $el.data('open-url');
            var subject  = $el.data('subject') || 'Conversación';

            // Reset modal state
            $('#prev-conv-subject').text(subject);
            $('#prev-conv-open-btn').attr('href', openUrl || '#');
            $('#prev-conv-body').html('<div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');

            // Open modal immediately (shows loading)
            openPrevConvModal();

            if (!url) { return; }

            $.get(url)
                .done(function (data) {
                    $('#prev-conv-subject').text(data.conversation.subject || subject);
                    $('#prev-conv-open-btn').attr('href', data.open_url || openUrl || '#');
                    $('#prev-conv-body').html(buildPreviewBody(data));
                })
                .fail(function () {
                    $('#prev-conv-body').html(
                        '<div class="bv-tab-empty"><i class="fas fa-triangle-exclamation"></i>' +
                        '<div class="bv-tab-empty-title">Error al cargar</div>' +
                        '<div class="bv-tab-empty-sub">No se pudo obtener el historial de esta conversación</div></div>'
                    );
                });
        });

        // Exportar: abre la URL de la conversación en nueva pestaña (sin backend de PDF)
        $(document).on('click', '#prev-conv-export-btn', function () {
            var href = $('#prev-conv-open-btn').attr('href');
            if (href && href !== '#') { window.open(href, '_blank'); }
        });
    })();

    // ═══════════════════════════════════════════════════════════════
    // FEATURE: Notificaciones push en tiempo real (Helpdesk)
    // Sondeo cada 15s + listener del evento 'notification-received'
    // Cuando Reverb esté operativo, el evento se dispara desde Echo.
    // Mientras tanto, el sondeo actúa como fallback.
    // ═══════════════════════════════════════════════════════════════
    (function () {
        var HD_TYPES = {
            helpdesk_conversation_assigned: { level: 'info',    refresh: true  },
            helpdesk_new_conversation:      { level: 'info',    refresh: true  },
            helpdesk_message_received:      { level: 'info',    refresh: true  },
            helpdesk_mention:               { level: 'warning', refresh: false },
            helpdesk_status_changed:        { level: 'info',    refresh: true  },
            helpdesk_escalation:            { level: 'error',   refresh: false },
        };

        var seenDbIds   = new Set();
        var seenEcho    = {};
        var initialized = false;

        function echoKey(n) { return (n.type || '') + '_' + (n.entity_id || ''); }

        function wasRecentlyShownByEcho(n) {
            var t = seenEcho[echoKey(n)];
            return !!(t && (Date.now() - t) < 60000);
        }

        function markEcho(n) { seenEcho[echoKey(n)] = Date.now(); }

        function normalizeUrl(raw) {
            if (!raw) { return null; }
            try {
                var parsed = new URL(raw);
                return window.location.origin + parsed.pathname + (parsed.search || '');
            } catch (_e) { return raw; }
        }

        function showNotification(n) {
            var meta  = HD_TYPES[n.type];
            var url   = normalizeUrl(n.action_url);
            var title = n.title   || 'Helpdesk';
            var msg   = n.message || '';

            var opts = { closeButton: true, tapToDismiss: true, timeOut: 8000, extendedTimeOut: 2000 };
            if (url) {
                opts.onclick = function () { window.location.href = url; };
            }

            if (window.toastr && toastr[meta.level]) {
                toastr[meta.level](msg, title, opts);
            }

            if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
                try {
                    var push = new window.Notification(title, { body: msg, icon: '/favicon.ico', tag: 'hd-' + (n.id || echoKey(n)) });
                    if (url) {
                        push.onclick = function () { window.focus(); window.location.href = url; push.close(); };
                    }
                } catch (_e) {}
            }

            if (meta.refresh && typeof window.applyInboxFilters === 'function') {
                setTimeout(function () { window.applyInboxFilters({}); }, 1000);
            }
        }

        // Listener para evento global (Echo / Reverb cuando esté disponible)
        window.addEventListener('notification-received', function (e) {
            var n = e.detail || {};
            if (!n.type || !HD_TYPES[n.type]) { return; }
            if (wasRecentlyShownByEcho(n)) { return; }
            markEcho(n);
            if (n.id) { seenDbIds.add(n.id); }
            showNotification(n);
        });

        // Timestamp de cuándo cargó la página — solo mostramos toasts para notificaciones más nuevas
        var pageLoadTs = null;

        function poll() {
            $.ajax({
                url: '/api/notifications',
                method: 'GET',
                dataType: 'json',
                data: { unread: 1, limit: 20 },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                xhrFields: { withCredentials: true },
            }).done(function (resp) {
                var list = resp.notifications || [];

                if (!initialized) {
                    // Primera carga: registrar todos los IDs actuales sin mostrar toasts
                    list.forEach(function (n) { seenDbIds.add(n.id); });
                    initialized = true;
                    return;
                }

                list.forEach(function (n) {
                    if (seenDbIds.has(n.id)) { return; }
                    seenDbIds.add(n.id);
                    if (!HD_TYPES[n.type]) { return; }
                    // Solo mostrar si la notificación es posterior a la carga de la página
                    if (pageLoadTs && n.created_at_full && new Date(n.created_at_full) < pageLoadTs) { return; }
                    if (wasRecentlyShownByEcho(n)) { return; }
                    showNotification(n);
                });
            });
        }

        function echoIsConnected() {
            try {
                var connector = window.Echo && window.Echo.connector;
                var conn = connector && connector.pusher && connector.pusher.connection;
                return !!(conn && conn.state === 'connected');
            } catch (e) {
                return false;
            }
        }

        // FE-06: el polling solo actúa como red de seguridad. Si Echo/Reverb está
        // conectado (realtime activo) espaciamos a 60s para no duplicar eventos;
        // si no hay conexión disponible mantenemos el fallback cada 15s.
        function scheduleNextPoll() {
            var delay = echoIsConnected() ? 60000 : 15000;
            setTimeout(function () {
                poll();
                scheduleNextPoll();
            }, delay);
        }

        $(function () {
            pageLoadTs = new Date();
            poll();
            scheduleNextPoll();
        });
    })();

})(jQuery);
